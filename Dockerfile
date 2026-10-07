# syntax=docker/dockerfile:1

############################
# Builder: deps + assets + pre-rendered pages + warm prod cache
############################
FROM dunglas/frankenphp:1-php8.4 AS builder

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    DEFAULT_URI=https://jeremielibeau.fr

# System tools + PHP extensions needed to build (gd for the vCard QR code).
RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions gd
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# 1. Dependencies first (cached until composer.* changes).
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist \
    --no-interaction --optimize-autoloader

# 2. Application source, then finalize the autoloader.
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# 3. Build the front-end, pre-render the static pages into public/, warm cache.
# A throwaway APP_SECRET is set only for this layer (never persisted as ENV).
RUN export APP_SECRET=build-time-only \
    && php bin/console tailwind:build --minify \
    && php bin/console asset-map:compile \
    && php bin/console app:build-static --output=public \
    && php bin/console cache:clear

############################
# Runtime: FrankenPHP serving public/ (static) + /mcp (live)
############################
FROM dunglas/frankenphp:1-php8.4 AS runtime

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    DEFAULT_URI=https://jeremielibeau.fr

COPY --from=builder /app /app
COPY Caddyfile /etc/frankenphp/Caddyfile

# var/ must stay writable at runtime (prod cache pools, MCP sessions, logs).
RUN chown -R www-data:www-data var

EXPOSE 80
