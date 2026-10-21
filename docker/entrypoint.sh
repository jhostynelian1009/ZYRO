#!/bin/bash
set -e

# ==============================================================================
# Script de inicio seguro para ZYRO en Render
# ==============================================================================

# 1. Configurar puerto dinámico asignado por Render (PORT env)
PORT="${PORT:-10000}"
echo "==> [ZYRO] Configurando Nginx para escuchar en puerto: $PORT..."
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/http.d/default.conf

# 2. Asegurar directorios de almacenamiento y permisos de ejecución
echo "==> [ZYRO] Verificando permisos de almacenamiento y bootstrap/cache..."
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/private/customizations/temp \
         /var/www/html/storage/app/public \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Enlace simbólico de storage para archivos públicos
echo "==> [ZYRO] Creando enlace simbólico storage:link..."
php artisan storage:link --force || true

# 4. Optimización de caché de Laravel en producción (si APP_KEY existe)
if [ -n "$APP_KEY" ]; then
    echo "==> [ZYRO] Optimizando configuración de Laravel para producción..."
    php artisan config:cache || echo "==> [AVISO] Falló config:cache, continuando con configuración dinámica."
    php artisan route:cache || echo "==> [AVISO] Falló route:cache, continuando con rutas dinámicas."
    php artisan view:cache || echo "==> [AVISO] Falló view:cache, continuando con vistas dinámicas."
else
    echo "==> [AVISO CRÍTICO] APP_KEY no está configurada en las variables de entorno."
    echo "==> [AVISO] Se omite el cacheo para evitar errores de arranque."
fi

# 5. Ejecutar migraciones si se solicita explícitamente vía RUN_MIGRATIONS=true
# (Nunca se ejecuta migrate:fresh ni seed automático en producción)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "==> [ZYRO] Variable RUN_MIGRATIONS=true detectada. Ejecutando migraciones pendientes..."
    php artisan migrate --force || echo "==> [AVISO] No se pudieron ejecutar migraciones automáticas. Verifica la conexión a la base de datos."
else
    echo "==> [ZYRO] RUN_MIGRATIONS no está activo. Omitiendo migraciones automáticas en arranque."
fi

# 6. Iniciar Supervisord (Nginx + PHP-FPM)
echo "==> [ZYRO] Arrancando Nginx y PHP-FPM mediante Supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
