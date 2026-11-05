#!/bin/bash
# Sourced by the official MySQL entrypoint only for a new data directory.
(
set -eo pipefail

: "${MYSQL_DATABASE:?}" "${ECODATA_DB_USER:?}" "${ECODATA_MIGRATOR_USER:?}"

for identifier in "$MYSQL_DATABASE" "$ECODATA_DB_USER" "$ECODATA_MIGRATOR_USER"; do
    [[ "$identifier" =~ ^[a-zA-Z][a-zA-Z0-9_]{0,47}$ ]] || { echo 'Invalid database/user identifier.' >&2; exit 1; }
done
[[ "$ECODATA_DB_USER" != "$ECODATA_MIGRATOR_USER" && "$ECODATA_DB_USER" != root && "$ECODATA_MIGRATOR_USER" != root ]] || exit 1

ecodata_app_password=$(cat /run/secrets/db_password)
ecodata_migrator_password=$(cat /run/secrets/db_migrator_password)
[[ "$ecodata_app_password" =~ ^[a-f0-9]{64}$ && "$ecodata_migrator_password" =~ ^[a-f0-9]{64}$ ]] || { echo 'Production DB passwords must be 64 lowercase hexadecimal characters.' >&2; exit 1; }

docker_process_sql --database=mysql <<SQL
CREATE USER IF NOT EXISTS '$ECODATA_DB_USER'@'%' IDENTIFIED BY '$ecodata_app_password';
CREATE USER IF NOT EXISTS '$ECODATA_MIGRATOR_USER'@'%' IDENTIFIED BY '$ecodata_migrator_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`$MYSQL_DATABASE\`.* TO '$ECODATA_DB_USER'@'%';
GRANT ALL PRIVILEGES ON \`$MYSQL_DATABASE\`.* TO '$ECODATA_MIGRATOR_USER'@'%';
SQL
unset ecodata_app_password ecodata_migrator_password
)
