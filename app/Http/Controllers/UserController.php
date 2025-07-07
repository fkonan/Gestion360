<?php

namespace App\Http\Controllers;

use App\Mail\CorreoCredenciales;
use App\Models\GESTIONADMIN\Persona;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(){
        return view("usuarios.index");
    }

    public function cambiarEstado($id){
        $usuarioAuth = Auth::user();

        if($id == $usuarioAuth->IdUsuario){
             return response()->json([
                'message' => 'No se puede cambiar el estado asi mismo',
                'type' => 'warning'
            ]);
        }

        try{
            $usuario = User::findOrFail($id);
            $usuario->UsuarioEstado = $usuario->UsuarioEstado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
            $usuario->save();
            return response()->json([
                'message' => 'Estado cambiado a ' . $usuario->UsuarioEstado,
                'type' => 'success'
            ]);
           
        }catch(Exception $e){
            Log::error('Error al actualizar el estado del usuario: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar el estado del usuario',
                'type' => 'danger'
            ]);
        }
    }

    public function cargarDatos(Request $request){
        //paginacion
        $limit = $request->get('limit', 25); // Número de registros por página
        $offset = $request->get('offset', 0); // Desde qué registro empezar
        $search = $request->get('search');
        
        $order = $request->get('order', 'desc');
        $sort = $request->get('sort');

        $usuarios = User::with(['persona','roles']);

        // Validar campo de ordenamiento
        if ($sort === 'fechaHoraRegistro') {
            $usuarios = $usuarios
                ->orderBy('UsuFecReg', $order)
                ->orderBy('UsuHorReg', $order);
        } 

        //buscador
        if (!empty($search)) {
            $usuarios->where(function ($q) use ($search) {
            $q->where('UsuFecReg', 'like', "%$search%")
            ->orWhere('UsuHorReg', 'like', "%$search%")
            ->orWhereHas('persona', function ($q2) use ($search) {
                $q2->where('PerNumDoc', 'like', "%$search%")
                    ->orWhere(DB::raw("CONCAT(PerNombres, ' ', PerApellidos)"), 'like', "%$search%");
                });
            });
        }

        //datos de la pagina 
        $total = $usuarios->count();
        $rows = $usuarios
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'PerNumDoc' => $item->persona->PerNumDoc,
                    'nombreCompleto' => $item->persona->PerNombres . ' ' . $item->persona->PerApellidos,
                    'fechaHoraRegistro' => $item->UsuFecReg . ' ' . $item->UsuHorReg,
                    'estado' => $item->UsuarioEstado,
                    'IdUsuario' => $item->IdUsuario,
                    'rol' => $item->roles->pluck('name')->first() ?: 'SIN ROL',
                ];
            });

        return response()->json([
         'total' => $total,
         'rows' => $rows
          ]);
    }

    public function create(){
        $personas = Persona::all();
        $roles = Role::all();
        return view("usuarios.crearUsuario", compact("personas","roles"));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'idPersona' => 'required|unique:_usuarios,idPersona',
        ],[
            'idPersona.required' => 'Debe seleccionar una persona',
            'idPersona.unique' => 'La persona seleccionada ya tiene un usuario asignado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try{
            $contraseñaPlana = Str::random(12);

            $user = new User();
            $user->idPersona = $request->idPersona;
            $user->Password =  bcrypt($contraseñaPlana);
            $user->UsuarioEstado = "ACTIVO";
            $user->Verificado = "TRUE";
            $user->UsuFecReg = now();
            $user->UsuHorReg = now();
            $user->UsuReg = "Gestion";
            $user->save();

            //se asigna el rol 
            $user->syncRoles($request->rol);

            $datos = [
                'usuario' => $user->persona->PerNumDoc,
                'contraseña' => $contraseñaPlana,
            ];

            Mail::to($user->persona->datos->PerEmail)->send(new CorreoCredenciales($datos));
            DB::commit();
            return toastModal("Usuario creado exitosamente","success",route('usuarios.index'));
    
        }catch(Exception $e){
            DB::rollBack();
            Log::error('Error al crear el usuario: ' . $e->getMessage());
            return toastModal("Error al crear el usuario","error",route('usuarios.index'));
        }
    }

    public function edit($id){
        $usuario = User::findOrFail($id);
        return view("usuarios.editarUsuario",compact("usuario"))->render();
    }

    public function update(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'UsuarioEstado' => 'required',
            'Verificado' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $data = $request->all();
            if ($request->has('Password')) {
                $data['Password'] = Hash::make($request->Password);
            }

            User::findOrFail($id)->update($data);
            return toastModal("Usuario modificado exitosamente","success",route('usuarios.index'));
    
        }catch(Exception $e){
            Log::error('Error al modificar el usuario: ' . $e->getMessage());
            return toastModal("Error al modificar el usuario","error",route('usuarios.index'));
        }
        
    }
}
