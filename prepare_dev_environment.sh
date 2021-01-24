#!/bin/bash -ex

# TODO: detect latest archive
curl -o ifdb-archive.zip https://ifarchive.org/if-archive/info/ifdb/ifdb-archive-20210117.zip
unzip ifdb-archive.zip

mkdir -p initdb
cat create_db.sql ifdb-archive.sql patch-schema.sql > initdb/init.sql

sed 's/"127.0.0.1", "username", "password"/"db", "root", "secret"/' local-credentials.php.template > www/local-credentials.php
