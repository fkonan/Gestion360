<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Modulo;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        $modulos = $this->modulosConPermisos();
        return view("roles.crearRol",compact('modulos'));
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name|max:50|regex:/^[\pL\s]+$/u',
        ], [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.unique' => 'El nombre del rol ya existe.',
            'name.max' => 'El nombre no puede tener más de 50 caracteres.',
            'name.regex' => 'El nombre no es valido.'
        ]);

        if ($validator->fails()) {
            return toast($validator->errors()->first(), 'danger');
        }
       
        try{
            $rol = new Role();
            $rol->name = $request->name;
            $rol->guard_name = 'web';
            $rol->created_at = now();
            $rol->updated_at = now();
            $rol->save();

            //Sincronizar los permisos
            $rol->syncPermissions($request->permissions ?? []);

            return toast('Rol '. $rol->name . ' creado correctamente', 'success', redirect()->route('roles.index'));
         
        }catch(Exception $e){
            Log::error('Error al crear el rol: ' . $e->getMessage());
            return response()->json([
                'redirect' => route('roles.index'),
                'type' => 'error', 
                'title' => 'Error al crear el rol',
            ]); 
        }
    }

    //relaciona los permisos con los modulos en base al nombre => formato permiso: "modulo.submodulo.permiso"
    private function modulosConPermisos(){
        $modulos = Modulo::where('ModuloEstado', 'ACTIVO')
        ->with('submodulos')
        ->get();

        foreach ($modulos as $modulo) {
            $nombreModulo = normalizarNombre($modulo->ModNom);
            
            foreach ($modulo->submodulos as $submodulo) {
                $nombreSubmodulo = normalizarNombre($submodulo->SubModNom);
        
                $prefix = "$nombreModulo.$nombreSubmodulo";
                $submodulo->permisos = Permission::where('name', 'like', "$prefix.%")->get();
            }
        }
        return $modulos;
    }

    public function permisosRol($id){
        $role = Role::findOrFail($id);
        $modulos = $this->modulosConPermisos();
        $permisosAsignados = $role->permissions->pluck('id')->toArray();
        return view('roles.permisosRol', compact('modulos', 'role', 'permisosAsignados'));
    }

    public function updatePermisos(Request $request, $id){
        if($request->name){
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'max:50',
                    'regex:/^[\pL\s]+$/u',
                    Rule::unique('roles')->ignore($id),
                ],
            ], [
                'name.required' => 'El campo nombre es obligatorio.',
                'name.unique' => 'El nombre del rol ya existe.',
                'name.max' => 'El nombre no puede tener más de 50 caracteres.',
                'name.regex' => 'El nombre no es valido.',
            ]);

            if ($validator->fails()) {
                return toast($validator->errors()->first(), 'danger');
            }
        }

        try{
            $rol = Role::findOrFail($id);

            //Actualzar nombre
            if($request->name){
                $rol->name = $request->name;
                $rol->save();    
            }
           
            //Sincronizar los permisos
            $rol->syncPermissions($request->permissions ?? []);

            return toast('Permisos actualizados correctamente', 'success',redirect()->route('roles.index'));
           
        }catch(Exception $e){
            Log::error('Error al actualizar los permisos: ' . $e->getMessage());
            return toast('Error al actualizar los permisos', 'danger',redirect()->route('roles.index'));
        }
    }
    
    public function editRolUsuario($id){
        $usuario = User::findOrFail($id);
        /* $rolesDisponibles = Role::where('name', '!=', User::SUPER_ADMIN_ROLE)->get(); */
        $rolesDisponibles = Role::all();
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
