<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GESTIONADMIN\Persona;
use App\Models\LOGTRANS\PerPersonas;
use App\Services\Auth\LoginValidatorService;
use App\Services\Auth\RegistroSesionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
   public function showLoginForm(Request $request)
   {
      $darkMode = $request->cookie('darkMode') === 'enabled';
      return view('auth.login', compact('darkMode'));
   }

   public function login(Request $request)
   {
      $request->validate([
         'identificacion' => 'required|numeric',
         'password' => 'required|string',
         // 'g-recaptcha-response' => 'required|captcha',
      ], [
         'identificacion.required' => 'La identificación es obligatoria.',
         'password.required' => 'La contraseña es obligatoria.',
         'g-recaptcha-response.required' => 'El captcha es obligatorio.',
         'g-recaptcha-response.captcha' => 'Captcha inválido, por favor inténtalo de nuevo.',
      ]);


      try {
         // Buscar usuario relacionado a la persona
         $persona = Persona::with(['usuario'])
            ->where('PerNumDoc', $request->identificacion)
            ->first();

         $user = $persona?->usuario;





         $per_persona = PerPersonas::where('IDENTIFICACION', $request->identificacion)->first();
         if ($per_persona && $this->checkSHA1Password($request->password, $per_persona->clave)) {


            if ($this->shouldRehashPassword($user)) {
               $user->update(['Password' => Hash::make($request->password)]);
            }

            Auth::login($user);

            // Opcional: Actualizar a bcrypt para mayor seguridad
            if ($this->shouldRehashPassword($user)) {
               $user->update(['clave' => Hash::make($request->password)]);
            }

            return redirect()->intended('/dashboard');
         }
         dd('error de clave');





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
      } catch (Exception $e) {
         Log::error('Error al hacer login', ['exception' => $e]);
         return toast('Error en el login', 'danger');
      }
   }

   public function logout()
   {
      try {
         RegistroSesionService::registrar('LOGOUT', Auth::id());
         Auth::logout();
         return toast('Sesion cerrada exitosamente', 'success', redirect()->route('login'));

      } catch (Exception $e) {
         Log::error('Error al hacer logout: ' . $e->getMessage());
         return toast('Error en el logout', 'danger');
      }
   }

   private function checkSHA1Password($plainPassword, $hashedPassword)
   {
      // Replicar exactamente el proceso de Java
      $isoString = mb_convert_encoding($plainPassword, 'ISO-8859-1', 'UTF-8');
      $sha1Hash = sha1($isoString);

      return hash_equals($hashedPassword, $sha1Hash);
   }

   private function shouldRehashPassword($user)
   {
      // Opcional: migrar gradualmente a bcrypt
      return Hash::needsRehash($user->Password);
      //return false; // Por ahora mantener SHA-1
   }
}
