<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function edit($id){
        
        $usuario = User::findOrFail($id);
        $rolesDisponibles = Role::all();
        $rolesUsuario = $usuario->getRoleNames();  
        return view("roles.rolesUsuario",compact("usuario","rolesUsuario","rolesDisponibles"))->render();
    }

    public function update(Request $request, $id){

        $usuario = User::findOrFail($id);
        $usuario->syncRoles($request->roles);
       
        return response()->json([
            'redirect' => route('usuarios.index'),
            'type' => 'success', 
            'title' => 'Roles actualizados correctamente'
        ]); 

    }
}
