import entries from './entries-tuids-divisions.json' with {type: "json"};
import { username, password, url } from './settings.mjs';
import { XMLParser } from 'fast-xml-parser';
import { runTasks } from 'concurrency-limit-runner';

const tasks = entries.map(entry => async () => {
    if (!entry.division) return;
    console.log(entry.tuid, entry.division);
    try {
        const text = await fetch(`${url}/gametags?xml`, {
            method: 'post',
            body: new URLSearchParams({
                username,
                password,
                id: entry.tuid,
                mine_only: true,
            }),
        }).then(r => r.text());
        const xml = new XMLParser().parse(text);
        let tags = xml.response.tag;
        if (!Array.isArray(tags)) {
            tags = [tags];
        }
        tags.push({name: entry.division});
        const result = await fetch(`${url}/taggame?xml`, {
            method: 'post',
            body: new URLSearchParams({
                username,
                password,
                id: entry.tuid,
                ...Object.fromEntries(tags.map(({ name }, i) => [`t${i}`, name]))
            }),
        }).then(r => r.text());
        if (/<error>/.test(result)) throw new Error(result);
    } catch (cause) {
        throw new Error(`failed on ${entry.tuid}`, {cause});
    }
});

for await (const result of runTasks(10, tasks.values())) { }
