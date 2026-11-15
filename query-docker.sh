#!/bin/bash

docker compose exec db mariadb -psecret ifdb --default-character-set=utf8mb4
