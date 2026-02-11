<?php

namespace App\Modules\RadFact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RadFact\Models\RadFactAprobacion;
use App\Modules\RadFact\Models\RadFactArea;
use App\Modules\RadFact\Models\RadFactDistribucion;
use App\Modules\RadFact\Models\RadFactProveedor;
use App\Modules\RadFact\Models\RadFactRadicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:rad_fact_proveedores,id',
            'num_factura' => 'required|string|max:50',
            'num_contrato' => 'nullable|string|max:50',
            'numero_pagos' => 'required|integer|min:1',
            'fecha_radicacion' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_radicacion',
            'necesita_visto_bueno' => 'boolean',
            'descripcion' => 'nullable|string',
            'valor' => 'required|numeric|min:0',
            'pdf' => 'nullable|file|mimes:pdf|max:10240',
            'observacion' => 'nullable|string',
            // Distribuciones
            'distribuciones' => 'required|array|min:1',
            'distribuciones.*.area_id' => 'required|exists:rad_fact_areas,id',
            'distribuciones.*.porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        // Validar que los porcentajes sumen 100
        $totalPorcentaje = collect($validated['distribuciones'])->sum('porcentaje');
        if (abs($totalPorcentaje - 100) > 0.01) {
            return back()->with('error', 'Los porcentajes deben sumar 100%')->withInput();
        }

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

            return redirect()->route('radfact.radicaciones.index')->with('success', 'Radicación creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando radicación', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al crear radicación')->withInput();
        }
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
    public function updateDistribuciones(Request $request, RadFactRadicacion $radicacion)
    {
        $validated = $request->validate([
            'distribuciones' => 'required|array|min:1',
            'distribuciones.*.area_id' => 'required|exists:rad_fact_areas,id',
            'distribuciones.*.porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        // Validar que los porcentajes sumen 100
        $totalPorcentaje = collect($validated['distribuciones'])->sum('porcentaje');
        if (abs($totalPorcentaje - 100) > 0.01) {
            return back()->with('error', 'Los porcentajes deben sumar 100%')->withInput();
        }

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

            return redirect()->route('radfact.radicaciones.show', $radicacion)
                ->with('success', 'Distribuciones actualizadas exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando distribuciones', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al actualizar distribuciones')->withInput();
        }
    }
}
