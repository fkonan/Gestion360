# Convenios con Autogestion2

## Usuario/autenticacion
- Modelo de usuario corporativo: `App\\Models\\User`.
- PK real: `IdUsuario` (no `id`).
- En SARLAFT usar `auth()->id()` para `creado_por` / `atendida_por`.

## UI
- SARLAFT usa rutas web bajo `/sarlaft` y nombres `sarlaft.*`.
- Vistas namespaced: `sarlaft::admin.*`.

## Operacion segura
- No tocar `RadFact` durante cambios SARLAFT.
- Evitar cambios transversales no necesarios fuera del modulo.
