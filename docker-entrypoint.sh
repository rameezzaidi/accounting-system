#!/bin/sh
set -e
# Railway MySQL variables -> app variables (ACC_* wins if set)
export ACC_DB_HOST="${ACC_DB_HOST:-$MYSQLHOST}"
export ACC_DB_USER="${ACC_DB_USER:-$MYSQLUSER}"
export ACC_DB_PASS="${ACC_DB_PASS:-$MYSQLPASSWORD}"
export ACC_DB_NAME="${ACC_DB_NAME:-$MYSQLDATABASE}"
PORT="${PORT:-8080}"
# first run: import schema if the database is empty
if [ -n "$ACC_DB_HOST" ]; then
  for i in $(seq 1 30); do mysqladmin --skip-ssl ping -h"$ACC_DB_HOST" -P"${MYSQLPORT:-3306}" -u"$ACC_DB_USER" -p"$ACC_DB_PASS" --silent && break; sleep 2; done
  T=$(mysql --skip-ssl -N -h"$ACC_DB_HOST" -P"${MYSQLPORT:-3306}" -u"$ACC_DB_USER" -p"$ACC_DB_PASS" "$ACC_DB_NAME" -e "SHOW TABLES LIKE 'accounts'" 2>/dev/null || true)
  if [ -z "$T" ]; then
    echo "Importing schema..."
    mysql --skip-ssl -h"$ACC_DB_HOST" -P"${MYSQLPORT:-3306}" -u"$ACC_DB_USER" -p"$ACC_DB_PASS" "$ACC_DB_NAME" < /var/www/html/database/schema.sql
  fi
fi
export PHP_CLI_SERVER_WORKERS=6
exec php -S 0.0.0.0:"${PORT}" -t /var/www/html /var/www/html/router.php
