# ZYRO — Spec as a Skill v2
Especificación actualizada para tienda académica de chompas con pedido, aprobación administrativa y pago simulado desde dos dispositivos.

## Stack fijo
Laravel 12 · Blade/CSS · MySQL · XAMPP · Eloquent · monolito MVC.

## Lectura obligatoria
1. `SKILL.md`
2. `spec/01-requirements.md`
3. `spec/02-architecture.md`
4. `spec/03-data-model.md`
5. `spec/04-order-payment-stock.md`
6. `spec/05-ui-ux.md`
7. `spec/06-security.md`
8. `spec/07-testing.md`
9. `spec/08-deployment.md`
10. `spec/09-one-day-plan.md`
11. `spec/TRACEABILITY.md`

## Objetivo
El cliente envía un pedido desde su dispositivo. El administrador lo ve desde otro dispositivo y lo aprueba o rechaza. Solo después de aprobarlo, el cliente puede realizar un pago ficticio. Si se aprueba el pago simulado, el pedido queda pagado, el dashboard refleja el ingreso simulado y el stock disminuye exactamente una vez.

La aplicación local y el flujo completo son prioritarios. El dashboard detectará cambios mediante polling cada 5–10 segundos; WebSockets son opcionales y no deben retrasar la entrega.

## Entorno local (XAMPP)

1. PHP 8.2+ y Composer. Iniciar Apache y MySQL en XAMPP.
2. Crear la base `zyro_store` en phpMyAdmin.
3. Copiar `.env.example` a `.env` si aún no existe. No publicar secretos.
4. Ajustar `DB_CONNECTION=mysql`, host, puerto, base, usuario y contraseña locales.
5. `composer install`
6. `php artisan key:generate` solo si falta `APP_KEY`.
7. `php artisan migrate --seed`
8. El seeder crea el administrador local (`ADMIN_EMAIL` / `ADMIN_PASSWORD` en `.env`). El registro público solo crea clientes.
9. `npm install` y `npm run build` si se usa Vite. Hay estilos de reserva si el manifiesto aún no existe.
10. `php artisan serve` o el VirtualHost de Apache.

El logotipo oficial no está en el repositorio. Reservar `public/images/brand/logo.svg`. Las imágenes de producto actuales son SVG provisionales en `public/images/products/`.
