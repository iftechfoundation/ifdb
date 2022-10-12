#!/usr/bin/env node
// This script works in Node 18+

// update the username and password to your email address and password
// if you want to run it on the local Docker dev environment, change the url to http://localhost:8080/putific
// but beware, per the README, the local dev environment doesn't allow you to upload images, so you won't see the cover art

// or, you can even copy and paste this script into Chrome Dev Tools
// Just go to the IFDB home page (either ifdb.org or localhost:8080, both work) and paste the script in

const body = new FormData();
body.append('username', 'ifdbadmin@ifdb.org');
body.append('password', 'secret');
let url = 'https://ifdb.org/putific';
// url = 'http://localhost:8080/putific';
if (typeof window !== 'undefined' && /\b(ifdb.org|localhost)$/.test(location.hostname)) url = '/putific';
const xml = `<?xml version="1.0" encoding="UTF-8"?>
<ifindex version="1.0" xmlns="http://babel.ifarchive.org/protocol/iFiction/">
<story>
<bibliographic>
<title>Test ${Date.now()}</title>
<author>Test Author</author>
</bibliographic>
</story>
</ifindex>
`;
body.append('ifiction', new Blob([xml], { type: 'text/xml' }));
const links = `<?xml version="1.0" encoding="UTF-8"?>
<downloads xmlns="http://ifdb.org/api/xmlns"><links>
<link>
<url>http://www.ifarchive.org/if-archive/games/palm/ACgames.zip</url>
<title>ACgames.zip</title>
<desc>converted to PalmOS .prc file</desc>
<isGame/>
<format>executable</format>
<os>PalmOS.PalmOS-LoRes</os>
<compression>zip</compression>
<compressedPrimary>PHOTOPIA.PRC</compressedPrimary>
</link>
</links></downloads>
`;
body.append('links', new Blob([links], { type: 'text/xml' }));
body.append('requireIFID', 'no');

if (typeof process !== 'undefined') {
    // we're in node
    const { readFile } = await import('fs/promises');
    const image = await readFile('cover.png');
    body.append('coverart', new Blob([image], { type: 'image/png' }));
}

const response = await fetch(url, {
    method: 'post',
    body,
});
const { status, statusText } = response;
const text = await response.text();
console.log(JSON.stringify({ status, statusText, text }, null, 2));
