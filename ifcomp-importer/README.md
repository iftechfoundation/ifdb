This importer is designed to grab the game list from https://ifcomp.org/ballot and upload the games to IFDB.

1. `extract-microdata.mjs`: The IFComp Ballot page is populated with https://schema.org/VideoGame microdata. This script downloads the ballot, reads the microdata, and stores it in a more convenient JSON format, in `microdata.json`.
2. `process-cover-art.mjs`: This script reads `microdata.json` and downloads the cover art for all games. Some games have art too large for IFDB's 256KiB limit, so we convert PNGs to JPG, and try lowering the quality bit by bit until the image is small enough to submit. We deposit the art in the `cover-art` directory, and store a record of our results in `cover-art.json`.
3. `submit-games.mjs`: This script reads `microdata.json` and `cover-art.json`, and uses the IFDB [putific API](https://ifdb.org/api/putific) to create results for all games.

    Initially, we'll create the IFDB entries based on the ballot alone; it will take a few days for IF Archive to accept and process the "big zip" of all competition entries. Once that ZIP is available on ifarchive.org, we can compute and set download links.
4. `compute-download-links.mjs`: Computes the correct download link (including the game file in the download ZIP) using the big zip as input.
5. `merge-tuids.mjs`: Rather than assuming that `submit-games.mjs` was run, we search IFDB for games published in the current year that match the title of the given IFComp game; this gives us the IFDB "TUID" ID of each game in IFComp.


TODO

* add API for viewing/editing download links https://github.com/iftechfoundation/ifdb-suggestion-tracker/issues/365
* add download links for all games (if they're not already there)

* create an API to tag games https://github.com/iftechfoundation/ifdb-suggestion-tracker/issues/366
* Tag games with "IFComp YYYY" tag
* Allow bulk-adding games to competitions https://github.com/iftechfoundation/ifdb-suggestion-tracker/issues/367

* compute IFIDs for games that are missing them
* inject IFIDs when we find them
