# 01 — Requisitos del producto

## Objetivo y actores
Tienda académica ZYRO con 10 chompas. Cliente y administrador usarán navegadores/dispositivos distintos, conectados al mismo Laravel y MySQL.
- **Visitante:** catálogo y búsqueda.
- **Cliente:** cuenta, carrito, pedidos propios y pago simulado de pedidos aprobados.
- **Administrador:** pedidos entrantes, aprobación/rechazo, ingreso simulado y stock.
El registro público siempre crea rol `customer`; nunca permitir autoasignación de admin.

## Requisitos P0
**RF-01 Catálogo:** 10 productos de demostración, imagen, nombre, descripción, precio, tallas/variantes y stock. Producto inactivo o sin stock no se compra.
**RF-02 Búsqueda:** buscar por nombre/descripción y mostrar estado vacío.
**RF-03 Cuenta:** registro, login/logout, correo único, contraseñas con hashing de Laravel, cuenta e historial propio.
**RF-04 Carrito:** añadir, cambiar cantidad y eliminar; sesión Laravel; validar cantidad/stock en servidor.
**RF-05 Checkout:** destinatario, teléfono, dirección, ciudad/cantón, modalidad de entrega y notas opcionales. Enviar crea pedido `pending_approval`, no pago. Mostrar número y total.
**RF-06 Administración:** listar pedidos pendientes y ver cliente, productos, cantidades, entrega, total y fecha. Admin aprueba/rechaza solo pedidos `pending_approval`. Guardar responsable y fecha. Rechazo no altera stock ni ingresos.
**RF-07 Pago:** tarjeta de prueba, transferencia demo y billetera demo, sin datos bancarios reales. Solo el propietario puede pagar cuando el pedido esté `approved`. Pago aprobado marca `paid`, guarda referencia ficticia, suma ingreso simulado y descuenta stock una sola vez. Pago rechazado no descuenta stock ni suma ingresos; puede reintentarse.
**RF-08 Dashboard:** pedidos pendientes, pedidos pagados, ingresos simulados calculados desde pagos aprobados y stock actual. Mostrar estados y pedidos recientes. Polling cada 5–10 s.
**RF-09 Multi-dispositivo:** el pedido creado en dispositivo A aparece en dispositivo B porque ambas sesiones usan el mismo servidor/base de datos; no depende de compartir sesión.

## P1 académico
Cabecera/cuerpo/footer, responsive, SEO básico, listado de keywords SEO/SEM, métodos de pago, impuestos, envío/plazos documentados, prevención de errores y pruebas de usabilidad.

## Fuera de alcance
Pagos reales, bancos, couriers reales, facturación electrónica, tracking real, correos transaccionales, campañas SEM reales, WebSockets obligatorios, wishlist completa, cupones y panel avanzado de roles.

## Aceptación
1. Cliente crea pedido y queda pendiente.
2. Admin lo ve desde otra sesión/dispositivo.
3. Admin aprueba/rechaza.
4. Pago solo disponible tras aprobación.
5. Pago aprobado persiste, aumenta ingreso simulado y reduce stock según cantidades.
6. Rechazos no cambian stock/ingresos.
7. Doble clic/reintento no duplica cobro simulado ni descuento.
8. Accesos y propiedad de pedidos protegidos.
9. Flujo local completo en Laravel/MySQL.
