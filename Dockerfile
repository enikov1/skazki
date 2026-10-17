FROM node:22-bookworm-slim AS assets
WORKDIR /workspace
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.3-apache-bookworm
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libicu-dev libonig-dev libpq-dev libsqlite3-dev libxml2-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" curl intl mbstring pdo_pgsql pdo_sqlite xml zip \
    && a2enmod rewrite headers \
    
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=assets /workspace/public/ /var/www/html/public/
COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint
RUN chmod +x /usr/local/bin/render-entrypoint
EXPOSE 10000
CMD ["/usr/local/bin/render-entrypoint"]
