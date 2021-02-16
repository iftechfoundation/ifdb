#!/bin/bash -ex

IFDB_FULL_EXPORT=$1

rm -rf initdb
mkdir initdb
echo "create database if not exists ifdb CHARACTER SET latin1 COLLATE latin1_german2_ci; use ifdb;" > initdb/ifdb.sql
grep -a -v 'DEFINER=' $IFDB_FULL_EXPORT/ifdb-*.sql >> initdb/ifdb.sql
echo "create database if not exists ifdb_images0; use ifdb_images0;" | cat - $IFDB_FULL_EXPORT/ifdb_images0-*.sql > initdb/ifdb_images0.sql
echo "create database if not exists ifdb_images1; use ifdb_images1;" | cat - $IFDB_FULL_EXPORT/ifdb_images1-*.sql > initdb/ifdb_images1.sql
echo "create database if not exists ifdb_images2; use ifdb_images2;" | cat - $IFDB_FULL_EXPORT/ifdb_images2-*.sql > initdb/ifdb_images2.sql
echo "create database if not exists ifdb_images3; use ifdb_images3;" | cat - $IFDB_FULL_EXPORT/ifdb_images3-*.sql > initdb/ifdb_images3.sql
echo "create database if not exists ifdb_images4; use ifdb_images4;" | cat - $IFDB_FULL_EXPORT/ifdb_images4-*.sql > initdb/ifdb_images4.sql

sed 's/"127.0.0.1", "username", "password"/"db", "root", "secret"/' local-credentials.php.template > www/local-credentials.php
sed 's/return null/return localCredentials()/' www/local-credentials.php > www/local-credentials2.php
mv www/local-credentials2.php www/local-credentials.php
