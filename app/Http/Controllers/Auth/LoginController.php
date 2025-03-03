<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); 
    }

    public function login(Request $request)
    {
        $request->validate([
            'documento' => 'required|numeric',
            'password' => 'required|string',
        ]);

        $user = Persona::where('PerNumDoc', $request->documento)->first()->usuario;

        if (!$user || !password_verify($request->password, $user->Password)) {
            return back()->withErrors(['documento' => 'Documento o contraseña incorrectos']);
        }

        if($user->persona->PerEstado == "INACTIVO"){
            return back()->withErrors(['documento' => 'Persona inactiva']);
        }

        if($user->UsuarioEstado == "INACTIVO"){
            return back()->withErrors(['documento' => 'Usuario inactivo']);
        }

        Auth::login($user);
        return redirect()->route('home');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
