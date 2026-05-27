<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GESTIONADMIN\Persona;
use App\Modules\GestionRRHH\Services\EmpleadoService;
use App\Services\Auth\LoginValidatorService;
use App\Services\Auth\RegistroSesionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|numeric',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required|captcha',
        ], [
            'identificacion.required' => 'La identificación es obligatoria.',
            'password.required' => 'La contraseña es obligatoria.',
            'g-recaptcha-response.required' => 'El captcha es obligatorio.',
            'g-recaptcha-response.captcha' => 'Captcha inválido, por favor inténtalo de nuevo.',
        ]);

        try {
            $validador = new LoginValidatorService;

            $persona = Persona::with(['usuario'])
                ->where('PerNumDoc', $request->identificacion)
                ->first();

            $user = $persona?->usuario;

            // CASO 1 - No existe usuario en autogestion -> se crea uno si existe en logtrans
            if (! $user) {
                $resultadoLogtrans = $validador->validarLogtrans($request->identificacion, $request->password);

                if (is_array($resultadoLogtrans)) {
                    return toast($resultadoLogtrans['message'], $resultadoLogtrans['type']);
                }

                $user = $resultadoLogtrans;
            } else {

                // CASO 2 - Si existe usuario en autogestion -> se valida
                //!password_verify($request->password, $user->Password)
                if (false) {
                    return back()->withInput()->withErrors(['identificacion' => 'Identificación o contraseña incorrectos']);
                }

                // Validaciones adicionales
                $resultado = $validador->validar($user, $request->identificacion);
                if ($resultado) {
                    return toast($resultado['message'], $resultado['type']);
                }
            }

            // Actualizar roles
            EmpleadoService::asignarRolesLogtrans($request->identificacion, $user);

            RegistroSesionService::registrar('LOGIN', $user->IdUsuario);
            Auth::login($user);

            return redirect()->intended(route('home'));
        } catch (Exception $e) {
            Log::error('Error al hacer login', ['exception' => $e]);

            return toast('Error en el login, verfique la información', 'danger');
        }
    }

    public function logout()
    {
        try {
            RegistroSesionService::registrar('LOGOUT', Auth::id());
            Auth::logout();

            return toast('Sesion cerrada exitosamente', 'success', redirect()->route('login'));
        } catch (Exception $e) {
            Log::error('Error al hacer logout: '.$e->getMessage());

            return toast('Error en el logout', 'danger');
        }
    }
}
