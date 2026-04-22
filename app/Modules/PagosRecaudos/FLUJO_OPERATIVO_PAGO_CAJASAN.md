# Flujo operativo de pago Cajasan

## Objetivo
Describir de forma simple el proceso que debe seguir el cajero para realizar un pago de Cajasan, desde el ingreso al modulo hasta el cierre del caso.

## 1. Preparacion inicial
1. Ingresar al sistema con usuario autorizado.
2. Entrar al modulo **Pagos y Recaudos > Pago Convenio**.
3. Verificar que la caja activa este disponible.
4. Confirmar que el cliente presente un documento valido para consulta.

## 2. Consulta del cliente
1. Digitar la identificacion del cliente.
2. Ejecutar la consulta.
3. Validar el resultado:
- Si no existe el cliente o no tiene saldo, se informa y el proceso termina.
- Si hay saldo disponible, se continua con validacion del pago.

## 3. Confirmacion previa al pago
1. Revisar con el cliente los datos mostrados en pantalla.
2. Confirmar el valor a pagar.
3. Registrar el telefono de contacto si aplica.
4. Confirmar que se desea continuar con la operacion.

## 4. Ejecucion del pago
1. Presionar **Pagar** una sola vez.
2. Esperar respuesta del sistema.
3. Resultado esperado:
- Exito: el pago queda confirmado y se habilita la confirmacion final.
- Fallo: el sistema informa que no fue posible completar el pago.

## 5. Cierre del proceso en caso exitoso
1. Verificar que aparezca la confirmacion del pago.
2. Generar o descargar el recibo.
3. Entregar comprobante al cliente.
4. Finalizar la atencion.

## 6. Cierre del proceso en caso de fallo
1. Informar al cliente que el pago no se completo.
2. Si el mensaje indica reintento, solicitar que intente mas tarde.
3. Si el mensaje indica contacto con mesa de ayuda, registrar el numero de detalle y escalar el caso.
4. No entregar recibo como pago exitoso mientras no exista confirmacion final.

## 7. Reglas operativas clave
- No procesar pagos fuera de caja activa.
- No confirmar exito al cliente sin pantalla de confirmacion y comprobante.
- No repetir clics de pago ante lentitud; esperar respuesta del sistema.
- Ante error critico, usar el numero de detalle para trazabilidad y soporte.
- Mantener comunicacion clara con el cliente en todo momento.

## 8. Resultado final esperado
- Cada solicitud termina en uno de dos estados: **pagado** o **fallido**.
- Si el pago falla en un escenario incierto, el sistema maneja controles de compensacion y deja trazabilidad para soporte.
