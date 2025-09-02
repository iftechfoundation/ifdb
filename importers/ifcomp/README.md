This importer is designed to grab the game list from https://ifcomp.org/ballot and upload the games to IFDB.

# How to use the scripts

## Phase 1: Create IFDB listings for each game on the ballot

(You can skip this phase if someone has already created IFDB listings for each game.)

1. Run `npm install` to install dependencies.
2. Copy `settings.mjs.template` to `settings.mjs`, putting in your username, password, and the compStartDate. (If you want to test the scripts against a local IFDB dev environment, also change the url to `http://localhost:8080`.)
3. Run `node extract-microdata.mjs` to record the ballot data in `microdata.json`.
4. Run `node process-cover-art.mjs` to download all of the cover art in the `cover-art` directory.
5. Run `node submit-games.mjs` to submit all of the game listings. (Note that when testing in a dev environment, uploaded images will not appear in the web UI.)
6. In the admin UI, run the duplicate detector and resolve any duplicates. https://ifdb.org/adminops?duplicateDetector=&days=2
7. Run `node merge-tuids.mjs` to record the IFDB TUIDs in `microdata-tuids.json`. (Read the log and look for "too many matches" errors, indicating any duplicate entries we might have missed.)
8. Run `node tag-games.mjs` to tag each game with the "IFComp YYYY" tag.
9. In the web UI, create a new competition, "Add by Tag". It will prompt you for a tag name, and populate the main division with the list of games.

## Phase 2: Add IF Archive external links for each game on the ballot.

Once the voting period has closed, authors can no longer update their games.

At that point, IF Archive will extract all games, and assign each game file TUID metadata.

In addition, hopefully the IFComp team will have run the `populate_ifdb_ids.pl` script to populate IFDB IDs in IFComp's database.

1. Run `npm install` to install dependencies.
2. Copy `settings.mjs.template` to `settings.mjs`, putting in your username, password, and the compStartDate. (If you want to test the scripts against a local IFDB dev environment, also change the url to `http://localhost:8080`.)
3. Run `node compute-ifarchive-links.mjs` to record the file names in `external-links.json`. (If IFComp hasn't run the populate script, this will fail. You can either run `node compute-tuid-entry-map.mjs` to workaround this, or nudge the IFComp team to run the script.)
4. Run `node submit-external-links.mjs` to edit each IFDB listing, adding the links we computed.

## Phase 3: Remove ballot links

The ballot links stop working after the competition ends.

1. Run `npm install` to install dependencies.
2. Copy `settings.mjs.template` to `settings.mjs`, putting in your username, password, and the compStartDate. (If you want to test the scripts against a local IFDB dev environment, also change the url to `http://localhost:8080`.)
3. Run `node remove-ballot-links.mjs` to remove all of the ballot links. The IF Archive links will be the only remaining "Play Online" options for IFComp games.

# List of the scripts

1. `settings.mjs`: All of the other scripts depend on this script. Copy `settings.mjs.template` to `settings.mjs`, putting in your username, password, and the compStartDate. (If you want to test the scripts against a local IFDB dev environment, also change the url to `http://localhost:8080`.)
1. `extract-microdata.mjs`: The IFComp Ballot page is populated with https://schema.org/VideoGame microdata. This script downloads the ballot, reads the microdata, and stores it in a more convenient JSON format, in `microdata.json`.
1. `process-cover-art.mjs`: This script reads `microdata.json` and downloads the cover art for all games. Some games have art too large for IFDB's 256KiB limit, so we convert PNGs to JPG, and try lowering the quality bit by bit until the image is small enough to submit. We deposit the art in the `cover-art` directory, and store a record of our results in `cover-art.json`.
1. `submit-games.mjs`: This script reads `microdata.json` and `cover-art.json`, and uses the IFDB [putific API](https://ifdb.org/api/putific) to create results for all games.

    Initially, we'll create IFDB external links pointing to the IFComp ballot, ensuring that players play the latest version of each game.
1. `merge-tuids.mjs`: Rather than assuming that `submit-games.mjs` was run, we search IFDB for games published in the current year that match the title of the given IFComp game; this gives us the IFDB "TUID" ID of each game in IFComp. This generates `microdata-tuids.json` from `microdata.json`.
1. `tag-games.mjs`: Tag all games in `microdata-tuids.json` with the "IFComp YYYY" tag.
1. `compute-ifarchive-links.mjs`: Computes the correct external link (including the game file in the download ZIP) based on IF Archive's `Master-Index.xml` file and the IFComp JSON API. This generates `external-links.json`.
1. `compute-tuid-entry-map.mjs`: Generates `tuid-entry-map.json`, a JSON mapping of IFDB TUIDs to IFComp entry ID numbers. It assumes that each IFDB listing includes a ballot link which includes the entry ID. (This is the same way the IFComp `populate_ifdb_ids.pl` script works.)
1. `submit-external-links.mjs`: Automatically submits IF Archive download links for all IFComp games, based on `external-links.json`.
1. `remove-ballot-links.mjs`: Removes `https://ifcomp.org/ballot` links from this year's competition entries. (The ballot links stop working after the competition ends.)
