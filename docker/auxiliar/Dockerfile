# ── Stage 1: Node 22 — compilar assets Vite / Tailwind CSS v4 ───────────────
FROM node:22-alpine AS assets

WORKDIR /app

# Instalar dependencias npm
COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

# Copiar fuentes para el build de Vite y el escaneo de clases de Tailwind
COPY vite.config.js ./
COPY public/ public/
COPY resources/ resources/

RUN npm run build

# ── Stage 2: PHP 8.4 FPM — imagen final del Auxiliar ────────────────────────
FROM php:8.4-fpm-alpine

# Extensiones del sistema + PHP necesarias para Laravel
RUN apk add --no-cache \
        bash \
        curl \
        git \
        mariadb-client \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
        pcntl \
        opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instalar dependencias PHP (sin dev, sin scripts aun — el autoloader se genera despues)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar el codigo fuente (.dockerignore excluye node_modules, .env, vendor)
COPY . .

# [8] Copiar los assets compilados en el stage Node (sobreescribe public/build/ si existia)
COPY --from=assets /app/public/build ./public/build

# Crear directorios de Laravel que el .dockerignore excluye del build context
RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs

# Autoloader optimizado
RUN composer dump-autoload --optimize --no-dev

# [9] Permisos base — el entrypoint los reaplica sobre el volumen en cada inicio
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/auxiliar/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
