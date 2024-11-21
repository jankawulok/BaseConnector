FROM dunglas/frankenphp

RUN install-php-extensions \
    pcntl
    # Add other PHP extensions here...

COPY . /app
RUN frankenphp php-cli artisan optimize


ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
