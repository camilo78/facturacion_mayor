FROM php:8.4-fpm-alpine

# Dependencias del sistema + extensiones PHP para Laravel
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

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instalar dependencias PHP (sin dev)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar toda la aplicación
COPY . .

# Crear directorios que Laravel necesita (excluidos del .dockerignore)
RUN mkdir -p bootstrap/cache \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs

# Autoloader optimizado
RUN composer dump-autoload --optimize --no-dev

# Permisos de Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY docker/auxiliar/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
