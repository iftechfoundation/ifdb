#!/bin/bash -ex

# TODO: detect latest archive
FILENAME=ifdb-archive-20210117.zip
if [ ! -f $FILENAME ]; then
    curl -o $FILENAME https://ifarchive.org/if-archive/info/ifdb/$FILENAME
fi
unzip -o $FILENAME

rm -rf initdb
mkdir initdb
cat create_db.sql ifdb-archive.sql > initdb/00-init.sql
cp patch-schema.sql initdb/01-patch-schema.sql
cp create-admin.sql initdb/02-create-admin.sql

sed 's/"127.0.0.1", "username", "password"/"db", "root", "secret"/' local-credentials.php.template > www/local-credentials.php
