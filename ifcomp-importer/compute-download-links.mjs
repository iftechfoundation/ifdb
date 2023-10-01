import {compYear} from './settings.mjs';
import {readFile, writeFile} from 'fs/promises';
import JSZip from 'jszip';
import leven from 'leven';

const zipFileName = `IFComp${compYear}.zip`;
const zipFileBytes = await readFile(zipFileName);
const zip = await JSZip.loadAsync(zipFileBytes);
const zipKeys = Object.keys(zip.files);
const microdata = JSON.parse(await readFile('microdata-tuids.json', 'utf8')).sort((a,b) => a.name.localeCompare(b.name));
const gameZipFileNames = new Set(zipKeys.filter(key => /^Games\/.*\.zip$/.test(key)));

for (const game of microdata) {
    const strippedName = game.name.replace(/[^A-Za-z0-9 ]/g, '').replace(/\s+/g, ' ');
    const expectedFileName = `Games/${strippedName}.zip`;
    if (gameZipFileNames.has(expectedFileName)) {
        game.zipFileName = expectedFileName;
        gameZipFileNames.delete(expectedFileName);
    }
}
// find inexact matches
for (const game of microdata) {
    if (game.zipFileName) continue;
    const strippedName = game.name.replace(/[^A-Za-z0-9 ]/g, '').replace(/\s+/g, ' ');
    const expectedFileName = `Games/${strippedName}.zip`;
    const [closestMatch] = [...gameZipFileNames].sort((a, b) => leven(expectedFileName, a) - leven(expectedFileName, b));
    if (leven(closestMatch, expectedFileName) > 5) {
        console.log(`MISSING ${game.name}, closest match is ${closestMatch}`);
    } else {
        game.zipFileName = closestMatch;
        gameZipFileNames.delete(closestMatch);
    }
}

const htmlPlatforms = new Set([
    'ChoiceScript',
    'Ink',
    'Texture',
    'Twine',
    'Web-based',
])

    // 'Z-code',
    // 'TADS',
    // 'Glulx',

function findCandidatesMatchingExtension(game, zipKeys, extensions) {
    let candidates = zipKeys.filter(key => !/\/.*\//.test(key));
    let matches = candidates.filter(key => {
        for (const extension of extensions) {
            if (key.endsWith("." + extension)) return true;
        }
        return false;
    });

    if (matches.length === 0) {
        if (candidates.length === 1 && candidates[0].endsWith("/")) {
            candidates = zipKeys.filter(key => !/\/.*\/.*\//.test(key));
            matches = candidates.filter(key => {
                for (const extension of extensions) {
                    if (key.endsWith("." + extension)) return true;
                }
                return false;
            });
        }
    }

    if (matches.length === 0) {
        console.log(`no candidate [${extensions.join(' ')}] files for ${game.name}:\n  ${candidates.join('\n  ')}`);
    } else if (matches.length === 1) {
        game.zipMainFile = matches[0];
        return true;
    } else {
        console.log(`too many candidate [${extensions.join(' ')}] files for ${game.name}:\n  ${matches.join('\n  ')}`);
    }
}

for (const game of microdata) {
    if (!game.zipFileName) continue;
    if (!game.gamePlatform) continue;
    const gameZip = await JSZip.loadAsync(await zip.files[game.zipFileName].async("arraybuffer"));
    const zipKeys = Object.keys(gameZip.files);
    let result;
    if (htmlPlatforms.has(game.gamePlatform)) {
        result = findCandidatesMatchingExtension(game, zipKeys, ["html"]);
    } else if (game.gamePlatform === 'Z-code') {
        result = findCandidatesMatchingExtension(game, zipKeys, ["zblorb", "z5", "z8"]);
    } else if (game.gamePlatform === 'Glulx') {
        result = findCandidatesMatchingExtension(game, zipKeys, ["gblorb", "ulx"]);
        
    } else if (game.gamePlatform === 'TADS') {
        result = findCandidatesMatchingExtension(game, zipKeys, ["t3"]);
    } else if (game.gamePlatform === 'Windows executable') {
        result = findCandidatesMatchingExtension(game, zipKeys, ["exe"]);
    } else {
        console.log(`no main file for ${game.gamePlatform} ${game.name}`)
    }
}

await writeFile('microdata-downloads-tuids.json', JSON.stringify(microdata, null, 2), 'utf8');
