FROM php:8.4-cli-alpine AS runtime

WORKDIR /app

RUN apk add --no-cache \
    bash \
    curl \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
  && docker-php-ext-install \
    intl \
    mbstring \
    opcache \
    pdo \
    pdo_mysql \
    zip \
  && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /app

RUN if [ -f composer.json ]; then composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; fi

EXPOSE 8080

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=8080"]
