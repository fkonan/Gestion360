<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\IntentoOperacion;
use App\Modules\Sarlaft\Models\SistemaConsumidor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntentoOperacionService
{
    /**
     * Procesa un intento reportado via Push (el sistema externo nos llama).
     *
     * @param  array<string, mixed>  $datos
     */
    public function registrarPush(array $datos, SistemaConsumidor $sistema, string $ip): IntentoOperacion
    {
        return DB::connection('mysql-sarlaft')->transaction(function () use ($datos, $sistema, $ip): IntentoOperacion {
            $referencia = $this->normalizarReferencia($datos['referencia'] ?? null);

            $intentoExistente = $this->buscarIntentoExistente(
                sistema: $sistema,
                modoIntegracion: 'push',
                referencia: $referencia,
            );

            if ($intentoExistente !== null) {
                return $intentoExistente->load(['alertas']);
            }

            $intento = $this->crearIntento(
                datos: $datos,
                sistema: $sistema,
                modoIntegracion: 'push',
                ipOrigen: $ip,
                sistemaOrigenExterno: null,
            );

            $this->generarAlerta($intento);

            return $intento->load(['alertas']);
        });
    }

    /**
     * Ejecuta el proceso Pull para un sistema consumidor:
     * consulta su endpoint y registra los intentos encontrados.
     */
    public function ejecutarPull(
        SistemaConsumidor $sistema,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
    ): int {
        if (! $sistema->pull_endpoint) {
            return 0;
        }

        $filtroFechaDesde = $fechaDesde ?? now()->subDay()->toIso8601String();
        $filtroFechaHasta = $fechaHasta ?? now()->toIso8601String();

        try {
            $response = Http::withToken($sistema->pull_token ?? '')
                ->timeout(30)
                ->get($sistema->pull_endpoint, [
                    'FechaDesde' => $filtroFechaDesde,
                    'FechaHasta' => $filtroFechaHasta,
                    'fecha_desde' => $filtroFechaDesde,
                    'fecha_hasta' => $filtroFechaHasta,
                ]);

            if (! $response->successful()) {
                Log::warning('SARLAFT Pull: respuesta no exitosa', [
                    'sistema' => $sistema->codigo,
                    'status' => $response->status(),
                ]);

                return 0;
            }

            $intentos = $response->json('data') ?? $response->json() ?? [];
            $registrados = 0;

            foreach ($intentos as $item) {
                if (! is_array($item) || ! $this->tieneCamposMinimos($item)) {
                    continue;
                }

                DB::connection('mysql-sarlaft')->transaction(function () use ($item, $sistema, &$registrados): void {
                    $referencia = $this->normalizarReferencia($item['referencia'] ?? null);

                    $intentoExistente = $this->buscarIntentoExistente(
                        sistema: $sistema,
                        modoIntegracion: 'pull',
                        referencia: $referencia,
                    );

                    if ($intentoExistente !== null) {
                        return;
                    }

                    $intento = $this->crearIntento(
                        datos: $item,
                        sistema: $sistema,
                        modoIntegracion: 'pull',
                        ipOrigen: null,
                        sistemaOrigenExterno: $item['sistema_origen'] ?? null,
                    );

                    $this->generarAlerta($intento);
                    $registrados++;
                });
            }

            return $registrados;
        } catch (\Throwable $e) {
            Log::error('SARLAFT Pull: error al consultar sistema', [
                'sistema' => $sistema->codigo,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Lee directamente la tabla de operaciones de un sistema inhouse (modo db)
     * y registra los intentos encontrados. No usa HTTP: consulta la conexion
     * configurada en el sistema consumidor.
     */
    public function ejecutarLecturaDb(
        SistemaConsumidor $sistema,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
    ): int {
        if (! $sistema->db_conexion || ! $sistema->db_tabla) {
            return 0;
        }

        $filtroFechaDesde = $fechaDesde
            ?? $sistema->db_ultima_lectura_at?->toDateTimeString()
            ?? now()->subDay()->toDateTimeString();
        $filtroFechaHasta = $fechaHasta ?? now()->toDateTimeString();

        try {
            $query = DB::connection($sistema->db_conexion)
                ->table($sistema->db_tabla)
                ->whereBetween('CREATED_AT', [$filtroFechaDesde, $filtroFechaHasta]);

            if (is_string($sistema->db_filtro_sistema_origen) && trim($sistema->db_filtro_sistema_origen) !== '') {
                $query->where('SISTEMA_ORIGEN', trim($sistema->db_filtro_sistema_origen));
            }

            $filas = $query->orderBy('CREATED_AT')->get();
            $registrados = 0;

            foreach ($filas as $fila) {
                $datos = $this->mapearFilaDb((array) $fila);

                if (! $this->tieneCamposMinimos($datos)) {
                    continue;
                }

                DB::connection('mysql-sarlaft')->transaction(function () use ($datos, $sistema, &$registrados): void {
                    $referencia = $this->normalizarReferencia($datos['referencia'] ?? null);

                    $intentoExistente = $this->buscarIntentoExistente(
                        sistema: $sistema,
                        modoIntegracion: 'db',
                        referencia: $referencia,
                    );

                    if ($intentoExistente !== null) {
                        return;
                    }

                    $intento = $this->crearIntento(
                        datos: $datos,
                        sistema: $sistema,
                        modoIntegracion: 'db',
                        ipOrigen: null,
                        sistemaOrigenExterno: $datos['sistema_origen'] ?? null,
                    );

                    $this->generarAlerta($intento);
                    $registrados++;
                });
            }

            $sistema->forceFill(['db_ultima_lectura_at' => now()])->save();

            return $registrados;
        } catch (\Throwable $e) {
            Log::error('SARLAFT Lectura DB: error al leer tabla de operaciones', [
                'sistema' => $sistema->codigo,
                'conexion' => $sistema->db_conexion,
                'tabla' => $sistema->db_tabla,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Normaliza una fila cruda de la tabla Oracle (columnas en mayuscula) al
     * array de datos que espera crearIntento(). Los CLOB se castean a string.
     *
     * @param  array<string, mixed>  $fila
     * @return array<string, mixed>
     */
    private function mapearFilaDb(array $fila): array
    {
        $get = static function (string $columna) use ($fila): mixed {
            return $fila[$columna] ?? $fila[strtolower($columna)] ?? null;
        };

        $descripcion = $get('DESCRIPCION');
        $contexto = $get('CONTEXTO');
        $contextoTexto = is_string($contexto) ? trim($contexto) : null;
        $contextoDecodificado = $contextoTexto !== null && $contextoTexto !== ''
            ? json_decode($contextoTexto, true)
            : null;

        return [
            'tipo_documento' => $get('TIPO_DOCUMENTO'),
            'numero_documento' => $get('NUMERO_DOCUMENTO'),
            'nombre' => $get('NOMBRE'),
            'tipo_lista' => $get('TIPO_LISTA'),
            'lista_nombre' => $get('LISTA_NOMBRE'),
            'tipo_operacion' => $get('TIPO_OPERACION'),
            'referencia' => $get('REFERENCIA'),
            'monto' => $get('MONTO'),
            'descripcion' => is_string($descripcion) ? $descripcion : null,
            'contexto' => is_array($contextoDecodificado) ? $contextoDecodificado : null,
            'created_at' => $get('CREATED_AT'),
            'sistema_origen' => $get('SISTEMA_ORIGEN'),
        ];
    }

    private function crearIntento(
        array $datos,
        SistemaConsumidor $sistema,
        string $modoIntegracion,
        ?string $ipOrigen,
        mixed $sistemaOrigenExterno,
    ): IntentoOperacion {
        $createdAt = $this->resolverFechaCreacion($datos['created_at'] ?? null);
        $referencia = $this->normalizarReferencia($datos['referencia'] ?? null);

        return IntentoOperacion::create([
            'sistema_id' => $sistema->id,
            'sistema_origen' => $this->resolverSistemaOrigen($sistema, $sistemaOrigenExterno),
            'modo_integracion' => $modoIntegracion,
            'tipo_documento' => trim((string) ($datos['tipo_documento'] ?? '')),
            'numero_documento' => trim((string) ($datos['numero_documento'] ?? '')),
            'nombre' => $this->normalizarTexto($datos['nombre'] ?? null),
            'tipo_lista' => trim((string) ($datos['tipo_lista'] ?? '')),
            'lista_nombre' => trim((string) ($datos['lista_nombre'] ?? '')),
            'tipo_operacion' => trim((string) ($datos['tipo_operacion'] ?? '')),
            'referencia' => $referencia,
            'referencia_externa' => $referencia,
            'fecha_operacion' => $createdAt ?? now(),
            'monto' => $datos['monto'] ?? null,
            'descripcion' => $this->normalizarTexto($datos['descripcion'] ?? null),
            'contexto' => $this->normalizarContexto($datos['contexto'] ?? null),
            'ip_origen' => $ipOrigen,
            'created_at' => $createdAt,
        ]);
    }

    private function generarAlerta(IntentoOperacion $intento): void
    {
        $tipoLista = strtolower(trim((string) $intento->tipo_lista));
        $nivelRiesgo = str_contains($tipoLista, 'vinculante') ? 'vinculante' : 'restrictiva';

        Alerta::create([
            'intento_id' => $intento->id,
            'tipo' => 'intento_operacion_'.$intento->modo_integracion,
            'nivel_riesgo' => $nivelRiesgo,
            'estado' => 'pendiente',
            'tipo_documento' => $intento->tipo_documento,
            'numero_documento' => $intento->numero_documento,
            'datos_persona' => [
                'tipo_documento' => $intento->tipo_documento,
                'numero_documento' => $intento->numero_documento,
                'nombre' => $intento->nombre,
            ],
            'listas_coincidentes' => [
                [
                    'tipo_lista' => $intento->tipo_lista,
                    'tipo' => $intento->tipo_lista,
                    'lista' => $intento->lista_nombre,
                    'nombre' => $intento->lista_nombre,
                    'identificacion' => $intento->numero_documento,
                    'tipo_coincidencia' => 'coincidencia_intento',
                ],
            ],
            'contexto_operacion' => [
                'tipo_operacion' => $intento->tipo_operacion,
                'referencia' => $intento->referencia,
                'monto' => $intento->monto,
                'descripcion' => $intento->descripcion,
                'contexto' => $intento->contexto,
                'created_at' => $intento->created_at?->toIso8601String(),
                'sistema_origen' => $intento->sistema_origen,
                'modo_integracion' => $intento->modo_integracion,
            ],
        ]);
    }

    private function tieneCamposMinimos(array $datos): bool
    {
        foreach (['tipo_documento', 'numero_documento', 'tipo_lista', 'lista_nombre', 'tipo_operacion'] as $campo) {
            if (! isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                return false;
            }
        }

        return true;
    }

    private function resolverSistemaOrigen(SistemaConsumidor $sistema, mixed $sistemaOrigenExterno): string
    {
        if (is_string($sistemaOrigenExterno) && trim($sistemaOrigenExterno) !== '') {
            return trim($sistemaOrigenExterno);
        }

        if (is_string($sistema->codigo) && trim($sistema->codigo) !== '') {
            return trim($sistema->codigo);
        }

        return (string) $sistema->id;
    }

    private function normalizarContexto(mixed $contexto): ?array
    {
        return is_array($contexto) ? $contexto : null;
    }

    private function normalizarTexto(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $texto = trim($valor);

        return $texto !== '' ? $texto : null;
    }

    private function resolverFechaCreacion(mixed $valor): ?\Illuminate\Support\Carbon
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($valor);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizarReferencia(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $referencia = trim($valor);

        return $referencia !== '' ? $referencia : null;
    }

    private function buscarIntentoExistente(
        SistemaConsumidor $sistema,
        string $modoIntegracion,
        ?string $referencia,
    ): ?IntentoOperacion {
        if ($referencia === null) {
            return null;
        }

        return IntentoOperacion::query()
            ->where('sistema_id', $sistema->id)
            ->where('modo_integracion', $modoIntegracion)
            ->where(static function (Builder $query) use ($referencia): void {
                $query->where('referencia', $referencia)
                    ->orWhere('referencia_externa', $referencia);
            })
            ->first();
    }
}
