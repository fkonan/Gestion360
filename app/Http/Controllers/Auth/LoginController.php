<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm(){
        return view('auth.login'); 
    }

    public function login(Request $request){
        $request->validate([
            'documento' => 'required|numeric',
            'password' => 'required|string',
        ]);

        $user = Persona::where('PerNumDoc', $request->documento)->first()?->usuario;

        if (!$user) {
            session()->flash('alert', ['type' => 'warning', 'title' => 'Documento o contraseña incorrectos']);
            return back();
        }

        if(!password_verify($request->password, $user->Password)){
            session()->flash('alert', ['type' => 'error','title' => 'Contraseña incorrecta']);
            return back();  
        }

        if($user->persona->PerEstado == "INACTIVO"){
            session()->flash('alert', ['type' => 'warning','title' => 'Persona inactiva']);
            return back();
        }

        if($user->UsuarioEstado == "INACTIVO"){
            session()->flash('alert', ['type' => 'warning','title' => 'Usuario inactivo']);
            return back();
        }

        if($user->UsuarioEstado == "SUSPENDIDO"){
            session()->flash('alert', ['type' => 'warning','title' => 'Usuario suspendido']);
            return back();
        }

        Auth::login($user);
        return redirect()->intended(route('home'));
    }

    public function logout(){
        Auth::logout();
        return redirect()->route('login');
    }
}
