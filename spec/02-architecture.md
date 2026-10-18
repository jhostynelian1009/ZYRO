# 02 — Arquitectura

## Decisión
Monolito MVC Laravel 12: cliente y administrador son dos áreas web de la misma aplicación. Comparten backend Laravel y MySQL. Blade para vistas; Eloquent para persistencia; XAMPP para desarrollo.

## Componentes
- `routes/web.php`
- Controllers: catálogo, carrito, pedidos/checkout, pago simulado, cuenta y admin.
- Models: `User`, `Product`, `Order`, `OrderItem`, `Payment`; `OrderStatusHistory` opcional.
- Services recomendados: `CheckoutService`, `OrderWorkflowService`, `SimulatedPaymentService`.
- Views: tienda, catálogo, carrito, checkout, cuenta, pedidos, pago, admin dashboard y detalle de pedido.
- Sesión para carrito; MySQL para pedidos, pagos y stock.

## Flujo
1. Cliente envía checkout.
2. Laravel recalcula precios y crea pedido `pending_approval` con líneas snapshot.
3. Admin ve el pedido desde su panel.
4. Admin aprueba (`approved`) o rechaza (`rejected`).
5. Solo pedido aprobado muestra pago simulado al cliente dueño.
6. En pago aprobado, Laravel bloquea pedido/productos, verifica stock y estado, registra pago, descuenta stock y marca `paid` en una transacción.
7. Dashboard consulta ingresos y stock actuales.

## Actualización del dashboard
P0: polling HTTP cada 5–10 segundos mientras el panel está abierto, más un botón de refresco manual. Proteger el endpoint con rol admin. No refrescar la página completa si es sencillo actualizar solo los datos.
P2: Broadcasting/WebSockets solo después de terminar y probar P0.

## Rutas orientativas
- `GET /`, `GET /productos`
- `GET /carrito`, `POST /carrito/agregar/{product}`, `PATCH /carrito/{product}`, `DELETE /carrito/{product}`
- `GET /checkout`, `POST /pedidos`
- `GET /mi-cuenta/pedidos`, `GET /mi-cuenta/pedidos/{order}`
- `GET /mi-cuenta/pedidos/{order}/pago`, `POST /mi-cuenta/pedidos/{order}/pago-simulado`
- `GET /admin`, `GET /admin/pedidos`, `GET /admin/pedidos/{order}`
- `POST /admin/pedidos/{order}/aprobar`, `POST /admin/pedidos/{order}/rechazar`
- `GET /admin/actualizaciones` (solo admin)

Adaptar nombres a las convenciones existentes. Usar `auth`, middleware de rol/Policy, CSRF, validación y rutas nombradas. No aceptar del navegador precios, total ni estado final.
