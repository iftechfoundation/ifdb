# IFDB HTTP smoke tests (Hurl)

These tests drive the local IFDB over HTTP with [Hurl](https://hurl.dev)

## Setup

1. Start IFDB locally (`docker compose up` → http://localhost:8080).
2. From this directory:

```sh
npm install
```

That pulls `@orangeopensource/hurl`, which downloads the correct Hurl binary for your OS.

## Running

```sh
npm test
```

## Layout

- `http/write/write.hurl` — register a new user, activate it via adminops, and create test data
- `http/readonly/` — parallel verification
- `http/run.mjs` — Test orchestration + shared variables
