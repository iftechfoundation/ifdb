// run this in JS Console when creating the competition

const tag = prompt("Tag name?");

const url = '/search?' + new URLSearchParams({
    searchbar: 'tag:' + tag,
    xml: 1,
    pg: 'all',
}).toString();

const xml = await fetch(url).then(r => r.text());

const results = [...new DOMParser().parseFromString(xml, "application/xml").querySelectorAll('game')].map(game => ({
    id: game.querySelector('tuid').textContent,
    title: game.querySelector('title').textContent,
    author: game.querySelector('author').textContent,
}))

for (const { id, title, author } of results) {
    const i = window[divModel0.vals].length;
    gfInsRow('divModel0', i);
    document.getElementById('gameplace0_' + i).value = "Entrant";
    gameSearchPopupSetID(id, title, author);
}