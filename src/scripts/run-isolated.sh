#!/usr/bin/env bash
set -euo pipefail
# No Compose services, real database, SMTP, or writable source checkout.
project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
image_name="${ECODATA_TEST_IMAGE:-ecodata-app}"
if [ "$#" -eq 0 ]; then set -- php vendor/bin/phpunit --do-not-cache-result; fi
docker run --rm --network none --read-only \
  --user "$(id -u):$(id -g)" \
  --mount "type=bind,src=$project_dir,dst=/app,readonly" \
  --tmpfs /tmp:rw,mode=1777 --tmpfs /app/storage:rw,mode=1777 \
  --workdir /app \
  --env APP_ENV=testing --env APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
  --env APP_DEBUG=false --env APP_URL=http://localhost \
  --env APP_CONFIG_CACHE=/tmp/config.php --env APP_SERVICES_CACHE=/tmp/services.php \
  --env APP_PACKAGES_CACHE=/tmp/packages.php --env APP_ROUTES_CACHE=/tmp/routes.php \
  --env VIEW_COMPILED_PATH=/tmp/views \
  --env DB_CONNECTION=sqlite --env DB_DATABASE=:memory: --env DB_URL= \
  --env MAIL_MAILER=array --env QUEUE_CONNECTION=sync \
  --env CACHE_STORE=array --env SESSION_DRIVER=array \
  "$image_name" sh -c \
  'mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs /tmp/views; exec "$@"' \
  sh "$@"
