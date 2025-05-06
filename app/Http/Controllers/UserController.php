<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Persona;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        return view("usuarios.crearUsuario", compact("personas"));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'idPersona' => 'required',
            'Password' => 'required',
            'UsuarioEstado' => 'required',
            'Verificado' => 'required',
        ],[
            'idPersona.required' => 'Debe seleccionar una persona',
            'Password.required' => 'El campo contraseña es obligatorio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            $user = new User();
            $user->idPersona = $request->idPersona;
            $user->Password =  bcrypt($request->Password);
            $user->UsuarioEstado = $request->UsuarioEstado;
            $user->Verificado = $request->Verificado;
            $user->UsuFecReg = now();
            $user->UsuHorReg = now();
            $user->UsuReg = "AppMovil";
            $user->save();
    
            return response()->json([
                'redirect' => route('usuarios.index'),
                'type' => 'success', 
                'title' => 'Usuario creado exitosamente'
            ]); 
        }catch(Exception $e){
            Log::error('Error al crear el usuario: ' . $e->getMessage());
            return response()->json([
                'redirect' => route('usuarios.index'),
                'type' => 'error', 
                'title' => 'Error al crear el usuario',
            ]);
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
            User::findOrFail($id)->update($request->all());     

            return response()->json([
                'redirect' => route('usuarios.index'),
                'type' => 'success', 
                'title' => 'Usuario modificado exitosamente'
            ]); 
    
        }catch(Exception $e){
            Log::error('Error al modificar el usuario: ' . $e->getMessage());
            return response()->json([
                'redirect' => route('usuarios.index'),
                'type' => 'error', 
                'title' => 'Error al modificar el usuario',
            ]);
        }
        
    }
}
