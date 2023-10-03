import { username, password, url, compYear } from './settings.mjs';
import { readFile } from 'node:fs/promises';

const games = JSON.parse(await readFile('microdata-tuids.json', 'utf8'));

for (const {name, tuid} of games) {
    console.log(name, tuid);
    const result = await fetch(`${url}/taggame?xml`, {
        method: 'post',
        body: new URLSearchParams({
            username,
            password,
            id: tuid,
            t0: `IFComp ${compYear}`,
        }),
    }).then(r => r.text());
    if (/<error>/.test(result)) throw new Error(result);
}