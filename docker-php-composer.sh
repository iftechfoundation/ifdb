#!/bin/bash -ex
cd "$(dirname "$0")"
docker build -q --target php-composer -t ifdb-php-composer . >/dev/null
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
    -v "$(pwd):/app" -w /app ifdb-php-composer composer "$@"
rm -rf vendor
