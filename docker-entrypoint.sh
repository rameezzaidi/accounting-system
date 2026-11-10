#!/bin/sh
set -e
# Railway MySQL variables -> app variables (ACC_* wins if set)
export ACC_DB_HOST="${ACC_DB_HOST:-$MYSQLHOST}"
export ACC_DB_USER="${ACC_DB_USER:-$MYSQLUSER}"
export ACC_DB_PASS="${ACC_DB_PASS:-$MYSQLPASSWORD}"
export ACC_DB_NAME="${ACC_DB_NAME:-$MYSQLDATABASE}"
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
# pass env to Apache/PHP
{ echo "PassEnv ACC_DB_HOST ACC_DB_USER ACC_DB_PASS ACC_DB_NAME ACC_ENC_KEY CI_ENV"; } > /etc/apache2/conf-enabled/accenv.conf
# first run: import schema if the database is empty
if [ -n "$ACC_DB_HOST" ]; then
  for i in $(seq 1 30); do mysqladmin ping -h"$ACC_DB_HOST" -P"${MYSQLPORT:-3306}" -u"$ACC_DB_USER" -p"$ACC_DB_PASS" --silent && break; sleep 2; done
  T=$(mysql -N -h"$ACC_DB_HOST" -P"${MYSQLPORT:-3306}" -u"$ACC_DB_USER" -p"$ACC_DB_PASS" "$ACC_DB_NAME" -e "SHOW TABLES LIKE 'accounts'" 2>/dev/null || true)
  if [ -z "$T" ]; then
    echo "Importing schema..."
    mysql -h"$ACC_DB_HOST" -P"${MYSQLPORT:-3306}" -u"$ACC_DB_USER" -p"$ACC_DB_PASS" "$ACC_DB_NAME" < /var/www/html/database/schema.sql
  fi
fi
exec apache2-foreground
