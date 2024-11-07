#!/bin/bash

docker exec -it ifdb-db-1 mysql -psecret ifdb --default-character-set=utf8mb4
