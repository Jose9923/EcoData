#!/bin/sh
set -eu
export MYSQL_PWD="$(cat /run/secrets/db_password)"
mysql --protocol=TCP --host=127.0.0.1 --user="$ECODATA_DB_USER" \
    --database="$MYSQL_DATABASE" --connect-timeout=3 --batch --skip-column-names \
    --execute='SELECT 1' >/dev/null 2>&1
