import {url} from './settings.mjs';
import {readFile, writeFile} from 'fs/promises';
import {XMLParser} from 'fast-xml-parser';
import {runTasks} from 'concurrency-limit-runner';

const entries = JSON.parse(await readFile('entries.json', 'utf8'));
const year = new Date().getFullYear();

const tasks = [];
for (const game of entries) {
    tasks.push(async () => {
        //if (game.tuid) continue;
        const queryString = `${game.title} published:${year}`;
        const searchUrl = `${url}/search?xml&game&searchfor=${escape(queryString)}`;
        console.log(searchUrl);
        const response = await fetch(searchUrl);
        if (!response?.ok) {
            throw new Error(`Failed (${response?.status}) loading ${searchUrl}`);
        }
        const xml = new XMLParser().parse(await response.text());
        let games = xml.searchReply.games.game;
        if (!Array.isArray(games)) {
            // when there's only one result, the parser inlines the array
            games = [games];
        }
        const matches = games.filter(result => result.title === game.title);
        if (!matches.length) {
            console.log(`Couldn't find ${game.title}: ${JSON.stringify(xml)}`);
        } else if (matches.length > 1) {
            console.log(`Too many matches for ${game.title}: ${JSON.stringify(xml)}`);
        } else {
            game.tuid = matches[0].tuid;
        }
    })
}

for await (const result of runTasks(10, tasks.values())) { }

await writeFile('entries-tuids.json', JSON.stringify(entries, null, 2), 'utf8');
