#!/bin/bash

docker exec -it ifdb-db-1 mariadb -psecret ifdb --default-character-set=utf8mb4
