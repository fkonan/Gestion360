<?php

declare(strict_types=1);

namespace App\Modules\Sarlaft\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Administration\Models\Municipio;
use App\Modules\Administration\Models\TipoDocumento;
use App\Modules\Sarlaft\Http\Requests\Admin\StoreSimulacionPasajeRequest;
use App\Modules\Sarlaft\Http\Requests\Admin\StoreSimulacionRemesaRequest;
use App\Modules\Sarlaft\Mail\SimulacionOperacionMail;
use App\Modules\Sarlaft\Models\Consulta;
use App\Modules\Sarlaft\Models\SimulacionPasaje;
use App\Modules\Sarlaft\Models\SimulacionRemesa;
use App\Modules\Sarlaft\Services\ConsultaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SimulacionController extends Controller
{
    private const OFICIAL_CUMPLIMIENTO_EMAIL = 'desarrollo2@copetran.com';

    public function index(): View
    {
        $municipios = Municipio::query()
            ->select('IdMunicipio', 'MunNomMin', 'IdDepartamento')
            ->with('departamento:IdDepartamento,DepNomMin')
            ->orderBy('MunNomMin')
            ->get();

        $tiposDocumento = TipoDocumento::query()
            ->select('id', 'nomenclatura', 'nombre')
            ->orderBy('nombre')
            ->get();

        return view('sarlaft::admin.simulaciones.index', compact('municipios', 'tiposDocumento'));
    }

    public function storePasaje(StoreSimulacionPasajeRequest $request, ConsultaService $consultaService): RedirectResponse
    {
        $datos = $request->validated();
        $ciudades = $this->resolverCiudades([(int) $datos['ciudad_origen_id'], (int) $datos['ciudad_destino_id']]);

        /** @var Consulta $consulta */
        $consulta = DB::connection('mysql-sarlaft')->transaction(function () use ($consultaService, $datos, $ciudades): Consulta {
            $tipoDocumento = $this->normalizarTipoDocumento((string) $datos['tipo_documento']);
            $nombreCompleto = trim($datos['nombres'].' '.$datos['apellidos']);

            $consulta = $consultaService->ejecutar([
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $datos['documento'],
                'nombre' => $nombreCompleto,
            ], request()->ip() ?? '0.0.0.0', 'simulacion_pasaje');

            SimulacionPasaje::create([
                'consulta_id' => $consulta->id,
                'usuario_id' => auth()->id(),
                'ciudad_origen_id' => (int) $datos['ciudad_origen_id'],
                'ciudad_origen_nombre' => $ciudades[(int) $datos['ciudad_origen_id']] ?? 'N/A',
                'ciudad_destino_id' => (int) $datos['ciudad_destino_id'],
                'ciudad_destino_nombre' => $ciudades[(int) $datos['ciudad_destino_id']] ?? 'N/A',
                'fecha_viaje' => $datos['fecha_viaje'],
                'tipo_documento' => $tipoDocumento,
                'documento' => $datos['documento'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'direccion' => $datos['direccion'],
                'telefono' => $datos['telefono'],
                'correo' => $datos['correo'],
                'encontrado' => $consulta->encontrado,
                'presta_servicio' => $consulta->presta_servicio,
                'nivel_riesgo' => $consulta->nivel_riesgo,
                'coincidencias' => $consulta->coincidencias,
            ]);

            return $consulta;
        });

        $this->notificarOficial('Compra de tiquete', [
            'Ciudad origen' => $ciudades[(int) $datos['ciudad_origen_id']] ?? 'N/A',
            'Ciudad destino' => $ciudades[(int) $datos['ciudad_destino_id']] ?? 'N/A',
            'Fecha viaje' => $datos['fecha_viaje'],
            'Tipo documento' => $datos['tipo_documento'],
            'Documento' => $datos['documento'],
            'Nombres' => $datos['nombres'],
            'Apellidos' => $datos['apellidos'],
            'Direccion' => $datos['direccion'],
            'Telefono' => $datos['telefono'],
            'Correo' => $datos['correo'],
        ], $consulta);

        return $this->respuestaResultado('pasaje', $consulta, $datos['documento'], trim($datos['nombres'].' '.$datos['apellidos']));
    }

    public function storeRemesa(StoreSimulacionRemesaRequest $request, ConsultaService $consultaService): RedirectResponse
    {
        $datos = $request->validated();
        $ciudades = $this->resolverCiudades([(int) $datos['ciudad_origen_id'], (int) $datos['ciudad_destino_id']]);

        /** @var Consulta $consulta */
        $consulta = DB::connection('mysql-sarlaft')->transaction(function () use ($consultaService, $datos, $ciudades): Consulta {
            $tipoDocumento = $this->normalizarTipoDocumento((string) $datos['tipo_documento']);
            $nombreCompleto = trim($datos['nombres_remitente'].' '.$datos['apellidos_remitente']);

            $consulta = $consultaService->ejecutar([
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $datos['documento_remitente'],
                'nombre' => $nombreCompleto,
            ], request()->ip() ?? '0.0.0.0', 'simulacion_remesa');

            SimulacionRemesa::create([
                'consulta_id' => $consulta->id,
                'usuario_id' => auth()->id(),
                'ciudad_origen_id' => (int) $datos['ciudad_origen_id'],
                'ciudad_origen_nombre' => $ciudades[(int) $datos['ciudad_origen_id']] ?? 'N/A',
                'ciudad_destino_id' => (int) $datos['ciudad_destino_id'],
                'ciudad_destino_nombre' => $ciudades[(int) $datos['ciudad_destino_id']] ?? 'N/A',
                'fecha_envio' => $datos['fecha_envio'],
                'tipo_documento' => $tipoDocumento,
                'documento_remitente' => $datos['documento_remitente'],
                'nombres_remitente' => $datos['nombres_remitente'],
                'apellidos_remitente' => $datos['apellidos_remitente'],
                'telefono_remitente' => $datos['telefono_remitente'],
                'nombre_destinatario' => $datos['nombre_destinatario'],
                'documento_destinatario' => $datos['documento_destinatario'],
                'monto' => $datos['monto'],
                'concepto' => $datos['concepto'],
                'encontrado' => $consulta->encontrado,
                'presta_servicio' => $consulta->presta_servicio,
                'nivel_riesgo' => $consulta->nivel_riesgo,
                'coincidencias' => $consulta->coincidencias,
            ]);

            return $consulta;
        });

        $this->notificarOficial('Elaboracion de remesa', [
            'Ciudad origen' => $ciudades[(int) $datos['ciudad_origen_id']] ?? 'N/A',
            'Ciudad destino' => $ciudades[(int) $datos['ciudad_destino_id']] ?? 'N/A',
            'Fecha envio' => $datos['fecha_envio'],
            'Tipo documento' => $datos['tipo_documento'],
            'Documento remitente' => $datos['documento_remitente'],
            'Nombres remitente' => $datos['nombres_remitente'],
            'Apellidos remitente' => $datos['apellidos_remitente'],
            'Telefono remitente' => $datos['telefono_remitente'],
            'Nombre destinatario' => $datos['nombre_destinatario'],
            'Documento destinatario' => $datos['documento_destinatario'],
            'Monto' => number_format((float) $datos['monto'], 2, '.', ''),
            'Concepto' => $datos['concepto'],
        ], $consulta);

        return $this->respuestaResultado('remesa', $consulta, $datos['documento_remitente'], trim($datos['nombres_remitente'].' '.$datos['apellidos_remitente']));
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function resolverCiudades(array $ids): array
    {
        return Municipio::query()
            ->whereIn('IdMunicipio', $ids)
            ->pluck('MunNomMin', 'IdMunicipio')
            ->map(static fn (mixed $nombre): string => (string) $nombre)
            ->toArray();
    }

    private function normalizarTipoDocumento(string $tipo): string
    {
        $valor = strtoupper((string) preg_replace('/[^A-Z0-9]/', '', $tipo));

        return match ($valor) {
            'CC' => 'CC',
            'NIT' => 'NIT',
            'CE', 'TE' => 'CE',
            'PA', 'PT', 'PASAPORTE' => 'PA',
            default => substr($valor, 0, 20),
        };
    }

    /**
     * @param  array<string, mixed>  $datosOperacion
     */
    private function notificarOficial(string $escenario, array $datosOperacion, Consulta $consulta): void
    {
        if (! $this->debeNotificarOficial($consulta)) {
            return;
        }

        try {
            Mail::to(self::OFICIAL_CUMPLIMIENTO_EMAIL)->send(
                new SimulacionOperacionMail($escenario, $datosOperacion, $consulta)
            );
        } catch (\Throwable $e) {
            Log::warning('No fue posible enviar correo de simulacion SARLAFT.', [
                'escenario' => $escenario,
                'consulta_id' => $consulta->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function debeNotificarOficial(Consulta $consulta): bool
    {
        if (! $consulta->encontrado) {
            return false;
        }

        return ! $consulta->presta_servicio || $consulta->nivel_riesgo === 'alto';
    }

    private function respuestaResultado(string $escenario, Consulta $consulta, string $documento, string $nombre): RedirectResponse
    {
        $mensaje = $consulta->presta_servicio
            ? 'Validacion completada: operacion permitida.'
            : 'Validacion completada: operacion NO permitida. Revisar alerta SARLAFT.';

        return redirect()
            ->route('sarlaft.simulaciones.index')
            ->with($consulta->presta_servicio ? 'success' : 'error', $mensaje)
            ->with('resultado_simulacion', [
                'escenario' => $escenario,
                'consulta_id' => $consulta->id,
                'documento' => $documento,
                'nombre' => $nombre,
                'encontrado' => $consulta->encontrado,
                'presta_servicio' => $consulta->presta_servicio,
                'nivel_riesgo' => $consulta->nivel_riesgo,
                'total_coincidencias' => is_array($consulta->coincidencias) ? count($consulta->coincidencias) : 0,
                'fecha' => optional($consulta->created_at)->format('d/m/Y H:i:s'),
            ]);
    }
}
