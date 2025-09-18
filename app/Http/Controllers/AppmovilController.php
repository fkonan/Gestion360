<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\Notificaciones;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AppmovilController extends Controller
{
  public function indexGestionMovil()
  {
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

  public function notificaciones()
  {
    return view("gestionWeb.appmovil.notificaciones.index");
  }

  public function crearNotificacion()
  {
    return view("gestionWeb.appmovil.notificaciones.crearNotificacion");
  }

  public function registrarNotificacion(Request $request)
  {

    $data = $request->all();
    $usuarios = $data['usuarios'] ?? null;
    $grupos = $data['grupos'] ?? null;

    $validator = Validator::make($data, [
      'titulo' => 'required|string|max:50',
      'bodyPush' => 'required|string|max:140',
      'bodyCompleto' => 'nullable|string',
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

    try {
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
        'usuarioCrea' => Auth::user()->IdUsuario,
        'destino' => $destinatarios,
        'privacidad' => $privacidad ?? 'privada',
        'programada' => $request->programada,
        'createdBy' => 'Gestion360'

      ]);

      return toastModal("Notificacion creada correctamente", "success", route('notificaciones.index'));
    } catch (Exception $e) {
      Log::error('Error al registrar la notificacion: ' . $e->getMessage());
      return toastModal("Error al registrar la notificacion", "error", route('notificaciones.index'));
    }
  }

  public function cargarNotificaciones(Request $request)
  {
    $limit = $request->input('limit', 25);
    $offset = $request->input('offset', 0);
    $search = $request->input('search');

    $query = Notificaciones::where('createdBy', 'Gestion360');

    // Filtros de búsqueda
    if ($search) {
      $query->where(function ($q) use ($search) {
        $q->where('titulo', 'like', "%$search%")
          ->orWhere('bodyPush', 'like', "%$search%")
          ->orWhere('bodyCompleto', 'like', "%$search%")
          ->orWhere('estado', 'like', "%$search%")
          ->orWhere('privacidad', 'like', "%$search%")
          ->orWhere('proceso', 'like', "%$search%")
          ->orWhere('programada', 'like', "%$search%")
          ->orWhere('createdAt', 'like', "%$search%");
      });
    }

    $total = $query->count();
    $sort = $request->input('sort', 'createdAt');
    $order = $request->input('order', 'desc');

    $notificaciones = $query
      ->orderBy($sort, $order)
      ->skip($offset)
      ->take($limit)
      ->get()
      ->map(function ($item) {
        $destino = json_decode($item->destino, true);
        $destinoFormateado = '';

        if (isset($destino['grupos'])) {
          $destinoFormateado .= collect($destino['grupos'])->map(
            fn($grupo) =>
            "<span class='badge bg-secondary me-1'>$grupo</span>"
          )->implode(' ');
        } elseif (isset($destino['usuarios'])) {
          // Obtener los usuarios relacionados al destino
          $usuarios = User::with('persona')
            ->whereIn('IdUsuario', $destino['usuarios'])
            ->get();

          $nombres = $usuarios->map(function ($user) {
            if ($user->persona) {
              return $user->persona->PerNombres . ' ' . $user->persona->PerApellidos;
            }
            return 'Sin nombre';
          })->toArray();

          $destinoFormateado .= collect($nombres)->map(
            fn($nombre) =>
            "<span class='badge bg-secondary me-1'>$nombre</span>"
          )->implode(' ');
        }

        // Nombre del usuario que creó la notificación
        $usuario = User::with('persona')->find($item->usuarioCrea);
        $nombreUsuario = $usuario && $usuario->persona
          ? $usuario->persona->PerNombres . ' ' . $usuario->persona->PerApellidos
          : 'Sin nombre';

        return [
          'id' => $item->id,
          'titulo' => $item->titulo,
          'bodyPush' => $item->bodyPush,
          'bodyCompleto' => $item->bodyCompleto,
          'destino' => $destinoFormateado,
          'estado' => $item->estado,
          'privacidad' => $item->privacidad,
          'proceso' => $item->proceso,
          'usuarioCrea' => $nombreUsuario,
          'programada' => $item->programada ? Carbon::parse($item->programada)->format('Y-m-d H:i:s') : 'No',
          'createdAt' => Carbon::parse($item->createdAt)->format('Y-m-d H:i:s'),
        ];
      });

    return response()->json([
      'total' => $total,
      'rows' => $notificaciones,
    ]);
  }

  public function editarNotificacion($id)
  {
    $notificacion = Notificaciones::findOrFail($id);
    $editable = false;

    // Verificar si la notificación está programada y si la fecha programada es futura
    if ($notificacion->programada) {
      $fechaProgramada = Carbon::createFromFormat('Y-m-d H:i:s', $notificacion->programada);
      $editable = $fechaProgramada->greaterThan(Carbon::now());
    }

    //Decodificar el destino y obtener los usuarios y grupos seleccionados
    $destino = json_decode($notificacion->destino, true);
    $usuariosSeleccionados = $destino['usuarios'] ?? [];
    $gruposSeleccionados = $destino['grupos'] ?? [];

    return view("gestionWeb.appmovil.notificaciones.editarNotificacion", compact(
      'notificacion',
      'editable',
      'usuariosSeleccionados',
      'gruposSeleccionados'
    ));
  }

  public function updateNotificacion(Request $request, $id)
  {
    $data = $request->all();
    $notificacion = Notificaciones::findOrFail($id);

    $validator = Validator::make($data, [
      'titulo' => 'required|string|max:50',
      'bodyPush' => 'required|string|max:140',
      'bodyCompleto' => 'required|string',
      'programada' => 'nullable|date_format:Y-m-d\TH:i',
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

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      // Actualizar la notificación
      $notificacion->update([
        'titulo' => $data['titulo'],
        'bodyPush' => $data['bodyPush'],
        'bodyCompleto' => $data['bodyCompleto'],
        'programada' => $data['programada'] ?? null,
        'usuarioModifica' => Auth::user()->IdUsuario,
        'updatedAt' => now()->format('Y-m-d H:i:s')
      ]);

      return toastModal("Notificación actualizada correctamente", "success", route('notificaciones.index'));
    } catch (Exception $e) {
      Log::error('Error al actualizar la notificación: ' . $e->getMessage());
      return toastModal("Error al actualizar la notificación", "error", route('notificaciones.index'));
    }
  }

  public function cambiarEstadoNotificacion($id)
  {
    try {
      $notificacion = Notificaciones::findOrFail($id);
      $notificacion->estado = $notificacion->estado === 'activo' ? 'inactivo' : 'activo';
      $notificacion->save();
      return response()->json([
        'message' => 'Estado cambiado a ' . $notificacion->estado,
        'type' => 'success'
      ]);
    } catch (Exception $e) {
      Log::error('Error al actualizar el estado de la notificacion: ' . $e->getMessage());
      return response()->json([
        'message' => 'Error al actualizar el estado de la notificacion',
        'type' => 'danger'
      ]);
    }
  }
}
