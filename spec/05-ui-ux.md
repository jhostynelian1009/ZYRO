# 05 — UI/UX y marca

## Identidad
ZYRO: monograma negro y palabra ZYRO del logo entregado. Buscar el archivo original en el repositorio; si no está, reservar espacio y documentar dónde copiarlo. Paleta: blanco `#FFFFFF`, gris `#F5F5F5`, texto `#111111`, secundario `#666666`, bordes `#E5E5E5`. Moda minimalista/editorial; H&M solo como referencia estructural, sin copiar sus activos.

## Tienda
Cabecera con logo, categorías, búsqueda, cuenta y carrito; hero editorial; grid de 10 chompas; tarjetas con imagen, nombre, precio, tallas y stock; footer con colecciones, información, ayuda y cuenta.

## Flujo cliente
Catálogo → carrito → login/registro → checkout → “Pedido enviado: pendiente de aprobación” → estado del pedido → tras aprobación, pago simulado → confirmación de pago aprobado o rechazo.
Nunca mostrar pedido pendiente como pagado.

## Dashboard admin
Tarjetas: pedidos pendientes, pedidos pagados, ingresos simulados, productos/stock. Tabla de pedidos recientes con número, cliente, total, estado y fecha. Detalle con artículos, cantidades, entrega y total. Acciones aprobar/rechazar solo en `pending_approval`. Mostrar historial/resultado de pago y stock actual. Etiqueta “Entorno académico / pagos simulados”. Actualización por polling y botón refrescar.

## UX
Confirmar antes de rechazar; bloquear botón mientras procesa; mensajes claros para stock insuficiente, estado inválido y pago rechazado; estados vacíos; labels accesibles; foco visible; contraste suficiente. No pedir datos bancarios reales. Mostrar desglose subtotal/impuesto/envío/total.

## Responsive/SEO
Prioridad escritorio, móvil usable; grid fluido, checkout en una columna en móvil, tablas adaptables. `title`, meta description, H1 semántico, alt de imágenes y enlaces descriptivos. Documentar keywords SEO/SEM sugeridas, sin afirmar que se compró publicidad. “Mapa de calor mental” es hipótesis de atención, no medición real.
