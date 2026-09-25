#!/bin/sh
# Runs before Nginx starts (see the official image's /docker-entrypoint.sh).
set -e

greenfield-reload-ssl --no-reload

# Periodically re-check the certificate so renewed Let's Encrypt certificates
# are picked up without a restart. Disabled when NGINX_SSL_RELOAD_INTERVAL=0.
if [ "${NGINX_SSL_RELOAD_INTERVAL:-0}" -gt 0 ]; then
    (
        while sleep "$NGINX_SSL_RELOAD_INTERVAL"; do
            greenfield-reload-ssl || true
        done
    ) &
fi
