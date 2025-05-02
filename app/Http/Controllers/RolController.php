<?php

namespace App\Http\Controllers;

use App\Models\Permisos;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function index(){
        $roles = Role::with('permissions')
            ->where('name', '!=', User::SUPER_ADMIN_ROLE)
            ->get();

        return view("roles.listaRoles",compact("roles"));
    }

    public function create(){
        return view("roles.crearRol");
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name|max:50',
        ], [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.unique' => 'El rol ya existe.',
            'name.max' => 'El nombre no puede tener más de 50 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
       
        try{
            $rol = new Role();
            $rol->name = $request->name;
            $rol->guard_name = 'web';
            $rol->created_at = now();
            $rol->updated_at = now();
            $rol->save();

            return response()->json([
                'title' => 'Rol '. $rol->name . ' creado correctamente',
                'redirect' => route('roles.index'),
                'type' => 'success', 
            ]);
         
        }catch(Exception $e){
            Log::error('Error al crear el rol: ' . $e->getMessage());
            return response()->json([
                'redirect' => route('roles.index'),
                'type' => 'error', 
                'title' => 'Error al crear el rol',
            ]); 
        }
    }

    public function permisosRol($id){
        $rol = Role::findOrFail($id);
        $permisosRol = $rol->permissions;
        $permisosDisponibles = Permisos::with('modulo')->get();
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
        }catch(Exception $e){
            Log::error('Error al actualizar los permisos: ' . $e->getMessage());
            return response()->json([
                'redirect' => route('roles.index'),
                'type' => 'error', 
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
        try{
            $usuario = User::findOrFail($id);
            $usuario->syncRoles($request->roles);
           
            return response()->json([
                'redirect' => route('usuarios.index'),
                'type' => 'success', 
                'title' => 'Roles actualizados correctamente para el usuario ' . $usuario->persona->nombreCompleto() ,
            ]); 
        }catch(Exception $e){
            Log::error('Error al actualizar los roles: ' . $e->getMessage());
            return response()->json([
                'redirect' => route('usuarios.index'),
                'type' => 'error', 
                'title' => 'Error al actualizar los roles',
            ]);
        }
      
    }
}
