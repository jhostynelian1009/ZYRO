# 06 — Seguridad y autorización

## Autenticación/roles
Usar auth existente si es segura, hashing Laravel, CSRF y validación server-side. Público: catálogo. Cliente: cuenta, carrito, pedidos propios y pago propio aprobado. Admin: dashboard, todos los pedidos, aprobar/rechazar, ingresos y stock.
Registro público asigna siempre `customer`. Crear admin mediante seeder o instrucción local; nunca permitir autoasignación. Proteger `/admin` con middleware/Policy/Gate.

## Autorización de pedidos
Comprobar propiedad en cada ruta cliente; no basta con conocer el ID. Solo admin puede aprobar/rechazar. Solo orden `approved` del usuario dueño puede pagarse.

## Integridad
Servidor recalcula importes y define resultado final. No confiar en campos de estado/precio del navegador. Pago, transición a `paid` y stock deben ir en transacción con bloqueo y comprobación de estado. Stock no puede ser negativo. Prevenir doble procesamiento con estado y restricción única adecuada.

## Datos sensibles
No almacenar PAN, CVV, PIN, claves bancarias ni credenciales de billeteras. Guardar solo método demo, monto, estado, referencia ficticia y fecha. En producción `APP_DEBUG=false`; nunca mostrar secretos o stack traces.
