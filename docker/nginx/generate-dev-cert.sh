#!/bin/sh
# Generate the local development TLS certificate used by the `web` container.
#
#   ./docker/nginx/generate-dev-cert.sh [domain]
#
# Uses mkcert (a locally-trusted certificate, no browser warnings) when it is
# installed, otherwise falls back to a self-signed certificate via openssl.
set -e

CERT_DIR="$(cd "$(dirname "$0")" && pwd)/certs"
CERT="$CERT_DIR/localhost.pem"
KEY="$CERT_DIR/localhost-key.pem"
DOMAIN="${1:-localhost}"

mkdir -p "$CERT_DIR"

if command -v mkcert >/dev/null 2>&1; then
    mkcert -install
    mkcert -cert-file "$CERT" -key-file "$KEY" "$DOMAIN" localhost 127.0.0.1 ::1
else
    echo "mkcert not found; generating a self-signed certificate (your browser will show a warning)."
    echo "Install mkcert for a trusted certificate: https://github.com/FiloSottile/mkcert"
    openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
        -keyout "$KEY" -out "$CERT" \
        -subj "/CN=$DOMAIN" \
        -addext "subjectAltName=DNS:$DOMAIN,DNS:localhost,IP:127.0.0.1"
fi

echo
echo "Certificate written to $CERT"
echo "Apply it with: docker compose restart web"
