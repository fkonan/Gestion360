<?php

namespace App\Http\Controllers;

use App\Mail\CorreoUsuarioTemporal;
use App\Models\GESTIONADMIN\UsuarioTemporal;
use App\Services\BloqueoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmpleadoController extends Controller
{   
    //Modal solicitud nuevo ingreso - gestiom empleado
    public function nuevoIngreso(){
        return view('empleados.nuevoIngreso');
    }

    public function gestionNuevoIngreso(Request $request){
        $validator = Validator::make($request->all(), [
            'identificacion' => 'required|numeric|digits_between:5,20',
            'nombres'        => 'required|string|max:100',
            'apellidos'      => 'required|string|max:100',
            'email'          => 'required|email|max:150',
        ], [
            'identificacion.required' => 'La identificación es obligatoria.',
            'identificacion.numeric'  => 'La identificación debe ser un número.',
            'identificacion.digits_between' => 'La identificación debe tener entre 5 y 20 dígitos.',
            'nombres.required'        => 'El nombre es obligatorio.',
            'nombres.string'          => 'El nombre debe ser texto.',
            'nombres.max'             => 'El nombre no puede exceder 100 caracteres.',
            'apellidos.required'      => 'Los apellidos son obligatorios.',
            'apellidos.string'        => 'Los apellidos deben ser texto.',
            'apellidos.max'           => 'Los apellidos no pueden exceder 100 caracteres.',
            'email.required'          => 'El correo electrónico es obligatorio.',
            'email.email'             => 'El correo electrónico debe ser válido.',
            'email.max'               => 'El correo electrónico no puede exceder 150 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            //Crear bloqueo SIPLAFT
            $descripcionBloqueo = "REQUIERE FIRMA NORMAS SIPLAFT";

            $bloqueo = BloqueoService::crearNovedadEmpleado(
                $request->identificacion, 
                $descripcionBloqueo, 
                BloqueoService::ID_BLOQUEO_LOGTRANS_SIPLAFT
            );

            if(!$bloqueo){
                return toastModal("Error al registrar la solicitud, intente nuevamente", "error",route('gestion-incapacidades.index'));
            }

            DB::beginTransaction();

            //Crear usuario temporal
            $token = Str::random(40);
            $nombreCompleto = $request->nombres ." ". $request->apellidos;

            //Crear o actualizar un nuevo registro de usuario temporal
            UsuarioTemporal::updateOrCreate(
                ['identificacion' => $request->identificacion], 
                [
                    'correo' => $request->email,
                    'token' => $token,
                    'nombreCompleto' => $nombreCompleto,
                    'estado' => true,
                ]
            );

            //URL validacion de token 
            $baseUrl = config('app.validar_temporal_url');
            $url = $baseUrl . $token;

            //Enviar correo
            Mail::to($request->email)->send(new CorreoUsuarioTemporal([
                'nombre' => $nombreCompleto,
                'token' => $token,
                'url' => $url
            ]));

            DB::commit();
            return toastModal("Solicitud creada exitosamente","success",route('gestion-incapacidades.index'));

        }catch(Exception $e){
            DB::rollBack();
            Log::error("Error al registrar la solicitud: " . $e->getMessage());
            return toastModal("Error al registrar la solicitud", "error",route('gestion-incapacidades.index'));
        }
    }
}
