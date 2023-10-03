This importer is designed to grab the game list from an Itch.io game jam and upload the games to IFDB.

# How to use the scripts

1. Run `npm install` to install dependencies.
2. Copy `settings.mjs.template` to `settings.mjs`, putting in your username, password, jamEntriesUrl, tagName, and the compStartDate. (If you want to test the scripts against a local IFDB dev environment, also change the url to `http://localhost:8080`.)
3. Run `node fetch-entries-json.mjs`. This will record the entries data in `entries.json`.
4. Run `node process-cover-art.mjs` to download all of the cover art in the `cover-art` directory.
5. Run `node submit-games.mjs` to submit all of the game listings. (Note that when testing in a dev environment, uploaded images will not appear in the web UI.)

# List of the scripts

1. `settings.mjs`: All of the other scripts depend on this script. Copy `settings.mjs.template` to `settings.mjs`, putting in your username, password, jamEntriesUrl, tagName, and the compStartDate. (If you want to test the scripts against a local IFDB dev environment, also change the url to `http://localhost:8080`.)
1. `fetch-entries-json.mjs`: According to [this Itch forum post](https://itch.io/t/1487695/solved-any-api-to-fetch-jam-entries), Itch jams have an unofficial JSON URL at `https://itch.io/jam/ID/entries.json`. This script goes to the `entries.json` URL, processes the results, and stores the results in `entries.json`.
1. `process-cover-art.mjs`: This script reads `entries.json` and downloads the cover art for all games. Some games have art too large for IFDB's 256KiB limit, so we convert PNGs to JPG, and try lowering the quality bit by bit until the image is small enough to submit. We deposit the art in the `cover-art` directory, and store a record of our results in `cover-art.json`.
1. `submit-games.mjs`: This script reads `entries.json` and `cover-art.json`, and uses the IFDB [putific API](https://ifdb.org/api/putific) to create results for all games, including a "Play Online" link for all games that list their platform as "web."
