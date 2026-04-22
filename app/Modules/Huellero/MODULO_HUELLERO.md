# Modulo Huellero

## Que hace

Este modulo cubre 4 cosas:

1. Enroll de huellas.
2. Verificacion de huella o identificacion.
3. Registro de ingreso y salida de empleados.
4. Registro de salida y regreso de descanso de conductores.

## Como funciona hoy

El flujo principal es este:

1. La huella se captura en el navegador, no en PHP.
2. La captura usa el SDK web de DigitalPersona.
3. Laravel recibe la muestra y la envia a un servicio externo de huellas.
4. Si la persona fue identificada, Laravel registra el evento localmente.

En corto:

- el navegador captura
- el servicio externo identifica
- Laravel decide y guarda

## Flujo real

```text
Navegador
  -> public/vendor/fingerprint/*
  -> public/js/fingerprint-client.js
  -> captura huella desde el lector local

Laravel
  -> POST /api/fingerprint/enroll
  -> POST /api/fingerprint/verify
  -> POST /api/fingerprint/verify-detailed
  -> App\Modules\Huellero\Http\Controllers\Api\FingerprintController

Servicio externo
  -> FINGERPRINT_SERVICE_URL
  -> responde si pudo identificar o enrolar

Registro local
  -> empleados: RegistrarEventoEmpleadoService + DecidirEventoService
  -> conductores: FingerprintController guarda directo en PRS_EVENTOS
```

## Pantallas

### Gestion Huellero

Ruta:

- `GET /gestion-huellero`

Funcion:

- muestra el menu del modulo
- cada opcion depende de permisos del usuario

Permisos principales:

- `biometria.gestion_huellero.acceso_personal`
- `biometria.gestion_huellero.ingreso_manual`
- `biometria.gestion_huellero.enroll`
- `biometria.gestion_huellero.verificar`
- `biometria.gestion_huellero.descanso_conductores`

### Enroll

Vista:

- `Resources/Views/fingerprint/enroll.blade.php`

Flujo:

1. Busca persona con `GET /fingerprint/personas`.
2. Selecciona el dedo.
3. Captura 4 muestras.
4. Envia a `POST /api/fingerprint/enroll`.
5. Laravel valida y reenvia al servicio externo.

Importante:

- el enroll actual no guarda directo en `PER_IDENTHUELLA`
- Laravel solo hace proxy al servicio externo

### Verify

Vista:

- `Resources/Views/fingerprint/verify.blade.php`

Flujo:

- por huella: `POST /api/fingerprint/verify-detailed`
- por identificacion: `POST /api/fingerprint/verify-detailed`

Sirve para consultar a la persona. No registra asistencia.

### Ingreso personal automatico

Vista:

- `Resources/Views/fingerprint/ingreso_personal.blade.php`

Flujo:

1. Captura huella.
2. Llama `POST /api/fingerprint/verify` con `tipo = 1`.
3. Si hay identificacion valida, llama `POST /fingerprint/eventos/empleados`.
4. El registro real lo hace `RegistrarEventoEmpleadoService`.

La misma vista tambien:

- muestra ultimos registros
- deja buscar registros del dia
- evita capturas repetidas muy seguidas
- traduce varios rechazos a mensajes mas claros

### Ingreso personal manual

Usa la misma vista de ingreso personal.

Diferencia:

- el operador elige si va a guardar entrada o salida
- puede enviar fecha manual
- termina en el mismo `POST /fingerprint/eventos/empleados`

### Descanso conductores

Vista:

- `Resources/Views/fingerprint/descanso_conductores.blade.php`

Flujo:

1. Captura huella.
2. Llama `POST /api/fingerprint/verify` con `tipo = 2`.
3. Si identifica al conductor, llama `POST /fingerprint/eventos/conductores`.
4. El controlador guarda directo en `PRS_EVENTOS`.

Codigos:

- `3`: salida a descanso
- `4`: regreso de descanso

Este flujo no usa `DecidirEventoService`.

## Logica de empleados

Archivos clave:

- `app/Modules/Huellero/Services/RegistrarEventoEmpleadoService.php`
- `app/Services/Asistencia/DecidirEventoService.php`

### Que hace el orquestador

`RegistrarEventoEmpleadoService`:

- busca a la persona
- valida contrato activo
- resuelve cargo
- si el evento es automatico, consulta `DecidirEventoService`
- guarda el evento en `PRS_EVENTOS`
- replica en `PER_PERSONASEVENTOS`
- intenta crear notificacion interna

### Que decide `DecidirEventoService`

Decide ingreso o salida segun:

- cargo
- horario aplicable
- eventos ya registrados hoy
- si hay ingreso abierto
- si ya paso la hora de salida
- si la marca cuenta como llegada tarde

Reglas visibles:

- ingreso permitido desde 15 minutos antes de `hora_inicio`
- llegada tarde desde 5 minutos despues de `hora_inicio`
- salida solo si existe ingreso abierto y ya paso `hora_fin`

### Cargo especial

En el codigo actual, el cargo especial es:

- `cargo_id = 3`

Ese cargo tiene reglas extra:

- un ingreso por dia
- una salida por dia
- bloqueo de reingreso por 7 horas

### Registro manual

Si el request ya trae `evento = 1` o `evento = 2`:

- no se usa la decision automatica de horarios
- se guarda el tipo enviado
- igual se exige persona valida y contrato activo

## Tablas y conexiones

Conexiones usadas:

- `oracle`
- `oracle-360`
- `oracle-pruebas`
- `mysql-gestion-admin`

Tablas clave:

- `PER_CONTRATO_PERSONA`
- `PER_EMPRESAPERSONAS`
- `PER_PERSONAS`
- `PRS_EVENTOS`
- `PRS_HORARIOS_CARGOS`
- `PRS_CARGOS`
- `PRS_PERSONAS`
- `PER_PERSONASEVENTOS`
- `_notificaciones`

Codigos de evento:

- `PRS_EVENTOS`
  - `1`: salida empleado
  - `2`: ingreso empleado
  - `3`: salida a descanso
  - `4`: regreso de descanso
- `PER_PERSONASEVENTOS`
  - `49`: entrada
  - `50`: salida

## Rutas importantes

Web:

- `GET /gestion-huellero`
- `GET /fingerprint/enroll`
- `GET /fingerprint/verify`
- `GET /fingerprint/eventos/empleados`
- `GET /fingerprint/eventos/empleados/manual`
- `POST /fingerprint/eventos/empleados`
- `GET /fingerprint/eventos/empleados/ultimo`
- `GET /fingerprint/eventos/empleados/ultimos`
- `GET /fingerprint/eventos/empleados/hoy`
- `GET /fingerprint/eventos/conductores`
- `POST /fingerprint/eventos/conductores`
- `GET /fingerprint/eventos/conductores/ultimo`
- `GET /fingerprint/personas`

Proxy biometrico:

- `POST /api/fingerprint/enroll`
- `POST /api/fingerprint/verify`
- `POST /api/fingerprint/verify-detailed`

## Servicio externo de huella

Variables clave:

- `FINGERPRINT_SERVICE_URL`
- `FINGERPRINT_SERVICE_KEY`
- `HUELLA_API_MODE`

Modos:

- `mock`
  - no consulta servicio real
- `java`
  - usa el servicio configurado

Endpoints esperados:

- `/enroll`
- `/verify`
- `/verifyDetailed`

## Archivos que conviene leer primero

- `Http/Controllers/FingerprintController.php`
- `Http/Controllers/Api/FingerprintController.php`
- `Services/RegistrarEventoEmpleadoService.php`
- `app/Services/Asistencia/DecidirEventoService.php`
- `Resources/Views/fingerprint/enroll.blade.php`
- `Resources/Views/fingerprint/verify.blade.php`
- `Resources/Views/fingerprint/ingreso_personal.blade.php`
- `Resources/Views/fingerprint/descanso_conductores.blade.php`
- `public/js/fingerprint-client.js`

## Despliegue

### En el servidor

Debe existir:

- Laravel funcionando
- acceso a `public/`
- assets publicados
- conexion a las bases externas
- variables de entorno correctas

Minimo a revisar:

- `FINGERPRINT_SERVICE_URL`
- `FINGERPRINT_SERVICE_KEY`
- `HUELLA_API_MODE`
- conexiones de `oracle`, `oracle-360`, `oracle-pruebas`, `mysql-gestion-admin`

Importante:

- este modulo no vive solo con migraciones locales
- depende de tablas existentes fuera del repo

### En el equipo cliente

Cada equipo que capture huella necesita:

- lector DigitalPersona conectado
- WebSDK funcionando
- sesion iniciada en el sistema
- permisos del modulo

Si falla el lector en ese equipo, Laravel puede estar bien y la captura igual va a fallar.

### Assets a verificar

- `public/vendor/fingerprint/es6-shim.js`
- `public/vendor/fingerprint/websdk.client.bundle.min.js`
- `public/vendor/fingerprint/fingerprint.sdk.min.js`
- `public/js/fingerprint-client.js`

Si compilas assets:

```powershell
npm run build
```

### Smoke test rapido

1. Entrar a `/gestion-huellero`.
2. Abrir `Enroll` y validar lector.
3. Abrir `Verify` y probar por identificacion.
4. Abrir ingreso automatico y hacer una marca.
5. Confirmar registro en `PRS_EVENTOS`.
6. Para empleados, confirmar replica en `PER_PERSONASEVENTOS`.

## Errores y soporte

### Lo que se ve en pantalla

Mensajes comunes:

- no se detectaron lectores
- fallo la comunicacion con el servicio WebAPI
- error del lector
- muestra rechazada por calidad
- no se pudo validar la huella
- fuera de horarios
- aun no puede salir
- ya existe ingreso o salida registrada

### Logs del modulo

Revisar:

- `storage/logs/huellero/huellero-YYYY-MM-DD.log`
- `storage/logs/huellero/asistencia_decisiones-YYYY-MM-DD.log`

Que guarda cada uno:

- `huellero-*.log`
  - fallas del proxy HTTP
  - validaciones
  - errores al guardar
  - errores de notificaciones
- `asistencia_decisiones-*.log`
  - por que se acepto o rechazo una marca
  - horarios evaluados
  - evento decidido

Comandos utiles:

```powershell
Get-ChildItem storage\logs\huellero
```

```powershell
Get-Content storage\logs\huellero\huellero-2026-03-04.log -Wait
```

```powershell
Get-Content storage\logs\huellero\asistencia_decisiones-2026-03-06.log -Wait
```

### Logs generales

Si no aparece nada ahi, revisar:

- `storage/logs/laravel.log`
- `storage/logs/laravel-YYYY-MM-DD.log`

### Navegador

Revisar DevTools:

- `Console`
- `Network`

Requests clave:

- `POST /api/fingerprint/verify`
- `POST /api/fingerprint/enroll`
- `POST /api/fingerprint/verify-detailed`
- `POST /fingerprint/eventos/empleados`
- `POST /fingerprint/eventos/conductores`

## Cosas a tener en cuenta

### Depende de varios sistemas

Para que funcione completo deben estar bien:

- lector del cliente
- SDK web
- servicio externo de huellas
- bases Oracle y MySQL del flujo

### El registro de empleados toca varias bases

Puede escribir en:

- `oracle-360`
- `oracle-pruebas`
- `mysql-gestion-admin`

No todo queda dentro de una sola transaccion real.

### Las notificaciones no bloquean el evento principal

Si falla la notificacion:

- el evento puede quedar guardado igual
- el error queda en logs

### El mensaje "Payload guardado" es enganoso

En algunas vistas aparece:

- `No se pudo contactar el servicio. Payload guardado.`

Pero hoy el codigo no guarda ese payload en `localStorage`.

En la practica significa:

- fallo la llamada
- toca reintentar

## Flujo heredado

Todavia existen estas piezas:

- `HuellaService`
- `EventoService`
- `VerificacionController`
- `DigitalPersonaService`
- rutas `api/huellero/*`

No son el camino principal que usan las pantallas actuales.

## Riesgos conocidos

### Registro manual de empleados

Hoy el flujo manual termina guardando `tiporegistro = 0` en `PER_PERSONASEVENTOS`.

Eso hace que en esa tabla no quede claro que fue manual.

### Diferencia entre codigo y pruebas del cargo especial

Al correr el 2026-03-12:

```powershell
php artisan test --filter=DecidirEventoServiceTest
```

el resultado fue:

- 11 pruebas OK
- 2 pruebas fallidas

La diferencia detectada fue:

- el codigo usa `cargo_id = 3` como cargo especial
- algunas pruebas viejas siguen esperando ese comportamiento para `cargo_id = 8`

Antes de tocar reglas de horario, conviene revisar eso primero.

## Resumen rapido

1. La huella se captura en el navegador.
2. Laravel la envia a un servicio externo.
3. Si hay identificacion valida, Laravel registra el evento.
4. Empleados usan reglas de horario locales.
5. Conductores usan un flujo mas simple.
6. Para soporte, los logs mas utiles son `huellero-*.log` y `asistencia_decisiones-*.log`.
