FROM composer:latest AS composer
RUN apt-get update && apt-get install -y \
    unzip \
    && docker-php-ext-install zip

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY . /app
WORKDIR /app

RUN composer install --no-interaction --prefer-dist

RUN apt-get update && apt-get install -y \
    supervisor \
    curl \
    && curl -L --output /usr/local/bin/cloudflared https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 \
    && chmod +x /usr/local/bin/cloudflared \
    && rm -rf /var/lib/apt/lists/*

COPY . /app
COPY supervisord.conf /app/supervisord.conf

RUN cp .env.example .env
# Change APP_ENV and APP_DEBUG to be production ready
RUN sed -i'' -e 's/^APP_ENV=.*/APP_ENV=production/' -e 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
# Set APP_URL to placeholder, can be overridden at runtime via env
RUN sed -i'' -e 's|^APP_URL=.*|APP_URL=${APP_URL:-http://localhost}|' .env
# Trust all proxies for Cloudflare Tunnel
RUN echo "\nTRUSTED_PROXIES=*" >> .env

RUN frankenphp php-cli artisan key:generate
RUN frankenphp php-cli artisan storage:link
# We might want to keep migrations out of build time for real prod, 
# but keeping it here as per original Dockerfile for now.
RUN frankenphp php-cli artisan migrate -n --force
RUN frankenphp php-cli artisan optimize

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/app/supervisord.conf"]
