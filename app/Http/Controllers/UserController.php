<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::all();
        return view("usuario.listaUsuarios",compact("usuarios"));
    }

    public function crearNuevoUsuario()
    {
        return view("usuario.crearUsuario");
    }

    public function store(Request $request){

    }

    public function edit($id){
        $usuarios = User::all();

        if(!request()->ajax()){
            return view("usuario.listaUsuarios",compact("usuarios"));
        }

        $usuario = User::findOrFail($id);
    
        return view("usuario.editarUsuario",compact("usuario"))->render();
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
            'redirect' => route('admin.usuarios'),
            'type' => 'success', 
            'title' => 'Usuario modificado exitosamente'
        ]); 

    }
}
