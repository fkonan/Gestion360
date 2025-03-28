<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermisosController extends Controller
{
    public function edit($id){

        $usuario = User::findOrFail($id);
        $permisosDisponibles = Permission::all(); 
        $permisosDirectos = $usuario->permissions->pluck('id')->toArray();
        $permisosHeredados = $usuario->getAllPermissions()->pluck('id')->diff($permisosDirectos)->toArray();

        return view("usuarios.permisosUsuario",compact("usuario","permisosDirectos","permisosHeredados","permisosDisponibles"))->render();
    }

    public function update(Request $request, $id){

        $usuario = User::findOrFail($id);
   
        $usuario->syncPermissions($request->permissions);
       
        return response()->json([
            'redirect' => route('usuarios.index'),
            'type' => 'success', 
            'title' => 'Permisos actualizados correctamente'
        ]); 
    }
}
