#!/bin/bash

docker exec -it ifdb-web-1 php /var/www/html/cron-pending.php $1