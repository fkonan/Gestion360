# Autogestion2 - Instrucciones para Claude Code

## Stack Tecnológico

### Backend
- **PHP**: 8.2.2
- **Laravel**: 12 (estructura streamlined desde Laravel 11)
- **Base de datos**: MySQL (múltiples conexiones: mysql-gestion-admin, oracle, etc.)

### Frontend
- **AdminLTE**: 3.2 (basado en Bootstrap 5)
- **Bootstrap**: 5.x (NO usar Tailwind)
- **Font Awesome**: 5.15.4 (iconos con prefijo `fas`, `far`, `fab`)
- **Select2**: Para selects avanzados
- **Vite**: Para bundling de assets

### Testing
- **PHPUnit**: v11 (NO usar Pest)

---

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Huellero/
│   │   ├── RadFact/          # Módulo de Radicación de Facturas
│   │   └── ...
│   └── Requests/             # Form Requests para validación
├── Models/
│   ├── GESTIONADMIN/         # Modelos de gestión administrativa
│   ├── GESTIONHUMANA/        # Modelos de gestión humana
│   ├── LOGTRANS/             # Modelos de transporte
│   ├── RADFACT/              # Modelos de radicación de facturas
│   └── User.php
└── Services/                 # Lógica de negocio

resources/views/
├── components/               # Componentes Blade reutilizables
├── layouts/
│   ├── app.blade.php         # Layout principal (AdminLTE)
│   ├── dashboard.blade.php   # Layout con sidebar
│   └── notLogin.blade.php    # Layout sin autenticación
├── radfact/                  # Vistas del módulo RadFact
└── ...
```

---

## Convenciones de Código

### PHP / Laravel

```php
// ✅ BIEN - Usar Form Requests para validación
public function store(StoreProveedorRequest $request)

// ❌ MAL - Validación inline en controlador
public function store(Request $request)
{
    $request->validate([...]); // Evitar esto
}
```

```php
// ✅ BIEN - Type hints y return types
public function obtenerProveedor(int $id): ?RadFactProveedor

// ❌ MAL - Sin tipos
public function obtenerProveedor($id)
```

```php
// ✅ BIEN - Usar casts() method en modelos (Laravel 11+)
protected function casts(): array
{
    return ['activo' => 'boolean'];
}

// ❌ EVITAR - Propiedad $casts (legacy)
protected $casts = ['activo' => 'boolean'];
```

```php
// ✅ BIEN - Eager loading para evitar N+1
$radicaciones = RadFactRadicacion::with(['proveedor', 'distribuciones'])->get();

// ❌ MAL - Queries N+1
foreach ($radicaciones as $rad) {
    echo $rad->proveedor->nombre; // Query por cada iteración
}
```

### Modelos - Primary Key especial

```php
// El modelo User usa IdUsuario como PK, no id
// Al relacionar con User:
public function usuario(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id', 'IdUsuario');
}
```

---

## Frontend - Bootstrap 5 + AdminLTE

### Clases Bootstrap a usar

```html
<!-- Contenedores -->
<div class="container-fluid">
<div class="row">
<div class="col-md-6 col-lg-4">

<!-- Cards (estilo AdminLTE) -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0">Título</h5>
    </div>
    <div class="card-body">
        Contenido
    </div>
    <div class="card-footer">
        <button class="btn btn-primary">Guardar</button>
    </div>
</div>

<!-- Formularios -->
<div class="mb-3">
    <label for="campo" class="form-label">Label *</label>
    <input type="text" class="form-control" id="campo" name="campo" required>
    <span class="text-danger" id="error-campo"></span>
</div>

<div class="mb-3">
    <label for="select" class="form-label">Select</label>
    <select class="form-select select2" id="select" name="select">
        <option value="">Seleccione...</option>
    </select>
</div>

<!-- Botones -->
<button type="submit" class="btn btn-success">Guardar</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<a href="{{ route('ruta.index') }}" class="btn btn-outline-dark">Volver</a>

<!-- Tablas -->
<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th>Columna</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Valor</td>
        </tr>
    </tbody>
</table>

<!-- Badges/Estados -->
<span class="badge bg-success">Aprobado</span>
<span class="badge bg-warning text-dark">Pendiente</span>
<span class="badge bg-danger">Rechazado</span>

<!-- Alertas -->
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Mensaje exitoso
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Modales -->
<div class="modal fade" id="miModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Título</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Contenido
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
```

### Iconos - Font Awesome 5

```html
<!-- Iconos comunes -->
<i class="fas fa-plus"></i>           <!-- Agregar -->
<i class="fas fa-edit"></i>           <!-- Editar -->
<i class="fas fa-trash"></i>          <!-- Eliminar -->
<i class="fas fa-eye"></i>            <!-- Ver -->
<i class="fas fa-check"></i>          <!-- Aprobar -->
<i class="fas fa-times"></i>          <!-- Rechazar/Cerrar -->
<i class="fas fa-search"></i>         <!-- Buscar -->
<i class="fas fa-file-pdf"></i>       <!-- PDF -->
<i class="fas fa-download"></i>       <!-- Descargar -->
<i class="fas fa-spinner fa-spin"></i> <!-- Loading -->
```

### Dark Mode

El proyecto soporta dark mode con `[data-bs-theme="dark"]`:

```css
/* Estilos normales */
.mi-clase {
    background: #ffffff;
    color: #0f172a;
}

/* Dark mode */
[data-bs-theme="dark"] .mi-clase {
    background: rgba(15, 23, 42, 0.7);
    color: #f8fafc;
}
```

---

## Componentes Blade Existentes

Usar los componentes existentes en `resources/views/components/`:

```blade
{{-- Alert para mensajes flash --}}
<x-alert />

{{-- Modal reutilizable --}}
<x-modal />

{{-- Toast para notificaciones --}}
<x-toast />

{{-- Loader/Spinner --}}
<x-loader />

{{-- Card personalizada --}}
<x-card
    :ruta="route('ruta')"
    icono="fa-file"
    color="bg-primary"
    titulo="Título"
    descripcion="Descripción"
/>

{{-- Breadcrumb --}}
<x-breadcrumb />
```

---

## Rutas - Convenciones

```php
// Usar prefijos y nombres agrupados
Route::prefix('radfact')->name('radfact.')->group(function () {
    Route::resource('proveedores', ProveedorController::class);
    Route::resource('radicaciones', RadicacionController::class);

    // Acciones adicionales
    Route::post('aprobaciones/{aprobacion}/aprobar', [AprobacionController::class, 'aprobar'])
        ->name('aprobaciones.aprobar');
});

// En vistas usar route()
<a href="{{ route('radfact.proveedores.index') }}">
```

---

## Validación - Form Requests

Siempre crear Form Requests en lugar de validación inline:

```php
// app/Http/Requests/RadFact/StoreProveedorRequest.php
class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // O verificar permisos
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => 'required|string|max:20',
            'documento' => 'required|string|max:50|unique:rad_fact_proveedores',
            'correo' => 'nullable|email|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'documento.unique' => 'Este documento ya está registrado.',
        ];
    }
}
```

---

## Testing - PHPUnit

```php
// Crear test con artisan
php artisan make:test RadFact/ProveedorControllerTest

// Estructura de test
class ProveedorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_crear_proveedor(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->post(route('radfact.proveedores.store'), [
                'tipo_documento' => 'NIT',
                'documento' => '900123456',
            ]);

        // Assert
        $response->assertRedirect(route('radfact.proveedores.index'));
        $this->assertDatabaseHas('rad_fact_proveedores', [
            'documento' => '900123456',
        ]);
    }
}
```

---

## NO Hacer

- ❌ NO usar Tailwind CSS (usar Bootstrap 5)
- ❌ NO usar Pest (usar PHPUnit)
- ❌ NO validar inline en controladores (usar Form Requests)
- ❌ NO usar `env()` fuera de config/ (usar `config()`)
- ❌ NO usar `DB::` para queries simples (usar Eloquent)
- ❌ NO crear archivos de documentación sin que lo pida el usuario
- ❌ NO cambiar dependencias sin aprobación

---

## Laravel 12 - Estructura

- `bootstrap/app.php` → Registrar middleware, excepciones, rutas
- `bootstrap/providers.php` → Service providers
- **NO existe** `app/Console/Kernel.php` → Usar `routes/console.php`
- **NO existe** `app/Http/Middleware/` por defecto → Middleware en bootstrap/app.php
- Commands en `app/Console/Commands/` se auto-registran

---

## Herramientas Laravel Boost (MCP)

Cuando estén disponibles, usar:
- `search-docs` → Buscar documentación de Laravel
- `tinker` → Ejecutar PHP para debug
- `database-query` → Consultas a BD
- `list-artisan-commands` → Ver comandos disponibles

---

## Idioma

- Código: **Inglés** (nombres de variables, métodos, clases)
- Comentarios/Documentación: **Español** si el usuario lo prefiere
- UI/Mensajes al usuario: **Español**

---

## Comportamiento de Desarrollo

### Principios Fundamentales
- **Verifica antes de responder** - Usa las herramientas disponibles para confirmar
- **Propón siempre alternativas** - Con pros y contras claros
- **Desafía cuando sea necesario** - Pregunta si realmente es necesario antes de implementar
- **NO eres un yes-man** - Veras recomendaciones, no obediencia ciega

### Antes de Generar Código
1. Pregunta primero: ¿Qué tipo de solución necesitás?
2. Propone alternativas con sus pros/contras
3. Confirma el approach antes de escribir

### Cuando Usuario Pide Algo
- Analiza archivos existentes antes de modificar
- Declara explícitamente qué archivos se modificarán y por qué
- Solicita confirmación después de tomar cambios significativos

---

## Código - Reglas Generales

### PHP
- Siempre usa curly braces en estructuras de control, incluso con una línea
- Usa PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`
- Nunca permitas constructores vacíos sin parámetros
- Siempre tipos explícitos en return type declarations y parámetros
- Prefiere PHPDoc blocks sobre comentarios inline

### Naming
- Variables y métodos: camelCase en inglés (e.g., `isRegisteredForDiscounts`)
- Clases: PascalCase
- Constantes: UPPER_SNAKE_CASE
- Enums: TitleCase (e.g., `FavoritePerson`, `BestLake`, `Monthly`)

---

## Laravel - Reglas Específicas

### Uso de Artisan
- Siempre usa `php artisan make:` para crear archivos (migrations, controllers, models, etc.)
- Pasa `--no-interaction` para evitar prompts interactivos
- Para clases genéricas: `artisan make:class`

### Database & Eloquent
- **Prefiere Eloquent** sobre raw SQL
- **Eager loading obligatorio** - Evita problemas N+1 con `with()`
- **Relaciones con type hints** - Siempre declara return types explícitos
- Evita `DB::`; usa `Model::query()` en su lugar
- URL Generation: prefiere `route()` con named routes

### Models
- Usa `casts()` method en lugar de propiedad `$casts` (Laravel 11+)
- El modelo `User` usa `IdUsuario` como PK, no `id`
- Al relacionar con User: `$this->belongsTo(User::class, 'user_id', 'IdUsuario')`
- Crea factories y seeders útiles al crear nuevos modelos

### Controllers & Validation
- **Controllers deben ser thin** - Lógica en Services o Actions
- **Form Requests obligatorios** - Nunca validación inline en controlador
- Incluye ambos: validation rules y custom error messages

### Configuración
- `env()` SOLO en archivos de `config/`
- Usa `config('app.name')`, NO `env('APP_NAME')`

### Queues & Jobs
- Para operaciones largas: implementa `ShouldQueue`

### Testing (PHPUnit)
- **Siempre PHPUnit** - nunca Pest
- Estructura AAA: Arrange, Act, Assert
- Happy paths, failure paths, y edge cases
- Crea tests con: `php artisan make:test --phpunit <name>`
- Ejecuta después de cambios: `php artisan test --filter=testName`
- Test coverage mínimo: 80%

---

## Laravel 12 - Estructura Específica

- **NO existe** `app/Console/Kernel.php` - Usa `routes/console.php` o `bootstrap/app.php`
- **NO existe** `app/Http/Middleware/` por defecto - Middleware en `bootstrap/app.php`
- **Commands auto-registran** desde `app/Console/Commands/`
- `bootstrap/app.php` registra middleware, excepciones, rutas
- `bootstrap/providers.php` contiene service providers

### Migraciones
- Al modificar columna: incluye TODOS los atributos anteriores (o se pierden)
- Limit en eager loading: `$query->latest()->limit(10);` nativo

---

## Code Quality & Formatting

### Laravel Pint
- **Obligatorio**: `vendor/bin/pint --dirty` antes de finalizar cambios
- NO uses `vendor/bin/pint --test` - solo ejecuta `vendor/bin/pint`

### Vite / Frontend Bundling
- Si cambios frontend no se ven: user debe ejecutar `npm run build`, `npm run dev`, o `composer run dev`

---

## Herramientas & MCP Disponibles

Cuando están disponibles:
- `search-docs` → Documentación oficial de Laravel (úsala SIEMPRE primero)
- `list-artisan-commands` → Verificar parámetros de comandos Artisan
- `tinker` → Debug: ejecutar PHP y queries Eloquent
- `database-query` → Lecturas directo de BD
- `browser-logs` → Ver errores en browser (solo logs recientes útiles)

---

## NO Hacer

- ❌ Validación inline en controladores (usa Form Requests)
- ❌ Raw SQL a menos que sea necesario (usa Eloquent)
- ❌ `DB::` para queries simples
- ❌ Crear archivos de documentación sin que lo pida el usuario
- ❌ Cambiar dependencias sin aprobación
- ❌ Crear scripts de verificación si tests ya cubren esa funcionalidad
- ❌ Crear nuevas carpetas base sin aprobación
- ❌ Pest (usar PHPUnit)
- ❌ Tailwind CSS (usar Bootstrap 5)