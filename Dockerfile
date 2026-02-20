# Stage 1: Build dependencies
FROM composer:latest AS composer
WORKDIR /app
COPY composer.json composer.lock ./
# Install only production dependencies
RUN composer install --no-interaction --prefer-dist --no-dev --no-scripts
# Stage 2: Runtime (FrankenPHP)
FROM dunglas/frankenphp:1.11.2-php8.5-trixie AS runtime
# Install system dependencies
RUN apt-get update && apt-get install -y \
    supervisor \
    curl \
    unzip \
    libzip-dev \
    libicu-dev \
    && curl -L --output /usr/local/bin/cloudflared https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 \
    && chmod +x /usr/local/bin/cloudflared \
    && rm -rf /var/lib/apt/lists/*
RUN install-php-extensions \
    pcntl
WORKDIR /app
# Copy application code
COPY . /app
# Copy vendor from composer stage
COPY --from=composer /app/vendor /app/vendor
# Configuration
RUN cp .env.example .env && \
    sed -i'' -e 's/^APP_ENV=.*/APP_ENV=production/' -e 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env && \
    sed -i'' -e 's|^APP_URL=.*|APP_URL=http://localhost|' .env && \
    echo "TRUSTED_PROXIES=*" >> .env && \
    echo "DB_DATABASE=/app/storage/database.sqlite" >> .env
# Set up storage and database SQLite file
RUN mkdir -p /app/storage/logs /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/database && \
    touch /app/storage/database.sqlite && \
    chown -R www-data:www-data /app/storage /app/database && \
    chmod -R 775 /app/storage /app/database
# Laravel optimization
RUN frankenphp php-cli artisan key:generate && \
    frankenphp php-cli artisan storage:link && \
    frankenphp php-cli artisan optimize
# Copy and register the runtime entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/app/supervisord.conf"]