# Modulo Camara

## Que hace

Este modulo cubre 4 cosas:

1. Registrar rostros.
2. Reconocer personas en vivo para ingreso y salida de personal.
3. Verificar identidad en vivo sin registrar asistencia.
4. Consultar ultimos registros y registros del dia.

## Como funciona hoy

El flujo principal es este:

1. El navegador abre la camara.
2. El JS detecta el rostro localmente y recorta frames utiles.
3. Laravel recibe esos JPG y los reenvia a un servicio facial externo.
4. Si el flujo es `recognize-live`, Laravel intenta registrar el evento local.
5. Si el flujo es `verify-live` o `enroll`, Laravel solo hace proxy y devuelve la respuesta.

En corto:

- el navegador captura
- Laravel hace proxy
- el servicio externo reconoce o enrola
- solo `recognize-live` registra asistencia

## Flujo real

```text
Navegador
  -> resources/js/camara/enroll.js
  -> resources/js/camara/recognize.js
  -> resources/js/camara/verify.js
  -> getUserMedia + deteccion facial local

Laravel
  -> app/Modules/Camara/Http/Controllers/Api/CamaraApiController.php
  -> app/Modules/Camara/Services/CameraService.php

Servicio externo
  -> CAMERA_SERVICE_URL
  -> /health
  -> /enroll
  -> /recognize
  -> /verify

Registro local de asistencia
  -> solo en POST /camera/recognize-live
  -> App\Modules\Huellero\Services\RegistrarEventoEmpleadoService
  -> App\Services\Asistencia\DecidirEventoService
```

## Pantallas

### Menu del modulo

Ruta:

- `GET /reconocimiento-facial`

Opciones visibles:

- `Registrar rostro`
- `Ingreso/Salida Personal`
- `Validar persona`

Permisos principales:

- `biometria.gestion_camara.enroll`
- `biometria.gestion_camara.reconocer`

Las rutas del modulo estan protegidas por:

- `auth`
- `deny.mobile`

Eso significa que el flujo esta pensado para usuarios autenticados en escritorio, no para acceso movil.

### Registrar rostro

Vista:

- `app/Modules/Camara/Resources/Views/camara/enroll.blade.php`

JS principal:

- `resources/js/camara/enroll.js`

Flujo:

1. El operador busca la persona con `GET /camera/personas`.
2. La busqueda solo trae personas activas.
3. El navegador abre la camara.
4. El JS pide 3 capturas guiadas:
   - frente
   - perfil izquierdo
   - perfil derecho
5. Cuando las 3 capturas estan listas, envia `POST /camera/enroll`.
6. Laravel valida y reenvia al servicio externo `/enroll`.

Validaciones backend:

- exactamente 3 imagenes
- tipo `jpg` o `jpeg`
- maximo 3 MB por archivo
- `identificacion` obligatoria

Importante:

- el enroll no guarda una plantilla facial en una tabla local del proyecto
- hoy Laravel solo hace proxy al servicio facial externo
- si el servicio tarda mas de lo normal, el backend corta rapido: `connectTimeout(2)` y `timeout(3)`

La busqueda de personas usa:

- `PerPersonas`
- `tipdocumento = 1`
- `estado = ACTIVO`
- `estborrado = 0`
- limite de 20 resultados

### Ingreso/Salida Personal

Vista:

- `app/Modules/Camara/Resources/Views/camara/recognize.blade.php`

JS principal:

- `resources/js/camara/recognize.js`

Flujo:

1. El navegador abre la camara.
2. El JS detecta caras localmente.
3. Recorta frames utiles y arma lotes pequenos.
4. Envia `POST /camera/recognize-live`.
5. Laravel reenvia esas imagenes al servicio externo `/recognize`.
6. Si el servicio devuelve personas reconocidas, Laravel intenta registrar asistencia para cada una.

Detalles utiles:

- el frontend captura en ciclos y envia de forma adaptativa, normalmente cerca de cada 1 a 1.5 segundos
- por request no envia un volumen grande de imagenes
- el frontend evita duplicados cercanos y limpia backlog viejo
- mientras la vista esta abierta hace `GET /camera/session-keepalive` cada 60 segundos para extender la sesion

Que pasa cuando una persona es reconocida:

1. Laravel toma la `identificacion`.
2. Ignora duplicados repetidos dentro del mismo payload.
3. Llama `RegistrarEventoEmpleadoService->registrar(..., 'camara')`.
4. Ese servicio usa la misma logica de horarios y decision que Huellero para empleados.
5. Si todo sale bien, la UI muestra la persona con evento, fecha y hora.

Esto es importante:

- Camara no tiene una logica propia de horarios
- la decision de `ingreso` o `salida` la hereda del mismo servicio compartido con Huellero
- si cambias `RegistrarEventoEmpleadoService` o `DecidirEventoService`, cambias tambien el comportamiento de Camara

Codigos de evento usados en la respuesta:

- `2`: ingreso
- `1`: salida

Consultas auxiliares de esta pantalla:

- `GET /camera/ultimos-eventos`
- `GET /camera/eventos-hoy?identificacion=...`

Esas consultas leen en `oracle-360`:

- `PRS_EVENTOS`
- `PRS_PERSONAS`

Importante:

- si el backend rechaza registrar una persona, `recognize-live` puede devolverla en `rechazados`
- la UI actual no muestra ese arreglo
- o sea: puede haber reconocimiento facial valido, pero sin registro efectivo, y el operador no ver el motivo en pantalla

### Validar persona

Vista:

- `app/Modules/Camara/Resources/Views/camara/verify.blade.php`

JS principal:

- `resources/js/camara/verify.js`

Flujo:

1. La vista inicia la camara automaticamente.
2. Detecta caras y envia `POST /camera/verify-live`.
3. Laravel reenvia al servicio externo `/verify`.
4. La UI muestra coincidencias recientes.

Este flujo:

- no registra asistencia
- no escribe en `PRS_EVENTOS`
- no usa `RegistrarEventoEmpleadoService`

## Estructura que conviene leer primero

Dentro del modulo:

- `Http/Controllers/CamaraController.php`
- `Http/Controllers/Api/CamaraApiController.php`
- `Http/Requests/EnrollRequest.php`
- `Http/Requests/RecognizeLiveRequest.php`
- `Http/Requests/RecognizeRequest.php`
- `Http/Requests/VerifyLiveRequest.php`
- `Services/CameraService.php`
- `Resources/Views/camara/index.blade.php`
- `Resources/Views/camara/enroll.blade.php`
- `Resources/Views/camara/recognize.blade.php`
- `Resources/Views/camara/verify.blade.php`
- `Routes/web.php`

Fuera del modulo, pero clave para entenderlo:

- `resources/js/camara/enroll.js`
- `resources/js/camara/recognize.js`
- `resources/js/camara/verify.js`
- `app/Modules/Huellero/Services/RegistrarEventoEmpleadoService.php`
- `app/Services/Asistencia/DecidirEventoService.php`

## Rutas importantes

Pantallas:

- `GET /reconocimiento-facial`
- `GET /face-enroll`
- `GET /face-recognize`
- `GET /face-verify`

Rutas que usa la UI actual:

- `GET /camera/health`
- `GET /camera/session-keepalive`
- `GET /camera/personas`
- `POST /camera/enroll`
- `POST /camera/recognize-live`
- `GET /camera/ultimos-eventos`
- `GET /camera/eventos-hoy`
- `POST /camera/verify-live`

Rutas simples heredadas:

- `GET /health`
- `POST /recognize`
- `POST /enroll`

Esas rutas existen, pero las pantallas actuales trabajan principalmente con `/camera/*`.

## Servicio externo de camara

Variables clave:

- `CAMERA_SERVICE_URL`
- `CAMERA_SERVICE_KEY`
- `CAMERA_ALLOWED_CIDRS`

Header usado:

- `X-API-Key`

Endpoints esperados del servicio facial:

- `/health`
- `/enroll`
- `/recognize`
- `/verify`

Importante:

- `CameraService` toma la URL y la llave desde `config/camera.php` y `config/services.php`
- hoy la restriccion por CIDR existe en `DenyExternalCameraAccess`, pero esa middleware no esta conectada a las rutas activas del modulo
- en la practica, `CAMERA_ALLOWED_CIDRS` no esta protegiendo este flujo hoy

## Despliegue

### En el servidor

Debe existir:

- Laravel funcionando
- autenticacion operativa
- permisos del modulo cargados
- servicio facial accesible desde el servidor web
- conexiones a BD del flujo de asistencia
- assets de Vite compilados

Minimo a revisar:

- `CAMERA_SERVICE_URL`
- `CAMERA_SERVICE_KEY`
- acceso HTTP desde PHP hacia `CAMERA_SERVICE_URL`
- tablas de asistencia accesibles si se va a usar `recognize-live`

Como el modulo usa `@vite`, si despliegas cambios frontend conviene validar:

```powershell
npm run build
```

Si cambias variables de entorno:

```powershell
php artisan optimize:clear
```

Smoke test rapido:

1. Entrar a `/reconocimiento-facial`.
2. Abrir `Registrar rostro`.
3. Confirmar que `GET /camera/health` responda bien.
4. Buscar una persona y completar las 3 capturas.
5. Abrir `Ingreso/Salida Personal`.
6. Hacer una marca y confirmar registro en `PRS_EVENTOS`.
7. Abrir `Validar persona` y confirmar que reconoce sin registrar asistencia.

### En el equipo cliente

Cada equipo necesita:

- navegador con permisos de camara
- sesion iniciada
- permisos del modulo
- buena iluminacion
- camara funcional

Si la camara falla en ese equipo:

- Laravel puede estar sano
- el servicio facial puede estar sano
- y aun asi el flujo falla por `getUserMedia`

## Errores y soporte

### Lo que se ve en pantalla

Mensajes comunes:

- `No se pudo acceder a la camara`
- `No se pudo inicializar la deteccion facial`
- `Servicio fuera de linea`
- `Servicio restaurado`
- `La sesion expiro. Recargando...`
- `Selecciona una identificacion antes de iniciar la camara`
- `Alinea tu rostro antes de capturar este paso`
- `No se pudo enrolar. Intentalo de nuevo`

Indicadores visuales utiles:

- badge de camara
- badge de deteccion de cara
- badge `Online` o `Offline`
- lista de personas reconocidas

### DevTools del navegador

Revisar `Console` y `Network`.

Requests mas importantes:

- `GET /camera/health`
- `GET /camera/session-keepalive`
- `GET /camera/personas`
- `POST /camera/enroll`
- `POST /camera/recognize-live`
- `POST /camera/verify-live`
- `GET /camera/ultimos-eventos`
- `GET /camera/eventos-hoy`

Pistas rapidas:

- si falla `GET /camera/health`, el problema suele ser conectividad hacia el servicio facial o variables de entorno
- si `POST /camera/recognize-live` responde `503`, Laravel no pudo contactar el servicio facial
- si `POST /camera/recognize-live` responde `200` pero no se registra la marca, revisa la respuesta JSON y los logs del servicio compartido
- si la vista se recarga sola con mensaje de sesion vencida, el problema es sesion o autenticacion, no reconocimiento facial

### Logs del backend

Para errores propios del proxy Camara, revisar primero:

- `storage/logs/laravel.log`
- `storage/logs/laravel-YYYY-MM-DD.log`

Hoy `CamaraApiController` escribe logs explicitos sobre todo en enroll:

- `Camara enroll: no se pudo contactar el servicio`
- `Camara enroll: API respondio error`

Para decisiones de asistencia del flujo `recognize-live`, revisar tambien:

- `storage/logs/huellero/huellero-YYYY-MM-DD.log`
- `storage/logs/huellero/asistencia_decisiones-YYYY-MM-DD.log`

Eso pasa porque Camara reutiliza `RegistrarEventoEmpleadoService`, y ese servicio sigue logueando en el arbol de Huellero aunque el origen sea `camara`.

Comandos utiles:

```powershell
Get-Content storage\logs\laravel.log -Wait
```

```powershell
Get-Content storage\logs\huellero\huellero-2026-03-12.log -Wait
```

```powershell
Get-Content storage\logs\huellero\asistencia_decisiones-2026-03-12.log -Wait
```

### Logs del servicio facial

Si Laravel responde `503` o `Offline` y en `laravel.log` no hay mucho detalle, toca revisar el servicio facial externo.

Ese log no vive en este repo.

## Cosas a tener en cuenta

### `recognize-live` depende de varios sistemas

Para que funcione bien deben estar sanos al mismo tiempo:

- navegador y camara
- JS de deteccion facial
- servicio facial externo
- autenticacion y sesion Laravel
- bases de datos del flujo de asistencia

### Camara comparte el registro de asistencia con Huellero

Esto implica:

- mismas reglas de horarios
- mismos rechazos por contrato, horario o eventos previos
- mismos logs de decisiones

Si el usuario dice "la camara reconocio pero no dejo marcar", el problema puede estar en la logica compartida y no en la deteccion facial.

### El timeout del backend es corto

`CameraService` usa:

- `connectTimeout(2)`
- `timeout(3)`

Si el servicio facial esta lento, el sistema lo va a marcar como caido rapido.

### La restriccion por red no esta aplicada en rutas activas

Existe `DenyExternalCameraAccess`, pero no aparece conectado en `app/Modules/Camara/Routes/web.php`.

Hoy el control efectivo de acceso es:

- autenticacion
- permisos
- `deny.mobile`

### Hay rutas heredadas que no son el camino principal

Todavia existen:

- `GET /health`
- `POST /recognize`
- `POST /enroll`

Pero la UI actual usa `/camera/*`.

## Resumen rapido

1. El navegador captura rostro y filtra frames.
2. Laravel reenvia esas imagenes al servicio facial.
3. `enroll` registra rostro en el servicio externo.
4. `verify-live` solo valida identidad.
5. `recognize-live` ademas registra asistencia con la misma logica de Huellero para empleados.
6. Para soporte, los puntos mas utiles son `Network`, `laravel.log` y los logs de `storage/logs/huellero`.
