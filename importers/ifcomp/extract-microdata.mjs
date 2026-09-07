import {writeFile} from 'fs/promises';
import microdata from 'microdata-node';

// Drop co-writer entries whose exact name already appears in an earlier
// author credit (e.g. primary "A and B" plus co-writer "B").
function dedupeAuthors(authors) {
    if (!authors?.length) return authors ?? [];

    const seen = new Set();
    const result = [];

    for (const author of authors) {
        if (!author) continue;

        const parts = author
            .split(/\s*(?:,|\band\b|&)\s*/i)
            .map(s => s.trim())
            .filter(n => n);
        if (parts.length > 0 && parts.every(p => seen.has(p))) {
            continue;
        }

        result.push(author);
        for (const part of parts) {
            seen.add(part);
        }
    }

    return result;
}

const response = await fetch('https://ifcomp.org/ballot/?alphabetize=1');
const text = await response.text();

const {items} = microdata.toJson(text);
const games = items.map(item => {
    const {properties} = item;
    const output = {};
    const simpleFields = ['name', 'alternateName', 'description', 'gamePlatform', 'genre', 'size', 'interactivityType', 'downloadUrl', 'url'];
    for (const simpleField of simpleFields) {
        output[simpleField] = properties[simpleField]?.[0];
    }
    output.description = output.description
        .replace(/\n +\n/g, '\n')
        .replace(/\n\n\n\n/g, "\n\n")
        .replace(/\n\n\n +Content warning/, "\n\nContent warning")
        .trim();
    output.authors = dedupeAuthors(
        properties.author?.map(item => item.properties.name[0])
    );
    const image = properties.image?.[0];
    output.fullCoverArtUrl = image?.properties?.contentUrl?.[0];
    output.thumbnailArtUrl = image?.properties?.thumbnailUrl?.[0];
    return output;
})
await writeFile('microdata.json', JSON.stringify(games, null, 2), 'utf8');
