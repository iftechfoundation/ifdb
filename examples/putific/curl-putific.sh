#!/bin/bash

# if you want to test on the local Docker dev environment, change the url to http://localhost:8080/putific
# but beware, per the README, the local dev environment doesn't allow you to upload images, so you won't see the cover art

USERNAME=ifdbadmin@ifdb.org
PASSWORD=secret
URL=https://ifdb.org/putific
#URL=http://localhost:8080/putific

TMP_DIR=`mktemp -d`
TMP_FILE="$TMP_DIR"/ifiction.xml
TIMESTAMP=$(date +%s)
cat <<EOT > $TMP_FILE
<?xml version="1.0" encoding="UTF-8"?>
<ifindex version="1.0" xmlns="http://babel.ifarchive.org/protocol/iFiction/">
<story>
<bibliographic>
<title>Test $TIMESTAMP</title>
<author>Test Author</author>
</bibliographic>
</story>
</ifindex>
EOT

curl -v \
    -F username="$USERNAME" \
    -F password="$PASSWORD" \
    -F ifiction=@$TMP_FILE \
    -F coverart=@cover.png \
    -F requireIFID=no \
    "$URL"
