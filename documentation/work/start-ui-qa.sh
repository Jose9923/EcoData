#!/usr/bin/env bash
set -euo pipefail
project_dir=/home/josev/EcoData
if docker container inspect ecodata-review-ui >/dev/null 2>&1; then
  echo 'QA container already exists; refusing to replace it.' >&2
  exit 1
fi
docker run --rm -d --name ecodata-review-ui --read-only \
  --user "$(id -u):$(id -g)" \
  --mount "type=bind,src=$project_dir/src,dst=/app,readonly" \
  --mount "type=bind,src=$project_dir/documentation/work,dst=/qa,readonly" \
  --tmpfs /tmp:rw,mode=1777 --tmpfs /app/storage:rw,mode=1777 \
  --workdir /app --publish 127.0.0.1:18086:8080 \
  --env APP_ENV=testing --env APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
  --env APP_DEBUG=true --env APP_URL=http://127.0.0.1:18086 \
  --env APP_CONFIG_CACHE=/tmp/config.php --env APP_SERVICES_CACHE=/tmp/services.php \
  --env APP_PACKAGES_CACHE=/tmp/packages.php --env APP_ROUTES_CACHE=/tmp/routes.php \
  --env VIEW_COMPILED_PATH=/tmp/views --env DB_CONNECTION=sqlite \
  --env DB_DATABASE=/tmp/ecodata-preview.sqlite --env DB_URL= \
  --env MAIL_MAILER=array --env QUEUE_CONNECTION=sync --env CACHE_STORE=array \
  --env SESSION_DRIVER=file --env SESSION_DOMAIN= --env SESSION_SECURE_COOKIE=false \
  --env ASSET_URL= ecodata-app sh -c \
  'mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs /tmp/views; touch /tmp/ecodata-preview.sqlite; php /qa/ui-fixture.php && exec php -S 0.0.0.0:8080 -t public'
