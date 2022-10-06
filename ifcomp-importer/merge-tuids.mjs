import {readFile, writeFile} from 'fs/promises';
import {XMLParser} from 'fast-xml-parser';

const microdata = JSON.parse(await readFile('microdata-downloads.json', 'utf8'));
const year = new Date().getFullYear();

function escapeXml(unsafe) {
    return unsafe.replace(/[<>&]/g, function (c) {
        switch (c) {
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '&': return '&amp;';
        }
    });
}

function escapeRegex(string) {
    return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
}

for (const game of microdata) {
    //if (game.tuid) continue;
    const queryString = `${game.name} published:${year}`;
    const searchUrl = `https://ifdb.org/search?xml&game&searchfor=${escape(queryString)}`;
    console.log(searchUrl);
    let response;
    try {
        response = await fetch(searchUrl);
    } catch (e) {}
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
}

await writeFile('microdata-downloads-tuids.json', JSON.stringify(microdata, null, 2), 'utf8');