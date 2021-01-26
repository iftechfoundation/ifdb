#!/bin/bash -ex

IFDB_FULL_EXPORT=$1

rm -rf initdb
mkdir initdb
echo "create database if not exists ifdb; use ifdb;\n" > initdb/init.sql
# remove bogus definers to fix views and rewrite URLs
grep -v 'DEFINER=' $IFDB_FULL_EXPORT/ifdb.sql | perl -pe 's!https?://ifdb.tads.org!https://ifdb.org!g' >> initdb/init.sql
echo "create database if not exists ifdb_images0; use ifdb_images0;\n" | cat - $IFDB_FULL_EXPORT/pics1.sql > initdb/pics1.sql
echo "create database if not exists ifdb_images1; use ifdb_images1;\n" | cat - $IFDB_FULL_EXPORT/pics2.sql > initdb/pics2.sql
echo "create database if not exists ifdb_images2; use ifdb_images2;\n" | cat - $IFDB_FULL_EXPORT/pics3.sql > initdb/pics3.sql
echo "create database if not exists ifdb_images3; use ifdb_images3;\n" | cat - $IFDB_FULL_EXPORT/pics4.sql > initdb/pics4.sql
echo "create database if not exists ifdb_images4; use ifdb_images4;\n" | cat - $IFDB_FULL_EXPORT/pics5.sql > initdb/pics5.sql

sed 's/"127.0.0.1", "username", "password"/"db", "root", "secret"/' local-credentials.php.template > www/local-credentials.php
sed 's/return null/return localCredentials()/' www/local-credentials.php > www/local-credentials2.php
mv www/local-credentials2.php www/local-credentials.php
