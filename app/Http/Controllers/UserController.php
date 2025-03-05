<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
}
