---
name: laravel-runner
description: Intelligent detection and selection of Laravel command runner (Sail vs host commands). Automatically adapts commands based on environment.
keywords: sail, docker, commands, environment, runner
---

# Laravel Runner Selection Skill

## Cuando Activar

- Al iniciar sesión en proyecto Laravel
- Antes de ejecutar CUALQUIER comando (artisan, composer, npm, etc.)
- Cuando el usuario cambia de entorno

## Detección Automática

### Check 1: ¿Sail Disponible?

```bash
# Verificar
[[ -f "vendor/bin/sail" ]] || [[ -f "sail" ]]
```

### Check 2: ¿Sail Corriendo?

```bash
# Si Sail disponible, verificar containers
sail ps | grep -q "Up"
```

### Check 3: Variables de Entorno

```bash
# Usar variables exportadas por SessionStart
$GENTLEMAN_HAS_SAIL
$GENTLEMAN_SAIL_RUNNING
```

## Decision Tree

```
¿Sail disponible?
├─ NO → Use host commands
│        php artisan, composer, npm
│
└─ SÍ → ¿Containers running?
         ├─ NO → Ask user:
         │       1. Start Sail (sail up -d)
         │       2. Use host commands
         │
         └─ SÍ → Use Sail commands
                  sail artisan, sail composer, sail npm
```

## Reglas de Comandos

### SIEMPRE usar Sail cuando:
- ✅ Sail está disponible
- ✅ Containers están corriendo
- ✅ Usuario no especifica lo contrario

### Comandos que van con Sail:

```bash
# PHP/Artisan
sail artisan migrate
sail artisan make:model Product
sail artisan test

# Composer
sail composer install
sail composer require package/name
sail composer dump-autoload

# NPM/Node
sail npm install
sail npm run dev
sail npm run build

# Database
sail mysql
sail psql

# Redis
sail redis-cli

# Tinker
sail tinker
```

### Comandos que NO van con Sail:

```bash
# Git (siempre host)
git status
git commit
git push

# Docker/Sail management
sail up -d
sail down
sail restart

# File operations (siempre host)
cat file.php
ls -la
mkdir app/Actions
```

## Patrón de Pregunta

Cuando Sail disponible pero NO corriendo:

```
I noticed Sail is available but containers aren't running.

Would you like to:
A) Start Sail now (sail up -d) and use containerized environment
B) Use host commands (php, composer, npm from your machine)

Which option do you prefer? I recommend Option A for consistency.
```

## Mensajes de Confirmación

### Antes de ejecutar comandos sensibles:

```bash
# Migraciones
⚠️  About to run: sail artisan migrate
This will modify your database.
Continue? [y/N]

# Composer update
⚠️  About to run: sail composer update
This may change package versions.
Continue? [y/N]

# Database reset
⚠️  About to run: sail artisan migrate:fresh --seed
This will DROP ALL TABLES and re-seed.
Continue? [y/N]
```

## Ejemplos de Uso

### Escenario 1: Sail Running

**Usuario:** "Run migrations"

**Agent:**
```bash
# Detected: Sail is running
sail artisan migrate
```

### Escenario 2: Sail Available but Not Running

**Usuario:** "Install dependencies"

**Agent:**
```
I see Sail is available but not running.

Option A: Start Sail first (RECOMMENDED)
  sail up -d
  sail composer install
  
Option B: Use host commands
  composer install
  
Which do you prefer? Option A ensures consistency with Docker environment.
```

### Escenario 3: No Sail

**Usuario:** "Run migrations"

**Agent:**
```bash
# No Sail detected, using host commands
php artisan migrate
```

### Escenario 4: Mixing Commands

**Usuario:** "Commit changes and run tests"

**Agent:**
```bash
# Git: always host
git add .
git commit -m "Add feature"

# Tests: use Sail if running
sail test

# Or if Sail not running:
php artisan test
```

## Portable Sail Alias

Recomendar al usuario crear alias:

```bash
# Add to ~/.bashrc or ~/.zshrc
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

Esto permite usar `sail` sin preocuparse por la ubicación.

## Advertencias Importantes

### ⚠️ NUNCA mezclar host y Sail

```bash
# ❌ MAL - Mixing host and Sail
composer install          # Host
sail npm install         # Sail

# Resultado: Dependencies en lugares diferentes, errores
```

```bash
# ✅ BIEN - Consistent runner
sail composer install
sail npm install

# O todo host:
composer install
npm install
```

### ⚠️ Environment Drift

Si usuario instala algo en host y luego usa Sail:

```
⚠️  WARNING: I notice you ran 'composer install' on host earlier,
but now using Sail commands.

This can cause environment drift. Recommend:
1. Remove host vendor/: rm -rf vendor
2. Install via Sail: sail composer install
```

## Verificación de Consistencia

Antes de comandos importantes, verificar:

```bash
# Check if mixing environments
if has_vendor_on_host && using_sail; then
    warn "Mixed environments detected"
    suggest "sail composer install to sync"
fi
```

## Variables de Sesión

Mantener track del runner usado:

```bash
export GENTLEMAN_RUNNER="sail"  # or "host"
export GENTLEMAN_LAST_COMMAND="sail artisan migrate"
```

## Comandos Especiales

### Database Commands

```bash
# Con Sail
sail mysql -u root -p
sail psql -U sail

# Sin Sail
mysql -u root -p
psql -U postgres
```

### Queue Workers

```bash
# Con Sail
sail artisan queue:work

# Sin Sail
php artisan queue:work
```

### Horizon

```bash
# Con Sail
sail artisan horizon

# Sin Sail
php artisan horizon
```

## Troubleshooting

### Sail no responde

```
Issue: sail commands hang
Solution: 
1. Check containers: sail ps
2. Restart: sail restart
3. If fails: sail down && sail up -d
```

### Port conflicts

```
Issue: Sail can't start (port 80 in use)
Solution:
1. Check docker-compose.yml ports
2. Change APP_PORT in .env
3. Or stop conflicting service
```

## Checklist de Ejecución

Antes de ejecutar CUALQUIER comando:

- [ ] ¿Es comando de Git? → SIEMPRE host
- [ ] ¿Es comando de Sail management? → SIEMPRE host
- [ ] ¿Es comando PHP/Composer/NPM/DB?
  - [ ] ¿Sail disponible y corriendo? → Sail
  - [ ] ¿Sail disponible pero NO corriendo? → Preguntar
  - [ ] ¿No Sail? → Host
- [ ] ¿Comando es sensible (migrate, seed)? → Confirmar

## Resumen

**Golden Rules:**
1. Detectar entorno ANTES de ejecutar
2. Ser consistente en la sesión
3. Preguntar cuando hay ambigüedad
4. NUNCA mezclar host y Sail para dependencias
5. Git siempre en host
