<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\IntentoOperacion;
use App\Modules\Sarlaft\Models\ListaNegraInterna;
use App\Modules\Sarlaft\Models\RegistroLista;
use Illuminate\Support\Facades\Log;
use Throwable;

class ValidacionListaNegraService
{
    /**
     * @return array{
     *   en_lista_negra: bool,
     *   listas: array<int, string>,
     *   coincidencias: array<int, array<string, mixed>>
     * }
     */
    public function consultarPorIdentificacion(string $numeroIdentificacion, ?string $tipoDocumento = null): array
    {
        $numeroIdentificacion = trim($numeroIdentificacion);
        $tipoDocumentoNormalizado = $this->normalizarTipoDocumento($tipoDocumento);

        if ($numeroIdentificacion === '') {
            return [
                'en_lista_negra' => false,
                'listas' => [],
                'coincidencias' => [],
            ];
        }

        $coincidencias = [];

        $registrosVinculantes = RegistroLista::query()
            ->select(['lista_id', 'identificacion', 'nombres', 'alias', 'tipo_identificacion'])
            ->where('identificacion', $numeroIdentificacion)
            ->where('estado', 'activo')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->whereHas('lista', static function ($query): void {
                $query->whereIn('tipo', ['vinculante', 'interna']);
            })
            ->with('lista:id,nombre,tipo')
            ->get();

        foreach ($registrosVinculantes as $registro) {
            if ($registro->lista === null) {
                continue;
            }

            $coincidencias[] = [
                'origen' => 'vinculante',
                'lista_id' => (int) $registro->lista_id,
                'nombre_lista' => (string) $registro->lista->nombre,
                'tipo_lista' => (string) $registro->lista->tipo,
                'tipo_documento' => $registro->tipo_identificacion,
                'numero_documento' => $registro->identificacion,
                'nombres' => $registro->nombres,
                'alias' => $registro->alias,
                'tipo_coincidencia' => 'documento_exacto',
            ];
        }

        $registrosInternos = ListaNegraInterna::query()
            ->select(['tipo_documento', 'numero_documento', 'nombres', 'motivo', 'estado'])
            ->where('numero_documento', $numeroIdentificacion)
            ->where('estado', 'activo')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($registrosInternos as $registroInterno) {
            $tipoDocumentoRegistro = strtoupper(trim((string) $registroInterno->tipo_documento));

            $coincidencias[] = [
                'origen' => 'interna',
                'lista_id' => null,
                'nombre_lista' => 'Lista Negra Interna',
                'tipo_lista' => 'interna',
                'tipo_documento' => $tipoDocumentoRegistro,
                'numero_documento' => $registroInterno->numero_documento,
                'nombres' => $registroInterno->nombres,
                'motivo' => $registroInterno->motivo,
                'tipo_coincidencia' => 'lista_negra_interna',
                'coincide_tipo_documento' => $tipoDocumentoNormalizado !== null
                    ? $tipoDocumentoRegistro === $tipoDocumentoNormalizado
                    : null,
            ];
        }

        $listas = collect($coincidencias)
            ->pluck('nombre_lista')
            ->filter(static fn (mixed $valor): bool => is_string($valor) && trim($valor) !== '')
            ->map(static fn (string $valor): string => trim($valor))
            ->unique()
            ->values()
            ->all();

        return [
            'en_lista_negra' => $coincidencias !== [],
            'listas' => $listas,
            'coincidencias' => $coincidencias,
        ];
    }

    /**
     * @param  array{
     *   en_lista_negra?: bool,
     *   coincidencias?: array<int, array<string, mixed>>
     * }  $resultadoValidacion
     * @param  array{
     *   sistema_origen?: string|null,
     *   modo_integracion?: string|null,
     *   tipo_documento: string,
     *   numero_documento: string,
     *   nombre?: string|null,
     *   tipo_operacion: string,
     *   referencia?: string|null,
     *   referencia_externa?: string|null,
     *   fecha_operacion?: mixed,
     *   monto?: mixed,
     *   moneda?: string|null,
     *   descripcion?: string|null,
     *   contexto?: array<string, mixed>|null,
     *   ip_origen?: string|null
     * }  $datosOperacion
     */
    public function registrarIntentoOperacionPrimeraCoincidencia(
        array $resultadoValidacion,
        array $datosOperacion,
    ): ?IntentoOperacion {
        $coincidencia = $this->obtenerPrimeraCoincidencia($resultadoValidacion['coincidencias'] ?? null);

        if ($coincidencia === null) {
            return null;
        }

        $origenCoincidencia = strtolower(trim((string) ($coincidencia['origen'] ?? '')));
        $tipoLista = $origenCoincidencia === 'interna' ? 'restrictiva' : 'vinculante';
        $sistemaId = $origenCoincidencia === 'interna'
            ? null
            : $this->toNullableInt($coincidencia['lista_id'] ?? null);

        $listaNombre = trim((string) ($coincidencia['nombre_lista'] ?? ''));
        if ($listaNombre === '') {
            $listaNombre = $origenCoincidencia === 'interna' ? 'Lista Negra Interna' : 'Lista no identificada';
        }

        $contextoBase = is_array($datosOperacion['contexto'] ?? null) ? $datosOperacion['contexto'] : [];
        $contexto = $this->construirContextoIntento($contextoBase);

        try {
            return IntentoOperacion::create([
                'sistema_id' => $sistemaId,
                'sistema_origen' => $this->normalizarTexto($datosOperacion['sistema_origen'] ?? 'pagos_recaudos_cajasan'),
                'modo_integracion' => $this->normalizarTexto($datosOperacion['modo_integracion'] ?? 'push') ?? 'push',
                'tipo_documento' => trim((string) $datosOperacion['tipo_documento']),
                'numero_documento' => trim((string) $datosOperacion['numero_documento']),
                'nombre' => $this->normalizarTexto($datosOperacion['nombre'] ?? null),
                'tipo_lista' => $tipoLista,
                'lista_nombre' => $listaNombre,
                'tipo_operacion' => trim((string) $datosOperacion['tipo_operacion']),
                'referencia' => $this->normalizarTexto($datosOperacion['referencia'] ?? null),
                'referencia_externa' => $this->normalizarTexto($datosOperacion['referencia_externa'] ?? null),
                'fecha_operacion' => $datosOperacion['fecha_operacion'] ?? now(),
                'monto' => $this->normalizarMonto($datosOperacion['monto'] ?? null),
                'moneda' => $this->normalizarTexto($datosOperacion['moneda'] ?? 'COP') ?? 'COP',
                'descripcion' => $this->normalizarTexto($datosOperacion['descripcion'] ?? null),
                'contexto' => $contexto,
                'ip_origen' => $this->normalizarTexto($datosOperacion['ip_origen'] ?? null),
            ]);
        } catch (Throwable $e) {
            Log::error('No fue posible registrar intento de operacion por lista negra', [
                'numero_documento' => $datosOperacion['numero_documento'] ?? null,
                'tipo_documento' => $datosOperacion['tipo_documento'] ?? null,
                'tipo_operacion' => $datosOperacion['tipo_operacion'] ?? null,
                'coincidencia' => $coincidencia,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function normalizarTipoDocumento(?string $tipoDocumento): ?string
    {
        if ($tipoDocumento === null) {
            return null;
        }

        $valor = strtoupper(trim($tipoDocumento));

        if ($valor === '') {
            return null;
        }

        return match ($valor) {
            'NT' => 'NIT',
            default => $valor,
        };
    }

    /**
     * @param  mixed  $coincidencias
     * @return array<string, mixed>|null
     */
    private function obtenerPrimeraCoincidencia(mixed $coincidencias): ?array
    {
        if (! is_array($coincidencias)) {
            return null;
        }

        foreach ($coincidencias as $coincidencia) {
            if (is_array($coincidencia)) {
                return $coincidencia;
            }
        }

        return null;
    }

    private function normalizarTexto(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $texto = trim($valor);

        return $texto !== '' ? $texto : null;
    }

    private function normalizarMonto(mixed $monto): ?string
    {
        if (is_int($monto) || is_float($monto)) {
            return number_format((float) $monto, 2, '.', '');
        }

        if (! is_string($monto)) {
            return null;
        }

        $valor = trim($monto);
        if ($valor === '') {
            return null;
        }

        $valor = str_replace([',', ' '], ['', ''], $valor);

        if (! is_numeric($valor)) {
            return null;
        }

        return number_format((float) $valor, 2, '.', '');
    }

    private function toNullableInt(mixed $valor): ?int
    {
        if (is_int($valor)) {
            return $valor;
        }

        if (is_string($valor) && trim($valor) !== '' && is_numeric($valor)) {
            return (int) $valor;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $contextoBase
     * @return array<string, mixed>|null
     */
    private function construirContextoIntento(array $contextoBase): ?array
    {
        $contexto = [];

        $modulo = $this->normalizarTexto($contextoBase['modulo'] ?? null);
        if ($modulo !== null) {
            $contexto['modulo'] = $modulo;
        }

        $proceso = $this->normalizarTexto($contextoBase['proceso'] ?? null);
        if ($proceso !== null) {
            $contexto['proceso'] = $proceso;
        }

        if (array_key_exists('saldo_api', $contextoBase)) {
            $saldoApi = $this->normalizarMonto($contextoBase['saldo_api']);
            if ($saldoApi !== null) {
                $contexto['saldo_api'] = $saldoApi;
            }
        }

        $agencia = $this->normalizarTexto($contextoBase['agencia'] ?? null);
        if ($agencia !== null) {
            $contexto['agencia'] = $agencia;
        }

        return $contexto !== [] ? $contexto : null;
    }
}
