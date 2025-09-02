import { compYear } from './settings.mjs';
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { XMLParser } from 'fast-xml-parser';

const ifcompGames = await fetch(`https://ifcomp.org/comp/${compYear}/json`).then(r => r.json());

if (!ifcompGames[0].ifdb_id) {
    if (!existsSync('tuid-entry-map.json')) {
        throw new Error("IFComp hasn't run populate_ifdb_ids.pl. Ask the IFComp team to run that script, or run compute-tuid-entry-map.mjs to generate tuid-entry-map.json.");
    }
    const tuidsByEntryId = JSON.parse(readFileSync('tuid-entry-map.json'));
    const entryIdsByTuid = Object.fromEntries(Object.entries(tuidsByEntryId).map(([tuid,entry]) => [entry, tuid]));
    for (const game of ifcompGames) {
        game.ifdb_id = entryIdsByTuid[game.id];
    }
}

const ifcompGamesByTuid = Object.fromEntries(ifcompGames.map(g => [g.ifdb_id, g]));

function fileTuid(file) {
    if (!file.metadata) return null;
    let items = file.metadata.item;
    if (!items.length) items = [items];
    for (const item of items) {
        if (item.key === 'tuid') {
            return item.value;
        }
    }
    return null;
}

const xml = new XMLParser().parse(await fetch('https://ifarchive.org/indexes/Master-Index.xml').then(r=>r.text()));

const compDir = `if-archive/games/competition${compYear}`;
const links = xml.ifarchive.file.filter(f => f.path.startsWith(compDir) && fileTuid(f));

if (!links.length) {
    throw new Error(`No files found in ${compDir} with TUID metadata; contact the IF Archive team?`);
}

const formats = {
    "html": "hypertextgame",
    "gblorb": "blorb/glulx",
    "t3": "tads3",
    "z5": "zcode",
    "z8": "zcode",
    "zblorb": "blorb/zcode",
    "ulx": "glulx",
    "exe": "executable"
}

const extensionsByPlatform = {
    'website': ['html'],
    'choicescript': ['html'],
    'ink': ['html'],
    'texture': ['html'],
    'twine': ['html'],
    'adventuron': ['html'],
    'zcode': ['zblorb', 'z5', 'z8'],
    'inform': ['gblorb', 'ulx'],
    'inform-website': ['gblorb', 'ulx'],
    'quixe': ['gblorb', 'ulx'],
    'tads': ['t3'],
    'windows': ['exe'],
    'other': [],
}

function findFileMatchingExtensions(path, files, extensions) {
    let candidates = files.filter(f => {
        if (f.startsWith('__MACOSX/')) return false;
        for (const extension of extensions) {
            if (f.endsWith('.' + extension)) return true;
        }
        return false;
    });

    // if there are multiple HTML candidates, prefer `index.html`
    if (candidates.length > 1 && extensions.length === 1 && extensions[0] === 'html') {
        const indexHtml = candidates.filter(f => f.split("/").at(-1) === "index.html");
        if (indexHtml.length === 1) {
            candidates = indexHtml;
        }
    }

    if (candidates.length === 0) {
        console.log(`no candidate [${extensions.join(' ')}] files for ${path}`);
        return null;
    } else if (candidates.length > 1) {
        console.log(`too many candidate [${extensions.join(' ')}] files for ${path}:\n  ${candidates.join('\n  ')}`);
    }
    return candidates;
}

const results = await Promise.all(links.map(async (link) => {
    const tuid = fileTuid(link);
    const ifcompGame = ifcompGamesByTuid[tuid];
    const { name, path } = link;
    const url = `https://ifarchive.org/${path}`;
    const extension = name.split('.').at(-1);

    if (extension === 'zip') {
        try {
            const unboxUrl = `https://unbox.ifarchive.org/?` + new URLSearchParams({
                url: '/' + path,
                json: 1
            });
            // console.log(unboxUrl);
            const response = await fetch(unboxUrl);
            const {files} = JSON.parse(await response.text());
            const gamePlatform = ifcompGame.is_zcode ? "zcode" : ifcompGame.platform;
            if (!gamePlatform) {
                console.log(`No gamePlatform for ${path} ${tuid}`);
                return { tuid, name, url };
            }
            const extensions = extensionsByPlatform[gamePlatform];
            if (!extensions) {
                throw new Error("No known extensions for gamePlatform " + gamePlatform);
            }
            const candidates = findFileMatchingExtensions(path, files, extensions);
            if (candidates?.length > 1) {
                const extension = candidates[0].split('.').at(-1);
                const format = formats[extension];
                return { tuid, name, url, format };
            } else if (candidates?.length === 1) {
                const zipMainFile = candidates[0];
                const extension = zipMainFile.split('.').at(-1);
                const format = formats[extension];
                if (!format) throw new Error("Unknown format for " + path);
                return { tuid, name, url, format, zipMainFile };
            } else {
                return { tuid, name, url };
            }
        } catch (cause) {
            throw new Error("Failed fetching file list for " + path, {cause});
        }
    } else {
        const format = formats[extension];
        if (!format) throw new Error("Unknown format for " + path);
        return { tuid, name, url, format };
    }
}));


writeFileSync('external-links.json', JSON.stringify(results, null, 2), 'utf8');