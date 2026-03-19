# Guia del proyecto autogestion2

## Resumen

Este repositorio contiene una aplicacion Laravel 12 orientada a autogestion interna, con arquitectura modular en `app/Modules` y varias integraciones con bases de datos legadas y servicios externos.

No es un proyecto completamente autocontenido: parte importante de la logica consulta conexiones MySQL, Oracle y SQL Server ya existentes, ademas de servicios para huellero y camara.

## Tecnologias principales

- Backend: PHP 8.2, Laravel 12, Composer, Eloquent, middlewares y comandos Artisan.
- Frontend: Blade, Vite 6, Tailwind CSS 3, Bootstrap 5, jQuery.
- Librerias UI y utilidades: Select2, SweetAlert2, Bootstrap Table, Bootstrap Icons, XLSX.
- Integraciones backend: Guzzle, Spatie Laravel Permission, DOMPDF, QR Code, no-captcha.
- Bases de datos soportadas en el proyecto: MySQL/MariaDB, Oracle y SQL Server.
- Integraciones biometrica/vision: DigitalPersona / fingerprint API y `@mediapipe/face_detection`.
- Pruebas: PHPUnit 11.

Referencia del entorno actual del workspace:

- PHP `8.2.12`
- Laravel `12.47.0`
- Node `22.14.0`
- npm `10.9.2`

## Requisitos para levantarlo

- PHP 8.2 o superior.
- Composer.
- Node.js y npm compatibles con Vite 6.
- Una base de datos MySQL/MariaDB para la conexion principal.
- Drivers/extensiones segun los modulos que vayas a usar:
  - `pdo_mysql` para MySQL/MariaDB
  - Oracle OCI8/PDO OCI para conexiones Oracle usadas por `yajra/laravel-oci8`
  - `sqlsrv` / `pdo_sqlsrv` para modulos que consultan SQL Server
- Servidor web apuntando a `public/` si se ejecuta con Apache/Nginx/XAMPP.

## Instalacion

1. Instalar dependencias PHP:

```powershell
composer install
```

2. Instalar dependencias frontend:

```powershell
npm install
```

3. Crear el archivo de entorno a partir del ejemplo:

```powershell
copy .env.example .env
```

4. Configurar `.env`.

Variables minimas a revisar:

- `APP_NAME`
- `APP_ENV`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Nota: el `.env.example` no incluye todas las variables de conexiones secundarias (`DB_HOST_2`, `DB_DATABASE_4`, `DB_USERNAME_7`, etc.). Si vas a usar modulos que dependen de esas conexiones, debes agregarlas manualmente en tu `.env`.

Variables de servicios externos usadas por modulos especificos:

- `FINGERPRINT_SERVICE_URL`
- `FINGERPRINT_SERVICE_KEY`
- `HUELLA_API_MODE`
- `CAMERA_SERVICE_URL`
- `CAMERA_SERVICE_KEY`
- `CAMERA_ALLOWED_CIDRS`

Variables operativas importantes:

- `SESSION_DRIVER`
- `QUEUE_CONNECTION`
- `CACHE_STORE`

5. Generar la llave de aplicacion si no existe:

```powershell
php artisan key:generate
```

6. Ejecutar migraciones:

```powershell
php artisan migrate
```

### Nota sobre base de datos y migraciones

El codigo usa varias conexiones ademas de la principal. En `config/database.php` estan definidas, entre otras:

- `mysql-gestion-admin`
- `mysql-gestion-humana`
- `mysql-gestion-pasajes`
- `oracle`
- `oracle-360`
- `oracle-pruebas`
- `sqlsrv`
- `sqlsrv-lectura`

Varios modelos y servicios apuntan directamente a esas conexiones, por lo que un arranque funcional completo requiere acceso a esas bases segun el modulo que se quiera usar. Incluso la autenticacion base del proyecto usa la conexion `mysql-gestion-admin`.

Ademas, el repositorio solo incluye una parte de las migraciones locales:

- `database/migrations/*`
- migraciones del modulo `RadFact`

Si en local vas a usar `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` o `CACHE_STORE=database`, asegurate de que esas tablas existan en tu base. Si no existen, tienes dos opciones:

- cambiar temporalmente a `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync` y `CACHE_STORE=file`
- o generar las migraciones/tablas necesarias antes de ejecutar el flujo completo

## Ejecucion en desarrollo

La forma mas simple es usar el script de Composer:

```powershell
composer dev
```

Ese comando levanta:

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `npm run dev`

Si prefieres separarlo por terminales:

```powershell
php artisan serve --port=8080
```

```powershell
php artisan queue:listen --tries=1
```

```powershell
npm run dev
```

### Nota sobre puertos

En `.env.example`, `CAMERA_SERVICE_URL` apunta a `http://127.0.0.1:8000`. Si tambien ejecutas Laravel en el puerto `8000`, tendras conflicto si ese servicio de camara esta en la misma maquina. En ese caso:

- corre Laravel en otro puerto, por ejemplo `8080`
- o sirve la aplicacion mediante Apache/XAMPP y deja `8000` para el servicio externo

## Ejecucion con Apache / XAMPP

Como el proyecto esta ubicado bajo `C:\xampp\htdocs\gestion\autogestion2`, puedes servirlo con Apache apuntando el `DocumentRoot` a la carpeta `public/`.

Puntos a revisar:

- `APP_URL` debe coincidir con la URL local real.
- Si usas assets compilados con `npm run build`, revisa `vite.config.js`.
- Actualmente el `base` de produccion esta configurado como `/gestion360/public/build/`.
- En el mismo archivo existe una ruta comentada de prueba: `/gestion/autogestion2/public/build/`.

Si los assets no cargan correctamente en local, ajusta `base` para que coincida con tu ruta publica real.

Compilacion de assets para ese escenario:

```powershell
npm run build
```

## Estructura del proyecto

### Raiz

- `app/`: codigo principal de la aplicacion.
- `bootstrap/`: arranque de Laravel y registro de providers.
- `config/`: configuraciones del framework, conexiones y servicios.
- `database/`: migraciones, factories y seeders globales.
- `public/`: punto de entrada web y archivos publicos.
- `resources/`: CSS, JS y vistas compartidas.
- `routes/`: rutas globales web, api, auth y consola.
- `storage/`: logs, cache y archivos generados.
- `tests/`: pruebas Feature y Unit.

### Estructura modular

El proyecto usa `app/Modules` como contenedor de dominios funcionales. El provider `App\Providers\ModuleServiceProvider` autodetecta cada modulo y registra:

- vistas
- rutas web
- migraciones del modulo
- comandos Artisan del modulo

Modulos detectados en el repositorio:

- `Administration`
- `Camara`
- `Configuracion`
- `GestionRRHH`
- `GestionWeb`
- `Huellero`
- `PagosRecaudos`
- `RadFact`
- `SIG`

Cada modulo sigue, en general, esta convencion:

- `Http/Controllers`
- `Models`
- `Services`
- `Routes/web.php`
- `Resources/Views`
- `Console/Commands` cuando aplica
- `Database/Migrations` cuando aplica

### Carpetas compartidas relevantes

- `app/Http/Middleware`: middlewares globales como control de caja activa, acceso movil y estado de modulos.
- `app/Services`: servicios transversales no acoplados a un modulo.
- `resources/js`: scripts frontend cargados por Vite.
- `resources/css`: estilos globales.
- `resources/views`: vistas compartidas fuera de los modulos.

## Servicios e integraciones a tener en cuenta

- Huellero / fingerprint: configurado mediante `FINGERPRINT_SERVICE_URL` y `HUELLA_API_MODE`.
- Camara: configurada mediante `CAMERA_SERVICE_URL`, `CAMERA_SERVICE_KEY` y redes permitidas.
- El build frontend copia assets de MediaPipe al directorio `public/` desde `node_modules` durante la carga de `vite.config.js`.

## Comandos utiles

Ejecutar pruebas:

```powershell
php artisan test
```

Ejecutar una prueba puntual:

```powershell
php artisan test --filter=DecidirEventoServiceTest
```

Ver comandos disponibles, incluidos los de modulos:

```powershell
php artisan list
```

## Resumen practico

Para un arranque local minimo:

1. `composer install`
2. `npm install`
3. crear `.env`
4. configurar `APP_URL` y la base principal
5. usar `SESSION_DRIVER=file`, `CACHE_STORE=file` y `QUEUE_CONNECTION=sync` si no tienes tablas/infraestructura de cola
6. `php artisan key:generate`
7. `php artisan migrate`
8. `composer dev` o Apache + `npm run build`

Para un arranque funcional completo de todos los modulos, ademas necesitas acceso a las conexiones MySQL/Oracle/SQL Server y a los servicios externos usados por la organizacion.
