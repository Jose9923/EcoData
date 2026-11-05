# EcoData en producción

Esta configuración es independiente de `src/compose.yaml`. Se ejecuta desde `src`, con proyecto `ecodata-production`, red `172.28.241.0/24` y Nginx solo en `127.0.0.1:18081`. No contiene Adminer, bases de pruebas, datos de demostración ni inicialización automática de registros. No reutilizar volúmenes o redes de otros proyectos.

## Imágenes y persistencia

- `Dockerfile.production` construye los locks de Composer sin dependencias de desarrollo y los recursos de Vite con Node 22. El runtime PHP 8.4 no contiene Node, Composer, secretos ni archivos del almacenamiento local. PHP usa UID/GID `1000:1000`, raíz de solo lectura y capacidades retiradas.
- MySQL 8.4 utiliza el volumen `ecodata-production_mysql_data`. La inicialización oficial solo crea la base configurada, el usuario de aplicación con `SELECT/INSERT/UPDATE/DELETE` y un usuario migrador con facultades sobre esa base. Root permanece únicamente en MySQL. Cambiar secretos en el env no cambia credenciales de un volumen ya inicializado.
- `ecodata-production_storage_data` conserva `storage`, incluidos `app/private` y `app/public`. Nginx monta solo el subdirectorio `app/public`, en lectura, y permite únicamente `/storage/schools/shields/`. Los PDF y evidencias heredados de disco público continúan pasando por los controladores autorizados; el resto de `/storage` devuelve 404.
- Sesiones y cola permanecen en MySQL; Redis es caché privada y descartable, sin puertos publicados ni persistencia. `QUEUE_CONNECTION=database`, `retry_after=180` y worker `timeout=60` conservan el contrato actual.
- Seis servicios ordinarios: MySQL, Redis, PHP-FPM, Nginx, queue y scheduler. El perfil `maintenance` contiene `migrate`, que solo se ejecuta expresamente. No se invoca ningún seeder al arrancar.

El proyecto requiere Docker Engine con montajes `volume.subpath` y Docker Compose moderno (comprobado con Engine 29 y Compose 5). Las etiquetas base siguen sus series de mantenimiento; conservar en cada entrega los IDs/digests de las imágenes construidas. PHP Redis está fijado a [PECL Redis 6.3.0](https://pecl.php.net/package/redis/6.3.0).

## Configuración privada

Guardar el env fuera del checkout, con directorio `0700` y archivo `0600`. No pasarlo a `docker build`, copiarlo a la imagen ni mostrar `docker compose config` completo. Se puede generar sin imprimir secretos:

```bash
python3 docker/production/create-env.py \
  --output /opt/ecodata/private/production.env \
  --url https://ecodata.varcor.tech
```

Al restaurar datos cifrados, añadir `--app-key-file /ruta/privada/app-key` para conservar la clave del origen; ese archivo debe contener únicamente la clave y tener permisos `0600`. Las notificaciones implementan `ShouldBeEncrypted` y las sesiones pueden estar cifradas. No generar otra `APP_KEY` sobre un traslado sin evaluar esos datos.

Contrato de variables: `APP_KEY` contiene 32 bytes en base64; `APP_URL` es un origen HTTPS obligatorio. `DB_PASSWORD`, `DB_MIGRATOR_PASSWORD` y `MYSQL_ROOT_PASSWORD` son secretos nuevos, distintos, de 64 caracteres hexadecimales minúsculos. Los nombres de base y usuarios aceptan letras, dígitos y `_`, comenzando por letra; los predeterminados son `ecodata_production`, `ecodata_app` y `ecodata_migrator`. El migrador solo recibe su secreto durante la operación de mantenimiento; PHP ordinario no recibe credenciales root ni migradoras.

El generador crea además `secrets/` junto al env, con directorio `0700` y tres archivos `0444`: `mysql_root_password`, `db_password` y `db_migrator_password`. `ECODATA_SECRETS_DIR` debe apuntar a ese directorio absoluto fuera del repositorio. El permiso de los archivos permite leer el secreto montado dentro de cada contenedor; el directorio privado impide el acceso de otros usuarios del host. Compose usa fuentes `file`, compatibles con los servicios de raíz de solo lectura. No regenerar secretos sobre una DB inicializada: una instalación existente debe materializar los mismos valores del env en esos archivos y conservar sus credenciales.

La configuración inicial usa `MAIL_MAILER=array`: no envía mensajes ni registra enlaces de activación/recuperación. En una instalación nueva, las invitaciones, recuperaciones y reportes por correo no estarán operativos hasta configurar y verificar SMTP; la instalación publicada ya utiliza Brevo, como se detalla abajo. No usar `log` ni `failover` a log en producción. Mientras se difiere SMTP, procesar una notificación con transporte array la da por procesada sin entrega; no prometer un envío posterior automático.

## Activación y rotación de SMTP

La instalación publicada utiliza Brevo desde el 4 de noviembre de 2026; el generador sigue usando `array` como estado inicial seguro para nuevas instalaciones. Consultar [el informe del despliegue](../../../documentation/deployment-production.md#correo-transaccional-con-brevo) para la evidencia vigente.

Configurar el env privado con `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtp`, `MAIL_HOST=smtp-relay.brevo.com`, `MAIL_PORT=587` y `MAIL_FROM_ADDRESS=notificaciones@varcor.tech`. `MAIL_FROM_NAME` permanece en `EcoData`. El login SMTP y la clave SMTP se guardan únicamente en `MAIL_USERNAME` y `MAIL_PASSWORD` privados; no usar la contraseña del panel ni una clave API. Symfony negocia STARTTLS en el puerto 587 y debe validar el certificado.

**Omitir `MAIL_URL` tanto del env como de las asignaciones que Compose transmite a PHP.** Un valor vacío sobreescribe el transporte en Laravel 13 y causa `Unsupported mail transport []`; no usar `MAIL_URL=` ni `MAIL_URL=''`. Esta configuración utiliza `MAIL_SCHEME`, no `MAIL_ENCRYPTION`.

Tras respaldar la configuración y validarla mediante `config --quiet`, recrear `app`, `queue` y `scheduler` con `up -d --no-deps --force-recreate`; `restart` no actualiza sus variables. El entrypoint regenera `config:cache`. Recargar después Nginx (`exec -T nginx nginx -s reload`), porque su upstream debe volver a resolver el contenedor PHP recreado. Comprobar en los tres servicios el transporte `EsmtpTransport`, la configuración esperada y presencia de credenciales sin imprimirlas; verificar `/up`, HTTPS, aceptación del envío y recepción por separado.

La cuenta gratuita de Brevo comparte 300 envíos diarios entre EcoData y SysForest. Revisar el uso y estados de entrega del proveedor. Las notificaciones antiguas consumidas con `array` no se reenvían al activar SMTP: emitir una nueva invitación autorizada o usar la recuperación de contraseña sobre la misma cuenta. Los enlaces de invitación/recuperación vencen a los 60 minutos; no ampliar la vigencia ni marcar correos como verificados para ocultar una demora.

## Construcción e inicialización explícita

Todos los comandos deben usar el mismo archivo productivo y env privado:

```bash
cd /opt/ecodata/releases/RELEASE/src
dc() { docker compose --env-file /opt/ecodata/private/production.env -f compose.production.yaml "$@"; }
dc config --quiet
dc build --pull app nginx
dc up -d --wait mysql redis
```

Después elegir **un solo** flujo, según los datos autorizados:

- Instalación vacía: ejecutar `dc --profile maintenance run --rm migrate`; cargar únicamente `RolesAndPermissionsSeeder` de forma explícita con `dc --profile maintenance run --rm migrate php artisan db:seed --class=RolesAndPermissionsSeeder --force`. No ejecuta usuarios predeterminados ni datos funcionales.
- Traslado: restaurar la base y `storage/app` antes de levantar web/colas. No importar las bases de sistema MySQL ni los usuarios del servidor anterior. Conservar `APP_KEY`, comprobar conteos/hashes y ejecutar `dc --profile maintenance run --rm migrate` solo tras revisar las migraciones pendientes.

No ejecutar `SuperAdminSeeder`, `EcoDataInitialSeeder`, `EcoDataDemoSeeder`, `migrate:fresh`, `db:wipe` ni `down -v` en esta instalación. La preparación inicial autorizada utiliza `ecodata:bootstrap-school --config-file=/run/bootstrap/bootstrap-school.json --password-file=/run/bootstrap/bootstrap-password --verified-operator-emails --apply`. Omitir `--apply` permite revisar el plan sin leer la contraseña. El JSON contiene `school: {name}` y `users: [{name, email, role}]`, con cuatro cuentas y un representante de cada rol existente. El operador debe confirmar la titularidad de los correos; no utilizar este mecanismo para verificar cuentas arbitrarias. Los dos archivos se montan en lectura, fuera de la aplicación, con permisos privados y UID `1000` para que pueda leerlos el proceso. El comando solo admite una instalación vacía o una repetición que coincida exactamente; no carga demo ni envía correos. No sustituirlo por seeders con contraseñas compartidas. Revisar las cuentas de desarrollo y revocar sesiones/tokens de la copia antes de publicar.

El volumen de almacenamiento toma inicialmente la estructura y propietario de la imagen PHP. Al restaurar, conservar rutas `storage/app`, ajustar el propietario a UID/GID `1000:1000` y conservar permisos privados; no abrir toda la carpeta con `chmod 777`. No restaurar sesiones de archivos, vistas compiladas ni cachés locales. Nginx requiere que exista `storage/app/public`; el arranque de PHP lo precede.

```bash
dc up -d --wait app queue scheduler nginx
curl --fail --silent --output /dev/null -H 'Host: ecodata.varcor.tech' http://127.0.0.1:18081/up
```

`/up` comprueba el arranque de Laravel; los healthchecks independientes de MySQL/Redis comprueban los servicios. Los de queue/scheduler comprueban vida del proceso, no entrega de correos ni éxito de tareas. Verificar también login, autorización por colegio, consulta/descarga privada, formularios y recursos compilados.

## Caddy compartido y HTTPS

Añadir este sitio al Caddy existente que corre con `network_mode: host`; no sustituir ni eliminar los bloques de otros proyectos:

```caddyfile
ecodata.varcor.tech {
    reverse_proxy 127.0.0.1:18081 {
        header_up X-Real-IP {remote_host}
    }
}
```

Nginx confía en `X-Real-IP` únicamente desde `172.28.241.1` y `127.0.0.1`. Fija `HTTPS=on`, `SERVER_PORT=443` y `REQUEST_SCHEME=https` para PHP porque el único ingreso externo es el proxy TLS. No es necesario confiar indiscriminadamente en cabeceras forwarded dentro de Laravel. Si cambia red/proxy, revisar ambas configuraciones y comprobar IP real, cookies Secure y URLs; no exponer 18081 públicamente. El DNS/TLS se configura y valida por separado.

## Respaldo y restauración

Respaldar MySQL y `storage/app` como una unidad, junto con una copia privada separada de `APP_KEY` y de la configuración operativa. Los SQL y archivos contienen datos personales; usar un destino explícito fuera del repositorio, directorio `0700`, archivos `0600`, cifrado cuando corresponda y hashes SHA-256. No guardar contraseñas en argumentos, historial, logs o el SQL de negocio.

Para el volcado, dentro del contenedor MySQL, leer `MYSQL_ROOT_PASSWORD_FILE` en `MYSQL_PWD` y ejecutar `mysqldump --single-transaction --quick --routines --triggers --events --hex-blob --set-gtid-purged=OFF --no-tablespaces "$MYSQL_DATABASE"`. Redirigir a un archivo protegido en el host; omitir `--databases` para no incluir `CREATE DATABASE` ni `USE` del origen. El comando no debe volcar `mysql`, usuarios del servidor ni otros esquemas.

`--single-transaction` da coherencia a las tablas InnoDB, pero no sincroniza por sí solo la copia de archivos con la base. Para una copia coordinada, registrar los IDs de los contenedores de **este proyecto** que estaban corriendo, detener nginx/scheduler/queue/app, esperar los workers (hasta 75 s), volcar SQL y copiar `storage/app`, y reanudar esos mismos IDs con `docker start` mediante trap/finally. No usar `compose start` para arrancar dependencias o servicios que antes estaban apagados. Documentar la interrupción breve. Si se respalda online, advertir esa limitación y conciliar los archivos con sus referencias.

Restaurar primero en un proyecto/volúmenes independientes: MySQL inicializa las credenciales nuevas, importar el SQL en `DB_DATABASE` sin reemplazar los usuarios técnicos, restaurar `storage/app`, conservar `APP_KEY`, verificar tablas/conteos/hashes y aplicar solo migraciones pendientes. Mantener SMTP en array y las colas detenidas hasta revisar destinatarios/trabajos restaurados; limpiar sesiones y tokens según el procedimiento de acceso productivo. No restaurar Redis. Comprobar permisos y archivos legacy privados antes de abrir el sitio.

Se incluye `scripts/production-backup.sh` para ejecución manual explícita:

```bash
bash scripts/production-backup.sh \
  --project-dir /opt/ecodata/releases/RELEASE/src \
  --env-file /opt/ecodata/private/production.env \
  --destination /opt/ecodata/backups --dry-run
# Repetir las mismas rutas con --quiesce en vez de --dry-run para capturar.
```

El preflight comprueba proyecto, contenedores, volumen, base y permisos sin escribir ni detener servicios. La captura produce `mysql.sql.gz`, `storage-app.tar.gz`, manifiesto, notas y SHA-256. Solo finaliza con `COMPLETE` cuando se verifican lectura gzip/tar, marcadores básicos de SQL y hashes; un fallo deja `FAILED` y los archivos parciales privados. El volcado añade `--skip-add-drop-table` y se restaura en una DB vacía. La reanudación por ID ocurre también ante errores e interrupciones capturables; comprobar después salud y HTTPS.

El script no programa cron, no incluye secretos, no configura copias externas y no elimina respaldos por retención. Conservar APP_KEY/configuración/archivos de secretos por un canal privado separado. Definir periodicidad, copia externa y retención, y ensayar una restauración por separado. Un hash correcto no acredita por sí solo una restauración funcional.
