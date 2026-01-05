<?php

namespace App\Http\Controllers;

use App\Models\GESTIONADMIN\SIG\Documentos;
use App\Models\GESTIONADMIN\SIG\DocumentosVersiones;
use App\Models\GESTIONADMIN\SIG\Procesos;
use App\Models\GESTIONADMIN\SIG\TiposDocumentos;
use App\Models\GESTIONADMIN\SIG\Ubicaciones;
use App\Constants\Permisos;
use App\Models\User;
use App\Notifications\EmisionEstadoNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MapaProcesosController extends Controller
{
  public function porCategoria(Request $request, string $categoria)
  {
    $categorias = [
      'gerenciales' => ['codigo' => 'G', 'titulo' => 'Procesos Gerenciales'],
      'misionales' => ['codigo' => 'M', 'titulo' => 'Procesos Misionales'],
      'apoyo' => ['codigo' => 'A', 'titulo' => 'Procesos de Apoyo'],
      'otros' => ['codigo' => 'O', 'titulo' => 'Otros Procesos']
    ];

    if (!array_key_exists($categoria, $categorias)) {
      abort(404);
    }

    $estadoFiltro = $request->get('estado', 'activos'); // activos|inactivos|todos
    if (!in_array($estadoFiltro, ['activos', 'inactivos', 'todos'], true)) {
      $estadoFiltro = 'activos';
    }

    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    $puedeVerInactivos = $user?->can(Permisos::SIG_MAPA_PROCESOS_ELIMINAR) ?? false;
    if (!$puedeVerInactivos && $estadoFiltro !== 'activos') {
      $estadoFiltro = 'activos';
    }
    $codigoCategoria = $categorias[$categoria]['codigo'];
    $procesosCategoria = Procesos::where('categoria', $codigoCategoria)
      ->get(['id', 'nombre']);
    $tiposCategoria = TiposDocumentos::orderBy('nombre')->get(['id', 'nombre']);
    $documentosQuery = Documentos::with([
      'proceso' => function ($query) {
        $query->select('id', 'categoria', 'nombre');
      },
      'tipoDocumento' => function ($query) {
        $query->select('id', 'nombre');
      },
      'ubicacion' => function ($query) {
        $query->select('id', 'nombre');
      },
      'versiones' => function ($query) {
        $query->select('id', 'documento_id', 'version', 'fecha_aprobacion', 'id_elabora', 'id_revisa', 'id_aprueba', 'estado')
          ->where('estado', 'APROBADO')
          ->orderByDesc('version')
          ->limit(1);
      }
    ])
      ->whereHas('proceso', function ($query) use ($codigoCategoria) {
        $query->where('categoria', $codigoCategoria);
      });

    if ($estadoFiltro === 'activos') {
      $documentosQuery->where('estado', 'ACTIVO');
    } elseif ($estadoFiltro === 'inactivos') {
      $documentosQuery->where('estado', 'INACTIVO');
    }

    $documentos = $documentosQuery->get(['id', 'codigo', 'nombre', 'id_proceso', 'id_ubicacion', 'id_tipo_doc', 'estado']);

    $documentoIds = $documentos->pluck('id')->all();
    $ultimosEstados = $documentoIds
      ? DocumentosVersiones::whereIn('documento_id', $documentoIds)
        ->orderByDesc('id')
        ->get(['documento_id', 'estado'])
        ->unique('documento_id')
        ->keyBy('documento_id')
      : collect();
    $enRevisionIds = $ultimosEstados->filter(fn ($v) => $v->estado === 'EN_REVISION')->keys()->all();

    $ubicacionesIds = $documentos->flatMap(function ($documento) {
      $version = $documento->versiones->first();
      if (!$version) {
        return [];
      }
      return [
        trim((string) $version->id_elabora),
        trim((string) $version->id_revisa),
        trim((string) $version->id_aprueba),
      ];
    })->filter()->unique()->all();
    $ubicaciones = $ubicacionesIds
      ? Ubicaciones::whereIn('id', $ubicacionesIds)->get(['id', 'nombre'])->keyBy('id')
      : collect();

    $filas = $documentos->map(function ($documento) use ($ubicaciones, $enRevisionIds) {
      $version = $documento->versiones->first();

      $obtenerNombre = function ($id) use ($ubicaciones) {
        $id = trim((string) $id);
        if ($id === '') {
          return null;
        }
        return optional($ubicaciones->get($id))->nombre;
      };

      return [
        'id' => $documento->id,
        'codigo' => $documento->codigo,
        'nombre' => $documento->nombre,
        'proceso_id' => $documento->proceso?->id,
        'proceso' => $documento->proceso?->nombre ?? $documento->proceso?->id,
        'tipo_id' => $documento->tipoDocumento?->id,
        'tipo_documento' => $documento->tipoDocumento?->nombre,
        'version' => $version?->version,
        'fecha_aprobacion' => $version?->fecha_aprobacion,
        'elaboro' => $obtenerNombre($version?->id_elabora),
        'reviso' => $obtenerNombre($version?->id_revisa),
        'aprueba' => $obtenerNombre($version?->id_aprueba),
        'ubicacion' => $documento->ubicacion?->nombre,
        'estado' => $documento->estado,
        'en_revision' => in_array($documento->id, $enRevisionIds, true),
      ];
    })->values();

    $procesosFiltro = $procesosCategoria->map(function ($proceso) {
      return [
        'id' => $proceso->id,
        'label' => $proceso->nombre ?? $proceso->id,
      ];
    })->filter(fn ($p) => !empty($p['label']))->unique('id')->sortBy('label')->values();

    $tiposFiltro = $tiposCategoria->map(function ($tipo) {
      return [
        'id' => $tipo->id,
        'label' => $tipo->nombre ?? $tipo->id,
      ];
    })->filter(fn ($t) => !empty($t['label']))->unique('id')->sortBy('label')->values();

    return view('sig.documentos_categoria', [
      'categoriaSlug' => $categoria,
      'categoriaTitulo' => $categorias[$categoria]['titulo'],
      'documentos' => $filas,
      'procesosFiltro' => $procesosFiltro,
      'tiposFiltro' => $tiposFiltro,
      'estadoFiltro' => $estadoFiltro,
    ]);
  }

  public function emisiones(int $documentoId)
  {
    $documento = Documentos::with('proceso')->findOrFail($documentoId);

    $versiones = DocumentosVersiones::where('documento_id', $documentoId)
      ->whereIn('estado', ['APROBADO', 'HISTORICO'])
      ->orderByDesc('version')
      ->orderByDesc('id')
      ->get([
        'id',
        'version',
        'comentario_revision',
        'id_elabora',
        'id_revisa',
        'id_aprueba',
        'fecha_aprobacion',
        'estado',
        'fecha_elaboracion'
      ]);

    $ubicacionesIds = $versiones->pluck('id_elabora')
      ->merge($versiones->pluck('id_revisa'))
      ->merge($versiones->pluck('id_aprueba'))
      ->filter()
      ->map(fn ($id) => trim((string) $id))
      ->filter()
      ->unique()
      ->all();

    $ubicaciones = $ubicacionesIds
      ? Ubicaciones::whereIn('id', $ubicacionesIds)->get(['id', 'nombre'])->keyBy('id')
      : collect();

    $versiones = $versiones->map(function ($version) use ($ubicaciones) {
      $version->elaboro_nombre = optional($ubicaciones->get(trim((string) $version->id_elabora)))->nombre;
      $version->reviso_nombre = optional($ubicaciones->get(trim((string) $version->id_revisa)))->nombre;
      $version->aprueba_nombre = optional($ubicaciones->get(trim((string) $version->id_aprueba)))->nombre;
      return $version;
    });

    return view('sig.documentos_emisiones', [
      'documento' => $documento,
      'versiones' => $versiones,
      'ubicaciones' => $ubicaciones,
    ]);
  }

  public function cambiarEstado(Request $request, int $id)
  {
    $estado = strtoupper($request->input('estado'));
    if (!in_array($estado, ['ACTIVO', 'INACTIVO'])) {
      return response()->json(['message' => 'Estado no valido'], 422);
    }

    $documento = Documentos::findOrFail($id);
    $documento->estado = $estado;
    $documento->save();

    return response()->json(['message' => 'Estado actualizado', 'estado' => $estado]);
  }

  public function crearEmision(int $documentoId)
  {
    $documento = Documentos::findOrFail($documentoId);
    $ubicacionesElabora = Ubicaciones::select('id', 'nombre')
      ->where('tipo', 'E')
      ->orderBy('nombre')
      ->get();
    $ubicacionesRevisa = Ubicaciones::select('id', 'nombre')
      ->where('tipo', 'R')
      ->orderBy('nombre')
      ->get();
    $ubicacionesAprueba = Ubicaciones::select('id', 'nombre')
      ->where('tipo', 'A')
      ->orderBy('nombre')
      ->get();

    $proximaVersion = DocumentosVersiones::where('documento_id', $documentoId)
      ->whereNotNull('version')
      ->max('version');
    $proximaVersion = $proximaVersion ? ($proximaVersion + 1) : 1;

    return view('sig.documentos_nueva_emision', [
      'documento' => $documento,
      'ubicacionesElabora' => $ubicacionesElabora,
      'ubicacionesRevisa' => $ubicacionesRevisa,
      'ubicacionesAprueba' => $ubicacionesAprueba,
      'proximaVersion' => $proximaVersion,
    ]);
  }

  public function guardarEmision(Request $request, int $documentoId)
  {
    $documento = Documentos::findOrFail($documentoId);
    $userId = Auth::id() ?? $request->user()?->getKey();

    $ultimaVersion = DocumentosVersiones::where('documento_id', $documentoId)
      ->orderByDesc('id')
      ->first();

    $tieneRevision = $ultimaVersion && $ultimaVersion->estado === 'EN_REVISION';

    if ($tieneRevision) {
      return response()->json([
        'title' => 'Accion no permitida',
        'message' => 'Ya existe una emision en revision para este documento.',
        'type' => 'warning',
        'redirect' => '#',
      ], 422);
    }

    $data = $request->validate([
      'archivo_url' => 'required|string|max:255',
      'paginas' => 'nullable|integer|min:1',
      'comentario_revision' => 'nullable|string|max:500',
      'id_elabora' => 'nullable|integer',
      'id_revisa' => 'nullable|integer',
      'id_aprueba' => 'nullable|integer',
      'fecha_elaboracion' => 'required|date',
    ]);

    $version = new DocumentosVersiones();
    $version->documento_id = $documentoId;
    $version->version = null;
    $version->archivo_url = $data['archivo_url'];
    $version->paginas = $data['paginas'];
    $version->estado = 'EN_REVISION';
    $version->comentario_revision = $data['comentario_revision'] ?? null;
    $version->id_elabora = $data['id_elabora'] ?? null;
    $version->id_revisa = $data['id_revisa'] ?? null;
    $version->id_aprueba = $data['id_aprueba'] ?? null;
    $version->fecha_elaboracion = $data['fecha_elaboracion'];
    $version->fecha_revision = null;
    $version->fecha_aprobacion = null;
    $version->fecha_modificacion = now();
    $version->usrcreacion = $userId;
    $version->save();

    $this->notificarCambioEstado($userId, 'EN_REVISION', $version);

    return response()->json([
      'title' => 'Emision registrada',
      'type' => 'success',
      'redirect' => url()->previous() ?: route('home'),
    ]);
  }

  public function emisionesPendientes()
  {
    $ultimosIds = DocumentosVersiones::orderByDesc('id')
      ->get(['id', 'documento_id', 'estado'])
      ->unique('documento_id')
      ->filter(fn ($v) => $v->estado === 'EN_REVISION')
      ->pluck('id');

    $versiones = DocumentosVersiones::with([
      'documento' => function ($query) {
        $query->select('id', 'codigo', 'nombre');
      }
    ])
      ->whereIn('id', $ultimosIds)
      ->orderByDesc('fecha_elaboracion')
      ->get(['id', 'documento_id', 'version', 'comentario_revision', 'id_elabora', 'id_revisa', 'id_aprueba', 'fecha_elaboracion', 'estado']);

    $ubicacionesIds = $versiones->pluck('id_elabora')
      ->merge($versiones->pluck('id_revisa'))
      ->merge($versiones->pluck('id_aprueba'))
      ->filter()
      ->map(fn ($id) => trim((string) $id))
      ->unique()
      ->all();

    $ubicaciones = $ubicacionesIds
      ? Ubicaciones::whereIn('id', $ubicacionesIds)->get(['id', 'nombre'])->keyBy('id')
      : collect();

    $filas = $versiones->map(function ($version) use ($ubicaciones) {
      $obtenerNombre = function ($id) use ($ubicaciones) {
        $id = trim((string) $id);
        if ($id === '') {
          return null;
        }
        return optional($ubicaciones->get($id))->nombre;
      };

      return [
        'id' => $version->id,
        'documento_id' => $version->documento_id,
        'codigo' => $version->documento?->codigo,
        'nombre' => $version->documento?->nombre,
        'version' => $version->version,
        'comentario_revision' => $version->comentario_revision,
        'elaboro' => $obtenerNombre($version->id_elabora),
        'reviso' => $obtenerNombre($version->id_revisa),
        'aprueba' => $obtenerNombre($version->id_aprueba),
        'fecha_elaboracion' => $version->fecha_elaboracion,
        'estado' => $version->estado,
      ];
    })->values();

    return view('sig.emisiones_pendientes', [
      'versiones' => $filas,
    ]);
  }

  public function aprobarEmision(Request $request, int $versionId)
  {
    $version = DocumentosVersiones::findOrFail($versionId);
    $creadorId = $version->usrcreacion;
    if ($version->estado !== 'EN_REVISION') {
      return response()->json(['message' => 'La emision no esta en revision'], 422);
    }

    $request->validate([
      'comentario' => 'required|string|max:500',
    ]);

    DB::transaction(function () use ($version, $request, $creadorId) {
      $proximaVersion = DocumentosVersiones::where('documento_id', $version->documento_id)
        ->whereNotNull('version')
        ->max('version');
      $proximaVersion = $proximaVersion ? ($proximaVersion + 1) : 1;

      DocumentosVersiones::where('documento_id', $version->documento_id)
        ->where('estado', 'APROBADO')
        ->update(['estado' => 'HISTORICO']);

      $nueva = new DocumentosVersiones();
      $nueva->documento_id = $version->documento_id;
      $nueva->version = $proximaVersion;
      $nueva->archivo_url = $version->archivo_url;
      $nueva->paginas = $version->paginas;
      $nueva->estado = 'APROBADO';
      $nueva->comentario_revision = $request->input('comentario');
      $nueva->id_elabora = $version->id_elabora;
      $nueva->id_revisa = $version->id_revisa;
      $nueva->id_aprueba = $version->id_aprueba;
      $nueva->fecha_elaboracion = $version->fecha_elaboracion;
      $nueva->fecha_revision = $version->fecha_revision;
      $nueva->fecha_aprobacion = now();
      $nueva->fecha_modificacion = now();
      $nueva->usrcreacion = $creadorId;
      $nueva->save();

      $this->notificarCambioEstado($creadorId, 'APROBADO', $nueva);
    });

    return response()->json([
      'title' => 'Emision aprobada',
      'type' => 'success',
      'message' => 'La emision fue aprobada y es la vigente.',
    ]);
  }

  public function rechazarEmision(Request $request, int $versionId)
  {
    $version = DocumentosVersiones::findOrFail($versionId);
    $creadorId = $version->usrcreacion;
    if ($version->estado !== 'EN_REVISION') {
      return response()->json(['message' => 'La emision no esta en revision'], 422);
    }

    $request->validate([
      'comentario' => 'required|string|max:500',
    ]);

    $nuevo = new DocumentosVersiones();
    $nuevo->documento_id = $version->documento_id;
    $nuevo->version = null;
    $nuevo->archivo_url = $version->archivo_url;
    $nuevo->paginas = $version->paginas;
    $nuevo->estado = 'RECHAZADO';
    $nuevo->comentario_revision = $request->input('comentario');
    $nuevo->id_elabora = $version->id_elabora;
    $nuevo->id_revisa = $version->id_revisa;
    $nuevo->id_aprueba = $version->id_aprueba;
    $nuevo->fecha_elaboracion = $version->fecha_elaboracion;
    $nuevo->fecha_revision = $version->fecha_revision;
    $nuevo->fecha_aprobacion = null;
    $nuevo->fecha_modificacion = now();
    $nuevo->usrcreacion = $creadorId;
    $nuevo->save();

    $this->notificarCambioEstado($creadorId, 'RECHAZADO', $nuevo);

    return response()->json([
      'title' => 'Emision rechazada',
      'type' => 'info',
      'message' => 'Se marco la emision como rechazada.',
    ]);
  }

  public function devolverEmision(Request $request, int $versionId)
  {
    $version = DocumentosVersiones::findOrFail($versionId);
    $creadorId = $version->usrcreacion;
    if ($version->estado !== 'EN_REVISION') {
      return response()->json(['message' => 'La emision no esta en revision'], 422);
    }

    $request->validate([
      'comentario' => 'required|string|max:500',
    ]);

    $nuevo = new DocumentosVersiones();
    $nuevo->documento_id = $version->documento_id;
    $nuevo->version = null;
    $nuevo->archivo_url = $version->archivo_url;
    $nuevo->paginas = $version->paginas;
    $nuevo->estado = 'DEVUELTO';
    $nuevo->comentario_revision = $request->input('comentario');
    $nuevo->id_elabora = $version->id_elabora;
    $nuevo->id_revisa = $version->id_revisa;
    $nuevo->id_aprueba = $version->id_aprueba;
    $nuevo->fecha_elaboracion = $version->fecha_elaboracion;
    $nuevo->fecha_revision = $version->fecha_revision;
    $nuevo->fecha_aprobacion = null;
    $nuevo->fecha_modificacion = now();
    $nuevo->usrcreacion = $creadorId;
    $nuevo->save();

    $this->notificarCambioEstado($creadorId, 'DEVUELTO', $nuevo);

    return response()->json([
      'title' => 'Emision devuelta',
      'type' => 'warning',
      'message' => 'Se devolvio la emision con observaciones.',
    ]);
  }

  public function emisionesDevueltasUsuario(Request $request)
  {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    if (!$user) {
      abort(403);
    }
    $userId = $user->id ?? $user->getKey() ?? $user->IdUsuario;

    $versiones = DocumentosVersiones::with([
      'documento' => fn ($q) => $q->select('id', 'codigo', 'nombre'),
    ])
      ->where('usrcreacion', $userId)
      ->orderByDesc('id')
      ->get(['id', 'documento_id', 'version', 'comentario_revision', 'archivo_url', 'paginas', 'fecha_elaboracion', 'estado'])
      ->unique('documento_id');

    $filas = $versiones->map(function ($version) {
      return [
        'id' => $version->id,
        'codigo' => $version->documento?->codigo,
        'nombre' => $version->documento?->nombre,
        'comentario_revision' => $version->comentario_revision,
        'archivo_url' => $version->archivo_url,
        'paginas' => $version->paginas,
        'fecha_elaboracion' => $version->fecha_elaboracion,
        'estado' => $version->estado,
        'version' => $version->version,
      ];
    })->values();

    return view('sig.emisiones_devueltas', [
      'versiones' => $filas,
    ]);
  }

  private function notificarCambioEstado(?int $usuarioId, string $estado, DocumentosVersiones $version): void
  {
    if (!$usuarioId) {
      return;
    }

    $creador = User::find($usuarioId);
    if (!$creador) {
      return;
    }

    $documento = Documentos::find($version->documento_id);

    try {
      $creador->notify(new EmisionEstadoNotification($estado, $version, $documento));
    } catch (\Throwable $e) {
      Log::warning('No se pudo enviar notificacion de emision', [
        'usuario_id' => $usuarioId,
        'version_id' => $version->id,
        'estado' => $estado,
        'error' => $e->getMessage(),
      ]);
    }
  }

}
