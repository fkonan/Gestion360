<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermisosController extends Controller
{
    public function edit($id){

        $usuario = User::find($id);

        $permisosDisponibles = Permission::all();
        $permisosUsuario = $usuario->getAllPermissions();
        return view("permisos.permisosUsuario",compact("usuario","permisosUsuario","permisosDisponibles"))->render();
    }
}
