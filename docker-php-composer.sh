#!/bin/bash -ex
docker run --rm -v "$(pwd):/app" builder composer "$@"
rm -rf vendor
