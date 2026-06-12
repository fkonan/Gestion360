# Contexto de Transferencia - GestionRRHH

Fecha de corte: `2026-06-04`

Este documento resume el estado actual del trabajo realizado en el modulo `GestionRRHH`, especialmente el dominio de `novedades`, para poder continuar en otro chat sin reconstruir contexto desde cero.

## 1. Objetivo del modulo

El modulo se reorganizo para centralizar solicitudes y novedades de RRHH bajo un mismo dominio funcional.

Tipos de solicitud/novedad ya trabajados:

- `Permiso`
- `Incapacidad`
- `Vacaciones`
- `Permiso permanente`
- `Citacion a descargos` solo en etapa inicial

La idea funcional base es:

- Una `solicitud` existe mientras esta en tramite.
- Una `novedad` existe realmente cuando ya fue `APROBADA`.
- La vista de `mis solicitudes` muestra tramites.
- La vista de `mis novedades` y `novedades aprobadas` muestra novedades ya aprobadas.

## 2. Estructura tecnica principal

Rutas y controladores principales:

- Web: [app/Modules/GestionRRHH/Routes/web.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Routes/web.php)
- API: [app/Modules/GestionRRHH/Routes/api.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Routes/api.php)
- Controlador web central: [EmpleadoSolicitudController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/EmpleadoSolicitudController.php)
- API central de solicitudes: [EmpleadoSolicitudApiController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/Api/EmpleadoSolicitudApiController.php)
- Incapacidades web/API:
  - [EmpleadoIncapacidadController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/Incapacidades/EmpleadoIncapacidadController.php)
  - [EmpleadoIncapacidadApiController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/Incapacidades/Api/EmpleadoIncapacidadApiController.php)
- Vacaciones web: [EmpleadoVacacionController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/Vacaciones/EmpleadoVacacionController.php)
- Permisos permanentes web: [EmpleadoPermisoPermanenteController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/PermisosPermanentes/EmpleadoPermisoPermanenteController.php)
- Descargos web: [DescargoCitacionController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/Descargos/DescargoCitacionController.php)

Servicios principales:

- Servicio central de lectura/listado: [EmpleadoNovedadService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/EmpleadoNovedadService.php)
- Resolver de tipo de novedad: [NovedadTipoResolver.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/NovedadTipoResolver.php)
- Notificaciones/correos: [NovedadNotificacionService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/NovedadNotificacionService.php)
- Incapacidades: [EmpleadoIncapacidadService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Incapacidades/EmpleadoIncapacidadService.php)
- Documentos incapacidad: [EmpleadoIncapacidadDocumentoService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Incapacidades/EmpleadoIncapacidadDocumentoService.php)
- Permisos: [EmpleadoPermisoService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Permisos/EmpleadoPermisoService.php)
- Vacaciones: [EmpleadoVacacionService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Vacaciones/EmpleadoVacacionService.php)
- Permisos permanentes: [EmpleadoPermisoPermanenteService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/PermisosPermanentes/EmpleadoPermisoPermanenteService.php)
- Bloqueos: [BloqueoService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/BloqueoService.php)
- Equipo/jefes/asociados: [JefeEquipoService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/JefeEquipoService.php)
- Personas/utilidades generales: [EmpleadoService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/EmpleadoService.php)

Vistas principales:

- Home de solicitudes/novedades: [index.blade.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Resources/Views/novedades/index.blade.php)
- Selector de radicacion: [radicar_selector.blade.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Resources/Views/novedades/radicar_selector.blade.php)
- Lista central: [novedades_lista.blade.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Resources/Views/novedades/novedades_lista.blade.php)
- Documentos modal/vista: [documentos.blade.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Resources/Views/novedades/documentos.blade.php)

## 3. Modelo de datos de novedades

La logica quedo centralizada con `EMP_NOVEDADES` como maestro.

Tablas clave:

- `EMP_NOVEDADES`: registro maestro de la solicitud/novedad.
- `EMP_NOVEDADES_TIPO`: parametriza tipos (`tabla`, `descripcion`, `requiere_bloqueo`, `id_bloqueo_logtrans`, `id_bloqueo_fics`, etc.).
- `EMP_NOVEDADES_DOCUMENTOS`: adjuntos centralizados para todos los tipos.
- `EMP_NOVEDADES_HISTORIAL`: historial de cambios. Ojo: actualmente existe trigger en BD que registra cambios automaticamente.

Tablas detalle por tipo:

- `EMP_PERMISOS`
- `EMP_INCAPACIDADES`
- `EMP_VACACIONES`
- `EMP_PERMISOS_PERMANENTES`

Regla estructural actual:

- La tabla detalle no guarda `id_novedad`.
- `EMP_NOVEDADES` guarda:
  - `id_tipo_novedad`
  - `id_origen`
  - el `id_origen` apunta al registro detalle
  - el tipo resuelve en qué tabla buscar a traves de `EMP_NOVEDADES_TIPO.tabla`

Documentos:

- Todo adjunto va a `EMP_NOVEDADES_DOCUMENTOS`.
- La columna real vigente es `RUTA_DOCUMENTO`.
- `URL_DOCUMENTO` ya no se usa y fue retirada del flujo.

## 4. Estados por tipo

### Permisos

Estados manejados:

- `RADICADO`
- `JEFE_APROBADO`
- `APROBADO`
- `RECHAZADO`
- `ANULADO`

Flujo:

- Empleado radica.
- Jefe aprueba o rechaza.
- Si aprueba jefe, pasa a RRHH.
- RRHH aprueba o rechaza.
- No se puede anular despues de aprobado.

### Incapacidades

Estados manejados:

- `RADICADO`
- `APROBADO`
- `RECHAZADO`
- `ANULADO`

Flujo:

- Empleado radica.
- RRHH gestiona directamente.
- Solo incapacidades `APROBADAS` pueden tener seguimiento RRHH.

### Vacaciones

Estados manejados en la practica:

- `RADICADO`
- `JEFE_APROBADO`
- `APROBADO`
- `RECHAZADO`
- `ANULADO`

Excepcion funcional importante:

- Si el centro de costo es `CONTRATO CERREJON`, solo aprueba jefe y no pasa por RRHH.
- Para conductores, el primer aprobador no es jefe sino asociado/empleador relacionado.

### Permiso permanente

Estados manejados:

- `RADICADO`
- `JEFE_APROBADO`
- `APROBADO`
- `RECHAZADO`
- `ANULADO`

Particularidades:

- Tiene `jornada`.
- Puede tener `horario_fijo`.
- Aplica a administrativos.
- No deben cruzarse dos permisos permanentes activos para la misma persona.

## 5. Fuentes de datos y decisiones actuales

### Personas

La busqueda de personas activas sale de Oracle, principalmente `per_personas` y `per_empresapersonas`.

Se reutiliza desde servicios del modulo, no desde MySQL legacy.

### Motivos de permiso

Ya no estan quemados.

Salen de `PAR_PARAMETROS` usando el area configurada para:

- `EMPLEADOS-MOTIVOS-PERMISO`

Referencia de implementacion:

- [EmpleadoPermisoService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Permisos/EmpleadoPermisoService.php)

### EPS y ARL para incapacidades

Decision vigente:

- `EMP_EPS` y `EMP_ARL` quedaron obsoletas conceptualmente.
- El codigo ya fue cambiado para usar solo `PAR_PARAMETROS`.
- EPS sale de `PAR_PARAMETROS` con `area_id = 'GENERAL-EPS'`.
- ARL sale de `PAR_PARAMETROS` con `area_id = 'GENERAL-ARL'`.
- Se usa `PARAMETRO_ID` como llave y `parametro_valor` como nombre.
- No hay fallback a `EMP_EPS/EMP_ARL`.

Archivos ajustados:

- [EmpleadoIncapacidadService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Incapacidades/EmpleadoIncapacidadService.php)
- [EmpleadoNovedadService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/EmpleadoNovedadService.php)

### Diagnosticos y causas de incapacidad

Siguen en tablas propias Oracle 360:

- `EMP_CAUSAS_INCAPACIDAD`
- `EMP_DIAGNOSTICOS`

Con columna `estado = ACTIVO`.

## 6. Bloqueos operativos

La columna `EMP_NOVEDADES_TIPO.REQUIERE_BLOQUEO` define si una novedad aprobada debe generar bloqueo.

IDs de bloqueo se parametrizaron en `EMP_NOVEDADES_TIPO`:

- `id_bloqueo_fics`
- `id_bloqueo_logtrans`

Decisiones funcionales registradas:

- Vacaciones:
  - FICS: `1`
  - Logtrans: `53`
- Incapacidad:
  - FICS: `5`
  - Logtrans: `45`
- Permiso normal:
  - por ahora no bloquea

Regla:

- El bloqueo usa las fechas `fecha_inicio` y `fecha_fin` de la novedad aprobada.

Proceso FICS pendiente/reproceso:

- Existe tabla `gestionrrhh_desbloqueos_fics_pendientes`
- Existe comando programado cada 5 minutos:
  - `bloqueo:reprocesar-desbloqueos-fics --limit=300`

Referencia:

- [bootstrap/app.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/bootstrap/app.php)
- [ReprocesarDesbloqueosFicsCommand.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Console/Commands/ReprocesarDesbloqueosFicsCommand.php)

## 7. Correos y notificaciones

La logica se centralizo en:

- [NovedadNotificacionService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/NovedadNotificacionService.php)

Y se encola con job generico:

- [EnviarNotificacionNovedadJob.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Jobs/EnviarNotificacionNovedadJob.php)

Puntos importantes:

- Los correos de RRHH y solicitudes ya usan colas.
- La cola usada es `database-admin`, queue `rrhh-mails`.
- Las tablas `jobs` y `failed_jobs` viven en `mysql-gestion-admin`.

Variables clave:

- `EMPLOYEE_PERMITS_NOTIFICATION_EMAIL`
- `EMPLOYEE_PERMITS_RRHH_NOTIFICATION_EMAIL`
- `EMPLOYEE_PERMITS_FORCE_NOTIFICATION_EMAIL`
- `EMPLOYEE_PERMITS_FORCE_MAGIC_LINK_FOR_TESTING`
- `EMPLOYEE_PERMITS_MAIL_QUEUE_CONNECTION`
- `EMPLOYEE_PERMITS_MAIL_QUEUE_NAME`

Comportamiento actual de `EMPLOYEE_PERMITS_FORCE_NOTIFICATION_EMAIL`:

- `true`: siempre usa correo de pruebas configurado en `EMPLOYEE_PERMITS_NOTIFICATION_EMAIL`.
- `false`: usa correo real de jefe/aprobador inicial cuando existe; si falta, cae al correo configurado.

Comportamiento actual de `EMPLOYEE_PERMITS_FORCE_MAGIC_LINK_FOR_TESTING`:

- Fuerza generación de magic link en correos de jefe aunque el destinatario real esté quemado para pruebas.
- No controla por sí mismo el destinatario; eso lo controla `EMPLOYEE_PERMITS_FORCE_NOTIFICATION_EMAIL`.

## 8. API v2

La version vigente es `v2`. La `v1` ya no es la version objetivo.

Archivo de rutas:

- [app/Modules/GestionRRHH/Routes/api.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Routes/api.php)

Autenticacion:

- Endpoint: `POST /api/v2/auth/token`
- Controlador: [JwtAuthController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Http/Controllers/Api/JwtAuthController.php)
- Middleware: [ValidateApiJwt.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Http/Middleware/ValidateApiJwt.php)

Modelo actual:

- `client_credentials`
- `client_id`
- `client_secret`
- `scope`
- `documento_usuario` opcional/recomendado

El token puede incluir `actor_documento` como claim. Parte de los endpoints usan ese claim para no requerir el documento en cada request.

Scope admin ya soportado:

- `empleados.novedades.admin`

Endpoints principales implementados:

- `POST /api/v2/auth/token`
- `GET /api/v2/empleados/novedades`
- `GET /api/v2/empleados/permisos`
- `POST /api/v2/empleados/permisos/radicar`
- `POST /api/v2/empleados/incapacidades/radicar`
- `POST /api/v2/empleados/vacaciones/radicar`
- `POST /api/v2/empleados/permisos-permanentes/radicar`
- `POST /api/v2/empleados/solicitudes/{idNovedad}/anular`
- `GET /api/v2/empleados/solicitudes/equipo-jefe`
- `POST /api/v2/empleados/solicitudes/{idNovedad}/gestionar-jefe`
- `GET /api/v2/empleados/solicitudes/pendientes-rrhh`
- `POST /api/v2/empleados/solicitudes/{idNovedad}/gestionar-rrhh`
- `GET /api/v2/empleados/incapacidades/catalogos`

Notas de API:

- Se buscó unificar naming a `documento_usuario`, `documento_actor`, `documento_persona`, `documento_radica`.
- Algunas respuestas fueron simplificadas para devolver `ruta_documento` y no `url_documento`.

## 9. Web actual

Entrada principal actual:

- `GET /gestionRRHH/solicitudes`
- Nombre de ruta: `gestionRRHH.solicitudes.index`

Observacion importante:

- Aun sobreviven muchos nombres de ruta legacy `gestionRRHH.permisos.*`
- La URL visible para el usuario ya se movió hacia `solicitudes`, pero internamente sigue habiendo alias y nombres antiguos para compatibilidad.
- Si en el futuro se limpia esto, debe hacerse con cuidado porque muchas vistas y correos aún referencian `gestionRRHH.permisos.*`.

## 10. Listados y UX

La vista central de bandejas es:

- [novedades_lista.blade.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Resources/Views/novedades/novedades_lista.blade.php)

La bandeja unificada soporta varios modos:

- `mis solicitudes`
- `mis novedades`
- `solicitudes del equipo`
- `gestion central RRHH`
- `novedades aprobadas`

Decisiones ya aplicadas:

- El detalle de tabla se movió a la fila de detalle, no como columna principal.
- Los adjuntos ya no abren otra vista innecesaria por defecto; se gestionan desde modal/vista más directa.
- Hay filtros para solicitudes y novedades.
- En novedades aprobadas existe enfoque de “activas hoy”.

## 11. Descargos y suspensiones

Estado actual:

- Solo se implementó la primera pieza: `citacion a descargos`.
- Todavía no existe flujo completo de disciplina/suspension.

Tabla creada:

- `EMP_DESCARGOS_CITACIONES`

Servicio:

- [DescargoCitacionService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Descargos/DescargoCitacionService.php)

Controlador:

- [DescargoCitacionController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/Descargos/DescargoCitacionController.php)

Campos relevantes actuales:

- `id`
- `documento_persona`
- `fecha_citacion`
- `observacion`
- `estado`
- `fecha_notificacion`
- `origen`
- `ip_equipo`
- `sistema_origen`
- `id_creacion`
- `id_modifica`
- `fecha_creacion`
- `fecha_modifica`

Estado inicial usado:

- `CITADO_DESCARGOS`

Correo:

- Se notifica al empleado al crear la citacion.

Pendiente funcional fuerte:

- No esta definido aún el flujo final de `disciplina`, `pendiente de decision`, `suspendido`, `sin sancion`, `conciliacion economica`, etc.
- Antes de seguir desarrollando esta parte conviene cerrar requerimientos con el dueño del proceso.

## 12. Logs y trazabilidad

Middleware de consumo:

- [LogRrhhRequestConsumption.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Http/Middleware/LogRrhhRequestConsumption.php)

Canal de log:

- `rrhh_novedades`

Ubicacion:

- `storage/logs/gestionrrhh/novedades/rrhh-novedades-YYYY-MM-DD.log`

Reglas actuales:

- API: registra consumos en general.
- WEB: registra radicaciones y errores relevantes del flujo RRHH.
- No debe guardar HTML de respuesta completa en web.
- Sanitiza campos sensibles y archivos.

Tambien existe log de performance para radicaciones:

- [NovedadPerfLogger.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/NovedadPerfLogger.php)

Hallazgo previo importante:

- El mayor costo en radicacion de incapacidad normalmente está en `guardar_adjuntos`, especialmente upload documental.

## 13. Migraciones relevantes

Migraciones importantes ya creadas en este frente:

- Alineacion de `EMP_NOVEDADES`
- Creacion de `EMP_PERMISOS`
- Creacion de `EMP_INCAPACIDADES`
- Creacion y ajustes de catalogos de incapacidad
- Creacion de `EMP_VACACIONES`
- Creacion de `EMP_PERMISOS_PERMANENTES`
- Semilla de motivos de permiso en `PAR_PARAMETROS`
- Creacion y refinamiento de `EMP_DESCARGOS_CITACIONES`
- Cola/pendientes de desbloqueo FICS

Revisar listado real en:

- [database/migrations](/abs/path/c:/xampp/htdocs/gestion/autogestion2/database/migrations)

## 14. Pendientes y riesgos conocidos

Pendientes funcionales:

- Definir flujo completo de disciplina/suspensiones.
- Eventualmente eliminar tablas redundantes `EMP_EPS` y `EMP_ARL` si ya no son usadas fuera de este modulo.
- Eventualmente normalizar nombres de rutas legacy `gestionRRHH.permisos.*`.

Pendientes tecnicos:

- Limpiar carpetas vacías/residuos legacy si aún quedan.
- Verificar si existen referencias externas a modelos/tablas obsoletas antes de borrar físicamente tablas Oracle.
- Revisar si alguna documentacion PDF/Postman quedó desactualizada respecto a `documento_usuario`, `scope admin`, y `PAR_PARAMETROS` para EPS/ARL.

Riesgos:

- Los workers de cola deben reiniciarse después de cambios en jobs/notificaciones.
- Si se cambia `.env` de notificaciones y no se reinician workers, el comportamiento puede parecer “inconsistente”.
- La parte de descargos todavía no debe venderse como módulo cerrado; solo existe citación inicial.

## 15. Variables de entorno críticas

Autenticacion API:

- `API_JWT_SECRET`
- `API_JWT_ISSUER`
- `API_JWT_AUDIENCE`
- `API_JWT_TTL_MINUTES`
- `API_JWT_LEEWAY_SECONDS`
- `API_JWT_CLIENT_ID`
- `API_JWT_CLIENT_SECRET`
- `API_JWT_CLIENT_SCOPES`
- `API_JWT_DEFAULT_SCOPE`
- `API_JWT_ADMIN_SCOPES`

Correos y colas:

- `EMPLOYEE_PERMITS_NOTIFICATION_EMAIL`
- `EMPLOYEE_PERMITS_RRHH_NOTIFICATION_EMAIL`
- `EMPLOYEE_PERMITS_FORCE_NOTIFICATION_EMAIL`
- `EMPLOYEE_PERMITS_FORCE_MAGIC_LINK_FOR_TESTING`
- `EMPLOYEE_PERMITS_MAIL_QUEUE_CONNECTION`
- `EMPLOYEE_PERMITS_MAIL_QUEUE_NAME`
- `DB_QUEUE_ADMIN_CONNECTION`
- `DB_QUEUE_ADMIN_TABLE`
- `DB_QUEUE_ADMIN_QUEUE`
- `QUEUE_FAILED_DATABASE`
- `QUEUE_FAILED_TABLE`

Adjuntos/documental:

- `DOCUMENTAL_DISK`
- `DOCUMENTAL_BASE_DIRECTORY`
- `DOCUMENTAL_PUBLIC_BASE_URL`
- `EMPLOYEE_PERMITS_ATTACHMENTS_API_*`
- `EMPLOYEE_INCAPACITIES_ATTACHMENTS_API_*`

Logs:

- `RRHH_NOVEDADES_LOG_LEVEL`
- `RRHH_NOVEDADES_LOG_DAYS`
- `EMPLOYEE_PERMITS_PERF_LOG_ENABLED`
- `EMPLOYEE_PERMITS_PERF_LOG_THRESHOLD_MS`
- `EMPLOYEE_PERMITS_PERF_LOG_LEVEL`

## 16. Si se retoma trabajo en otro chat

Conviene empezar leyendo estos archivos:

- [app/Modules/GestionRRHH/README.md](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/README.md)
- [EmpleadoSolicitudController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/EmpleadoSolicitudController.php)
- [EmpleadoSolicitudApiController.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Http/Controllers/Novedades/Api/EmpleadoSolicitudApiController.php)
- [EmpleadoNovedadService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/EmpleadoNovedadService.php)
- [NovedadNotificacionService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/NovedadNotificacionService.php)
- [EmpleadoIncapacidadService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/Novedades/Incapacidades/EmpleadoIncapacidadService.php)
- [BloqueoService.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Services/BloqueoService.php)
- [app/Modules/GestionRRHH/Routes/api.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Routes/api.php)
- [app/Modules/GestionRRHH/Routes/web.php](/abs/path/c:/xampp/htdocs/gestion/autogestion2/app/Modules/GestionRRHH/Routes/web.php)

Preguntas típicas a validar antes de tocar algo:

- ¿El cambio afecta solicitud, novedad o ambas?
- ¿El flujo aplica a web, API o ambas?
- ¿El tipo de novedad ya existe en `EMP_NOVEDADES_TIPO`?
- ¿Genera bloqueo?
- ¿Requiere correo a jefe, RRHH o ambos?
- ¿El origen del catálogo está en tabla propia o en `PAR_PARAMETROS`?
- ¿Debe quedar trazabilidad en historial o ya lo cubre trigger?

