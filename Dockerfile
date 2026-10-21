# Default is the unpinned minor (php:8-apache) for local compose.
# docker-compose.prod.yml passes PHP_VERSION=8.3.
ARG PHP_VERSION=8

FROM composer:2 AS builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

FROM php:${PHP_VERSION}-apache AS web

RUN docker-php-ext-install mysqli
RUN apt-get update -y && apt-get install -y zlib1g-dev libpng-dev libjpeg-dev
RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install gd
RUN a2enmod rewrite headers
COPY --from=builder /app/vendor /opt/vendor
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["apache2-foreground"]
