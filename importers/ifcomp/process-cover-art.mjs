import {readFile, writeFile, mkdir} from 'fs/promises';
import sharp from 'sharp';
const games = JSON.parse(await readFile('microdata.json', 'utf8'));
const urls = games.filter(game => game.thumbnailArtUrl).map(game => game.thumbnailArtUrl);
try {
    await mkdir('cover-art');
} catch (e) {
    if (e.code !== 'EEXIST') throw e;
}
const extensions = {
    'image/jpeg': 'jpg',
    'image/png': 'png',
}
const coverFiles = {};

const maxImageSize = 262144;

async function resizeJpeg(inputBuffer) {
    const originalImage = sharp(inputBuffer);
    let outputBuffer = inputBuffer;
    let quality = 100;
    while (outputBuffer.length > maxImageSize) {
        quality -= 20;
        console.log({quality});
        outputBuffer = await originalImage.jpeg({quality}).toBuffer();
    }
    return outputBuffer;
}

await Promise.all(urls.map(async (url) => {
    const [, entryId] = /\/([^\/]+?)\/cover$/.exec(url);
    if (!entryId) throw new Error("Couldn't parse entryId from url: " + url);
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`Error ${response.status} ${response.statusText} downloading ${url}: ${await response.text()}`);
    }
    const contentType = response.headers.get('content-type');
    let extension = extensions[contentType];
    if (!extension) {
        throw new Error(`Couldn't handle ${contentType} image for ${url}`);
    }
    let fileName = `cover-art/${entryId}.${extension}`;
    let image = Buffer.from(await response.arrayBuffer());
    if (image.length > 250000) {
        const originalLength = image.length;
        if (extension === 'jpg') {
            image = await resizeJpeg(image);
        } else {
            extension = 'jpg';
            fileName = `cover-art/${entryId}.${extension}`;
            image = await sharp(image).jpeg({quality: 100}).toBuffer();
            image = await resizeJpeg(image);
        }
        console.log(`resizing ${fileName} from ${originalLength} to ${image.length}`);
    }
    await writeFile(fileName, image);
    coverFiles[entryId] = fileName;
    console.log(fileName);
}));
await writeFile("cover-art.json", JSON.stringify(coverFiles, null, 2), 'utf8');