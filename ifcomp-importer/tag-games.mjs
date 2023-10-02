import { username, password, url, compYear } from './settings.mjs';
import { readFile } from 'node:fs/promises';

const games = JSON.parse(await readFile('microdata-tuids.json', 'utf8'));

// TODO in the future, use the new taggame API that takes a username/password
const login = await fetch(`${url}/login`, {
    method: 'post',
    body: new URLSearchParams({
        userid: username,
        password,
    }),
});

const cookie = login.headers.getSetCookie()[0].split(';')[0];

for (const {name, tuid} of games) {
    console.log(name, tuid);
    const result = await fetch(`${url}/taggame?xml`, {
        method: 'post',
        headers: {
            cookie
        },
        credentials: 'include',
        body: new URLSearchParams({
            id: tuid,
            t0: `IFComp ${compYear}`,
        }),
    }).then(r => r.text());
    if (/<error>/.test(result)) throw new Error(result);
}