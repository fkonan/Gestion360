<?php

namespace App\Http\Controllers;

use App\Mail\CorreoRecuperacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        try{
            $request->validate(['email' => 'required|email']);

            $token = Str::random(60);

            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]
            );

            $resetLink = url('/reset-password/' . $token . '?email=' . urlencode($request->email));


            $datos = ['link' => $resetLink];
            Mail::to($request->email)->send(new CorreoRecuperacion($datos));

            return toast('Se ha enviado el enlace a tu correo', 'success', redirect()->route('login'));

        }catch(Exception $e){
            Log::error('Error al enviar enlace de recuperación: ' . $e->getMessage());
            return toast('Ocurrió un error al enviar el enlace de recuperación', 'error', redirect()->route('login'));
        }
       
    }
}
