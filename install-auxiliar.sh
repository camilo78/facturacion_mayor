#!/usr/bin/env bash
# install-auxiliar.sh — Instalador del Nodo Auxiliar de Factunet v3.1
# Fixes: [1-11] errores previos  [12] BD vacía no detectada  [13] seeder en tenant
#        [14] watermark stale    [15] MARIADB_USER=root ERROR 1396

set -euo pipefail
IFS=$'\n\t'

# ── Constantes ────────────────────────────────────────────────────────────────
VERSION="3.1"
COMPOSE_FILE="docker-compose.auxiliar.yml"
COMPOSE="docker compose"
ENV_FILE=".env"
STATE_FILE=".install-state"
SPIN_PID=""
_cleanup_done=false

# ── Detección de TTY ANTES de redirigir fd1/fd2 ───────────────────────────────
use_color=false
if [[ -t 1 && -z "${NO_COLOR:-}" ]]; then
    use_color=true
fi

if $use_color; then
    RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'
    BLUE=$'\033[1;34m'; BOLD=$'\033[1m'; NC=$'\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; BLUE=''; BOLD=''; NC=''
fi

# ── Log file: DESPUÉS de detectar TTY ────────────────────────────────────────
LOG_FILE="install-auxiliar.$(date +%Y%m%d-%H%M%S).log"
exec > >(tee -a "$LOG_FILE") 2>&1

# ── Output helpers ─────────────────────────────────────────────────────────────
log()      { printf '%b[install]%b %s\n' "$BOLD"   "$NC" "$*"  >&2; }
ok()       { printf '%b✓%b %s\n'         "$GREEN"  "$NC" "$*"  >&2; }
warn()     { printf '%b⚠%b  %s\n'        "$YELLOW" "$NC" "$*"  >&2; }
fail_msg() { printf '%b✗%b  %s\n'        "$RED"    "$NC" "$*"  >&2; }
fatal()    { printf '%bFATAL:%b %s\n'    "$RED"    "$NC" "$*"  >&2; exit 1; }
section()  { printf '\n%b══ %s%b\n'      "$BLUE"   "$*"  "$NC" >&2; }

# ── Helper .env ────────────────────────────────────────────────────────────────
env_write() {
    local key="$1"
    local value="${2//—/-}"
    local escaped="${value//\"/\\\"}"
    printf '%s="%s"\n' "$key" "$escaped" >> "$ENV_FILE"
}
env_val() { grep -m1 "^${1}=" "$ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '"' || true; }

# ── Spinner (escribe a /dev/tty para no contaminar el log) ────────────────────
start_spinner() {
    $use_color || return 0
    local msg="$1"
    (
        local frames=('⠋' '⠙' '⠹' '⠸' '⠼' '⠴' '⠦' '⠧' '⠇' '⠏') i=0
        while true; do
            printf '\r%b%s%b %s' "$BLUE" "${frames[$i]}" "$NC" "$msg" >/dev/tty 2>/dev/null || exit 0
            i=$(( (i + 1) % 10 ))
            sleep 0.1
        done
    ) &
    SPIN_PID=$!
}

stop_spinner() {
    if [[ -n "${SPIN_PID:-}" ]]; then
        kill "${SPIN_PID}" 2>/dev/null || true
        wait "${SPIN_PID}" 2>/dev/null || true
        SPIN_PID=""
        printf '\r%60s\r' '' >/dev/tty 2>/dev/null || true
    fi
}

# ── Trap / cleanup ─────────────────────────────────────────────────────────────
cleanup() {
    if $_cleanup_done; then return; fi
    _cleanup_done=true
    stop_spinner
    local code=$?
    if [[ $code -ne 0 ]]; then
        printf '\n%bInstalación interrumpida (código %d).%b\n' "$RED" "$code" "$NC" >&2
        printf 'Log: %s\n' "$LOG_FILE" >&2
        printf 'Para limpiar:\n  docker compose -f %s down --remove-orphans\n' "$COMPOSE_FILE" >&2
        printf 'Para reintentar:\n  bash install-auxiliar.sh\n' >&2
    fi
}
trap 'cleanup' EXIT
trap 'exit 130' INT TERM

# ── Banner ─────────────────────────────────────────────────────────────────────
show_banner() {
    printf '\n%b' "$BLUE" >&2
    printf '  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' >&2
    printf '     FACTUNET  ·  Instalador Nodo Auxiliar  ·  v%s\n' "$VERSION" >&2
    printf '  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' >&2
    printf '%b' "$NC" >&2
    printf '  Log: %s\n\n' "$LOG_FILE" >&2
}

# ── Variables a recolectar ─────────────────────────────────────────────────────
MAYOR_SYNC_URL=""
MAYOR_SYNC_TOKEN=""
INSTANCE_UUID=""
INSTANCE_LABEL=""
TENANT_ID=""
DB_USERNAME="auxiliar"
DB_PASSWORD=""
DB_ROOT_PASSWORD=""
APP_PORT="80"
_confirm_pass=""

# ── Utilidades ─────────────────────────────────────────────────────────────────
suggest_password() { openssl rand -base64 18 | tr -d '/+=' | head -c 16; }

gen_uuid() {
    if command -v uuidgen &>/dev/null; then
        uuidgen | tr '[:upper:]' '[:lower:]'
    elif [[ -r /proc/sys/kernel/random/uuid ]]; then
        cat /proc/sys/kernel/random/uuid
    else
        openssl rand -hex 16 \
            | sed 's/\(.\{8\}\)\(.\{4\}\)\(.\{4\}\)\(.\{4\}\)\(.\{12\}\)/\1-\2-\3-\4-\5/'
    fi
}

# ── Step 0: Detectar instalación previa ───────────────────────────────────────
detect_existing_install() {
    local has_state=false has_env=false has_containers=false
    [[ -f "$STATE_FILE" ]] && has_state=true
    [[ -f "$ENV_FILE"   ]] && has_env=true
    if docker ps -a --format '{{.Names}}' 2>/dev/null | grep -q '^auxiliar_'; then
        has_containers=true
    fi

    $has_state || $has_env || $has_containers || return 0

    warn "Se detectó una instalación previa:"
    $has_containers && warn "  - Contenedores Docker existentes"
    $has_env        && warn "  - Archivo .env presente"
    $has_state      && warn "  - Estado previo: $(cat "$STATE_FILE" 2>/dev/null || echo 'desconocido')"
    printf '\n' >&2
    printf '%b¿Qué deseas hacer?%b\n' "$BOLD" "$NC" >&2
    printf '  (a) Reinstalar desde cero [RECOMENDADO para problemas]\n' >&2
    printf '  (b) Solo re-ejecutar los tests de aceptación\n' >&2
    printf '  (c) Salir\n' >&2

    local choice=""
    while [[ "$choice" != "a" && "$choice" != "b" && "$choice" != "c" ]]; do
        printf '\n%bOpción (a/b/c)%b [a]: ' "$BOLD" "$NC" >&2
        IFS= read -r choice </dev/tty || choice="a"
        [[ -z "$choice" ]] && choice="a"
        choice="${choice,,}"
    done

    case "$choice" in
        a) ok "Reinstalando desde cero..." ;;
        b)
            ok "Ejecutando solo tests de aceptación..."
            TENANT_ID=$(env_val TENANT_ID 2>/dev/null || true)
            APP_PORT=$(env_val APP_PORT 2>/dev/null || true)
            APP_PORT="${APP_PORT:-80}"
            if [[ -z "$TENANT_ID" ]]; then
                printf '%bTENANT_ID%b: ' "$BOLD" "$NC" >&2
                IFS= read -r TENANT_ID </dev/tty
            fi
            run_tests
            exit $?
            ;;
        c) ok "Saliendo sin cambios."; exit 0 ;;
    esac
}

# ── Step 1: Prerequisitos ──────────────────────────────────────────────────────
check_prereqs() {
    local missing=0
    for cmd in docker openssl curl; do
        if ! command -v "$cmd" &>/dev/null; then
            fail_msg "Falta: $cmd"
            missing=$(( missing + 1 ))
        fi
    done
    if ! $COMPOSE version &>/dev/null; then
        fail_msg "Plugin 'docker compose' no disponible (prueba: docker compose version)"
        missing=$(( missing + 1 ))
    fi
    [[ $missing -eq 0 ]] || fatal "Instala los prerequisitos faltantes y vuelve a correr."
    ok "Docker, openssl, curl → OK"
}

# ── Step 2: Captura de configuración ──────────────────────────────────────────
# ask <prompt> <varname> [<default>] [secret=no]
ask() {
    local prompt="$1" varname="$2" default="${3:-}" secret="${4:-no}"
    while true; do
        local hint=""
        [[ -n "$default" ]] && hint=" [${default}]"
        printf '%b%s%b%s: ' "$BOLD" "$prompt" "$NC" "$hint" >&2
        local value=""
        if [[ "$secret" == "yes" ]]; then
            IFS= read -rs value </dev/tty; printf '\n' >&2
        else
            IFS= read -r value </dev/tty
        fi
        # trim leading/trailing spaces
        value="${value#"${value%%[![:space:]]*}"}"
        value="${value%"${value##*[![:space:]]}"}"
        [[ -z "$value" && -n "$default" ]] && value="$default"
        if [[ -n "$value" ]]; then
            printf -v "$varname" '%s' "$value"
            return
        fi
        warn "Este campo es obligatorio."
    done
}

ask_url() {
    local varname="$1"
    while true; do
        ask "URL del Mayor (ej: https://factunet.io)" "$varname"
        local val="${!varname}"
        if [[ ! "$val" =~ ^https?:// ]]; then
            warn "Debe comenzar con http:// o https://"; continue
        fi
        if [[ ! "$val" =~ ^https?://[a-zA-Z0-9._-]+ ]]; then
            warn "URL inválida — falta hostname"; continue
        fi
        val="${val%/}"
        printf -v "$varname" '%s' "$val"
        log "Probando conectividad con el Mayor..."
        if curl -sf --connect-timeout 10 --max-time 15 "${val}/up" &>/dev/null; then
            ok "Mayor alcanzable en ${val}"
        else
            warn "No se pudo alcanzar ${val}/up"
            warn "El bootstrap reintentará cuando haya conexión."
        fi
        return
    done
}

ask_password() {
    local prompt="$1" varname="$2"
    local suggested; suggested=$(suggest_password)
    local p1="" p2=""
    while true; do
        warn "Sugerencia segura: ${suggested}"
        ask "${prompt} (mínimo 12 caracteres)" "$varname" "" "yes"
        p1="${!varname}"
        if [[ ${#p1} -lt 12 ]]; then
            warn "Contraseña demasiado corta (mínimo 12 caracteres)"; continue
        fi
        ask "Confirmar ${prompt}" "_confirm_pass" "" "yes"
        p2="$_confirm_pass"
        if [[ "$p1" == "$p2" ]]; then return; fi
        warn "Las contraseñas no coinciden. Intenta de nuevo."
    done
}

collect_fields() {
    ask_url MAYOR_SYNC_URL

    # Token: sin espacios, mínimo 16 chars
    while true; do
        ask "Token de sincronización (MAYOR_SYNC_TOKEN)" MAYOR_SYNC_TOKEN "" "yes"
        if [[ "${MAYOR_SYNC_TOKEN}" =~ [[:space:]] ]]; then
            warn "El token no puede contener espacios"; continue
        fi
        if [[ ${#MAYOR_SYNC_TOKEN} -lt 16 ]]; then
            warn "Token demasiado corto (mínimo 16 caracteres)"; continue
        fi
        break
    done

    # UUID: autogenerar o validar formato
    local auto_uuid; auto_uuid=$(gen_uuid)
    while true; do
        ask "UUID de la instancia (Enter = autogenerar)" INSTANCE_UUID "$auto_uuid"
        if [[ ! "$INSTANCE_UUID" =~ ^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$ ]]; then
            warn "Formato inválido — esperado: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
            continue
        fi
        break
    done

    ask "Etiqueta de la instancia (INSTANCE_LABEL)" INSTANCE_LABEL "Auxiliar - $(hostname)"

    # TENANT_ID: solo minúsculas, números y guiones bajos
    while true; do
        ask "ID del tenant en el Mayor (TENANT_ID)" TENANT_ID
        if [[ ! "$TENANT_ID" =~ ^[a-z0-9_]+$ ]]; then
            warn "TENANT_ID inválido: solo minúsculas, números y guiones bajos"
            continue
        fi
        break
    done

    # [15] DB_USERNAME: no puede ser 'root'
    while true; do
        ask "Usuario de BD para la app (DB_USERNAME)" DB_USERNAME "auxiliar"
        if [[ "$DB_USERNAME" == "root" ]]; then
            fail_msg "DB_USERNAME no puede ser 'root'"
            warn "Causa: la imagen MariaDB crea 'root'@'%' automáticamente via MARIADB_ROOT_PASSWORD."
            warn "Si MARIADB_USER=root, el init falla con ERROR 1396 (Operation CREATE USER failed)"
            warn "y el contenedor queda unhealthy. Usa 'auxiliar' u otro nombre aplicativo."
            continue
        fi
        if [[ ! "$DB_USERNAME" =~ ^[a-z][a-z0-9_]*$ ]]; then
            warn "Usuario inválido: debe empezar con letra, solo minúsculas/números/_"
            continue
        fi
        break
    done

    ask_password "Contraseña de BD (DB_PASSWORD)" DB_PASSWORD
    ask_password "Contraseña root de MariaDB (DB_ROOT_PASSWORD)" DB_ROOT_PASSWORD

    # Puerto: 1-65535
    while true; do
        ask "Puerto local para Nginx (APP_PORT)" APP_PORT "80"
        if [[ ! "$APP_PORT" =~ ^[0-9]+$ ]] || (( APP_PORT < 1 || APP_PORT > 65535 )); then
            warn "Puerto inválido: debe ser un número entre 1 y 65535"; continue
        fi
        if (( APP_PORT < 1024 )); then
            warn "Puerto ${APP_PORT} < 1024 puede requerir privilegios de root."
        fi
        break
    done
}

# ── Step 3: .env + mariadb-init.sql ───────────────────────────────────────────
backup_env() {
    if [[ -f "$ENV_FILE" ]]; then
        local backup="${ENV_FILE}.backup-$(date +%Y%m%d-%H%M%S)"
        cp "$ENV_FILE" "$backup"
        ok "Backup del .env anterior: ${backup}"
    fi
}

write_env() {
    local app_key
    app_key="base64:$(openssl rand -base64 32 | tr -d '\n')"

    printf '# Factunet Auxiliar — generado por install-auxiliar.sh v%s\n' "$VERSION" > "$ENV_FILE"
    printf '# %s\n\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" >> "$ENV_FILE"

    printf '# -- Aplicacion --------------------------------------------------------\n' >> "$ENV_FILE"
    env_write APP_NAME     "Factunet Auxiliar"
    env_write APP_ENV      "production"
    env_write APP_KEY      "$app_key"
    env_write APP_DEBUG    "false"
    env_write APP_URL      "http://localhost"
    env_write APP_PORT     "$APP_PORT"
    env_write APP_LOCALE   "es"
    env_write APP_TIMEZONE "America/Tegucigalpa"
    printf '\n' >> "$ENV_FILE"

    printf '# -- Logs --------------------------------------------------------------\n' >> "$ENV_FILE"
    env_write LOG_CHANNEL "daily"
    env_write LOG_LEVEL   "warning"
    printf '\n' >> "$ENV_FILE"

    printf '# -- Base de datos -----------------------------------------------------\n' >> "$ENV_FILE"
    env_write DB_CONNECTION    "mariadb"
    env_write DB_HOST          "db"
    env_write DB_PORT          "3306"
    env_write DB_DATABASE      "auxiliar_landlord"
    env_write DB_USERNAME      "$DB_USERNAME"
    env_write DB_PASSWORD      "$DB_PASSWORD"
    env_write DB_ROOT_PASSWORD "$DB_ROOT_PASSWORD"
    printf '\n' >> "$ENV_FILE"

    printf '# -- Drivers (sin Redis) -----------------------------------------------\n' >> "$ENV_FILE"
    env_write QUEUE_CONNECTION "database"
    env_write CACHE_STORE      "database"
    env_write SESSION_DRIVER   "database"
    env_write SESSION_LIFETIME "480"
    printf '\n' >> "$ENV_FILE"

    printf '# -- Instancia Auxiliar ------------------------------------------------\n' >> "$ENV_FILE"
    env_write INSTANCE_MODE  "auxiliar"
    env_write INSTANCE_UUID  "$INSTANCE_UUID"
    env_write INSTANCE_LABEL "$INSTANCE_LABEL"
    printf '\n' >> "$ENV_FILE"

    printf '# -- Conexion con el Mayor ---------------------------------------------\n' >> "$ENV_FILE"
    env_write MAYOR_SYNC_URL   "$MAYOR_SYNC_URL"
    env_write MAYOR_SYNC_TOKEN "$MAYOR_SYNC_TOKEN"

    ok ".env escrito (APP_KEY generada, DB_USERNAME=${DB_USERNAME})"
}

# [15] Generar mariadb-init.sql dinámicamente con el usuario correcto
generate_mariadb_init() {
    local sql_file="docker/auxiliar/mariadb-init.sql"
    if [[ ! -d "docker/auxiliar" ]]; then
        fatal "Directorio docker/auxiliar no encontrado — corre el instalador desde la raíz del proyecto"
    fi
    # NO crear 'root'@'%' aquí: la imagen MariaDB ya lo hace via MARIADB_ROOT_PASSWORD.
    # MARIADB_USER crea el usuario aplicativo; este GRANT le da permisos para crear DBs de tenants.
    printf '-- Generado por install-auxiliar.sh v%s — NO editar manualmente\n' "$VERSION"  > "$sql_file"
    printf '-- Otorga al usuario aplicativo permisos para crear bases de datos de tenants.\n' >> "$sql_file"
    printf '-- NOTA: root@%% es gestionado por la imagen via MARIADB_ROOT_PASSWORD. No incluir aqui.\n' >> "$sql_file"
    printf "GRANT ALL PRIVILEGES ON *.* TO '%s'@'%%';\n" "$DB_USERNAME" >> "$sql_file"
    printf 'FLUSH PRIVILEGES;\n' >> "$sql_file"
    ok "mariadb-init.sql generado para usuario: ${DB_USERNAME}"
}

validate_env() {
    local errors=0
    LC_ALL=C grep -Pn $'\x1b' "$ENV_FILE" >/dev/null 2>&1 \
        && { fail_msg "Códigos ANSI en .env"; errors=$(( errors + 1 )); }
    grep -Fn '=""' "$ENV_FILE" >/dev/null 2>&1 \
        && { fail_msg "Valor vacío en .env"; errors=$(( errors + 1 )); }
    grep -Fn '<COMPLETAR>' "$ENV_FILE" >/dev/null 2>&1 \
        && { fail_msg "Placeholder sin llenar en .env"; errors=$(( errors + 1 )); }
    local saved_user; saved_user=$(env_val DB_USERNAME)
    if [[ "$saved_user" == "root" ]]; then
        fail_msg "DB_USERNAME=root detectado en .env — ERROR 1396 garantizado en MariaDB"
        errors=$(( errors + 1 ))
    fi
    [[ $errors -eq 0 ]] || fatal "Errores en .env — abortando."
    ok ".env validado OK"
}

# ── Step 4: Validar compose ────────────────────────────────────────────────────
validate_compose() {
    $COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" config >/dev/null 2>&1 \
        || fatal "docker compose config falló — revisa $COMPOSE_FILE"
    ok "docker compose config OK"
}

# ── Step 5: Limpiar huérfanos ──────────────────────────────────────────────────
clean_orphans() {
    $COMPOSE -f "$COMPOSE_FILE" down --remove-orphans 2>/dev/null || true
    local orphans
    orphans=$(docker ps -aq --filter 'name=auxiliar_' 2>/dev/null || true)
    if [[ -n "$orphans" ]]; then
        printf '%s\n' "$orphans" | xargs docker rm -f 2>/dev/null || true
    fi
    ok "Contenedores anteriores eliminados"
}

# ── Step 6: Manejar volúmenes ─────────────────────────────────────────────────
handle_volumes() {
    # auxiliar_app siempre se elimina — se puebla desde la nueva imagen
    local app_vol
    app_vol=$(docker volume ls --format '{{.Name}}' 2>/dev/null | grep '^auxiliar_app$' | head -1 || true)
    if [[ -n "$app_vol" ]]; then
        log "Eliminando volumen de código anterior (${app_vol})..."
        docker volume rm "$app_vol" 2>/dev/null || warn "No se pudo eliminar ${app_vol}"
    fi

    # auxiliar_db puede tener datos — preguntar
    local db_vol
    db_vol=$(docker volume ls --format '{{.Name}}' 2>/dev/null | grep '^auxiliar_db$' | head -1 || true)
    if [[ -n "$db_vol" ]]; then
        warn "El volumen de base de datos '${db_vol}' ya existe."
        warn "  (a) Recrear — borra datos locales; se re-sincronizan del Mayor [RECOMENDADO]"
        warn "  (b) Conservar — mantener datos y relanzar contenedores"
        local choice=""
        while [[ "$choice" != "a" && "$choice" != "b" ]]; do
            printf '\n%bOpción (a/b)%b [a]: ' "$BOLD" "$NC" >&2
            IFS= read -r choice </dev/tty || choice="a"
            [[ -z "$choice" ]] && choice="a"
            choice="${choice,,}"
        done
        if [[ "$choice" == "a" ]]; then
            printf '\n%b⚠  ESTO BORRARÁ TODOS LOS DATOS LOCALES DE LA BD ⚠%b\n' "$RED" "$NC" >&2
            printf '%bEscribí BORRAR para confirmar%b: ' "$RED" "$NC" >&2
            local confirm=""
            IFS= read -r confirm </dev/tty
            if [[ "$confirm" == "BORRAR" ]]; then
                docker volume rm "$db_vol" 2>/dev/null || warn "No se pudo eliminar ${db_vol}"
                ok "Volumen de BD eliminado — se creará nuevo"
            else
                ok "Cancelado — conservando volumen de BD"
            fi
        else
            ok "Conservando volumen de BD existente"
        fi
    fi
}

# ── Step 7: Build e inicio ────────────────────────────────────────────────────
build_and_start() {
    printf 'building\n' > "$STATE_FILE"

    start_spinner "Construyendo imagen Docker (Node 22 + PHP 8.4 — puede tardar varios minutos)..."
    if $use_color; then
        $COMPOSE -f "$COMPOSE_FILE" build --no-cache &>/dev/null
    else
        $COMPOSE -f "$COMPOSE_FILE" build --no-cache --progress=plain
    fi
    stop_spinner
    ok "Imagen construida"

    log "Iniciando contenedores..."
    $COMPOSE -f "$COMPOSE_FILE" up -d --force-recreate
    ok "Contenedores iniciados"
    printf 'started\n' > "$STATE_FILE"

    log "Esperando MariaDB healthy (hasta 120s — start_period=30s)..."
    local elapsed=0 timeout=120
    while true; do
        local db_status
        db_status=$(docker inspect auxiliar_db --format '{{.State.Health.Status}}' 2>/dev/null || echo "missing")
        case "$db_status" in
            healthy) ok "MariaDB lista"; break ;;
            missing) fatal "auxiliar_db no encontrado — verifica $COMPOSE_FILE" ;;
        esac
        if [[ $elapsed -ge $timeout ]]; then
            fatal "MariaDB no respondió en ${timeout}s — revisa: docker logs auxiliar_db"
        fi
        sleep 3; elapsed=$(( elapsed + 3 ))
    done

    start_spinner "Esperando entrypoint (migraciones + bootstrap)..."
    elapsed=0; timeout=180
    while true; do
        if docker exec auxiliar_app php -r 'echo "ok";' &>/dev/null 2>&1; then
            stop_spinner
            ok "php-fpm listo — entrypoint completado"
            break
        fi
        if [[ $elapsed -ge $timeout ]]; then
            stop_spinner
            warn "php-fpm no respondió en ${timeout}s"
            warn "El bootstrap puede seguir ejecutándose. Revisa: docker logs auxiliar_app"
            break
        fi
        sleep 3; elapsed=$(( elapsed + 3 ))
    done

    printf 'ready\n' > "$STATE_FILE"
}

# ── Step 8: Tests de aceptación T1-T15 ────────────────────────────────────────
PASS=0; FAIL=0
FAILED_IDS=()

t_pass() { ok "[${1}] ${2}"; PASS=$(( PASS + 1 )); }
t_fail() {
    fail_msg "[${1}] ${2}"
    [[ -n "${3:-}" ]] && warn "    → ${3}"
    FAILED_IDS+=("$1")
    FAIL=$(( FAIL + 1 ))
}

tenant_php() {
    local code="$1"
    timeout 30 docker exec auxiliar_app php artisan tinker \
        --execute="tenancy()->initialize('${TENANT_ID}'); ${code}" \
        2>/dev/null | grep -E '^[0-9]+$' | tail -1 || echo "0"
}

run_tests() {
    set +e
    local port; port=$(env_val APP_PORT 2>/dev/null || true); port="${port:-80}"

    # T1: .env limpio
    local t1=true
    LC_ALL=C grep -Pn $'\x1b' "$ENV_FILE" >/dev/null 2>&1 && t1=false
    grep -Fn '=""'            "$ENV_FILE" >/dev/null 2>&1 && t1=false
    grep -Fn '<COMPLETAR>'    "$ENV_FILE" >/dev/null 2>&1 && t1=false
    if $t1; then t_pass T1 ".env sin ANSI / comillas vacías / placeholders"
    else         t_fail T1 ".env limpio" "Inspecciona $ENV_FILE manualmente"; fi

    # T2: DB_USERNAME != root [FIX #15]
    local saved_user; saved_user=$(env_val DB_USERNAME)
    if [[ "$saved_user" != "root" ]]; then
        t_pass T2 "DB_USERNAME=${saved_user} (no es root — ERROR 1396 evitado)"
    else
        t_fail T2 "DB_USERNAME=root detectado en .env" \
            "ERROR 1396 probable — MariaDB crea root@% automáticamente. Re-instala y usa 'auxiliar'"
    fi

    # T3: docker compose config válido
    if $COMPOSE -f "$COMPOSE_FILE" config >/dev/null 2>&1
    then t_pass T3 "docker compose config válido"
    else t_fail T3 "docker compose config inválido" "Error de sintaxis en $COMPOSE_FILE"; fi

    # T4: MariaDB healthy + RestartCount <= 1 (detecta ERROR 1396)
    local db_health rc
    db_health=$(docker inspect auxiliar_db --format '{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
    rc=$(docker inspect auxiliar_db --format '{{.RestartCount}}' 2>/dev/null || echo "99")
    if [[ "$db_health" == "healthy" && "$rc" -le 1 ]]; then
        t_pass T4 "MariaDB healthy, RestartCount=${rc}"
    else
        t_fail T4 "MariaDB: health=${db_health}, RestartCount=${rc}" \
            "RestartCount>1 sugiere ERROR 1396. Revisa: docker logs auxiliar_db | grep -i error"
    fi

    # T5: DB responde con usuario aplicativo
    local db_user db_pass
    db_user=$(env_val DB_USERNAME)
    db_pass=$(env_val DB_PASSWORD)
    if timeout 10 docker exec auxiliar_db \
            mariadb -u"$db_user" -p"$db_pass" -e "SELECT 1" >/dev/null 2>&1
    then t_pass T5 "DB responde con usuario aplicativo (${db_user})"
    else t_fail T5 "DB no responde con usuario ${db_user}" \
            "Access denied — en próxima instalación elige (a) para recrear el volumen DB"; fi

    # T6: 5 contenedores running
    local t6=true
    for ctn in auxiliar_db auxiliar_app auxiliar_nginx auxiliar_worker auxiliar_scheduler; do
        local st
        st=$(docker inspect "$ctn" --format '{{.State.Status}}' 2>/dev/null || echo "missing")
        if [[ "$st" != "running" ]]; then
            warn "  ${ctn} → ${st}"
            t6=false
        fi
    done
    if $t6; then t_pass T6 "5 contenedores running"
    else         t_fail T6 "Contenedor(es) no running" "Revisa: docker compose -f $COMPOSE_FILE ps"; fi

    # T7: bootstrap idempotente — 2 corridas sin Duplicate entry
    local run1 run2
    run1=$(timeout 30 docker exec auxiliar_app php artisan instance:bootstrap 2>&1 || true)
    run2=$(timeout 30 docker exec auxiliar_app php artisan instance:bootstrap 2>&1 || true)
    if printf '%s%s' "$run1" "$run2" | grep -qi "Duplicate entry\|SQLSTATE.*1062"
    then t_fail T7 "Bootstrap idempotente" "Segunda corrida lanzó Duplicate entry — verifica upsertTenant()"
    else t_pass T7 "Bootstrap idempotente (2 corridas sin errores)"; fi

    # T8: Assets Vite presentes
    if timeout 10 docker exec auxiliar_app test -f public/build/manifest.json 2>/dev/null
    then t_pass T8 "Assets Vite presentes (public/build/manifest.json)"
    else t_fail T8 "Assets Vite ausentes" "Verifica el Dockerfile multi-stage (etapa node:22-alpine)"; fi

    # T9: storage/logs escribible
    if timeout 10 docker exec auxiliar_app sh -c \
        'test -w storage/logs && touch storage/logs/.wt && rm storage/logs/.wt' 2>/dev/null
    then t_pass T9 "storage/logs escribible"
    else t_fail T9 "storage/logs no escribible" "Verifica chown en docker/auxiliar/entrypoint.sh"; fi

    # T10: Nginx sirve login del tenant (HTTP 200)
    local login_url="http://localhost:${port}/empresa/${TENANT_ID}/login"
    local http_code
    http_code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$login_url" 2>/dev/null || echo "000")
    if [[ "$http_code" == "200" ]]
    then t_pass T10 "Nginx sirve login del tenant (HTTP 200)"
    else t_fail T10 "Nginx/login HTTP ${http_code}" \
            "502=php-fpm no responde | 404=tenant no encontrado | URL: $login_url"; fi

    # T11: Regla nginx para Livewire
    if timeout 10 docker exec auxiliar_nginx grep -q 'livewire-' /etc/nginx/conf.d/default.conf 2>/dev/null
    then t_pass T11 "Regla nginx para Livewire presente"
    else t_fail T11 "Regla nginx Livewire ausente" "Verifica docker/auxiliar/nginx.conf (location ^/livewire-)"; fi

    # T12: catálogos en BD del tenant > 0
    local prod_count client_count
    prod_count=$(tenant_php 'echo \App\Models\Producto::count();')
    client_count=$(tenant_php 'echo \App\Models\Cliente::count();')
    prod_count="${prod_count:-0}"; client_count="${client_count:-0}"
    if [[ "$prod_count" -gt 0 || "$client_count" -gt 0 ]]
    then t_pass T12 "Catálogos en tenant: productos=${prod_count}, clientes=${client_count}"
    else t_fail T12 "0 catálogos en tenant" \
            "MAYOR_SYNC_URL usa localhost? (no funciona desde Docker en Linux — usa IP real o host.docker.internal)"; fi

    # T13: usuarios en BD del tenant > 0
    local user_count
    user_count=$(tenant_php 'echo \App\Models\User::count();')
    user_count="${user_count:-0}"
    if [[ "$user_count" -gt 0 ]]
    then t_pass T13 "Usuarios sincronizados en tenant: ${user_count}"
    else t_fail T13 "0 usuarios en tenant — login imposible" \
            "Verifica MAYOR_SYNC_TOKEN y que GET /api/sync/usuarios esté activo en el Mayor"; fi

    # T14: permisos >= 20 + Rol Admin con permisos
    local perm_count admin_perm_count
    perm_count=$(tenant_php 'echo \Spatie\Permission\Models\Permission::count();')
    admin_perm_count=$(tenant_php '$r=\Spatie\Permission\Models\Role::where("name","Admin")->first(); echo $r ? $r->permissions()->count() : 0;')
    perm_count="${perm_count:-0}"; admin_perm_count="${admin_perm_count:-0}"
    if [[ "$perm_count" -ge 20 && "$admin_perm_count" -ge 20 ]]
    then t_pass T14 "Permisos en tenant: ${perm_count} | Rol Admin: ${admin_perm_count}"
    else t_fail T14 "Permisos insuficientes (${perm_count}) o Admin sin permisos (${admin_perm_count})" \
            "RolesPermisosSeeder no corrió en el tenant. Prueba: docker exec auxiliar_app php artisan instance:bootstrap --fresh"; fi

    # T15: watermark de sync presente
    local watermark
    watermark=$(timeout 30 docker exec auxiliar_app php artisan tinker \
        --execute="echo \Illuminate\Support\Facades\Cache::get('sync:last_pull_at','__VACIO__');" \
        2>/dev/null | grep -v '^$' | grep -v '^=>' | tail -1 || echo "__VACIO__")
    watermark="${watermark:-__VACIO__}"
    if [[ "$watermark" != "__VACIO__" && "$watermark" != "null" && -n "$watermark" ]]
    then t_pass T15 "Watermark de sync presente: ${watermark}"
    else t_fail T15 "Watermark de sync ausente" \
            "sync:pull nunca completó. Ejecuta: docker exec auxiliar_app php artisan sync:pull --force"; fi

    set -e

    # ── Resumen ────────────────────────────────────────────────────────────────
    printf '\n' >&2
    local total=$(( PASS + FAIL ))
    if [[ $FAIL -eq 0 ]]; then
        printf '%b══ RESULTADO: %d/%d PASS ✓%b\n' "$GREEN" "$PASS" "$total" "$NC" >&2
        printf 'done\n' > "$STATE_FILE"
        return 0
    else
        local ids; ids=$(printf '%s ' "${FAILED_IDS[@]}")
        printf '%b══ RESULTADO: %d/%d PASS — FALLARON: %s%b\n' "$RED" "$PASS" "$total" "$ids" "$NC" >&2
        return 1
    fi
}

# ── Resumen final ─────────────────────────────────────────────────────────────
print_summary_box() {
    local url="http://localhost:${APP_PORT:-80}/empresa/${TENANT_ID:-?}/login"
    printf '\n%b' "$GREEN" >&2
    printf '  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' >&2
    printf '  ✓  AUXILIAR INSTALADO CORRECTAMENTE\n' >&2
    printf '  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' >&2
    printf '%b' "$NC" >&2
    printf '  Acceso:    %s\n' "$url" >&2
    printf '  Tenant:    %s\n' "${TENANT_ID:-?}" >&2
    printf '  Sync:      cada 5 min (automática)\n' >&2
    printf '\n' >&2
    printf '  Logs:      docker logs -f auxiliar_app\n' >&2
    printf '  Detener:   docker compose -f %s down\n' "$COMPOSE_FILE" >&2
    printf '  Log:       %s\n' "$LOG_FILE" >&2
    printf '%b  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━%b\n\n' "$GREEN" "$NC" >&2
}

# ── Main ──────────────────────────────────────────────────────────────────────
main() {
    show_banner

    section "0/8  Detección de instalación previa"
    detect_existing_install

    section "1/8  Prerequisitos"
    check_prereqs

    section "2/8  Captura de configuración"
    collect_fields

    section "3/8  Generando .env y mariadb-init.sql"
    backup_env
    write_env
    generate_mariadb_init
    validate_env

    section "4/8  Validando docker compose"
    validate_compose

    section "5/8  Limpiando contenedores huérfanos"
    clean_orphans

    section "6/8  Manejando volúmenes"
    handle_volumes

    section "7/8  Build e inicio"
    build_and_start

    section "8/8  Tests de aceptación T1-T15"
    run_tests

    print_summary_box
}

main "$@"
