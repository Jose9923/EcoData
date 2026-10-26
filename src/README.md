# EcoData

Aplicación Laravel para colegios, mediciones ambientales, estaciones y sensores, guías de laboratorio, eventos y diarios de campo.

## Requisitos

- PHP 8.4 con PDO MySQL/SQLite, mbstring, intl, bcmath, zip, XML y GD; Composer 2.
- Node.js 22.12 o posterior de la serie 22 y npm.
- MySQL 8.4 para la aplicación. Las pruebas usan exclusivamente SQLite en memoria.
- Alternativa recomendada: Docker y Docker Compose. Las imágenes PHP, MySQL, Redis y Node están definidas en compose.yaml.

El código usa Bootstrap y JavaScript compilados con Vite. Las librerías de interfaz se instalan desde package-lock.json; no dependen de scripts CDN al abrir una página.

## Instalación nueva

Ejecutar desde src en una copia independiente. Elegir un proyecto Compose y puertos sin recursos existentes; por ejemplo, usar -p ecodata-nuevo en **todos** los comandos siguientes. No reutilizar el volumen de otra instalación.

1. Copiar .env.example a .env solo si este último no existe. Configurar DB_PASSWORD y MYSQL_ROOT_PASSWORD con secretos nuevos, DB_DATABASE/DB_USERNAME, APP_URL y el proveedor de correo. No publicar .env.
2. Elegir HTTP_PORT, MYSQL_FORWARD_PORT, REDIS_FORWARD_PORT y ADMINER_PORT libres si los predeterminados ya están ocupados. Los puertos de DB, Redis y Adminer están vinculados solo a localhost.
3. Construir e instalar:

```bash
docker compose -p ecodata-nuevo build app queue scheduler
docker compose -p ecodata-nuevo run --rm --no-deps app composer install --no-interaction --prefer-dist
docker compose -p ecodata-nuevo run --rm --no-deps app php scripts/prepare-environment.php
docker compose -p ecodata-nuevo run --rm --no-deps node npm ci --ignore-scripts
docker compose -p ecodata-nuevo run --rm --no-deps node npm run build
docker compose -p ecodata-nuevo up -d mysql redis
docker compose -p ecodata-nuevo run --rm app php artisan migrate --force
docker compose -p ecodata-nuevo run --rm app php artisan db:seed --class=RolesAndPermissionsSeeder --force
docker compose -p ecodata-nuevo run --rm app php artisan storage:link
docker compose -p ecodata-nuevo run --rm app chown -R www-data:www-data storage bootstrap/cache
docker compose -p ecodata-nuevo run --rm app php artisan ecodata:admin
docker compose -p ecodata-nuevo up -d app nginx queue scheduler
```

El comando ecodata:admin solicita nombre, correo y contraseña sin mostrarla ni crear credenciales predefinidas. El correo debe verificarse al ingresar. Configurar SMTP antes de invitar usuarios; MAIL_MAILER=log permite probar en desarrollo, pero no entrega mensajes y puede registrar enlaces de activación sensibles.

composer setup prepara dependencias, entorno y frontend; conserva la clave existente y **no ejecuta migraciones**. Las migraciones se aplican por separado y con respaldo cuando corresponda.

## Actualizar una instalación existente

1. Respaldar la base de datos, storage/app y .env/APP_KEY en un destino protegido. Verificar la restauración en una instancia separada.
2. Revisar las variables nuevas de .env.example y copiar únicamente las que falten. Conservar APP_KEY y credenciales actuales. Cambiar variables de MySQL en Compose no cambia las credenciales dentro de un volumen ya inicializado.
3. Instalar exactamente composer.lock y package-lock.json, compilar recursos y probar en una copia aislada.
4. En la ventana de despliegue, aplicar php artisan migrate --force. Esta entrega añade la migración 2026_10_26_120000_add_measurement_audits_and_import_receipts: historial de mediciones y recibos de importación; no elimina mediciones existentes. El historial comienza a registrar cambios a partir de su instalación.
5. Limpiar/reconstruir cachés con php artisan optimize:clear, config:cache, route:cache y view:cache, después de verificar la configuración.
6. Reiniciar workers con php artisan queue:restart y comprobar queue/scheduler, /up, login y los flujos principales. Si hay trabajos pendientes anteriores a una actualización de clases de notificación, drenarlos durante la ventana de mantenimiento antes de sustituir el código.
7. En producción: APP_ENV=production, APP_DEBUG=false, APP_URL con HTTPS y SESSION_SECURE_COOKIE=true; proteger secretos, backups y accesos al servidor.

APP_TIMEZONE es configurable; el ejemplo usa America/Bogota. Cambiar la zona de una instalación que ya almacena fechas requiere revisar el significado de los valores históricos antes de activarla.

## Datos iniciales y demostración

- DatabaseSeeder carga únicamente roles/permisos. Se puede repetir sin vaciar usuarios ni datos funcionales.
- EcoDataDemoSeeder **vacía tablas** y está deshabilitado por defecto.
- Solo permite ejecución en local/testing con ECODATA_ALLOW_DEMO_SEED=true. Usarlo exclusivamente con una base descartable y desactivar la opción después.
- No ejecutar migrate:fresh, db:wipe ni seeders demostrativos sobre información que deba conservarse.

## Cuentas y permisos

- Roles: super_admin, admin_colegio, docente y estudiante.
- Las cuentas inactivas no pueden iniciar sesión y sus sesiones quedan bloqueadas en la siguiente solicitud.
- El acceso privado exige correo verificado; cambiar el correo invalida su verificación.
- Las altas administrativas y las importaciones envían un enlace individual para establecer contraseña. No existe contraseña compartida; una columna password en un archivo antiguo se ignora.
- AUTH_REGISTRATION_ENABLED=false cierra el registro público. Si se habilita, crea solicitudes inactivas pendientes de asignación y activación por administración.
- Los estudiantes consultan mediciones de su colegio asignadas a su grado/curso, generales y propias; solo editan las propias. El mismo alcance se aplica a panel, consultas, gráficas y exportaciones.
- Las vistas y exportaciones estudiantiles no muestran documentos, correos ni identidad de otros autores. Los archivos descargados deben mantenerse bajo control de quien los solicita.

## Mediciones y CSV

La edición actualiza cada valor conservando los omitidos o pertenecientes a variables inactivas. La eliminación de un valor requiere seleccionarla expresamente. Estación, origen y cambios de valores quedan registrados con autor, fecha y antes/después.

Los números se validan y almacenan con un máximo de cuatro decimales. No se redondean silenciosamente entradas con más precisión. Revisar configuraciones antiguas que anuncien más de cuatro decimales.

El cargue CSV consta de:
1. Elegir estación y descargar su plantilla.
2. Cargar CSV UTF-8 de hasta 2 MiB y 500 filas (coma o punto y coma).
3. Revisar resultados por fila, tipos, rangos y unidades sin guardar datos.
4. Confirmar una sola vez. El servidor revalida y guarda toda la importación dentro de una transacción.

La vista previa vence a los 20 minutos. El recibo impide duplicar una misma confirmación. Un archivo inválido no deja una importación parcial; se debe corregir y volver a previsualizar. El contrato de columnas se muestra en la pantalla: recorded_at, observations opcional y variables con slug/unidad obtenidos de sensores activos.

## Diario de campo

- Editar título, fechas o texto conserva preguntas y respuestas.
- Cuando existen entregas, no se permite cambiar colegio/destinatarios, tipo de respuesta, opciones o eliminar preguntas de forma que invalide lo entregado. Crear otra actividad para cambios estructurales.
- El borrador se guarda automáticamente tras una pausa breve al escribir; su estado se muestra en la pantalla.
- Las evidencias se adjuntan mediante guardado manual.
- Dos pestañas no se sobrescriben silenciosamente: una revisión obsoleta devuelve conflicto y requiere recargar.
- Los envíos y revisiones se comprueban bajo bloqueo transaccional; el autosave no modifica una entrega enviada o revisada.
- Las actividades cerradas conservan su consulta histórica en modo lectura.

## Reportes, gráficas y correo

El módulo **Gráficos** se abre desde el menú, los accesos del dashboard o el listado de mediciones, en /graficos. La dirección anterior /mediciones/evolucion sigue disponible.

1. Elegir una variable numérica, las fechas (entre 1 y 366 días) y, opcionalmente, una estación.
2. Elegir agrupación diaria, semanal o mensual y representación de líneas o barras.
3. Consultar promedio, mínimo, máximo y cantidad de mediciones; activar la comparación con el período inmediatamente anterior de igual duración o los límites configurados de la variable.
4. Revisar la comparación entre estaciones y la distribución de frecuencias. Las mediciones sin estación se muestran como un grupo propio.
5. Descargar cada gráfico como PNG o la tabla de evolución como CSV. Las tablas permiten consultar los valores con precisión y acceder a la información sin depender del gráfico.

Los promedios semanales y mensuales se ponderan por la cantidad de mediciones; no se promedian promedios diarios con el mismo peso. La comparación alinea los días por su posición en períodos de igual duración, y muestra las fechas originales del período anterior. Los intervalos semanales empiezan el lunes y los mensuales el día 1; el primer y último intervalo se recortan al rango consultado. Los días sin mediciones conservan valores vacíos, nunca ceros. Los límites mostrados corresponden a la configuración actual de la variable.

Cada consulta aplica el alcance de colegio, grado, curso y autor que corresponda al usuario. Los gráficos y sus descargas contienen datos agregados y no incluyen información personal de otros autores. El módulo incluye variables inactivas para poder consultar su historial; solo acepta variables numéricas. Chart.js se compila localmente con Vite y se carga únicamente en este módulo.

Reportes por correo envía hasta 2500 registros al correo verificado del propio solicitante. Se revalidan cuenta, colegio y permisos al procesar la cola. Si los datos cambian y superan el límite, se solicita acotar el rango.

Las notificaciones de contenido e invitaciones están en cola. Ejecutar el worker con timeout 60 segundos y retry_after mayor (180 por defecto). Revisar trabajos fallidos con php artisan queue:failed y reintentar IDs concretos después de corregir la causa. Los payloads sensibles se cifran; conservar APP_KEY.

## Pruebas y verificación

Nunca apuntar pruebas a una base real. phpunit.xml fuerza SQLite :memory:, correo en memoria y entorno testing.

En Docker se incluye un runner sin red, fuentes de solo lectura y almacenamiento temporal:

```bash
# Requiere una imagen ya construida; no usa Compose ni sus servicios.
ECODATA_TEST_IMAGE=ecodata-app bash scripts/run-isolated.sh
ECODATA_TEST_IMAGE=ecodata-app bash scripts/run-isolated.sh php artisan route:list --except-vendor
ECODATA_TEST_IMAGE=ecodata-app bash scripts/run-isolated.sh php artisan view:cache
```

Para una imagen con otro nombre, cambiar ECODATA_TEST_IMAGE. Con PHP local configurado puede ejecutarse php vendor/bin/phpunit.

```bash
composer validate --no-check-publish
composer audit --locked
npm ci --ignore-scripts
npm run build
npm run test:charts
npm audit
```

La verificación de GitHub Actions instala los locks, compila recursos y vistas, comprueba rutas y ejecuta la suite. Las pruebas cubren autenticación, invitaciones, aislamiento entre colegios, integridad del diario, mediciones, CSV, exportaciones, reportes y seguridad de la instalación.

## Respaldo y operación

Respaldar datos y archivos como una unidad consistente; conservar una copia protegida de la clave de cifrado. Una copia de SQL no incluye PDFs ni evidencias. Ensayar la restauración en una instancia con proyecto Compose, volumen y puertos propios. Registrar fecha, tamaño, resultado y responsable de cada prueba de restauración.

No iniciar trabajadores de cola sobre un respaldo restaurado mientras tenga destinatarios reales y SMTP activo. Usar MAIL_MAILER=log/array en entornos de verificación y revisar trabajos pendientes.
