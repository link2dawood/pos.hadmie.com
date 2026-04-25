#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.docker ]; then
    cp .env.docker .env
fi

mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage
chmod -R ug+rwX bootstrap/cache storage

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

exec "$@"
