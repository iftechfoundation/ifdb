import { username, password, url, compYear } from './settings.mjs';
import { readFile } from 'fs/promises';
import { XMLParser } from 'fast-xml-parser';

const games = JSON.parse(await readFile('microdata-downloads-tuids.json', 'utf8'));

const fileTypes = {
    "html": "hypertextgame",
    "gblorb": "blorb/glulx",
    "t3": "tads3",
    "z5": "zcode",
    "z8": "zcode",
    "zblorb": "blorb/zcode",
    "ulx": "glulx",
    "exe": "executable"
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

for (const game of games) {
    const { name, tuid, zipFileName, zipMainFile } = game;

    const viewgameUrl = `${url}/viewgame?id=${tuid}&ifiction`;
    let response;
    try {
        response = await fetch(viewgameUrl);
    } catch (e) { }
    if (!response?.ok) {
        throw new Error(`Failed (${response?.status}) loading ${viewgameUrl}`);
    }
    const xml = new XMLParser().parse(await response.text());

    const downloadLink = `https://ifarchive.org/if-archive/games/competition${compYear}/${escape(zipFileName)}`;
    const downloadTitle = zipFileName.replace(/^Games\//, "");
    let fileType = "";
    if (zipMainFile) {
        const extension = zipMainFile.split(".").pop();
        fileType = fileTypes[extension];
        if (!fileType) throw new Error("missing fileType for extension: " + extension);
    } else {
        fileType = "storyfile"
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
    <link>
    <url>${downloadLink}</url>
    <title>${downloadTitle}</title>
    <isGame/>
    <format>${fileType}</format>
    ${fileType === 'executable' ? `<os>Windows.</os>`: ''}
    <compression>zip</compression>
    ${zipMainFile ? `<compressedPrimary>${zipMainFile}</compressedPrimary>`: ''}
    </link>
    </links></downloads>
    `;
    body.append('links', new Blob([links], { type: 'text/xml' }));
    response = await fetch(`${url}/putific`, {
        method: 'post',
        body,
    });
    const { status, statusText, ok } = response;
    const text = await response.text();
    if (!ok) {
        throw new Error(`Failed ${status} ${statusText} adding download link for ${name}: ${text}`);
    }
    console.log(name, `${url}/viewgame?id=${tuid}`, "OK");
}