#!/usr/bin/env bash
set -euo pipefail
umask 077

usage() {
    cat <<'HELP'
Uso:
  bash scripts/production-backup.sh --project-dir /ruta/release/src \
    --env-file /ruta/privada/production.env --destination /ruta/respaldos \
    [--quiesce] [--dry-run]

Las tres rutas absolutas son obligatorias. Env y destino deben quedar fuera del
release/repositorio. El destino debe tener permisos 0700; si no existe se crea.

Incluye solamente la base MySQL de EcoData y storage/app. No incluye secretos,
APP_KEY, Redis ni una copia externa. No programa tareas ni elimina respaldos.

Sin --quiesce se permiten escrituras y SQL/archivos pueden reflejar instantes
distintos. --quiesce detiene únicamente nginx, scheduler, queue y app del proyecto
ecodata-production que estuvieran funcionando, y reactiva los mismos IDs al
terminar o fallar. Coordinar una ventana breve sin despliegues ni otros escritores.

--dry-run valida configuración y contenedores sin crear copias ni detenerlos.
La comprobación de archivos/hashes no sustituye un ensayo de restauración.
HELP
}

fail() { printf 'Error: %s\n' "$*" >&2; exit 1; }
source_argument=''
environment_argument=''
destination_argument=''
quiesce=false
dry_run=false
while [[ $# -gt 0 ]]; do
    case "$1" in
        --project-dir|--env-file|--destination)
            [[ $# -ge 2 ]] || fail "Falta el valor de $1."
            case "$1" in
                --project-dir) source_argument="$2" ;;
                --env-file) environment_argument="$2" ;;
                --destination) destination_argument="$2" ;;
            esac
            shift 2 ;;
        --quiesce) quiesce=true; shift ;;
        --dry-run) dry_run=true; shift ;;
        --help|-h) usage; exit 0 ;;
        *) fail 'Opción desconocida. Consulta --help.' ;;
    esac
done
for value in "$source_argument" "$environment_argument" "$destination_argument"; do
    [[ "$value" == /* ]] || fail 'Se requieren tres rutas absolutas explícitas.'
    [[ "$value" != *$'\n'* && "$value" != *$'\r'* ]] || fail 'Las rutas no pueden contener saltos de línea.'
done
for command in docker python3 realpath stat mktemp flock sha256sum gzip tar date; do
    command -v "$command" >/dev/null || fail "Falta la herramienta $command."
done
project_dir="$(realpath -e -- "$source_argument")"
environment_file="$(realpath -e -- "$environment_argument")"
destination="$(realpath -m -- "$destination_argument")"
[[ -d "$project_dir" && -f "$project_dir/compose.production.yaml" ]] || fail 'No existe compose.production.yaml en el directorio indicado.'
[[ -f "$environment_file" && -r "$environment_file" ]] || fail 'No se puede leer la configuración privada.'
protected_root="$project_dir"
[[ "$(basename -- "$project_dir")" != src ]] || protected_root="$(dirname -- "$project_dir")"
[[ "$destination" != / && "$destination" != "$protected_root" ]] || fail 'El destino no puede ser la raíz ni el release.'
for external_path in "$environment_file" "$destination"; do
    case "$external_path" in "$protected_root"|"$protected_root"/*) fail 'Env y destino deben estar fuera del release/repositorio.' ;; esac
done
environment_mode="$(stat -c '%a' -- "$environment_file")"
(( (8#$environment_mode & 077) == 0 )) || fail 'La configuración debe ser privada (0600 o más restrictivo).'
if [[ -e "$destination" ]]; then
    [[ -d "$destination" && "$(stat -c '%a' -- "$destination")" == 700 ]] || fail 'El destino existente debe ser un directorio 0700.'
fi

# Compose interpreta el env; nunca se ejecuta como shell ni se imprime su contenido.
compose=(docker compose --project-name ecodata-production --project-directory "$project_dir" --env-file "$environment_file" -f "$project_dir/compose.production.yaml")
"${compose[@]}" config --quiet
database="$("${compose[@]}" config --format json | python3 -c '
import json, re, sys
try:
    c = json.load(sys.stdin)
    s = c["services"]
    assert c["name"] == "ecodata-production"
    assert s["app"]["environment"]["APP_ENV"] == "production"
    assert s["app"]["environment"]["DB_CONNECTION"] == "mysql"
    name = s["app"]["environment"]["DB_DATABASE"]
    assert isinstance(name, str) and re.fullmatch(r"[a-zA-Z][a-zA-Z0-9_]{0,47}", name)
    assert name == s["mysql"]["environment"]["MYSQL_DATABASE"]
    assert name.lower() not in ("mysql", "sys", "information_schema", "performance_schema")
    print(name)
except Exception:
    sys.exit("Configuración productiva o nombre de base inválido.")
')"

declare -A containers
for service in mysql app queue scheduler nginx; do
    container="$("${compose[@]}" ps --all --quiet "$service")"
    [[ "$container" =~ ^[a-f0-9]{64}$ ]] || fail "Se requiere exactamente un contenedor existente para $service."
    actual_project="$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$container")"
    actual_service="$(docker inspect --format '{{index .Config.Labels "com.docker.compose.service"}}' "$container")"
    actual_directory="$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project.working_dir"}}' "$container")"
    [[ "$actual_project" == ecodata-production && "$actual_service" == "$service" ]] || fail 'El contenedor no pertenece a EcoData producción.'
    [[ "$(realpath -m -- "$actual_directory")" == "$project_dir" ]] || fail 'Indica el directorio real utilizado por los contenedores.'
    if [[ "$(docker inspect --format '{{.State.Paused}}' "$container")" == true ]]; then
        fail 'Un contenedor está pausado manualmente; resolver su estado antes del respaldo.'
    fi
    containers[$service]="$container"
done
[[ "$(docker inspect --format '{{.State.Running}}' "${containers[mysql]}")" == true ]] || fail 'MySQL no está funcionando.'
[[ "$(docker inspect --format '{{.State.Health.Status}}' "${containers[mysql]}")" == healthy ]] || fail 'MySQL no está saludable.'
docker exec "${containers[mysql]}" sh -c '[ "$MYSQL_DATABASE" = "$1" ] && [ -n "${MYSQL_ROOT_PASSWORD_FILE:-}" ] && [ -r "$MYSQL_ROOT_PASSWORD_FILE" ]' sh "$database" \
    >/dev/null 2>&1 || fail 'La base o el secreto montado no coincide con el contenedor MySQL.'
storage_mount="$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html/storage"}}{{.Type}}:{{.Name}}{{end}}{{end}}' "${containers[app]}")"
[[ "$storage_mount" =~ ^volume:([a-zA-Z0-9][a-zA-Z0-9_.-]+)$ ]] || fail 'No se identificó un volumen único de almacenamiento.'
storage_volume="${BASH_REMATCH[1]}"
[[ "$(docker volume inspect --format '{{index .Labels "com.docker.compose.project"}}' "$storage_volume")" == ecodata-production ]] || fail 'El volumen no pertenece al proyecto EcoData.'
for service in queue scheduler; do
    mount="$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html/storage"}}{{.Type}}:{{.Name}}{{end}}{{end}}' "${containers[$service]}")"
    [[ "$mount" == "$storage_mount" ]] || fail 'Los servicios no comparten el mismo almacenamiento.'
done
app_image="$(docker inspect --format '{{.Image}}' "${containers[app]}")"
mysql_image="$(docker inspect --format '{{.Image}}' "${containers[mysql]}")"
mode=online
$quiesce && mode=quiesced
if $dry_run; then
    printf 'Validación completada. Origen: %s\nDestino: %s\nModo: %s\nNo se crearon archivos ni se detuvieron servicios.\n' "$project_dir" "$destination" "$mode"
    exit 0
fi

exec {backup_lock}>"$(dirname -- "$environment_file")/.ecodata-production-backup.lock"
flock --nonblock "$backup_lock" || fail 'Otro respaldo está usando esta configuración.'
if [[ ! -d "$destination" ]]; then mkdir -p -m 700 -- "$destination"; fi
[[ "$(stat -c '%a' -- "$destination")" == 700 ]] || fail 'El destino no tiene permisos 0700.'
started_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
backup_dir="$(mktemp -d "$destination/backup-$(date -u +%Y%m%dT%H%M%SZ)-XXXXXX")"
chmod 700 "$backup_dir"
printf '%s\n' 'Respaldo en curso; no restaurar mientras exista este marcador.' > "$backup_dir/INCOMPLETE"
paused_ids=()
paused_names=()
resume_services() {
    local index
    local failed_ids=()
    for ((index=${#paused_ids[@]}-1; index>=0; index--)); do
        if ! docker start "${paused_ids[$index]}" >/dev/null 2>&1; then
            failed_ids+=("${paused_ids[$index]}")
        fi
    done
    paused_ids=("${failed_ids[@]}")
    [[ ${#paused_ids[@]} -eq 0 ]]
}
cleanup() {
    local status=$?
    trap - EXIT INT TERM HUP
    if ! resume_services; then
        status=1
        printf '%s\n' "${paused_ids[@]}" > "$backup_dir/SERVICES_TO_RESUME.txt"
        printf '%s\n' 'No se reactivaron todos los servicios; revisa SERVICES_TO_RESUME.txt.' >&2
    fi
    if [[ "$status" -ne 0 ]]; then
        printf '%s\n' 'Copia incompleta conservada para revisión. No es un respaldo confirmado.' > "$backup_dir/FAILED"
        printf 'Respaldo incompleto conservado en: %s\n' "$backup_dir" >&2
    fi
    exit "$status"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP

if $quiesce; then
    for service in nginx scheduler queue app; do
        container="${containers[$service]}"
        if [[ "$(docker inspect --format '{{.State.Running}}' "$container")" == true ]]; then
            paused_ids+=("$container")
            paused_names+=("$service")
            docker stop --time 75 "$container" >/dev/null 2>&1
        fi
    done
fi

# La contraseña se lee dentro de MySQL; no se pasa en argumentos ni se respalda.
# Sin --databases no se genera CREATE DATABASE ni se cambia al nombre del origen.
if ! docker exec "${containers[mysql]}" sh -c '
    set -eu
    MYSQL_PWD=$(cat "$MYSQL_ROOT_PASSWORD_FILE")
    export MYSQL_PWD
    exec mysqldump --user=root --protocol=socket --single-transaction --quick \
        --routines --events --triggers --no-tablespaces --set-gtid-purged=OFF \
        --hex-blob --skip-add-drop-table "$1"
' sh "$database" 2>/dev/null | gzip -c > "$backup_dir/mysql.sql.gz.partial"; then
    fail 'Falló el volcado MySQL; no se imprimen errores que puedan contener información privada.'
fi
if ! docker run --rm --network none --read-only --user 0:0 --cap-drop ALL --cap-add DAC_READ_SEARCH \
    --security-opt no-new-privileges:true \
    --mount "type=volume,source=$storage_volume,target=/snapshot,readonly" \
    --entrypoint tar "$app_image" --numeric-owner --one-file-system -czf - -C /snapshot app \
    > "$backup_dir/storage-app.tar.gz.partial" 2>/dev/null; then
    fail 'Falló la captura de archivos; una copia online puede requerir --quiesce por escrituras concurrentes.'
fi
snapshot_finished_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
resume_services || fail 'No fue posible reactivar todos los servicios después de la captura.'

for archive in mysql.sql.gz storage-app.tar.gz; do
    [[ -s "$backup_dir/$archive.partial" ]] || fail 'Se obtuvo un archivo de respaldo vacío.'
    gzip -t "$backup_dir/$archive.partial" 2>/dev/null || fail 'Un archivo comprimido no superó su verificación.'
done
tar -tzf "$backup_dir/storage-app.tar.gz.partial" >/dev/null 2>&1 || fail 'El archivo de almacenamiento no se puede leer.'
python3 - "$backup_dir/mysql.sql.gz.partial" <<'PY'
import gzip, sys
try:
    header = table = completed = False
    tail = b""
    with gzip.open(sys.argv[1], "rb") as stream:
        while chunk := stream.read(1024 * 1024):
            sample = tail + chunk
            header |= b"-- MySQL dump" in sample
            table |= b"CREATE TABLE " in sample
            completed |= b"-- Dump completed on " in sample
            tail = sample[-128:]
    if not (header and table and completed):
        raise ValueError()
except Exception:
    sys.exit("El volcado no supera las comprobaciones básicas de integridad; no se restauró SQL.")
PY
for archive in mysql.sql.gz storage-app.tar.gz; do
    mv -- "$backup_dir/$archive.partial" "$backup_dir/$archive"
done

python3 - "$backup_dir/manifest.json" "$mode" "$started_at" "$snapshot_finished_at" "$project_dir" \
    "$database" "$app_image" "$mysql_image" "${paused_names[*]}" <<'PY'
import json, sys
path, mode, started, captured, source, database, app, mysql, paused = sys.argv[1:]
manifest = {
    "format": "ECODATA_PRODUCTION_BACKUP_V1",
    "mode": mode,
    "started_at_utc": started,
    "capture_finished_at_utc": captured,
    "release_directory": source,
    "mysql_database": database,
    "storage_scope": "storage/app",
    "image_ids": {"app": app, "mysql": mysql},
    "services_stopped_for_capture": paused.split(),
    "secrets_included": False,
    "redis_included": False,
    "offsite_copy_created": False,
    "restore_rehearsal_performed": False,
    "consistency": "Application services stopped; independent database/file writers must also be excluded." if mode == "quiesced" else "Online SQL and file exports can reflect different instants; no cross-store atomic snapshot is claimed.",
    "validation": "gzip/tar readability, SQL dump markers, streaming SHA-256; no SQL execution or restore rehearsal.",
}
with open(path, "w", encoding="utf-8") as output:
    json.dump(manifest, output, ensure_ascii=False, indent=2)
    output.write("\n")
PY
cat > "$backup_dir/RESTORE_NOTES.txt" <<'NOTES'
Copia privada: incluye datos personales, hashes de usuarios y archivos.
No incluye .env, APP_KEY, credenciales técnicas, Redis ni una copia externa.
Conservar configuración y APP_KEY separadamente con acceso restringido. No cambiar
la clave sin evaluar sesiones/payloads cifrados. No hay programación ni retención.

Verificar SHA256SUMS. gzip/tar y marcadores de SQL comprueban lectura básica;
no equivalen a ejecutar SQL ni a una restauración funcional de la aplicación.

Restaurar primero en un proyecto/volúmenes independientes y con SMTP array.
Inicializar MySQL con roles técnicos nuevos mediante el bootstrap productivo.
Importar mysql.sql.gz en la DB de destino, vacía, sin reemplazar usuarios MySQL:
el dump no incluye bases del sistema ni CREATE DATABASE/USE del origen, y omite
DROP TABLE. Revisar DEFINER de routines/events/triggers si existen; no ampliar
permisos globales para el usuario de aplicación. Conservar propietarios/permisos
de storage/app compatibles con UID/GID 1000:1000 y su alcance privado.
No restaurar cachés de Redis ni publicar PDFs/evidencias legacy por /storage.

Mantener colas detenidas hasta revisar trabajos, sesiones, tokens y destinatarios
restaurados. Aplicar únicamente migraciones pendientes, nunca seeders destructivos.
Comprobar conteos, archivos, permisos institucionales y login antes de abrir el sitio.
--quiesce coordina solo los servicios de esta instalación; excluir escritores
externos durante la captura. En modo online, conciliar referencias SQL y archivos.

El script reanuda los mismos IDs que estaban corriendo y detuvo para la copia.
Comprobar salud y HTTPS tras la ejecución. Una señal SIGKILL o fallo del host no
puede ser recuperado por trap; revisar servicios antes de volver a abrir el sitio.
NOTES
(
    cd "$backup_dir"
    sha256sum mysql.sql.gz storage-app.tar.gz manifest.json RESTORE_NOTES.txt > SHA256SUMS
    sha256sum --check --status SHA256SUMS
)
printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$backup_dir/COMPLETE"
rm -- "$backup_dir/INCOMPLETE"
printf 'Respaldo completado y archivos verificados: %s\nModo: %s. No se ejecutó restauración ni copia externa.\n' "$backup_dir" "$mode"
