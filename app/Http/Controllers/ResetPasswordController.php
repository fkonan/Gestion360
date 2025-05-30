<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\PersonaDatos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log ;

class ResetPasswordController extends Controller
{
    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => [
                'required',
                'confirmed',
                'min:6',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            ],[
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial.',
        ]);

        try{
            $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

            if (!$record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
                return toast('Token inválido o expirado', 'danger', redirect()->route('login'));
            }

            $personaDatos = PersonaDatos::where('PerEmail', $request->email)->first();
            $usuario = $personaDatos->persona->usuario;

            if ($personaDatos && $usuario) {
                $usuario->Password = bcrypt($request->password);
                $usuario->save();

                DB::table('password_resets')->where('email', $request->email)->delete();

                return toast('Contraseña actualizada correctamente', 'success', redirect()->route('login'));
            }

            return toast('Ocurrio un error al actualizar la contraseña', 'danger', redirect()->route('login'));

        }catch(Exception $e){
            Log::error('Error al validar datos de restablecimiento de contraseña: ' . $e->getMessage());
            return toast('Ocurrió un error al validar los datos', 'error', redirect()->route('login'));
        } 
    }
}
