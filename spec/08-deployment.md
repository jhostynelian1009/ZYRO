# 08 — Entorno local y despliegue opcional

## Local
1. Verificar PHP compatible con Laravel 12 y Composer.
2. Iniciar Apache/MySQL en XAMPP y crear `zyro_store` en phpMyAdmin.
3. Configurar `.env`: `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, puerto real (normalmente 3306), base, usuario y contraseña locales.
4. Ejecutar `composer install`.
5. `php artisan key:generate` solo si falta APP_KEY.
6. `php artisan migrate --seed`.
7. Ejecutar `php artisan serve` o configurar VirtualHost/Apache correctamente.
8. Si usa Vite, `npm install` y `npm run build`.

No sobrescribir `.env` ni publicar secretos.

## Probar dos dispositivos en LAN
Ambos dispositivos deben llegar al mismo servidor. `localhost` en el móvil apunta al móvil, no al PC. Obtener IP LAN del PC y, para una prueba local, ejecutar `php artisan serve --host=0.0.0.0 --port=8000` si procede; desde el otro dispositivo abrir `http://IP_LAN_DEL_PC:8000`. Verificar firewall y misma red. No exponer MySQL a la red; exponer solo el servidor web. Usar datos ficticios.

## Despliegue opcional
Solo después del flujo local. Laravel requiere runtime compatible y MySQL externo persistente; no asumir que un plan gratuito incluye MySQL administrado. Verificar condiciones actuales del proveedor. No declarar despliegue listo hasta probar pedido, aprobación, pago y persistencia en la URL publicada.
