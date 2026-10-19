# 07 — Pruebas y aceptación

## Flujo obligatorio con dos sesiones/dispositivos
1. Cliente A inicia sesión y crea pedido con dos productos.
2. Verificar pedido `pending_approval`.
3. Admin B ve el pedido desde su panel.
4. Admin lo aprueba.
5. Cliente A ve el estado aprobado y el pago disponible.
6. Simular pago aprobado.
7. Verificar pedido `paid`, pago persistido, ingresos simulados aumentados y stock reducido por las cantidades.
8. Repetir/recargar la petición: no debe haber doble descuento ni doble ingreso.

## Rechazos
- Pedido rechazado por admin: cliente no puede pagar; stock e ingresos sin cambios.
- Pago simulado rechazado: no cambia stock ni ingresos; puede reintentarse.

## Seguridad
Cliente no entra a `/admin`, no consulta pedidos ajenos, no paga pedido pendiente, no modifica total/estado desde el navegador y no se registra como admin. No se solicitan datos bancarios reales.

## Catálogo/carrito
Seeder de 10 productos, búsqueda con/sin coincidencias, cantidad no positiva rechazada, cantidad superior a stock rechazada, producto inactivo no comprable, precios obtenidos de base de datos.

## UI
Revisar escritorio (~1366 px) y viewport móvil; header, catálogo, carrito, checkout, admin, footer, mensajes, labels y alt.

## Carga y evidencia
Solo probar carga si P0 está estable. Registrar herramienta, usuarios, duración, latencia y errores. No inventar resultados. Cada prueba debe registrar ID, precondiciones, pasos, esperado, resultado real, estado y captura si existe.

## Comandos
`php artisan test`, `php artisan route:list`, `php artisan migrate --seed` sobre base de desarrollo y `npm run build` si usa Vite. No usar `migrate:fresh` sobre datos que no sean descartables.
