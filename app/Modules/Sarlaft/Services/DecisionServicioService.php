<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Services;

use App\Modules\Sarlaft\Models\Alerta;
use App\Modules\Sarlaft\Models\Bloqueo;
use Illuminate\Support\Facades\DB;

class DecisionServicioService
{
    public function __construct(
        private readonly PoliticaSarlaftService $politicaSarlaftService,
    ) {}

    public function resolverDecisionActiva(string $tipoDocumento, string $numeroDocumento): ?Alerta
    {
        return Alerta::query()
            ->where('tipo', 'coincidencia_lista')
            ->where('tipo_documento', $tipoDocumento)
            ->where('numero_documento', $numeroDocumento)
            ->where('decision_activa', true)
            ->whereIn('decision_servicio', ['bloquear', 'permitir_una_operacion', 'permitir_permanente'])
            ->latest('updated_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function aplicarDecisionEnAtencion(
        Alerta $alerta,
        array $datos,
        ?int $userId,
        bool $esAutomatica = false,
    ): void {
        $decisionServicio = (string) $datos['decision_servicio'];
        $decisionActiva = $decisionServicio !== 'sin_decision';
        $documento = $this->resolverDocumentoAlerta($alerta);
        $momentoAtencion = now();

        DB::connection('mysql-sarlaft')->transaction(function () use (
            $alerta,
            $datos,
            $userId,
            $esAutomatica,
            $momentoAtencion,
            $decisionServicio,
            $decisionActiva,
            $documento,
        ): void {
            if ($decisionActiva && $documento['tipo_documento'] !== null && $documento['numero_documento'] !== null) {
                Alerta::query()
                    ->where('tipo', 'coincidencia_lista')
                    ->where('tipo_documento', $documento['tipo_documento'])
                    ->where('numero_documento', $documento['numero_documento'])
                    ->where('decision_activa', true)
                    ->where('id', '<>', $alerta->id)
                    ->update([
                        'decision_activa' => false,
                        'updated_at' => now(),
                    ]);
            }

            $payload = [
                'estado' => $datos['estado'],
                'notas' => $datos['notas'] ?? $alerta->notas,
                'atendida_por' => $userId ?? $alerta->atendida_por,
                'fecha_atencion' => $momentoAtencion,
                'tipo_documento' => $documento['tipo_documento'],
                'numero_documento' => $documento['numero_documento'],
                'decision_servicio' => $decisionServicio,
                'decision_activa' => $decisionActiva,
            ];

            $evidenciasActuales = is_array($alerta->evidencias) ? $alerta->evidencias : [];
            $evidenciasNuevas = isset($datos['evidencias']) && is_array($datos['evidencias'])
                ? array_values($datos['evidencias'])
                : [];

            if ($evidenciasNuevas !== []) {
                $payload['evidencias'] = array_merge($evidenciasActuales, $evidenciasNuevas);
            }

            if (in_array($decisionServicio, ['sin_decision', 'permitir_una_operacion'], true)) {
                $payload['decision_consumida_at'] = null;
                $payload['decision_consumida_consulta_id'] = null;
            }

            if ($esAutomatica) {
                $contextoOperacion = is_array($alerta->contexto_operacion) ? $alerta->contexto_operacion : [];
                $contextoOperacion['escalada_automatica'] = true;
                $contextoOperacion['escalada_automatica_at'] = $momentoAtencion->toIso8601String();

                $payload['escalada_automatica'] = true;
                $payload['escalada_automatica_at'] = $momentoAtencion;
                $payload['contexto_operacion'] = $contextoOperacion;
            }

            $alerta->update($payload);
            $alerta->refresh();

            $this->sincronizarBloqueoDesdeDecision($alerta);
        });
    }

    public function consumirDecisionUnaOperacion(int $alertaId, int $consultaId): bool
    {
        $filasActualizadas = Alerta::query()
            ->where('id', $alertaId)
            ->where('decision_servicio', 'permitir_una_operacion')
            ->where('decision_activa', true)
            ->whereNull('decision_consumida_at')
            ->update([
                'decision_activa' => false,
                'decision_consumida_at' => now(),
                'decision_consumida_consulta_id' => $consultaId,
            ]);

        return $filasActualizadas > 0;
    }

    public function sincronizarBloqueoDesdeDecision(Alerta $alerta): void
    {
        if ($alerta->decision_servicio !== 'bloquear' || ! $alerta->decision_activa) {
            return;
        }

        if ($alerta->tipo_documento === null || $alerta->numero_documento === null) {
            return;
        }

        $existeBloqueoActivo = Bloqueo::query()
            ->where('tipo_documento', $alerta->tipo_documento)
            ->where('numero_documento', $alerta->numero_documento)
            ->where('estado', 'bloqueado')
            ->exists();

        if ($existeBloqueoActivo) {
            return;
        }

        $datosPersona = is_array($alerta->datos_persona) ? $alerta->datos_persona : [];
        $politica = $this->politicaSarlaftService->obtener();
        $creadoPor = $alerta->atendida_por !== null
            ? (int) $alerta->atendida_por
            : (int) ($politica['auto_user_id'] ?? config('sarlaft.auto_user_id', 1));

        if ($creadoPor <= 0) {
            $creadoPor = 1;
        }

        Bloqueo::create([
            'tipo_documento' => $alerta->tipo_documento,
            'numero_documento' => $alerta->numero_documento,
            'nombre' => isset($datosPersona['nombre']) ? (string) $datosPersona['nombre'] : null,
            'tipo_bloqueo' => 'automatico',
            'estado' => 'bloqueado',
            'motivo_bloqueo' => 'Alerta #'.$alerta->id.' - decision de cumplimiento: bloquear.',
            'creado_por' => $creadoPor,
        ]);
    }

    /**
     * @return array{tipo_documento: ?string, numero_documento: ?string}
     */
    private function resolverDocumentoAlerta(Alerta $alerta): array
    {
        $tipoDocumento = $alerta->tipo_documento;
        $numeroDocumento = $alerta->numero_documento;

        $datosPersona = is_array($alerta->datos_persona) ? $alerta->datos_persona : [];

        if ($tipoDocumento === null && isset($datosPersona['tipo_documento'])) {
            $tipoDocumento = trim((string) $datosPersona['tipo_documento']);
        }

        if ($numeroDocumento === null && isset($datosPersona['numero_documento'])) {
            $numeroDocumento = trim((string) $datosPersona['numero_documento']);
        }

        return [
            'tipo_documento' => $tipoDocumento !== '' ? $tipoDocumento : null,
            'numero_documento' => $numeroDocumento !== '' ? $numeroDocumento : null,
        ];
    }
}
