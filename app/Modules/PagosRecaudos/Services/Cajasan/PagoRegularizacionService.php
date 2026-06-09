<?php

namespace App\Modules\PagosRecaudos\Services\Cajasan;

use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Modules\PagosRecaudos\Models\ConComprobantes;
use App\Modules\PagosRecaudos\Models\ConDetCarguePagRec;
use App\Modules\PagosRecaudos\Models\ConDetPagoRecaudo;
use App\Modules\PagosRecaudos\Models\ConPagosRecaudos;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class PagoRegularizacionService
{
    public function obtenerCasosPendientes(array $filters = []): Paginator
    {
        $q = trim((string) ($filters['q'] ?? ''));

        $query = DB::connection('oracle')
            ->table('CON_DETCARGUEPAGOSYRECAUDOS as D')
            ->join('CON_CARGUEPAGOSYRECAUDOS as C', 'D.ID_CARGUEPAGYREC', '=', 'C.ID')
            ->leftJoin('CON_DETALLEPAGORECAUDO as DPR', function ($join) {
                $join->on('DPR.ID_DET_CARPAGYREC', '=', 'D.ID')
                    ->where('DPR.ESTBORRADO', '=', 0);
            })
            ->selectRaw("
                D.ID,
                D.ID_CARGUEPAGYREC,
                D.FECCREACION,
                D.IDEN_CLIENTEPRINCIPAL as IDENTIFICACION,
                D.CLIENTEPRINCIPAL as CLIENTE,
                D.VALORTOTAL,
                D.ESTADO,
                D.NRO_INTERNO,
                D.USRCREACION,
                D.EMPCREACION,
                D.AGENCIA,
                D.CODAGENCIA,
                CASE WHEN COUNT(DPR.ID) > 0 THEN 1 ELSE 0 END as TIENE_DETALLE_CONTABLE,
                CASE
                    WHEN D.ESTADO = 'C' AND (D.NRO_INTERNO IS NULL OR TRIM(TO_CHAR(D.NRO_INTERNO)) IN ('', '0')) THEN 'ESTADO_C_Y_SIN_NRO_INTERNO'
                    WHEN D.ESTADO = 'C' THEN 'ESTADO_C'
                    ELSE 'SIN_NRO_INTERNO'
                END as MOTIVO_PENDIENTE,
                CASE WHEN D.ESTADO = 'C' THEN 1 ELSE 0 END as ES_REGULARIZABLE
            ")
            ->where('D.ESTBORRADO', 0)
            ->where('C.ESTBORRADO', 0)
            ->where('C.DESCRIPCION', 'like', '%CAJASAN%')
            ->where('D.ESTADO', 'C')
            ->where(function (Builder $builder) {
                $builder->whereNull('D.NRO_INTERNO')
                    ->orWhereRaw("TRIM(TO_CHAR(D.NRO_INTERNO)) IN ('', '0')");
            })
            ->groupBy([
                'D.ID',
                'D.ID_CARGUEPAGYREC',
                'D.FECCREACION',
                'D.IDEN_CLIENTEPRINCIPAL',
                'D.CLIENTEPRINCIPAL',
                'D.VALORTOTAL',
                'D.ESTADO',
                'D.NRO_INTERNO',
                'D.USRCREACION',
                'D.EMPCREACION',
                'D.AGENCIA',
                'D.CODAGENCIA',
            ])
            ->orderByDesc('D.FECCREACION')
            ->orderByDesc('D.ID');

        if ($q !== '') {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('D.IDEN_CLIENTEPRINCIPAL', 'like', "%{$q}%")
                    ->orWhere('D.CLIENTEPRINCIPAL', 'like', "%{$q}%")
                    ->orWhereRaw('TO_CHAR(D.ID) like ?', ["%{$q}%"])
                    ->orWhere('D.NRO_INTERNO', 'like', "%{$q}%")
                    ->orWhere('D.AGENCIA', 'like', "%{$q}%");
            });
        }

        return $query->simplePaginate(20)->withQueryString();
    }

    public function obtenerCasoPendienteOrFail(int $detalleId): object
    {
        $caso = DB::connection('oracle')
            ->table('CON_DETCARGUEPAGOSYRECAUDOS as D')
            ->join('CON_CARGUEPAGOSYRECAUDOS as C', 'D.ID_CARGUEPAGYREC', '=', 'C.ID')
            ->leftJoin('CON_DETALLEPAGORECAUDO as DPR', function ($join) {
                $join->on('DPR.ID_DET_CARPAGYREC', '=', 'D.ID')
                    ->where('DPR.ESTBORRADO', '=', 0);
            })
            ->selectRaw("
                D.*,
                C.DESCRIPCION as CARGUE_DESCRIPCION,
                C.ESTADO as CARGUE_ESTADO,
                CASE WHEN COUNT(DPR.ID) > 0 THEN 1 ELSE 0 END as TIENE_DETALLE_CONTABLE,
                CASE
                    WHEN D.ESTADO = 'C' AND (D.NRO_INTERNO IS NULL OR TRIM(TO_CHAR(D.NRO_INTERNO)) IN ('', '0')) THEN 'ESTADO_C_Y_SIN_NRO_INTERNO'
                    WHEN D.ESTADO = 'C' THEN 'ESTADO_C'
                    ELSE 'SIN_NRO_INTERNO'
                END as MOTIVO_PENDIENTE,
                CASE WHEN D.ESTADO = 'C' THEN 1 ELSE 0 END as ES_REGULARIZABLE
            ")
            ->where('D.ID', $detalleId)
            ->where('D.ESTBORRADO', 0)
            ->where('C.ESTBORRADO', 0)
            ->groupBy([
                'D.ID',
                'D.ID_CARGUEPAGYREC',
                'D.CONCEPTO',
                'D.IDEN_CLIENTEPRINCIPAL',
                'D.CLIENTEPRINCIPAL',
                'D.IDEN_CLIENTESECUNDARIO',
                'D.CLIENTESECUNDARIO',
                'D.VALORTOTAL',
                'D.VALORPARCIAL',
                'D.ESTADO',
                'D.CODCIUDAD',
                'D.CIUDAD',
                'D.FECHA_DESDE',
                'D.FECHA_HASTA',
                'D.NRO_INTERNO',
                'D.CODAGENCIA',
                'D.AGENCIA',
                'D.ESTBORRADO',
                'D.FECMODIFICA',
                'D.EMPMODIFICA',
                'D.USRMODIFICA',
                'D.ROLMODIFICA',
                'D.FECCREACION',
                'D.USRCREACION',
                'D.EMPCREACION',
                'C.DESCRIPCION',
                'C.ESTADO',
            ])
            ->first();

        if (! $caso) {
            throw new RuntimeException("No se encontro el detalle {$detalleId}.");
        }

        return $caso;
    }

    public function previsualizar(
        int $detalleId,
        string $authorizationCode,
        int $userIdRegulariza,
        string $telefono = '0',
        ?int $turnoIdForzado = null
    ): array {
        return $this->regularizar(
            detalleId: $detalleId,
            authorizationCode: $authorizationCode,
            userIdRegulariza: $userIdRegulariza,
            telefono: $telefono,
            turnoIdForzado: $turnoIdForzado,
            persistir: false
        );
    }

    public function regularizar(
        int $detalleId,
        string $authorizationCode,
        int $userIdRegulariza,
        string $telefono = '0',
        ?int $turnoIdForzado = null,
        bool $persistir = true
    ): array {
        $authorizationCode = trim($authorizationCode);
        if ($authorizationCode === '') {
            throw new RuntimeException('El codigo de autorizacion es obligatorio para regularizar.');
        }

        $oracle = DB::connection('oracle');

        if ($persistir) {
            $oracle->beginTransaction();
        }

        try {
            $detalleQuery = ConDetCarguePagRec::query()->where('id', $detalleId);
            $detalle = $persistir ? $detalleQuery->lockForUpdate()->firstOrFail() : $detalleQuery->firstOrFail();

            if ($detalle->estado !== 'C') {
                throw new RuntimeException("El detalle {$detalleId} no esta en estado C (estado actual: {$detalle->estado}).");
            }

            $existeDetalleContable = ConDetPagoRecaudo::where('id_det_carpagyrec', $detalleId)->exists();
            if ($existeDetalleContable) {
                throw new RuntimeException("El detalle {$detalleId} ya tiene detalle contable. Abortado para evitar duplicados.");
            }

            $caja = $this->resolverCajaDelPago($detalle, $turnoIdForzado);
            if (! $caja) {
                throw new RuntimeException("No se pudo resolver la caja/turno para el detalle {$detalleId}.");
            }

            $cajaActiva = (object) [
                'id' => (int) $caja->turno_id,
                'idsucursal' => (int) $caja->idsucursal,
                'en_id' => (int) $this->resolverEnId((int) $caja->turno_id, (int) $caja->idsucursal),
            ];
            $codigoAgencia = trim((string) ($caja->codigo_agencia ?? ''));
            $nombreSucursal = trim((string) ($caja->nomsucursal ?? ''));
            $agenciaDescripcion = $codigoAgencia !== ''
                ? $codigoAgencia.($nombreSucursal !== '' ? ' - '.$nombreSucursal : '')
                : ($nombreSucursal !== '' ? $nombreSucursal : null);

            $saldo = (float) $detalle->valortotal;
            if ($saldo <= 0) {
                throw new RuntimeException("El detalle {$detalleId} tiene valor no valido ({$detalle->valortotal}).");
            }

            $centroCosto = $cajaActiva->idsucursal
                ? EmpleadoService::codigoCentroCostoPorSucursal($cajaActiva->idsucursal)
                : null;

            if (! $centroCosto) {
                throw new RuntimeException("No se pudo obtener centro de costo de la sucursal {$cajaActiva->idsucursal}.");
            }

            $resultado = [
                'detalle_id' => (int) $detalle->id,
                'estado_inicial' => (string) $detalle->estado,
                'turno_id' => (int) $cajaActiva->id,
                'idsucursal' => (int) $cajaActiva->idsucursal,
                'en_id' => (int) $cajaActiva->en_id,
                'saldo' => $saldo,
                'centro_costo' => (string) $centroCosto,
                'cliente_identificacion' => (string) $detalle->iden_clienteprincipal,
                'cliente_nombre' => (string) $detalle->clienteprincipal,
                'id_cargue' => (int) $detalle->id_carguepagyrec,
                'nro_interno_objetivo' => $authorizationCode,
                'codagencia_objetivo' => $codigoAgencia !== '' ? $codigoAgencia : null,
                'agencia_objetivo' => $agenciaDescripcion,
            ];

            $resultado['preview'] = $this->previsualizarOperaciones(
                detalle: $detalle,
                cajaActiva: $cajaActiva,
                userIdRegulariza: $userIdRegulariza,
                authorizationCode: $authorizationCode,
                telefono: $telefono,
                centroCosto: $centroCosto,
                saldo: $saldo,
                codAgenciaObjetivo: $codigoAgencia !== '' ? $codigoAgencia : null,
                agenciaObjetivo: $agenciaDescripcion
            );

            if (! $persistir) {
                return $resultado;
            }

            $clienteData = [
                'identificacion' => (string) $detalle->iden_clienteprincipal,
                'nombre' => (string) $detalle->clienteprincipal,
            ];

            $comprobanteService = app(ComprobanteService::class);
            $cajaTurnoDocService = app(CajaTurnoDocService::class);

            $comprobante = $comprobanteService->obtenerOCrear($saldo, $cajaActiva, $userIdRegulariza);
            $grupoBloque = $comprobanteService->obtenerGrupoBloque($comprobante->id);
            $bloque = (int) $grupoBloque->bloque;
            $grupo = (int) $grupoBloque->grupo;

            $comprobanteService->crearDetalleComprobante(
                $comprobante->id,
                $detalle->id,
                (string) $saldo,
                $clienteData,
                $cajaActiva,
                $telefono,
                $userIdRegulariza
            );

            $comprobanteService->crearAuxComprobante(
                $comprobante->id,
                'D',
                $saldo,
                $clienteData,
                $cajaActiva,
                $bloque,
                $grupo,
                $userIdRegulariza,
                $centroCosto
            );
            $comprobanteService->crearAuxComprobante(
                $comprobante->id,
                'C',
                $saldo,
                $clienteData,
                $cajaActiva,
                $bloque,
                $grupo,
                $userIdRegulariza,
                $centroCosto
            );
            $cajaTurnoDocService->crear($comprobante->id, $saldo, $cajaActiva, $userIdRegulariza);

            $detalle->update([
                'estado' => 'P',
                'nro_interno' => $authorizationCode,
                'codagencia' => $codigoAgencia !== '' ? $codigoAgencia : $detalle->codagencia,
                'agencia' => $agenciaDescripcion ?? $detalle->agencia,
                'usrcreacion' => $userIdRegulariza,
                'empcreacion' => $cajaActiva->idsucursal,
                'fecmodifica' => now(),
                'usrmodifica' => $userIdRegulariza,
                'empmodifica' => $cajaActiva->idsucursal,
            ]);

            ConPagosRecaudos::where('id', $detalle->id_carguepagyrec)->update([
                'estado' => 'P',
                'fecmodifica' => now(),
                'usrmodifica' => $userIdRegulariza,
                'empmodifica' => $cajaActiva->idsucursal,
            ]);

            $oracle->commit();

            return $resultado + [
                'comprobante_id' => (int) $comprobante->id,
                'comprobante' => (string) $comprobante->comprobante,
                'estado_final' => 'P',
            ];
        } catch (Throwable $e) {
            if ($persistir) {
                $oracle->rollBack();
            }

            throw $e;
        }
    }

    private function resolverCajaDelPago(object $detalle, ?int $turnoIdForzado): ?object
    {
        $query = DB::connection('oracle')
            ->table('TES_CAJATURNOS as T')
            ->join('TES_CAJAS as CJ', 'T.CJ_ID', '=', 'CJ.ID')
            ->leftJoin('PER_PERSONAS as P', 'CJ.PE_ID_AG', '=', 'P.ID')
            ->selectRaw('T.ID as turno_id, CJ.PE_ID_AG as idsucursal, P.CODIGO as codigo_agencia, P.NOMSUCURSAL as nomsucursal')
            ->where('T.ESTBORRADO', 0);

        if ($turnoIdForzado) {
            return $query->where('T.ID', $turnoIdForzado)->first();
        }

        return $query
            ->where('T.PE_ID', $detalle->usrcreacion)
            ->whereRaw('? BETWEEN T.FECINI AND NVL(T.FECFIN, SYSDATE)', [$detalle->feccreacion])
            ->orderByDesc('T.ID')
            ->first();
    }

    private function resolverEnId(int $turnoId, int $sucursalId): int
    {
        $oracle = DB::connection('oracle');

        if ($this->columnaExiste('TES_CAJATURNOS', 'EN_ID')) {
            $enIdTurno = $oracle->table('TES_CAJATURNOS')
                ->where('ID', $turnoId)
                ->value('EN_ID');
            if ($enIdTurno) {
                return (int) $enIdTurno;
            }
        }

        if ($this->columnaExiste('TES_CAJAS', 'EN_ID')) {
            $enIdCaja = $oracle->table('TES_CAJATURNOS as T')
                ->join('TES_CAJAS as CJ', 'T.CJ_ID', '=', 'CJ.ID')
                ->where('T.ID', $turnoId)
                ->value('CJ.EN_ID');
            if ($enIdCaja) {
                return (int) $enIdCaja;
            }
        }

        $enIdHistorico = ConComprobantes::query()
            ->where('pe_id_ag', $sucursalId)
            ->whereNotNull('en_id')
            ->orderByDesc('id')
            ->value('en_id');

        if ($enIdHistorico) {
            return (int) $enIdHistorico;
        }

        throw new RuntimeException("No se pudo resolver EN_ID para turno {$turnoId} / sucursal {$sucursalId}.");
    }

    private function columnaExiste(string $tabla, string $columna): bool
    {
        $count = DB::connection('oracle')
            ->table('ALL_TAB_COLUMNS')
            ->where('TABLE_NAME', strtoupper($tabla))
            ->where('COLUMN_NAME', strtoupper($columna))
            ->count();

        return $count > 0;
    }

    private function previsualizarOperaciones(
        object $detalle,
        object $cajaActiva,
        int $userIdRegulariza,
        string $authorizationCode,
        string $telefono,
        string $centroCosto,
        float $saldo,
        ?string $codAgenciaObjetivo,
        ?string $agenciaObjetivo
    ): array {
        $hoy = Carbon::today('America/Bogota')->toDateString();
        $comprobanteExistente = ConComprobantes::query()
            ->where('pe_id_ag', $cajaActiva->idsucursal)
            ->whereDate('fecaplica', $hoy)
            ->where('usrcreacion', $userIdRegulariza)
            ->where('descripcion', 'PAGOS CONVENIOS EMPRESARIALES')
            ->where('estborrado', 0)
            ->first();

        $cpIdPreview = $comprobanteExistente->id ?? 'NUEVO';
        $grupoPreview = null;
        $bloquePreview = null;

        if ($comprobanteExistente) {
            $auxStats = DB::connection('oracle')
                ->table('CON_AUXCOMPROBANTES')
                ->where('cp_id', $comprobanteExistente->id)
                ->where('estborrado', 0)
                ->selectRaw('COALESCE(MAX(grupo),0)+1 as grupo, COALESCE(MAX(bloque),0)+1 as bloque')
                ->first();
            $grupoPreview = (int) ($auxStats->grupo ?? 1);
            $bloquePreview = (int) ($auxStats->bloque ?? 1);
        }

        $previewComprobante = $comprobanteExistente
            ? [
                'accion' => 'UPDATE/REUSAR',
                'cp_id_objetivo' => (int) $comprobanteExistente->id,
                'campos_actualizados' => [
                    'valtotcredito' => '+'.$saldo,
                    'valtotdebito' => '+'.$saldo,
                    'fecmodifica' => 'now()',
                ],
                'campos_reutilizados' => [
                    'descripcion' => (string) $comprobanteExistente->descripcion,
                    'pe_id_ag' => (int) $comprobanteExistente->pe_id_ag,
                    'en_id_actual_comprobante' => (int) $comprobanteExistente->en_id,
                    'usrcreacion_actual' => (int) $comprobanteExistente->usrcreacion,
                    'fecaplica_actual' => (string) $comprobanteExistente->fecaplica,
                ],
                'contexto_resuelto_para_la_regularizacion' => [
                    'idsucursal_caja' => (int) $cajaActiva->idsucursal,
                    'en_id_resuelto_caja' => (int) $cajaActiva->en_id,
                    'user_id_regulariza' => $userIdRegulariza,
                ],
                'nota' => 'Cuando se reutiliza un comprobante existente no se actualiza EN_ID; solo se incrementan totales y se actualiza FECMODIFICA.',
            ]
            : [
                'accion' => 'INSERT',
                'cp_id_preview' => 'NUEVO',
                'campos_insertados' => [
                    'descripcion' => 'PAGOS CONVENIOS EMPRESARIALES',
                    'pe_id_ag' => (int) $cajaActiva->idsucursal,
                    'en_id' => (int) $cajaActiva->en_id,
                    'usrcreacion' => $userIdRegulariza,
                    'fecaplica' => 'now()',
                    'valor_inicial_credito' => $saldo,
                    'valor_inicial_debito' => $saldo,
                ],
            ];

        return [
            'CON_COMPROBANTES' => $previewComprobante,
            'CON_DETALLEPAGORECAUDO' => [
                'accion' => 'INSERT',
                'campos_insertados' => [
                    'id_det_carpagyrec' => (int) $detalle->id,
                    'valregistrado' => $saldo,
                    'iden_clienteregistro' => (string) $detalle->iden_clienteprincipal,
                    'clienteregistro' => (string) $detalle->clienteprincipal,
                    'telefono' => $telefono,
                    'cp_id_relacionado' => $cpIdPreview,
                    'usrcreacion' => $userIdRegulariza,
                    'empcreacion' => (int) $cajaActiva->idsucursal,
                ],
            ],
            'CON_AUXCOMPROBANTES' => [
                'accion' => 'INSERT x2 (D/C)',
                'cp_id_relacionado' => $cpIdPreview,
                'campos_insertados' => [
                    'cc_codigo' => $centroCosto,
                    'grupo_preview' => $grupoPreview,
                    'bloque_preview' => $bloquePreview,
                    'valor' => $saldo,
                    'usrcreacion' => $userIdRegulariza,
                    'empcreacion' => (int) $cajaActiva->idsucursal,
                ],
                'nota' => $comprobanteExistente
                    ? 'GRUPO y BLOQUE se calculan sobre el comprobante reutilizado.'
                    : 'GRUPO y BLOQUE se calculan despues de crear/reutilizar el comprobante real.',
            ],
            'TES_CAJATURNODOCUMENTOS' => [
                'accion' => 'INSERT',
                'campos_insertados' => [
                    'ctu_ori_id' => (int) $cajaActiva->id,
                    'ctu_res_id' => (int) $cajaActiva->id,
                    'cp_id_relacionado' => $cpIdPreview,
                    'valor' => $saldo,
                    'usrcreacion' => $userIdRegulariza,
                    'empcreacion' => (int) $cajaActiva->idsucursal,
                ],
            ],
            'CON_DETCARGUEPAGOSYRECAUDOS' => [
                'accion' => 'UPDATE',
                'campos_actualizados' => [
                    'estado' => 'P',
                    'nro_interno' => $authorizationCode,
                    'codagencia' => $codAgenciaObjetivo,
                    'agencia' => $agenciaObjetivo,
                    'usrcreacion' => $userIdRegulariza,
                    'empcreacion' => (int) $cajaActiva->idsucursal,
                    'usrmodifica' => $userIdRegulariza,
                    'empmodifica' => (int) $cajaActiva->idsucursal,
                    'fecmodifica' => 'now()',
                ],
                'id_objetivo' => (int) $detalle->id,
            ],
            'CON_CARGUEPAGOSYRECAUDOS' => [
                'accion' => 'UPDATE',
                'campos_actualizados' => [
                    'estado' => 'P',
                    'usrmodifica' => $userIdRegulariza,
                    'empmodifica' => (int) $cajaActiva->idsucursal,
                    'fecmodifica' => 'now()',
                ],
                'id_objetivo' => (int) $detalle->id_carguepagyrec,
            ],
        ];
    }
}
