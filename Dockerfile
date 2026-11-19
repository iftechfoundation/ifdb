# Match the PHP version Debian ships on production.
ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-cli AS php-composer
RUN apt-get update -y && apt-get install -y unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM php-composer AS builder
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
