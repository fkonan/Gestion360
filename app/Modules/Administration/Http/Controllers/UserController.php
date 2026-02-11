<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GESTIONADMIN\Persona;
use App\Models\User;
use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Shared\Services\UsuarioService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return view('administration::usuarios.index');
    }

    public function cambiarEstado($id)
    {
        try {
            $resultado = UsuarioService::cambiarEstado($id, Auth::id());

            return response()->json($resultado);
        } catch (Exception $e) {
            Log::error('Error al actualizar el estado del usuario: '.$e->getMessage());

            return response()->json([
                'message' => 'Error al actualizar el estado del usuario',
                'type' => 'danger',
            ]);
        }
    }

    public function cargarDatos(Request $request)
    {

        try {
            // Paginacion y parametros de ordenamiento
            $limit = $request->get('limit', 25);
            $offset = $request->get('offset', 0);
            $search = $request->get('search');
            $order = $request->get('order', 'desc');
            $sort = $request->get('sort');

            // Traer documentos válidos desde Oracle (los empleados con estado 1 y estborrado 0)
            $documentosEmpleados = EmpleadoService::documentosEmpleadosValidos();

            $query = $this->buildUsuariosQuery($documentosEmpleados, $search, $sort, $order);
            $total = $query->count();

            // Obtener solo los paginados
            $usuariosPaginados = $query
                ->skip($offset)
                ->take($limit)
                ->get();

            // Solo traemos los centros de costo de esos usuarios visibles
            $identificacionesPagina = $usuariosPaginados->pluck('persona.PerNumDoc')->toArray();
            $centrosCosto = EmpleadoService::obtenerCentrosCostoMasivos($identificacionesPagina);

            // Formar los datos de la respuesta
            $rows = $usuariosPaginados->map(function ($item) use ($centrosCosto) {
                $doc = $item->persona->PerNumDoc;

                return [
                    'PerNumDoc' => $doc,
                    'nombreCompleto' => ucfirst(strtolower($item->persona->PerNombres.' '.$item->persona->PerApellidos)),
                    'fechaHoraRegistro' => $item->UsuFecReg.' '.$item->UsuHorReg,
                    'estado' => $item->UsuarioEstado,
                    'IdUsuario' => $item->IdUsuario,
                    'rol' => $item->roles->pluck('name')->first() ?: 'SIN ROL',
                    'centroCosto' => $centrosCosto[$doc] ?? 'No asignado',
                ];
            });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (Exception $e) {
            Log::error('Error al cargar los datos de los usuarios: '.$e->getMessage());
        }
    }

    private function buildUsuariosQuery(array $documentosEmpleados, ?string $search, ?string $sort, string $order)
    {
        $query = User::with(['persona', 'roles'])
            ->whereHas(
                'persona',
                fn ($q) => $q->whereIn('PerNumDoc', $documentosEmpleados)
            );

        if (! empty($search)) {
            $likeSearch = "%{$search}%";

            $query->where(function ($q) use ($likeSearch) {
                $q->where('UsuFecReg', 'like', $likeSearch)
                    ->orWhere('UsuHorReg', 'like', $likeSearch)
                    ->orWhereHas('persona', function ($q2) use ($likeSearch) {
                        $q2->where('PerNumDoc', 'like', $likeSearch)
                            ->orWhereRaw("CONCAT(PerNombres, ' ', PerApellidos) LIKE ?", [$likeSearch]);
                    });
            });
        }

        if ($sort === 'fechaHoraRegistro') {
            $query->orderBy('UsuFecReg', $order)
                ->orderBy('UsuHorReg', $order);
        }

        return $query;
    }

    public function create()
    {
        $personas = Persona::all();
        $roles = Role::all();

        return view('administration::usuarios.crearUsuario', compact('personas', 'roles'));
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'idPersona' => 'required|unique:_usuarios,idPersona',
        ], [
            'idPersona.required' => 'Debe seleccionar una persona',
            'idPersona.unique' => 'La persona seleccionada ya tiene un usuario asignado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $service = new UsuarioService;
            $service->crearUsuarioConRol($request->only('idPersona', 'rol'));

            return toastModal('Usuario creado exitosamente', 'success', route('usuarios.index'));
        } catch (Exception $e) {
            Log::error('Error al crear el usuario: '.$e->getMessage());

            return toastModal('Error al crear el usuario', 'error', route('usuarios.index'));
        }
    }

    public function edit($id)
    {
        $usuario = User::findOrFail($id);

        return view('administration::usuarios.editarUsuario', compact('usuario'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'UsuarioEstado' => 'required',
            'Verificado' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->all();
            if ($request->has('Password')) {
                $data['Password'] = Hash::make($request->Password);
            }

            User::findOrFail($id)->update($data);

            return toastModal('Usuario modificado exitosamente', 'success', route('usuarios.index'));
        } catch (Exception $e) {
            Log::error('Error al modificar el usuario: '.$e->getMessage());

            return toastModal('Error al modificar el usuario', 'error', route('usuarios.index'));
        }
    }
}
