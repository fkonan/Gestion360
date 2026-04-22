---
name: laravel-test
description: Create comprehensive tests using PHPUnit for Laravel applications with feature tests, unit tests, and database testing.
keywords: test, phpunit, testing, feature test, unit test
---

# Laravel Test Skill

## Cuando Activar

- Usuario dice: "create test", "write test", "testing"
- Usuario terminó de crear código
- Usuario necesita aumentar coverage

## Antes de Generar

**SIEMPRE pregunta:**

```
¿Qué necesitás testear?

Opción A: Feature Test (API/Web endpoints)
- Testa HTTP requests/responses
- Include auth, validación, DB
- Ejemplo: UserControllerTest

Opción B: Unit Test (lógica aislada)
- Testa Services, Actions, Models
- Mock dependencies
- Ejemplo: CreateUserActionTest

Opción C: Integration Test
- Testa múltiples componentes juntos
- Include DB, eventos, jobs

¿Cuál necesitás?
```

## Template - Feature Test (API Controller)

```php
<?php

namespace Tests\Feature;

use App\Models\{{Model}};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {{Model}}ControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_it_lists_{{models}}_with_pagination(): void
    {
        {{Model}}::factory()->count(15)->create();

        $response = $this->getJson('/api/{{models}}');

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'status',
                        'created_at',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    public function test_it_filters_{{models}}_by_status(): void
    {
        {{Model}}::factory()->active()->count(5)->create();
        {{Model}}::factory()->inactive()->count(3)->create();

        $response = $this->getJson('/api/{{models}}?status=active');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_it_shows_a_single_{{model}}(): void
    {
        ${{model}} = {{Model}}::factory()->create();

        $response = $this->getJson("/api/{{models}}/{${{model}}->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => ${{model}}->id,
                    'name' => ${{model}}->name,
                ],
            ]);
    }

    public function test_it_returns_404_for_non_existent_{{model}}(): void
    {
        $response = $this->getJson('/api/{{models}}/999');

        $response->assertNotFound();
    }

    public function test_it_creates_a_{{model}}(): void
    {
        $data = [
            'name' => 'New {{Model}}',
            'description' => 'Test description',
            'status' => {{Model}}Status::ACTIVE->value,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/{{models}}', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New {{Model}}');

        $this->assertDatabaseHas('{{table}}', [
            'name' => 'New {{Model}}',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/{{models}}', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'status']);
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->postJson('/api/{{models}}', [
            'name' => 'Test',
        ]);

        $response->assertUnauthorized();
    }

    public function test_it_updates_a_{{model}}(): void
    {
        ${{model}} = {{Model}}::factory()->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/{{models}}/{${{model}}->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('{{table}}', [
            'id' => ${{model}}->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_it_deletes_a_{{model}}(): void
    {
        ${{model}} = {{Model}}::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/{{models}}/{${{model}}->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('{{table}}', [
            'id' => ${{model}}->id,
            'deleted_at' => null,
        ]);
    }
}
```

## Template - Unit Test (Action/Service)

```php
<?php

namespace Tests\Unit\Actions;

use App\Actions\{{Model}}\Create{{Model}};
use App\DataTransferObjects\{{Model}}Data;
use App\Enums\{{Model}}Status;
use App\Events\{{Model}}Created;
use App\Models\User;
use App\Models\{{Model}};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Create{{Model}}Test extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_{{model}}_with_valid_data(): void
    {
        $user = User::factory()->create();

        $data = new {{Model}}Data(
            name: 'Test {{Model}}',
            description: 'Test description',
            status: {{Model}}Status::ACTIVE,
            userId: $user->id,
        );

        $action = new Create{{Model}}();
        ${{model}} = $action->execute($data);

        $this->assertInstanceOf({{Model}}::class, ${{model}});
        $this->assertEquals('Test {{Model}}', ${{model}}->name);
        $this->assertEquals({{Model}}Status::ACTIVE, ${{model}}->status);

        $this->assertDatabaseHas('{{table}}', [
            'name' => 'Test {{Model}}',
            'user_id' => $user->id,
        ]);
    }

    public function test_it_dispatches_{{model}}_created_event(): void
    {
        Event::fake([{{Model}}Created::class]);

        $user = User::factory()->create();

        $data = new {{Model}}Data(
            name: 'Test {{Model}}',
            description: 'Test',
            status: {{Model}}Status::ACTIVE,
            userId: $user->id,
        );

        $action = new Create{{Model}}();
        ${{model}} = $action->execute($data);

        Event::assertDispatched({{Model}}Created::class, function ($event) use (${{model}}) {
            return $event->{{model}}->id === ${{model}}->id;
        });
    }

    public function test_it_syncs_tags_when_provided(): void
    {
        $user = User::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $data = new {{Model}}Data(
            name: 'Test {{Model}}',
            description: 'Test',
            status: {{Model}}Status::ACTIVE,
            userId: $user->id,
            tags: $tags->pluck('id')->toArray(),
        );

        $action = new Create{{Model}}();
        ${{model}} = $action->execute($data);

        $this->assertCount(3, ${{model}}->tags);
        $this->assertEquals($tags->pluck('id')->toArray(), ${{model}}->tags->pluck('id')->toArray());
    }
}
```

## Template - Model Test

```php
<?php

namespace Tests\Unit\Models;

use App\Models\{{Model}};
use App\Models\User;
use App\Enums\{{Model}}Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {{Model}}Test extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes(): void
    {
        ${{model}} = new {{Model}}();

        $this->assertContains('name', ${{model}}->getFillable());
        $this->assertContains('description', ${{model}}->getFillable());
        $this->assertContains('status', ${{model}}->getFillable());
    }

    public function test_it_casts_status_to_enum(): void
    {
        ${{model}} = {{Model}}::factory()->create([
            'status' => {{Model}}Status::ACTIVE,
        ]);

        $this->assertInstanceOf({{Model}}Status::class, ${{model}}->status);
        $this->assertEquals({{Model}}Status::ACTIVE, ${{model}}->status);
    }

    public function test_it_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        ${{model}} = {{Model}}::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, ${{model}}->user);
        $this->assertEquals($user->id, ${{model}}->user->id);
    }

    public function test_it_scopes_active_{{models}}(): void
    {
        {{Model}}::factory()->count(3)->active()->create();
        {{Model}}::factory()->count(2)->inactive()->create();

        $active = {{Model}}::active()->get();

        $this->assertCount(3, $active);
        foreach ($active as ${{model}}) {
            $this->assertEquals({{Model}}Status::ACTIVE, ${{model}}->status);
        }
    }

    public function test_it_can_be_activated(): void
    {
        ${{model}} = {{Model}}::factory()->inactive()->create();

        $this->assertFalse(${{model}}->isActive());

        ${{model}}->activate();

        $this->assertTrue(${{model}}->fresh()->isActive());
    }

    public function test_it_soft_deletes(): void
    {
        ${{model}} = {{Model}}::factory()->create();

        ${{model}}->delete();

        $this->assertEquals(0, {{Model}}::count());
        $this->assertEquals(1, {{Model}}::withTrashed()->count());
        $this->assertTrue(${{model}}->fresh()->trashed());
    }
}
```

## Template - Job Test

```php
<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Process{{Model}};
use App\Models\{{Model}};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Process{{Model}}Test extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_be_dispatched(): void
    {
        Queue::fake();

        ${{model}} = {{Model}}::factory()->create();

        Process{{Model}}::dispatch(${{model}});

        Queue::assertPushed(Process{{Model}}::class, function ($job) use (${{model}}) {
            return $job->{{model}}->id === ${{model}}->id;
        });
    }

    public function test_it_processes_the_{{model}}(): void
    {
        ${{model}} = {{Model}}::factory()->create();

        $job = new Process{{Model}}(${{model}});
        $job->handle();

        $this->assertNotNull(${{model}}->fresh()->processed_at);
    }

    public function test_it_has_retry_configuration(): void
    {
        ${{model}} = {{Model}}::factory()->create();

        $job = new Process{{Model}}(${{model}});

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }
}
```

## Helpers de Testing

### Database Assertions

```php
// Assert database has record
$this->assertDatabaseHas('{{table}}', [
    'name' => 'Test',
]);

// Assert database missing record
$this->assertDatabaseMissing('{{table}}', [
    'name' => 'Deleted',
]);

// Assert database count
$this->assertDatabaseCount('{{table}}', 5);
```

### Event Testing

```php
Event::fake();

// Assert dispatched
Event::assertDispatched({{Model}}Created::class);

// Assert dispatched with condition
Event::assertDispatched({{Model}}Created::class, function ($event) {
    return $event->{{model}}->name === 'Test';
});

// Assert not dispatched
Event::assertNotDispatched({{Model}}Deleted::class);

// Assert dispatched times
Event::assertDispatchedTimes({{Model}}Created::class, 3);
```

### Queue Testing

```php
Queue::fake();

// Assert pushed
Queue::assertPushed(ProcessOrder::class);

// Assert pushed with condition
Queue::assertPushed(ProcessOrder::class, function ($job) {
    return $job->order->id === 1;
});

// Assert pushed on queue
Queue::assertPushedOn('high-priority', ProcessOrder::class);

// Assert not pushed
Queue::assertNotPushed(ProcessOrder::class);
```

### Mail Testing

```php
Mail::fake();

// Assert sent
Mail::assertSent(WelcomeMail::class);

// Assert sent to
Mail::assertSent(WelcomeMail::class, function ($mail) {
    return $mail->hasTo('test@example.com');
});

// Assert queued
Mail::assertQueued(WelcomeMail::class);
```

### Storage Testing

```php
Storage::fake('public');

$file = UploadedFile::fake()->image('avatar.jpg');

// Upload file
$path = $file->store('avatars', 'public');

// Assert exists
Storage::disk('public')->assertExists($path);

// Assert missing
Storage::disk('public')->assertMissing('old-avatar.jpg');
```

## Checklist de Testing

- Feature tests (API/Web endpoints)
- Unit tests (Actions/Services)
- Model tests (relationships, scopes)
- Request validation tests
- Authorization tests
- Event/Job tests
- Database assertions
- Edge cases (null, empty, invalid)
- Error handling
- Coverage >80%

## Comandos PHPUnit

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific file
php artisan test tests/Feature/{{Model}}ControllerTest.php

# Run specific test
php artisan test --filter=test_it_creates_a_{{model}}

# Run in parallel
php artisan test --parallel

# Run with detailed output
php artisan test --verbose
```

## Decisiones

| Qué Testear | Tipo de Test |
|-------------|--------------|
| API endpoints | Feature Test |
| Actions/Services | Unit Test |
| Models | Unit Test |
| Validación | Feature Test |
| Eventos | Unit Test con fake |
| Jobs | Unit Test con fake |
| Policies | Feature Test |
