# IA SARLAFT - Entry Point

Este directorio centraliza el contexto operativo del modulo SARLAFT integrado en `Autogestion2`.

## Orden de lectura recomendado
1. `contexto.md`
2. `arquitectura.md`
3. `database.md`
4. `api.md`
5. `convenios-autogestion.md`
6. `migracion-checklist.md`
7. `token-budget.md`

## Regla de oro
Para SARLAFT en este repositorio:
- No usar Sanctum para la API SARLAFT.
- Usar middleware Bearer custom del modulo.
- Persistir en tablas prefijadas `sarlaft_*`.
- Mantener compatibilidad con `App\\Models\\User` (`IdUsuario`).
