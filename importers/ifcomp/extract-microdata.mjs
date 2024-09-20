import {writeFile} from 'fs/promises';
import microdata from 'microdata-node';

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
    output.authors = properties.author?.map(item => item.properties.name[0]);
    const image = properties.image?.[0];
    output.fullCoverArtUrl = image?.properties?.contentUrl?.[0];
    output.thumbnailArtUrl = image?.properties?.thumbnailUrl?.[0];
    return output;
})
await writeFile('microdata.json', JSON.stringify(games, null, 2), 'utf8');
