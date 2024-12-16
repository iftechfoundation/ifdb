#!/bin/bash -ex

cp ./php-composer-setup.sh www
cp ./composer.json www
cp ./composer.lock www
docker compose exec --privileged web bash ./php-composer-setup.sh
docker compose exec --privileged web bash
rm www/php-composer-setup.sh
mv www/composer.json .
mv www/composer.lock .
