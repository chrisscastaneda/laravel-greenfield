# Greenfield container images.
#
#   dev   - PHP-FPM + Node toolchain; source code is bind-mounted at runtime (compose.override.yaml)
#   prod  - PHP-FPM with the application, production vendor/ and compiled assets baked in
#   web       - Nginx; public/ is bind-mounted at runtime in development
#   web-prod  - Nginx with public/ (including compiled assets) baked in

ARG PHP_VERSION=8.5
ARG NGINX_VERSION=1.30.5
ARG NODE_VERSION=24

FROM node:${NODE_VERSION}-alpine AS node

# ---------------------------------------------------------------------------
# base: PHP runtime shared by every PHP image
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions bcmath intl opcache pcntl pdo_pgsql pgsql zip \
    && apk add --no-cache fcgi

COPY docker/php/conf.d/greenfield.ini /usr/local/etc/php/conf.d/zz-greenfield.ini
COPY docker/php/fpm/zz-greenfield.conf /usr/local/etc/php-fpm.d/zz-greenfield.conf
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/greenfield-entrypoint

ENV COMPOSER_HOME=/tmp/composer

ENTRYPOINT ["greenfield-entrypoint"]
CMD ["php-fpm"]

HEALTHCHECK --interval=10s --timeout=3s --start-period=60s --retries=5 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1

# ---------------------------------------------------------------------------
# base-node: PHP + Node. Wayfinder's Vite plugin runs `php artisan` while
# building, so asset builds need both runtimes in one image.
# ---------------------------------------------------------------------------
FROM base AS base-node

RUN apk add --no-cache libstdc++
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# ---------------------------------------------------------------------------
# dev: local development image (code is bind-mounted, not copied)
# ---------------------------------------------------------------------------
FROM base-node AS dev

# Match www-data to the host user so bind-mounted files stay writable on Linux hosts.
ARG UID=1000
ARG GID=1000
RUN apk add --no-cache shadow \
    && groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data \
    && mkdir -p /var/www/html/node_modules \
    && chown -R www-data:www-data /var/www/html

ENV GREENFIELD_IMAGE=dev

USER www-data

# ---------------------------------------------------------------------------
# vendor: production PHP dependencies
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

# ---------------------------------------------------------------------------
# assets: compile frontend assets with Vite
# ---------------------------------------------------------------------------
FROM base-node AS assets

COPY package.json package-lock.json .npmrc ./
RUN npm ci

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
RUN composer dump-autoload --no-dev --no-interaction \
    && npm run build

# ---------------------------------------------------------------------------
# prod: production PHP-FPM image (also used for the queue and scheduler)
# ---------------------------------------------------------------------------
FROM base AS prod

COPY docker/php/conf.d/opcache-prod.ini /usr/local/etc/php/conf.d/zz-opcache-prod.ini
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /var/www/html/public/build ./public/build

RUN composer dump-autoload --no-dev --no-interaction --optimize --classmap-authoritative \
    && chown -R www-data:www-data bootstrap/cache storage

ENV GREENFIELD_IMAGE=prod

USER www-data

# ---------------------------------------------------------------------------
# web: Nginx (public/ is bind-mounted in development)
# ---------------------------------------------------------------------------
FROM nginx:${NGINX_VERSION}-alpine AS web

RUN apk add --no-cache openssl curl \
    && mkdir -p /var/www/certbot /etc/nginx/snippets /etc/nginx/ssl

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/snippets/ /etc/nginx/snippets/
COPY docker/nginx/templates/ /etc/nginx/templates/
COPY --chmod=755 docker/nginx/entrypoint.d/ /docker-entrypoint.d/
COPY --chmod=755 docker/nginx/greenfield-reload-ssl.sh /usr/local/bin/greenfield-reload-ssl

HEALTHCHECK --interval=10s --timeout=3s --start-period=20s --retries=5 \
    CMD curl -fsSk -o /dev/null https://127.0.0.1/up || exit 1

# ---------------------------------------------------------------------------
# web-prod: Nginx with the public/ directory (and compiled assets) baked in
# ---------------------------------------------------------------------------
FROM web AS web-prod

COPY --from=prod /var/www/html/public /var/www/html/public
