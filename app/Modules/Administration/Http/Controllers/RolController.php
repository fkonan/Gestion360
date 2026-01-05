<?php

namespace App\Modules\Administration\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Modules\Administration\Services\ModuloService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view("roles.index",compact("roles"));
    }

    public function create(ModuloService $moduloService){
        $modulos = $this->modulosConPermisos($moduloService);
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
            $rol->name = strtoupper($request->name);
            $rol->guard_name = 'web';
            $rol->created_at = now();
            $rol->updated_at = now();
            $rol->save();

            //Sincronizar los permisos
            $rol->syncPermissions($request->permissions ?? []);

            return toast('Rol '. $rol->name . ' creado correctamente', 'success', redirect()->route('roles.index'));

        }catch(Exception $e){
            Log::error('Error al crear el rol: ' . $e->getMessage());
            return toast('Error al crear el rol', 'danger');
        }
    }

    //relaciona los permisos con los modulos en base al nombre => formato permiso: "modulo.submodulo.permiso"
    //ejemplo: "administracion.reportes.acceder"
    private function modulosConPermisos($moduloService){

        $modulos = $moduloService->modulosActivosConSubmodulos();

        foreach ($modulos as $modulo) {
            // Normalizar el nombre del módulo para evitar problemas con caracteres especiales
            $nombreModulo = normalizarNombre($modulo->ModNom);

            foreach ($modulo->submodulos as $submodulo) {
                // Normalizar el nombre del submódulo
                $nombreSubmodulo = normalizarNombre($submodulo->SubModNom);

                // Crear el prefijo para los permisos
                $prefix = "$nombreModulo.$nombreSubmodulo";

                // Asignar los permisos al submódulo
                $submodulo->permisos = Permission::where('name', 'like', "$prefix.%")->get();
            }
        }
        return $modulos;
    }

    public function permisosRol($id, ModuloService $moduloService){
        $role = Role::findOrFail($id);
        $modulos = $this->modulosConPermisos($moduloService);
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
        $usuarioAuth = Auth::user();

        if($id == $usuarioAuth->IdUsuario){
            return toastModal("No puedes cambiar tus propios roles", "warning");
        }

        try{
            $usuario = User::findOrFail($id);
            $usuario->syncRoles($request->roles);

            return toastModal("Roles actualizados correctamente para el usuario " . $usuario->persona->nombreCompleto(), "success",route('usuarios.index'));

        }catch(Exception $e){
            Log::error('Error al actualizar los roles: ' . $e->getMessage());
            return toastModal("Error al actualizar los roles", "error",route('usuarios.index'));
        }
    }
}
