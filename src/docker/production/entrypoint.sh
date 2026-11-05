#!/bin/sh
set -eu
umask 027

if [ -n "${DB_PASSWORD_FILE:-}" ]; then
    DB_PASSWORD=$(cat "$DB_PASSWORD_FILE")
    export DB_PASSWORD
fi

php -r '
function fail(string $message): never { fwrite(STDERR, $message."\n"); exit(1); }
if (getenv("APP_ENV") !== "production" || getenv("APP_DEBUG") !== "false") fail("EcoData requires production mode without debug.");
$url = parse_url((string) getenv("APP_URL"));
if (!$url || ($url["scheme"] ?? null) !== "https" || empty($url["host"]) || isset($url["user"]) || isset($url["pass"]) || isset($url["query"]) || isset($url["fragment"]) || !in_array($url["path"] ?? "", ["", "/"], true)) fail("APP_URL must be an HTTPS origin.");
$key = (string) getenv("APP_KEY");
if (!str_starts_with($key, "base64:") || strlen(base64_decode(substr($key, 7), true) ?: "") !== 32) fail("APP_KEY must contain 32 base64-encoded bytes.");
if (getenv("SESSION_SECURE_COOKIE") !== "true" || getenv("AUTH_REGISTRATION_ENABLED") !== "false" || getenv("ECODATA_ALLOW_DEMO_SEED") !== "false") fail("Production security flags are not configured.");
if (getenv("MYSQL_ROOT_PASSWORD") || getenv("DB_MIGRATOR_PASSWORD")) fail("Privileged database secrets must not be exposed to the application.");
if (!getenv("DB_PASSWORD")) fail("Database credentials are missing.");
if (!in_array(getenv("MAIL_MAILER"), ["array", "smtp"], true)) fail("Use array until SMTP is explicitly configured; log/failover transports are not allowed.");
'

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
cp /opt/ecodata/bootstrap-cache/*.php bootstrap/cache/
php artisan config:cache --no-ansi >/dev/null
exec "$@"
