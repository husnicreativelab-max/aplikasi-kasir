#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    php artisan migrate --force
fi

php artisan optimize
chown -R www-data:www-data bootstrap/cache storage

exec "$@"
