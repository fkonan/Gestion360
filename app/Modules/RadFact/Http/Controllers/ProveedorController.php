<?php

namespace App\Modules\RadFact\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RadFact\Http\Requests\StoreProveedorRequest;
use App\Modules\RadFact\Http\Requests\UpdateProveedorRequest;
use App\Modules\RadFact\Models\RadFactProveedor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
{
    /**
     * Listar proveedores
     */
    public function index()
    {
        return view('radfact::proveedores.index');
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
            $query = RadFactProveedor::with('usuario');

            // Búsqueda
            if (! empty($search)) {
                $likeSearch = "%{$search}%";
                $query->where(function ($q) use ($likeSearch) {
                    $q->where('documento', 'like', $likeSearch)
                        ->orWhere('razon_social', 'like', $likeSearch)
                        ->orWhere('nombres', 'like', $likeSearch)
                        ->orWhere('apellidos', 'like', $likeSearch)
                        ->orWhere('correo', 'like', $likeSearch)
                        ->orWhere('telefono', 'like', $likeSearch);
                });
            }

            // Total
            $total = $query->count();

            // Ordenamiento y paginación
            $rows = $query->orderBy($sort, $order)
                ->skip($offset)
                ->take($limit)
                ->get()
                ->map(function ($proveedor) {
                    return [
                        'id' => $proveedor->id,
                        'tipo_documento' => $proveedor->tipo_documento,
                        'documento' => $proveedor->documento,
                        'nombres' => $proveedor->nombres,
                        'apellidos' => $proveedor->apellidos,
                        'razon_social' => $proveedor->razon_social,
                        'nombre_completo' => $proveedor->nombre_completo,
                        'correo' => $proveedor->correo ?? '-',
                        'telefono' => $proveedor->telefono ?? '-',
                        'created_at' => $proveedor->created_at->format('d/m/Y H:i'),
                        'usuario' => $proveedor->usuario ? $proveedor->usuario->name : 'N/A',
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (\Exception $e) {
            Log::error('Error cargando datos de proveedores', ['error' => $e->getMessage()]);

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
        return view('radfact::proveedores.create');
    }

    /**
     * Guardar nuevo proveedor
     */
    public function store(StoreProveedorRequest $request)
    {
        try {
            $proveedor = RadFactProveedor::create([
                ...$request->validated(),
                'user_id' => auth()->user()->IdUsuario,
            ]);

            return redirect()->route('radfact.proveedores.index')
                ->with('success', 'Proveedor creado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error creando proveedor', ['error' => $e->getMessage()]);

            return back()->with('error', 'Error al crear proveedor')->withInput();
        }
    }

    /**
     * Mostrar proveedor
     */
    public function show(RadFactProveedor $proveedor)
    {
        $proveedor->load(['radicaciones' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('radfact::proveedores.show', compact('proveedor'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(RadFactProveedor $proveedor)
    {
        return view('radfact::proveedores.edit', compact('proveedor'));
    }

    /**
     * Actualizar proveedor
     */
    public function update(UpdateProveedorRequest $request, RadFactProveedor $proveedor)
    {
        try {
            $proveedor->update($request->validated());

            return toastModal('Proveedor actualizado exitosamente', 'success', route('radfact.proveedores.index'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al modificar la persona: '.$e->getMessage());

            return toastModal('Error al modificar proveedor', 'error', route('radfact.proveedores.index'));
        }
    }

    /**
     * Buscar proveedor por documento (AJAX)
     */
    public function buscarPorDocumento(Request $request)
    {
        $documento = $request->get('documento');

        $proveedor = RadFactProveedor::where('documento', $documento)->first();

        if ($proveedor) {
            return response()->json([
                'encontrado' => true,
                'proveedor' => $proveedor,
            ]);
        }

        return response()->json(['encontrado' => false]);
    }
}
