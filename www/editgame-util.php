<?php

include_once "pagetpl.php";

// --------------------------------------------------------------------------
//
// Initialize the list of download link file formats
//
global $linkfmts, $fmtmap;
function init_link_formats($db)
{
    global $linkfmts, $fmtmap;

    // if the format map is already set up, we don't need to do this again
    if ($fmtmap)
        return;

    // fetch the list of link formats
    $result = mysql_query(
        "select id, fmtname, fmtclass, extension, externid
        from filetypes
        order by fmtname", $db);
    $rows = mysql_num_rows($result);
    $linkfmts = [];
    for ($i = 0 ; $i < $rows ; $i++)
        $linkfmts[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // build a map for the formats keyed by format ID
    $fmtmap = [];
    foreach ($linkfmts as $lf)
        $fmtmap[$lf['id']] = [$lf['fmtname'], $lf['fmtclass']];
}


// --------------------------------------------------------------------------
//
// The form and database field descriptors.  These define the database
// fields, how they appear on the form, and how we map between the form
// representation and the database representation.
//

// field datatypes
define("TypeInt", 1);
define("TypeString", 2);
define("TypeDate", 3);
define("TypeImage", 4);
define("TypeDateOrYear", 5);


// field descriptors
global $fields;
global $nonce;
$fields = [
    ["Title", "title", 60, "Required", null, TypeString],
    ["Author(s)", "eAuthor", 60,
          "Required; enter Anonymous if the work is unattributed.
           - <a href=\"needjs\">
            <script type='text/javascript' nonce='$nonce'>
                document.currentScript.parentElement.addEventListener('click', function(event) {
                    event.preventDefault();
                    aplOpen('eAuthor', 'Author');
                });
            </script>
            Link to author's profile</a> - "
          . helpWinLink("help-author", "Tips"),
          null, TypeString],
    ["IFID(s)", "ifids", 60,
          "To enter multiple IFIDs, use commas to separate them - "
          . helpWinLink("help-ifid", "What's an IFID?"),
          null, TypeString],
    ["Cover Art", "coverart", 0, null, null, TypeImage],
    ["First&nbsp;Publication&nbsp;Date", "published", 15,
          "Format as dd-Mon-yyyy (example: 12-Jul-2005), or
          just enter the year if you don't know the exact date",
          null, TypeDateOrYear],
    ["Current Version", "version", 10, null, null, TypeString],
    ["License Type", "license", 30,
          "Free, Shareware, Public Domain, Commercial ... "
          . helpWinLink("help-license-type", "Help with License Types"),
          ["Freeware", "Shareware", "Public Domain",
                "Commercial", "Commercial (Out of Print)",
                "GPL", "Apache", "BSD", "Creative Commons"], TypeString],
    ["Development System", "system", 30, null,
          ["None", "Custom",
                "ADRIFT",
                "Adventuron",
                "ChoiceScript",
                "ChooseYourStory",
                "Dialog",
                "Inform 6", "Inform 7",
                "Ink",
                "Quest", "Quest 5",
                "Ren'Py",
                "TADS 2", "TADS 3",
                "Texture",
                "Twine",
                "Unity",
                "ZIL"],
          TypeString],
    ["Language", "language", 20,
          "The spoken language of the story's text.  Use an <a
           target=\"_blank\" href=\"http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes\">
           ISO-639</a> code (2 or 3 letters: en for English, fr for French,
           de for German, etc); <i>optionally</i>, add a hyphen and <a
           target=\"_blank\" href=\"http://en.wikipedia.org/wiki/ISO_3166\">
           ISO-3166</a> country code (example: en-US).",
          ["en", "es", "it", "fr", "de", "nl", "ru", "sv"],
          TypeString],
    ["Description", "desc", 60,
          "An overview of the game, like the blurb on the back of a book: "
          . "plot summary, writing style, technical "
          . "aspects, awards, etc. - "
          . helpWinLink("help-formatting", "Formatting Hints") . " - "
          . helpWinLink("gamelink", "Add a game link"),
          null, TypeString],
    ["Series Name", "seriesname", 50,
          "If this game is part of a series, enter the series name; e.g.,
           \"the Last Days on Mars Trilogy,\" \"the Enthraller series\"",
          null, TypeString],
    ["Episode Number", "seriesnumber", 10,
          "If this game is part of a series, the episode number of this
          game within the series", null, TypeString],
    ["Genre", "genre", 50,
          "We recommend the <a target=\"_blank\"
            href=\"https://web.archive.org/web/20110514231056/https://www.wurb.com/if/genre\">Baf's
            Guide</a> list, but feel free to combine genres or
            go off-list as needed",
          ["Abuses", "Adaptation", "Children's",
                "Collegiate", "Educational", "Espionage",
                "Fantasy", "Historical", "Horror", "Humor",
                "Mystery", "Pornographic", "RPG", "Religious",
                "Romance", "Science Fiction", "Seasonal",
                "Slice of life", "Superhero", "Surreal",
                "Travel", "Western"], TypeString],
    ["Forgiveness Rating", "forgiveness", 30,
          "On the Zarfian forgiveness scale - "
          . helpWinLink("help-forgiveness", "Details"),
          ["Merciful", "Polite", "Tough", "Nasty", "Cruel"], TypeString],
    ["<i>Baf's Guide</i> ID", "bafsid", 10,
          "Integer identifier assigned by Baf's Guide - "
          . helpWinLink("help-bafs", "How do I find this?"),
          null, TypeInt],
    ["Web site", "website", 60,
          "Full URL to the author's web site for the game
          (http://mygame.com/...)", null, TypeString],
    ["Download Notes", "downloadnotes", 80,
          "Brief notes on downloads; if the game isn't available,
           explain why (commercial status, etc.) - "
          . helpWinLink("help-formatting", "Formatting"),
          null, TypeString]
];


// --------------------------------------------------------------------------
// Retrieve the current game record from the database.  Takes a game ID
// as input; returns an array of (game_record, error_message), where the
// game record is an associative array of the values keyed by column name.
//
function loadGameRecord($db, $id)
{
    // query the main game record
    $result = mysqli_execute_query($db,
        "select
            title, author, authorExt,
            date_format(published,
               if (month(published) = 1 and day(published) = 1
                   and hour(published) = 0,
               '%Y', '%d-%b-%Y')) published,
            version, license, system, `desc`, genre, language,
            seriesname, seriesnumber, forgiveness,
            bafsid, website, editedby, moddate, pagevsn,
            coverart, downloadnotes
         from games
         where id = ?", [$id]);
    if (mysql_num_rows($result) == 0)
        return [
            false,
            "This game was not found in the database. If you reached
             this page through a link, you might want to notify the
             maintainer of the referencing site of the broken link.",
            "the specified game was not found in the database"];

    // retrieve the result
    $rec = mysql_fetch_array($result, MYSQL_ASSOC);

    // if the authorExt field is set, use it in place of the basic author
    // field - the author value is derived if there's an extended author
    // value
    $rec['eAuthor'] = $rec[$rec['authorExt'] ? 'authorExt' : 'author'];

    // build the list of downloads
    $result = mysqli_execute_query($db,
        "select
           url, title, `desc`, attrs, fmtid, osid, osvsn,
           compression, compressedprimary, displayorder
        from gamelinks
        where gameid = ?
        order by displayorder", [$id]);
    $rows = mysql_num_rows($result);
    $links = [];
    for ($i = 0 ; $i < $rows ; $i++)
        $links[$i] = mysql_fetch_array($result, MYSQL_ASSOC);

    // put the links in the main record
    $rec['links'] = $links;

    // build the list of IFIDs
    $result = mysqli_execute_query($db, "select ifid from ifids
        where gameid = ?", [$id]);
    $rows = mysql_num_rows($result);
    for ($i = 0, $ifids = [] ; $i < $rows ; $i++)
        $ifids[] = mysql_result($result, $i, "ifid");

    // put the ifids in the main record - under the 'ifids' key, store
    // it in our comma-delimited string format; store the original array
    // under the 'ifid array' key
    $rec['ifids'] = implode(",", $ifids);
    $rec['ifid array'] = $ifids;

    // build the list of external review links
    $result = mysqli_execute_query($db,
        "select
           extreviews.gameid as gameid, extreviews.url as url,
           extreviews.sourcename as sourcename,
           extreviews.sourceurl as sourceurl,
           reviews.rating as rating, reviews.summary as headline,
           reviews.review as summary
         from
           extreviews
           left outer join reviews on extreviews.reviewid = reviews.id
         where extreviews.gameid = ?
         order by extreviews.displayorder", [$id]);
    for ($extrev = [], $i = 0 ; $i < mysql_num_rows($result) ; $i++)
        $extrev[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // put the reviews in the main record under the 'reviews' key
    $rec['extreviews'] = $extrev;

    // query the list of genealogical cross-references
    $result = mysqli_execute_query($db,
        "select
           gamexrefs.reftype as reftype,
           gamexrefs.toid as toID,
           games.title as toTitle
         from
           gamexrefs
           join games on games.id = gamexrefs.toID
         where
           gamexrefs.fromid = ?
         order by
           gamexrefs.displayorder", [$id]);

    // fetch them
    for ($xrefs = [], $i = 0 ; $i < mysql_num_rows($result) ; $i++)
        $xrefs[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // put the list in the main record under the 'xrefs' key
    $rec['xrefs'] = $xrefs;

    // Store our local version of the cover art information: it's either
    // "none" or "old" for the editing record, depending on whether we
    // had any cover art.  Save the actual cover art data under the non-db
    // key 'orig:coverart'.
    $art = $rec['coverart'];
    $rec['orig:coverart'] = $art;
    $rec['coverart'] = (is_null($art) || strlen($art) == 0 ? "none" : "old");

    // return the result
    return [$rec, false];
}


// --------------------------------------------------------------------------
//
// Save updates
//
//   $db = the database connection
//   $adminPriv = true if we have administrator privileges (if this is
//        set, we'll include mysql error messages in the message output
//        for any database errors that occur, for debugging purposes;
//        we omit the mysql error for ordinary users because (a) it's
//        internal technical data that's not helpful to most users, and
//        (b) it could expose vulnerabilities to hackers by exposing
//        bits of our sql queries and database table structure)
//   $id = the game ID
//   $rec = the existing database record we're updating; we update this
//        with the request record if we're going to redisplay the form
//   $req = the request data with the posted new field values
//   $saveErrMsg is filled in with a suitable error message to display
//        in the form, if an error occurs
//   $saveErrDetail is filled in with an array of error details, with
//        error messages to display with the individual fields in the
//        the form
//
function saveUpdates($db, $adminPriv, $apiMode,
                     $id, &$rec, &$req,
                     &$saveErrMsg, &$saveErrCode, &$errDetail)
{
    global $fields, $fmtmap;

    // no errors yet
    $errDetail = [];

    // initialize the link format map, if we haven't already done so
    init_link_formats($db);

    // start a transaction for the update
    $progress = "BTX070A";
    $result = mysql_query("set autocommit=0", $db);
    if ($result)
        $result = mysql_query("start transaction", $db);

    // lock needed tables while we're working
    if ($result) {
        $progress = "LKT070A.5";
        $result = mysql_query(
            "lock tables games write, gamefwds read,
            ifids write, gamelinks write, games_history write,
            extreviews write, reviews write, gamexrefs write,
            gameprofilelinks write, users read, gamexreftypes read", $db);
    }

    // fetch the latest version of the game record, in case it changed
    // since we started working - we have the tables locked now, so there's
    // no change of a race condition from this point forward
    if ($id != "new")
        list($rec, $errMsg) = loadGameRecord($db, $id);

    // strip whitespace from the IFID field, and uppercase it
    $req['ifids'] = strtoupper(str_replace(
        [" ", "\t"], ["", ""], $req['ifids']));

    // If there are any profile links in the author name, we actually have
    // an EXTENDED author value.  In this case, set the extended author to
    // the field contents, and derive the plain author from the extended
    // value by removing the profile links.  Otherwise, the field value
    // goes into the basic author slot, and the extended author is blank.
    $au = $req["eAuthor"];
    if (preg_match("/\{[a-z0-9]+\}/i", $au)) {
        // we have an extended author value - save it in the authorExt
        // field, and derive the basic author by stripping out the
        // profile links
        $req["authorExt"] = $au;
        $req["author"] = preg_replace("/\s*\{[a-z0-9]+\}/i", "", $au);
    } else {
        // we have a basic author value
        $req["author"] = $au;
        $req["authorExt"] = "";
    }

    // calculate the list of changes
    list ($changesOld, $changesNew) = calcDeltas($rec, $req);

    // get the database column definitions
    $result = mysql_query("describe games", $db);
    for ($i = 0, $dbcols = [] ; $i < mysql_num_rows($result) ; $i++) {
        $colrow = mysql_fetch_array($result, MYSQL_ASSOC);
        $dbcols[$colrow['Field']] = $colrow;
    }

    $result = mysql_query("describe ifids", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $colrow = mysql_fetch_array($result, MYSQL_ASSOC);
        if ($colrow['Field'] == "ifid")
            $dbcols['ifids'] = $colrow;
    }

    $result = mysql_query("describe gamelinks", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $colrow = mysql_fetch_array($result, MYSQL_ASSOC);
        $dbcols['links.' . $colrow['Field']] = $colrow;
    }

    $result = mysql_query("describe extreviews", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $colrow = mysql_fetch_array($result, MYSQL_ASSOC);
        $dbcols['extreviews.' . $colrow['Field']] = $colrow;
    }

    $result = mysql_query("describe reviews", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $colrow = mysql_fetch_array($result, MYSQL_ASSOC);
        $dbcols['reviews.' . $colrow['Field']] = $colrow;
    }

    $result = mysql_query("describe gamexrefs", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $colrow = mysql_fetch_array($result, MYSQL_ASSOC);
        $dbcols['xrefs.' . $colrow['Field']] = $colrow;
    }

    // get the maximum data length for each CHAR or VARCHAR column
    $dbLens = [];
    foreach ($dbcols as $col => $attrs) {
        // if the type column is of the form "CHAR(len)" or "VARCHAR(len),
        // pick out the length
        if (preg_match("/(char|varchar)\(([0-9]+)\)/i", $attrs['Type'], $m))
            $dbLens[$col] = $m[2];
    }

    // validate the changes
    foreach ($changesNew as $key => $val) {
        // check to make sure it doesn't exceed the column length
        $dblen = (isset($dbLens[$key]) ? $dbLens[$key] : 0);
        if ($dblen && gettype($val) != "array" && strlen($val) > $dblen) {
            $errDetail[$key][] = "This value is too long (the maximum "
                                 . "length is $dblen character).";
        }

        // validate certain entries
        switch ($key) {
        case "author":
            // This field isn't allowed to be empty.  If the "external
            // author" version is non-empty, it's because they've only
            // supplied the profile link {TUID} codes, sans author names.
            if (is_null($val) || $val == "") {
                if ($changesNew["authorExt"] != "") {
                    $errDetail["eAuthor"][] =
                        "Please be sure to enter the author's display name "
                        . "just before each profile link.  Use the format "
                        . "<b>Author Dent {a10x00139ke9041j}</b>.";
                } else {
                    // the whole field appears to be empty
                    $errDetail["eAuthor"][] = "This field is required.";
                }
            }
            break;

        case "title":
            // these fields are not allowed to be empty
            if (is_null($val) || $val == "")
                $errDetail[$key][] = "This field is required.";
            break;

        case "published":
            // make sure there's a valid date here
            if (trim($val) != "" && parseDateVal($val) == false)
                $errDetail["published"][] =
                    "The date \"$val\" isn't formatted in a way that
                    the database can understand. Format as DD-Mon-YYYY
                    (e.g., 10-Apr-2005), or just as a four-digit year.";
            break;

        case "ifids":
            // validate for data length
            foreach ($changesNew['ifids'] as $ifid) {
                // validate for column length
                if (strlen($ifid) > $dblen) {
                    $errDetail['ifids'][] =
                        "The IFID " . htmlspecialcharx($ifid) . " is too "
                        . "long - IFIDs are limited to $dblen characters.";
                }

                // make sure this IFIDs isn't used by any other game
                $result = mysqli_execute_query($db, "select gameid from ifids
                    where lower_ifid = ?", [strtolower($ifid)]);
                if ($result && mysql_num_rows($result) > 0) {
                    $otherid = mysql_result($result, 0, "gameid");
                    if ($id != $otherid) {
                        // found a duplicate - flag the error; get the
                        // title of the conflicting game for better reporting
                        $result = mysql_query("select title from games
                            where id = '$otherid'", $db);
                        if ($result && mysql_num_rows($result) > 0) {
                            $otherTitle = mysql_result($result, 0, "title");
                            $otherTitle = "<i>" . htmlspecialcharx($otherTitle)
                                          . "</i>";
                        } else {
                            $otherTitle = "View Game";
                        }

                        $errDetail['ifids'][] =
                            "The IFID " . htmlspecialcharx($ifid)
                             . " is already assigned to another game:
                              <a target=\"_blank\"
                                href=\"viewgame?id=$otherid\">$otherTitle</a>.
                              IFIDs must be unique.";
                    }
                }
            }
            break;
        }
    }

    // if we're updating the title, set the sorting title
    if (isset($changesNew["title"]))
        $changesNew["sort_title"] =
            strtoupper(getSortingTitle($changesNew["title"]));

    // if we're updating the author, derived the author sorting key
    if (isset($changesNew["author"])) {
        $changesNew["sort_author"] =
            strtoupper(getSortingPersonalNameList($changesNew["author"]));
    }

    // check for blank or invalid entries in the links
    if (isset($changesNew['links'])) {
        foreach ($changesNew['links'] as $link) {
            $url = $link['url'];
            $title = $link['title'];
            $fmt = $link['fmtid'];
            $os = $link['osid'];
            $osvsn = $link['osvsn'];
            $cmp = $link['compression'];
            $cmppri = $link['compressedprimary'];
            if ($url == "") {
                $errDetail["links"][] =
                    "You must enter a URL for each link.";
            }
            if (preg_match("#if\.illuminion\.de/infocom/#i", $url)
                || preg_match("#xs4all.nl/~pot/infocom/.*\.z\d#i", $url)) {
                $errDetail["links"][] =
                    htmlspecialcharx($url) . ": links to this site "
                    . "aren't allowed by the IFDB <a href=\" "
                    . "tos\">terms of service</a>, because the site "
                    . "illegally contains "
                    . "copyrighted material without the copyright owner's "
                    . "permission. We're sorry, but IFDB prohibits "
                    . "links to \"abandonware\" and other illegal file "
                    . "sharing sites, because links to such sites could "
                    . "create legal problems for IFDB and even lead to IFDB "
                    . "being shut down. We appreciate your help keeping "
                    . "IFDB free of illegal links.";
            }
            if ($title == "") {
                $errDetail["links"][] =
                    "You must enter a title for each link.";
            }
            if ($fmt == "") {
                $errDetail["links"][] =
                    "You must select a file type for each link.";
            }
            if ($fmtmap[$fmt][1] == 'X' && $os == "") {
                $errDetail["links"][] =
                    "You must select the operating system for an Application
                     or Installer link.";
            }

            // validate the column lengths
            if (($ml = $dbLens['links.title']) != 0
                && strlen($title) > $ml) {
                $errDetail["links"][] =
                    "The link title \""
                    . htmlspecialcharx(substr($title, 0, 50))
                    . "...\" is too long - the limit is $ml characters.";
            }
            if (($ml = $dbLens['links.compressedprimary'])
                && strlen($cmppri) > $ml) {
                $errDetail["links"][] =
                    "The main filename \""
                    . htmlspecialcharx(substr($cmppri, 0, 50))
                    . "...\" is too long - the limit is $ml characters.";
            }
        }
    }

    // check for missing or invalid data in the reviews
    if (isset($changesNew['extreviews'])) {
        foreach ($changesNew['extreviews'] as $rev) {
            $url = $rev['url'];
            $src = $rev['sourcename'];
            $headline = $rev['headline'];
            $summary = $rev['summary'];

            if ($url == "") {
                $errDetail["extreviews"][] =
                    "Please enter a URL for each off-site review.";
            }
            if ($summary == "") {
                $errDetail["extreviews"][] =
                    "Please enter a summary for each off-site review.";
            }
            if ($src == "") {
                $errDetail["extreviews"][] =
                    "Please enter a source name for each off-site review.";
            }

            if (($ml = $dbLens['extreviews.sourcename']) != 0
                && strlen($src) > $ml) {
                $errDetail["extreviews"][] =
                    "The source name \""
                    . htmlspecialcharx(substr($src, 0, 50))
                    . "...\" is too long - the limit is $ml characters.";
            }

            if (($ml = $dbLens['reviews.summary']) != 0
                       && strlen($headline) > $ml) {
                $errDetail["extreviews"][] =
                    "The title \"" . htmlspecialcharx(substr($src, 0, 50))
                    . "...\" is too long - the limit is $ml characters.";
            }
        }
    }

    // check for missing or invalid data in the cross-references
    $errA = $errB = 0;
    if (isset($changesNew['xrefs'])) {
        foreach ($changesNew['xrefs'] as $xref) {
            $reftype = $xref['reftype'];
            $rid = $xref['toID'];
            $rname = $xref['toTitle'];

            if ($reftype == "" && $errA++ == 0) {
                $errDetail["xrefs"][] =
                    "Please select a connection type for each referenced game.";
            }
            if ($rid == "" && $errB++ == 0) {
                $errDetail["xrefs"][] =
                    "Please select a game for each item in the list.";
            }
        }
    }

    // check for author profile links
    if (isset($changesNew['authorExt'])) {
        // pull out each profile link
        $au = $changesNew['authorExt'];
        for ($ofs = 0, $proList = [] ;
             preg_match("/\{([a-z0-9]+)\}/i", $au, $match,
                        PREG_OFFSET_CAPTURE, $ofs) ; $ofs = $match[0][1]+1) {
            // pull out this match and add it to the list
            $proList[] = $pro = mysql_real_escape_string($match[1][0], $db);

            // validate it
            $result = mysql_query(
                "select id from users where id='$pro'", $db);
            if (mysql_num_rows($result) == 0) {
                $errDetail['eAuthor'][] =
                    "The profile ID {{$pro}} doesn't refer to any existing user.";
            }
        }

        // save the profile list in the update record for insertion
        $changesNew['profileLinks'] = $proList;
    }


    // if validation failed, stop the update and redisplay the form
    if (count($errDetail)) {
        // we're going to redisplay the page after all, so keep the
        // current in-memory copy of our data set
        $rec = $req;

        // generate the error message
        $errcnt = count($errDetail);
        $problems = ($errcnt == 1 ? "a problem" : "some problems");
        if ($apiMode) {
            $saveErrMsg = "The changes could not be saved due to "
                          . "errors in the entered values.";
            $saveErrCode = "DataValidation";
        } else {
            $saveErrMsg = "Your changes were not saved due to $problems
                with the values you entered - refer to the details below.
                Click Save to re-submit your changes after making
                corrections.";
            if ($errcnt > 1)
                $saveErrMsg .= " (Note that <b>$errcnt</b> fields
                    require your attention.)";
        }
        return false;
    }

    // if we're creating a game, we need to insert a new GAMES table
    // row; otherwise, we need to update an existing GAMES row and
    // save a new GAMES_HISTORY row
    $saveErrMsg = false;
    if ($id == "new") {
        $newid = false;
        $result = saveNewGame($db, $adminPriv, $apiMode,
                              $changesNew, $newid,
                              $progress, $saveErrMsg, $saveErrCode,
                              $errDetail);
    }
    else {
        $result = saveOldGame($db, $adminPriv, $apiMode,
                              $id, $rec, $req, $changesOld, $changesNew,
                              $progress, $saveErrMsg, $saveErrCode, $errDetail);
        $newid = $id;
    }

    // if any errors occurred, flag it and show the page again;
    // if we were successful, redirect to the new version of the
    // game page that we just created
    if ($result)
    {
        // success!

        // commit the new image, if any
        if (isset($changesNew['coverart']))
            commit_image($changesNew['coverart']);

        // check for spam links in the description
        if ($changesNew['desc'] ?? null)
            send_admin_email_if_links($changesNew['desc'], "Game description", "viewgame?id=$newid");

        // check the operating mode
        if ($apiMode)
        {
            // API mode - just return the new/existing game ID
            return $newid;
        }
        else
        {
            // UI mode - redirect to the page we just updated/created
            header("HTTP/1.1 301 Moved Permanently");
            header("Content-Type: text/html");
            header("Location: viewgame?id=$newid");

            echo "<a href=\"viewgame?id=$newid\">Redirecting
                (click here if your browser doesn't redirect
                 automatically)</a>";

            // we're done; the browser will handle the rest via
            // the redirect
            exit();
        }
    }
    else
    {
        // failed - show an error message if we haven't already set one up
        if (!$saveErrMsg)
        {
            if ($apiMode) {
                $saveErrMsg = "A database error occurred saving "
                              . "the data (error code: $progress).";
                $saveErrCode = "DbError";
            } else {
                $saveErrMsg = "An error occurred updating the game
                    record in the database.  You might try the request
                    again in a little while, or
                    <a target=\"_blank\" href=\"/contact\">contact us</a>
                    if the problem persists.  If you need to contact us,
                    please tell us <b>exactly</b> what you were trying to do
                    (if you can take a snapshot of the screen showing
                    all of the updates you made, that would help), and
                    mention this error code, which might tell us more
                    about what's going on internally to the database:
                    $progress.";
            }
        }

        // we're going to redisplay the page after all, so keep the
        // current in-memory copy of our data set
        $rec = $req;

        // return failure
        return false;
    }
}

// ------------------------------------------------------------------------
//
// Parse a "date or year" value.  This does a normal date parse, but
// then checks the result to see if it looks like a full date, or just
// a year value.  If it's a full date, we add a time of one hour past
// midnight to flag it as a fully specified date.  Exactly midnight
// indicates that just a year was entered.
//
function parseDateOrYearVal($val)
{
    // do the normal date parse
    $d = parseDateVal($val);

    // if the value doesn't look like just a year value, add the time portion
    if (!preg_match("/[0-9][0-9][0-9][0-9]$/AD", $val))
        $d .= " 01:00:00";

    // return the parsed value
    return $d;
}

// ------------------------------------------------------------------------
//
// Calculate the deltas between an old record and a new record.  Returns
// an array of (old-deltas, new-deltas) - each item is a hashtable keyed
// by column name of the changed items.  Old-deltas contains as its values
// the old values of the changed items, and new-deltas gives the new values
// of the changed items.  Each table has the same set of keys, so you can
// fish out the old and new value for each changed item by looking in the
// respective table.
//
function calcDeltas($oldRec, $newRec)
{
    global $fields;

    // get the logged-in user
    $userid = $_SESSION['logged_in_as'];

    // set up our internal list of fields - start with the display list,
    // and add our special internal fields
    $f = $fields;
    $f[] = ["author", "author", 60, null, null, TypeString];
    $f[] = ["authorExt", "authorExt", 60, null, null, TypeString];

    // generate a list of updated fields
    $changesOld = [];
    $changesNew = [];
    for ($i = 0 ; $i < count($f) ; $i++) {

        // get the column descriptor
        $field = $f[$i];
        $colname = $field[1];

        // get the old and new values
        $oldval = (isset($oldRec[$colname]) ? $oldRec[$colname] : "");
        $newval = $newRec[$colname];

        // skip derived values
        if ($colname == "eAuthor")
            continue;

        // convert the types
        switch ($field[5]) {
        case TypeString:
            $newval = trim($newval);
            break;

        case TypeInt:
            $oldval = parseIntVal($oldval);
            $newval = parseIntVal($newval);
            break;

        case TypeDate:
            $oldval = parseDateVal($oldval);
            $newval = parseDateVal($newval);
            break;

        case TypeDateOrYear:
            // do the normal date parse
            $oldval = parseDateOrYearVal($oldval);
            $newval = parseDateOrYearVal($newval);
            break;

        case TypeImage:
            // For images, we have a reference to data in the session
            // or in the database.  If the value is OLD, we know it's
            // unchanged, so we can just make both 'false' to indicate
            // no change.  If the value is NONE, we can make the
            // new value empty so that it gets set to null.  If the
            // new value is a temp image, we have to pull the temp
            // image data out of the session.
            if ($newval == "old")
            {
                // the new image is explicitly the old image, so there's
                // no change
                $newval = false;
                $oldval = false;
            }
            else if (findTempImage($newval))
            {
                // we're changing to an uploaded image in the session
                $img = findTempImage($newval);

                // the new value is the image ID - tentatively store the
                // image and generate an ID for it
                $newval = tentative_store_image(
                    $img[0], $img[1], $img[3], $img[4], $userid);
            }
            else if ($newval == "none")
            {
                // changing to an empty image - use an empty string
                $newval = "";
                if ($oldval == "none")
                    $oldval = "";
            }
            else
            {
                // no change to the image
                $newval = false;
                $oldval = false;
            }

            // If we're making any change, we need to get the *true* old
            // value for storing in the deltas.  The fake old value is
            // always either "old" or "none".  For the deltas we want
            // the actual old value, which is the old picture ID.
            if ($newval != $oldval)
            {
                // the old value is the original cover art data
                $oldKey = 'orig:' . $colname;
                if (isset($oldRec[$oldKey]))
                    $oldval = $oldRec[$oldKey];
                else
                    $oldval = "";
            }

            // done with images
            break;
        }

        // compare the fields: if they differ, this column has changed
        if (strcmp($oldval, $newval) != 0) {
            $changesOld[$colname] = $oldval;
            $changesNew[$colname] = $newval;
        }
    }

    // check for changes to the links
    $newlinks = $newRec['links'];
    $oldlinks = (isset($oldRec['links']) ? $oldRec['links'] : []);
    if (!arrayOfArrayValsEqual(
        $newlinks, $oldlinks,
        ['url', 'title', 'desc', 'attrs', 'fmtid', 'osid', 'osvsn',
         'compression', 'compressedprimary'])) {
        // they don't match - add to the change lists
        $changesOld['links'] = $oldlinks;
        $changesNew['links'] = $newlinks;
    }

    // check for changes to the external reviews
    $newrevs = $newRec['extreviews'];
    $oldrevs = (isset($oldRec['extreviews']) ? $oldRec['extreviews'] : []);
    if (!arrayOfArrayValsEqual(
        $newrevs, $oldrevs,
        ['url', 'sourcename', 'sourceurl', 'rating',
         'headline', 'summary'])) {
        // they don't match - add to the change lists
        $changesOld['extreviews'] = $oldrevs;
        $changesNew['extreviews'] = $newrevs;
    }

    // check for changes to the external references
    $newxrefs = $newRec['xrefs'];
    $oldxrefs = (isset($oldRec['xrefs']) ? $oldRec['xrefs'] : []);
    if (!arrayOfArrayValsEqual(
        $newxrefs, $oldxrefs, ['reftype', 'toID'])) {

        // they don't match
        $changesOld['xrefs'] = $oldxrefs;
        $changesNew['xrefs'] = $newxrefs;
    }

    // if we're updating the IFID list, represent this as an array
    if (isset($changesNew['ifids']))
    {
        // turn the IFID lists into arrays
        if (isset($changesOld['ifids']) && $changesOld['ifids'] != "")
            $changesOld['ifids'] = explode(",", $changesOld['ifids']);
        if (isset($changesNew['ifids']) && $changesNew['ifids'] != "")
            $changesNew['ifids'] = explode(",", $changesNew['ifids']);
    }

    // return the changes
    return [$changesOld, $changesNew];
}

// ------------------------------------------------------------------------
//
// save a gamelink row
//
function insert_gamelink($db, $qid, $link, $idx)
{
    global $fmtmap;

    // initialize the link format map, if we haven't already done so
    init_link_formats($db);

    // get the fields, quoting special characters
    $url = mysql_real_escape_string($link['url'], $db);
    $fmt = mysql_real_escape_string($link['fmtid'], $db);
    $attrs = (int)$link['attrs'];
    $title = mysql_real_escape_string($link['title'], $db);
    $desc = mysql_real_escape_string($link['desc'], $db);
    $fmtclass = $fmtmap[$fmt][1];
    $os = mysql_real_escape_string($link['osid'], $db);
    $osvsn = mysql_real_escape_string($link['osvsn'], $db);
    $cmp = mysql_real_escape_string($link['compression'], $db);
    $cmppri = mysql_real_escape_string($link['compressedprimary'], $db);

    // quote-or-null the nullable fields
    $desc = ($desc == "" ? "null" : "'$desc'");
    $os = ($os == "" || $fmtclass != 'X' ? "null" : "'$os'");
    $osvsn = ($osvsn == "" || $fmtclass != 'X' ? "null" : "'$osvsn'");
    $cmp = ($cmp == "" ? "null" : "'$cmp'");
    $cmppri = ($cmppri == "" || $cmp == "" ? "null" : "'$cmppri'");

    // insert the row
    return mysql_query(
        "insert into gamelinks
          (gameid, title, `desc`, url, attrs,
           fmtid, osid, osvsn,
           compression, compressedprimary, displayorder)
          values ('$qid', '$title', $desc, '$url', '$attrs',
                  '$fmt', $os, $osvsn,
                  $cmp, $cmppri, $idx)",
        $db);
}

// ------------------------------------------------------------------------
//
// save an external review row
//
function insert_extrev($db, $qid, $rev, $idx)
{
    // get the fields, quoting special characters
    $url = mysql_real_escape_string($rev['url'], $db);
    $src = mysql_real_escape_string($rev['sourcename'], $db);
    $srcurl = mysql_real_escape_string($rev['sourceurl'], $db);
    $rating = mysql_real_escape_string($rev['rating'], $db);
    $headline = mysql_real_escape_string($rev['headline'], $db);
    $summary = mysql_real_escape_string($rev['summary'], $db);

    // quote-or-null the nullable fields
    $srcurl = ($srcurl == "" ? "null" : "'$srcurl'");
    $rating = ($rating == "" || $rating == "0" ? "null" : "'$rating'");
    $headline = ($headline == "" ? "null" : "'$headline'");

    // insert the REVIEWS row (SPECIAL code 4 = EXTERNAL)
    $result = mysql_query(
        "insert into reviews
         (summary, review, rating, userid, createdate, special, gameid)
         values ($headline, '$summary', $rating, '\$system',
                 now(), '4', '$qid')", $db);

    // if that failed, give up
    if (!$result)
        return false;

    // get the new REVIEWS row's ID
    $revID = mysql_insert_id($db);

    // insert the EXTREVIEWS row
    return mysql_query(
        "insert into extreviews
          (gameid, reviewid, url, sourcename, sourceurl, displayorder)
          values ('$qid', '$revID', '$url', '$src', $srcurl, '$idx')", $db);
}


// ------------------------------------------------------------------------
//
// save a cross-reference row
//
function insert_xref($db, $qid, $xrefs, $idx)
{
    // get the fields, quoting special characters
    $reftype = mysql_real_escape_string($xrefs['reftype'], $db);
    $toid = mysql_real_escape_string($xrefs['toID'], $db);

    // insert the row
    return mysql_query(
        "insert into gamexrefs
         (fromid, toid, reftype, displayorder)
         values ('$qid', '$toid', '$reftype', '$idx')", $db);
}

// ------------------------------------------------------------------------
//
// save a profile link
//
function insert_profile_link($db, $qid, $l)
{
    // quote the profile ID
    $l = mysql_real_escape_string($l, $db);

    // insert the row
    return mysql_query(
        "insert into gameprofilelinks (gameid, userid, moddate)
         values ('$qid', '$l', now())", $db);
}

// update a profile link set
function update_profile_links($db, $qgameid, $plNew, &$progress)
{
    // make a table keyed by new profile user ID
    $plNewTab = [];
    foreach ($plNew as $p)
        $plNewTab[$p] = true;

    // query the old profile links
    $result = mysql_query("select userid from gameprofilelinks where gameid = '$qgameid'", $db);
    for ($i = 0, $n = mysql_num_rows($result), $plOldTab = [] ; $i < $n ; $i++) {
        list($p) = mysql_fetch_row($result);
        $plOldTab[$p] = true;
    }

    // make a list of items appearing only in the OLD table
    $plOldOnly = [];
    foreach ($plOldTab as $p => $v) {
        if (!isset($plNewTab[$p]))
            $plOldOnly[] = $p;
    }

    // make a list of items appearing only in the NEW table
    $plNewOnly = [];
    foreach ($plNewTab as $p => $v) {
        if (!isset($plOldTab[$p]))
            $plNewOnly[] = $p;
    }

    // delete the old links that aren't set in the new list
    $result = true;
    if (count($plOldOnly) > 0) {
        $progress = "DLP0721";
        $result = mysql_query(
            "delete from gameprofilelinks where gameid = '$qgameid' and userid in ('"
            . implode("','", $plOldOnly) . "')", $db);
    }

    // insert the new profile links
    for ($i = 0 ; $i < count($plNewOnly) ; $i++) {
        if ($result) {
            $progress = "IPL0721.$i";
            $result = insert_profile_link($db, $qgameid, $plNewOnly[$i]);
        }
    }

    // return the mysql success/error status
    return $result;
}


// ------------------------------------------------------------------------
//
// save a NEW game
//
function saveNewGame($db, $adminPriv, $apiMode,
                     $changesNew, &$newid,
                     &$progress, &$saveErrMsg, &$saveErrCode, &$errDetail)
{
    global $fields;

    // get the logged-in user
    $userid = $_SESSION['logged_in_as'];

    // make sure that the required fields are present
    foreach (["title" => "title", "author" => "eAuthor"]
             as $col => $errCol) {

        // get the new value, if present
        $newVal = isset($changesNew[$col]) ? $changesNew[$col] : "";

        // set up the default error message
        $colErr = "This field is required.";

        // if the author field is empty but the external version isn't,
        // it means that the {TUID} formatting was missing the display names
        if ($col == "author" && $changesNew["authorExt"] != "") {
            $colErr = "Please be sure to enter the author's display name "
                      . "before each profile link.  Use the format "
                      . "<b>Author Dent {a10x00139ke9041j}</b>.";
        }

        // if it's not set, flag the error
        if ($newVal == "") {
            // flag the specific column error
            $errDetail[$errCol][] = $colErr;

            // flag the general save error
            if ($apiMode) {
                $saveErrMsg = "Your changes were not saved due to "
                              . "errors in one or more field values.";
                $saveErrCode = "DataValidation";
            }
            else {
                $saveErrMsg = "Your changes were not saved - please refer "
                              . "to the error details below.";
            }
        }
    }

    // give up on a validation failure
    if ($saveErrMsg)
        return false;

    // build the INSERT statement clauses
    $collist = [];
    $vallist = [];
    foreach ($changesNew as $key => $val) {
        // skip IFIDs, links, external reviews, or anything else that's
        // represented here as an array - these are stored in separate tables
        if (gettype($val) == "array")
            continue;

        // generate the value in the proper format
        if ($val == "")
            $outval = "NULL";
        else
            $outval = "'" . mysql_real_escape_string($val, $db) . "'";

        // add the column name and value to the respective lists
        $collist[] = "`$key`";
        $vallist[] = $outval;
    }

    // add the initial page version (1) and editor (current user)
    $collist[] = "pagevsn";
    $vallist[] = "1";

    $collist[] = "editedby";
    $vallist[] = "'$userid'";

    // turn each list into a comma-separated string for the INSERT syntax
    $cols = implode(",", $collist);
    $vals = implode(",", $vallist);

    // we haven't inserted the game row yet
    $newid = false;
    $insertedGame = false;

    // presume success
    $result = true;

    // generate a new ID - game IDs are TUIDs
    if ($result) {
        $progress = "TUI1907.B";
        $tuid = generateTUID($db, "games.id,gamefwds.gameid", 10);
    }

    // if we didn't manage to generate a random ID, give up
    if (!$tuid)
        $result = false;

    // use the new TUID as the new ID
    $newid = $tuid;
    $qnewid = mysql_real_escape_string($tuid, $db);

    // run the INSERT
    if ($result) {
        $progress = "IGR1908";
        $result = mysql_query(
            "insert into games (id, created, moddate, $cols)
            values ('$qnewid', now(), now(), $vals)", $db);

        // note the insert
        if ($result)
            $insertedGame = true;
    }

    // insert the IFIDs, if any
    if ($result && isset($changesNew['ifids'])) {
        $ifids = $changesNew['ifids'];
        for ($i = 0 ; $i < count($ifids) ; $i++) {
            if ($result) {
                $progress = "IIF190A.$i";
                $ifid = mysql_real_escape_string(strtoupper($ifids[$i]), $db);
                $result = mysql_query("insert into ifids
                    (gameid, ifid) values ('$qnewid','$ifid')", $db);
            }
        }
    }

    // insert the download links, if any
    if ($result && isset($changesNew['links'])) {
        $links = $changesNew['links'];
        for ($i = 0 ; $i < count($links) ; $i++) {
            if ($result) {
                $progress = "ILN190B.$i";
                $result = insert_gamelink($db, $qnewid, $links[$i], $i);
            }
        }
    }

    // insert the review links, if any
    if ($result && isset($changesNew['extreviews'])) {
        $revs = $changesNew['extreviews'];
        for ($i = 0 ; $i < count($revs) ; $i++) {
            if ($result) {
                $progress = "IRV190C.$i";
                $result = insert_extrev($db, $qnewid, $revs[$i], $i);
            }
        }
    }

    // insert the profile links, if any
    if ($result && isset($changesNew['profileLinks'])) {
        $pl = $changesNew['profileLinks'];
        for ($i = 0 ; $i < count($pl) ; $i++) {
            if ($result) {
                $progress = "IPL190D.$i";
                $result = insert_profile_link($db, $qnewid, $pl[$i]);
            }
        }
    }

    // insert the cross-references, if any
    if ($result && isset($changesNew['xrefs'])) {
        $xrefs = $changesNew['xrefs'];
        for ($i = 0 ; $i < count($xrefs) ; $i++) {
            if ($result) {
                $progress = "IXF190E.$i";
                $result = insert_xref($db, $qnewid, $xrefs[$i], $i);
            }
        }
    }

    // note the error if in diagnostic mode
    if ($adminPriv)
        $progress .= " [" . mysql_errno($db) . " - "
                     . mysql_error($db) . "]";

    // unlock the tables
    mysql_query("unlock tables", $db);

    // if successful so far, commit; otherwise roll back
    if ($result) {
        $progress = "CTX0712";
        $result = mysql_query("commit", $db);
    }
    else {
        //if (!mysql_query("rollback", $db)) {
            // no transactions - do an ad hoc rollback of whatever we can
            if ($insertedGame && $newid) {
                mysql_query("delete from games where id = '$qnewid'", $db);
                mysql_query(
                    "delete from gamelinks where gameid = '$qnewid'", $db);
                mysql_query("delete from ifids where gameid = '$qnewid'", $db);
                mysql_query(
                    "delete from reviews where gameid = '$qnewid'", $db);
                mysql_query(
                    "delete from gameprofilelinks where gameid = '$qnewid'",
                    $db);
            }
        //}
    }

    // restore auto-commit mode
    mysql_query("set autocommit=1", $db);

    // return the success/failure indication
    return $result;
}

// ------------------------------------------------------------------------
//
// save updates to an EXISTING game
//
function saveOldGame($db, $adminPriv, $apiMode,
                     $id, $rec, &$req, $changesOld, $changesNew,
                     &$progress, &$saveErrMsg, &$saveErrCode, &$errDetail)
{
    // get the logged-in user
    $userid = $_SESSION['logged_in_as'];

    // the ID of an existing game will not change
    $newid = $id;
    $qnewid = $qid = mysql_real_escape_string($id, $db);

    // The first step in saving is to figure out what's changed,
    // and save a history record for each modified field.  The
    // history record consists of the OLD value of each updated
    // field, since this lets us generate the old version of the
    // page by starting with the next version and applying each
    // changed value from the history record.

    // check to make sure there were changes
    if (count($changesOld) == 0) {
        $saveErrMsg = "You didn't make any changes.";
        $saveErrCode = "NoChanges";
        return false;
    }

    $progress = "";
    $insertedHistory = false;
    $updatedGame = false;

    // serialize the OLD values for storage in the history table
    $deltas = serialize($changesOld);

    // presume success
    $result = true;

    // note the page version in the *current* record
    $pagevsn = $rec['pagevsn'];

    // If the page version has changed since we originally loaded the page,
    // it means that another user has saved changes while we were working
    // on the page.  Look at the history row (or rows - there could even
    // have been multiple updates) to see what's changed.  If the concurrent
    // update changed any of columns we're changing, don't save - instead,
    // warn the user about the other change, explain what happened, and
    // let them decide whether (and how) to proceed.
    if ($id != "new" && $result && $pagevsn != $req['pagevsn']) {

        // Build out a copy of the original record that we started with
        // for this editing session.  To do this, take the current record,
        // and apply the reverse deltas from the history log until we get
        // back to our original version.

        // start with the current record, in our serialization format
        $origRec = $rec;
        $origRec['ifids'] = explode(",", $rec['ifids']);
        $origRec['coverart'] = $rec['orig:coverart'];

        // apply history records from latest to oldest until we get back
        // to the version we've been editing
        $curvsn = (int)$origRec['pagevsn'];
        $origvsn = (int)$_REQUEST['pagevsn'];
        $result = mysql_query(
            "select deltas from games_history
             where id = '$qid'
               and pagevsn between $origvsn and $curvsn - 1
               order by pagevsn desc", $db);
        for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
            // retrieve and unserialize the deltas
            $dg = unserialize(mysql_result($result, 0, "deltas"));

            // apply the deltas
            foreach ($dg as $col => $val)
                $origRec[$col] = $val;
        }

        // put things back in our internal format
        $origRec['ifids'] = implode(",", $origRec['ifids']);
        $art = $origRec['coverart'];
        $origRec['orig:coverart'] = $origRec['coverart'];
        $origRec['coverart'] = (is_null($art) || strlen($art) == 0
                                ? "none" : "old");

        // calculate the deltas between the ORIGINAL and NEW database
        // versions - this tells us what the OTHER user changes
        list ($dbChangesOld, $dbChangesNew) = calcDeltas($origRec, $rec);

        // calculate the deltas between the ORIGINAL database version
        // and our EDITED version - this tells us what WE changed
        list ($editChangesOld, $editChangesNew) = calcDeltas($origRec, $req);

        // Now find out what we changed, what the other user changed, and
        // what we both changed:
        //
        // - if WE changed it, and the other user didn't, keep our value
        // - if THEY changed it, and we didn't touch it, keep their value
        // - if we BOTH changed it, keep our value and flag it as a conflict
        //
        $conflict = false;
        foreach ($dbChangesNew as $k=>$v) {

            // check to see if we edited this value as well
            if (isset($editChangesNew[$k])) {
                // it's a conflict - flag it and keep our value
                $conflict = true;
                $errDetail[$k][] =
                    "This item was edited by another user "
                    . "while you were working on your changes.";
            } else {
                // they changed it, and we didn't - keep their update
                $req[$k] = $rec[$k];
            }
        }

        // if we found any conflicts, fail
        if ($conflict != 0) {
            // explain what's going on
            if ($apiMode) {
                $saveErrMsg = "An update conflict occurred. Another user "
                              . "submitted changes while your request "
                              . "was being processed. Please view the "
                              . "listing via the IFDB Web site. You can "
                              . "then make changes manually if you wish.";
                $saveErrCode = "CannotMerge";
            } else {
                $saveErrMsg =
                    "<b>Update Conflict!</b> Another user saved changes to this "
                    . "game while you were working on your changes. You both "
                    . "changed some of the same items, which are flagged "
                    . "below. We recommend that you look at the "
                    . "<a href=\"viewgame?id=$id\" target=\"_blank\">"
                    . "current version of the page</a> and compare your changes "
                    . "with those made by the other user. When you're "
                    . "satisfied with your changes, click Save to apply "
                    . "them.";
            }

            // this counts as a failure
            $result = false;

            // our base version for the next attempted save is the *new*
            // version that we just compared against - if they Save again
            // now that they've been warned, we want to proceed with the
            // update (unless, of course, *another* concurrent update
            // occurs in the meantime)
            $req['pagevsn'] = $pagevsn;
        }
    }

    // save the history row
    if ($result) {
        $progress = "IHR070C";
        $deltas = mysql_real_escape_string($deltas, $db);
        $result = mysql_query("insert into games_history
            (id, pagevsn, editedby, moddate, deltas)
            values ('$qid', '$pagevsn', '{$rec['editedby']}',
                    '{$rec['moddate']}', '$deltas')", $db);

        // note if we successfully inserted the history row
        if ($result)
            $insertedHistory = true;
    }

    // build the update list for the active row
    if ($result) {
        // assemble the list of new values for the update -
        // these are the changed columns set to their $req values
        $varlist = [];
        foreach ($changesNew as $key => $val) {
            // skip IFIDs, links, reviews, cross-references, and
            // profile links - these are stored in separate tables
            if ($key == "ifids"
                || $key == "links"
                || $key == "extreviews"
                || $key == "profileLinks"
                || $key == "xrefs")
                continue;

            // start with the column name
            $ele = "`$key`=";

            // set to the new value, or NULL if applicable
            if ($val == "")
                $ele .= "NULL";
            else
                $ele .= "'" . mysql_real_escape_string($val, $db) . "'";

            // add it to the variable list
            $varlist[] = $ele;
        }

        // add the updates for the page version, modifier, and timestamp
        $newpagevsn = $pagevsn + 1;
        $varlist[] = "pagevsn='$newpagevsn'";
        $varlist[] = "editedby='$userid'";
        $varlist[] = "moddate=now()";

        // turn it into a SET list (a=b,c=d,...)
        $varlist = implode(",", $varlist);

        // the variable list might be empty after all of that,
        // because there are some fields that don't go directly
        // in the main table - send the update only if we
        // actually have columns to update
        if ($varlist != "") {
            // send the update
            $progress = "UGR070D";
            $result = mysql_query("update games set $varlist
                where id = '$qid'", $db);

            // if successful, note that we updated the game row
            if ($result)
                $updatedGame = true;
        }
    }

    // update IFIDs
    if ($result && isset($changesNew['ifids'])) {
        // delete the existing IFIDs for the game
        $progress = "DIF070E";
        $result = mysql_query("delete from ifids
            where gameid = '$qid'", $db);

        // now insert the new IFID list
        $ifids = $changesNew['ifids'];
        for ($i = 0 ; $i < count($ifids) ; $i++) {
            if ($result) {
                if ($ifids[$i] == '') continue;
                $progress = "IIF070F.$i";
                $ifid = mysql_real_escape_string(strtoupper($ifids[$i]), $db);
                $result = mysql_query("insert into ifids
                    (gameid, ifid) values ('$qid','$ifid')", $db);
            }
        }
    }

    // update download links
    if ($result && isset($changesNew['links'])) {
        // delete the existing links
        $progress = "DLN0710";
        $result = mysql_query(
            "delete from gamelinks where gameid = '$qid'", $db);

        // insert the new links
        $links = $changesNew['links'];
        for ($i = 0 ; $i < count($links) ; $i++) {
            if ($result) {
                $progress = "ILN0711.$i";
                $result = insert_gamelink($db, $qid, $links[$i], $i);
            }
        }
    }

    // update external reviews
    if ($result && isset($changesNew['extreviews'])) {
        // delete the existing EXTREVIEWS rows
        $progress = "DLR0720";
        $result = mysql_query(
            "delete from extreviews where gameid = '$qid'", $db);

        // delete the corresponding REVIEWS rows (SPECIAL code 4 = EXTERNAL)
        $progress = "DLR0720.B";
        $result = mysql_query(
            "delete from reviews where gameid='$qid' and special='4'", $db);

        // insert the new reviews
        $revs = $changesNew['extreviews'];
        for ($i = 0 ; $i < count($revs) ; $i++) {
            if ($result) {
                $progress = "IRV0721.$i";
                $result = insert_extrev($db, $qnewid, $revs[$i], $i);
            }
        }
    }

    // update the cross-references
    if ($result && isset($changesNew['xrefs'])) {
        // delete the existing GAMEXREFS rows
        $progress = "DLX0720";
        $result = mysql_query(
            "delete from gamexrefs where fromid = '$qid'", $db);

        // insert the new rows
        $xrefs = $changesNew['xrefs'];
        for ($i = 0 ; $i < count($xrefs) ; $i++) {
            if ($result) {
                $progress = "IXR0721.$i";
                $result = insert_xref($db, $qnewid, $xrefs[$i], $i);
            }
        }
    }

    // update the profile links, if any
    if ($result && isset($changesNew['profileLinks'])) {
        $result = update_profile_links($db, $qnewid, $changesNew['profileLinks'], $progress);
    }

    // note the error if in diagnostic mode
    if ($adminPriv)
        $progress .= " [" . mysql_errno($db) . " - " . mysql_error($db) . "]";

    // release table locks
    mysql_query("unlock tables", $db);

    // if successful so far, commit; otherwise roll back
    if ($result) {
        $progress = "CTX0712";
        $result = mysql_query("commit", $db);
    }
    else {
        //if (!mysql_query("rollback", $db)) {
            // if we failed trying to update the game row, try
            // an ad hoc rollback by deleting the history row
            if ($insertedHistory && !$updatedGame) {
                mysql_query("delete from games_history
                    where id='$qid' and pagevsn=$pagevsn", $db);
            }
        //}
    }

    // restore auto-commit mode
    mysql_query("set autocommit=1", $db);

    // return success/failure indication
    return $result;
}

?>
