<?php

namespace App\Http\Controllers;

use App\Mail\CorreoCredenciales;
use App\Models\GESTIONADMIN\Persona;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
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
        return view("usuarios.listaUsuarios");
    }

    public function cargarDatos(){
        $usuarios = User::with('persona')->get();
        return $usuarios;
    }

    public function create(){
        $personas = Persona::all();
        $roles = Role::all();
        return view("usuarios.crearUsuario", compact("personas","roles"));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'idPersona' => 'required',
        ],[
            'idPersona.required' => 'Debe seleccionar una persona',
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
                'usuario' => $user->persona->datos->PerEmail,
                'contraseña' => $contraseñaPlana,
            ];

            //Se envia a una cola de correso
            //Mail::to($user->persona->datos->PerEmail)->queue(new CorreoCredenciales($datos));

            Mail::to($user->persona->datos->PerEmail)->send(new CorreoCredenciales($datos));
            DB::commit();
            return sweetAlertJson("Usuario creado exitosamente","success",route('usuarios.index'));
    
        }catch(Exception $e){
            DB::rollBack();
            Log::error('Error al crear el usuario: ' . $e->getMessage());
            return sweetAlertJson("Error al crear el usuario","error",route('usuarios.index'));
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
            return sweetAlertJson("Usuario modificado exitosamente","success",route('usuarios.index'));
    
        }catch(Exception $e){
            Log::error('Error al modificar el usuario: ' . $e->getMessage());
            return sweetAlertJson("Error al modificar el usuario","error",route('usuarios.index'));
        }
        
    }
}
