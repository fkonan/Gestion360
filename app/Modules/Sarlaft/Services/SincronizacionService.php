<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\ListaVinculante;
use App\Modules\Sarlaft\Models\RegistroLista;
use App\Modules\Sarlaft\Models\SincronizacionLog;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SincronizacionService
{
    public function sincronizar(ListaVinculante $lista): SincronizacionLog
    {
        $inicio = microtime(true);
        $stats = ['procesados' => 0, 'nuevos' => 0, 'actualizados' => 0, 'eliminados' => 0];

        try {
            $config = $this->obtenerConfigLista($lista);

            if (! $config) {
                return $this->registrarLog($lista, 'fallido', $stats, $inicio, 'Configuración de lista no encontrada.');
            }

            $response = $this->httpClient()
                ->timeout(300)
                ->get($config['url']);

            if (! $response->successful()) {
                return $this->registrarLog($lista, 'fallido', $stats, $inicio, "HTTP {$response->status()}: Error descargando lista.");
            }

            $registros = $this->parsearXml($response->body(), $config);
            $stats['procesados'] = count($registros);

            // Crear el log primero para tener el ID disponible
            $estado = ($stats['procesados'] > 0) ? 'exitoso' : 'parcial';
            $log = $this->registrarLog($lista, $estado, $stats, $inicio);

            // Obtener referencias actuales activas de esta lista
            $referenciasActuales = RegistroLista::where('lista_id', $lista->id)
                ->whereNull('deleted_at')
                ->pluck('referencia_externa')
                ->filter()
                ->toArray();

            $referenciasEntrantes = collect($registros)
                ->pluck('referencia_externa')
                ->filter()
                ->toArray();

            // Detectar eliminados: estaban activos pero ya no vienen en la fuente
            $referenciasEliminadas = array_diff($referenciasActuales, $referenciasEntrantes);

            if (count($referenciasEliminadas) > 0) {
                RegistroLista::where('lista_id', $lista->id)
                    ->whereIn('referencia_externa', $referenciasEliminadas)
                    ->whereNull('deleted_at')
                    ->update([
                        'estado' => 'removido',
                        'novedad' => 'salida',
                        'sincronizacion_log_id' => $log->id,
                        'deleted_at' => now(),
                    ]);

                $stats['eliminados'] = count($referenciasEliminadas);
            }

            // Upsert por lotes para mejor rendimiento con listas grandes
            $chunks = array_chunk($registros, 500);
            foreach ($chunks as $chunk) {
                foreach ($chunk as $registro) {
                    $existente = RegistroLista::withTrashed()
                        ->where('lista_id', $lista->id)
                        ->where('referencia_externa', $registro['referencia_externa'])
                        ->first();

                    if ($existente) {
                        $cambio = $this->detectarCambios($existente, $registro);

                        if ($existente->trashed()) {
                            // Re-ingreso: estaba eliminado y vuelve a aparecer
                            $existente->restore();
                            $existente->update([
                                ...$registro,
                                'estado' => 'activo',
                                'novedad' => 'ingreso',
                                'sincronizacion_log_id' => $log->id,
                            ]);
                            $stats['nuevos']++;
                        } elseif ($cambio) {
                            $existente->update([
                                ...$registro,
                                'novedad' => 'actualizado',
                                'sincronizacion_log_id' => $log->id,
                            ]);
                            $stats['actualizados']++;
                        } else {
                            // Sin cambios, solo marcar como procesado
                            $existente->update([
                                'novedad' => 'sin_cambio',
                                'sincronizacion_log_id' => $log->id,
                            ]);
                        }
                    } else {
                        RegistroLista::create([
                            ...$registro,
                            'lista_id' => $lista->id,
                            'novedad' => 'ingreso',
                            'sincronizacion_log_id' => $log->id,
                        ]);
                        $stats['nuevos']++;
                    }
                }
            }

            $lista->update(['ultima_sincronizacion' => now()]);

            // Actualizar el log con las estadísticas finales
            $log->update([
                'registros_nuevos' => $stats['nuevos'],
                'registros_actualizados' => $stats['actualizados'],
                'registros_eliminados' => $stats['eliminados'],
                'duracion_segundos' => (int) (microtime(true) - $inicio),
            ]);

            return $log->fresh();
        } catch (\Throwable $e) {
            Log::error("Sincronización fallida para lista {$lista->id}: {$e->getMessage()}");

            return $this->registrarLog($lista, 'fallido', $stats, $inicio, $e->getMessage());
        }
    }

    /**
     * Consultar entidad individual via OFAC SLS API.
     *
     * @return array<string, mixed>|null
     */
    public function consultarEntidadOfac(int $entityId): ?array
    {
        try {
            $baseUrl = config('listas.ofac_api_base');
            $response = $this->httpClient()
                ->timeout(30)
                ->accept('application/xml')
                ->get("{$baseUrl}/entities/{$entityId}");

            if (! $response->successful()) {
                return null;
            }

            $xml = simplexml_load_string($response->body());

            if ($xml === false || ! isset($xml->entity)) {
                return null;
            }

            $entity = $xml->entity;

            return $this->parsearEntidadSls($entity);
        } catch (\Throwable $e) {
            Log::warning("Error consultando entidad OFAC {$entityId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Obtener últimos cambios de OFAC.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerUltimosCambiosOfac(): ?array
    {
        try {
            $baseUrl = config('listas.ofac_api_base');
            $response = $this->httpClient()
                ->timeout(30)
                ->get("{$baseUrl}/changes/latest");

            if (! $response->successful()) {
                return null;
            }

            return ['body' => $response->body(), 'status' => $response->status()];
        } catch (\Throwable $e) {
            Log::warning("Error consultando cambios OFAC: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Obtener historial de publicaciones OFAC por año/mes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerHistorialOfac(int $year, ?int $month = null, ?int $day = null): array
    {
        try {
            $baseUrl = config('listas.ofac_api_base');
            $url = "{$baseUrl}/changes/history/{$year}";

            if ($month !== null) {
                $url .= '/'.str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                if ($day !== null) {
                    $url .= '/'.str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                }
            }

            $response = $this->httpClient()
                ->timeout(30)
                ->get($url);

            if (! $response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::warning("Error consultando historial OFAC: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Obtener listas de sanciones disponibles en OFAC.
     *
     * @return array<int, string>
     */
    public function obtenerListasOfac(): array
    {
        try {
            $baseUrl = config('listas.ofac_api_base');
            $response = $this->httpClient()
                ->timeout(30)
                ->get("{$baseUrl}/sanctions-lists");

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Obtener programas de sanciones disponibles en OFAC.
     *
     * @return array<int, string>
     */
    public function obtenerProgramasOfac(): array
    {
        try {
            $baseUrl = config('listas.ofac_api_base');
            $response = $this->httpClient()
                ->timeout(30)
                ->get("{$baseUrl}/sanctions-programs");

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function httpClient(): PendingRequest
    {
        $client = Http::withHeaders([
            'Accept' => '*/*',
            'User-Agent' => 'SCLV-SARLAFT/1.0',
        ]);

        if (! config('listas.verificar_ssl', true)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function obtenerConfigLista(ListaVinculante $lista): ?array
    {
        $listas = config('listas');
        $nombreLista = mb_strtolower(trim($lista->nombre));

        foreach (['onu', 'ofac_sdn', 'ofac_consolidated'] as $key) {
            if (! isset($listas[$key]['nombre'])) {
                continue;
            }

            $nombreConfig = mb_strtolower(trim((string) $listas[$key]['nombre']));

            if ($nombreConfig === $nombreLista) {
                return $listas[$key];
            }
        }

        if ($lista->url_fuente) {
            $url = mb_strtolower($lista->url_fuente);
            $parser = 'ofac';

            if (str_contains($url, 'scsanctions.un.org')) {
                $parser = 'onu';
            } elseif (str_contains($url, 'europa.eu')) {
                $parser = 'eu';
            }

            return [
                'url' => $lista->url_fuente,
                'formato' => 'xml',
                'parser' => $parser,
                'nombre' => $lista->nombre,
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    private function parsearXml(string $xml, array $config): array
    {
        $doc = simplexml_load_string($xml);

        if ($doc === false) {
            throw new \RuntimeException('Error parseando XML de la lista.');
        }

        $parser = $config['parser'] ?? 'ofac';

        return match ($parser) {
            'onu' => $this->parsearOnuXml($doc),
            'ofac' => $this->parsearOfacXml($doc),
            'eu' => $this->parsearEuXml($doc),
            default => throw new \RuntimeException("Parser desconocido: {$parser}"),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parsearOnuXml(\SimpleXMLElement $doc): array
    {
        $registros = [];

        $individuos = $doc->xpath('//INDIVIDUAL') ?: [];
        foreach ($individuos as $ind) {
            $nombres = trim(
                ((string) ($ind->FIRST_NAME ?? '')).' '
                .((string) ($ind->SECOND_NAME ?? '')).' '
                .((string) ($ind->THIRD_NAME ?? ''))
            );

            $alias = [];
            foreach ($ind->INDIVIDUAL_ALIAS ?? [] as $a) {
                $aliasNombre = trim((string) ($a->ALIAS_NAME ?? ''));
                if ($aliasNombre !== '') {
                    $alias[] = $aliasNombre;
                }
            }

            $registros[] = [
                'tipo_entidad' => 'persona',
                'nombres' => $nombres ?: 'Sin nombre',
                'alias' => $alias ?: null,
                'fecha_nacimiento' => $this->parsearFechaOnu($ind),
                'pais' => (string) ($ind->NATIONALITY->VALUE ?? null) ?: null,
                'motivo' => (string) ($ind->COMMENTS1 ?? null) ?: null,
                'fecha_inclusion' => $this->normalizarFecha((string) ($ind->LISTED_ON ?? '')),
                'referencia_externa' => (string) ($ind->DATAID ?? uniqid('onu_ind_')),
                'estado' => 'activo',
            ];
        }

        $entidades = $doc->xpath('//ENTITY') ?: [];
        foreach ($entidades as $ent) {
            $alias = [];
            foreach ($ent->ENTITY_ALIAS ?? [] as $a) {
                $aliasNombre = trim((string) ($a->ALIAS_NAME ?? ''));
                if ($aliasNombre !== '') {
                    $alias[] = $aliasNombre;
                }
            }

            $registros[] = [
                'tipo_entidad' => 'organizacion',
                'nombres' => (string) ($ent->FIRST_NAME ?? 'Sin nombre'),
                'alias' => $alias ?: null,
                'fecha_nacimiento' => null,
                'pais' => null,
                'motivo' => (string) ($ent->COMMENTS1 ?? null) ?: null,
                'fecha_inclusion' => $this->normalizarFecha((string) ($ent->LISTED_ON ?? '')),
                'referencia_externa' => (string) ($ent->DATAID ?? uniqid('onu_ent_')),
                'estado' => 'activo',
            ];
        }

        return $registros;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parsearOfacXml(\SimpleXMLElement $doc): array
    {
        $registros = [];

        foreach ($doc->sdnEntry ?? [] as $entry) {
            $tipo = strtolower((string) ($entry->sdnType ?? 'individual'));
            $tipoEntidad = ($tipo === 'individual') ? 'persona' : 'organizacion';

            $firstName = trim((string) ($entry->firstName ?? ''));
            $lastName = trim((string) ($entry->lastName ?? ''));
            $nombres = trim("{$firstName} {$lastName}");

            if ($nombres === '') {
                $nombres = $lastName !== '' ? $lastName : 'Sin nombre';
            }

            $alias = [];
            foreach ($entry->akaList->aka ?? [] as $aka) {
                $akaNombre = trim(
                    ((string) ($aka->firstName ?? '')).' '
                    .((string) ($aka->lastName ?? ''))
                );
                if ($akaNombre !== '') {
                    $alias[] = $akaNombre;
                }
            }

            $pais = null;
            foreach ($entry->addressList->address ?? [] as $addr) {
                $pais = (string) ($addr->country ?? null) ?: null;
                if ($pais) {
                    break;
                }
            }

            $fechaNacimiento = null;
            foreach ($entry->dateOfBirthList->dateOfBirthItem ?? [] as $dob) {
                $fechaNacimiento = (string) ($dob->dateOfBirth ?? null) ?: null;
                if ($fechaNacimiento) {
                    break;
                }
            }

            $identificacion = null;
            $tipoIdentificacion = null;

            // Tipos de ID excluidos (features, no identificaciones reales)
            $tiposExcluidos = [
                'Gender', 'Birthdate', 'Additional Sanctions Information',
                'Secondary sanctions risk:', 'Nationality', 'Place of Birth',
            ];

            foreach ($entry->idList->id ?? [] as $id) {
                $idType = (string) ($id->idType ?? '');
                $idNumber = (string) ($id->idNumber ?? '');

                // Filtrar IDs válidos: no vacíos, no guiones, longitud razonable, tipo no excluido
                if ($idNumber !== ''
                    && $idNumber !== '-'
                    && strlen($idNumber) < 150
                    && ! in_array($idType, $tiposExcluidos)
                    && ! str_contains($idNumber, 'Executive Order')
                ) {
                    $identificacion = $idNumber;
                    $tipoIdentificacion = $idType;
                    break;
                }
            }

            $registros[] = [
                'tipo_entidad' => $tipoEntidad,
                'identificacion' => $identificacion,
                'tipo_identificacion' => $tipoIdentificacion,
                'nombres' => $nombres,
                'alias' => $alias ?: null,
                'fecha_nacimiento' => $this->normalizarFecha($fechaNacimiento),
                'pais' => $pais,
                'motivo' => (string) ($entry->remarks ?? null) ?: null,
                'fecha_inclusion' => null,
                'referencia_externa' => (string) ($entry->uid ?? uniqid('ofac_')),
                'estado' => 'activo',
            ];
        }

        return $registros;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parsearEuXml(\SimpleXMLElement $doc): array
    {
        $registros = [];

        foreach ($doc->sanctionEntity ?? [] as $entity) {
            $subjectType = (string) ($entity->subjectType['code'] ?? 'person');
            $tipoEntidad = ($subjectType === 'person') ? 'persona' : 'organizacion';

            $nombres = '';
            $alias = [];

            foreach ($entity->nameAlias ?? [] as $nameAlias) {
                $wholeName = trim((string) ($nameAlias->wholeName ?? ''));
                $isPrimary = ((string) ($nameAlias['regulationLanguage'] ?? '')) === 'en';

                if ($isPrimary && $nombres === '') {
                    $nombres = $wholeName;
                } elseif ($wholeName !== '' && $wholeName !== $nombres) {
                    $alias[] = $wholeName;
                }
            }

            if ($nombres === '') {
                $nombres = 'Sin nombre';
            }

            $fechaNacimiento = null;
            foreach ($entity->birthdate ?? [] as $bd) {
                $fechaNacimiento = (string) ($bd['birthdate'] ?? null) ?: null;
                if ($fechaNacimiento) {
                    break;
                }
            }

            $pais = null;
            foreach ($entity->citizenship ?? [] as $citizenship) {
                $pais = (string) ($citizenship['countryDescription'] ?? null) ?: null;
                if ($pais) {
                    break;
                }
            }

            $identificacion = null;
            $tipoIdentificacion = null;
            foreach ($entity->identification ?? [] as $id) {
                $idNumber = (string) ($id['number'] ?? '');
                $idType = (string) ($id['identificationTypeDescription'] ?? '');

                if ($idNumber !== '' && strlen($idNumber) < 150) {
                    $identificacion = $idNumber;
                    $tipoIdentificacion = $idType;
                    break;
                }
            }

            $motivo = (string) ($entity->remark ?? null) ?: null;
            $euRefNum = (string) ($entity['euReferenceNumber'] ?? uniqid('eu_'));

            $registros[] = [
                'tipo_entidad' => $tipoEntidad,
                'identificacion' => $identificacion,
                'tipo_identificacion' => $tipoIdentificacion,
                'nombres' => $nombres,
                'alias' => $alias ?: null,
                'fecha_nacimiento' => $this->normalizarFecha($fechaNacimiento),
                'pais' => $pais,
                'motivo' => $motivo,
                'fecha_inclusion' => null,
                'referencia_externa' => $euRefNum,
                'estado' => 'activo',
            ];
        }

        return $registros;
    }

    /**
     * Parsear entidad individual de la SLS API (formato enhanced).
     *
     * @return array<string, mixed>
     */
    private function parsearEntidadSls(\SimpleXMLElement $entity): array
    {
        $tipo = strtolower((string) ($entity->generalInfo->entityType ?? 'Individual'));
        $tipoEntidad = str_contains($tipo, 'individual') ? 'persona' : 'organizacion';

        $nombres = '';
        $alias = [];
        foreach ($entity->names->name ?? [] as $name) {
            $isPrimary = ((string) ($name->isPrimary ?? 'false')) === 'true';
            foreach ($name->translations->translation ?? [] as $t) {
                $fullName = (string) ($t->formattedFullName ?? '');
                if ($isPrimary && $nombres === '') {
                    $nombres = $fullName;
                } elseif (! $isPrimary && $fullName !== '') {
                    $alias[] = $fullName;
                }
            }
        }

        $pais = null;
        foreach ($entity->addresses->address ?? [] as $addr) {
            $pais = (string) ($addr->country ?? null) ?: null;
            if ($pais) {
                break;
            }
        }

        $fechaNacimiento = null;
        foreach ($entity->features->feature ?? [] as $feature) {
            $featureType = (string) ($feature->type ?? '');
            if (str_contains(strtolower($featureType), 'birthdate')) {
                $fechaNacimiento = (string) ($feature->valueDate->fromDateBegin ?? null) ?: null;
                if ($fechaNacimiento) {
                    break;
                }
            }
        }

        $listas = [];
        foreach ($entity->sanctionsLists->sanctionsList ?? [] as $sl) {
            $listas[] = trim((string) $sl);
        }

        $programas = [];
        foreach ($entity->sanctionsPrograms->sanctionsProgram ?? [] as $sp) {
            $programas[] = trim((string) $sp);
        }

        return [
            'entity_id' => (string) ($entity['id'] ?? ''),
            'tipo_entidad' => $tipoEntidad,
            'nombres' => $nombres ?: 'Sin nombre',
            'alias' => $alias ?: null,
            'pais' => $pais,
            'fecha_nacimiento' => $fechaNacimiento,
            'listas' => $listas,
            'programas' => $programas,
        ];
    }

    private function parsearFechaOnu(\SimpleXMLElement $ind): ?string
    {
        $dob = $ind->INDIVIDUAL_DATE_OF_BIRTH->DATE ?? null;

        return $dob ? $this->normalizarFecha((string) $dob) : null;
    }

    private function normalizarFecha(?string $fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        try {
            $parsed = \Carbon\Carbon::parse($fecha);

            return $parsed->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function registrarLog(
        ListaVinculante $lista,
        string $estado,
        array $stats,
        float $inicio,
        ?string $error = null,
    ): SincronizacionLog {
        return SincronizacionLog::create([
            'lista_id' => $lista->id,
            'estado' => $estado,
            'registros_procesados' => $stats['procesados'],
            'registros_nuevos' => $stats['nuevos'],
            'registros_actualizados' => $stats['actualizados'],
            'registros_eliminados' => $stats['eliminados'] ?? 0,
            'error_mensaje' => $error,
            'duracion_segundos' => (int) (microtime(true) - $inicio),
            'created_at' => now(),
        ]);
    }

    /**
     * Compara campos relevantes para detectar si un registro cambió.
     *
     * @param  array<string, mixed>  $nuevos
     */
    private function detectarCambios(RegistroLista $existente, array $nuevos): bool
    {
        $camposComparar = ['nombres', 'identificacion', 'tipo_identificacion', 'pais', 'motivo', 'tipo_entidad'];

        foreach ($camposComparar as $campo) {
            if (! array_key_exists($campo, $nuevos)) {
                continue;
            }

            $valorExistente = $existente->getAttribute($campo);
            $valorNuevo = $nuevos[$campo];

            if ((string) $valorExistente !== (string) ($valorNuevo ?? '')) {
                return true;
            }
        }

        // Comparar alias (es JSON/array)
        if (array_key_exists('alias', $nuevos)) {
            $aliasExistente = $existente->alias ?? [];
            $aliasNuevo = $nuevos['alias'] ?? [];

            if (json_encode($aliasExistente) !== json_encode($aliasNuevo)) {
                return true;
            }
        }

        return false;
    }
}
