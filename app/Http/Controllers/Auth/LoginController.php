<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GESTIONADMIN\Persona;
use App\Services\Auth\LoginValidatorService;
use App\Services\Auth\RegistroSesionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            // 'g-recaptcha-response' => 'required|captcha',
        ],[
            'identificacion.required' => 'La identificación es obligatoria.',
            'password.required' => 'La contraseña es obligatoria.',
            'g-recaptcha-response.required' => 'El captcha es obligatorio.',
            'g-recaptcha-response.captcha' => 'Captcha inválido, por favor inténtalo de nuevo.',
        ]);

        try{
            // Buscar usuario relacionado a la persona
            $persona = Persona::with(['usuario'])
                ->where('PerNumDoc', $request->identificacion)
                ->first();

            $user = $persona?->usuario;

            // Validar credenciales
            if (!$user || !password_verify($request->password, $user->Password)) {
                return back()->withInput()->withErrors(['identificacion' => 'Identificación o contraseña incorrectos']);
            }

            // Validaciones adicionales (empleado activo, estados, etc.)
            $validador = new LoginValidatorService();
            $resultado = $validador->validar($user, $request->identificacion);

            if ($resultado) {
                return toast($resultado['message'], $resultado['type']);
            }

            // Registrar evento de login
            RegistroSesionService::registrar('LOGIN', $user->IdUsuario);

            // Iniciar sesión
            Auth::login($user);
            return redirect()->intended(route('home'));
        }catch(Exception $e){
            Log::error('Error al hacer login', ['exception' => $e]);
            return toast('Error en el login', 'danger');
        }
    }

    public function logout(){
        try{
            RegistroSesionService::registrar('LOGOUT', Auth::id());
            Auth::logout();
            return toast('Sesion cerrada exitosamente', 'success',redirect()->route('login'));

        }catch(Exception $e){
            Log::error('Error al hacer logout: ' . $e->getMessage());
            return toast('Error en el logout', 'danger');
        }
    }
}
