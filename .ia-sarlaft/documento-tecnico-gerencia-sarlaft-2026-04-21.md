# Documento Tecnico Gerencial - Modulo SARLAFT

Fecha de corte: 21 de abril de 2026  
Sistema: `Autogestion2`  
Alcance: Solo `app/Modules/Sarlaft` y su integracion directa en rutas, configuracion y scheduler.

## 1. Resumen Ejecutivo

El modulo SARLAFT tiene una base funcional solida y madura para:

1. Consultar personas y organizaciones contra listas vinculantes e internas.
2. Generar alertas operativas con decisiones de servicio.
3. Ejecutar procesos automaticos de retencion y escalamiento por SLA.
4. Administrar politicas, bloqueos, lista negra interna, sincronizaciones y reportes operativos.

Estado general inferido: **Operativo con oportunidades claras de endurecimiento en seguridad API y gobierno operativo**.

Hallazgos gerenciales clave:

1. Existen controles relevantes de cumplimiento (politicas configurables, trazabilidad, escalamiento, archivado).
2. El diseno prioriza continuidad operativa (logs, jobs, scheduler, tabla de mantenimiento).
3. Hay riesgos importantes de seguridad/compliance que conviene cerrar en corto plazo:
   - Exposicion potencial de datos por endpoints API sin segmentacion por sistema.
   - Manejo de token API en texto plano.
   - Configuracion SSL con default permisivo en `config/listas.php`.
4. La cobertura de pruebas del modulo existe y es buena para flujos criticos, pero en este entorno no fue ejecutable porque no estan instaladas dependencias `require-dev` (PHPUnit).

## 2. Metodologia y Evidencia

Analisis realizado mediante:

1. Revision estatica de rutas, controladores, servicios, middleware, jobs, listeners, modelos y migraciones SARLAFT.
2. Revision de integracion con scheduler y providers de aplicacion.
3. Revision de pruebas en `tests/Feature/Sarlaft`.
4. Validacion tecnica de sintaxis PHP del modulo (`php -l` OK).

Fuera de alcance:

1. Pruebas de carga y pentesting.
2. Validacion de datos reales en BD productiva.
3. Revision de otros modulos no SARLAFT.

## 3. Inventario del Modulo

Resumen de artefactos SARLAFT:

| Componente | Cantidad |
|---|---:|
| Archivos totales | 103 |
| PHP (sin Blade) | 83 |
| Vistas Blade | 20 |
| Comandos consola | 3 |
| Servicios de negocio | 8 |
| Modelos | 13 |
| Migraciones | 20 |
| Controladores admin | 9 |
| Controladores API | 2 |
| Requests admin | 10 |
| Requests API | 2 |
| Middlewares API | 2 |
| Jobs | 1 |
| Eventos | 3 |
| Listeners | 3 |

Rutas expuestas:

| Tipo | Cantidad |
|---|---:|
| Web SARLAFT (`/sarlaft/...`) | 31 |
| API consulta (`/api/v1/consulta...`) | 3 |
| API listas (`/api/v1/listas/registros`) | 1 |

## 4. Arquitectura Funcional

### 4.1 Capas principales

1. **Presentacion/Admin**: vistas Blade con panel operativo (`dashboard`, alertas, bloqueos, politicas, simulaciones, reportes, sincronizacion, sistemas consumidores).
2. **API SARLAFT**: endpoints Bearer con middleware propio (sin Sanctum).
3. **Servicios de dominio**:
   - `ConsultaService`: motor de consulta/riesgo/alerta.
   - `DecisionServicioService`: vigencia y consumo de decisiones.
   - `SincronizacionService`: descarga, parseo y actualizacion de listas.
   - `ConsultaArchiveService`: retencion de consultas negativas.
   - `AlertaEscalationService`: escalamiento automatico por SLA.
   - `PoliticaSarlaftService`: politica central con fallback a config.
   - `ReporteOperacionesService`: analitica operacional.
   - `AlertaEvidenciaService`: adjuntos y descarga de soportes.
4. **Persistencia**: tablas `sarlaft_*` en conexion dedicada `mysql-sarlaft`.
5. **Orquestacion**:
   - Scheduler (`routes/console.php`): sincronizacion diaria, archivado diario, escalamiento horario.
   - Cola: `SincronizarListaJob` en cola `sincronizacion`.
6. **Observabilidad**:
   - Eventos/listeners para consulta, alerta y bloqueo.
   - Logs de mantenimiento y sincronizacion.

### 4.2 Integraciones externas

1. Fuentes de listas externas (ONU, OFAC SDN, OFAC Consolidated) via HTTP/XML.
2. Datos maestros internos para simulaciones (municipios y tipo documento en otros modulos/BD).
3. Correo para oficial de cumplimiento en simulaciones con riesgo.

## 5. Flujos Operativos Críticos

### 5.1 Flujo de consulta SARLAFT (API y simulaciones)

1. Sistema consumidor autentica por token Bearer.
2. Middleware valida estado activo y aplica rate limit por sistema.
3. `ConsultaService` busca coincidencias:
   - Documento exacto en listas vinculantes.
   - Coincidencia por nombre con algoritmo de scoring.
   - Lista negra interna.
4. Evalua bloqueos activos y calcula riesgo (`ninguno`, `bajo`, `medio`, `alto`).
5. Evalua decision activa previa (`bloquear`, `permitir_una_operacion`, `permitir_permanente`).
6. Persiste consulta con `contexto_operacion`.
7. Si aplica, consume decision de una sola operacion.
8. Si hay coincidencia y no esta suprimida, crea alerta.
9. Si coincide con lista negra interna y politica activa, autoatiende alerta y bloquea.

### 5.2 Flujo de sincronizacion de listas

1. Comando `listas:sincronizar` encola job por lista activa.
2. Job descarga XML y parsea segun parser (`onu`, `ofac`, `eu`).
3. Motor compara referencias:
   - Nuevos ingresos.
   - Actualizados.
   - Salidas (soft delete + novedad).
4. Actualiza log de sincronizacion con conteos y duracion.

### 5.3 Flujo de mantenimiento automático

1. `sarlaft:archivar-consultas` (diario 03:00):
   - Mueve a archivo consultas negativas antiguas sin alerta.
2. `sarlaft:procesar-alertas-vencidas` (cada hora):
   - Escala alertas pendientes segun SLA y riesgo.
   - Aplica decision automatica definida en politica.

## 6. Modelo de Datos (Resumen Ejecutivo)

Tablas principales y proposito:

| Tabla | Proposito |
|---|---|
| `sarlaft_consultas` | Registro de cada evaluacion de riesgo |
| `sarlaft_alertas` | Gestion de alertas y decisiones de servicio |
| `sarlaft_bloqueos` | Bloqueos manuales/automaticos por documento |
| `sarlaft_lista_negra_interna` | Lista restrictiva interna |
| `sarlaft_listas_vinculantes` | Catalogo de listas externas |
| `sarlaft_registros_lista` | Registros de listas sincronizadas |
| `sarlaft_sincronizacion_logs` | Trazabilidad de sincronizacion |
| `sarlaft_politicas` | Politica operativa editable |
| `sarlaft_consultas_archivo` | Archivo historico de consultas retenidas |
| `sarlaft_mantenimiento_logs` | Auditoria de procesos automaticos |
| `sarlaft_sistemas_consumidores` | Sistemas habilitados para consumir API |
| `sarlaft_simulacion_pasajes` | Simulaciones de venta de tiquete |
| `sarlaft_simulacion_remesas` | Simulaciones de remesas/mensajeria |

Aspectos tecnicos relevantes:

1. Uso consistente de `SoftDeletes` en entidades sensibles.
2. Indices para retencion, busqueda por documento y estado.
3. FULLTEXT en `sarlaft_registros_lista(nombres, alias)` para busqueda por nombre.
4. Contexto JSON para trazabilidad de decision y origen de atencion.

## 7. Seguridad, Cumplimiento y Gobierno

Fortalezas:

1. Middleware custom de autenticacion Bearer y limite por sistema.
2. Validacion estructurada por `FormRequest` en casi todo el modulo.
3. Evidencias documentales por alerta (adjuntos con metadata).
4. Politicas configurables persistidas en BD.

Riesgos observados:

1. **Token API almacenado en texto plano** en `sarlaft_sistemas_consumidores`.
2. **Endpoint `GET /api/v1/consulta/{consulta}` sin control de pertenencia por sistema consumidor**.
3. **Endpoint de listas devuelve universo de datos de listas a cualquier token activo** (segun fecha/filtro, pero no por dominio funcional de sistema).
4. **`LISTAS_VERIFICAR_SSL` con default `false`**, que puede permitir llamadas sin verificacion SSL si no se ajusta en entorno.
5. **Falta de notificacion formal multicanal** (hay TODO para email/Slack en listener de alertas).

## 8. Operacion y Continuidad

Dependencias operativas obligatorias:

1. Scheduler habilitado para tareas diarias/horarias.
2. Worker de colas activo para `sincronizacion`.
3. Acceso de red a endpoints de listas externas.
4. Capacidad de almacenamiento local para evidencias.

Buenas practicas presentes:

1. `withoutOverlapping()` en comandos programados.
2. Logs de proceso y mantenimiento para post-mortem.
3. Soporte de `--dry-run` y `--chunk` en procesos de mantenimiento.

## 9. Calidad Técnica y Pruebas

Estado encontrado:

1. Existen suites orientadas a flujos criticos:
   - `DecisionServicioFlowTest`
   - `RetentionAndEscalationTest`
   - `ReporteOperacionesServiceTest`
2. Cobertura funcional enfocada en:
   - Decisiones de servicio.
   - Escalamiento y retencion.
   - Reporte operativo.

Limitacion del entorno analizado:

1. No fue posible ejecutar pruebas porque `php artisan test` no esta disponible en este entorno y no se encuentra binario `phpunit` en `vendor/bin` (dependencias dev no instaladas).

## 10. Riesgos Priorizados para Gerencia

Escala usada:

1. Criticidad Alta: impacto legal/operativo relevante en corto plazo.
2. Criticidad Media: impacto moderado o acumulativo.
3. Criticidad Baja: mejora de eficiencia o deuda menor.

| Riesgo | Criticidad | Impacto de negocio | Accion recomendada |
|---|---|---|---|
| Exposicion de consultas por `GET /api/v1/consulta/{id}` sin ownership por sistema | Alta | Fuga de informacion sensible entre consumidores | Validar que la consulta pertenezca al `sistema_origen` del token |
| API tokens en texto plano | Alta | Mayor superficie de compromiso por fuga de BD | Migrar a hash de token + flujo de rotacion/revocacion |
| SSL verificable desactivable con default inseguro | Alta | Riesgo de MITM y alteracion de fuente externa | Cambiar default a `true` y permitir `false` solo en local |
| Notificacion de alertas no integrada (solo logs) | Media | Respuesta tardia a eventos de alto riesgo | Implementar canal formal (correo/Teams/Slack) con SLA |
| Hardcode de correo oficial en simulaciones | Media | Rigidez operacional y errores por entorno | Parametrizar en `config/sarlaft.php` + `.env` |
| Dependencia operativa fuerte de scheduler/queue sin healthcheck dedicado | Media | Procesos criticos no ejecutados sin deteccion temprana | Agregar checks de salud y alertas operativas |
| Validacion inline en endpoint de listas | Baja | Inconsistencia de estandar interno | Mover a `FormRequest` dedicado para API |

## 11. Plan de Accion 30/60/90 Dias

### Primeros 30 dias (Cierre de riesgos altos)

1. Restringir acceso a `consulta/{id}` por sistema consumidor.
2. Definir estrategia de token seguro (hash + rotacion + revocacion).
3. Endurecer SSL por defecto en integraciones de listas.
4. Parametrizar correo oficial de cumplimiento.

### 31 a 60 dias (Confiabilidad y gobierno)

1. Implementar notificaciones de alerta por canal formal.
2. Publicar tablero de salud de procesos programados y colas.
3. Normalizar autorizacion por permisos en rutas administrativas sensibles.

### 61 a 90 dias (Escalabilidad y auditoria)

1. Fortalecer trazabilidad de cambios de politica (auditoria historica).
2. Agregar pruebas de regresion para seguridad API y ownership.
3. Revisar estrategia de datos historicos para analitica y cumplimiento.

## 12. KPIs Recomendados para Seguimiento Gerencial

KPIs operativos:

1. Tiempo promedio de atencion de alerta.
2. Alertas vencidas vs atendidas dentro de SLA.
3. Porcentaje de sincronizaciones exitosas por lista y dia.
4. Volumen de consultas bloqueadas vs permitidas por canal.
5. Tasa de coincidencias por tipo (documento exacto, nombre, lista interna).

KPIs de riesgo/compliance:

1. Numero de decisiones activas por tipo (`bloquear`, `permitir_*`).
2. Numero de bloqueos automaticos vs manuales.
3. Cobertura de evidencias en alertas atendidas.
4. Incidentes de acceso API no autorizado o throttling.

## 13. Conclusión

El modulo SARLAFT ya incorpora capacidades importantes de cumplimiento y automatizacion, con una arquitectura funcional clara y trazabilidad razonable para operacion diaria.  
La recomendacion principal para gerencia es avanzar en un **plan corto de endurecimiento de seguridad API y gobierno operativo**, manteniendo la base actual y priorizando acciones de alto impacto en 30 dias.

---

## Anexo A - Evidencias de Codigo Revisadas

Referencias principales usadas en este analisis:

1. `app/Modules/Sarlaft/Services/ConsultaService.php`
2. `app/Modules/Sarlaft/Services/DecisionServicioService.php`
3. `app/Modules/Sarlaft/Services/SincronizacionService.php`
4. `app/Modules/Sarlaft/Services/ConsultaArchiveService.php`
5. `app/Modules/Sarlaft/Services/AlertaEscalationService.php`
6. `app/Modules/Sarlaft/Routes/api.php`
7. `app/Modules/Sarlaft/Routes/web.php`
8. `app/Modules/Sarlaft/Http/Middleware/AutenticarSistemaConsumidor.php`
9. `app/Modules/Sarlaft/Http/Middleware/RateLimitSistema.php`
10. `app/Modules/Sarlaft/Database/Migrations/*`
11. `routes/console.php`
12. `app/Providers/ModuleServiceProvider.php`
13. `tests/Feature/Sarlaft/*`
