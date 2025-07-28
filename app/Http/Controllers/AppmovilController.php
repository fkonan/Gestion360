<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Notificaciones;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppmovilController extends Controller
{
    public function indexGestionMovil(){
        return view("gestionWeb.appmovil.index");
    }

    public function usuariosConAppmovil(Request $request)
    {
        $query = $request->input('query');

        $usuarios = User::with('persona')
            ->whereHas('persona', function ($q) use ($query) {
                $q->where(DB::raw("CONCAT(PerNombres, ' ', PerApellidos)"), 'like', "%$query%")
                ->orWhere('PerNumDoc', 'like', "%$query%");
            })
            ->limit(10)
            ->get()
            ->map(function ($usuario) {
                return [
                    'id' => $usuario->IdUsuario,
                    'text' => $usuario->persona->PerNombres . ' ' . $usuario->persona->PerApellidos . ' - ' . $usuario->persona->PerNumDoc
                ];
            });

        return response()->json($usuarios);
    }

    public function notificaciones(){
        return view("gestionWeb.appmovil.notificaciones.index");
    }

    public function crearNotificacion(){
        return view("gestionWeb.appmovil.notificaciones.crearNotificacion");
    }

    public function registrarNotificacion(Request $request){

        $data = $request->all();
        $usuarios = $data['usuarios'] ?? null;
        $grupos = $data['grupos'] ?? null;

        $validator = Validator::make($data, [
            'titulo' => 'required|string|max:100',
            'bodyPush' => 'required|string|max:140',
            'bodyCompleto' => 'required|string',
            'programada' => 'nullable|date_format:Y-m-d\TH:i',
            'usuarios' => 'nullable|array|min:1',
            'grupos' => 'nullable|array|min:1',
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.string' => 'El título debe ser una cadena de texto.',
            'titulo.max' => 'El título no debe superar los 100 caracteres.',

            'bodyPush.required' => 'El mensaje corto (push) es obligatorio.',
            'bodyPush.string' => 'El mensaje corto debe ser una cadena de texto.',
            'bodyPush.max' => 'El mensaje corto no debe superar los 140 caracteres.',

            'bodyCompleto.required' => 'El mensaje completo es obligatorio.',
            'bodyCompleto.string' => 'El mensaje completo debe ser una cadena de texto.',

            'programada.date_format' => 'La fecha no tiene el formato correcto'
        ]);

        // Validación personalizada para evitar enviar a usuarios y grupos al mismo tiempo
        if (!empty($usuarios) && !empty($grupos)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('destino', 'No puede seleccionar usuarios y grupos al mismo tiempo.');
            });
        }

        // Validación personalizada para asegurarse de que se seleccione al menos un destino
        if (empty($usuarios) && empty($grupos)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('destino', 'Debe seleccionar al menos un usuario o un grupo.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try{
            //Informacion adicional para la notificacion
            $destinatarios = null;
            $privacidad = 'privada';

            if (!empty($usuarios)) {
                $usuarios = array_map('intval', $usuarios);
                $destinatarios = json_encode(['usuarios' => $usuarios]);
            } elseif (!empty($grupos)) {
                $grupos = array_map('strval', $grupos);
                $destinatarios = json_encode(['grupos' => $grupos]);

                if (in_array('clientes', $grupos)) {
                    $privacidad = 'publica';
                }
            }

            // Crear la notificación
            Notificaciones::create([
                'titulo' => $request->titulo,
                'bodyPush' => $request->bodyPush,
                'bodyCompleto' => $request->bodyCompleto,
                'destino' => $destinatarios,
                'privacidad' => $privacidad ?? 'privada',
                'programada' => $request->programada,
                'createdBy' => 'Gestion360'
            ]);

            return toastModal("Notificacion creada correctamente","success",route('notificaciones.index'));

        }catch(Exception $e){
            Log::error('Error al registrar la notificacion: ' . $e->getMessage());
            return toastModal("Error al registrar la notificacion","error",route('notificaciones.index'));
        }

    }

    public function cargarNotificaciones(){
        $notificaciones = Notificaciones::where('createdBy', 'Gestion360')->get()->map(function ($item) {
            return [
                'titulo' => $item->titulo,
                'bodyPush' => $item->bodyPush,
                'bodyCompleto' => $item->bodyCompleto,
                'destino' => $item->destino,
                'estado' => $item->estado,
                'privacidad' => $item->privacidad,
                'proceso' => $item->proceso,
                'programada' => $item->programada,
                'createdAt' => $item->createdAt,
            ];
        });
        
        return $notificaciones;
    }
}
