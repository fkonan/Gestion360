# API SARLAFT

## Endpoints
- `POST /api/v1/consulta`
- `POST /api/v1/consulta/lote`
- `GET /api/v1/consulta/{consulta}`

## Seguridad
- Middleware `AutenticarSistemaConsumidor` (Bearer token en tabla de sistemas).
- Middleware `RateLimitSistema` (limite por sistema consumidor).

## Convenciones
- No usar Sanctum en este modulo.
- Mantener contratos de request/response de SARLAFT.
