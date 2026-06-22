#!/usr/bin/env bash
# =============================================================================
#  Factunet — Instalador del Nodo Auxiliar
#  Versión 2.0
#
#  Uso:
#    1. Clona el repositorio:  git clone <URL> factunet-auxiliar && cd factunet-auxiliar
#    2. Ejecuta el instalador: bash install-auxiliar.sh
#
#  Si la instalación se interrumpe, vuelve a ejecutar el mismo comando
#  y el script retomará desde donde quedó.
#
#  Para reiniciar desde cero:  bash install-auxiliar.sh --reset
# =============================================================================
set -euo pipefail

# ── Colores ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
NC='\033[0m'

# ── Helpers ───────────────────────────────────────────────────────────────────
info()    { echo -e "${BLUE}ℹ${NC}  $*"; }
ok()      { echo -e "${GREEN}✔${NC}  $*"; }
warn()    { echo -e "${YELLOW}⚠${NC}  $*"; }
error()   { echo -e "${RED}✖${NC}  $*" >&2; }
fatal()   { error "$*"; exit 1; }
step()    { echo -e "\n${BOLD}${CYAN}▶ $*${NC}"; }
divider() { echo -e "${DIM}────────────────────────────────────────────────────────────────${NC}"; }

ask() {
    local prompt="$1" default="${2:-}" var
    if [[ -n "$default" ]]; then
        read -rp "$(echo -e "  ${BOLD}$prompt${NC} ${DIM}[$default]${NC}: ")" var
        echo "${var:-$default}"
    else
        while true; do
            read -rp "$(echo -e "  ${BOLD}$prompt${NC}: ")" var
            [[ -n "$var" ]] && echo "$var" && break
            warn "Este campo es obligatorio."
        done
    fi
}

ask_secret() {
    local prompt="$1" var
    while true; do
        read -rsp "$(echo -e "  ${BOLD}$prompt${NC}: ")" var; echo
        [[ -n "$var" ]] && echo "$var" && break
        warn "Este campo es obligatorio."
    done
}

ask_yes() {
    local prompt="$1" default="${2:-s}" resp
    read -rp "$(echo -e "  ${BOLD}$prompt${NC} ${DIM}[S/n]${NC}: ")" resp
    resp="${resp:-$default}"
    [[ "${resp,,}" == "s" || "${resp,,}" == "si" || "${resp,,}" == "y" || "${resp,,}" == "yes" ]]
}

# ── Directorio de trabajo ─────────────────────────────────────────────────────
# El script siempre opera desde el directorio donde reside
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# ── Progreso de instalación ───────────────────────────────────────────────────
# Permite reanudar si el script se interrumpe
STATE_FILE="$SCRIPT_DIR/.install_progress"

mark_done() { grep -qxF "$1" "$STATE_FILE" 2>/dev/null || echo "$1" >> "$STATE_FILE"; }
is_done()   { grep -qxF "$1" "$STATE_FILE" 2>/dev/null; }

# ── Bandera --reset ───────────────────────────────────────────────────────────
if [[ "${1:-}" == "--reset" ]]; then
    warn "Modo reset: se borrará el progreso guardado y se reconfigurará desde cero."
    rm -f "$STATE_FILE"
    info "Progreso eliminado. El .env existente será consultado durante la instalación."
    echo
fi

# ── Banner ────────────────────────────────────────────────────────────────────
clear
echo -e "${BOLD}${BLUE}"
cat << 'BANNER'
  ███████╗ █████╗  ██████╗████████╗██╗   ██╗███╗   ██╗███████╗████████╗
  ██╔════╝██╔══██╗██╔════╝╚══██╔══╝██║   ██║████╗  ██║██╔════╝╚══██╔══╝
  █████╗  ███████║██║        ██║   ██║   ██║██╔██╗ ██║█████╗     ██║
  ██╔══╝  ██╔══██║██║        ██║   ██║   ██║██║╚██╗██║██╔══╝     ██║
  ██║     ██║  ██║╚██████╗   ██║   ╚██████╔╝██║ ╚████║███████╗   ██║
  ╚═╝     ╚═╝  ╚═╝ ╚═════╝   ╚═╝    ╚═════╝ ╚═╝  ╚═══╝╚══════╝   ╚═╝
BANNER
echo -e "${NC}"
echo -e "  ${BOLD}Instalador del Nodo Auxiliar${NC}  ${DIM}v2.0${NC}"
divider
echo -e "  Directorio de instalación: ${BOLD}$SCRIPT_DIR${NC}"
echo
echo -e "  ${DIM}Si la instalación fue interrumpida, este script retomará"
echo -e "  desde el último paso completado.${NC}"
echo -e "  ${DIM}Para reinstalar desde cero: bash install-auxiliar.sh --reset${NC}"
divider
echo

# ── PASO 1: Verificar prerequisitos ──────────────────────────────────────────
step "PASO 1: Verificando prerequisitos del sistema"

MISSING=()

command -v docker &>/dev/null || MISSING+=("docker")

if docker compose version &>/dev/null 2>&1; then
    COMPOSE_CMD="docker compose"
elif command -v docker-compose &>/dev/null; then
    COMPOSE_CMD="docker-compose"
else
    MISSING+=("docker-compose")
fi

command -v curl    &>/dev/null || MISSING+=("curl")
command -v openssl &>/dev/null || MISSING+=("openssl")

if [[ ${#MISSING[@]} -gt 0 ]]; then
    error "Faltan los siguientes programas: ${MISSING[*]}"
    echo
    echo -e "  ${DIM}Instálalos con:${NC}"
    echo -e "  ${DIM}  Ubuntu/Debian: sudo apt install docker.io docker-compose-plugin curl openssl${NC}"
    echo -e "  ${DIM}  CentOS/RHEL:   sudo yum install docker docker-compose-plugin curl openssl${NC}"
    exit 1
fi

if ! docker info &>/dev/null; then
    fatal "Docker no está en ejecución. Inícialo con: sudo systemctl start docker"
fi

ok "docker         $(docker --version | awk '{print $3}' | tr -d ',')"
ok "docker compose disponible ($COMPOSE_CMD)"
ok "curl / openssl disponibles"
echo

# Verificar que el script está dentro del repositorio correcto
COMPOSE_FILE="docker-compose.auxiliar.yml"
if [[ ! -f "$COMPOSE_FILE" ]]; then
    fatal "No se encontró '$COMPOSE_FILE' en $SCRIPT_DIR\n  Asegúrate de ejecutar este script desde la raíz del repositorio clonado."
fi

ok "Repositorio detectado en $SCRIPT_DIR"
echo

# ── PASO 2: Configuración del .env ───────────────────────────────────────────
step "PASO 2: Configuración del entorno (.env)"
divider

if is_done "env_configured" && [[ -f .env ]]; then
    ok ".env ya configurado (paso completado previamente)."
    ENV_SKIP=true
elif [[ -f .env ]]; then
    warn "Ya existe un archivo .env."
    if ask_yes "¿Deseas reconfigurarlo?"; then
        cp .env ".env.bak.$(date +%Y%m%d_%H%M%S)"
        info "Respaldo guardado."
        ENV_SKIP=false
    else
        info "Se mantendrá el .env existente."
        mark_done "env_configured"
        ENV_SKIP=true
    fi
else
    [[ -f .env.example ]] && cp .env.example .env || touch .env
    ENV_SKIP=false
fi

if [[ "$ENV_SKIP" == "false" ]]; then
    echo
    echo -e "  ${DIM}Completa cada campo. Los valores entre [corchetes] son el valor por defecto.${NC}"
    echo

    echo -e "  ${BOLD}--- Aplicación ---${NC}"
    APP_NAME=$(ask "Nombre de la app (APP_NAME)" "Factunet Auxiliar")
    APP_URL=$(ask "URL pública del auxiliar (APP_URL, ej: http://192.168.1.10)" "http://localhost")
    APP_PORT=$(ask "Puerto HTTP del auxiliar (APP_PORT)" "80")

    echo
    echo -e "  ${BOLD}--- Base de datos ---${NC}"
    DB_DATABASE=$(ask "Nombre de la base de datos (DB_DATABASE)" "auxiliar_landlord")
    DB_USERNAME=$(ask "Usuario de la base de datos (DB_USERNAME)" "auxiliar")
    DB_PASSWORD=$(ask_secret "Contraseña del usuario DB (DB_PASSWORD)")
    DB_ROOT_PASSWORD=$(ask_secret "Contraseña root de MariaDB (DB_ROOT_PASSWORD)")

    echo
    echo -e "  ${BOLD}--- Conexión con el Mayor ---${NC}"
    echo -e "  ${DIM}Necesitas haber ejecutado en el Mayor:${NC}"
    echo -e "  ${DIM}  php artisan instance:registrar --tipo=auxiliar${NC}"
    echo

    MAYOR_SYNC_URL=$(ask "URL del Mayor (MAYOR_SYNC_URL, ej: https://factunet.io)" "")
    INSTANCE_UUID=$(ask "UUID de esta instancia (INSTANCE_UUID del Mayor)" "")
    MAYOR_SYNC_TOKEN=$(ask_secret "Token de sincronización (MAYOR_SYNC_TOKEN del Mayor)")
    INSTANCE_LABEL=$(ask "Nombre descriptivo de esta instancia (INSTANCE_LABEL)" "Auxiliar")

    echo
    echo -e "  ${BOLD}--- Seguridad ---${NC}"
    info "Generando APP_KEY aleatoria..."
    APP_KEY="base64:$(openssl rand -base64 32)"
    ok "APP_KEY generada."

    cat > .env <<EOF
APP_NAME="${APP_NAME}"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=${APP_URL}
APP_PORT=${APP_PORT}

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_HN

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mariadb
DB_HOST=db
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

INSTANCE_MODE=auxiliar
INSTANCE_UUID=${INSTANCE_UUID}
INSTANCE_LABEL="${INSTANCE_LABEL}"
MAYOR_SYNC_URL=${MAYOR_SYNC_URL}
MAYOR_SYNC_TOKEN=${MAYOR_SYNC_TOKEN}
EOF

    mark_done "env_configured"
    ok ".env configurado correctamente."
fi

echo

# ── PASO 3: Build y arranque de contenedores ──────────────────────────────────
step "PASO 3: Construyendo y levantando contenedores Docker"
divider
echo

# Detectar si los contenedores ya están corriendo
CONTAINERS_RUNNING=false
if $COMPOSE_CMD -f "$COMPOSE_FILE" ps --status running 2>/dev/null | grep -q "app"; then
    CONTAINERS_RUNNING=true
fi

if is_done "docker_built" && $CONTAINERS_RUNNING; then
    ok "Contenedores ya están en ejecución (paso completado previamente)."
    info "Para reconstruir usa: bash install-auxiliar.sh --reset"
else
    if is_done "docker_built" && ! $CONTAINERS_RUNNING; then
        info "La imagen ya fue construida pero los contenedores no están corriendo. Levantando..."
    else
        if $CONTAINERS_RUNNING; then
            warn "Los contenedores están corriendo. ¿Reconstruir la imagen?"
            if ! ask_yes "¿Reconstruir imagen Docker? (puede tardar varios minutos)"; then
                info "Se omite la reconstrucción."
                mark_done "docker_built"
                goto_step4=true
            fi
        fi

        if [[ "${goto_step4:-false}" == "false" ]]; then
            info "Construyendo imagen (puede tardar varios minutos)..."
            echo
            $COMPOSE_CMD -f "$COMPOSE_FILE" build --no-cache 2>&1 | \
                grep -E "^(Step|STEP|#|---|\s*--->|Successfully|ERROR|error)" || true
            echo
            mark_done "docker_built"
            ok "Imagen construida correctamente."
        fi
    fi

    info "Iniciando servicios..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" up -d
    mark_done "docker_running"
    ok "Servicios iniciados."
fi

echo

# ── PASO 4: Verificar salud de los contenedores ───────────────────────────────
step "PASO 4: Verificando salud de los contenedores"

MAX_WAIT=90
WAITED=0
printf "  Esperando que MariaDB esté lista"
while true; do
    STATUS=$($COMPOSE_CMD -f "$COMPOSE_FILE" ps --format json 2>/dev/null | \
        python3 -c "
import sys, json
data = sys.stdin.read().strip()
items = []
for line in data.splitlines():
    try: items.append(json.loads(line))
    except: pass
db = next((x for x in items if 'db' in x.get('Name','') or 'db' in x.get('Service','')), None)
print(db.get('Health','unknown') if db else 'unknown')
" 2>/dev/null || echo "unknown")

    if [[ "$STATUS" == "healthy" ]]; then
        echo
        ok "MariaDB lista."
        break
    fi

    if [[ $WAITED -ge $MAX_WAIT ]]; then
        echo
        fatal "MariaDB no respondió en ${MAX_WAIT}s. Revisa los logs: $COMPOSE_CMD -f $COMPOSE_FILE logs db"
    fi

    printf "."
    sleep 3
    WAITED=$((WAITED + 3))
done

APP_STATUS=$($COMPOSE_CMD -f "$COMPOSE_FILE" ps --format json 2>/dev/null | \
    python3 -c "
import sys, json
data = sys.stdin.read().strip()
items = []
for line in data.splitlines():
    try: items.append(json.loads(line))
    except: pass
app = next((x for x in items if x.get('Service','') == 'app'), None)
print(app.get('State','unknown') if app else 'unknown')
" 2>/dev/null || echo "unknown")

if [[ "$APP_STATUS" != "running" ]]; then
    warn "El contenedor 'app' está en estado: $APP_STATUS"
    warn "Mostrando logs recientes:"
    $COMPOSE_CMD -f "$COMPOSE_FILE" logs --tail=30 app
    echo
    if ! ask_yes "¿Deseas continuar de todas formas?"; then
        fatal "Instalación pausada. Corrige el problema y vuelve a ejecutar el script."
    fi
else
    ok "Contenedor app: corriendo"
fi

for svc in nginx scheduler worker; do
    SVC_STATE=$($COMPOSE_CMD -f "$COMPOSE_FILE" ps --format json 2>/dev/null | \
        python3 -c "
import sys, json
data = sys.stdin.read().strip()
items = []
for line in data.splitlines():
    try: items.append(json.loads(line))
    except: pass
s = next((x for x in items if x.get('Service','') == '$svc'), None)
print(s.get('State','no encontrado') if s else 'no encontrado')
" 2>/dev/null || echo "desconocido")
    [[ "$SVC_STATE" == "running" ]] && ok "Contenedor $svc: corriendo" || warn "Contenedor $svc: $SVC_STATE"
done
echo

# ── PASO 5: Verificar bootstrap ───────────────────────────────────────────────
step "PASO 5: Verificando bootstrap de la instancia"

info "Revisando logs del bootstrap (entrypoint)..."
sleep 3
LOGS=$($COMPOSE_CMD -f "$COMPOSE_FILE" logs --tail=60 app 2>&1)

if echo "$LOGS" | grep -q "Bootstrap completado\|Instancia ya inicializada"; then
    ok "Bootstrap ejecutado exitosamente."
elif echo "$LOGS" | grep -q "Faltan variables de entorno\|INSTANCE_UUID\|MAYOR_SYNC_TOKEN"; then
    warn "El bootstrap falló por variables de entorno faltantes."
    echo
    echo -e "  ${DIM}Fragmento del log:${NC}"
    echo "$LOGS" | grep -A5 "Faltan variables\|INSTANCE" | head -10 | sed 's/^/    /'
    echo
    warn "Corrige el .env, luego vuelve a ejecutar: bash install-auxiliar.sh"
    warn "El script retomará desde los contenedores ya construidos."
    exit 1
elif echo "$LOGS" | grep -qi "error\|failed\|exception"; then
    warn "Se detectaron posibles errores en el bootstrap."
    echo -e "  ${DIM}Últimas líneas del log:${NC}"
    echo "$LOGS" | tail -20 | sed 's/^/    /'
else
    info "Bootstrap en progreso o logs no concluyentes. Últimas líneas:"
    echo "$LOGS" | tail -15 | sed 's/^/    /'
fi
echo

# ── PASO 6: Pruebas de conectividad ──────────────────────────────────────────
step "PASO 6: Pruebas de conectividad"

ENV_PORT=$(grep "^APP_PORT=" .env | cut -d= -f2 | tr -d '"' || echo "80")
ENV_PORT="${ENV_PORT:-80}"
TEST_URL="http://localhost:${ENV_PORT}"

info "Probando HTTP en $TEST_URL ..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$TEST_URL" 2>/dev/null || echo "000")

if [[ "$HTTP_CODE" =~ ^(200|301|302|303|401|403)$ ]]; then
    ok "Servidor HTTP responde: HTTP $HTTP_CODE"
else
    warn "No se pudo conectar a $TEST_URL (código: $HTTP_CODE)."
    warn "Nginx puede necesitar más tiempo o el puerto está en uso."
fi

info "Verificando artisan dentro del contenedor app..."
ARTISAN_OUT=$($COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan --version 2>&1 || echo "ERROR")
if echo "$ARTISAN_OUT" | grep -qi "laravel"; then
    ok "Artisan disponible: $ARTISAN_OUT"
else
    warn "No se pudo ejecutar artisan: $ARTISAN_OUT"
fi

info "Verificando conexión con la base de datos..."
DB_TEST=$($COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app \
    php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>&1 || echo "ERROR")
if echo "$DB_TEST" | grep -q "^OK"; then
    ok "Conexión a la base de datos: OK"
else
    warn "Error al conectar con la base de datos."
    echo "$DB_TEST" | tail -5 | sed 's/^/    /'
fi

MAYOR_URL=$(grep "^MAYOR_SYNC_URL=" .env | cut -d= -f2 | tr -d '"' || echo "")
if [[ -n "$MAYOR_URL" ]]; then
    info "Verificando conectividad con el Mayor ($MAYOR_URL)..."
    MAYOR_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 15 "$MAYOR_URL" 2>/dev/null || echo "000")
    if [[ "$MAYOR_STATUS" =~ ^(200|301|302|401|403)$ ]]; then
        ok "Mayor accesible: HTTP $MAYOR_STATUS"
    else
        warn "No se pudo alcanzar el Mayor en $MAYOR_URL (código: $MAYOR_STATUS)."
        warn "Verifica que la URL sea correcta y que haya conectividad de red."
    fi
fi
echo

# ── PASO 7: Sincronización manual (opcional) ─────────────────────────────────
step "PASO 7: Sincronización inicial (opcional)"

if ask_yes "¿Deseas ejecutar sync:pull ahora para descargar datos del Mayor?"; then
    info "Ejecutando sync:pull..."
    $COMPOSE_CMD -f "$COMPOSE_FILE" exec -T app php artisan sync:pull --force 2>&1 | \
        tail -20 | sed 's/^/    /'
    ok "sync:pull completado."
else
    info "Puedes ejecutarlo luego con:"
    echo -e "  ${DIM}$COMPOSE_CMD -f $COMPOSE_FILE exec app php artisan sync:pull${NC}"
fi
echo

# Instalación completada — limpiar archivo de progreso
rm -f "$STATE_FILE"

# ── Resumen final ─────────────────────────────────────────────────────────────
divider
echo
echo -e "  ${BOLD}${GREEN}Instalación completada exitosamente${NC}"
echo
echo -e "  ${BOLD}Directorio:${NC}  $SCRIPT_DIR"
echo -e "  ${BOLD}URL local:${NC}   http://localhost:${ENV_PORT}"
echo -e "  ${BOLD}Compose:${NC}     $COMPOSE_CMD -f $COMPOSE_FILE"
echo
echo -e "  ${BOLD}Comandos útiles:${NC}"
echo -e "  ${DIM}Ver logs:        $COMPOSE_CMD -f $COMPOSE_FILE logs -f${NC}"
echo -e "  ${DIM}Detener:         $COMPOSE_CMD -f $COMPOSE_FILE down${NC}"
echo -e "  ${DIM}Reiniciar:       $COMPOSE_CMD -f $COMPOSE_FILE restart${NC}"
echo -e "  ${DIM}Shell en app:    $COMPOSE_CMD -f $COMPOSE_FILE exec app bash${NC}"
echo -e "  ${DIM}Sync manual:     $COMPOSE_CMD -f $COMPOSE_FILE exec app php artisan sync:pull${NC}"
echo
divider
