---
name: laravel-model
description: Create Eloquent models with relationships, casts, scopes, and proper type safety following Laravel 11+ best practices.
keywords: model, eloquent, relationship, cast, scope, factory
---

# Laravel Model Skill

## Cuando Activar

- Usuario dice: "create model", "nuevo modelo", "eloquent model"
- Usuario necesita representar una tabla
- Usuario menciona relationships

## Antes de Generar

**SIEMPRE pregunta:**

```
¿Qué necesitás en este modelo?

1. Relationships:
   - ¿Tiene relaciones? (hasMany, belongsTo, belongsToMany)
   - ¿Cuáles son?

2. Casts/Attributes:
   - ¿Campos con tipos especiales? (enum, date, array, json)
   
3. Scopes:
   - ¿Queries comunes? (active, recent, byStatus)

4. Factory:
   - ¿Necesitás factory para testing?

Contame para generar el modelo completo.
```

## Template - Model Completo

```php
<?php

namespace App\Models;

use App\Enums\{{Model}}Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class {{Model}} extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
    ];

    /**
     * The attributes that should be hidden.
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => {{Model}}Status::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ==================== Relationships ====================

    /**
     * Get the user that owns the {{model}}.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the {{model}}.
     */
    public function items(): HasMany
    {
        return $this->hasMany({{Model}}Item::class);
    }

    // ==================== Scopes ====================

    /**
     * Scope a query to only include active {{models}}.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', {{Model}}Status::ACTIVE);
    }

    /**
     * Scope a query to only include recent {{models}}.
     */
    public function scopeRecent(Builder $query, int $days = 7): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus(Builder $query, {{Model}}Status $status): void
    {
        $query->where('status', $status);
    }

    // ==================== Accessors & Mutators ====================

    /**
     * Get the formatted name.
     */
    public function getFormattedNameAttribute(): string
    {
        return ucfirst($this->name);
    }

    /**
     * Check if {{model}} is active.
     */
    public function isActive(): bool
    {
        return $this->status === {{Model}}Status::ACTIVE;
    }

    // ==================== Helper Methods ====================

    /**
     * Activate the {{model}}.
     */
    public function activate(): bool
    {
        return $this->update(['status' => {{Model}}Status::ACTIVE]);
    }

    /**
     * Deactivate the {{model}}.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => {{Model}}Status::INACTIVE]);
    }
}
```

## Template - Enum

```php
<?php

namespace App\Enums;

enum {{Model}}Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case ARCHIVED = 'archived';

    /**
     * Get the label for the status.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::PENDING => 'Pendiente',
            self::ARCHIVED => 'Archivado',
        };
    }

    /**
     * Get the color for the status.
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'red',
            self::PENDING => 'yellow',
            self::ARCHIVED => 'gray',
        };
    }

    /**
     * Get all active statuses.
     */
    public static function activeStatuses(): array
    {
        return [self::ACTIVE, self::PENDING];
    }
}
```

## Template - Migration

```php
<?php

use App\Enums\{{Model}}Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{{table}}', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default({{Model}}Status::PENDING->value);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{{table}}');
    }
};
```

## Template - Factory

```php
<?php

namespace Database\Factories;

use App\Enums\{{Model}}Status;
use App\Models\User;
use App\Models\{{Model}};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<{{Model}}>
 */
class {{Model}}Factory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement({{Model}}Status::cases()),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the {{model}} is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => {{Model}}Status::ACTIVE,
        ]);
    }

    /**
     * Indicate that the {{model}} is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => {{Model}}Status::INACTIVE,
        ]);
    }

    /**
     * Indicate that the {{model}} belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
```

## Template - Model Test

```php
<?php

use App\Enums\{{Model}}Status;
use App\Models\User;
use App\Models\{{Model}};

describe('{{Model}} Model', function () {
    it('creates a {{model}}', function () {
        ${{model}} = {{Model}}::factory()->create([
            'name' => 'Test {{Model}}',
        ]);

        expect(${{model}}->name)->toBe('Test {{Model}}')
            ->and(${{model}}->status)->toBeInstanceOf({{Model}}Status::class);
    });

    it('belongs to a user', function () {
        $user = User::factory()->create();
        ${{model}} = {{Model}}::factory()->forUser($user)->create();

        expect(${{model}}->user)->toBeInstanceOf(User::class)
            ->and(${{model}}->user->id)->toBe($user->id);
    });

    it('has many items', function () {
        ${{model}} = {{Model}}::factory()
            ->has({{Model}}Item::factory()->count(3), 'items')
            ->create();

        expect(${{model}}->items)->toHaveCount(3);
    });

    it('can be activated', function () {
        ${{model}} = {{Model}}::factory()->inactive()->create();

        expect(${{model}}->isActive())->toBeFalse();

        ${{model}}->activate();

        expect(${{model}}->fresh()->isActive())->toBeTrue();
    });

    it('can be deactivated', function () {
        ${{model}} = {{Model}}::factory()->active()->create();

        expect(${{model}}->isActive())->toBeTrue();

        ${{model}}->deactivate();

        expect(${{model}}->fresh()->isActive())->toBeFalse();
    });

    it('scopes active {{models}}', function () {
        {{Model}}::factory()->active()->count(3)->create();
        {{Model}}::factory()->inactive()->count(2)->create();

        $active = {{Model}}::active()->get();

        expect($active)->toHaveCount(3);
    });

    it('scopes recent {{models}}', function () {
        // Old {{models}}
        {{Model}}::factory()->count(2)->create([
            'created_at' => now()->subDays(10),
        ]);

        // Recent {{models}}
        {{Model}}::factory()->count(3)->create();

        $recent = {{Model}}::recent()->get();

        expect($recent)->toHaveCount(3);
    });

    it('soft deletes', function () {
        ${{model}} = {{Model}}::factory()->create();

        ${{model}}->delete();

        expect({{Model}}::count())->toBe(0)
            ->and({{Model}}::withTrashed()->count())->toBe(1);
    });
});
```

## Relationships Comunes

### One to Many (1:N)

```php
// Parent Model (User)
public function {{models}}(): HasMany
{
    return $this->hasMany({{Model}}::class);
}

// Child Model ({{Model}})
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

### Many to Many (N:M)

```php
// {{Model}}
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class)
        ->withTimestamps()
        ->withPivot(['order', 'is_primary']);
}

// Tag
public function {{models}}(): BelongsToMany
{
    return $this->belongsToMany({{Model}}::class)
        ->withTimestamps();
}
```

### One to One (1:1)

```php
// {{Model}}
public function profile(): HasOne
{
    return $this->hasOne({{Model}}Profile::class);
}

// {{Model}}Profile
public function {{model}}(): BelongsTo
{
    return $this->belongsTo({{Model}}::class);
}
```

### Polymorphic

```php
// Comment (polymorphic model)
public function commentable(): MorphTo
{
    return $this->morphTo();
}

// {{Model}} (can have comments)
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

## Casts Comunes

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'settings' => 'array',
        'metadata' => 'json',
        'price' => 'decimal:2',
        'status' => {{Model}}Status::class, // Enum
        'tags' => AsCollection::class,
        'config' => AsArrayObject::class,
    ];
}
```

## Scopes Útiles

```php
// Active/Inactive
public function scopeActive(Builder $query): void
{
    $query->where('is_active', true);
}

// Date ranges
public function scopeBetweenDates(Builder $query, $start, $end): void
{
    $query->whereBetween('created_at', [$start, $end]);
}

// Search
public function scopeSearch(Builder $query, ?string $term): void
{
    $query->when($term, function ($query, $term) {
        $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    });
}

// With relationships
public function scopeWithRelations(Builder $query): void
{
    $query->with(['user', 'items']);
}
```

## Checklist de Generación

- ✅ Mass assignment protection ($fillable)
- ✅ Hidden attributes ($hidden)
- ✅ Type casts (casts())
- ✅ Relationships typed
- ✅ Scopes útiles
- ✅ Factory incluido
- ✅ Migration incluida
- ✅ Enum si aplica
- ✅ Tests incluidos
- ✅ PHPDoc comments

## Comandos Artisan

```bash
# Generar model con todo
php artisan make:model {{Model}} -mfsc
# -m: migration
# -f: factory
# -s: seeder
# -c: controller

# Solo model
php artisan make:model {{Model}}

# Model con migration
php artisan make:model {{Model}} -m

# Generar factory
php artisan make:factory {{Model}}Factory

# Generar migration
php artisan make:migration create_{{table}}_table
```

## Decisiones

| Caso | Usar |
|------|------|
| Constantes | Enum (PHP 8.1+) |
| Dates | Cast datetime |
| JSON fields | Cast array/json |
| Soft Delete | SoftDeletes trait |
| Queries comunes | Scopes |
| Testing | Factory + states |
| Relationships | Typed return |
