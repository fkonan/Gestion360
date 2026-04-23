# Guía de Prueba — Consumo de Listas SARLAFT por Empresas

## Objetivo

Documentar el flujo mínimo para:

1. Sincronizar listas SARLAFT en el backend.
2. Probar (como empresa consumidora) la extracción de información por API:
   - descarga **completa** (bootstrap)
   - descarga por **novedades** (incremental por `punto_de_control`)

> Referencia técnica verificada en código:
> - Rutas API: `app/Modules/Sarlaft/Routes/api.php`
> - Request de exportación: `app/Modules/Sarlaft/Http/Requests/Api/ExportarListasRequest.php`
> - Controlador exportación: `app/Modules/Sarlaft/Http/Controllers/Api/ListaRegistroController.php`

---

## 0) Prerrequisitos

- Tener dependencias instaladas (`vendor/`) para poder ejecutar Artisan.
- Tener migraciones SARLAFT aplicadas en conexión `mysql-sarlaft`.
- Tener al menos un `SistemaConsumidor` activo con `api_token` válido.

---

## 1) Ejecutar sincronización de listas (lado SARLAFT)

### 1.1 Encolar sincronización

```bash
php artisan listas:sincronizar
```

Este comando ahora:

- sincroniza/crea listas desde `config/listas.php`
- restaura listas soft-deleted desde config
- encola jobs solo para listas activas

### 1.2 Procesar la cola

```bash
php artisan queue:work --queue=sincronizacion --tries=3 --timeout=300
```

> Si no corrés el worker, la sincronización queda encolada y no procesa.

---

## 2) Validar que sincronizó correctamente

### SQL sugerido

```sql
SELECT id, nombre, activa, ultima_sincronizacion
FROM sarlaft_listas_vinculantes
ORDER BY id DESC;

SELECT id, lista_id, estado, registros_procesados, registros_nuevos, registros_actualizados, registros_eliminados, created_at
FROM sarlaft_sincronizacion_logs
ORDER BY id DESC
LIMIT 20;
```

---

## 3) Obtener token del sistema consumidor

La API de SARLAFT usa **Bearer Token custom** (no Sanctum en este módulo).

- Middleware de auth: `AutenticarSistemaConsumidor`
- Middleware de rate limit por sistema: `RateLimitSistema`

Si necesitás recuperar token completo para pruebas:

```sql
SELECT id, nombre, codigo, api_token, estado, limite_requests_minuto
FROM sarlaft_sistemas_consumidores
WHERE codigo = 'empresa_demo';
```

> Seguridad: no compartir tokens por chat/correo sin canal seguro.

---

## 4) Endpoints de extracción para empresas

Base path:

```text
GET /api/v1/listas/registros
```

Query params (validados por `ExportarListasRequest`):

- `tipo_descarga`: `completa` | `novedades` (obligatorio)
- `punto_de_control`: entero >= 0 (obligatorio si `novedades`)
- `tamano_lote`: 1..5000 (obligatorio si `novedades`, default 1000)
- `lista_id`: opcional (filtra solo una lista vinculante)

Headers:

- `Authorization: Bearer <API_TOKEN>`
- `Accept: application/json`

---

## 5) Prueba en Postman (recomendada)

### 5.1 Variables de Environment

- `base_url` → `https://tu-dominio.com`
- `sarlaft_token` → token real del sistema consumidor
- `punto_control` → `0` (inicial)
- `tamano_lote` → `1000`

### 5.2 Request 1 — Descarga completa (bootstrap)

### Request

```http
GET {{base_url}}/api/v1/listas/registros?tipo_descarga=completa
Authorization: Bearer {{sarlaft_token}}
Accept: application/json
```

### Tests (Postman)

```javascript
pm.test('Status 200', function () {
  pm.response.to.have.status(200);
});

const body = pm.response.json();
const pc = body?.meta?.punto_de_control_actual ?? 0;
pm.environment.set('punto_control', String(pc));
```

### Qué guardar

- `meta.punto_de_control_actual` (cursor base para incrementales).

---

### 5.3 Request 2 — Novedades (incremental)

### Request

```http
GET {{base_url}}/api/v1/listas/registros?tipo_descarga=novedades&punto_de_control={{punto_control}}&tamano_lote={{tamano_lote}}
Authorization: Bearer {{sarlaft_token}}
Accept: application/json
```

### Tests (Postman)

```javascript
pm.test('Status 200', function () {
  pm.response.to.have.status(200);
});

const body = pm.response.json();
const next = body?.meta?.siguiente_punto_de_control;

if (typeof next === 'number') {
  pm.environment.set('punto_control', String(next));
}
```

### Interpretación de respuesta

- `meta.hay_mas = true`: volver a invocar inmediatamente con el nuevo `punto_control`.
- `meta.hay_mas = false`: lote completo consumido.

---

## 6) cURL de referencia

### 6.1 Completa

```bash
curl --request GET \
  --url "https://tu-dominio.com/api/v1/listas/registros?tipo_descarga=completa" \
  --header "Authorization: Bearer TU_TOKEN_EMPRESA" \
  --header "Accept: application/json"
```

### 6.2 Novedades

```bash
curl --request GET \
  --url "https://tu-dominio.com/api/v1/listas/registros?tipo_descarga=novedades&punto_de_control=12345&tamano_lote=1000" \
  --header "Authorization: Bearer TU_TOKEN_EMPRESA" \
  --header "Accept: application/json"
```

---

## 7) Contrato esperado (resumen)

### 7.1 `tipo_descarga=completa`

- `data.vinculantes[]` (resource `RegistroListaResource`)
- `data.lista_interna[]` (resource `ListaNegraInternaResource`)
- `meta.punto_de_control_actual`

### 7.2 `tipo_descarga=novedades`

- `data.novedades[]` con:
  - `id_novedad`
  - `origen_lista` (`vinculante`|`interna`)
  - `tipo_novedad` (`ingreso`|`actualizado`|`salida`)
  - `datos` (payload normalizado)
- `meta.siguiente_punto_de_control`
- `meta.hay_mas`

---

## 8) Errores esperados

- `401` → token faltante o inválido/inactivo.
- `422` → parámetros inválidos (`tipo_descarga`, `tamano_lote`, etc.).
- `429` → límite por minuto excedido (incluye `retry_after_seconds` y headers `X-RateLimit-*`).

---

## 9) Recomendación operativa para empresas

1. Ejecutar una sola vez `completa`.
2. Guardar `punto_de_control_actual`.
3. Ejecutar `novedades` periódicamente.
4. Aplicar cada evento (`ingreso`, `actualizado`, `salida`) de forma idempotente.
5. Actualizar cursor solo después de procesar exitosamente el lote.

---

## 10) Nota sobre `lista_id` en novedades

Si enviás `lista_id`, se filtra por `sarlaft_novedades_exportacion.lista_id`.

- útil para una lista vinculante puntual
- pero puede excluir novedades de lista interna (que tienen `lista_id = null`)

Usar con criterio según estrategia de integración.
