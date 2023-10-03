import {readFile, writeFile, mkdir} from 'fs/promises';
import sharp from 'sharp';
const file = process.argv[2] ?? 'entries.json';
const games = JSON.parse(await readFile(file, 'utf8'));
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

await Promise.all(games.map(async ({cover, id}) => {
    if (!cover) return;
    const response = await fetch(cover);
    if (!response.ok) {
        throw new Error(`Error ${response.status} ${response.statusText} downloading ${cover}: ${await response.text()}`);
    }
    const contentType = response.headers.get('content-type');
    let extension = extensions[contentType];
    if (!extension) {
        throw new Error(`Couldn't handle ${contentType} image for ${cover}`);
    }
    let fileName = `cover-art/${id}.${extension}`;
    let image = Buffer.from(await response.arrayBuffer());
    if (image.length > 250000) {
        const originalLength = image.length;
        if (extension === 'jpg') {
            image = await resizeJpeg(image);
        } else {
            extension = 'jpg';
            fileName = `cover-art/${id}.${extension}`;
            image = await sharp(image).jpeg({quality: 100}).toBuffer();
            image = await resizeJpeg(image);
        }
        console.log(`resizing ${fileName} from ${originalLength} to ${image.length}`);
    }
    await writeFile(fileName, image);
    coverFiles[id] = fileName;
    console.log(fileName);
}));
await writeFile("cover-art.json", JSON.stringify(coverFiles, null, 2), 'utf8');