# SKILL — ZYRO E-commerce Laravel 12

## Rol y objetivo
Actúa como ingeniero full-stack senior. Implementa ZYRO en un día con Laravel 12, Blade y MySQL/XAMPP. Lee toda la carpeta `spec/` antes de modificar código. La documentación es el contrato del proyecto.

## Límites técnicos
- Laravel 12, PHP compatible, Blade/CSS y MySQL.
- XAMPP para desarrollo local.
- Monolito MVC; no React, FastAPI, microservicios ni backend separado.
- Reutiliza el proyecto y los paquetes existentes; no recrees Laravel sin motivo.
- Pago exclusivamente simulado; nunca solicitar ni almacenar número real de tarjeta, CVV, claves bancarias o credenciales de billetera.
- No instales paquetes grandes sin necesidad.
- No implementes WebSockets hasta que el flujo P0 esté probado.

## Precedencia de especificaciones
1. `spec/01-requirements.md`
2. `spec/04-order-payment-stock.md`
3. `spec/06-security.md`
4. `spec/03-data-model.md`
5. `spec/02-architecture.md`
6. `spec/05-ui-ux.md`
7. `spec/07-testing.md`
8. `spec/08-deployment.md`
9. `spec/09-one-day-plan.md`
10. `spec/TRACEABILITY.md`

## Procedimiento
### 1. Inspeccionar
Revisa Git, `composer.json`, versiones reales de PHP/Laravel, rutas, autenticación, migraciones, modelos, vistas, estilos y pruebas. Comprueba si el logo ZYRO está en el repositorio. No sobrescribas `.env`, no borres datos y no ejecutes comandos destructivos sin verificar que la base sea descartable.

### 2. Planificar
Presenta un plan breve y los archivos principales a modificar; después continúa sin pedir confirmación entre cada paso, salvo bloqueo crítico o acción destructiva.

### 3. Implementar en vertical
1. Modelos, migraciones, roles y seeder de 10 productos.
2. Catálogo y búsqueda.
3. Autenticación cliente/admin.
4. Carrito y validación de stock.
5. Crear pedido `pending_approval`.
6. Panel admin para aprobar/rechazar.
7. Pago simulado habilitado solo tras aprobación.
8. Transacción de pago, ingreso simulado y stock.
9. Dashboard con pedidos, pagos e inventario.
10. Diseño ZYRO, responsive, footer y SEO.
11. Pruebas y README.

Mantén la aplicación ejecutable después de cada etapa. No generes pantallas desconectadas para integrarlas al final.

### 4. Verificar
Ejecuta las pruebas disponibles, `php artisan route:list`, `php artisan test` y compilación de assets si aplica. Corrige fallos y vuelve a probar. No declares pruebas no ejecutadas como exitosas.

### 5. Informe final
Indica lo implementado/pendiente, archivos cambiados, comandos de ejecución, pruebas y resultados reales, variables `.env` necesarias sin secretos, y estado del despliegue solo si se verificó.

## Reglas no negociables
- Crear pedido no equivale a aprobarlo ni pagarlo.
- Aprobar pedido no equivale a pago.
- Solo el dueño de un pedido aprobado puede pagar.
- Solo un pago simulado aprobado marca el pedido como `paid`, suma ingreso simulado y descuenta stock.
- Rechazo administrativo o pago rechazado no modifican stock ni ingresos.
- El servidor recalcula precios y totales desde MySQL.
- Pago, estado e inventario deben actualizarse en una transacción; evitar doble procesamiento.
- El cliente no puede acceder a administración ni a pedidos ajenos.
- Los estados y resultados no se aceptan como valores autoritativos desde el navegador.
- Etiquetar la pasarela como simulada.

## Diseño
ZYRO: negro, blanco y grises claros; minimalista, elegante, orientado a moda. H&M es referencia estructural, no se copian logo, fotos ni textos. Prioridad escritorio, soporte móvil correcto.

## Alcance
P0 obligatorio. Polling en lugar de WebSockets. Wishlist, cupones, analítica avanzada y despliegue cloud son opcionales.
