# ConsultaService explicado para dummies

Este documento explica **como funciona** `ConsultaService` del modulo SARLAFT de forma simple, con ejemplos.

Archivo fuente principal:
- `app/Modules/Sarlaft/Services/ConsultaService.php`

---

## 1. Que hace este servicio

`ConsultaService` responde esta pregunta:

> "Con estos datos de una persona/empresa, se puede prestar el servicio o no?"

Cuando lo llamas:
1. Busca coincidencias en listas SARLAFT.
2. Calcula nivel de riesgo.
3. Guarda la consulta en base de datos.
4. Si encontro coincidencias, crea una alerta.
5. Devuelve el resultado final.

---

## 2. Que datos recibe

Metodo principal:

```php
ejecutar(array $datos, string $ip, string $sistemaOrigen): Consulta
```

`$datos` espera:
- `tipo_documento` (ej: `CC`, `NIT`, `CE`)
- `numero_documento`
- `nombre` (opcional, pero recomendado)

Tambien recibe:
- `ip` del origen
- `sistemaOrigen` (ej: `simulacion_pasaje`)

---

## 3. En que tablas consulta

### 3.1 Listas vinculantes sincronizadas

Tabla:
- `sarlaft_registros_lista`

Busca por:
- documento exacto (`identificacion`)
- nombre similar (`nombres`, `alias`)

### 3.2 Lista negra interna

Tabla:
- `sarlaft_lista_negra_interna`

Busca por:
- `tipo_documento` + `numero_documento` exacto

---

## 4. Tipos de coincidencia que genera

Cada match queda marcado con `tipo_coincidencia`:

- `documento_exacto`
- `nombre_similar`
- `lista_negra_interna`

Esto es clave porque el riesgo cambia segun el tipo.

---

## 5. Como busca por nombre (parte importante)

Para reducir falsos positivos, no hace un "like simple".

### Paso A: normaliza texto

Ejemplo:
- Entrada: `LucioHERNANDEZ LECHUGA`
- Normalizado: `LUCIO HERNANDEZ LECHUGA`

Tambien:
- quita acentos/simbolos
- pasa a mayuscula
- limpia espacios dobles

### Paso B: saca tokens significativos

Ignora conectores como:
- `DE`, `DEL`, `LA`, `Y`, etc.

Exige:
- minimo 2 tokens validos

### Paso C: fulltext boolean mode

Construye consulta tipo:
- `+PEDRO* +MARTINEZ* +FERNANDEZ*`

Y busca en `MATCH(nombres, alias)`.

### Paso D: calcula puntaje de similitud

Para cada candidato calcula:
- similitud de texto (`similar_text`)
- jaccard de tokens
- cobertura de tokens del nombre consultado

Luego saca un puntaje final (0 a 1).

Umbral minimo para aceptar coincidencia por nombre:
- `NAME_MIN_SCORE = 0.60`

---

## 6. Como calcula el riesgo

Metodo:
- `calcularNivelRiesgo(...)`

Reglas actuales:

1. Si ya esta en bloqueos (`sarlaft_bloqueos`) -> `alto`
2. Si no hay coincidencias -> `ninguno`
3. Si hay `lista_negra_interna` -> `alto`
4. Si hay `documento_exacto` en lista `vinculante/interna` -> `alto`
5. Si hay `nombre_similar` **muy fuerte** en lista `vinculante/interna` -> `alto`
6. Si no, y hay mas de una coincidencia -> `medio`
7. Si no, una sola coincidencia debil/moderada -> `bajo`

### Regla de "nombre_similar muy fuerte"

Para subir a `alto` por solo nombre, deben cumplirse TODAS:
- `puntaje_nombre >= 0.95`
- `tokens_coincidentes >= 3`
- `cobertura_nombre >= 1.0`
- lista tipo `vinculante` o `interna`

---

## 7. Como decide permitir o no la operacion

Campo final:
- `presta_servicio`

Formula:

```php
presta_servicio = !bloqueado && nivel_riesgo !== 'alto'
```

Interpretacion:
- `alto` => no permite
- `medio/bajo/ninguno` => permite (pero puede dejar alerta para revision)

---

## 8. Que guarda en BD

### Siempre guarda consulta

Tabla:
- `sarlaft_consultas`

Guarda entre otros:
- documento consultado
- nombre consultado
- `encontrado`
- `nivel_riesgo`
- `presta_servicio`
- `coincidencias` (json)

### Si encontro coincidencias, guarda alerta

Tabla:
- `sarlaft_alertas`

Tipo de alerta:
- `coincidencia_lista`

Estado inicial:
- `pendiente`

---

## 9. Ejemplos reales

### Caso 1: No esta en listas

Entrada:
- nombre: `fabian oswaldo hernandez`
- doc: `00000000`

Resultado esperado:
- coincidencias: `0`
- riesgo: `ninguno`
- presta_servicio: `true`

---

### Caso 2: Esta en lista vinculante sin identificacion

Entrada:
- nombre: `Pedro Orlando MARTINEZ FERNANDEZ`
- doc: cualquiera

Dato en BD:
- existe en `sarlaft_registros_lista` (ej id `9202`)
- `identificacion` vacia

Con regla actual:
- detecta `nombre_similar` fuerte
- riesgo: `alto`
- presta_servicio: `false`

---

### Caso 3: Nombre pegado

Entrada:
- `LucioHERNANDEZ LECHUGA`

Normalizacion:
- pasa a `LUCIO HERNANDEZ LECHUGA`

Eso evita que se pierda la coincidencia por formato.

---

## 10. Parametros que puedes ajustar (tuning)

En el servicio hay constantes para calibrar sensibilidad:

- `NAME_MIN_SCORE`:
  - baja: detecta mas cosas (mas falsos positivos)
  - alta: detecta menos cosas (mas falsos negativos)

- `NAME_HIGH_RISK_SCORE`:
  - define que tan "exacta" debe ser una coincidencia por nombre para bloquear

- `NAME_HIGH_RISK_MIN_MATCHED_TOKENS`:
  - cuantos tokens minimos deben coincidir para bloquear por nombre

- `NAME_HIGH_RISK_MIN_COVERAGE`:
  - que tanto del nombre consultado debe estar cubierto por el registro

Recomendacion practica:
- cambiar de a 1 parametro por vez
- probar con lote de nombres reales antes de pasar a produccion

---

## 11. Checklist rapido cuando "deja pasar" algo sospechoso

1. Revisar si el registro en listas esta `estado = activo`.
2. Revisar si el nombre de entrada trae errores de digitacion.
3. Revisar si `tipo_entidad` coincide:
   - `NIT` => organizacion
   - otros documentos => persona
4. Ver en `coincidencias` los campos:
   - `puntaje_nombre`
   - `tokens_coincidentes`
   - `cobertura_nombre`
5. Confirmar si realmente debia bloquear por politica o solo alertar.

---

## 12. Resumen ultra simple

`ConsultaService`:
- busca por documento y nombre
- calcula riesgo con reglas de negocio
- guarda consulta + alerta
- devuelve si se permite o no operar

Y ahora, para nombres comunes, **bloquea solo cuando la coincidencia por nombre es realmente fuerte**.
