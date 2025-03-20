<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\Sesion;
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
        ],[
            'documento.required' => 'El campo documento es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $user = Persona::where('PerNumDoc', $request->documento)->first()?->usuario;

        if (!$user || !password_verify($request->password, $user->Password)) {
            session()->flash('alert', ['type' => 'error', 'title' => 'Documento o contraseña incorrectos']);
            return back()->withInput();
        }

        if($user->persona->PerEstado == "INACTIVO"){
            session()->flash('alert', ['type' => 'warning','title' => 'Persona inactiva']);
            return back()->withInput();
        }

        if($user->UsuarioEstado == "INACTIVO"){
            session()->flash('alert', ['type' => 'warning','title' => 'Usuario inactivo']);
            return back()->withInput();
        }

        if($user->UsuarioEstado == "SUSPENDIDO"){
            session()->flash('alert', ['type' => 'warning','title' => 'Usuario suspendido']);
            return back()->withInput();
        }

        $this->registrarLogin($user->IdUsuario);

        Auth::login($user);
        return redirect()->intended(route('home')); 
    }

    private function registrarLogin($IdUser){
        $sesion = new Sesion();
        $sesion->IdUser = $IdUser;
        $sesion->SesionFechReg = now();
        $sesion->SesionHorReg = now();
        $sesion->SesionTipo = "LOGIN";
        return $sesion->save();
    }

    public function logout(){
        $session = new Sesion();
        $session->IdUser = Auth::id();
        $session->SesionFechReg = now();
        $session->SesionHorReg = now();
        $session->SesionTipo = "LOGOUT";
        $session->save();

        Auth::logout();
        session()->flash('alert', ['type' => 'success','title' => 'Sesion cerrada exitosamente']);
        return redirect()->route('login');
    }
}
