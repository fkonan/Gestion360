# Guia Externa del Modulo Camara

Documento orientado a equipos externos (soporte, auditoria, infraestructura o integracion) para entender como funciona el reconocimiento facial con camara web en este proyecto.

Fecha de referencia: 2026-05-25.

## 1. Resumen ejecutivo

El modulo de Camara implementa 3 flujos principales:

1. Enrolamiento de rostro (`enroll`): registrar fotos de una persona en el servicio facial externo.
2. Reconocimiento en vivo (`recognize-live`): reconocer personas y registrar ingreso/salida laboral.
3. Verificacion en vivo (`verify-live`): reconocer personas de forma informativa, sin registrar asistencia.

Arquitectura base:

1. El navegador captura video y detecta rostro localmente.
2. El navegador envia imagenes JPG al backend Laravel.
3. Laravel reenvia esas imagenes al API facial externo.
4. En `recognize-live`, Laravel ademas intenta registrar evento laboral local.

## 2. Componentes del flujo

### 2.1 Frontend (navegador)

Responsabilidades:

1. Abrir webcam con `getUserMedia`.
2. Detectar cara localmente con MediaPipe.
3. Recortar frame(s) utiles de rostro.
4. Enviar lotes pequenos de JPG al backend.
5. Mostrar estado de camara, salud del servicio y resultados.

Archivos clave:

1. `resources/js/camara/recognize.js`
2. `resources/js/camara/enroll.js`
3. `resources/js/camara/verify.js`
4. `resources/js/camara/shared.js`
5. `resources/js/camara/mediapipe.js`

### 2.2 Backend Laravel (proxy + logica local)

Responsabilidades:

1. Validar payloads (`images[]`, formatos, cantidad, peso maximo).
2. Reenviar imagenes al servicio facial externo.
3. Responder al frontend con formato consistente.
4. En `recognize-live`, registrar asistencia (ingreso/salida) via servicio interno compartido.

Archivos clave:

1. `app/Modules/Camara/Http/Controllers/Api/CamaraApiController.php`
2. `app/Modules/Camara/Services/CameraService.php`
3. `app/Modules/Camara/Http/Requests/*.php`

### 2.3 Servicio facial externo

Responsabilidades:

1. Mantener plantillas faciales.
2. Resolver reconocimiento y verificacion facial.
3. Exponer endpoints HTTP consumidos por Laravel.

Configurable por entorno:

1. `CAMERA_SERVICE_URL`
2. `CAMERA_SERVICE_KEY`

### 2.4 Registro de asistencia local (solo recognize-live)

Responsabilidades:

1. Determinar si corresponde ingreso o salida.
2. Aplicar reglas de negocio de asistencia/horario.
3. Guardar evento en base de datos local (oracle-360).

Servicio usado:

1. `App\Modules\Huellero\Services\RegistrarEventoEmpleadoService`

## 3. Rutas expuestas y uso funcional

## 3.1 Vistas (UI)

1. `GET /reconocimiento-facial`: menu del modulo.
2. `GET /face-enroll`: pantalla de enrolamiento.
3. `GET /face-recognize`: pantalla de ingreso/salida por reconocimiento.
4. `GET /face-verify`: pantalla de verificacion informativa.

## 3.2 Endpoints usados por la UI

1. `GET /camera/health`
2. `GET /camera/session-keepalive`
3. `GET /camera/personas`
4. `POST /camera/enroll`
5. `POST /camera/recognize-live`
6. `POST /camera/verify-live`
7. `GET /camera/ultimos-eventos`
8. `GET /camera/eventos-hoy?identificacion=...`

## 4. Flujo detallado por caso de uso

## 4.1 Enrolamiento (`POST /camera/enroll`)

Objetivo: registrar 3 capturas de una persona en el servicio facial externo.

Secuencia:

1. Operador busca persona por documento/nombre (`GET /camera/personas`).
2. Frontend abre camara y valida alineacion facial.
3. Frontend toma 3 capturas guiadas:
   1. frente
   2. perfil izquierdo
   3. perfil derecho
4. Frontend envia `images[]` + `identificacion` a Laravel.
5. Laravel valida:
   1. `images` requerido, array, `size:3`
   2. cada imagen `jpg/jpeg`, max 3 MB
   3. `identificacion` requerida
6. Laravel reenvia multipart al API externo `POST {CAMERA_SERVICE_URL}/enroll`.
7. Laravel devuelve la respuesta al frontend.

Payload frontend -> Laravel:

```http
POST /camera/enroll
Content-Type: multipart/form-data

images[]: front.jpg
images[]: left.jpg
images[]: right.jpg
identificacion: "1098705238"
```

Payload Laravel -> API externo:

1. Archivos en campo `files` (no `images[]`).
2. Campos extra:
   1. `identificacion`
   2. `usrcreacion` (si existe usuario autenticado)
   3. `ident_crea` (si existe usuario autenticado)

## 4.2 Reconocimiento en vivo (`POST /camera/recognize-live`)

Objetivo: reconocer persona y registrar marca de ingreso/salida.

Secuencia:

1. Frontend inicia camara automaticamente.
2. Detecta rostros localmente (MediaPipe).
3. Construye lotes chicos de imagenes JPG optimizadas.
4. Envia `images[]` al backend cada ~1.0 a 1.5 segundos (adaptativo).
5. Laravel valida:
   1. `images` requerido, array, min 1, max 5
   2. cada imagen `jpg/jpeg`, max 3 MB
6. Laravel reenvia a API externo `POST /recognize`.
7. Si API externo devuelve personas:
   1. Laravel elimina duplicados por identificacion en el mismo payload.
   2. Intenta registrar evento laboral por cada identificacion.
8. Laravel responde al frontend con `personas` procesadas y, si aplica, `rechazados`.

Payload frontend -> Laravel:

```http
POST /camera/recognize-live
Content-Type: multipart/form-data

images[]: frame_1.jpg
images[]: frame_2.jpg
...
```

Payload Laravel -> API externo:

1. Archivos en campo `files` (uno por imagen).
2. Campos extra:
   1. `usrcreacion`
   2. `ident_crea`

Respuesta esperada al frontend:

```json
{
  "ok": true,
  "personas": [
    {
      "identificacion": "1098705238",
      "nombre": "NOMBRE APELLIDO",
      "evento": 2,
      "fecha_registro": "25/05/2026",
      "hora_registro": "11:10 am"
    }
  ],
  "rechazados": [
    {
      "identificacion": "1098705238",
      "motivo": "No se pudo registrar el evento."
    }
  ]
}
```

Nota importante:

1. `rechazados` puede existir aunque haya reconocimiento facial valido.
2. En la UI actual de recognize, ese arreglo no se visualiza directamente.

## 4.3 Verificacion en vivo (`POST /camera/verify-live`)

Objetivo: mostrar coincidencias faciales sin marcar asistencia.

Secuencia:

1. Frontend abre camara y detecta rostro localmente.
2. Envia lotes cortos `images[]` a Laravel.
3. Laravel valida (min 1, max 3, jpg/jpeg, 3 MB c/u).
4. Laravel reenvia a API externo `POST /verify`.
5. Laravel devuelve respuesta tal cual (proxy).

Diferencia clave con recognize:

1. No invoca `RegistrarEventoEmpleadoService`.
2. No escribe eventos de ingreso/salida.

## 5. Deteccion facial local y seleccion de imagenes

La deteccion se ejecuta en cliente (no en servidor web), con MediaPipe:

1. Reduce uso de red (solo se envia cuando hay cara util).
2. Permite filtrar imagenes borrosas/no centradas.
3. Mantiene baja latencia operativa.

Estrategia de envio (recognize):

1. Buffer corto de candidatos.
2. Dedupe por similitud de bounding boxes (IoU/centro/tiempo).
3. Limite por cantidad de imagenes por request.
4. Limite por tamano total aproximado de payload.

Objetivo de la estrategia:

1. Evitar saturar API externo.
2. Evitar enviar imagenes redundantes.
3. Sostener respuesta rapida en tiempo real.

## 6. Health-check, sesion y recuperacion ante falla

## 6.1 Health-check

Endpoint:

1. `GET /camera/health`

Comportamiento:

1. Frontend revisa salud periodicamente.
2. Si falla, marca estado `Offline`.
3. Reintenta automaticamente cada pocos segundos.
4. Al recuperar salud, vuelve a `Online` y reanuda envio.

## 6.2 Keepalive de sesion

Endpoint:

1. `GET /camera/session-keepalive`

Comportamiento:

1. En recognize se ejecuta cada 60s.
2. Si recibe 401/419, UI recarga por sesion expirada.

## 6.3 Caida del API externo

Comportamiento en frontend:

1. Cuenta fallos consecutivos de envio.
2. Al superar umbral, corta envio en vivo y entra en polling de salud.
3. Muestra mensajes de servicio fuera de linea.

Comportamiento en backend:

1. Si no logra contactar API externo, responde `503`.
2. Si API externo responde error HTTP, propaga codigo de estado.

## 7. Seguridad, acceso y permisos

Controles actuales:

1. Rutas bajo `auth`.
2. Middleware `deny.mobile` para bloquear flujo movil.
3. Permisos por accion:
   1. `biometria.gestion_camara.enroll`
   2. `biometria.gestion_camara.reconocer`
4. Header `X-API-Key` en llamadas al API facial externo.

Observacion relevante:

1. Existe middleware `DenyExternalCameraAccess` (control CIDR), pero no esta enlazado a las rutas activas del modulo al momento de esta guia.

## 8. Configuracion critica de entorno

Variables clave:

1. `CAMERA_SERVICE_URL`
2. `CAMERA_SERVICE_KEY`
3. `CAMERA_ALLOWED_CIDRS` (configurado, pero su middleware no esta aplicado en rutas activas)
4. `CAMERAS_JSON` (solo para flujos de camara IP)

Parametros tecnicos importantes:

1. Timeout backend a API externo:
   1. `connectTimeout(2)`
   2. `timeout(3)`
2. Validaciones de archivo:
   1. JPG/JPEG
   2. hasta 3 MB por archivo
   3. maximo de imagenes por endpoint segun caso

## 9. Observabilidad y diagnostico

## 9.1 En navegador (primera linea de soporte)

Revisar:

1. `Console` (errores JS, estado deteccion/camara).
2. `Network` (latencia/codigos en `/camera/*`).

Endpoints que mas valor dan en diagnostico:

1. `/camera/health`
2. `/camera/recognize-live`
3. `/camera/verify-live`
4. `/camera/enroll`
5. `/camera/session-keepalive`

## 9.2 En backend Laravel

Revisar:

1. `storage/logs/laravel-YYYY-MM-DD.log`
2. Logs de asistencia/huellero para `recognize-live` cuando falla registro de evento.

Mensajes tipicos:

1. "No se pudo contactar el servicio." (proxy/API externo no accesible)
2. "Servicio no disponible." (upstream devolvio error)
3. Errores de validacion de payload (cantidad/formato/peso de imagen)

## 10. Riesgos operativos y puntos de atencion

1. Dependencia multiple en `recognize-live`:
   1. camara del cliente
   2. deteccion local
   3. API facial externo
   4. backend Laravel
   5. DB de asistencia
2. Timeouts backend agresivos (2s/3s) pueden provocar falsos offline con latencia moderada.
3. Reconocimiento facial correcto no garantiza marca de asistencia (puede caer en `rechazados` por reglas de negocio).
4. Calidad de iluminacion/angulo influye directamente en tasa de acierto.

## 11. Checklist rapido para auditoria funcional

1. Ingresar a `/reconocimiento-facial`.
2. Validar que `health` responda OK.
3. Enrolar persona con 3 capturas.
4. Ejecutar recognize-live y confirmar evento en backend.
5. Ejecutar verify-live y confirmar coincidencia sin evento.
6. Simular caida de API y validar recuperacion automatica.

## 12. Glosario corto

1. Enrolamiento: alta de fotos/plantilla de rostro.
2. Recognize-live: reconocimiento con registro de asistencia.
3. Verify-live: reconocimiento solo informativo.
4. Upstream: servicio externo (API facial) que Laravel consume.
5. IoU: metrica de solapamiento de bounding boxes para deduplicar detecciones.
