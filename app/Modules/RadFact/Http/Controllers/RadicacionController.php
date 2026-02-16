<?php

namespace App\Modules\RadFact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RadFact\Http\Requests\StoreRadicacionRequest;
use App\Modules\RadFact\Http\Requests\UpdateDistribucionesRequest;
use App\Modules\RadFact\Http\Requests\UpdateRadicacionAdjuntoRequest;
use App\Modules\RadFact\Mail\RadFactDistribucionNotificacionMail;
use App\Modules\RadFact\Models\RadFactAprobacion;
use App\Modules\RadFact\Models\RadFactArea;
use App\Modules\RadFact\Models\RadFactDistribucion;
use App\Modules\RadFact\Models\RadFactProveedor;
use App\Modules\RadFact\Models\RadFactRadicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RadicacionController extends Controller
{
    /**
     * Listar radicaciones
     */
    public function index()
    {
        return view('radfact::radicaciones.index');
    }

    /**
     * Cargar datos para Bootstrap Table (AJAX)
     */
    public function cargarDatos(Request $request)
    {
        try {
            // Parámetros de Bootstrap Table
            $limit = $request->get('limit', 25);
            $offset = $request->get('offset', 0);
            $search = $request->get('search');
            $order = $request->get('order', 'desc');
            $sort = $request->get('sort', 'fecha_radicacion');

            // Query base
            $query = RadFactRadicacion::with(['proveedor', 'usuario', 'distribuciones']);

            // Búsqueda
            if (! empty($search)) {
                $likeSearch = "%{$search}%";
                $query->where(function ($q) use ($likeSearch) {
                    $q->where('num_factura', 'like', $likeSearch)
                        ->orWhere('num_contrato', 'like', $likeSearch)
                        ->orWhere('descripcion', 'like', $likeSearch)
                        ->orWhere('estado', 'like', $likeSearch)
                        ->orWhereHas('proveedor', function ($q2) use ($likeSearch) {
                            $q2->where('razon_social', 'like', $likeSearch)
                                ->orWhere('documento', 'like', $likeSearch);
                        });
                });
            }

            // Total
            $total = $query->count();

            // Ordenamiento y paginación
            $rows = $query->orderBy($sort, $order)
                ->skip($offset)
                ->take($limit)
                ->get()
                ->map(function ($radicacion) {
                    return [
                        'id' => $radicacion->id,
                        'num_factura' => $radicacion->num_factura,
                        'num_contrato' => $radicacion->num_contrato ?? '-',
                        'proveedor' => $radicacion->proveedor->razon_social ?? 'N/A',
                        'proveedor_documento' => $radicacion->proveedor->documento ?? '-',
                        'descripcion' => $radicacion->descripcion ?? '-',
                        'valor' => '$'.number_format($radicacion->valor, 2),
                        'fecha_radicacion' => $radicacion->fecha_radicacion->format('d/m/Y'),
                        'fecha_vencimiento' => $radicacion->fecha_vencimiento->format('d/m/Y'),
                        'estado' => $radicacion->estado,
                        'areas_count' => $radicacion->distribuciones->count(),
                        'created_at' => $radicacion->created_at->format('d/m/Y H:i'),
                        'usuario' => $radicacion->usuario ? $radicacion->usuario->name : 'N/A',
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (\Exception $e) {
            Log::error('Error cargando datos de radicaciones', ['error' => $e->getMessage()]);

            return response()->json([
                'total' => 0,
                'rows' => [],
            ], 500);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $proveedores = RadFactProveedor::orderBy('razon_social')->get();
        $areas = RadFactArea::orderBy('area')->get();

        return view('radfact::radicaciones.create', compact('proveedores', 'areas'));
    }

    /**
     * Guardar nueva radicación con distribuciones
     */
    public function store(StoreRadicacionRequest $request)
    {
        $validated = $request->validated();
        $correosAreas = $this->obtenerCorreosAreasDistribuidas($validated['distribuciones']);

        DB::beginTransaction();

        try {
            // Guardar PDF si existe
            $pdfPath = null;
            if ($request->hasFile('pdf')) {
                $pdfPath = $request->file('pdf')->store('radfact/pdfs', 'public');
            }

            // Crear radicación
            $radicacion = RadFactRadicacion::create([
                'user_id' => auth()->user()->IdUsuario,
                'proveedor_id' => $validated['proveedor_id'],
                'num_factura' => $validated['num_factura'],
                'num_contrato' => $validated['num_contrato'],
                'numero_pagos' => $validated['numero_pagos'],
                'fecha_radicacion' => $validated['fecha_radicacion'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'necesita_visto_bueno' => $validated['necesita_visto_bueno'] ?? false,
                'descripcion' => $validated['descripcion'],
                'valor' => $validated['valor'],
                'pdf' => $pdfPath,
                'observacion' => $validated['observacion'],
                'estado' => 'RADICADO',
            ]);
            // Crear distribuciones y aprobaciones
            foreach ($validated['distribuciones'] as $distData) {
                $valorCalculado = ($distData['porcentaje'] / 100) * $validated['valor'];

                $distribucion = RadFactDistribucion::create([
                    'user_id' => auth()->user()->IdUsuario,
                    'radicacion_id' => $radicacion->id,
                    'area_id' => $distData['area_id'],
                    'porcentaje' => $distData['porcentaje'],
                    'valor_calculado' => $valorCalculado,
                    'activo' => true,
                ]);

                // Crear aprobación pendiente
                RadFactAprobacion::create([
                    'distribucion_id' => $distribucion->id,
                    'estado' => 'PENDIENTE',
                ]);
            }

            // Actualizar estado de radicación
            $radicacion->update(['estado' => 'EN_APROBACION']);
            DB::commit();

            $resultadoNotificacion = $this->enviarNotificacionAreasDistribuidas(
                $radicacion->fresh(['proveedor']),
                $correosAreas
            );
            $mensajeToast = $this->resolverMensajeNotificacion(
                'Radicación creada exitosamente.',
                $resultadoNotificacion
            );

            return toastModal($mensajeToast['mensaje'], $mensajeToast['tipo'], route('radfact.radicaciones.index'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando radicación', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al crear radicación')->withInput();
        }
    }

    /**
     * Buscar última factura por número de contrato para autocompletar formulario.
     */
    public function buscarPorContrato(Request $request)
    {
        $numeroContrato = trim((string) $request->get('num_contrato', ''));

        if ($numeroContrato === '') {
            return response()->json(['encontrado' => false]);
        }

        $radicacion = RadFactRadicacion::query()
            ->with(['distribucionesActivas', 'proveedor'])
            ->where('num_contrato', $numeroContrato)
            ->latest('id')
            ->first();

        if (! $radicacion) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado' => true,
            'data' => [
                'proveedor_id' => $radicacion->proveedor_id,
                'num_factura' => $radicacion->num_factura,
                'numero_pagos' => $radicacion->numero_pagos,
                'fecha_vencimiento' => optional($radicacion->fecha_vencimiento)->format('Y-m-d'),
                'necesita_visto_bueno' => (bool) $radicacion->necesita_visto_bueno,
                'descripcion' => $radicacion->descripcion,
                'valor' => $radicacion->valor,
                'observacion' => $radicacion->observacion,
                'distribuciones' => $radicacion->distribucionesActivas
                    ->map(function (RadFactDistribucion $distribucion) {
                        return [
                            'area_id' => $distribucion->area_id,
                            'porcentaje' => $distribucion->porcentaje,
                        ];
                    })
                    ->values(),
            ],
        ]);
    }

    /**
     * Mostrar radicación con distribuciones y aprobaciones
     */
    public function show(RadFactRadicacion $radicacion)
    {
        $radicacion->load([
            'proveedor',
            'usuario',
            'distribucionesActivas.area',
            'distribucionesActivas.aprobacion.usuario',
            'distribucionesHistorial.area',
            'distribucionesHistorial.aprobacion',
        ]);

        return view('radfact::radicaciones.show', compact('radicacion'));
    }

    /**
     * Mostrar formulario para cargar adjunto cuando no existe PDF.
     */
    public function editAdjunto(RadFactRadicacion $radicacion)
    {
        if (! empty($radicacion->pdf)) {
            return toast(
                'La radicación ya cuenta con un PDF adjunto.',
                'warning',
                route('radfact.radicaciones.show', $radicacion)
            );
        }

        return view('radfact::radicaciones.edit-adjunto', compact('radicacion'));
    }

    /**
     * Actualizar adjunto de una radicación.
     */
    public function updateAdjunto(UpdateRadicacionAdjuntoRequest $request, RadFactRadicacion $radicacion)
    {
        if (! empty($radicacion->pdf)) {
            return toast(
                'La radicación ya cuenta con un PDF adjunto.',
                'warning',
                route('radfact.radicaciones.show', $radicacion)
            );
        }

        try {
            $pdfPath = $request->file('pdf')->store('radfact/pdfs', 'public');

            $radicacion->update([
                'pdf' => $pdfPath,
            ]);

            return toast(
                'Adjunto cargado exitosamente.',
                'success',
                route('radfact.radicaciones.show', $radicacion)
            );
        } catch (\Exception $e) {
            Log::error('Error actualizando adjunto de radicación', [
                'radicacion_id' => $radicacion->id,
                'error' => $e->getMessage(),
            ]);

            if (! empty($pdfPath ?? null)) {
                Storage::disk('public')->delete($pdfPath);
            }

            return toast(
                'No fue posible cargar el adjunto. Intente nuevamente.',
                'error',
                route('radfact.radicaciones.show', $radicacion)
            );
        }
    }

    /**
     * Mostrar formulario para ajustar distribuciones (cuando hay rechazos)
     */
    public function editDistribuciones(RadFactRadicacion $radicacion)
    {
        // Verificar que haya al menos un rechazo
        if (! $radicacion->algunaDistribucionRechazada()) {
            return redirect()->route('radfact.radicaciones.show', $radicacion)
                ->with('error', 'No hay distribuciones rechazadas para ajustar');
        }

        $radicacion->load([
            'distribucionesActivas.area',
            'distribucionesActivas.aprobacion',
        ]);

        $areas = RadFactArea::orderBy('area')->get();

        return view('radfact::radicaciones.edit-distribuciones', compact('radicacion', 'areas'));
    }

    /**
     * Actualizar distribuciones (crear nuevas versiones)
     */
    public function updateDistribuciones(UpdateDistribucionesRequest $request, RadFactRadicacion $radicacion)
    {
        $validated = $request->validated();
        $correosAreas = $this->obtenerCorreosAreasDistribuidas($validated['distribuciones']);

        DB::beginTransaction();
        try {
            // Desactivar distribuciones actuales
            $radicacion->distribucionesActivas()->update(['activo' => false]);

            // Crear nuevas distribuciones y aprobaciones
            foreach ($validated['distribuciones'] as $distData) {
                $valorCalculado = ($distData['porcentaje'] / 100) * $radicacion->valor;

                $distribucion = RadFactDistribucion::create([
                    'user_id' => auth()->user()->IdUsuario,
                    'radicacion_id' => $radicacion->id,
                    'area_id' => $distData['area_id'],
                    'porcentaje' => $distData['porcentaje'],
                    'valor_calculado' => $valorCalculado,
                    'activo' => true,
                ]);

                // Crear nueva aprobación pendiente
                RadFactAprobacion::create([
                    'distribucion_id' => $distribucion->id,
                    'estado' => 'PENDIENTE',
                ]);
            }

            // Actualizar estado de radicación
            $radicacion->update(['estado' => 'EN_APROBACION']);

            DB::commit();

            $resultadoNotificacion = $this->enviarNotificacionAreasDistribuidas(
                $radicacion->fresh(['proveedor']),
                $correosAreas
            );
            $mensajeToast = $this->resolverMensajeNotificacion(
                'Distribuciones actualizadas exitosamente.',
                $resultadoNotificacion
            );

            return toast(
                $mensajeToast['mensaje'],
                $mensajeToast['tipo'],
                route('radfact.radicaciones.show', $radicacion)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando distribuciones', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al actualizar distribuciones')->withInput();
        }
    }

    /**
     * Obtiene los correos válidos de las áreas incluidas en la distribución.
     */
    private function obtenerCorreosAreasDistribuidas(array $distribuciones): array
    {
        $areaIds = collect($distribuciones)
            ->pluck('area_id')
            ->filter()
            ->unique()
            ->values();

        if ($areaIds->isEmpty()) {
            return [];
        }

        $correos = RadFactArea::query()
            ->whereIn('id', $areaIds->all())
            ->pluck('correo')
            ->map(function ($correo) {
                return strtolower(trim((string) $correo));
            })
            ->filter();

        $correosInvalidos = $correos->filter(function ($correo) {
            return ! filter_var($correo, FILTER_VALIDATE_EMAIL);
        })->values();

        if ($correosInvalidos->isNotEmpty()) {
            Log::warning('Se omitieron correos inválidos de áreas RadFact', [
                'correos_invalidos' => $correosInvalidos->all(),
            ]);
        }

        return $correos
            ->filter(function ($correo) {
                return filter_var($correo, FILTER_VALIDATE_EMAIL);
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Enviar correo a cada área distribuida.
     */
    private function enviarNotificacionAreasDistribuidas(RadFactRadicacion $radicacion, array $correosAreas): array
    {
        $enviados = 0;
        $fallidos = [];

        foreach ($correosAreas as $correoArea) {
            try {
                Mail::to($correoArea)->send(new RadFactDistribucionNotificacionMail($radicacion));
                $enviados++;
            } catch (\Exception $e) {
                $fallidos[] = $correoArea;

                Log::warning('No se pudo enviar correo de radicación a área', [
                    'radicacion_id' => $radicacion->id,
                    'correo' => $correoArea,
                    'error' => $e->getMessage(),
                    'mailer' => config('mail.default'),
                    'smtp_host' => config('mail.mailers.smtp.host'),
                    'smtp_port' => config('mail.mailers.smtp.port'),
                ]);
            }
        }

        return [
            'total' => count($correosAreas),
            'enviados' => $enviados,
            'fallidos' => count($fallidos),
        ];
    }

    /**
     * Construye mensaje de resultado del envío de correos.
     */
    private function resolverMensajeNotificacion(string $mensajeBase, array $resultado): array
    {
        if (($resultado['total'] ?? 0) === 0) {
            return [
                'mensaje' => $mensajeBase.' No se enviaron correos porque las áreas no tienen un correo válido configurado.',
                'tipo' => 'warning',
            ];
        }

        if (($resultado['fallidos'] ?? 0) > 0) {
            return [
                'mensaje' => $mensajeBase." Correos enviados: {$resultado['enviados']}/{$resultado['total']}. Revise logs para más detalle.",
                'tipo' => 'warning',
            ];
        }

        return [
            'mensaje' => $mensajeBase,
            'tipo' => 'success',
        ];
    }
}
