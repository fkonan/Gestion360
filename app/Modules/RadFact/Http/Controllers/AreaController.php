<?php

namespace App\Modules\RadFact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RadFact\Http\Requests\StoreAreaRequest;
use App\Modules\RadFact\Models\RadFactArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AreaController extends Controller
{
    /**
     * Listar áreas
     */
    public function index()
    {
        return view('radfact::areas.index');
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
            $sort = $request->get('sort', 'created_at');

            // Query base
            $query = RadFactArea::with('usuario');

            // Búsqueda
            if (! empty($search)) {
                $likeSearch = "%{$search}%";
                $query->where(function ($q) use ($likeSearch) {
                    $q->where('area', 'like', $likeSearch)
                        ->orWhere('responsable', 'like', $likeSearch)
                        ->orWhere('correo', 'like', $likeSearch);
                });
            }

            // Total
            $total = $query->count();

            // Ordenamiento y paginación
            $rows = $query->orderBy($sort, $order)
                ->skip($offset)
                ->take($limit)
                ->get()
                ->map(function ($area) {
                    return [
                        'id' => $area->id,
                        'area' => $area->area,
                        'responsable' => $area->responsable,
                        'correo' => $area->correo ?? '-',
                        'subgerencia' => $area->subgerencia,
                        'compras' => $area->compras,
                        'created_at' => $area->created_at->format('d/m/Y H:i'),
                        'usuario' => $area->usuario ? $area->usuario->name : 'N/A',
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (\Exception $e) {
            Log::error('Error cargando datos de áreas', ['error' => $e->getMessage()]);

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
        return view('radfact::areas.create');
    }

    /**
     * Guardar nueva área
     */
    public function store(StoreAreaRequest $request)
    {
        try {
            RadFactArea::create([
                ...$request->validated(),
                'user_id' => auth()->user()->IdUsuario,
            ]);

            return redirect()->route('radfact.areas.index')
                ->with('success', 'Área creada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error creando área', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al crear área')->withInput();
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(RadFactArea $area)
    {
        return view('radfact::areas.edit', compact('area'));
    }

    /**
     * Actualizar área
     */
    public function update(StoreAreaRequest $request, RadFactArea $area)
    {
        try {
            $area->update($request->validated());

            return redirect()->route('radfact.areas.index')
                ->with('success', 'Área actualizada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error actualizando área', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al actualizar área')->withInput();
        }
    }

    /**
     * Listar áreas para select (AJAX)
     */
    public function listar()
    {
        $areas = RadFactArea::select('id', 'area', 'responsable')
            ->orderBy('area')
            ->get();

        return response()->json($areas);
    }
}
