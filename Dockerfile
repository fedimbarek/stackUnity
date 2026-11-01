# syntax=docker/dockerfile:1
# Image de production pour Render (PHP 8.3 + nginx + php-fpm).

# ---------- 1) Assets front (Vite / Tailwind) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
RUN npm run build

# ---------- 2) Application ----------
FROM serversideup/php:8.3-fpm-nginx AS app

USER root
RUN install-php-extensions gd intl

# Le script de seed optionnel (voir docker/99-seed-on-boot.sh)
COPY --chmod=755 docker/99-seed-on-boot.sh /etc/entrypoint.d/99-seed-on-boot.sh

USER www-data
WORKDIR /var/www/html

# Dépendances PHP d'abord (cache Docker)
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Au démarrage : migrate --force, storage:link, config/route/view cache
ENV AUTORUN_ENABLED=true \
    SSL_MODE=off \
    PHP_OPCACHE_ENABLE=1
