#!/bin/sh
# Point Nginx at the configured TLS certificate, falling back to a self-signed
# certificate when it does not exist yet (e.g. before Let's Encrypt has issued one).
#
#   greenfield-reload-ssl            select the certificate and reload Nginx
#   greenfield-reload-ssl --no-reload  only select the certificate (used on startup)
set -e

SNIPPET=/etc/nginx/snippets/greenfield-ssl-cert.conf
FALLBACK_DIR=/etc/nginx/ssl
FALLBACK_CERT="$FALLBACK_DIR/selfsigned.pem"
FALLBACK_KEY="$FALLBACK_DIR/selfsigned-key.pem"

if [ -n "$SSL_CERTIFICATE" ] && [ -f "$SSL_CERTIFICATE" ] && [ -f "$SSL_CERTIFICATE_KEY" ]; then
    cert="$SSL_CERTIFICATE"
    key="$SSL_CERTIFICATE_KEY"
else
    if [ ! -f "$FALLBACK_CERT" ] || [ ! -f "$FALLBACK_KEY" ]; then
        echo "[greenfield] Certificate '${SSL_CERTIFICATE:-<unset>}' not found; generating a self-signed fallback" >&2
        mkdir -p "$FALLBACK_DIR"
        openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
            -keyout "$FALLBACK_KEY" -out "$FALLBACK_CERT" \
            -subj "/CN=${NGINX_SERVER_NAME:-localhost}" \
            -addext "subjectAltName=DNS:${NGINX_SERVER_NAME:-localhost},DNS:localhost,IP:127.0.0.1" >/dev/null 2>&1
    fi
    cert="$FALLBACK_CERT"
    key="$FALLBACK_KEY"
fi

printf 'ssl_certificate %s;\nssl_certificate_key %s;\n' "$cert" "$key" > "$SNIPPET.tmp"

if [ -f "$SNIPPET" ] && cmp -s "$SNIPPET" "$SNIPPET.tmp"; then
    rm "$SNIPPET.tmp"
else
    mv "$SNIPPET.tmp" "$SNIPPET"
    echo "[greenfield] Using TLS certificate $cert"
fi

if [ "$1" != "--no-reload" ]; then
    nginx -s reload
fi
