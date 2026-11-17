#!/bin/bash -e
cd "$(dirname "$0")"
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
    -v "$(pwd):/app" -w /app composer:2 \
    sh -c 'composer install --no-interaction --quiet && vendor/bin/phpunit "$@"' phpunit "$@"
