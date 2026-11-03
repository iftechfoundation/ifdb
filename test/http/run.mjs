#!/usr/bin/env node
// Orchestrate Hurl smoke tests: writes, then parallel read-only checks.
import { spawnSync } from 'node:child_process';
import {
  appendFileSync,
  mkdtempSync,
  readdirSync,
  readFileSync,
  writeFileSync,
} from 'node:fs';
import { cpus, tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');
const HTTP_DIR = __dirname;
const BASE_URL = 'http://localhost:8080';

const work = mkdtempSync(join(tmpdir(), 'ifdb-hurl-'));
const varsFile = join(work, 'vars.env');
const writeJson = join(work, 'write.json');
const ifictionPath = join(work, 'ifiction.xml');

function runHurl(hurlArgs) {
  const result = spawnSync('npx', ['hurl', ...hurlArgs], {
    cwd: ROOT,
    encoding: 'utf8',
    stdio: 'inherit',
  });
  if (result.error) throw result.error;
  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}

function writeCommonVars(suffix) {
  const email = `hurl-smoke-${suffix}@example.com`;
  const password = `Secret1a-${suffix}`;
  const basicAuth = Buffer.from(`${email}:${password}`, 'utf8').toString('base64');
  writeFileSync(
    varsFile,
    `base_url=${BASE_URL}
suffix=${suffix}
email=${email}
password=${password}
username=HurlSmoke${suffix}
author=Hurl Author ${suffix}
game_title=Hurl Smoke Game ${suffix}
poll_title=Hurl Smoke Poll ${suffix}
poll_desc=A poll created by hurl smoke tests ${suffix}
list_title=Hurl Smoke List ${suffix}
list_desc=A list from hurl smoke ${suffix}
comp_title=Hurl Smoke Comp ${suffix}
comp_desc=A competition from hurl smoke ${suffix}
review_summary=Hurl smoke summary ${suffix}
review_body=This is a hurl smoke test review body for ${suffix}.
comment_body=Hurl smoke comment on the review ${suffix}.
game_title_encoded=${encodeURIComponent(`Hurl Smoke Game ${suffix}`)}
poll_title_encoded=${encodeURIComponent(`Hurl Smoke Poll ${suffix}`)}
list_title_encoded=${encodeURIComponent(`Hurl Smoke List ${suffix}`)}
comp_title_encoded=${encodeURIComponent(`Hurl Smoke Comp ${suffix}`)}
basic_auth=${basicAuth}
ifiction_path=ifiction.xml
`,
  );
}

function writeIfiction(suffix) {
  writeFileSync(
    ifictionPath,
    `<?xml version="1.0" encoding="UTF-8"?>
<ifindex version="1.0" xmlns="http://babel.ifarchive.org/protocol/iFiction/">
<story>
<bibliographic>
<title>Hurl Smoke Game ${suffix}</title>
<author>Hurl Author ${suffix}</author>
<description>A game created by hurl smoke tests ${suffix}.</description>
</bibliographic>
</story>
</ifindex>
`,
  );
}

function convertCapturesToVars() {
  // Hurl --json shape we care about (other entry fields omitted):
  // {
  //   "entries": [
  //     { "captures": [{ "name": "user_id", "value": "abc123" }] },
  //     { "captures": [{ "name": "game_id", "value": "def456" }] },
  //     { "captures": [{ "name": "poll_id", "value": "ghi789" }] },
  //     { "captures": [] }
  //   ]
  // }
  const data = JSON.parse(readFileSync(writeJson, 'utf8'));
  const captures = Object.fromEntries(
    (data.entries ?? []).flatMap((entry) =>
      (entry.captures ?? []).map((cap) => [cap.name, cap.value]),
    ),
  );
  const needed = ['game_id', 'poll_id', 'list_id', 'comp_id', 'review_id'];
  const missing = needed.filter((k) => !(k in captures));
  if (missing.length) {
    throw new Error(`Missing captures from write run: ${missing.join(', ')}`);
  }
  appendFileSync(
    varsFile,
    Object.entries(captures)
      .map(([k, v]) => `${k}=${v}`)
      .join('\n') + '\n',
  );
}

const suffix = String(Math.floor(Date.now() / 1000));
writeCommonVars(suffix);
writeIfiction(suffix);

console.log(`==> Writes (${suffix}) in ${work}`);
runHurl([
  '--json',
  '--output',
  writeJson,
  '--file-root',
  work,
  '--variables-file',
  varsFile,
  join(HTTP_DIR, 'write/write.hurl'),
]);
convertCapturesToVars();

const readonlyFiles = readdirSync(join(HTTP_DIR, 'readonly'))
  .filter((name) => name.endsWith('.hurl'))
  .map((name) => join(HTTP_DIR, 'readonly', name))
  .sort();
console.log('==> Parallel read-only checks');
runHurl([
  '--test',
  '--jobs',
  String(cpus().length || 1),
  '--variables-file',
  varsFile,
  ...readonlyFiles,
]);
console.log('All Hurl smoke tests passed');
