#!/bin/bash

rsync -hrv --progress www/* vaughany@45.79.166.17:/var/www/html/
scp www/.htaccess vaughany@45.79.166.17:/var/www/html/
