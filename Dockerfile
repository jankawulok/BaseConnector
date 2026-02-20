# =============================================================
# Stage 1: Build static FrankenPHP binary with embedded app
# =============================================================
FROM --platform=linux/amd64 dunglas/frankenphp:static-builder-gnu AS builder

WORKDIR /go/src/app/dist/app

# Copy application code
COPY . .

# Install production Composer dependencies (embedded into binary)
RUN composer install --ignore-platform-reqs --no-dev --optimize-autoloader --no-scripts

# Build the static binary with the entire app embedded
WORKDIR /go/src/app/
RUN EMBED=dist/app/ ./build-static.sh

# =============================================================
# Stage 2: Minimal runtime — only the binary + system tools
# =============================================================
FROM debian:bookworm-slim

# Install supervisord + curl (cloudflared is fetched as a standalone binary)
RUN apt-get update && apt-get install -y \
    supervisor \
    curl \
    sqlite3 \
    && curl -L --output /usr/local/bin/cloudflared \
    https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 \
    && chmod +x /usr/local/bin/cloudflared \
    && rm -rf /var/lib/apt/lists/*

# Copy the static FrankenPHP binary (contains PHP runtime + all extensions + app)
COPY --from=builder /go/src/app/dist/frankenphp-linux-x86_64 /usr/local/bin/frankenphp
RUN chmod +x /usr/local/bin/frankenphp

WORKDIR /app

# Copy .env template (app code is embedded in binary — only runtime config needed)
COPY .env.example .env
RUN sed -i'' -e 's/^APP_ENV=.*/APP_ENV=production/' \
    -e 's/^APP_DEBUG=.*/APP_DEBUG=false/' \
    -e 's|^APP_URL=.*|APP_URL=http://localhost|' .env && \
    echo "TRUSTED_PROXIES=*" >> .env && \
    echo "DB_DATABASE=/data/database.sqlite" >> .env

# Create writable runtime directories (use /data for volume-mounted persistence)
RUN mkdir -p /data \
    /app/storage/logs \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views && \
    touch /data/database.sqlite && \
    chmod -R 775 /app/storage /data

# Generate app key and warm caches
RUN frankenphp php-cli artisan key:generate && \
    frankenphp php-cli artisan optimize

# Config files
COPY supervisord.conf /etc/supervisor/supervisord.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Mount /data as a volume for SQLite persistence across container restarts
VOLUME ["/data"]

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]