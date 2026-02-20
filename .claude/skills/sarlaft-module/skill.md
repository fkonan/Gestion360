---
name: sarlaft-module
description: Reglas y flujo de trabajo para cambios en el modulo SARLAFT integrado en Autogestion2.
keywords: sarlaft, aml, api, listas, cumplimiento
---

# SARLAFT Module Skill

## Cuando activar
- Cambios en `app/Modules/Sarlaft/*`
- Cambios de API en `/api/v1/consulta*`
- Cambios de sincronizacion de listas ONU/OFAC

## Fuente de contexto
1. `.ia-sarlaft/README.md`
2. `.ia-sarlaft/database.md`
3. `.ia-sarlaft/api.md`

## Reglas no negociables
- No usar Sanctum para API SARLAFT.
- Mantener middleware Bearer custom del modulo.
- Mantener tablas prefijadas `sarlaft_*`.
- Usar `App\\Models\\User` con owner key `IdUsuario`.
- Evitar acoplar cambios SARLAFT con `RadFact`.

## Comandos utiles
- `php artisan route:list --name=sarlaft`
- `php artisan route:list --path=api/v1/consulta`
- `php artisan migrate --database=mysql-sarlaft --path=app/Modules/Sarlaft/Database/Migrations --realpath --no-interaction`
- `php artisan list --raw | Select-String listas:sincronizar`
