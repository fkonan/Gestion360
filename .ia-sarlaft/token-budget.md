# Token budget y contexto

## Regla practica
No todos los `.md` se cargan en cada consulta. El costo depende del agente/herramienta y de que archivos inyecte.

## Estimacion rapida
- 1 token ~= 4 caracteres (aprox).
- Costo de entrada por consulta = sistema + prompt usuario + contexto inyectado + fragmentos de codigo.

## Recomendaciones
- Cargar solo contexto SARLAFT cuando la tarea sea del modulo (`.ia-sarlaft/*`).
- Evitar duplicar instrucciones equivalentes en multiples `.md`.
- Mantener un entrypoint (`README.md`) y enlazar desde globales.
- Para tareas generales de Autogestion, no cargar docs SARLAFT completas.
