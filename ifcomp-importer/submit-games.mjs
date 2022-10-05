import { readFile } from 'fs/promises';

const firstpublished = '2022-10-01';

if ((Date.now() - new Date(firstpublished).getTime()) > 24 * 365 * 60 * 60 * 1000) {
    throw new Error(`firstpublished date ${firstpublished} is more than a year ago`);
}

const username = 'ifdbadmin@ifdb.org';
const password = 'secret';
const url = 'http://localhost:8080/putific';

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

const games = JSON.parse(await readFile('microdata.json', 'utf8'));
const coverArt = JSON.parse(await readFile('cover-art.json', 'utf8'));

const errors = {};

for (const game of games) {
    const body = new FormData();
    body.append('username', username);
    body.append('password', password);
    let authors = "";
    if (game.authors.length === 1) {
        authors = escapeXml(game.authors[0]);
    } else {
        for (let i = 0; i < (game.authors.length - 1); i++) {
            if (i > 0) authors += ", ";
            authors += escapeXml(game.authors[i]);
        }
        authors += " and " + escapeXml(game.authors[game.authors.length - 1]);
    }
    let description = game.description;
    if (game.alternateName) {
        if (description.substring(0, game.alternateName.length) != game.alternateName) {
            description = `${game.alternateName}\n\n${game.description}`;
        }
    }
    if (description) {
        description = escapeXml(description);
        description = description.replace(/\n\n/g, "<br/>\n\n");
    }

    /* firstpublished format description genre */
    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<ifindex version="1.0" xmlns="http://babel.ifarchive.org/protocol/iFiction/">
<story>
    ${game.gamePlatform ? `<identification>
        <format>${escapeXml(game.gamePlatform)}</format>
    </identification>
    ` : ''}
    <bibliographic>
        <title>${escapeXml(game.name)}</title>
        <author>${authors}</author>
        ${description ? `<description>${description}</description>` : '' }
        <firstpublished>${firstpublished}</firstpublished>
        ${game.genre ? `<genre>${escapeXml(game.genre)}</genre>` : ''}
    </bibliographic>
</story>
</ifindex>
`;
    console.log(xml);
    body.append('ifiction', new Blob([xml], { type: 'text/xml' }));
    body.append('requireIFID', 'force');
    if (game.thumbnailArtUrl) {
        const [, entryId] = /\/([^\/]+?)\/cover$/.exec(game.thumbnailArtUrl);
        const imageFile = coverArt[entryId];
        let mimeType;
        if (/\.png/.test(imageFile)) {
            mimeType = 'image/png';
        } else {
            mimeType = 'image/jpg';
        }
        const image = await readFile(imageFile);
        body.append('coverart', new Blob([image], { type: mimeType }));
    }

    const response = await fetch(url, {
        method: 'post',
        body,
    });
    const { status, statusText } = response;
    const text = await response.text();
    console.log(JSON.stringify({ name: game.name, status, statusText, text }, null, 2));
    if (status !== 200) {
        const [,code] = /<code>(.*?)<\/code>/.exec(text);
        if (!code) code = "unknown";
        if (!errors[code]) errors[code] = [];
        errors[code].push({ name: game.name, status, statusText, text });
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
