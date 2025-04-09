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
        $permisosHeredadosConRol = [];
        $roles = $usuario->roles()->with('permissions')->get();

        // Obtener permisos heredados a través de los roles
        // y almacenarlos en un array asociativo con el nombre del rol
        foreach ($roles as $rol) {
            foreach ($rol->permissions as $permiso) {
                if (!in_array($permiso->id, $permisosDirectos)) {
                    $permisosHeredadosConRol[$permiso->id] = $rol->name;
                }
            }
        }

        $permisosHeredados = array_keys($permisosHeredadosConRol);

        return view("usuarios.permisosUsuario", compact(
            "usuario",
            "permisosDirectos",
            "permisosHeredados",
            "permisosHeredadosConRol",
            "permisosDisponibles"
        ));
    }

    public function update(Request $request, $id){

        $usuario = User::findOrFail($id);
        $usuario->syncPermissions($request->permissions);
       
        return response()->json([
            'redirect' => route('usuarios.index'),
            'type' => 'success', 
            'title' => 'Permisos actualizados correctamente para el usuario ' . $usuario->persona->nombreCompleto(),
        ]); 
    }
}
