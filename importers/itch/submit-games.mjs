import { username, password, url, compStartDate, tagName } from './settings.mjs';
import { readFile } from 'fs/promises';
import { XMLParser } from 'fast-xml-parser';

if ((Date.now() - new Date(compStartDate).getTime()) > 24 * 365 * 60 * 60 * 1000) {
    throw new Error(`compStartDate date ${compStartDate} is more than a year ago`);
}

function escapeXml(unsafe) {
    return unsafe.replace(/[<>&'"]/g, function (c) {
        switch (c) {
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '&': return '&amp;';
            case '\'': return '&apos;';
            case '"': return '&quot;';
        }
    });
}

const games = JSON.parse(await readFile('entries.json', 'utf8'));
const coverArt = JSON.parse(await readFile('cover-art.json', 'utf8'));

const errors = {};

for (const game of games) {
    const body = new FormData();
    body.append('username', username);
    body.append('password', password);
    
    const title = escapeXml(game.title);
    const author = escapeXml(game.author);
    const description = game.short_text ? escapeXml(game.short_text) : null;
    const game_url = escapeXml(game.url);

    /* firstpublished format description genre */
    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<ifindex version="1.0" xmlns="http://babel.ifarchive.org/protocol/iFiction/">
<story>
    <bibliographic>
        <title>${title}</title>
        <author>${author}</author>
        ${description ? `<description>${description}</description>` : '' }
        <firstpublished>${compStartDate}</firstpublished>
    </bibliographic>
    <contacts><url>${game_url}</url></contacts>
</story>
</ifindex>
`;
    body.append('ifiction', new Blob([xml], { type: 'text/xml' }));

    if (game.play_online) {
        const links = `<?xml version="1.0" encoding="UTF-8"?>
<downloads xmlns="http://ifdb.org/api/xmlns"><links><link>
    <url>${game_url}</url>
    <title>Play Online</title>
    <isGame />
    <format>hypertextgame</format>
</link></links></downloads>
        `;
        body.append('links', new Blob([links], { type: 'text/xml' }));
    }

    body.append('requireIFID', 'force');
    if (game.cover) {
        const imageFile = coverArt[game.id];
        let mimeType;
        if (/\.png/.test(imageFile)) {
            mimeType = 'image/png';
        } else {
            mimeType = 'image/jpg';
        }
        const image = await readFile(imageFile);
        body.append('coverart', new Blob([image], { type: mimeType }));
    }

    const response = await fetch(`${url}/putific`, {
        method: 'post',
        body,
    });
    const { status, statusText } = response;
    const text = await response.text();
    console.log(JSON.stringify({ title: game.title, status, statusText, text }, null, 2));
    if (status !== 200) {
        const [,code] = /<code>(.*?)<\/code>/.exec(text);
        if (!code) code = "unknown";
        if (!errors[code]) errors[code] = [];
        errors[code].push({ title: game.title, status, statusText, text });
    } else {
        const xml = new XMLParser().parse(text);
        const tuid = xml.putific.tuid;
        const result = await fetch(`${url}/taggame?xml`, {
            method: 'post',
            body: new URLSearchParams({
                username,
                password,
                id: tuid,
                t0: tagName,
            }),
        }).then(r => r.text());
        if (/<error>/.test(result)) throw new Error(result);
    }
}

let errorCount = 0;
if (Object.keys(errors).length) {
    console.log("ERRORS");
    for (const code in errors) {
        console.log("  " + code);
        for (const error of errors[code]) {
            errorCount++;
            console.log("    " + JSON.stringify(error, null, 2));
        }
        console.log(`  ${errors[code].length} ${code} error(s)`);
    }
    console.log(`${errorCount} error(s)`);
}
