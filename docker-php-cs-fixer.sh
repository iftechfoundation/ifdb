#!/bin/bash -ex
docker run --rm -v "$(pwd):/code" -w /code \
    ghcr.io/php-cs-fixer/php-cs-fixer:3-php8.3 \
    fix "$@"
