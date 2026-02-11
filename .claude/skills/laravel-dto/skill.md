---
name: laravel-dto
description: Data Transfer Objects using Spatie Laravel Data. Type-safe data handling with validation and transformation.
keywords: dto, data transfer object, spatie, type safety
---

# Laravel DTO Skill

## Instalación

```bash
composer require spatie/laravel-data
```

## Por Qué DTOs

```php
// ❌ Arrays - Sin tipos, sin autocomplete, sin validación
public function create(array $data) {}

// ✅ DTO - Type-safe, autocomplete, validado
public function create(UserData $data) {}
```

## Template

```php
<?php

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

class {{Model}}Data extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
    ) {}

    public static function fromRequest(Store{{Model}}Request $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => strtolower($this->email),
            'phone' => $this->phone,
        ];
    }
}
```

## Con Validación (Atributos Spatie)

```php
use Spatie\LaravelData\Attributes\Validation\{Email, Min, Required};

class UserData extends Data
{
    public function __construct(
        #[Required, Min(3)]
        public string $name,
        
        #[Required, Email]
        public string $email,
    ) {}
}
```

## Nested DTOs

```php
class AddressData extends Data
{
    public function __construct(
        public string $street,
        public string $city,
    ) {}
}

class UserData extends Data
{
    public function __construct(
        public string $name,
        public AddressData $address,
    ) {}
}
```

## Uso en Controller + Action

```php
// Controller
public function store(StoreUserRequest $request, CreateUser $action): UserResource
{
    return UserResource::make(
        $action->execute(UserData::fromRequest($request))
    );
}

// Action
class CreateUser
{
    public function execute(UserData $data): User
    {
        return User::create($data->toArray());
    }
}
```

## Test

```php
it('creates DTO from request', function () {
    $request = StoreUserRequest::create('/', 'POST', [
        'name' => 'John',
        'email' => 'john@test.com',
    ]);
    
    $data = UserData::fromRequest($request);
    
    expect($data->name)->toBe('John')
        ->and($data->email)->toBe('john@test.com');
});
```

## Regla

**SIEMPRE** usa DTOs en lugar de arrays para:
- Parámetros de Actions/Services
- Data transfer entre capas
- Request → Business Logic
