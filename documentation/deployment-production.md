# EcoData: despliegue de producción

Actualizado el 4 de noviembre de 2026. **Publicado y verificado:** [acceso a EcoData](https://ecodata.varcor.tech/login). Se instaló una base nueva con un colegio y cuatro cuentas iniciales; no se copiaron datos del entorno local. Brevo SMTP está activado; las pruebas fueron aceptadas por el transporte y el usuario confirmó su recepción.

## Entorno publicado

| Elemento | Configuración verificada |
| --- | --- |
| Dirección pública | `https://ecodata.varcor.tech`, con acceso en `/login` |
| DNS y TLS | Registro A `179.66.133.169`; TLS 1.3 y certificado Let's Encrypt válido para el dominio hasta el 2 de febrero de 2027 en la comprobación del despliegue; HTTP redirige a HTTPS con 308 |
| Servidor | VPS Hostinger compartido con SysForest, Ubuntu 26.04; 4 CPU, 16 GB de RAM y 200 GB de almacenamiento |
| Aplicación | Laravel 13, PHP 8.4, MySQL 8.4 y Redis |
| Proyecto y Compose | `ecodata-production`, `compose.production.yaml` |
| Release | `/opt/ecodata/releases/20261104-initial` |
| Configuración privada | `/opt/ecodata/private/production.env`, fuera del código, con permisos `0600` |
| Secretos montados | Directorio privado `0700`, archivos `0444`; fuentes Compose de tipo `file` y acceso limitado por servicio |
| Entrada de la aplicación | `127.0.0.1:18081`, detrás del Caddy compartido |
| Red Docker | `172.28.241.0/24` |
| Servicios ordinarios | `mysql`, `redis`, `app`, `queue`, `scheduler` y `nginx`: los seis comprobados como `healthy` |
| Correo | Brevo SMTP, puerto 587 con STARTTLS; remitente `notificaciones@varcor.tech` autenticado. Prueba aceptada por el transporte y recepción confirmada por el usuario. |

En este VPS, **el contenido de `src/` está directamente en la raíz del release**: los comandos siguientes no llevan `/src` al final de `/opt/ecodata/releases/20261104-initial`. El proyecto, los volúmenes y la red son independientes de SysForest; su pantalla de acceso continuó respondiendo 200 tras publicar EcoData. Los recursos del VPS son compartidos y no se ha realizado una prueba de carga.

Caddy termina HTTPS y reenvía a Nginx por el puerto local. La configuración de Nginx fija el esquema HTTPS y limita la confianza en la dirección reenviada al proxy de esta red. Las cookies comprobadas tienen `Secure`, `HttpOnly` y `SameSite=Lax`. MySQL y Redis no tienen puertos públicos.

El directorio privado del host protege los archivos de secretos; el modo `0444` permite leerlos en los contenedores que los reciben. La aplicación ordinaria no recibe la contraseña root ni la del migrador. No imprimir `docker compose config` completo: puede mostrar valores privados; usar `config --quiet` para validar.

## Datos y cuentas iniciales

Se aplicaron **24 migraciones** y únicamente `RolesAndPermissionsSeeder` para cargar roles y permisos. El alta transaccional creó **Institución Educativa Jorge Eliécer Gaitán** y cuatro cuentas activas con los correos indicados por el responsable:

| Cuenta inicial | Rol | Colegio |
| --- | --- | --- |
| Administración general | `super_admin` | Sin colegio (`NULL`) |
| Administración del colegio | `admin_colegio` | Colegio creado, ID 1 |
| Docente | `docente` | Colegio creado, ID 1 |
| Estudiante | `estudiante` | Colegio creado, ID 1 |

Los nombres de presentación corresponden al rol. Documentos de identidad, grados y cursos permanecen sin asignar (`NULL`). Las contraseñas se almacenan mediante hash; sus valores no forman parte de este documento. Las cuentas se marcaron como verificadas mediante confirmación explícita del operador, sin enviar mensajes ni acreditar una verificación por correo recibido.

No se cargaron mediciones, guías, eventos ni otros datos funcionales o catálogos de demostración. La instalación local permanece intacta y operativa, sin sincronización hacia producción. Se generó una `APP_KEY` nueva y no se importaron sesiones, enlaces de recuperación ni trabajos locales.

## Comandos de operación e inicialización

Ejecutar en el VPS con una cuenta autorizada para Docker y la configuración privada. Esta función fija el proyecto y las rutas de **esta instalación**:

```bash
cd /opt/ecodata/releases/20261104-initial
ecodata_compose() {
  docker compose --project-name ecodata-production \
    --project-directory /opt/ecodata/releases/20261104-initial \
    --env-file /opt/ecodata/private/production.env \
    -f /opt/ecodata/releases/20261104-initial/compose.production.yaml "$@"
}
ecodata_compose config --quiet
ecodata_compose ps
ecodata_compose exec -T app php artisan migrate:status
curl --fail --silent --output /dev/null https://ecodata.varcor.tech/up
```

La inicialización ya realizada empleó los siguientes comandos de mantenimiento. No son pasos pendientes ni instrucciones para vaciar o reinicializar la producción:

```bash
ecodata_compose --profile maintenance run --rm migrate
ecodata_compose --profile maintenance run --rm migrate \
  php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

El comando `ecodata:bootstrap-school` recibe dos archivos externos: un JSON con `school: {name}` y `users: [{name, email, role}]` para las cuatro cuentas y un archivo separado con la contraseña elegida. Ambos deben ser archivos regulares, sin enlaces simbólicos, con permisos `0400` o `0600` y accesibles para UID `1000`. En este despliegue se usaron archivos `0400`, propietario `1000`, dentro del directorio privado `0700`. Cada archivo se montó individualmente en lectura bajo `/run/bootstrap`; no forma parte de la imagen, el repositorio o una carpeta pública.

Con los archivos privados preparados, la vista previa y la aplicación usan este contrato. Estos son los montajes de la inicialización realizada; el archivo temporal de contraseña ya se eliminó después de verificar el alta, por lo que el segundo comando no es una tarea pendiente que deba repetirse:

```bash
ecodata_compose run --rm --no-deps \
  --volume /opt/ecodata/private/bootstrap-school.json:/run/bootstrap/bootstrap-school.json:ro \
  app php artisan ecodata:bootstrap-school \
  --config-file=/run/bootstrap/bootstrap-school.json

ecodata_compose run --rm --no-deps \
  --volume /opt/ecodata/private/bootstrap-school.json:/run/bootstrap/bootstrap-school.json:ro \
  --volume /opt/ecodata/private/bootstrap-password:/run/bootstrap/bootstrap-password:ro \
  app php artisan ecodata:bootstrap-school \
  --config-file=/run/bootstrap/bootstrap-school.json \
  --password-file=/run/bootstrap/bootstrap-password \
  --verified-operator-emails --apply
```

Omitir `--apply` muestra el plan sin leer la contraseña ni cambiar datos. Aplicar requiere confirmar el control de los correos con `--verified-operator-emails`. El comando solo permite una instalación vacía o una repetición que coincida exactamente con el alta; conserva las cuentas existentes sin cambiar sus contraseñas y rechaza configuraciones distintas. No sustituye una herramienta general de administración de usuarios. Se conserva el JSON privado de configuración; el archivo temporal de contraseña se eliminó tanto del VPS como del entorno de preparación. La contraseña no se pasa como argumento ni se incluye en la documentación.

## Verificaciones del despliegue

| Comprobación | Resultado del 4 de noviembre de 2026 |
| --- | --- |
| Construcción | Frontend, imagen PHP y Nginx correctos |
| Suite automatizada | 148 pruebas, 946 aserciones; ejecución aislada, sin red, SQLite en memoria y límite PHP de 512 MB |
| Estilo | Pint correcto en los tres archivos PHP nuevos |
| Publicación | DNS, certificado, redirección HTTP 308 y cinco recursos del acceso comprobados; verificación pública independiente satisfactoria |
| Identidad | Cuatro cuentas activas y verificadas; roles, asignación de colegio y hash de contraseña comprobados |
| Acceso web | Login y logout de los cuatro roles; dashboard, perfil, gráficas y registros respondieron 200 según el acceso autorizado |
| Autorización | Denegaciones 403 previstas en usuarios, colegios y diario; guías y diario del estudiante accesibles con 200 |
| Superficie pública | Registro público, `/.env` y rutas de archivos privados heredadas respondieron 404 |
| Operación | Seis servicios saludables; pantalla de acceso de SysForest conservada con 200 |

Estas verificaciones cubren los recorridos y controles indicados; no equivalen a pruebas de carga, envío de correo, ensayo de recuperación ni una validación completa con datos de todos los módulos. La salud de queue y scheduler comprueba la vida del proceso, no la entrega de notificaciones.

## Correo transaccional con Brevo

El 4 de noviembre de 2026, en horario `America/Bogota`, se activó Brevo SMTP en `smtp-relay.brevo.com:587`, con `MAIL_SCHEME=smtp`, STARTTLS y validación del certificado. `varcor.tech` quedó autenticado y el remitente `notificaciones@varcor.tech` verificado con DKIM y DMARC; EcoData conserva su nombre como remitente. Los cuatro registros DNS de correo se añadieron sin cambiar los registros de las páginas publicadas.

La configuración privada se actualizó y se recrearon `app`, `queue` y `scheduler`; el entrypoint regeneró sus cachés de configuración. Se recargó Nginx para resolver la nueva dirección del contenedor PHP. En los tres servicios PHP se comprobó la configuración, la presencia de credenciales y la construcción de `EsmtpTransport`. Los servicios PHP y Nginx quedaron saludables; las comprobaciones HTTP y HTTPS fueron satisfactorias.

`MAIL_URL` debe estar **ausente**, incluso en las variables que Compose transmite al contenedor. Definirlo vacío anula el transporte en Laravel 13 y produce `Unsupported mail transport []`. Las credenciales permanecen fuera del repositorio, los documentos y las imágenes.

La prueba enviada al correo administrativo fue **aceptada por el transporte**. También se generó un enlace nuevo para la cuenta afectada por el incidente original, sin recrearla: la invitación se encoló y procesó correctamente, con vigencia de **60 minutos**. La consulta posterior confirmó cero trabajos pendientes y cero fallidos. El usuario confirmó el **4 de noviembre de 2026** la recepción de los tres mensajes: la prueba de EcoData, la prueba de SysForest y la nueva invitación. La aceptación SMTP y la recepción son comprobaciones distintas; esta última no se dedujo solo de la respuesta del transporte.

El transporte anterior `array` daba por procesadas las notificaciones sin enviarlas ni conservar un buzón de entrega diferida. Activar SMTP no recupera esos mensajes: las cuentas afectadas requieren una nueva invitación autorizada o solicitar **Olvidé mi contraseña** para generar un enlace vigente. No duplicar usuarios ni marcar sus correos como verificados para compensar una entrega fallida.

EcoData y SysForest comparten una sola cuenta gratuita de Brevo y su cupo de **300 envíos diarios en total**. Consultar el uso y los estados de entrega en el proveedor; si una demora supera la vigencia del enlace, solicitar uno nuevo. [Límites oficiales del plan gratuito](https://help.brevo.com/hc/en-us/articles/208580669-FAQs-What-are-the-limits-of-the-Free-plan).

Consultar el vencimiento y estado de la clave SMTP en el panel de Brevo; al renovarla, actualizar ambos env privados y recrear sus servicios PHP. Brevo también puede desactivarla tras 90 días sin envíos satisfactorios. [Administración de claves SMTP](https://help.brevo.com/hc/en-us/articles/7959631848850-Create-and-manage-your-SMTP-keys).

## Respaldo y recuperación

Se completó un respaldo privado coordinado en `/opt/ecodata/backups/backup-20261104T205356Z-KPyquV`, con comprobación de hashes y estructura de los archivos. Tras reanudar los servicios, los seis estaban saludables; `/up` de EcoData y la comprobación de salud de SysForest respondieron 200. **No se ensayó la restauración ni se creó una copia externa o una programación periódica.**

Para otra copia manual, desde la raíz del release:

```bash
bash scripts/production-backup.sh \
  --project-dir /opt/ecodata/releases/20261104-initial \
  --env-file /opt/ecodata/private/production.env \
  --destination /opt/ecodata/backups --dry-run

bash scripts/production-backup.sh \
  --project-dir /opt/ecodata/releases/20261104-initial \
  --env-file /opt/ecodata/private/production.env \
  --destination /opt/ecodata/backups --quiesce
```

`--dry-run` valida las rutas, el proyecto, los contenedores, los permisos y el volumen sin detener servicios ni crear una copia. `--quiesce` detiene solo nginx, scheduler, queue y app de EcoData que estaban funcionando y reanuda los mismos IDs al finalizar o ante errores capturables; requiere una ventana breve sin despliegues ni otros escritores. Sin esa opción, SQL y archivos pueden reflejar instantes distintos.

La copia incluye únicamente la base MySQL de EcoData y `storage/app`; produce `mysql.sql.gz`, `storage-app.tar.gz`, `manifest.json`, `RESTORE_NOTES.txt`, `SHA256SUMS` y `COMPLETE`. El destino es `0700` y sus archivos privados `0600`. Comprueba lectura gzip/tar, marcadores del volcado y SHA-256; un fallo conserva los parciales con `FAILED`. No incluye `.env`, `APP_KEY`, los secretos de Compose ni Redis; conservar la configuración y la clave por separado en un destino privado de recuperación. No configura retención ni borra respaldos anteriores.

Restaurar primero en un proyecto y volúmenes independientes, con MySQL compatible, credenciales técnicas propias y una base vacía. El volcado no reemplaza usuarios MySQL ni incluye bases del sistema, `CREATE DATABASE`, `USE` del origen o `DROP TABLE`. Conservar la `APP_KEY` del respaldo, restaurar `storage/app` con permisos compatibles con UID/GID `1000:1000`, mantener SMTP en `array` y las colas detenidas hasta revisar sesiones, tokens y trabajos. Revisar `RESTORE_NOTES.txt`, validar conteos, archivos y permisos por colegio, y aplicar solo migraciones pendientes antes de abrir el destino. No restaurar Redis ni cargar datos de demostración.

Quedan pendientes un ensayo de restauración, la periodicidad de las copias, la retención y un destino externo. La comprobación de integridad de esta primera copia no acredita por sí sola la recuperación funcional del sistema.

## Relación con los manuales existentes

Los PDF [Manual de usuario](../output/pdf/EcoData_Manual_de_Usuario.pdf) y [Manual técnico](../output/pdf/EcoData_Manual_Tecnico.pdf) son documentación histórica de la edición 1.0, del 15 de octubre de 2026, basada en la revisión `e9bc600`. Conservan sus ilustraciones y no se han reeditado para este despliegue.

Algunas descripciones quedaron superadas, entre ellas el procesamiento CSV meteorológico, los reportes por correo y la compilación local de recursos de interfaz. Para la implementación actual, consultar el [README de la aplicación](../src/README.md), el [procedimiento técnico de producción](../src/docker/production/README.md) y el código vigente. Los ejemplos genéricos del procedimiento técnico deben adaptarse a la raíz de release indicada aquí; este despliegue no incorpora el subdirectorio `/src` en el VPS.

Este documento registra el despliegue publicado; no declara actualizados todos los capítulos de los PDF ni sustituye una futura revisión funcional completa.
