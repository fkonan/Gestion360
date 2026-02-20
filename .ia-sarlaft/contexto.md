# Contexto SARLAFT

- Producto: Sistema de consulta de listas vinculantes (AML/CFT).
- Ubicacion tecnica: `app/Modules/Sarlaft`.
- Integracion: dentro de `Autogestion2` (Laravel 12).

## Reglas criticas
- Las decisiones se hacen sobre datos locales sincronizados.
- Coincidencia relevante puede bloquear prestacion de servicio.
- Trazabilidad y auditoria son obligatorias.

## Modulos funcionales
- Sincronizacion de listas (ONU/OFAC).
- API de consulta (`/api/v1/consulta*`).
- Panel administrativo (`/sarlaft/*`).
- Alertas, bloqueos, lista negra, sistemas consumidores.
