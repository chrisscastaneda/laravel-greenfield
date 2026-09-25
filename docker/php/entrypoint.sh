#!/bin/sh
# Greenfield PHP container entrypoint.
#
# Only the container with GREENFIELD_BOOTSTRAP=true (the `app` service) prepares
# the application (dependencies, .env, migrations). The queue and scheduler
# containers wait for `app` to be healthy and then just run their command.
set -e

cd /var/www/html

artisan() {
    php artisan "$@" --no-interaction
}

if [ "${GREENFIELD_BOOTSTRAP:-false}" = "true" ]; then
    if [ "${GREENFIELD_IMAGE}" = "dev" ]; then
        if [ ! -f .env ]; then
            echo "[greenfield] .env not found, copying .env.example"
            cp .env.example .env
        fi

        if [ ! -f vendor/autoload.php ]; then
            echo "[greenfield] Installing Composer dependencies"
            composer install --no-interaction --prefer-dist
        fi

        if ! grep -Eq '^APP_KEY=.+' .env; then
            echo "[greenfield] Generating APP_KEY"
            artisan key:generate
        fi

        artisan storage:link >/dev/null 2>&1 || true
    fi

    if [ ! -f .env ]; then
        echo "[greenfield] ERROR: /var/www/html/.env is missing. Mount your production .env into the container." >&2
        exit 1
    fi

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        echo "[greenfield] Running database migrations"
        artisan migrate --force
    fi
fi

if [ "${GREENFIELD_IMAGE}" = "prod" ]; then
    # Cache config, routes, views and events for this container.
    artisan optimize
fi

exec "$@"
