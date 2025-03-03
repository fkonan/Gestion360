<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::all();
        return view("usuario.crearUsuario",compact("usuarios"));
    }

    public function crearNuevoUsuario()
    {
        return view("usuario.crearUsuario");
    }
}
