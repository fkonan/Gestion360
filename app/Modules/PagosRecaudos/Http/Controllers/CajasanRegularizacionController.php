<?php

namespace App\Modules\PagosRecaudos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoProcesoLockService;
use App\Modules\PagosRecaudos\Services\Cajasan\PagoRegularizacionService;
use App\Modules\PagosRecaudos\Services\PagosRecaudosLogger;
use Illuminate\Http\Request;
use Throwable;

class CajasanRegularizacionController extends Controller
{
    public function __construct(
        private readonly PagoRegularizacionService $regularizacionService,
        private readonly PagoProcesoLockService $lockService
    ) {}

    public function index(Request $request)
    {
        $this->asegurarSuperAdmin();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
        ];

        $casos = $this->regularizacionService->obtenerCasosPendientes($filters);

        return view('pagosrecaudos::cajasan.regularizacionesIndex', [
            'filters' => $filters,
            'casos' => $casos,
        ]);
    }

    public function show(int $detalleId)
    {
        $this->asegurarSuperAdmin();

        try {
            return $this->renderDetalle($detalleId);
        } catch (Throwable $e) {
            PagosRecaudosLogger::exception('Error al cargar caso de regularizacion Cajasan', $e, [
                'operation' => 'regularizacion_show',
                'detalle_id' => $detalleId,
            ]);

            return redirect()
                ->route('pagosConvenios.regularizaciones.index')
                ->with('error', 'No fue posible cargar el caso solicitado.');
        }
    }

    public function preview(Request $request, int $detalleId)
    {
        $this->asegurarSuperAdmin();

        $data = $this->validarFormulario($request);
        $caso = $this->regularizacionService->obtenerCasoPendienteOrFail($detalleId);

        if ((int) ($caso->es_regularizable ?? 0) !== 1) {
            return $this->renderDetalle(
                $detalleId,
                null,
                $data,
                'Este caso no esta en estado C. Solo puede revisarse desde el panel, no regularizarse con este flujo.'
            );
        }

        try {
            $resultado = $this->regularizacionService->previsualizar(
                detalleId: $detalleId,
                authorizationCode: $data['authorization_code'],
                userIdRegulariza: $data['user_id'],
                telefono: $data['telefono'],
                turnoIdForzado: $data['turno_id']
            );

            PagosRecaudosLogger::info('Previsualizacion de regularizacion Cajasan generada', [
                'operation' => 'regularizacion_preview',
                'detalle_id' => $detalleId,
                'turno_id' => $resultado['turno_id'] ?? null,
                'idsucursal' => $resultado['idsucursal'] ?? null,
            ]);

            return $this->renderDetalle($detalleId, $resultado, $data);
        } catch (Throwable $e) {
            PagosRecaudosLogger::exception('Error en previsualizacion de regularizacion Cajasan', $e, [
                'operation' => 'regularizacion_preview',
                'detalle_id' => $detalleId,
            ]);

            return $this->renderDetalle($detalleId, null, $data, $e->getMessage());
        }
    }

    public function regularizar(Request $request, int $detalleId)
    {
        $this->asegurarSuperAdmin();

        $data = $this->validarFormulario($request);
        $caso = $this->regularizacionService->obtenerCasoPendienteOrFail($detalleId);

        if ((int) ($caso->es_regularizable ?? 0) !== 1) {
            return redirect()
                ->route('pagosConvenios.regularizaciones.show', $detalleId)
                ->withInput()
                ->with('error', 'Este caso no esta en estado C. Solo puede revisarse desde el panel.');
        }

        try {
            $resultado = $this->lockService->runSequenceCriticalSection(function () use ($detalleId, $data) {
                return $this->regularizacionService->regularizar(
                    detalleId: $detalleId,
                    authorizationCode: $data['authorization_code'],
                    userIdRegulariza: $data['user_id'],
                    telefono: $data['telefono'],
                    turnoIdForzado: $data['turno_id']
                );
            });

            PagosRecaudosLogger::info('Regularizacion Cajasan ejecutada desde panel administrativo', [
                'operation' => 'regularizacion_execute',
                'detalle_id' => $detalleId,
                'comprobante_id' => $resultado['comprobante_id'] ?? null,
                'comprobante' => $resultado['comprobante'] ?? null,
            ]);

            return redirect()
                ->route('pagosConvenios.regularizaciones.show', $detalleId)
                ->with('success', 'Regularizacion ejecutada correctamente.')
                ->with('regularizacion_resultado', $resultado);
        } catch (Throwable $e) {
            PagosRecaudosLogger::exception('Error al ejecutar regularizacion Cajasan desde panel administrativo', $e, [
                'operation' => 'regularizacion_execute',
                'detalle_id' => $detalleId,
            ]);

            return redirect()
                ->route('pagosConvenios.regularizaciones.show', $detalleId)
                ->withInput()
                ->with('error', 'No fue posible ejecutar la regularizacion: '.$e->getMessage());
        }
    }

    private function renderDetalle(
        int $detalleId,
        ?array $previewResult = null,
        ?array $formData = null,
        ?string $previewError = null
    ) {
        $caso = $this->regularizacionService->obtenerCasoPendienteOrFail($detalleId);

        return view('pagosrecaudos::cajasan.regularizacionesShow', [
            'caso' => $caso,
            'previewResult' => $previewResult,
            'previewError' => $previewError,
            'formData' => $formData ?? [
                'authorization_code' => (string) ($caso->nro_interno ?? ''),
                'user_id' => (int) ($caso->usrcreacion ?? 0),
                'telefono' => '0',
                'turno_id' => null,
            ],
            'regularizacionResultado' => session('regularizacion_resultado'),
        ]);
    }

    private function validarFormulario(Request $request): array
    {
        $data = $request->validate([
            'authorization_code' => ['required', 'string', 'max:60'],
            'user_id' => ['required', 'integer', 'min:1'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'turno_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return [
            'authorization_code' => trim((string) $data['authorization_code']),
            'user_id' => (int) $data['user_id'],
            'telefono' => trim((string) ($data['telefono'] ?? '')) ?: '0',
            'turno_id' => isset($data['turno_id']) ? (int) $data['turno_id'] : null,
        ];
    }

    private function asegurarSuperAdmin(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->hasRole(User::SUPER_ADMIN_ROLE), 403);
    }
}
