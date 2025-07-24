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

    public function registrarNotificacion(Request $request){

        $data = $request->all();

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

        //validaciones adicionales para el destino de la notificacion
        $usuarios = $data['usuarios'] ?? null;
        $grupos = $data['grupos'] ?? null;

        if ((is_array($usuarios) && count($usuarios) > 0) && (is_array($grupos) && count($grupos) > 0)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('destino', 'No puede seleccionar usuarios y grupos al mismo tiempo.');
                $validator->errors()->add('grupodestinos', 'No puede seleccionar usuarios y grupos al mismo tiempo.');
            });
        }

        if ((!is_array($usuarios) || count($usuarios) === 0) && (!is_array($grupos) || count($grupos) === 0)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('destino', 'Debe seleccionar al menos un usuario o un grupo.');
                $validator->errors()->add('destino', 'Debe seleccionar al menos un grupo o un usuario.');
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

            if (!empty($data['usuarios'])) {
                $usuarios = array_map('intval', $data['usuarios']); 
                $destinatarios = json_encode(['usuarios' => $usuarios]);
            }

            if (!empty($data['grupos'])) {
                $grupos = array_map('strval', $data['grupos']);
                $destinatarios = json_encode(['grupos' => $grupos]);
            }

            // Define la privacidad de la notificacion
            if (in_array('clientes', $grupos)) {
                $privacidad = 'publica'; 
            }

            $notificacion = new Notificaciones();
            $notificacion->titulo = $request->titulo;
            $notificacion->bodyPush = $request->bodyPush;
            $notificacion->bodyCompleto = $request->bodyCompleto;
            $notificacion->destino = $destinatarios;
            $notificacion->privacidad = $privacidad ?? 'privada';
            $notificacion->programada = $request->programada ?? null;
            $notificacion->save();

            return toastModal("Notificacion creada correctamente","success",route('notificaciones.index'));

        }catch(Exception $e){
            Log::error('Error al registrar la notificacion: ' . $e->getMessage());
            return toastModal("Error al registrar la notificacion","error",route('notificaciones.index'));
        }

    }
}
