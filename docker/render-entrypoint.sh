#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

touch /tmp/skazkomir-preview.sqlite
DB_CONNECTION=sqlite DB_DATABASE=/tmp/skazkomir-preview.sqlite php artisan migrate --force
if [ "${SEED_DEMO_CONTENT:-false}" = "true" ]; then
    APP_ENV=local DB_CONNECTION=sqlite DB_DATABASE=/tmp/skazkomir-preview.sqlite php artisan db:seed --force
fi

exec apache2-foreground
