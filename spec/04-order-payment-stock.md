# 04 — Reglas de pedido, aprobación, pago e inventario

## Estados oficiales
- `pending_approval`: cliente envió; espera admin.
- `approved`: admin aprobó; habilita pago.
- `rejected`: admin rechazó.
- `paid`: pago simulado aprobado.
- `cancelled`: cancelado bajo reglas permitidas.
Un pago fallido se guarda en `payments.status=rejected`; se mantiene la orden `approved` para permitir reintento. No añadir `pending_payment` si `approved` ya representa “aprobado y esperando pago”.

## Crear pedido
1. Solo cliente autenticado.
2. Reconsultar productos y precios desde MySQL.
3. Validar stock, cantidades y productos activos.
4. Calcular subtotal, impuesto configurable y envío configurable en servidor.
5. Crear `orders` + `order_items` snapshot en transacción.
6. Estado inicial `pending_approval`.
7. No descontar stock ni registrar ingreso.
8. Vaciar carrito tras crear el pedido correctamente; volver a validar stock al pagar.

## Aprobar/rechazar
- Solo admin autenticado y autorizado.
- Solo transición válida desde `pending_approval`.
- Aprobar: `status=approved`, guardar `approved_by` y `approved_at`.
- Rechazar: `status=rejected`, guardar `rejected_by` y `rejected_at`.
- No modificar stock ni ingresos.
- Rechazar acciones repetidas o pedidos ya procesados con mensaje claro.

## Pago simulado
- Solo el dueño autenticado del pedido.
- Solo estado `approved`.
- Aviso persistente: “SIMULACIÓN ACADÉMICA: no se procesa dinero real”.
- Métodos: tarjeta de prueba, transferencia demo, billetera virtual demo.
- No pedir número de tarjeta, CVV, PIN ni credenciales.
- El servidor usa el total almacenado/recalculado y genera referencia ficticia.
- No confiar en `amount`, `success`, `status` o `approved` enviados por el navegador.

## Pago aprobado: transacción atómica
1. Bloquear la orden y filas de productos (`lockForUpdate` cuando aplique).
2. Confirmar que la orden sigue `approved` y no existe pago aprobado previo.
3. Confirmar stock suficiente para todas las líneas.
4. Si falta stock, rollback y mostrar aviso; no marcar como pagado.
5. Registrar pago aprobado con monto del servidor y referencia ficticia.
6. Descontar las cantidades del stock.
7. Marcar orden `paid` y guardar `paid_at`.
8. Registrar historial si existe.
9. Commit y mostrar confirmación.
Si falla cualquier paso, rollback. Repetir la petición no debe duplicar ingreso ni descuento.

## Pago rechazado
Guardar intento rechazado y referencia ficticia. No cambiar stock ni ingresos. Mantener orden `approved` para permitir reintento.

## Dashboard
- Pendientes: contar `pending_approval`.
- Pagados: contar `paid`.
- Ingresos simulados: sumar pagos `approved` de órdenes `paid`.
- Stock: consultar `products.stock`.
- Actividad: pedidos/aprobaciones/pagos recientes.
- Etiquetar “Ingresos simulados”, nunca saldo bancario/ingresos reales.

## Dos dispositivos
P0: polling cada 5–10 s y refresco manual. El cliente puede refrescar el estado de su pedido. El estado vive en MySQL, no en JavaScript ni en la sesión del cliente.
