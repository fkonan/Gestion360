<?php

namespace App\Modules\SIG\Http\Controllers;

use App\Constants\Permisos;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SIG\Models\Documentos;
use App\Modules\SIG\Models\DocumentosVersiones;
use App\Modules\SIG\Services\DocumentosService;
use App\Modules\SIG\Services\EmisionesService;
use App\Modules\SIG\Services\NotificacionesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MapaProcesosController extends Controller
{
    public function __construct(
        private EmisionesService $emisionesService,
        private DocumentosService $documentosService,
        private NotificacionesService $notificacionesService
    ) {}

    public function porCategoria(Request $request, string $categoria)
    {
        $categorias = $this->documentosService->obtenerCategorias();

        if (! array_key_exists($categoria, $categorias)) {
            abort(404);
        }

        $estadoFiltro = $this->documentosService->normalizarEstadoFiltro($request->get('estado', 'activos'));

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $puedeVerInactivos = $user?->can(Permisos::SIG_MAPA_PROCESOS_ELIMINAR) ?? false;
        $puedeVerTodo = $user?->can(Permisos::SIG_MAPA_PROCESOS_VER_TODOS) ?? false;
        $centroCostoCodigo = $puedeVerTodo ? null : ($user?->obtenerCodigoCentroCosto() ?? null);
        $codigoCategoria = $categorias[$categoria]['codigo'];
        $datosCategoria = $this->documentosService->obtenerDatosCategoria(
            $codigoCategoria,
            $estadoFiltro,
            $puedeVerInactivos,
            $puedeVerTodo,
            $centroCostoCodigo
        );
        $procesosFiltro = $this->documentosService->obtenerProcesosFiltro($codigoCategoria);
        $tiposFiltro = $this->documentosService->obtenerTiposFiltro();

        return view('sig::documentos_categoria', [
            'categoriaSlug' => $categoria,
            'categoriaTitulo' => $categorias[$categoria]['titulo'],
            'documentos' => $datosCategoria['documentos'],
            'procesosFiltro' => $procesosFiltro,
            'tiposFiltro' => $tiposFiltro,
            'estadoFiltro' => $datosCategoria['estadoFiltro'],
        ]);
    }

    public function emisiones(int $documentoId)
    {
        $documento = Documentos::with('proceso')->findOrFail($documentoId);

        $versiones = $this->emisionesService->obtenerEmisionesDocumento($documentoId);
        $ubicaciones = $this->emisionesService->obtenerUbicacionesEmisiones($versiones);
        $versiones = $this->emisionesService->mapearEmisionesConNombres($versiones, $ubicaciones);

        return view('sig::documentos_emisiones', [
            'documento' => $documento,
            'versiones' => $versiones,
            'ubicaciones' => $ubicaciones,
        ]);
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $estado = strtoupper($request->input('estado'));
        if (! in_array($estado, ['ACTIVO', 'INACTIVO'])) {
            return response()->json(['message' => 'Estado no valido'], 422);
        }

        $documento = Documentos::findOrFail($id);
        $documento->estado = $estado;
        $documento->save();

        return response()->json(['message' => 'Estado actualizado', 'estado' => $estado]);
    }

    public function crearEmision(Request $request, int $documentoId)
    {
        $documento = Documentos::with(['proceso', 'tipoDocumento', 'ubicacion'])->findOrFail($documentoId);
        $datosEmision = $this->emisionesService->obtenerDatosFormularioNuevaEmision($documentoId);
        $emisionDevuelta = null;
        $versionId = $request->query('version_id');
        if ($versionId) {
            $emisionDevuelta = DocumentosVersiones::where('id', $versionId)
                ->where('documento_id', $documentoId)
                ->where('estado', 'DEVUELTO')
                ->first();

            $userId = Auth::id() ?? $request->user()?->getKey();
            if (! $emisionDevuelta || ($userId && $emisionDevuelta->usrcreacion && $emisionDevuelta->usrcreacion !== $userId)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No es posible reenviar esta solicitud.',
                ], 403);
            }
        }

        return view('sig::documentos_nueva_emision', [
            'documento' => $documento,
            'emisionDevuelta' => $emisionDevuelta,
            ...$datosEmision,
        ]);
    }

    public function crearDocumentoNuevo()
    {
        $procesos = $this->documentosService->obtenerProcesosFormulario();
        $tiposDocumentos = $this->documentosService->obtenerTiposDocumentosFormulario();
        $ubicacionesDocumento = $this->documentosService->obtenerUbicacionesDocumento();
        $ubicaciones = $this->emisionesService->obtenerUbicacionesFormulario();
        $centrosCostos = User::obtenerCentrosCostosActivos();

        return view('sig::documentos_nuevo', [
            'procesos' => $procesos,
            'tiposDocumentos' => $tiposDocumentos,
            'ubicacionesDocumento' => $ubicacionesDocumento,
            'centrosCostos' => $centrosCostos,
            ...$ubicaciones,
        ]);
    }

    public function editarDocumento(int $documentoId)
    {
        $documento = Documentos::with(['proceso', 'tipoDocumento', 'ubicacion'])->findOrFail($documentoId);
        if ($documento->estado !== 'ACTIVO') {
            return response()->json([
                'error' => true,
                'message' => 'Solo se permiten editar documentos activos.',
            ], 422);
        }

        $datosEmision = $this->emisionesService->obtenerDatosFormularioNuevaEmision($documentoId);
        $centrosCostos = User::obtenerCentrosCostosActivos();
        $centrosSeleccionados = $this->documentosService
            ->obtenerCentrosCostosDocumento($documentoId)
            ->pluck('centro_costo_codigo')
            ->all();

        return view('sig::documentos_editar', [
            'documento' => $documento,
            'centrosCostos' => $centrosCostos,
            'centrosSeleccionados' => $centrosSeleccionados,
            ...$datosEmision,
        ]);
    }

    public function actualizarDocumento(Request $request, int $documentoId)
    {
        $documento = Documentos::findOrFail($documentoId);
        if ($documento->estado !== 'ACTIVO') {
            return response()->json([
                'title' => 'Accion no permitida',
                'message' => 'Solo se permiten editar documentos activos.',
                'type' => 'warning',
                'redirect' => '#',
            ], 422);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'centros_costos' => 'nullable|array',
            'centros_costos.*' => 'string|max:20',
        ]);

        $documento->nombre = $data['nombre'];
        $documento->descripcion = $data['descripcion'];
        $documento->save();

        $centrosSeleccionados = $data['centros_costos'] ?? [];
        $centrosDisponibles = User::obtenerCentrosCostosActivos()->keyBy('codigo');
        $centrosParaGuardar = collect($centrosSeleccionados)
            ->filter()
            ->unique()
            ->map(function ($codigo) use ($centrosDisponibles) {
                $nombre = $centrosDisponibles->get($codigo)?->descripcion;
                if (! $nombre) {
                    return null;
                }

                return [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $this->documentosService->guardarCentrosCostosDocumento($documentoId, $centrosParaGuardar);

        return response()->json([
            'title' => 'Documento actualizado',
            'type' => 'success',
            'redirect' => url()->previous() ?: route('home'),
        ]);
    }

    public function guardarDocumentoNuevo(Request $request)
    {
        $userId = Auth::id() ?? $request->user()?->getKey();

        $data = $request->validate([
            'id_proceso' => 'required|integer',
            'id_tipo_doc' => 'required|integer',
            'id_ubicacion' => 'required|integer',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'archivo' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'paginas' => 'nullable|integer|min:1',
            'comentario_revision' => 'nullable|string|max:4000',
            'id_elabora' => 'nullable|integer',
            'id_revisa' => 'nullable|integer',
            'id_aprueba' => 'nullable|integer',
            'centros_costos' => 'nullable|array',
            'centros_costos.*' => 'string|max:20',
        ]);

        $centrosSeleccionados = $data['centros_costos'] ?? [];
        $centrosDisponibles = User::obtenerCentrosCostosActivos()->keyBy('codigo');
        $centrosParaGuardar = collect($centrosSeleccionados)
            ->filter()
            ->unique()
            ->map(function ($codigo) use ($centrosDisponibles) {
                $nombre = $centrosDisponibles->get($codigo)?->descripcion;
                if (! $nombre) {
                    return null;
                }

                return [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                ];
            })
            ->filter()
            ->values()
            ->all();
        $data['centros_costos'] = $centrosParaGuardar;

        $version = $this->documentosService->crearDocumentoNuevo($data, $userId);
        $this->notificacionesService->notificarCambioEstado($userId, 'EN_REVISION', $version);

        return response()->json([
            'title' => 'Documento registrado',
            'type' => 'success',
            'redirect' => url()->previous() ?: route('home'),
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
            'archivo' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'paginas' => 'nullable|integer|min:1',
            'comentario_revision' => 'nullable|string|max:4000',
            'id_elabora' => 'nullable|integer',
            'id_revisa' => 'nullable|integer',
            'id_aprueba' => 'nullable|integer',
        ]);

        $version = new DocumentosVersiones;
        $version->documento_id = $documentoId;
        $version->version = null;
        $version->archivo_url = $data['archivo']->getClientOriginalName();
        $version->paginas = $data['paginas'];
        $version->estado = 'EN_REVISION';
        $version->comentario_revision = $data['comentario_revision'] ?? null;
        $version->id_elabora = $data['id_elabora'] ?? null;
        $version->id_revisa = $data['id_revisa'] ?? null;
        $version->id_aprueba = $data['id_aprueba'] ?? null;
        $version->fecha_elaboracion = now();
        $version->fecha_revision = null;
        $version->fecha_aprobacion = null;
        $version->fecha_modificacion = now();
        $version->usrcreacion = $userId;
        $version->save();

        $this->notificacionesService->notificarCambioEstado($userId, 'EN_REVISION', $version);

        return response()->json([
            'title' => 'Emision registrada',
            'type' => 'success',
            'redirect' => url()->previous() ?: route('home'),
        ]);
    }

    public function emisionesPendientes()
    {
        $versiones = $this->emisionesService->obtenerEmisionesPendientes();
        $ubicaciones = $this->emisionesService->obtenerUbicacionesEmisiones($versiones);
        $filas = $this->emisionesService->mapearPendientes($versiones, $ubicaciones);

        return view('sig::solicitudes_pendientes', [
            'versiones' => $filas,
        ]);
    }

    public function aprobarEmision(Request $request, int $versionId)
    {
        $version = DocumentosVersiones::findOrFail($versionId);
        $creadorId = $version->usrcreacion;
        $adminId = Auth::id() ?? $request->user()?->getKey();
        if ($version->estado !== 'EN_REVISION') {
            return response()->json(['message' => 'La emision no esta en revision'], 422);
        }

        $request->validate([
            'comentario' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($version, $request, $creadorId, $adminId) {
                $proximaVersion = DocumentosVersiones::where('documento_id', $version->documento_id)
                    ->whereNotNull('version')
                    ->max('version');
                $proximaVersion = $proximaVersion ? ($proximaVersion + 1) : 1;

                $documento = Documentos::findOrFail($version->documento_id);
                $codigoAsignado = $this->documentosService->asignarCodigoDocumento($documento, $adminId);
                if (! $codigoAsignado) {
                    throw new \RuntimeException('No se pudo generar el codigo del documento.');
                }

                DocumentosVersiones::where('documento_id', $version->documento_id)
                    ->where('estado', 'APROBADO')
                    ->update(['estado' => 'HISTORICO']);

                $nueva = new DocumentosVersiones;
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
                $nueva->fecha_revision = now();
                $nueva->fecha_aprobacion = now();
                $nueva->fecha_modificacion = now();
                $nueva->usrcreacion = $creadorId;
                $nueva->save();

                $this->notificacionesService->notificarCambioEstado($creadorId, 'APROBADO', $nueva);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'title' => 'No se pudo aprobar',
                'message' => $e->getMessage(),
                'type' => 'error',
            ], 422);
        }

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

        $nuevo = new DocumentosVersiones;
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
        $nuevo->fecha_revision = now();
        $nuevo->fecha_aprobacion = null;
        $nuevo->fecha_modificacion = now();
        $nuevo->usrcreacion = $creadorId;
        $nuevo->save();

        $documento = Documentos::find($version->documento_id);
        if ($documento && empty($documento->codigo)) {
            DB::connection($documento->getConnectionName())
                ->table('sig_documentos_centros_costos')
                ->where('documento_id', $documento->id)
                ->delete();
        }

        $this->notificacionesService->notificarCambioEstado($creadorId, 'RECHAZADO', $nuevo);

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

        $nuevo = new DocumentosVersiones;
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
        $nuevo->fecha_revision = now();
        $nuevo->fecha_aprobacion = null;
        $nuevo->fecha_modificacion = now();
        $nuevo->usrcreacion = $creadorId;
        $nuevo->save();

        $this->notificacionesService->notificarCambioEstado($creadorId, 'DEVUELTO', $nuevo);

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
        if (! $user) {
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
                'documento_id' => $version->documento_id,
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

        return view('sig::solicitudes_usuario', [
            'versiones' => $filas,
        ]);
    }
}
