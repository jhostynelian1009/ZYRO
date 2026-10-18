# 09 — Plan de ejecución en un día

1. **Inspección (15–20 min):** versiones, repo, auth, migraciones, estilos, logo y pruebas.
2. **Persistencia/catálogo (45–60 min):** modelos, migraciones, seeder de 10 productos, catálogo y búsqueda.
3. **Cuenta/roles/carrito (45–60 min):** login, rol admin seguro, middleware, carrito y stock.
4. **Pedido/admin (60–90 min):** checkout crea `pending_approval`; dashboard lista y permite aprobar/rechazar; polling simple.
5. **Pago/inventario (60–90 min):** solo pedido aprobado; transacción para pago, ingreso y stock; idempotencia.
6. **Diseño/requisitos académicos (45–60 min):** identidad ZYRO, responsive, footer, SEO y textos de envío/pago.
7. **QA/entrega (45–60 min):** prueba en dos sesiones/dispositivos, rechazos, tests, README y capturas.

## Recortes si falta tiempo
Mantener catálogo, auth/roles, carrito, pedido, aprobación, pago simulado, persistencia, stock e ingresos. Usar polling en vez de WebSockets. Omitir wishlist, cupones, analítica avanzada y despliegue cloud. Nunca sustituir persistencia por una pantalla falsa de éxito.
