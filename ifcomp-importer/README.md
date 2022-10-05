This importer is designed to grab the game list from https://ifcomp.org/ballot and upload the games to IFDB.

1. `extract-microdata.mjs`: The IFComp Ballot page is populated with https://schema.org/VideoGame microdata. This script downloads the ballot, reads the microdata, and stores it in a more convenient JSON format, in `microdata.json`.
2. `process-cover-art.mjs`: This script reads `microdata.json` and downloads the cover art for all games. Some games have art too large for IFDB's 256KiB limit, so we convert PNGs to JPG, and try lowering the quality bit by bit until the image is small enough to submit. We deposit the art in the `cover-art` directory, and store a record of our results in `cover-art.json`.
3. `submit-games.mjs`: This script reads `microdata.json` and `cover-art.json`, and uses the IFDB [putific API](https://ifdb.org/api/putific) to create results for all games.

Initially, we'll create the IFDB entries based on the ballot alone; it will take a few days for IF Archive to accept and process the "big zip" of all competition entries. Once that ZIP is available on ifarchive.org, we can compute and set download links.

TODO

* parse the big zip; compute download links for each game in the competition
* compute IFIDs for games in the competition
* support editing someone else's game (latestVersion parameter)
* add download links for all games (if they're not already there)
* create an API to tag all games with "IFComp YYYY" tag, and use it when creating the games
* API to manage competition entries
* script to inject IFIDs for games that can detect them
