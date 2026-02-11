---
name: laravel-quality
description: Code quality enforcement with PHPStan (level 5+) and Laravel Pint. Pre-commit checks.
keywords: phpstan, pint, quality, static analysis, code style
---

# Laravel Quality Skill

## Stack

- **Laravel Pint** - Code styling (PSR-12 + Laravel)
- **PHPStan/Larastan** - Static analysis (nivel 5+)

## Instalación

```bash
# Pint (incluido en Laravel 9+)
composer require laravel/pint --dev

# PHPStan con Larastan
composer require nunomaduro/larastan:^3.0 --dev
```

## Configuración PHPStan

**Archivo:** `phpstan.neon`

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
        - config
        - database
        - routes
    
    level: 5
    
    checkMissingIterableValueType: false
    
    excludePaths:
        - app/Console/Kernel.php
```

## Comandos

```bash
# Pint - Format
./vendor/bin/pint              # Aplicar
./vendor/bin/pint --test       # Solo verificar
./vendor/bin/pint app/Models   # Path específico

# PHPStan - Analyze
./vendor/bin/phpstan analyse
./vendor/bin/phpstan analyse app/Actions
./vendor/bin/phpstan analyse --memory-limit=2G
```

## Pre-Commit Checklist

```bash
# 1. Format code
./vendor/bin/pint

# 2. Static analysis
./vendor/bin/phpstan analyse

# 3. Tests
php artisan test

# 4. No debugging code
grep -r "dd\|dump\|var_dump" app/ --include="*.php"
```

## Niveles PHPStan

| Level | Uso |
|-------|-----|
| 0-2 | Básico |
| **5** | **Recomendado Laravel** |
| 6-8 | Estricto |
| 9 | Máximo (difícil en Laravel) |

## Pre-Commit Hook (Opcional)

**Archivo:** `.git/hooks/pre-commit`

```bash
#!/bin/bash
echo "🔍 Quality Checks..."

./vendor/bin/pint --test || { echo "❌ Run: ./vendor/bin/pint"; exit 1; }
./vendor/bin/phpstan analyse || { echo "❌ Fix PHPStan errors"; exit 1; }

echo "✅ All checks passed!"
```

```bash
chmod +x .git/hooks/pre-commit
```

## Issues Comunes

```bash
# PHPStan sin memoria
./vendor/bin/phpstan analyse --memory-limit=2G

# Ver cambios de Pint antes de aplicar
./vendor/bin/pint --test && git diff
```

## Regla del Agente

**Antes de cada commit, recordar:**
1. `./vendor/bin/pint`
2. `./vendor/bin/phpstan analyse`
3. `php artisan test`
