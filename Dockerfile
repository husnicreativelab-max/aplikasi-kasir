# syntax=docker/dockerfile:1.7

FROM php:8.3-fpm-bookworm AS php-runtime

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        libonig-dev \
        libxml2-dev \
        libonig5 \
        libxml2; \
    docker-php-ext-install -j"$(nproc)" bcmath dom mbstring opcache pdo_mysql xml; \
    apt-mark manual libonig5 libxml2; \
    apt-get purge -y --auto-remove $PHPIZE_DEPS libonig-dev libxml2-dev; \
    rm -rf /var/lib/apt/lists/* /tmp/*

COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-production.conf

WORKDIR /var/www/html

FROM php-runtime AS composer-dependencies

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.* ./
COPY app ./app
COPY database ./database
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

FROM node:22-alpine AS frontend-assets

WORKDIR /var/www/html
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY app/Http ./app/Http
COPY resources ./resources
COPY public ./public
COPY tailwind.config.js vite.config.js ./
COPY --from=composer-dependencies /var/www/html/vendor ./vendor
RUN npm run build

FROM php-runtime AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

WORKDIR /var/www/html

COPY --from=composer-dependencies /var/www/html/vendor ./vendor
COPY app ./app
COPY artisan composer.json ./
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY lang ./lang
COPY public ./public
COPY resources/views ./resources/views
COPY routes ./routes
COPY --from=frontend-assets /var/www/html/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/container-entrypoint

RUN set -eux; \
    mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs; \
    rm -rf public/storage; \
    rm -f bootstrap/cache/*.php; \
    ln -s ../storage/app/public public/storage; \
    chmod +x /usr/local/bin/container-entrypoint; \
    APP_ENV=production php artisan package:discover --ansi; \
    chown -R www-data:www-data bootstrap/cache storage

ENTRYPOINT ["/usr/local/bin/container-entrypoint"]
CMD ["php-fpm", "-F"]

FROM nginx:stable-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
COPY --from=app /var/www/html/storage /var/www/html/storage

EXPOSE 80
