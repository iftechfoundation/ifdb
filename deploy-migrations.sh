#!/bin/bash -e

DBMATE_VERSION=v2.38.1
DBMATE_SHA256=3e2c62735e0e63f3e5e4de6a3fa5c8a29ac9a34dcb8631a08d9589ab943ad9bd
DBMATE=/usr/local/bin/dbmate

cd "$(dirname "$0")"

if ! echo "$DBMATE_SHA256  $DBMATE" | sha256sum --check --status 2>/dev/null; then
    tmp=$(mktemp)
    trap 'rm -f "$tmp"' EXIT
    curl -fsSL -o "$tmp" "https://github.com/amacneil/dbmate/releases/download/$DBMATE_VERSION/dbmate-linux-amd64"
    echo "$DBMATE_SHA256  $tmp" | sha256sum --check --quiet
    install -m 755 "$tmp" "$DBMATE"
fi

"$DBMATE" --url "mysql://root@/ifdb?socket=/run/mysqld/mysqld.sock" \
    --migrations-dir sql/migrations --no-dump-schema up
