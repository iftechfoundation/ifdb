import { username, password, url, compYear } from './settings.mjs';
import { XMLParser } from 'fast-xml-parser';
import { runTasks } from 'concurrency-limit-runner';

const xml = new XMLParser().parse(await fetch(`${url}/search?pg=all&xml&searchbar=tag:IFComp ${compYear}`).then(r => r.text()));

const games = xml.searchReply.games.game;

const tasks = games.map(({tuid}) => async () => {
    console.log('start', tuid);
    const xml = new XMLParser().parse(await fetch(`${url}/viewgame?id=${tuid}&ifiction`).then(r=>r.text()));
    let existingLinks = xml.ifindex.story.ifdb.downloads?.links?.link ?? [];
    if (!Array.isArray(existingLinks)) existingLinks = [existingLinks];
    const filtered = existingLinks.filter(l => !/https:\/\/ifcomp.org\/ballot/.test(l.url));
    if (existingLinks.length === filtered.length) {
        console.log(`${url}/viewgame?id=${tuid}`, 'NO BALLOT LINK');
        return;
    }
    const body = new FormData();
    body.append('username', username);
    body.append('password', password);
    body.append('lastversion', xml.ifindex.story.ifdb.pageversion);
    const ifiction = `<?xml version="1.0" encoding="UTF-8"?>
    <ifindex version="1.0" xmlns="http://babel.ifarchive.org/protocol/iFiction/">
    <story>
    <identification><tuid>${tuid}</tuid></identification>
    </story>
    </ifindex>
    `
    body.append('ifiction', new Blob([ifiction], { type: 'text/xml' }));
    const links = `<?xml version="1.0" encoding="UTF-8"?>
    <downloads xmlns="http://ifdb.org/api/xmlns"><links>
    ${filtered.map(link => `
        <link>
        <url>${link.url}</url>
        ${('isGame' in link ? '<isGame/>' : '')}
        ${['title', 'desc', 'format', 'os', 'compression', 'compressedPrimary'].map(field => 
            field in link ? `<${field}>${link[field]}</${field}>` : ''
        ).join('\n')}
        </link>
    `)}
    
    </links></downloads>
    `;
    body.append('links', new Blob([links], { type: 'text/xml' }));
    body.append('replaceLinks', 'yes');
    const response = await fetch(`${url}/putific`, {
        method: 'post',
        body,
    });
    const { status, statusText, ok } = response;
    const text = await response.text();
    if (!ok) {
        throw new Error(`Failed ${status} ${statusText} adding download link for ${name}: ${text}`);
    }
    console.log(`${url}/viewgame?id=${tuid}`, "OK");
});

for await (const result of runTasks(10, tasks.values())) { }
