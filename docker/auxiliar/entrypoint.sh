#!/usr/bin/env bash
# entrypoint.sh — Factunet Auxiliar
# Fixes: [7] bootstrap no-fatal  [9] permisos storage  [11] caché obsoleta

set -e

echo "=== Factunet Auxiliar — Inicio ==="

# [9] Re-aplicar permisos sobre el volumen (puede ser nuevo y estar owned por root)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# APP_KEY se genera en install-auxiliar.sh y queda en .env
# Este fallback cubre el caso de inicio manual sin pasar por el instalador.
# No escribe al .env porque esta montado :ro — solo persiste en esta sesion.
if [ -z "${APP_KEY:-}" ]; then
    echo "⚠  APP_KEY no definida en .env — generando para esta sesion"
    echo "   Actualiza .env con la clave generada y reinicia para que persista"
    APP_KEY=$(php artisan key:generate --show --no-ansi 2>/dev/null | tail -1)
    export APP_KEY
fi

# Esperar a que MariaDB responda (usa mariadb-client, no mysql)
echo "Esperando base de datos..."
until mariadb -h"${DB_HOST:-db}" \
              -u"${DB_USERNAME:-root}" \
              -p"${DB_PASSWORD:-secret}" \
              -e "SELECT 1" >/dev/null 2>&1; do
    sleep 2
done
echo "Base de datos lista."

# Migrar tablas centrales (landlord: tenants, instances, instance_tokens...)
echo "Migrando tablas centrales..."
php artisan migrate --force

# Migrar tablas de tenants ya existentes — idempotente
echo "Migrando tablas de tenants existentes..."
php artisan tenants:migrate --force 2>/dev/null || true

# [7] Bootstrap NO es fatal: si el Mayor no es alcanzable, la app levanta igual
# en modo degradado. El scheduler reintenta cada 5 min (routes/console.php).
echo "Ejecutando bootstrap de instancia..."
if ! php artisan instance:bootstrap; then
    echo "⚠  Bootstrap pendiente — Mayor no alcanzable o credenciales invalidas."
    echo "   La app inicia en modo degradado. El scheduler reintentara cada 5 min."
fi

# [11] Limpiar caches obsoletas antes de regenerar (evita 'manifest not found' en deploy)
php artisan optimize:clear
php artisan optimize

echo "=== Listo. Iniciando $* ==="
exec "$@"
