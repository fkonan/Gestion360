<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function index(){
        $roles = Role::with('permissions')->get();
        return view("roles.listaRoles",compact("roles"));
    }

    public function permisosRol($id){
        $rol = Role::findOrFail($id);
        $permisosRol = $rol->permissions;
        $permisosDisponibles = Permission::all();
        return view("roles.permisosRol",compact("rol","permisosRol","permisosDisponibles"));
    }

    public function updatePermisos(Request $request, $id){
        try{
            $rol = Role::findOrFail($id);
            $rol->syncPermissions($request->permisosRol);
           
            return response()->json([
                'redirect' => route('roles.index'),
                'type' => 'success', 
                'title' => 'Permisos para el rol ' . $rol->name . ' actualizados correctamente',
            ]); 
        }catch(\Exception $e){
            return response()->json([
                'redirect' => route('roles.index'),
                'type' => 'danger', 
                'title' => 'Error al actualizar los permisos',
            ]);
        }
    }
    
    public function editRolUsuario($id){

        $usuario = User::findOrFail($id);
        $rolesDisponibles = Role::where('name', '!=', User::SUPER_ADMIN_ROLE)->get();
        $rolesUsuario = $usuario->getRoleNames();  
        return view("usuarios.rolesUsuario",compact("usuario","rolesUsuario","rolesDisponibles"));
    }

    public function updateRolUsuario(Request $request, $id){

        $usuario = User::findOrFail($id);
        $usuario->syncRoles($request->roles);
       
        return response()->json([
            'redirect' => route('usuarios.index'),
            'type' => 'success', 
            'title' => 'Roles actualizados correctamente para el usuario ' . $usuario->persona->nombreCompleto() ,
        ]); 

    }
}
