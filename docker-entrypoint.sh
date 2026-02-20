#!/bin/sh
set -e

# Update APP_URL in .env at runtime from environment variable
if [ -n "$APP_URL" ]; then
    sed -i'' "s|^APP_URL=.*|APP_URL=${APP_URL}|" /app/.env
fi

# Update DB_DATABASE path if provided
if [ -n "$DB_DATABASE" ]; then
    sed -i'' "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE}|" /app/.env
fi

# Re-cache config so the updated APP_URL (and other env vars) take effect.
# artisan optimize at build time bakes http://localhost — this overwrites it.
frankenphp php-cli /app/artisan config:cache
frankenphp php-cli /app/artisan route:cache
frankenphp php-cli /app/artisan view:cache

# Run migrations automatically on every start (safe - idempotent)
echo "Running database migrations..."
frankenphp php-cli /app/artisan migrate --force --no-interaction

exec "$@"
