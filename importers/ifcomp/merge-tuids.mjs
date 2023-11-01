import {url} from './settings.mjs';
import {readFile, writeFile} from 'fs/promises';
import {XMLParser} from 'fast-xml-parser';
import {runTasks} from 'concurrency-limit-runner';

const microdata = JSON.parse(await readFile('microdata.json', 'utf8'));
const year = new Date().getFullYear();

const tasks = [];
for (const game of microdata) {
    tasks.push(async () => {
        //if (game.tuid) continue;
        const queryString = `${game.name} published:${year}`;
        const searchUrl = `${url}/search?xml&game&searchfor=${escape(queryString)}`;
        console.log(searchUrl);
        let response;
        try {
            response = await fetch(searchUrl);
        } catch (e) { }
        if (!response?.ok) {
            throw new Error(`Failed (${response?.status}) loading ${searchUrl}`);
        }
        const xml = new XMLParser().parse(await response.text());
        let games = xml.searchReply.games.game;
        if (!Array.isArray(games)) {
            // when there's only one result, the parser inlines the array
            games = [games];
        }
        const matches = games.filter(result => result.title === game.name);
        if (!matches.length) {
            console.log(`Couldn't find ${game.name}: ${JSON.stringify(xml)}`);
        } else if (matches.length > 1) {
            console.log(`Too many matches for ${game.name}: ${JSON.stringify(xml)}`);
        } else {
            game.tuid = matches[0].tuid;
        }
    })
}

for await (const result of runTasks(10, tasks.values())) { }

await writeFile('microdata-tuids.json', JSON.stringify(microdata, null, 2), 'utf8');
