# Guia tecnica de replicacion - Pago Cajasan

## Objetivo
Definir la logica funcional y transaccional que otro sistema debe replicar para implementar el proceso de pago Cajasan con comportamiento equivalente.

Este documento describe **que debe pasar** y **en que orden**, sin depender del codigo actual.

## 1. Alcance del proceso
El proceso cubre:
1. Consulta de saldo del cliente.
2. Retiro (pago) contra proveedor externo.
3. Reverso en escenarios de fallo incierto.
4. Persistencia local contable y operativa.
5. Trazabilidad para soporte y auditoria.

## 2. Entidades persistentes que soportan la logica
- `CON_CARGUEPAGOSYRECAUDOS`: cabecera de cargue diario del proceso.
- `CON_DETCARGUEPAGOSYRECAUDOS`: detalle principal de cada intento de pago.
- `CON_COMPROBANTES`: cabecera contable del comprobante.
- `CON_DETALLEPAGORECAUDO`: relacion del pago con el comprobante.
- `CON_AUXCOMPROBANTES`: movimientos contables debito/credito.
- `TES_CAJATURNODOCUMENTOS`: movimiento de caja del turno.
- `CON_REVERSO_CAJASAN`: bitacora de intentos y resultados de reverso.

## 3. Reglas de negocio que se deben conservar
1. Sin caja activa no se puede consultar ni pagar.
2. Sin saldo disponible no se puede pagar.
3. Todo intento de pago crea primero registro local base.
4. El exito al usuario solo se confirma despues de cerrar persistencia local contable.
5. Un pago fallido debe terminar en estado local anulado.
6. Si el estado del retiro es incierto (ej. timeout), se debe intentar reverso.
7. Todo reverso intentado debe quedar auditado.

## 4. Identificadores y correlacion
Para trazabilidad y conciliacion se debe conservar:
- `detalle_id` local del pago.
- `transactionId` y `sequenceId` del proveedor.
- `authorizationRspCode` de proveedor cuando exista.
- `request_id` de trazabilidad tecnica.

Regla de correlacion principal:
- El `detalle_id` local debe mapear de forma estable con `transactionId/sequenceId`.

## 5. Flujo logico por fases

## Fase A - Precondiciones
1. Validar usuario autenticado.
2. Validar caja activa asociada al usuario.
3. Resolver sucursal y ubicacion operativa.

## Fase B - Consulta
1. Validar existencia y vigencia del cliente.
2. Consultar saldo al proveedor externo.
3. Si saldo <= 0 o respuesta invalida, terminar con rechazo controlado.
4. Si saldo > 0, continuar a fase de pago.

## Fase C - Registro base local (antes de retiro)
1. Crear u obtener cargue diario.
2. Crear detalle de pago en estado inicial.
3. Persistir datos minimos para trazabilidad del intento.

Resultado esperado:
- Ya existe evidencia local del intento antes de afectar dinero.

## Fase D - Retiro en proveedor
1. Enviar solicitud de retiro con identificadores unicos.
2. Clasificar respuesta en tres categorias:
- Exito confirmado.
- Fallo confirmado.
- Fallo incierto (ej. timeout/caida de red sin certeza de estado remoto).

## Fase E - Resolucion
Si retiro es exito confirmado:
1. Abrir bloque transaccional local.
2. Marcar detalle como pagado.
3. Crear/actualizar comprobante.
4. Crear detalle contable y auxiliares debito/credito.
5. Registrar movimiento de caja turno.
6. Marcar cargue como pagado.
7. Confirmar transaccion y exponer recibo.

Si retiro es fallo confirmado:
1. Marcar detalle local como anulado.
2. Registrar mensaje de fallo.
3. No generar comprobante contable final.

Si retiro es fallo incierto:
1. Marcar detalle local como anulado.
2. Intentar reverso contra proveedor.
3. Registrar resultado del reverso en bitacora.
4. Retornar mensaje controlado al usuario y datos para soporte.

## 6. Maquina de estados minima
Estado en `CON_DETCARGUEPAGOSYRECAUDOS`:
- `C`: creado / pendiente de resolucion final.
- `P`: pagado confirmado y contabilizado.
- `A`: anulado/fallido.

Transiciones validas:
1. `C -> P` (retiro exitoso + persistencia local exitosa).
2. `C -> A` (retiro fallido o estado incierto).

No se debe permitir:
- Confirmar exito sin pasar por persistencia contable completa.
- Volver a `C` desde `P` o `A`.

## 7. Reverso y compensacion
Un reverso debe ejecutarse cuando el retiro queda en estado incierto.

Bitacora de reverso (`CON_REVERSO_CAJASAN`) debe guardar como minimo:
- identificadores de transaccion,
- datos usados en reverso,
- resultado (`response_code`),
- codigo de autorizacion,
- error tecnico/funcional,
- payload de soporte.

Si reverso falla:
- marcar caso para atencion manual,
- mantener evidencia completa para mesa de ayuda/conciliacion.

## 8. Consistencia transaccional
Reglas para replicar:
1. Separar claramente:
- etapa externa (retiro/reverso proveedor),
- etapa interna (contabilidad y caja local).
2. La etapa interna de exito debe ejecutarse en una sola transaccion DB.
3. Si falla persistencia local despues de retiro exitoso, activar compensacion (reverso) y auditar.

## 9. Concurrencia e idempotencia
Minimo requerido:
1. Bloqueo por intento de pago (evitar doble ejecucion del mismo contexto).
2. Proteccion de secciones criticas para generacion de consecutivos y escrituras sensibles.
3. Rechazo controlado de reenvios simultaneos del mismo pago.

## 10. Criterios de equivalencia funcional (para validar replicacion)
1. Pago exitoso:
- termina en estado `P`,
- genera comprobante y recibo,
- deja trazabilidad completa.
2. Pago fallido:
- termina en estado `A`,
- no confirma recibo exitoso.
3. Timeout/fallo incierto:
- intenta reverso,
- registra bitacora de reverso,
- retorna salida controlada para soporte.

## 11. Checklist de salida a produccion
1. Modo proveedor real habilitado para produccion.
2. Logs con correlacion (`request_id`, `detalle_id`, `transactionId`, `sequenceId`).
3. Sin exposicion de secretos en logs.
4. Manejo de errores funcionales y tecnicos diferenciado.
5. Pruebas de:
- exito,
- fallo confirmado,
- timeout con reverso exitoso,
- timeout con reverso fallido.
