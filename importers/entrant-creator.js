// run this in JS Console when creating the competition

const tag = prompt("Search?");

const url = '/search?' + new URLSearchParams({
    searchbar: tag,
    xml: 1,
    pg: 'all',
    sortby: 'ttl',
}).toString();

const xml = await fetch(url).then(r => r.text());

const results = [...new DOMParser().parseFromString(xml, "application/xml").querySelectorAll('game')].map(game => ({
    id: game.querySelector('tuid').textContent,
    title: game.querySelector('title').textContent,
    author: game.querySelector('author').textContent,
}))

const divId = prompt("Div ID?");


for (const { id, title, author } of results) {
    const i = window[window[`divModel${divId}`].vals].length;
    gfInsRow(`divModel${divId}`, i);
    document.getElementById(`gameplace${divId}_${i}`).value = "Entrant";
    gameSearchPopupSetID(id, title, author);
}