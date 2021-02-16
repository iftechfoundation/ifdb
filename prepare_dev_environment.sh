#!/bin/bash -ex

# TODO: detect latest archive
FILENAME=ifdb-archive-20210117.zip
if [ ! -f sql/$FILENAME ]; then
    curl -o sql/$FILENAME https://ifarchive.org/if-archive/info/ifdb/$FILENAME
fi
unzip -o sql/$FILENAME

rm -rf initdb
mkdir initdb
cp sql/create-db.sql initdb/00-init.sql
perl -pe 's!https?://ifdb.tads.org!https://ifdb.org!g' < ifdb-archive.sql >> initdb/00-init.sql
cp sql/patch-schema.sql initdb/01-patch-schema.sql
cp sql/create-admin.sql initdb/02-create-admin.sql

sed 's/"127.0.0.1", "username", "password"/"db", "root", "secret"/' local-credentials.php.template > www/local-credentials.php
