<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GESTIONADMIN\Persona;
use App\Models\GESTIONADMIN\PersonaDatos;
use App\Models\GESTIONADMIN\Sesion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm(Request $request){
        $darkMode = $request->cookie('darkMode') === 'enabled';
        return view('auth.login', compact('darkMode'));
    }

    public function login(Request $request){
        $request->validate([
            'identificacion' => 'required|numeric',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required|captcha',
        ],[
            'identificacion.required' => 'La identificación es obligatoria.',
            'password.required' => 'La contraseña es obligatoria.',
            'g-recaptcha-response.required' => 'El captcha es obligatorio.',
            'g-recaptcha-response.captcha' => 'Captcha inválido, por favor inténtalo de nuevo.',
        ]);

        try{
            $persona = Persona::with(['usuario'])
                ->where('PerNumDoc', $request->identificacion)
                ->first();

            $user = $persona?->usuario;

            if (!$user || !password_verify($request->password, $user->Password)) {
                return back()->withInput()->withErrors(['identificacion' => 'Identificación o contraseña incorrectos']);
            }

            //Validar si es empleado activo en Oracle
            $esEmpleado = DB::connection('oracle')
                ->table('PER_CONTRATO_PERSONA')
                ->where('identificacion', $request->identificacion)
                ->where('estado', 1)
                ->where('estborrado', 0)
                ->exists();

            if (!$esEmpleado) {
                return toast('Solo los empleados activos pueden iniciar sesión.', 'danger');
            }

            if($user->persona->PerEstado == "INACTIVO"){
                return toast('Persona inactiva, contacte con un administrador', 'warning');
            }

            if($user->UsuarioEstado == "INACTIVO"){
                return toast('Usuario inactivo, contacte con un administrador', 'warning');
            }

            if($user->UsuarioEstado == "SUSPENDIDO"){
                return toast('Usuario suspendido, contacte con un administrador', 'warning');
            }

            $this->registrarLogin($user->IdUsuario);

            Auth::login($user);
            return redirect()->intended(route('home'));
        }catch(Exception $e){
            Log::error('Error al hacer el login: ' . $e);
            return toast('Error en el login', 'danger');
        }
    }

    private function registrarLogin($IdUser){
        $sesion = new Sesion();
        $sesion->IdUser = $IdUser;
        $now = now();
        $sesion->SesionFechReg = $now;
        $sesion->SesionHorReg = $now;
        $sesion->SesionTipo = "LOGIN";
        $sesion->save();
    }

    public function logout(){
        try{
            $session = new Sesion();
            $session->IdUser = Auth::id();
            $session->SesionFechReg = now();
            $session->SesionHorReg = now();
            $session->SesionTipo = "LOGOUT";
            $session->save();

            Auth::logout();
            return toast('Sesion cerrada exitosamente', 'success',redirect()->route('login'));

        }catch(Exception $e){
            Log::error('Error al hacer logout: ' . $e->getMessage());
            return toast('Error en el logout', 'danger');
        }
    }
}
