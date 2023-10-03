import { jamEntriesUrl } from './settings.mjs';
import { writeFile } from 'node:fs/promises';

//https://itch.io/t/1487695/solved-any-api-to-fetch-jam-entries

if (!/\/entries/.test(jamEntriesUrl)) throw new Error("URL should end with /entries");
const text = await fetch(jamEntriesUrl).then(r => r.text());
const match = /"entries_url":"\\\/jam\\\/(\d+)\\\/entries.json"/.exec(text);
if (!match) {
    throw new Error("Couldn't scrape entries JSON");
}
const entries_url = `https://itch.io/jam/${match[1]}/entries.json`;

const {jam_games} = await fetch(entries_url).then(r => r.json());

console.log(JSON.stringify(jam_games, null, 2));

const results = jam_games.map(({ game: { id, title, url, short_text, cover, user: { name: author }, platforms = [] } }) => (
    { title, url, short_text, author, cover, id, play_online: platforms.includes('web') }
));

await writeFile('entries.json', JSON.stringify(results, null, 2), 'utf8');
