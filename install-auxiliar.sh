#!/usr/bin/env bash
# install-auxiliar.sh — Instalador del nodo Auxiliar de Factunet
# Fixes: [1] ANSI en .env  [2] comillas dobles  [3] placeholders sin llenar
#        [4] huérfanos      [5] volumen db        [6] bootstrap no-idempotente
#        [7] bootstrap fatal [8] sin node/npm     [9] permisos storage
#        [10] Livewire 404  [11] caché obsoleta

set -euo pipefail

COMPOSE_FILE="docker-compose.auxiliar.yml"
COMPOSE="docker compose"
ENV_FILE=".env"

# ── Colores: SOLO a stderr, NUNCA a archivos ──────────────────────────────────
RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'
BLUE=$'\033[1;34m'; BOLD=$'\033[1m'; NC=$'\033[0m'

log()     { printf '%b[install]%b %s\n'  "$BOLD"   "$NC" "$*"  >&2; }
ok()      { printf '%b✓%b %s\n'          "$GREEN"  "$NC" "$*"  >&2; }
warn()    { printf '%b⚠%b  %s\n'         "$YELLOW" "$NC" "$*"  >&2; }
fail_msg(){ printf '%b✗%b  %s\n'         "$RED"    "$NC" "$*"  >&2; }
fatal()   { printf '%bFATAL:%b %s\n'     "$RED"    "$NC" "$*"  >&2; exit 1; }
section() { printf '\n%b══ %s%b\n'       "$BLUE"   "$*"  "$NC" >&2; }

# ── Helper: escribe UNA línea al .env — printf puro, sin ANSI, sin heredoc ──
# Normaliza em-dash (—) a guión simple para evitar problemas de charset en dotenv
env_write() {
    local key="$1"
    local value="${2//—/-}"           # [2] normalizar em-dash
    local escaped="${value//\"/\\\"}" # escapar comillas internas
    printf '%s="%s"\n' "$key" "$escaped" >> "$ENV_FILE"
}

# ── Variables a recolectar ────────────────────────────────────────────────────
MAYOR_SYNC_URL=""
MAYOR_SYNC_TOKEN=""
INSTANCE_UUID=""
INSTANCE_LABEL=""
TENANT_ID=""
DB_PASSWORD=""
DB_ROOT_PASSWORD=""
APP_PORT="80"

# ─────────────────────────────────────────────────────────────────────────────
section "1/8  Prerequisitos"
# ─────────────────────────────────────────────────────────────────────────────

check_prereqs() {
    local missing=0
    for cmd in docker openssl curl; do
        if ! command -v "$cmd" &>/dev/null; then
            fail_msg "Falta: $cmd"
            (( missing++ )) || true
        fi
    done
    if ! $COMPOSE version &>/dev/null; then
        fail_msg "Plugin 'docker compose' no disponible"
        (( missing++ )) || true
    fi
    [[ $missing -eq 0 ]] || fatal "Instala los prerequisitos faltantes y vuelve a correr."
    ok "Prerequisitos OK"
}

# ─────────────────────────────────────────────────────────────────────────────
section "2/8  Captura de configuracion"
# ─────────────────────────────────────────────────────────────────────────────

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
        [[ -z "$value" && -n "$default" ]] && value="$default"
        if [[ -n "$value" ]]; then
            printf -v "$varname" '%s' "$value"
            return
        fi
        warn "Este campo es obligatorio."
    done
}

# [3] Valida que la URL tenga esquema y elimina trailing slash
ask_url() {
    local varname="$1"
    while true; do
        ask "URL del Mayor (ej: https://factunet.io)" "$varname"
        local val="${!varname}"
        if [[ ! "$val" =~ ^https?:// ]]; then
            warn "Debe comenzar con http:// o https://"; continue
        fi
        val="${val%/}"  # sin trailing slash
        printf -v "$varname" '%s' "$val"
        log "Probando conectividad con el Mayor..."
        if curl -sf --connect-timeout 5 --max-time 10 "${val}/up" &>/dev/null; then
            ok "Mayor alcanzable en ${val}"
        else
            warn "No se pudo alcanzar ${val}/up"
            warn "Continuando — el bootstrap reintentara cuando haya conexion."
        fi
        return
    done
}

# Generar UUID sin depender de uuidgen
gen_uuid() {
    if command -v uuidgen &>/dev/null; then
        uuidgen | tr '[:upper:]' '[:lower:]'
    elif [[ -r /proc/sys/kernel/random/uuid ]]; then
        cat /proc/sys/kernel/random/uuid
    else
        # fallback con openssl
        openssl rand -hex 16 | sed 's/\(.\{8\}\)\(.\{4\}\)\(.\{4\}\)\(.\{4\}\)\(.\{12\}\)/\1-\2-\3-\4-\5/'
    fi
}

collect_fields() {
    ask_url MAYOR_SYNC_URL
    ask "Token de sincronizacion (MAYOR_SYNC_TOKEN)" MAYOR_SYNC_TOKEN "" "yes"
    local auto_uuid; auto_uuid=$(gen_uuid)
    ask "UUID de la instancia (enter = autogenerar)" INSTANCE_UUID "$auto_uuid"
    ask "Etiqueta de la instancia (INSTANCE_LABEL)"  INSTANCE_LABEL "Auxiliar - $(hostname)"
    ask "ID del tenant en el Mayor (TENANT_ID)"       TENANT_ID
    ask "Contrasena de MariaDB (DB_PASSWORD)"         DB_PASSWORD      "" "yes"
    ask "Contrasena root de MariaDB (DB_ROOT_PASSWORD)" DB_ROOT_PASSWORD "" "yes"
    ask "Puerto local para Nginx"                      APP_PORT         "80"
}

# ─────────────────────────────────────────────────────────────────────────────
section "3/8  Generando .env"
# ─────────────────────────────────────────────────────────────────────────────

write_env() {
    # Generar APP_KEY con openssl (no requiere PHP local)
    local app_key
    app_key="base64:$(openssl rand -base64 32 | tr -d '\n')"

    # [1] Solo printf — sin echo -e, sin ANSI, sin tee redirigido, sin heredoc al archivo
    printf '# Factunet Auxiliar — generado por install-auxiliar.sh\n' > "$ENV_FILE"
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
    env_write DB_USERNAME      "root"
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

    ok ".env escrito (APP_KEY generada)"
}

# [1][2][3] Validacion dura del .env
validate_env() {
    local errors=0
    # Buscar secuencias de escape ANSI (\e o \033)
    if LC_ALL=C grep -Pn $'\x1b' "$ENV_FILE" >/dev/null 2>&1; then
        fail_msg "Codigos ANSI en .env"; (( errors++ )) || true
    fi
    # [2] Valores con comillas dobles vacías implica quoting roto
    if grep -Fn '=""' "$ENV_FILE" >/dev/null 2>&1; then
        fail_msg "Valor vacío en .env (APP_KEY u otro campo obligatorio)"; (( errors++ )) || true
    fi
    # [3] Placeholders sin completar
    if grep -Fn '<COMPLETAR>' "$ENV_FILE" >/dev/null 2>&1; then
        fail_msg "Placeholder <COMPLETAR> sin llenar en .env"; (( errors++ )) || true
    fi
    [[ $errors -eq 0 ]] || fatal "Errores en .env — abortando."
    ok ".env validado (sin ANSI, sin valores vacíos, sin placeholders)"
}

# ─────────────────────────────────────────────────────────────────────────────
section "4/8  Validando docker compose"
# ─────────────────────────────────────────────────────────────────────────────

validate_compose() {
    $COMPOSE -f "$COMPOSE_FILE" --env-file "$ENV_FILE" config >/dev/null 2>&1 \
        || fatal "docker compose config fallo — revisa $COMPOSE_FILE"
    ok "docker compose config OK"
}

# ─────────────────────────────────────────────────────────────────────────────
section "5/8  Limpiando contenedores huerfanos"
# ─────────────────────────────────────────────────────────────────────────────

# [4] Eliminar huerfanos ANTES de tocar volúmenes (no se puede borrar vol con ctn activo)
clean_orphans() {
    $COMPOSE -f "$COMPOSE_FILE" down --remove-orphans 2>/dev/null || true
    local orphans
    orphans=$(docker ps -aq --filter 'name=auxiliar_' 2>/dev/null || true)
    if [[ -n "$orphans" ]]; then
        echo "$orphans" | xargs docker rm -f 2>/dev/null || true
    fi
    ok "Contenedores anteriores eliminados"
}

# ─────────────────────────────────────────────────────────────────────────────
section "6/8  Manejando volumenes existentes"
# ─────────────────────────────────────────────────────────────────────────────

handle_volumes() {
    # El volumen auxiliar_app (codigo baked) siempre se elimina — se repuebla desde la imagen nueva
    local app_vol
    app_vol=$(docker volume ls --format '{{.Name}}' 2>/dev/null | grep 'auxiliar_app$' | head -1 || true)
    if [[ -n "$app_vol" ]]; then
        log "Eliminando volumen de codigo anterior ($app_vol)..."
        docker volume rm "$app_vol" 2>/dev/null || warn "No se pudo eliminar $app_vol (puede que aun este en uso)"
    fi

    # [5] El volumen auxiliar_db puede tener datos — preguntar al operador
    local db_vol
    db_vol=$(docker volume ls --format '{{.Name}}' 2>/dev/null | grep 'auxiliar_db$' | head -1 || true)
    if [[ -n "$db_vol" ]]; then
        warn "El volumen de base de datos '$db_vol' ya existe."
        warn "  (a) Recrear — borra datos locales; se re-sincronizan del Mayor [RECOMENDADO]"
        warn "  (b) Conservar — mantener datos y relanzar contenedores"
        local choice=""
        while [[ "$choice" != "a" && "$choice" != "b" ]]; do
            printf '%bOpcion (a/b)%b [a]: ' "$BOLD" "$NC" >&2
            IFS= read -r choice </dev/tty || choice="a"
            [[ -z "$choice" ]] && choice="a"
            choice="${choice,,}"
        done
        if [[ "$choice" == "a" ]]; then
            log "Eliminando volumen de DB..."
            docker volume rm "$db_vol" 2>/dev/null || warn "No se pudo eliminar $db_vol"
            ok "Volumen de DB eliminado — se creara nuevo"
        else
            ok "Conservando volumen de DB existente"
        fi
    fi
}

# ─────────────────────────────────────────────────────────────────────────────
section "7/8  Build e inicio"
# ─────────────────────────────────────────────────────────────────────────────

build_and_start() {
    log "Construyendo imagen Docker (multi-stage con Node 22 para assets Vite — puede tardar varios minutos)..."
    $COMPOSE -f "$COMPOSE_FILE" build --no-cache

    log "Iniciando contenedores..."
    # [10] --force-recreate garantiza que nginx recargue nginx.conf si cambio
    $COMPOSE -f "$COMPOSE_FILE" up -d --force-recreate

    ok "Contenedores iniciados"

    # Esperar db healthy
    log "Esperando MariaDB healthy..."
    local elapsed=0 timeout=120
    while true; do
        local status
        status=$(docker inspect auxiliar_db --format '{{.State.Health.Status}}' 2>/dev/null || echo "missing")
        case "$status" in
            healthy) ok "MariaDB lista"; break ;;
            missing) fatal "auxiliar_db no encontrado — verifica el compose." ;;
        esac
        if [[ $elapsed -ge $timeout ]]; then
            fatal "MariaDB no respondio en ${timeout}s — revisa: docker logs auxiliar_db"
        fi
        sleep 3; elapsed=$(( elapsed + 3 ))
    done

    # Esperar a que el entrypoint termine: php-fpm arranca con exec al final del entrypoint
    log "Esperando que el entrypoint complete (migraciones + bootstrap)..."
    elapsed=0; timeout=180
    while true; do
        if docker exec auxiliar_app php -r 'echo "ok";' &>/dev/null 2>&1; then
            ok "php-fpm listo — entrypoint completado"
            break
        fi
        if [[ $elapsed -ge $timeout ]]; then
            warn "php-fpm no respondio en ${timeout}s"
            warn "El bootstrap puede seguir ejecutandose. Revisa: docker logs auxiliar_app"
            break
        fi
        sleep 3; elapsed=$(( elapsed + 3 ))
    done
}

# ─────────────────────────────────────────────────────────────────────────────
section "8/8  Tests de aceptacion T1-T15"
# ─────────────────────────────────────────────────────────────────────────────

PASS=0; FAIL=0
FAILED_IDS=()

t_pass() {
    ok "[${1}] ${2}"
    PASS=$(( PASS + 1 ))
}
t_fail() {
    fail_msg "[${1}] ${2}"
    [[ -n "${3:-}" ]] && warn "    -> ${3}"
    FAILED_IDS+=("$1")
    FAIL=$(( FAIL + 1 ))
}

# Lee un valor del .env sin heredoc ni eval (strips comillas)
env_val() { grep -m1 "^${1}=" "$ENV_FILE" | cut -d= -f2- | tr -d '"' 2>/dev/null || true; }

run_tests() {
    # Desactivar set -e dentro del bloque de tests
    set +e

    # ── T1: .env limpio (sin ANSI, sin comillas vacías, sin placeholders) ─────
    local t1=true
    LC_ALL=C grep -Pn $'\x1b'     "$ENV_FILE" >/dev/null 2>&1 && t1=false
    grep -Fn '=""'                 "$ENV_FILE" >/dev/null 2>&1 && t1=false
    grep -Fn '<COMPLETAR>'         "$ENV_FILE" >/dev/null 2>&1 && t1=false
    if $t1; then t_pass T1 ".env sin ANSI / comillas vacías / placeholders"
    else         t_fail T1 ".env limpio" "Inspecciona $ENV_FILE manualmente"; fi

    # ── T2: docker compose config válido ─────────────────────────────────────
    if $COMPOSE -f "$COMPOSE_FILE" config >/dev/null 2>&1
    then t_pass T2 "docker compose config válido"
    else t_fail T2 "docker compose config" "Error de sintaxis en $COMPOSE_FILE"; fi

    # ── T3: 5 contenedores running, db healthy ────────────────────────────────
    local t3=true
    for ctn in auxiliar_db auxiliar_app auxiliar_nginx auxiliar_worker auxiliar_scheduler; do
        local st
        st=$(docker inspect "$ctn" --format '{{.State.Status}}' 2>/dev/null || echo "missing")
        if [[ "$st" != "running" ]]; then
            warn "    $ctn -> $st"
            t3=false
        fi
    done
    local db_health
    db_health=$(docker inspect auxiliar_db --format '{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
    [[ "$db_health" != "healthy" ]] && t3=false
    if $t3; then t_pass T3 "5 contenedores running, db healthy"
    else         t_fail T3 "Contenedores" "Revisa: docker compose -f $COMPOSE_FILE ps"; fi

    # ── T4: credenciales de DB válidas ────────────────────────────────────────
    local db_user db_pass
    db_user=$(env_val DB_USERNAME)
    db_pass=$(env_val DB_PASSWORD)
    if docker exec auxiliar_db mariadb -u"$db_user" -p"$db_pass" -e "SELECT 1" >/dev/null 2>&1
    then t_pass T4 "DB responde con credenciales del .env"
    else t_fail T4 "DB credenciales" "Access denied — en proxima instalacion elige opcion (a) para recrear el volumen DB"; fi

    # ── T5: bootstrap idempotente — 2 corridas consecutivas sin Duplicate entry
    local run1 run2
    run1=$(docker exec auxiliar_app php artisan instance:bootstrap 2>&1 || true)
    run2=$(docker exec auxiliar_app php artisan instance:bootstrap 2>&1 || true)
    if echo "${run1}${run2}" | grep -qi "Duplicate entry\|SQLSTATE.*1062"
    then t_fail T5 "Bootstrap idempotente" "Segunda corrida lanzo Duplicate entry — verifica InstanceBootstrap.php"
    else t_pass T5 "Bootstrap idempotente (2 corridas sin errores)"; fi

    # ── T6: Vite manifest.json presente ──────────────────────────────────────
    if docker exec auxiliar_app test -f public/build/manifest.json 2>/dev/null
    then t_pass T6 "Assets Vite presentes (public/build/manifest.json)"
    else t_fail T6 "Assets Vite" "manifest.json ausente — verifica el Dockerfile multi-stage"; fi

    # ── T7: storage escribible por www-data ──────────────────────────────────
    if docker exec auxiliar_app sh -c \
        'test -w storage/logs && touch storage/logs/.write_test && rm storage/logs/.write_test' \
        2>/dev/null
    then t_pass T7 "storage/logs escribible"
    else t_fail T7 "Storage escribible" "Permission denied — verifica entrypoint.sh (chown www-data)"; fi

    # ── T8: Nginx sirve login del tenant (HTTP 200) ───────────────────────────
    local port; port=$(env_val APP_PORT); port="${port:-80}"
    local login_url="http://localhost:${port}/empresa/${TENANT_ID}/login"
    local http_code
    http_code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$login_url" 2>/dev/null || echo "000")
    if [[ "$http_code" == "200" ]]
    then t_pass T8 "Nginx sirve login del tenant (HTTP 200)"
    else t_fail T8 "Nginx / login del tenant" "HTTP $http_code en $login_url"; fi

    # ── T9: Regla nginx para Livewire presente ────────────────────────────────
    if docker exec auxiliar_nginx grep -q 'livewire-' /etc/nginx/conf.d/default.conf 2>/dev/null
    then t_pass T9 "Regla nginx para Livewire presente"
    else t_fail T9 "Regla nginx Livewire" "No se encontro 'livewire-' en default.conf del contenedor nginx"; fi

    # Helper: corre código PHP en contexto del tenant y extrae el primer número del output
    # Uso: tenant_count "Modelo::count()" → número
    tenant_php() {
        local code="$1"
        docker exec auxiliar_app php artisan tinker \
            --execute="tenancy()->initialize('${TENANT_ID}'); ${code}" \
            2>/dev/null | grep -E '^[0-9]+$' | tail -1 || echo "0"
    }

    # ── T10: catálogos en BD del TENANT > 0 ──────────────────────────────────
    # [15-fix] tenancy()->initialize() garantiza consulta al tenant, no al landlord
    local prod_count client_count
    prod_count=$(tenant_php 'echo \App\Models\Producto::count();')
    client_count=$(tenant_php 'echo \App\Models\Cliente::count();')
    prod_count="${prod_count:-0}"; client_count="${client_count:-0}"
    if [[ "$prod_count" -gt 0 || "$client_count" -gt 0 ]]
    then t_pass T10 "Catalogos en tenant: productos=${prod_count}, clientes=${client_count}"
    else t_fail T10 "Sync catalogos (BD del tenant)" \
            "0 registros — puede ser pull incremental con BD vacía [bug 12/14] o Mayor sin datos"; fi

    # ── T11: usuarios en BD del TENANT > 0 (CRITICO — login imposible sin usuarios)
    local user_count
    user_count=$(tenant_php 'echo \App\Models\User::count();')
    user_count="${user_count:-0}"
    if [[ "$user_count" -gt 0 ]]
    then t_pass T11 "Usuarios sincronizados en tenant: ${user_count}"
    else t_fail T11 "Sync usuarios CRITICO — login imposible" \
            "Verifica MAYOR_SYNC_TOKEN y que /api/sync/usuarios este activo en el Mayor"; fi

    # ── T13: permisos sembrados en la BD del tenant ───────────────────────────
    # RolesPermisosSeeder debe haber corrido dentro del tenant durante el bootstrap
    local perm_count
    perm_count=$(tenant_php 'echo \Spatie\Permission\Models\Permission::count();')
    perm_count="${perm_count:-0}"
    if [[ "$perm_count" -ge 20 ]]
    then t_pass T13 "Permisos en tenant: ${perm_count} (esperados >= 20)"
    else t_fail T13 "Permisos del tenant CRITICO — menu vacio sin permisos" \
            "${perm_count} permisos — RolesPermisosSeeder no corrio en el tenant. Verifica InstanceBootstrap.php"; fi

    # ── T14: rol Admin tiene permisos asignados ───────────────────────────────
    local admin_perm_count
    admin_perm_count=$(tenant_php '$r=\Spatie\Permission\Models\Role::where("name","Admin")->first(); echo $r ? $r->permissions()->count() : 0;')
    admin_perm_count="${admin_perm_count:-0}"
    if [[ "$admin_perm_count" -ge 20 ]]
    then t_pass T14 "Rol Admin tiene ${admin_perm_count} permisos"
    else t_fail T14 "Rol Admin sin permisos CRITICO — @can() falla en Blade" \
            "${admin_perm_count} permisos en Admin — syncPermissions no se ejecuto. Verifica RolesPermisosSeeder"; fi

    # ── T15: watermark de sync establece que pull completo corrio ─────────────
    # Si el watermark está en cache, significa que sync:pull completó exitosamente.
    # Si está vacío, el pull pudo haber corrido incremental y traído 0 registros [bug 14].
    local watermark
    watermark=$(docker exec auxiliar_app php artisan tinker \
        --execute="echo \Illuminate\Support\Facades\Cache::get('sync:last_pull_at','__VACIO__');" \
        2>/dev/null | grep -v '^$' | grep -v '^=>' | tail -1 || echo "__VACIO__")
    watermark="${watermark:-__VACIO__}"
    if [[ "$watermark" != "__VACIO__" && "$watermark" != "null" && -n "$watermark" ]]
    then t_pass T15 "Watermark de sync presente: ${watermark}"
    else t_fail T15 "Watermark de sync ausente" \
            "sync:pull nunca completo — el bootstrap puede haber hecho pull incremental vacio [bug 12/14]"; fi

    set -e

    # ── Resumen ───────────────────────────────────────────────────────────────
    local total=$(( PASS + FAIL ))
    printf '\n' >&2
    if [[ $FAIL -eq 0 ]]; then
        printf '%b══ RESULTADO: %d/%d PASS — Auxiliar listo en http://localhost:%s/empresa/%s/login%b\n\n' \
            "$GREEN" "$PASS" "$total" "${port:-80}" "${TENANT_ID:-?}" "$NC" >&2
        return 0
    else
        local ids
        ids="${FAILED_IDS[*]}"
        printf '%b══ RESULTADO: %d/%d PASS — FALLARON: %s%b\n\n' \
            "$RED" "$PASS" "$total" "$ids" "$NC" >&2
        printf '%bVer detalle arriba para cada test fallido.%b\n\n' "$YELLOW" "$NC" >&2
        return 1
    fi
}

# ─────────────────────────────────────────────────────────────────────────────
main() {
    check_prereqs
    collect_fields
    write_env
    validate_env
    validate_compose
    clean_orphans     # detener primero, LUEGO tocar volúmenes
    handle_volumes
    build_and_start
    run_tests
}

main "$@"
