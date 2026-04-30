<?php

namespace App\Modules\Administration\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmpleadoPermisoService
{
    private const MAX_OBSERVACION_NOVEDAD = 3900;
    private const MAX_OBSERVACION_HISTORIAL = 1900;

    public const ESTADO_RADICADO = 'RADICADO';
    public const ESTADO_JEFE_APROBADO = 'JEFE_APROBADO';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';
    public const ESTADO_ANULADO = 'ANULADO';

    private const ROL_RRHH = 'GESTOR RECURSO HUMANO';

    private const MOTIVOS = [
        'REUNION_ESCOLAR' => 'REUNION ESCOLAR',
        'CITA_MEDICA_FAMILIARES' => 'CITA MEDICA FAMILIARES',
        'ESTUDIO' => 'ESTUDIO',
        'ACTIVIDAD_LABORAL_EXTERNA' => 'ACTIVIDAD LABORAL EXTERNA',
        'MEDICINA_GENERAL' => 'MEDICINA GENERAL',
        'MEDICINA_ESPECIALIZADA' => 'MEDICINA ESPECIALIZADA',
        'TERAPIAS' => 'TERAPIAS',
        'ODONTOLOGIA' => 'ODONTOLOGIA',
        'URGENCIA_O_CITA_PRIORITARIA' => 'URGENCIAS O CITA PRIORITARIA',
        'ACCIDENTE_DE_TRABAJO' => 'ACCIDENTE DE TRABAJO',
        'EXAMENES' => 'EXAMENES',
        'OTROS' => 'OTROS',
    ];

    private const META_MOTIVO_CODIGO = 'MOTIVO_CODIGO';
    private const META_OTRO_MOTIVO = 'OTRO_MOTIVO';
    private const META_ORIGEN = 'ORIGEN';
    private const META_CREADO_POR_NOMBRE = 'CREADO_POR_NOMBRE';
    private const META_IP_EQUIPO = 'IP_EQUIPO';
    private const META_SISTEMA_ORIGEN = 'SISTEMA_ORIGEN';
    private const META_ANULACION_MOTIVO = 'ANULACION_MOTIVO';

    public static function reglasCreacion(): array
    {
        return [
            'identificacion' => 'required|string|max:50',
            'fecha_permiso' => 'required|date_format:Y-m-d',
            'hora_salida' => 'required|date_format:H:i',
            'hora_ingreso' => 'nullable|date_format:H:i',
            'motivo' => 'required|string|in:'.implode(',', array_keys(self::MOTIVOS)),
            'otro_motivo' => 'nullable|string|max:255|required_if:motivo,OTROS',
            'actividad' => 'required|string|max:1000',
        ];
    }

    public static function mensajesCreacion(): array
    {
        return [
            'identificacion.required' => 'La identificacion del empleado es obligatoria.',
            'fecha_permiso.required' => 'La fecha del permiso es obligatoria.',
            'fecha_permiso.date_format' => 'La fecha del permiso debe tener formato YYYY-MM-DD.',
            'hora_salida.required' => 'La hora de salida es obligatoria.',
            'hora_salida.date_format' => 'La hora de salida debe tener formato HH:MM.',
            'hora_ingreso.date_format' => 'La hora de ingreso debe tener formato HH:MM.',
            'motivo.required' => 'Debe seleccionar un motivo.',
            'motivo.in' => 'El motivo seleccionado no es valido.',
            'otro_motivo.required_if' => 'Debe indicar el detalle cuando selecciona OTROS.',
            'actividad.required' => 'Debe especificar la actividad a realizar.',
            'actividad.max' => 'La actividad no puede exceder 1000 caracteres.',
        ];
    }

    public static function reglasRechazo(): array
    {
        return [
            'nivel' => 'required|string|in:jefe,rrhh',
            'motivo_rechazo' => 'required|string|max:500',
        ];
    }

    public static function reglasAnulacion(): array
    {
        return [
            'motivo_anulacion' => 'required|string|max:500',
        ];
    }

    public static function mensajesRechazo(): array
    {
        return [
            'nivel.required' => 'El nivel del rechazo es obligatorio.',
            'nivel.in' => 'El nivel de rechazo no es valido.',
            'motivo_rechazo.required' => 'Debe indicar el motivo del rechazo.',
            'motivo_rechazo.max' => 'El motivo de rechazo no puede exceder 500 caracteres.',
        ];
    }

    public static function mensajesAnulacion(): array
    {
        return [
            'motivo_anulacion.required' => 'Debe indicar la razon de la anulacion.',
            'motivo_anulacion.max' => 'La razon de anulacion no puede exceder 500 caracteres.',
        ];
    }

    public static function opcionesMotivo(): array
    {
        return self::MOTIVOS;
    }

    public static function opcionesEstadoFlujo(): array
    {
        return [
            self::ESTADO_RADICADO => 'Radicado',
            self::ESTADO_JEFE_APROBADO => 'Aprobado por jefe',
            self::ESTADO_APROBADO => 'Aprobado por RRHH',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_ANULADO => 'Anulado',
        ];
    }

    public function crearPermiso(
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        string $origen = 'WEB',
        ?string $ipEquipo = null,
        ?string $sistemaOrigen = null
    ): array {
        $persona = $this->buscarPersona($payload['identificacion']);
        if (! $persona) {
            return [
                'ok' => false,
                'message' => 'No se encontro el empleado activo en PER_PERSONAS.',
                'http_status' => 422,
            ];
        }

        $fechaBase = Carbon::createFromFormat('Y-m-d', (string) $payload['fecha_permiso']);
        $fechaInicio = $fechaBase->copy()->setTimeFromTimeString((string) $payload['hora_salida']);
        $horaIngreso = $this->normalizarTexto($payload['hora_ingreso'] ?? null);
        $fechaFin = null;
        if ($horaIngreso !== null) {
            $fechaFin = $fechaBase->copy()->setTimeFromTimeString($horaIngreso);
            if ($fechaFin->lt($fechaInicio)) {
                $fechaFin->addDay();
            }
        }

        $tipoPermisoId = $this->obtenerTipoPermisoId();
        if (! $tipoPermisoId) {
            return [
                'ok' => false,
                'message' => 'No se encontro el tipo de novedad Permiso en EMP_NOVEDADES_TIPO.',
                'http_status' => 500,
            ];
        }

        $idNovedad = Str::uuid()->toString();
        $observacion = mb_substr($this->normalizarTexto($payload['actividad'] ?? '') ?? '', 0, self::MAX_OBSERVACION_NOVEDAD, 'UTF-8');

        try {
            DB::connection('oracle-360')
                ->table('EMP_NOVEDADES')
                ->insert([
                    'id' => $idNovedad,
                    'id_persona' => (string) $persona['identificacion'],
                    'id_tipo_novedad' => $tipoPermisoId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'estado' => self::ESTADO_RADICADO,
                    'fecha_creacion' => now(),
                    'observacion' => $observacion,
                ]);

            $this->registrarMetadatosRadicacion(
                idNovedad: $idNovedad,
                payload: $payload,
                documentoActor: $documentoActor,
                nombreActor: $nombreActor,
                origen: $origen,
                ipEquipo: $ipEquipo,
                sistemaOrigen: $sistemaOrigen
            );

            return [
                'ok' => true,
                'message' => 'Permiso radicado correctamente.',
                'id_novedad' => $idNovedad,
                'estado' => self::ESTADO_RADICADO,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'No fue posible registrar el permiso.',
                'error' => $e->getMessage(),
                'http_status' => 500,
            ];
        }
    }

    public function obtenerPermisosPaginados(array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->nuevaConsultaPermisos360()
            ->select([
                'n.id as emp_novedad_id',
                'n.id_persona',
                'n.estado as estado_flujo',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.observacion',
                'n.fecha_creacion',
            ]);

        $persona = trim((string) ($filtros['persona'] ?? ''));
        if ($persona !== '') {
            $query->where('n.id_persona', 'like', '%'.$persona.'%');
        }

        $estadoFlujo = trim((string) ($filtros['estado_flujo'] ?? ''));
        if ($estadoFlujo !== '') {
            $query->where('n.estado', $estadoFlujo);
        }

        $creadoPorDocumento = trim((string) ($filtros['creado_por_documento'] ?? ''));
        if ($creadoPorDocumento !== '') {
            $query->whereExists(function ($subquery) use ($creadoPorDocumento) {
                $subquery->select(DB::raw(1))
                    ->from('EMP_NOVEDADES_HISTORIAL as h')
                    ->whereColumn('h.id_novedad', 'n.id')
                    ->where('h.estado_nuevo', self::ESTADO_RADICADO)
                    ->where('h.usuario_accion', $creadoPorDocumento);
            });
        }

        $permisos = $query
            ->orderByDesc('n.fecha_creacion')
            ->paginate($perPage)
            ->withQueryString();

        $collection = $permisos->getCollection();
        if ($collection->isEmpty()) {
            return $permisos;
        }

        $docs = $collection->pluck('id_persona')->filter()->unique()->values()->all();
        $personas = [];
        foreach ($docs as $doc) {
            $personaInfo = $this->buscarPersona((string) $doc);
            if ($personaInfo) {
                $personas[(string) $doc] = $personaInfo;
            }
        }

        $firmasPorNovedad = $this->obtenerFirmasPorNovedades(
            $collection->pluck('emp_novedad_id')->filter()->unique()->values()->all()
        );
        $motivosPorNovedad = $this->obtenerMotivosPorNovedades(
            $collection->pluck('emp_novedad_id')->filter()->unique()->values()->all()
        );

        $collection->transform(function ($permiso) use ($personas, $firmasPorNovedad, $motivosPorNovedad) {
            $persona = $personas[$permiso->id_persona] ?? null;
            $firma = $firmasPorNovedad[(string) ($permiso->emp_novedad_id ?? '')] ?? [];
            $motivoData = $motivosPorNovedad[(string) ($permiso->emp_novedad_id ?? '')] ?? [];

            $permiso->persona_nombre = $persona['nombre'] ?? 'Sin nombre';
            $permiso->persona_codigo = $persona['codigo'] ?? null;
            $permiso->persona_seccion = $persona['seccion'] ?? null;
            $permiso->persona_sexo = $persona['sexo'] ?? null;
            $permiso->persona_edad = $persona['edad'] ?? null;
            $permiso->novedad_estado = (string) ($permiso->estado_flujo ?? '');
            $permiso->fecha_inicio = isset($permiso->fecha_inicio) ? Carbon::parse($permiso->fecha_inicio) : null;
            $permiso->fecha_fin = isset($permiso->fecha_fin) ? Carbon::parse($permiso->fecha_fin) : null;
            $motivoCodigo = strtoupper(trim((string) ($motivoData['motivo_catalogo'] ?? '')));
            $permiso->motivo_catalogo = $motivoCodigo !== '' ? $motivoCodigo : null;
            $permiso->motivo_label = $motivoCodigo !== '' && isset(self::MOTIVOS[$motivoCodigo])
                ? self::MOTIVOS[$motivoCodigo]
                : 'N/A';
            $permiso->otro_motivo = $motivoData['otro_motivo'] ?? null;
            $permiso->actividad = $this->limpiarObservacionActividad($permiso->observacion ?? null);
            $permiso->jefe_aprobado_por_documento = $firma['jefe_aprobado_por_documento'] ?? null;
            $permiso->jefe_aprobado_por_nombre = $firma['jefe_aprobado_por_nombre'] ?? null;
            $permiso->rrhh_aprobado_por_documento = $firma['rrhh_aprobado_por_documento'] ?? null;
            $permiso->rrhh_aprobado_por_nombre = $firma['rrhh_aprobado_por_nombre'] ?? null;

            return $permiso;
        });

        $permisos->setCollection($collection);

        return $permisos;
    }

    public function aprobarPorJefe(
        string $idNovedad,
        string $documentoActor,
        bool $esSuperAdmin = false
    ): array {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        if ((string) $novedad->estado !== self::ESTADO_RADICADO) {
            return ['ok' => false, 'message' => 'El permiso no esta pendiente de aprobacion de jefe.'];
        }

        if (! $esSuperAdmin && ! $this->esJefeDirectoDeEmpleado($documentoActor, (string) $novedad->id_persona)) {
            return ['ok' => false, 'message' => 'No tienes autorizacion de jefe para aprobar este permiso.'];
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_JEFE_APROBADO,
                documentoActor: $documentoActor
            );

            return ['ok' => true, 'message' => 'Permiso aprobado por jefe correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible aprobar el permiso por jefe.');
        }
    }

    public function aprobarPorRrhh(
        string $idNovedad,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        if ((string) $novedad->estado !== self::ESTADO_JEFE_APROBADO) {
            return ['ok' => false, 'message' => 'El permiso no esta pendiente de aprobacion de RRHH.'];
        }

        if (! $esSuperAdmin && ! $esRrhh) {
            return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para aprobar este permiso.'];
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_APROBADO,
                documentoActor: $documentoActor
            );

            return ['ok' => true, 'message' => 'Permiso aprobado por RRHH correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible aprobar el permiso por RRHH.');
        }
    }

    public function rechazarPermiso(
        string $idNovedad,
        string $nivel,
        string $motivoRechazo,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        $nivel = strtolower(trim($nivel));
        if ($nivel === 'jefe') {
            if (! $esSuperAdmin && ! $this->esJefeDirectoDeEmpleado($documentoActor, (string) $novedad->id_persona)) {
                return ['ok' => false, 'message' => 'No tienes autorizacion de jefe para rechazar este permiso.'];
            }
        }

        if ($nivel === 'rrhh') {
            if (! $esSuperAdmin && ! $esRrhh) {
                return ['ok' => false, 'message' => 'No tienes autorizacion de RRHH para rechazar este permiso.'];
            }
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_RECHAZADO,
                documentoActor: $documentoActor
            );

            return ['ok' => true, 'message' => 'Permiso rechazado correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible rechazar el permiso.');
        }
    }

    public function anularPermiso(
        string $idNovedad,
        string $motivoAnulacion,
        string $documentoActor,
        bool $esRrhh,
        bool $esSuperAdmin = false
    ): array {
        $motivoAnulacion = (string) ($this->normalizarTexto($motivoAnulacion) ?? '');
        if ($motivoAnulacion === '') {
            return ['ok' => false, 'message' => 'Debe indicar la razon de la anulacion.'];
        }

        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            return ['ok' => false, 'message' => 'No se encontro el permiso.'];
        }

        $esTitular = trim($documentoActor) !== '' && trim($documentoActor) === trim((string) $novedad->id_persona);
        $esCreador = $this->fueCreadoPorUsuario((string) $novedad->id, $documentoActor);
        if (! $esSuperAdmin && ! $esRrhh && ! $esTitular && ! $esCreador) {
            return ['ok' => false, 'message' => 'No tienes autorizacion para anular este permiso.'];
        }

        if ((string) $novedad->estado === self::ESTADO_ANULADO) {
            return ['ok' => false, 'message' => 'El permiso ya esta anulado.'];
        }

        try {
            $this->actualizarEstadoNovedad(
                idNovedad: $idNovedad,
                nuevoEstado: self::ESTADO_ANULADO,
                documentoActor: $documentoActor,
                observacion: $this->construirObservacionAnulacion($novedad->observacion ?? null, $motivoAnulacion)
            );

            return ['ok' => true, 'message' => 'Permiso anulado correctamente.'];
        } catch (\Throwable $e) {
            return $this->construirErrorCambioEstado($e, 'No fue posible anular el permiso.');
        }
    }

    public function esUsuarioRrhh(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(self::ROL_RRHH) || $user->hasRole(User::SUPER_ADMIN_ROLE);
    }

    public function esUsuarioSuperAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(User::SUPER_ADMIN_ROLE);
    }

    public function puedeAprobarJefe(?User $user, string $documentoEmpleado, string $estadoFlujo): bool
    {
        if (! $user || $estadoFlujo !== self::ESTADO_RADICADO) {
            return false;
        }

        if ($this->esUsuarioSuperAdmin($user)) {
            return true;
        }

        $documentoActor = $this->obtenerDocumentoUsuario($user);
        if ($documentoActor === '') {
            return false;
        }

        return $this->esJefeDirectoDeEmpleado($documentoActor, $documentoEmpleado);
    }

    public function puedeAprobarRrhh(?User $user, string $estadoFlujo): bool
    {
        if (! $user || $estadoFlujo !== self::ESTADO_JEFE_APROBADO) {
            return false;
        }

        return $this->esUsuarioRrhh($user);
    }

    public function puedeRechazarJefe(?User $user, string $documentoEmpleado, string $estadoFlujo): bool
    {
        if (! $user) {
            return false;
        }

        if (in_array($estadoFlujo, [self::ESTADO_ANULADO], true)) {
            return false;
        }

        if ($this->esUsuarioSuperAdmin($user)) {
            return true;
        }

        $documentoActor = $this->obtenerDocumentoUsuario($user);
        if ($documentoActor === '') {
            return false;
        }

        return $this->esJefeDirectoDeEmpleado($documentoActor, $documentoEmpleado);
    }

    public function puedeRechazarRrhh(?User $user, string $estadoFlujo): bool
    {
        if (! $user || in_array($estadoFlujo, [self::ESTADO_ANULADO], true)) {
            return false;
        }

        return $this->esUsuarioRrhh($user);
    }

    public function puedeAnular(?User $user, string $documentoEmpleado, string $estadoFlujo): bool
    {
        if (! $user || $estadoFlujo === self::ESTADO_ANULADO) {
            return false;
        }

        if ($this->esUsuarioRrhh($user)) {
            return true;
        }

        $documentoActor = $this->obtenerDocumentoUsuario($user);

        return $documentoActor !== '' && $documentoActor === trim($documentoEmpleado);
    }

    public function obtenerDocumentoUsuario(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $documento = $user->persona?->PerNumDoc ?? $user->IdUsuario ?? '';

        return trim((string) $documento);
    }

    public function obtenerNombreUsuario(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $nombre = $user->persona?->nombreCompleto()
            ?? trim((string) ($user->Usuario ?? ''));

        return trim((string) $nombre);
    }

    public function obtenerDetalleParaPdf(string $idNovedad): array
    {
        $novedad = $this->obtenerNovedadPermiso($idNovedad);
        if (! $novedad) {
            throw new RuntimeException('No se encontro el registro en EMP_NOVEDADES.');
        }

        $persona = $this->buscarPersona((string) $novedad->id_persona);
        if (! $persona) {
            throw new RuntimeException('No se encontro la persona en PER_PERSONAS.');
        }

        $firma = $this->obtenerFirmasPorNovedades([(string) $novedad->id])[(string) $novedad->id] ?? [];
        $motivoData = $this->obtenerMotivosPorNovedades([(string) $novedad->id])[(string) $novedad->id] ?? [];
        $motivoCodigo = strtoupper(trim((string) ($motivoData['motivo_catalogo'] ?? '')));

        $permiso = (object) [
            'emp_novedad_id' => (string) $novedad->id,
            'id_persona' => (string) $novedad->id_persona,
            'estado_flujo' => (string) $novedad->estado,
            'motivo_catalogo' => $motivoCodigo !== '' ? $motivoCodigo : null,
            'otro_motivo' => $motivoData['otro_motivo'] ?? null,
            'actividad' => $this->limpiarObservacionActividad($novedad->observacion ?? null),
            'jefe_aprobado_por_documento' => $firma['jefe_aprobado_por_documento'] ?? null,
            'jefe_aprobado_por_nombre' => $firma['jefe_aprobado_por_nombre'] ?? null,
            'rrhh_aprobado_por_documento' => $firma['rrhh_aprobado_por_documento'] ?? null,
            'rrhh_aprobado_por_nombre' => $firma['rrhh_aprobado_por_nombre'] ?? null,
            'firma_empleado_ip' => $this->normalizarTexto($motivoData['ip_equipo'] ?? null),
            'firma_empleado_sistema' => $this->normalizarTexto($motivoData['sistema_origen'] ?? null),
        ];

        return [
            'permiso' => $permiso,
            'novedad' => $novedad,
            'persona' => $persona,
            'motivo_label' => $motivoCodigo !== '' && isset(self::MOTIVOS[$motivoCodigo])
                ? self::MOTIVOS[$motivoCodigo]
                : 'N/A',
        ];
    }

    public function buscarPersona(string $identificacion): ?array
    {
        $identificacion = trim($identificacion);
        if ($identificacion === '') {
            return null;
        }

        return Cache::remember('per_persona_permiso_'.$identificacion, 300, function () use ($identificacion) {
            $row = DB::connection('oracle')
                ->table('per_personas as p')
                ->leftJoin('per_empresapersonas as ep', function ($join) {
                    $join->on('ep.pe_id_pe', '=', 'p.id')
                        ->whereIn('ep.tp_id', [1, 11])
                        ->where('ep.activo', 1)
                        ->where('ep.estborrado', 0)
                        ->whereNull('ep.fecfin');
                })
                ->leftJoin('per_cargoccostos as cc', function ($join) {
                    $join->on('cc.id', '=', 'ep.cc_id')
                        ->where('cc.activo', 1)
                        ->where('cc.estborrado', 0);
                })
                ->leftJoin('per_centrocostos as ct', function ($join) {
                    $join->on('ct.codigo', '=', 'cc.ct_codigo')
                        ->where('ct.estado', 1)
                        ->where('ct.estborrado', 0);
                })
                ->where('p.identificacion', $identificacion)
                ->where('p.estado', 'ACTIVO')
                ->where('p.estborrado', 0)
                ->orderByDesc('ep.id')
                ->selectRaw(
                    "p.identificacion as identificacion,
                    TRIM(p.pnombre || ' ' || NVL(p.snombre, '')) || ' ' ||
                    TRIM(p.papellido || ' ' || NVL(p.sapellido, '')) as nombre,
                    NVL(p.codigo, ep.codigo) as codigo,
                    p.sexo as sexo,
                    NVL(p.edad, FLOOR(MONTHS_BETWEEN(SYSDATE, p.fecnacimiento) / 12)) as edad,
                    ct.descripcion as seccion"
                )
                ->first();

            if (! $row) {
                return null;
            }

            return [
                'identificacion' => trim((string) ($row->identificacion ?? '')),
                'nombre' => trim((string) ($row->nombre ?? '')),
                'codigo' => trim((string) ($row->codigo ?? '')),
                'sexo' => trim((string) ($row->sexo ?? '')),
                'edad' => isset($row->edad) ? (string) $row->edad : '',
                'seccion' => trim((string) ($row->seccion ?? '')),
            ];
        });
    }

    private function obtenerTipoPermisoId(): ?string
    {
        return Cache::remember('emp_novedades_tipo_permiso_id', 300, function () {
            return DB::connection('oracle-360')
                ->table('EMP_NOVEDADES_TIPO')
                ->whereRaw('UPPER(descripcion) = ?', ['PERMISO'])
                ->value('id');
        });
    }

    private function esJefeDirectoDeEmpleado(string $documentoJefe, string $documentoEmpleado): bool
    {
        $documentoJefe = trim($documentoJefe);
        $documentoEmpleado = trim($documentoEmpleado);
        if ($documentoJefe === '' || $documentoEmpleado === '') {
            return false;
        }

        $sql = <<<'SQL'
SELECT 1 AS aplica
FROM per_personas pj
JOIN per_empresapersonas epj ON epj.pe_id_pe = pj.id
JOIN per_cargoccostos ccj ON ccj.id = epj.cc_id
JOIN per_centrocostos ctj ON ctj.codigo = ccj.ct_codigo
JOIN per_cargoccostos cce
  ON cce.id_cargosup = ccj.ca_codigo
 AND cce.ct_codigo = ccj.ct_codigo
JOIN per_empresapersonas epe ON epe.cc_id = cce.id
JOIN per_personas pe ON pe.id = epe.pe_id_pe
JOIN per_centrocostos cte ON cte.codigo = cce.ct_codigo
WHERE pj.identificacion = ?
  AND pe.identificacion = ?
  AND epj.tp_id IN (1, 11)
  AND epj.activo = 1
  AND epj.estborrado = 0
  AND epj.fecfin IS NULL
  AND ccj.activo = 1
  AND ccj.estborrado = 0
  AND ctj.estado = 1
  AND ctj.estborrado = 0
  AND epe.tp_id IN (1, 11)
  AND epe.activo = 1
  AND epe.estborrado = 0
  AND epe.fecfin IS NULL
  AND cce.activo = 1
  AND cce.estborrado = 0
  AND cte.estado = 1
  AND cte.estborrado = 0
  AND ROWNUM = 1
SQL;

        $row = DB::connection('oracle')->selectOne($sql, [$documentoJefe, $documentoEmpleado]);

        return $row !== null;
    }

    private function obtenerNovedadPermiso(string $idNovedad): ?object
    {
        return $this->nuevaConsultaPermisos360()
            ->where('n.id', $idNovedad)
            ->select([
                'n.id',
                'n.id_persona',
                'n.estado',
                'n.fecha_inicio',
                'n.fecha_fin',
                'n.observacion',
            ])
            ->first();
    }

    private function nuevaConsultaPermisos360(): Builder
    {
        return DB::connection('oracle-360')
            ->table('EMP_NOVEDADES as n')
            ->leftJoin('EMP_NOVEDADES_TIPO as t', 't.id', '=', 'n.id_tipo_novedad')
            ->whereRaw('UPPER(NVL(t.descripcion, \'\')) = ?', ['PERMISO']);
    }

    private function fueCreadoPorUsuario(string $idNovedad, string $documentoUsuario): bool
    {
        $idNovedad = trim($idNovedad);
        $documentoUsuario = trim($documentoUsuario);

        if ($idNovedad === '' || $documentoUsuario === '') {
            return false;
        }

        return DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->where('id_novedad', $idNovedad)
            ->where('estado_nuevo', self::ESTADO_RADICADO)
            ->where('usuario_accion', $documentoUsuario)
            ->exists();
    }

    private function registrarMetadatosRadicacion(
        string $idNovedad,
        array $payload,
        string $documentoActor,
        ?string $nombreActor,
        string $origen,
        ?string $ipEquipo,
        ?string $sistemaOrigen
    ): void {
        try {
            DB::connection('oracle-360')
                ->table('EMP_NOVEDADES_HISTORIAL')
                ->insert([
                    'id' => Str::uuid()->toString(),
                    'id_novedad' => $idNovedad,
                    'estado_anterior' => self::ESTADO_RADICADO,
                    'estado_nuevo' => self::ESTADO_RADICADO,
                    'fecha_cambio' => now(),
                    'usuario_accion' => trim($documentoActor) !== '' ? trim($documentoActor) : null,
                    'observacion' => $this->construirObservacionRadicado($payload, $nombreActor, $origen, $ipEquipo, $sistemaOrigen),
                ]);
        } catch (\Throwable) {
            // No se interrumpe el radicado si falla el guardado de metadatos auxiliares.
        }
    }

    private function construirObservacionRadicado(
        array $payload,
        ?string $nombreActor,
        string $origen,
        ?string $ipEquipo,
        ?string $sistemaOrigen
    ): string
    {
        $motivo = strtoupper(trim((string) ($payload['motivo'] ?? '')));
        $otroMotivo = $this->normalizarTexto($payload['otro_motivo'] ?? null);
        $ipEquipo = $this->sanitizarValorMetadato($ipEquipo);
        $sistemaOrigen = $this->sanitizarValorMetadato($sistemaOrigen);

        $partes = [
            self::META_MOTIVO_CODIGO.'='.$motivo,
            self::META_ORIGEN.'='.strtoupper(trim($origen)),
        ];

        $nombreActorSanitizado = $this->sanitizarValorMetadato($nombreActor);
        if ($nombreActorSanitizado !== null) {
            $partes[] = self::META_CREADO_POR_NOMBRE.'='.$nombreActorSanitizado;
        }

        if ($ipEquipo !== null) {
            $partes[] = self::META_IP_EQUIPO.'='.$ipEquipo;
        }

        if ($sistemaOrigen !== null) {
            $partes[] = self::META_SISTEMA_ORIGEN.'='.$sistemaOrigen;
        }

        if ($motivo === 'OTROS' && $otroMotivo !== null) {
            $partes[] = self::META_OTRO_MOTIVO.'='.$this->sanitizarValorMetadato($otroMotivo);
        }

        return mb_substr(implode(' | ', $partes), 0, self::MAX_OBSERVACION_HISTORIAL, 'UTF-8');
    }

    private function obtenerMotivosPorNovedades(array $idsNovedad): array
    {
        $ids = $this->normalizarIds($idsNovedad);

        if ($ids === []) {
            return [];
        }

        $historial = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->whereIn('id_novedad', $ids)
            ->where('estado_nuevo', self::ESTADO_RADICADO)
            ->whereRaw("UPPER(NVL(observacion, '')) LIKE ?", ['%'.self::META_MOTIVO_CODIGO.'=%'])
            ->select([
                'id_novedad',
                'observacion',
                'fecha_cambio',
            ])
            ->orderBy('fecha_cambio')
            ->get();

        $resultado = [];
        foreach ($historial as $item) {
            $idNovedad = trim((string) ($item->id_novedad ?? ''));
            if ($idNovedad === '') {
                continue;
            }

            $metadatos = $this->parsearObservacionMetadatos((string) ($item->observacion ?? ''));
            $motivo = strtoupper(trim((string) ($metadatos[self::META_MOTIVO_CODIGO] ?? '')));
            if ($motivo === '') {
                continue;
            }

            $resultado[$idNovedad] = [
                'motivo_catalogo' => $motivo,
                'otro_motivo' => $this->normalizarTexto($metadatos[self::META_OTRO_MOTIVO] ?? null),
                'ip_equipo' => $this->normalizarTexto($metadatos[self::META_IP_EQUIPO] ?? null),
                'sistema_origen' => $this->normalizarTexto($metadatos[self::META_SISTEMA_ORIGEN] ?? null),
            ];
        }

        return $resultado;
    }

    private function sanitizarValorMetadato(?string $valor): ?string
    {
        $normalizado = $this->normalizarTexto($valor);
        if ($normalizado === null || $normalizado === '') {
            return null;
        }

        return str_replace(['|', "\r", "\n"], ['/', ' ', ' '], $normalizado);
    }

    private function construirObservacionAnulacion(?string $observacionActual, string $motivoAnulacion): string
    {
        $observacionBase = (string) ($this->normalizarTexto($observacionActual) ?? '');
        $motivoSanitizado = $this->sanitizarValorMetadato($motivoAnulacion) ?? 'NO_INDICADO';
        $segmentoAnulacion = self::META_ANULACION_MOTIVO.'='.$motivoSanitizado;

        $observacion = $observacionBase === ''
            ? $segmentoAnulacion
            : $observacionBase.' | '.$segmentoAnulacion;

        return mb_substr($observacion, 0, self::MAX_OBSERVACION_NOVEDAD, 'UTF-8');
    }

    private function limpiarObservacionActividad(?string $observacion): ?string
    {
        $texto = (string) ($this->normalizarTexto($observacion) ?? '');
        if ($texto === '') {
            return null;
        }

        $partes = preg_split('/\s\|\s'.preg_quote(self::META_ANULACION_MOTIVO, '/').'=/i', $texto, 2);
        $actividad = trim((string) ($partes[0] ?? ''));

        return $actividad === '' ? null : $actividad;
    }

    private function parsearObservacionMetadatos(string $observacion): array
    {
        $resultado = [];

        foreach (explode('|', $observacion) as $segmento) {
            $segmento = trim($segmento);
            if ($segmento === '' || ! str_contains($segmento, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $segmento, 2);
            $clave = strtoupper(trim((string) $clave));
            if ($clave === '') {
                continue;
            }

            $resultado[$clave] = trim((string) $valor);
        }

        return $resultado;
    }

    private function obtenerFirmasPorNovedades(array $idsNovedad): array
    {
        $ids = $this->normalizarIds($idsNovedad);

        if ($ids === []) {
            return [];
        }

        $historial = DB::connection('oracle-360')
            ->table('EMP_NOVEDADES_HISTORIAL')
            ->whereIn('id_novedad', $ids)
            ->whereIn('estado_nuevo', [self::ESTADO_JEFE_APROBADO, self::ESTADO_APROBADO])
            ->select([
                'id_novedad',
                'estado_nuevo',
                'usuario_accion',
                'fecha_cambio',
            ])
            ->orderBy('fecha_cambio')
            ->get();

        $firmas = [];
        $documentos = [];

        foreach ($historial as $item) {
            $idNovedad = trim((string) ($item->id_novedad ?? ''));
            if ($idNovedad === '') {
                continue;
            }

            if (! isset($firmas[$idNovedad])) {
                $firmas[$idNovedad] = [
                    'jefe_aprobado_por_documento' => null,
                    'jefe_aprobado_por_nombre' => null,
                    'rrhh_aprobado_por_documento' => null,
                    'rrhh_aprobado_por_nombre' => null,
                ];
            }

            $documentoAccion = trim((string) ($item->usuario_accion ?? ''));
            if ($documentoAccion !== '') {
                $documentos[$documentoAccion] = true;
            }

            $estadoNuevo = trim((string) ($item->estado_nuevo ?? ''));
            if ($estadoNuevo === self::ESTADO_JEFE_APROBADO && $documentoAccion !== '') {
                $firmas[$idNovedad]['jefe_aprobado_por_documento'] = $documentoAccion;
            }

            if ($estadoNuevo === self::ESTADO_APROBADO && $documentoAccion !== '') {
                $firmas[$idNovedad]['rrhh_aprobado_por_documento'] = $documentoAccion;
            }
        }

        $nombresPorDocumento = $this->obtenerNombresPorDocumentos(array_keys($documentos));
        foreach ($firmas as &$firma) {
            $docJefe = $firma['jefe_aprobado_por_documento'];
            $docRrhh = $firma['rrhh_aprobado_por_documento'];
            $firma['jefe_aprobado_por_nombre'] = $docJefe ? ($nombresPorDocumento[$docJefe] ?? null) : null;
            $firma['rrhh_aprobado_por_nombre'] = $docRrhh ? ($nombresPorDocumento[$docRrhh] ?? null) : null;
        }
        unset($firma);

        return $firmas;
    }

    private function obtenerNombresPorDocumentos(array $documentos): array
    {
        $documentos = $this->normalizarIds($documentos);

        if ($documentos === []) {
            return [];
        }

        $nombres = [];
        foreach (array_chunk($documentos, 900) as $chunk) {
            $filas = DB::connection('oracle')
                ->table('per_personas as p')
                ->whereIn('p.identificacion', $chunk)
                ->selectRaw(
                    "p.identificacion as identificacion,
                    TRIM(p.pnombre || ' ' || NVL(p.snombre, '')) || ' ' ||
                    TRIM(p.papellido || ' ' || NVL(p.sapellido, '')) as nombre"
                )
                ->get();

            foreach ($filas as $fila) {
                $documento = trim((string) ($fila->identificacion ?? ''));
                if ($documento === '') {
                    continue;
                }

                $nombres[$documento] = $this->normalizarTexto($fila->nombre ?? null);
            }
        }

        return $nombres;
    }

    private function actualizarEstadoNovedad(
        string $idNovedad,
        string $nuevoEstado,
        string $documentoActor,
        ?string $observacion = null
    ): void {
        $campos = [
            'estado' => $nuevoEstado,
            'fecha_modifica' => now(),
            'usuario_modifica' => $documentoActor,
        ];

        if ($observacion !== null) {
            $campos['observacion'] = $observacion;
        }

        DB::connection('oracle-360')
            ->table('EMP_NOVEDADES')
            ->where('id', $idNovedad)
            ->update($campos);
    }

    private function construirErrorCambioEstado(\Throwable $e, string $mensajeBase): array
    {
        $mensajeError = (string) $e->getMessage();
        if (
            str_contains(strtoupper($mensajeError), 'CK_EMP_NOVEDADES_ESTADO')
            && str_contains(strtoupper($mensajeError), 'JEFE_APROBADO')
        ) {
            return [
                'ok' => false,
                'message' => 'La base de datos aun no permite el estado JEFE_APROBADO en EMP_NOVEDADES. Debes ajustar la restriccion CK_EMP_NOVEDADES_ESTADO.',
                'error' => $mensajeError,
            ];
        }

        return [
            'ok' => false,
            'message' => $mensajeBase,
            'error' => $mensajeError,
        ];
    }

    private function normalizarTexto(mixed $value): ?string
    {
        $texto = trim((string) $value);

        return $texto === '' ? null : $texto;
    }

    private function normalizarIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();
    }
}
