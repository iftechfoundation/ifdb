# IFDB HTTP smoke tests (Hurl)

These tests drive the local IFDB over HTTP with [Hurl](https://hurl.dev)

## Setup

Requires Docker. Start IFDB locally (`docker compose up` → http://localhost:8080). Hurl runs from its official Docker image and targets the `web` container of this checkout's Compose project, so that must be running.

## Running

```sh
npm test
```

## Layout

- `http/write/write.hurl` — register a new user, activate it via adminops, and create test data
- `http/readonly/` — parallel verification
- `http/run.mjs` — Test orchestration + shared variables
