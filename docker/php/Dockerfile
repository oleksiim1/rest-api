FROM php:8.3.36-fpm-alpine3.24

RUN apk add --no-cache git unzip libpq-dev \
    && docker-php-ext-install opcache pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html

# Created here so the named volume mounted on var/ inherits this ownership.
RUN mkdir -p var && chown -R www-data:www-data var

USER www-data
