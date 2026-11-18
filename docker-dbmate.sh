#!/bin/bash -e
deps=()
if [ "$1" = new ]; then deps=(--no-deps); fi
docker compose run --rm "${deps[@]}" --user "$(id -u):$(id -g)" migrate "$@"
