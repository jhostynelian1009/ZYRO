# Trazabilidad

| Requisito | Implementación | Prueba |
|---|---|---|
| 10 productos y búsqueda | Product, seeder, catálogo | 10 productos; búsqueda y estado vacío |
| Cuenta y roles | User/auth/middleware | cliente bloqueado en admin |
| Carrito y checkout | sesión/CheckoutService | cantidades, stock y total validados |
| Pedido pendiente | Order/OrderItem | estado `pending_approval` persistido |
| Aprobación/rechazo | acciones admin | transición y responsable correctos |
| Pago solo tras aprobación | SimulatedPaymentService | pedido pendiente no puede pagar |
| Ingreso simulado | query de pagos aprobados | rechazados/pendientes excluidos |
| Stock una sola vez | transacción/locks/idempotencia | repetir petición no duplica descuento |
| Dos dispositivos | servidor Laravel + MySQL compartidos | pedido visible desde sesión admin |
| Polling admin | endpoint protegido | datos se actualizan automáticamente |
| Seguridad | auth/Policies/CSRF/validación | accesos indebidos rechazados |
| Responsive/SEO | Blade/CSS/meta | revisión desktop/móvil y metadata |
