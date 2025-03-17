<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(){
        $usuarios = User::with('persona')->get();
        return view("usuarios.listaUsuarios",compact("usuarios"));
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
    }

    public function edit($id){

        if(!request()->ajax()){
            return $this->index();
        }

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

        User::findOrFail($id)->update($request->all());     

        return response()->json([
            'redirect' => route('usuarios.index'),
            'type' => 'success', 
            'title' => 'Usuario modificado exitosamente'
        ]); 

    }
}
