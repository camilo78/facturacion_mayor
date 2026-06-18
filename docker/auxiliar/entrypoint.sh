#!/bin/bash
set -e

echo "=== Factunet Auxiliar — Inicio ==="

# Generar APP_KEY si no está definida
if [ -z "${APP_KEY}" ]; then
    echo "Generando APP_KEY..."
    php artisan key:generate --force
fi

# Esperar a que MariaDB esté lista
echo "Esperando base de datos..."
until mysql -h"${DB_HOST:-db}" \
            -u"${DB_USERNAME:-auxiliar}" \
            -p"${DB_PASSWORD:-secret}" \
            -e "SELECT 1" &>/dev/null 2>&1; do
    sleep 2
done
echo "Base de datos lista."

# Migrar tablas centrales (landlord: tenants, instances, instance_tokens...)
echo "Migrando tablas centrales..."
php artisan migrate --force

# Migrar tablas del tenant si ya existe
echo "Migrando tablas del tenant (si aplica)..."
php artisan tenants:migrate --force 2>/dev/null || true

# Bootstrap: registra el tenant local y descarga datos del Mayor (solo la primera vez)
echo "Ejecutando bootstrap de instancia..."
php artisan instance:bootstrap

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Listo. Iniciando $@ ==="
exec "$@"
