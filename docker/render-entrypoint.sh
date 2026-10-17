#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/app/public storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "${APP_DEBUG:-false}" = "true" ] && [ "${APP_ENV:-production}" = "production" ]; then
    echo "Refusing production startup with APP_DEBUG=true." >&2
    exit 1
fi

if [ "${DB_CONNECTION:-pgsql}" = "sqlite" ]; then
    : "${DB_DATABASE:?Set an absolute DB_DATABASE path for SQLite}"
    case "$DB_DATABASE" in
        /*) mkdir -p "$(dirname "$DB_DATABASE")"; touch "$DB_DATABASE"; chown www-data:www-data "$DB_DATABASE" ;;
        *) echo "SQLite DB_DATABASE must be an absolute path." >&2; exit 1 ;;
    esac
    if [ "${SEED_DEMO_CONTENT:-false}" != "true" ]; then
        case "$DB_DATABASE" in /tmp/*) echo "Production SQLite must be on persistent storage, not /tmp." >&2; exit 1 ;; esac
    fi
fi

php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan storage:link --force
if [ "${SEED_DEMO_CONTENT:-false}" = "true" ]; then
    APP_ENV=local php artisan db:seed --force
fi

# Seeding can create audio/art directories as root. Apache must be able to
# upload into those directories after startup, including on mounted storage.
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
