import { url, compYear } from './settings.mjs';
import { writeFileSync } from 'node:fs';

// Generates `tuid-entry-map.json`, a JSON mapping of IFDB TUIDs to IFComp entry ID numbers.
// It assumes that each IFDB listing includes a ballot link which includes the entry ID.
// (This is the same way the IFComp `populate_ifdb_ids.pl` script works.)

const ifcompGames = await fetch(`https://ifcomp.org/comp/${compYear}/json`).then(r => r.json());

if (ifcompGames[0].ifdb_id) {
    // IFComp has run the `populate_ifdb_ids.pl` script, so we can use their mapping
    const tuidEntryMap = ifcompGames.map(g => [g.ifdb_id, g.id]);
    writeFileSync('tuid-entry-map.json', JSON.stringify(tuidEntryMap, null, 2), 'utf8');
} else {
    const {competitions} = await fetch(`${url}/search?comp&searchfor=${compYear}%20series%3AAnnual+Interactive+Fiction+Competition&json`).then(r => r.json());
    const compTuid = competitions[0].tuid;
    console.log({compTuid});
    const {games} = await fetch(`${url}/search?searchfor=competitionid:${compTuid}&pg=all&sortby=ttl&json`).then(r => r.json());
    const tuids = games.map(g => g.tuid);
    console.log({tuids});
    const tuidEntryMap = Object.fromEntries(await Promise.all(tuids.map(async tuid => {
        try {
            const json = await fetch(`${url}/viewgame?id=${tuid}&json`).then(r => r.json())
            for (const link of json.ifdb?.downloads?.links ?? []) {
                const match = /^https:\/\/ifcomp.org\/ballot\/?#entry-(\d+)$/.exec(link.url);
                if (match) {
                    return [tuid, Number(match[1])];
                }
            }
            throw new Error("missing ballot link: " + JSON.stringify(json, null, 2));
        } catch (cause) {
            throw new Error(`Couldn't find ballot link for ${tuid}`, {cause});
        }
    })))
    writeFileSync('tuid-entry-map.json', JSON.stringify(tuidEntryMap, null, 2), 'utf8');
}
