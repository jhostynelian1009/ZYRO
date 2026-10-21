# ==============================================================================
# PROYECTO: ZYRO — E-Commerce Premium
# Dockerfile para despliegue en Render (Nginx + PHP-FPM + Supervisord)
# ==============================================================================

# ------------------------------------------------------------------------------
# Etapa 1: Compilación de Frontend (Vite + Tailwind CSS v4)
# ------------------------------------------------------------------------------
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copiar manifiestos de Node para aprovechar caché de Docker
COPY package.json package-lock.json* ./

RUN npm ci

# Copiar código fuente necesario para escaneo de clases Tailwind y build de Vite
COPY . .

# Compilar assets para producción
RUN npm run build

# ------------------------------------------------------------------------------
# Etapa 2: Entorno de Ejecución PHP 8.2 + Nginx + Supervisord (Alpine)
# ------------------------------------------------------------------------------
FROM php:8.2-fpm-alpine

# Instalar utilidades del sistema, Nginx, Supervisord y librerías C
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        pcntl \
        exif \
    && rm -rf /var/cache/apk/*

# Copiar binario oficial de Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar dependencias de Composer primero para caché de capas
COPY composer.json composer.lock ./

# Instalar dependencias PHP de producción sin ejecutar scripts que dependan del entorno
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist --optimize-autoloader

# Copiar el código fuente del proyecto
COPY . .

# Copiar los assets de frontend compilados en la Etapa 1
COPY --from=frontend-builder /app/public/build ./public/build

# Copiar configuraciones de infraestructura
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Configuración final de autoloader, permisos y directorios de almacenamiento
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/sessions \
                storage/framework/views \
                storage/framework/cache/data \
                storage/logs \
                storage/app/private/customizations/temp \
                storage/app/public \
                bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Puerto predeterminado para Render (se adapta dinámicamente mediante la variable PORT)
EXPOSE 10000

# Script de arranque
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
