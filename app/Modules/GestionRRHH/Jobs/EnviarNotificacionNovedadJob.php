<?php

namespace App\Modules\GestionRRHH\Jobs;

use App\Modules\GestionRRHH\Services\Novedades\NovedadNotificacionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnviarNotificacionNovedadJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $tipo,
        public array $payload
    ) {
        $this->onConnection((string) config('services.employee_permits.notifications.queue_connection', 'database-admin'));
        $this->onQueue((string) config('services.employee_permits.notifications.queue_name', 'rrhh-mails'));
    }

    public function handle(NovedadNotificacionService $notificacionService): void
    {
        if (method_exists($notificacionService, 'procesarNotificacionGenerica')) {
            $notificacionService->procesarNotificacionGenerica(
                tipo: $this->tipo,
                payload: $this->payload
            );

            return;
        }

        $tipo = trim($this->tipo);
        $payload = $this->payload;

        match ($tipo) {
            NovedadNotificacionService::TIPO_PERMISO_RADICADO => $notificacionService->procesarPermisoRadicado(
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                persona: is_array($payload['persona'] ?? null) ? $payload['persona'] : null,
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                jefeDirecto: is_array($payload['jefe_directo'] ?? null) ? $payload['jefe_directo'] : null,
                urlGestionJefe: isset($payload['url_gestion_jefe']) ? (string) $payload['url_gestion_jefe'] : null,
                magicLinkTtlMinutos: isset($payload['magic_link_ttl_minutos']) ? (int) $payload['magic_link_ttl_minutos'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB')
            ),
            NovedadNotificacionService::TIPO_PERMISO_PENDIENTE_RRHH => $notificacionService->procesarPermisoPendienteRrhh(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB')
            ),
            NovedadNotificacionService::TIPO_VACACION_RADICADA => $notificacionService->enviarVacacionRadicada(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                documentoActor: (string) ($payload['documento_actor'] ?? ''),
                nombreActor: isset($payload['nombre_actor']) ? (string) $payload['nombre_actor'] : null,
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            NovedadNotificacionService::TIPO_PERMISO_PERMANENTE_RADICADO => $notificacionService->enviarPermisoPermanenteRadicado(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                documentoActor: (string) ($payload['documento_actor'] ?? ''),
                nombreActor: isset($payload['nombre_actor']) ? (string) $payload['nombre_actor'] : null,
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            NovedadNotificacionService::TIPO_INCAPACIDAD_RADICADA_RRHH => $notificacionService->enviarIncapacidadRadicadaRrhh(
                resultado: is_array($payload['resultado'] ?? null) ? $payload['resultado'] : [],
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                documentoActor: (string) ($payload['documento_actor'] ?? ''),
                nombreActor: isset($payload['nombre_actor']) ? (string) $payload['nombre_actor'] : null,
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            NovedadNotificacionService::TIPO_CITACION_DESCARGOS_EMPLEADO => $notificacionService->enviarCitacionDescargosEmpleado(
                idCitacion: (string) ($payload['id_citacion'] ?? ''),
                payload: is_array($payload['payload'] ?? null) ? $payload['payload'] : [],
                persona: is_array($payload['persona'] ?? null) ? $payload['persona'] : [],
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            NovedadNotificacionService::TIPO_VACACION_PENDIENTE_RRHH => $notificacionService->enviarPendienteRrhhVacacion(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            NovedadNotificacionService::TIPO_PERMISO_PERMANENTE_PENDIENTE_RRHH => $notificacionService->enviarPendienteRrhhPermisoPermanente(
                idNovedad: (string) ($payload['id_novedad'] ?? ''),
                actor: is_array($payload['actor'] ?? null) ? $payload['actor'] : [],
                urlAccion: isset($payload['url_accion']) ? (string) $payload['url_accion'] : null,
                canal: (string) ($payload['canal'] ?? 'WEB'),
                encolar: false
            ),
            default => null,
        };
    }
}
