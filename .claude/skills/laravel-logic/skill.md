---
name: laravel-logic
description: Business logic patterns for Laravel 12+ - Actions (recommended), Services, DTOs, Events, Jobs. Single Responsibility with pragmatic choices.
keywords: action, service, business logic, dto, event, job, transaction
---

# Laravel Business Logic Skill

## Filosofía

**Default:** Actions (Single Responsibility)
**Permitido:** Services (CRUD simple < 5 métodos)
**Siempre:** DTOs para data transfer, nunca arrays

## Decision Tree

```
¿Cuántas operaciones?
├─ 1-2 operaciones → Action
├─ 3-5 simples (CRUD) → Service OK
└─ 6+ operaciones → Actions

¿Complejidad?
├─ Simple CRUD → Service OK
└─ Lógica compleja/workflows → Actions

¿Tamaño proyecto?
├─ Pequeño/prototipo → Service OK
└─ Mediano/Grande/Enterprise → Actions
```

## Pregunta Antes de Generar

```
¿Qué patrón necesitás?

Opción A: Actions (RECOMENDADO)
- Una clase por operación: CreateUser, UpdateUser
- Pros: Testeable, SOLID, escalable
- Cons: Más archivos

Opción B: Service (CRUD simple)
- Una clase con métodos relacionados
- Pros: Todo en un lugar
- Cons: Crece rápido

¿Cuál se ajusta a tu caso?
```

---

## Templates

### Action (RECOMENDADO)

```php
<?php

declare(strict_types=1);

namespace App\Actions\{{Model}};

use App\DataTransferObjects\{{Model}}Data;
use App\Events\{{Model}}Created;
use App\Models\{{Model}};
use Illuminate\Support\Facades\DB;

final class Create{{Model}}
{
    public function execute({{Model}}Data $data): {{Model}}
    {
        return DB::transaction(function () use ($data): {{Model}} {
            ${{model}} = {{Model}}::create([
                'name' => $data->name,
                'status' => $data->status,
                'user_id' => $data->userId,
            ]);

            if ($data->tags) {
                ${{model}}->tags()->sync($data->tags);
            }

            event(new {{Model}}Created(${{model}}));

            return ${{model}}->load(['user', 'tags']);
        });
    }
}
```

### Service (Múltiples Métodos)

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\{{Model}}Data;
use App\Events\{{Model}}Created;
use App\Events\{{Model}}Updated;
use App\Events\{{Model}}Deleted;
use App\Models\{{Model}};
use Illuminate\Support\Facades\DB;

final class {{Model}}Service
{
    public function create({{Model}}Data $data): {{Model}}
    {
        return DB::transaction(function () use ($data): {{Model}} {
            ${{model}} = {{Model}}::create($data->toArray());
            event(new {{Model}}Created(${{model}}));
            return ${{model}};
        });
    }

    public function update({{Model}} ${{model}}, {{Model}}Data $data): {{Model}}
    {
        return DB::transaction(function () use (${{model}}, $data): {{Model}} {
            ${{model}}->update($data->toArray());
            event(new {{Model}}Updated(${{model}}));
            return ${{model}}->fresh();
        });
    }

    public function delete({{Model}} ${{model}}): bool
    {
        return DB::transaction(function () use (${{model}}): bool {
            $deleted = ${{model}}->delete();
            if ($deleted) {
                event(new {{Model}}Deleted(${{model}}));
            }
            return $deleted;
        });
    }
}
```

### DTO (Data Transfer Object) - Laravel 12+ Style

```php
<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\{{Model}}Status;
use App\Http\Requests\Store{{Model}}Request;

final readonly class {{Model}}Data
{
    public function __construct(
        public string $name,
        public ?string $description,
        public {{Model}}Status $status,
        public int $userId,
        public ?array $tags = null,
    ) {}

    public static function fromRequest(Store{{Model}}Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
            status: $request->enum('status', {{Model}}Status::class),
            userId: $request->user()->id,
            tags: $request->validated('tags'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'user_id' => $this->userId,
        ];
    }
}
```

### DTO con Spatie Laravel Data

```php
<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\{{Model}}Status;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class {{Model}}Data extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public readonly string $name,

        public readonly ?string $description,

        #[Required]
        public readonly {{Model}}Status $status,

        public readonly int $userId,

        public readonly ?array $tags = null,
    ) {}
}
```

### Event

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\{{Model}};
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class {{Model}}Created
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly {{Model}} ${{model}}
    ) {}
}
```

### Listener

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\{{Model}}Created;
use App\Notifications\{{Model}}CreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

final class Send{{Model}}CreatedNotification implements ShouldQueue
{
    public function handle({{Model}}Created $event): void
    {
        $event->{{model}}->user->notify(
            new {{Model}}CreatedNotification($event->{{model}})
        );
    }
}
```

### Job (Queue)

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\{{Model}};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class Process{{Model}} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        public readonly {{Model}} ${{model}}
    ) {}

    public function handle(): void
    {
        // Heavy processing here
        $this->{{model}}->update(['processed_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        // Log failure, notify admin, etc.
        report($exception);
    }
}
```

### Repository (Opcional - Enterprise)

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\{{Model}};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface {{Model}}RepositoryInterface
{
    public function find(int $id): ?{{Model}};
    public function create(array $data): {{Model}};
    public function update({{Model}} ${{model}}, array $data): {{Model}};
    public function delete({{Model}} ${{model}}): bool;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}

final class {{Model}}Repository implements {{Model}}RepositoryInterface
{
    public function find(int $id): ?{{Model}}
    {
        return {{Model}}::with(['user', 'tags'])->find($id);
    }

    public function create(array $data): {{Model}}
    {
        return {{Model}}::create($data);
    }

    public function update({{Model}} ${{model}}, array $data): {{Model}}
    {
        ${{model}}->update($data);
        return ${{model}}->fresh();
    }

    public function delete({{Model}} ${{model}}): bool
    {
        return ${{model}}->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return {{Model}}::with(['user'])->latest()->paginate($perPage);
    }
}
```

---

## Uso en Controller

```php
// Con Action (recomendado)
public function store(
    Store{{Model}}Request $request,
    Create{{Model}} $action
): {{Model}}Resource {
    return {{Model}}Resource::make(
        $action->execute({{Model}}Data::fromRequest($request))
    );
}

// Con Service
public function store(
    Store{{Model}}Request $request,
    {{Model}}Service $service
): {{Model}}Resource {
    return {{Model}}Resource::make(
        $service->create({{Model}}Data::fromRequest($request))
    );
}
```

---

## Test (Pest)

```php
<?php

use App\Actions\{{Model}}\Create{{Model}};
use App\DataTransferObjects\{{Model}}Data;
use App\Enums\{{Model}}Status;
use App\Events\{{Model}}Created;
use App\Models\User;
use App\Models\{{Model}};
use Illuminate\Support\Facades\Event;

describe('Create{{Model}} Action', function () {
    it('creates a {{model}}', function () {
        $user = User::factory()->create();

        $data = new {{Model}}Data(
            name: 'Test',
            description: null,
            status: {{Model}}Status::ACTIVE,
            userId: $user->id,
        );

        ${{model}} = (new Create{{Model}}())->execute($data);

        expect(${{model}}->name)->toBe('Test')
            ->and({{Model}}::count())->toBe(1);
    });

    it('dispatches event', function () {
        Event::fake([{{Model}}Created::class]);

        $user = User::factory()->create();
        $data = new {{Model}}Data(
            name: 'Test',
            description: null,
            status: {{Model}}Status::ACTIVE,
            userId: $user->id,
        );

        (new Create{{Model}}())->execute($data);

        Event::assertDispatched({{Model}}Created::class);
    });

    it('wraps in transaction', function () {
        // Verify rollback on failure
        $user = User::factory()->create();
        $data = new {{Model}}Data(
            name: 'Test',
            description: null,
            status: {{Model}}Status::ACTIVE,
            userId: $user->id,
        );

        // Mock failure scenario
        // Verify {{Model}}::count() is 0 after rollback
    });
});
```

---

## Comandos Artisan

```bash
# Crear estructura manualmente
mkdir -p app/Actions/{{Model}}
mkdir -p app/DataTransferObjects

# Eventos, listeners, jobs
php artisan make:event {{Model}}Created
php artisan make:listener Send{{Model}}Notification --event={{Model}}Created
php artisan make:job Process{{Model}}
php artisan make:test Create{{Model}}ActionTest --pest
```

---

## Decisión Rápida

| Caso | Usar |
|------|------|
| Una operación | Action |
| CRUD simple (< 5 métodos) | Service |
| Operaciones complejas | Actions |
| Abstracción DB (enterprise) | Repository |
| Operación pesada/async | Job |
| Notificar cambios | Event + Listener |
| Data transfer | DTO (readonly) |

## Warning Signs → Refactor a Actions

- Service > 5 métodos
- Método > 30 líneas
- Baja cohesión entre métodos
- Difícil de testear
