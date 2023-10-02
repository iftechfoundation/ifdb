//https://itch.io/t/1487695/solved-any-api-to-fetch-jam-entries

const url = process.argv[2];

if (!url) throw new Error("URL required");
const text = await fetch(url).then(r => r.text());
const match = /"entries_url":"\\\/jam\\\/(\d+)\\\/entries.json"/.exec(text);
if (!match) {
    throw new Error("Couldn't scrape entries JSON");
}
const entries_url = `https://itch.io/jam/${match[1]}/entries.json`;

const entries = await fetch(entries_url).then(r => r.json());

console.log(JSON.stringify(entries, null, 2));