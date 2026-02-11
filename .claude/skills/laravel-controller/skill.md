---
name: laravel-controller
description: Create thin Laravel controllers following best practices with Form Requests, Resources, and dependency injection.
keywords: controller, api, rest, laravel, resource controller
---

# Laravel Controller Skill

## Cuando Activar

- Usuario dice: "create controller", "nuevo controller", "API controller"
- Usuario necesita endpoints REST
- Usuario menciona CRUD

## Antes de Generar

**SIEMPRE pregunta:**

```
¿Qué tipo de controller necesitás?

Opción A: API Resource Controller (RECOMENDADO)
- RESTful endpoints (index, store, show, update, destroy)
- JSON responses con API Resources
- Form Requests para validación

Opción B: API Controller Custom
- Endpoints específicos (no RESTful completo)
- Métodos personalizados

Opción C: Web Controller (con vistas)
- Para apps tradicionales con Blade
- Return views

¿Cuál se ajusta a tu caso?
```

## Template - API Resource Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Actions\{{Model}}\Create{{Model}};
use App\Actions\{{Model}}\Update{{Model}};
use App\Actions\{{Model}}\Delete{{Model}};
use App\DataTransferObjects\{{Model}}Data;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store{{Model}}Request;
use App\Http\Requests\Update{{Model}}Request;
use App\Http\Resources\{{Model}}Resource;
use App\Models\{{Model}};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class {{Model}}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        ${{models}} = {{Model}}::query()
            ->latest()
            ->paginate(15);
        
        return {{Model}}Resource::collection(${{models}});
    }

    /**
     * Store a newly created resource.
     */
    public function store(
        Store{{Model}}Request $request,
        Create{{Model}} $action
    ): {{Model}}Resource {
        ${{model}} = $action->execute(
            {{Model}}Data::fromRequest($request)
        );
        
        return {{Model}}Resource::make(${{model}});
    }

    /**
     * Display the specified resource.
     */
    public function show({{Model}} ${{model}}): {{Model}}Resource
    {
        // Eager load relationships if needed
        ${{model}}->load(['relationship']);
        
        return {{Model}}Resource::make(${{model}});
    }

    /**
     * Update the specified resource.
     */
    public function update(
        Update{{Model}}Request $request,
        {{Model}} ${{model}},
        Update{{Model}} $action
    ): {{Model}}Resource {
        $updated = $action->execute(
            ${{model}},
            {{Model}}Data::fromRequest($request)
        );
        
        return {{Model}}Resource::make($updated);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(
        {{Model}} ${{model}},
        Delete{{Model}} $action
    ): Response {
        $action->execute(${{model}});
        
        return response()->noContent();
    }
}
```

## Template - Routes (api.php)

```php
<?php

use App\Http\Controllers\Api\{{Model}}Controller;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/{{models}}', [{{Model}}Controller::class, 'index']);
Route::get('/{{models}}/{{{model}}}', [{{Model}}Controller::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/{{models}}', [{{Model}}Controller::class, 'store']);
    Route::put('/{{models}}/{{{model}}}', [{{Model}}Controller::class, 'update']);
    Route::delete('/{{models}}/{{{model}}}', [{{Model}}Controller::class, 'destroy']);
});
```

## Template - Custom API Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{{Custom}}Request;
use App\Http\Resources\{{Model}}Resource;
use App\Services\{{Model}}Service;
use Illuminate\Http\JsonResponse;

class {{Model}}Controller extends Controller
{
    public function __construct(
        private readonly {{Model}}Service $service
    ) {}

    /**
     * Custom endpoint example
     */
    public function customAction({{Custom}}Request $request): JsonResponse
    {
        $result = $this->service->performAction(
            $request->validated()
        );
        
        return response()->json([
            'message' => 'Action completed successfully',
            'data' => {{Model}}Resource::make($result),
        ]);
    }

    /**
     * Batch operation example
     */
    public function batchCreate({{Custom}}Request $request): JsonResponse
    {
        $items = $this->service->createMultiple(
            $request->validated('items')
        );
        
        return response()->json([
            'message' => sprintf('%d items created', count($items)),
            'data' => {{Model}}Resource::collection($items),
        ], 201);
    }

    /**
     * Search endpoint example
     */
    public function search({{Custom}}Request $request): JsonResponse
    {
        $results = $this->service->search(
            $request->validated('query')
        );
        
        return response()->json([
            'data' => {{Model}}Resource::collection($results),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
            ],
        ]);
    }
}
```

## Template - Controller Test (Pest)

```php
<?php

use App\Models\{{Model}};
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('{{Model}} Controller', function () {
    it('lists {{models}}', function () {
        {{Model}}::factory()->count(3)->create();
        
        $response = $this->getJson('/api/{{models}}');
        
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'created_at'],
                ],
                'links',
                'meta',
            ]);
    });

    it('shows a single {{model}}', function () {
        ${{model}} = {{Model}}::factory()->create();
        
        $response = $this->getJson("/api/{{models}}/{${{model}}->id}");
        
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => ${{model}}->id,
                    'name' => ${{model}}->name,
                ],
            ]);
    });

    it('creates a {{model}}', function () {
        $data = [
            'name' => 'Test {{Model}}',
            'description' => 'Test description',
        ];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/{{models}}', $data);
        
        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test {{Model}}');
        
        expect({{Model}}::count())->toBe(1);
    });

    it('validates required fields when creating', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/{{models}}', []);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('updates a {{model}}', function () {
        ${{model}} = {{Model}}::factory()->create();
        
        $data = ['name' => 'Updated Name'];
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/{{models}}/{${{model}}->id}", $data);
        
        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
        
        expect(${{model}}->fresh()->name)->toBe('Updated Name');
    });

    it('deletes a {{model}}', function () {
        ${{model}} = {{Model}}::factory()->create();
        
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/{{models}}/{${{model}}->id}");
        
        $response->assertNoContent();
        
        expect({{Model}}::count())->toBe(0);
    });

    it('requires authentication for write operations', function () {
        ${{model}} = {{Model}}::factory()->create();
        
        $this->postJson('/api/{{models}}', [])
            ->assertUnauthorized();
        
        $this->putJson("/api/{{models}}/{${{model}}->id}", [])
            ->assertUnauthorized();
        
        $this->deleteJson("/api/{{models}}/{${{model}}->id}")
            ->assertUnauthorized();
    });
});
```

## Checklist de Generación

- ✅ Controller delgado (< 20 líneas por método)
- ✅ Type hints en todo
- ✅ Form Requests para validación
- ✅ API Resources para responses
- ✅ Actions/Services inyectados
- ✅ Route model binding
- ✅ Auth middleware cuando corresponda
- ✅ Tests incluidos (Pest)
- ✅ PHPDoc comments
- ✅ RESTful naming

## Comandos Artisan

```bash
# Generar controller API
php artisan make:controller Api/{{Model}}Controller --api --requests

# Generar con resources
php artisan make:controller Api/{{Model}}Controller --resource

# Generar test
php artisan make:test {{Model}}ControllerTest --pest
```

## Decisiones

| Caso | Usar |
|------|------|
| CRUD completo | Resource Controller |
| Endpoints custom | Custom Controller |
| Solo lectura | Solo index/show |
| Lógica compleja | Inyectar Action/Service |
| Validación | Form Request |
| Response | API Resource |
