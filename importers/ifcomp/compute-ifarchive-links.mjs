import { compYear } from './settings.mjs';
import { readFileSync, writeFileSync } from 'node:fs';
import { XMLParser } from 'fast-xml-parser';

// TODO we shouldn't need microdata-tuids; we should just get TUID platforms via https://ifcomp.org/comp/YYYY/json
// https://github.com/iftechfoundation/ifcomp/pull/436

const microdata = JSON.parse(readFileSync('microdata-tuids.json', 'utf8')).sort((a, b) => a.name.localeCompare(b.name));

const microdataByTuid = Object.fromEntries(microdata.map(m => [m.tuid, m]));

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

const links = xml.ifarchive.file.filter(f => f.path.startsWith(`if-archive/games/competition${compYear}`) && fileTuid(f));

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
    'Web-based': ['html'],
    'ChoiceScript': ['html'],
    'Ink': ['html'],
    'Texture': ['html'],
    'Twine': ['html'],
    'Adventuron': ['html'],
    'Z-code': ['zblorb', 'z5', 'z8'],
    'Glulx': ['gblorb', 'ulx'],
    'TADS': ['t3'],
    'Windows Executable': ['exe'],
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
            const {name: title, gamePlatform} = microdataByTuid[tuid];
            if (!gamePlatform) {
                console.log(`No gamePlatform for ${path} ${tuid} "${title}"`);
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