#!/usr/bin/env bash
# entrypoint.sh — Factunet Auxiliar
# Fixes: [7] bootstrap no-fatal  [9] permisos storage  [11] caché obsoleta
#        [12] bootstrap detecta BD vacía via InstanceBootstrap (no .env)
#        [13] RolesPermisosSeeder corre dentro del bootstrap

set -e

echo "=== Factunet Auxiliar — Inicio ==="

# [9] Re-aplicar permisos sobre el volumen (puede estar owned por root en arranque limpio)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# APP_KEY viene del .env generado por install-auxiliar.sh.
# Este fallback cubre inicio manual sin el instalador; no persiste al reiniciar
# porque el .env está montado :ro en el contenedor.
if [ -z "${APP_KEY:-}" ]; then
    echo "⚠  APP_KEY no definida en .env — generando para esta sesion"
    echo "   Ejecuta install-auxiliar.sh para fijarla en .env de forma permanente"
    APP_KEY=$(php artisan key:generate --show --no-ansi 2>/dev/null | tail -1)
    export APP_KEY
fi

# Esperar a que MariaDB responda
echo "Esperando base de datos..."
until mariadb -h"${DB_HOST:-db}" \
              -u"${DB_USERNAME:-auxiliar}" \
              -p"${DB_PASSWORD:-secret}" \
              -e "SELECT 1" >/dev/null 2>&1; do
    sleep 2
done
echo "Base de datos lista."

# Migrar tablas centrales (landlord: tenants, instances, instance_tokens, cache...)
echo "Migrando tablas centrales..."
php artisan migrate --force

# Migrar tablas de tenants ya existentes — idempotente
echo "Migrando tablas de tenants existentes..."
php artisan tenants:migrate --force 2>/dev/null || true

# Bootstrap de instancia:
#   - Si el Mayor es alcanzable Y la BD del tenant está vacía → pull completo + seed permisos
#   - Si el Mayor NO es alcanzable → la app levanta en modo degradado; el scheduler reintenta
#   - Si ya tiene datos → retorna rápido ("Instancia ya inicializada")
#   [12] InstanceBootstrap detecta BD vacía por estado real, no por .env
#   [13] InstanceBootstrap corre RolesPermisosSeeder dentro del tenant antes del pull
echo "Ejecutando bootstrap de instancia..."
if php artisan instance:bootstrap; then
    echo "✓ Bootstrap completado."
else
    echo "⚠  Bootstrap pendiente — Mayor no alcanzable o credenciales invalidas."
    echo "   La app inicia en modo degradado. El scheduler reintentara cada 5 min."
    echo "   Login NO funcionará hasta que el bootstrap complete exitosamente."
fi

# [11] Limpiar caches obsoletas antes de regenerar
php artisan optimize:clear
php artisan optimize

echo "=== Listo. Iniciando $* ==="
exec "$@"
