import {writeFileSync} from 'node:fs';
import {jamEntriesUrl, divisions} from './settings.mjs';
import entries from './entries-tuids.json' with {type: "json"};
import { runTasks } from 'concurrency-limit-runner';

async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function tryTryAgain(code, tries = 3) {
    for (let i = 0; i < tries; i++) {
        try {
            return await code();
        } catch (e) {
            console.error(e);
            if (i < (tries - 1)) {
                console.error('retrying ' + i);
            } else {
                throw e;
            }
        }
    }
}

async function fetchRateUrl(rateUrl) {
    return tryTryAgain(async () => {
        await sleep(500);
        const response = await fetch(rateUrl);
        if (!response.ok) throw new Error(`failed fetching ${rateUrl}: ${response.status} ${response.statusText}: ${await response.text()}`);
        return response.text();
    });
}

const tasks = entries.map(entry => async () => {
    const rateUrl = `https://itch.io${entry.rate}`;
    console.log(rateUrl);

    const page = await fetchRateUrl(rateUrl);
    let found = false;
    for (const division of divisions) {
        if (page.includes(division)) {
            entry.division = division;
            found = true;
            break;
        }
    }
    if (!found) console.error(`Couldn't find a division for ${rateUrl}: ${page}`);
});

for await (const result of runTasks(10, tasks.values())) { }

writeFileSync('entries-tuids-divisions.json', JSON.stringify(entries, null, 2), 'utf8');