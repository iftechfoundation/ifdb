<?php


// -------------------------------------------------------------------------
// comparison callback for sorting an array of links by the display order
// element
//
function sortByDisplayOrder($a, $b)
{
    return $a['displayorder'] - $b['displayorder'];
}

// -------------------------------------------------------------------------
// fix a URL to ensure proper formatting in an HTML page
function fixUrl($url) {
    return str_replace(array("\""), array("%22"), $url);
}

// --------------------------------------------------------------------------
//
// Retrieve the game information
//
function getGameInfo($db, $id, $curuser, $requestVersion, &$errMsg, &$errCode)
{

    // quote the ID string
    $qid = mysql_real_escape_string($id, $db);

    // look up the game
    $result = mysql_query(
       "select
            title, author, authorExt, published,
            version, license, `system`, `desc`, length(coverart) hasart, genre,
            language, seriesname, seriesnumber, forgiveness, bafsid, website,
            downloadnotes, editedby,
            concat(date_format(moddate, '%e %M %Y at %l:%i'),
               lower(date_format(moddate, '%p'))) as moddate,
            date_format(moddate, '%d-%b-%Y %H:%i') as moddate2,
            pagevsn, flags
        from games
        where id = '$qid'", $db);
    if (mysql_num_rows($result) == 0) {
        $errMsg = "This game was not found in the database. If you reached
          this page through a link, you might want to notify the maintainer
          of the referencing site of the broken link.";
        $errCode = "notFound";
        return;
    }

    // retrieve the result
    $rec = mysql_fetch_array($result, MYSQL_ASSOC);

    // build the list of downloads
    $result = mysql_query(
        "select
          url, title, `desc`, fmtid, osid, osvsn,
          compression, compressedprimary, attrs, displayorder
        from
          gamelinks
        where
          gameid = '$qid'", $db);
    $rows = mysql_num_rows($result);
    for ($i = 0, $links = array() ; $i < $rows ; $i++)
        $links[$i] = mysql_fetch_array($result, MYSQL_ASSOC);

    // build the list of IFIDS
    $result = mysql_query("select ifid from ifids where gameid = '$qid'", $db);
    $rows = mysql_num_rows($result);
    for ($i = 0, $ifids = array() ; $i < $rows ; $i++)
        $ifids[] = mysql_result($result, $i, "ifid");

    // get the display rank of the external reviews
    $result = mysql_query(
        "select displayrank from specialreviewers where code='external'", $db);
    $extRevDisplayRank = mysql_result($result, 0, "displayrank");

    // fetch the external reviews
    $result = mysql_query(
        "select
           x.url as url, r.rating as rating, r.summary as headline,
           r.review as summary,
           x.sourcename as sourcename, x.sourceurl as sourceurl,
           sr.name as specialname, sr.code as code
         from
           reviews as r
           join extreviews as x on x.reviewid = r.id
           join specialreviewers as sr on sr.id = r.special
         where
           r.gameid = '$qid'
           and ifnull(now() >= r.embargodate, 1)
         order by
           x.displayorder", $db);
    $rows = mysql_num_rows($result);
    for ($i = 0, $extrevs = array() ; $i < $rows ; $i++)
        $extrevs[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // fetch the OUTGOING cross-references; arrange in display order
    $result = mysql_query(
        "select reftype, toid as toID
         from gamexrefs
         where fromid = '$qid'
         order by displayorder", $db);

    $rows = mysql_num_rows($result);
    for ($i = 0, $xrefs = array() ; $i < $rows ; $i++)
        $xrefs[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // Fetch the INCOMING cross-references.  Since these can come from 
    // multiple sources each with their own different display ordering,
    // the display order isn't meaningful when they're combined.  Instead,
    // group them by reference type so that we show all of the translations
    // together, all of the ports together, etc.
    $result = mysql_query(
        "select
           x.reftype as reftype, t.toname as toname, t.tonames as tonames,
           x.fromid as fromid,
           ifnull(g.title, 'Not Found') as title,
           ifnull(g.author, 'Unknown') as author,
           g.system as `system`,
           (select name from iso639x as iso
            where g.language like iso.id2
              or g.language like iso.id3
              or g.language like concat(iso.id2, '-%')
              or g.language like concat(iso.id3, '-%')
              limit 0, 1) as language
         from
           gamexrefs as x
           join gamexreftypes as t on t.reftype = x.reftype
           left outer join games as g
              on g.id = x.fromid
         where
           x.toid = '$qid'
         order by
           x.reftype, g.title, g.author", $db);
    echo mysql_error($db);

    $rows = mysql_num_rows($result);
    for ($i = 0, $inrefs = array() ; $i < $rows ; $i++)
        $inrefs[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // Add the downloads, external reviews, IFIDs, and outbound cross-
    // references to the current record under construction as arrays,
    // so that they can participate in the reverse-delta versioning
    // mechanism.  Note that the inbound xrefs DON'T get versioned with
    // this game, since they're links pointing in from other games.
    $rec["links"] = $links;
    $rec["ifids"] = $ifids;
    $rec["extreviews"] = $extrevs;
    $rec["xrefs"] = $xrefs;

    // if we want to go back in time, apply the history
    if ($requestVersion && (int)$requestVersion != $rec['pagevsn']) {

        // query up the version history, in order from newest to oldest
        $result = mysql_query(
            "select
               pagevsn, deltas,
               concat(date_format(moddate, '%e %M %Y at %l:%i'),
                      lower(date_format(moddate, '%p'))) as moddate,
               date_format(moddate, '%d-%b-%Y %H:%i') as moddate2
            from games_history
            where id = '$qid'
            order by pagevsn desc", $db);

        // we haven't found our target version yet
        $foundvsn = false;

        // now run through the list until we find the requested version,
        // applying each item's reverse deltas in order
        $rowcnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $rowcnt ; $i++) {
            // fetch this row
            $vrec = mysql_fetch_array($result, MYSQL_ASSOC);

            // update the main record with the version's timestamp
            $rec["moddate"] = $vrec["moddate"];
            $rec["moddate2"] = $vrec["moddate2"];

            // deserialize the array of deltas
            $deltas = unserialize($vrec['deltas']);

            // copy each modified item into the current row under construction
            foreach ($deltas as $colname => $colval) {
                // save this column
                $rec[$colname] = $colval;

                // if it's Cover Art, note the new 'hasart' status
                if ($colname == "coverart")
                    $rec['hasart'] = (is_null($colval) || strlen($colval) == 0)
                                     ? null : true;
            }

            // if this is our target version, we can stop now
            if ((int)($vrec['pagevsn']) == (int)$requestVersion) {
                $foundvsn = true;
                break;
            }
        }

        // if we didn't find our target version, it's an error
        if (!$foundvsn) {
            $errMsg = "The requested version of this game wasn't found in
                the database. <a href=\"viewgame?id=$id\">Click here
                to view the main page for this game</a>.";
            $errCode = "versionNotFound";
            return;
        }

        // note that we're viewing an old version
        $historyView = true;
    }

    // massage the retrieved fields into our display formats
    $title = htmlspecialcharx($rec["title"]);
    $author = $rec["author"];
    $authorExt = $rec["authorExt"];
    $rawDesc = $rec["desc"];
    $desc = fixDesc($rawDesc);
    $published = $rec["published"];
    $version = $rec["version"];
    $license = htmlspecialcharx($rec["license"]);
    $language = htmlspecialcharx($rec["language"]);
    $system = htmlspecialcharx($rec["system"]);
    $hasart = !is_null($rec["hasart"]);
    $genre = htmlspecialcharx($rec["genre"]);
    $seriesname = htmlspecialcharx($rec["seriesname"]);
    $seriesnum = htmlspecialcharx($rec["seriesnumber"]);
    $forgiveness = htmlspecialcharx($rec["forgiveness"]);
    $bafsid = $rec["bafsid"];
    $website = fixUrl($rec["website"]);
    $dlnotes = fixDesc($rec["downloadnotes"]);
    $editedbyid = $rec["editedby"];
    $moddate = $rec["moddate"];
    $moddate2 = $rec["moddate2"];
    $pagevsn = $rec["pagevsn"];
    $ifids = $rec["ifids"];
    $links = $rec["links"];
    $extReviews = $rec["extreviews"];
    $xrefs = $rec["xrefs"];
    $flags = $rec["flags"];

    // make sure that the special name and code are set for the external
    // reviews - historical reviews won't have these elements set
    foreach ($extReviews as $i => $r) {
        $extReviews[$i]['specialname'] = "External";
        $extReviews[$i]['code'] = 'external';
    }

    // delete links that are marked "pending"
    for ($origLinks = $links, $links = array(), $i = 0 ;
         $i < count($origLinks) ; $i++)
    {
        // get this link
        $link = $origLinks[$i];

        // keep it as long as it's not marked "pending"
        if (!($link['attrs'] & GAMELINK_PENDING))
            $links[] = $link;
    }

    // Get the format and OS descriptions for the link formats.  Note that
    // we didn't just fetch these with a SQL join in the first place
    // because we might have needed to apply history deltas, and the history
    // rows don't have the ancillary data for the join.  So we have to wait
    // until we have the final rows, *then* go and manually do the join.
    for ($i = 0 ; $i < count($links) ; $i++) {
        $link = $links[$i];
        $fmtid = mysql_real_escape_string($link['fmtid'], $db);
        $fmtos = mysql_real_escape_string($link['osid'], $db);
        $fmtosvsn = mysql_real_escape_string($link['osvsn'], $db);
        $result = mysql_query("select
              externid, fmtname, `desc`, fmtclass,
              (icon is not null) as hasicon
            from filetypes
            where id = '$fmtid'", $db);
        if (mysql_num_rows($result) > 0) {
            $link['fmtexternid'] = mysql_result($result, 0, "externid");
            $link['fmtname'] = mysql_result($result, 0, "fmtname");
            $link['fmtdesc'] = mysql_result($result, 0, "desc");
            $link['fmticon'] = mysql_result($result, 0, "hasicon");
            $link['fmtclass'] = mysql_result($result, 0, "fmtclass");
        }
        else
            echo "row not found for format $fmtid";

        if ($fmtos) {
            $vsnWhere = ($fmtosvsn
                         ? "osversions.vsnid = '$fmtosvsn'"
                         : "osversions.name = '*'");
            $result = mysql_query(
                "select
                   operatingsystems.name as osname,
                   if (osversions.name = '*',
                       operatingsystems.name, osversions.name) as osvsnname,
                   (icon is not null) as hasicon
                from
                   operatingsystems, osversions
                where
                   operatingsystems.id = '$fmtos'
                   and osversions.osid = '$fmtos'
                   and $vsnWhere", $db);

            if (mysql_num_rows($result) > 0) {
                $link['osname'] = mysql_result($result, 0, "osname");
                $link['osvsnname'] = mysql_result($result, 0, "osvsnname");
                $link['osicon'] = mysql_result($result, 0, "hasicon");
            }
        }

        // pull out the 'is a game file' flag from the attributes
        $link['isgame'] = $link['attrs'] & GAMELINK_IS_GAME;

        // put back the updated row
        $links[$i] = $link;
    }

    // sort the links by display order
    usort($links, "sortByDisplayOrder");

    // Fill in the outbound xrefs with the game titles and authors,
    // and the reference type names.  We can't just select this up 
    // front because of versioning - old versions won't have this
    // extra data stashed, so for uniformity we simply reconstruct
    // it now for all versions.
    for ($i = 0 ; $i < count($xrefs) ; $i++) {

        // look up the game and reference type
        $result = mysql_query(
            "select
               g.title as toTitle, g.author as author,
               t.fromname as fromname
             from
               games as g
               join gamexreftypes as t
             where
               g.id = '{$xrefs[$i]['toID']}'
               and t.reftype = '{$xrefs[$i]['reftype']}'", $db);

        list($xrefs[$i]['toTitle'],
             $xrefs[$i]['author'],
             $xrefs[$i]['fromname'])
            = mysql_fetch_row($result);
    }

    // expand the language code into a descriptive name
    $languageNameOnly = null;
    if (!isEmpty($language))
    {
        // Look up the language name(s).  There might be more than one -
        // this can be a comma-separated list.
        $langarr = explode(",", $language);
        for ($i = 0, $langnames = array() ; $i < count($langarr) ; $i++)
        {
            // pull out this item
            $curlang = trim($langarr[$i]);
            if ($curlang)
            {
                // look up the language as an ISO-639 code - just look up the part up
                // to the hyphen; ignore anything after that
                $qlang = mysql_real_escape_string($curlang, $db);
                if (($hpos = strpos($qlang, "-")) !== false)
                    $qlang = substr($qlang, 0, $hpos);
                $result = mysql_query(
                    "select name from iso639x where id2='$qlang' or id3='$qlang'", $db);
                if (mysql_num_rows($result)) {
                    $langnames[] = mysql_result($result, 0, "name");
                }
            }
        }

        if (count($langnames)) {
            $languageNameOnly = implode(", ", $langnames);
            $language = "$languageNameOnly ($language)";
        }
    }

    // look up the name of the editor
    $result = mysql_query("select name from users 
        where id = '$editedbyid'", $db);
    $editedbyname = ($result && mysql_num_rows($result))
                    ? htmlspecialcharx(mysql_result($result, 0, "name"))
                    : "";

	// get the ratings view for the current user's sandbox
	$gameRatingsView = getGameRatingsView($db);

    // get the rating statistics
    $result = mysql_query(
        "select
           numRatingsInAvg, numRatingsTotal, avgRating, numMemberReviews
         from
           $gameRatingsView
         where
           gameid = '$qid'", $db);
    list($ratingAvgCnt, $ratingTotCnt, $ratingAvg, $memberReviewCnt) =
        mysql_fetch_row($result);

    // get the rating histogram
    $result = mysql_query(
        "select
           rating,
           count(if(RFlags & " . RFLAG_OMIT_AVG . ", null, rating))
         from
           reviews
         where
           gameid = '$qid'
           and ifnull(now() >= embargodate, 1)
         group by rating", $db);
    $ratingHisto = array(0, 0, 0, 0, 0, 0);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        list($rating, $count) = mysql_fetch_row($result);
        $ratingHisto[$rating] = $count;
    }

    // determine if the logged-in user (if any) has reviewed it
    $currentUserRating = 0;
    $currentUserReview = "";
    if ($curuser) {
        $result = mysql_query("select rating, review from reviews
            where userid = '$curuser' and gameid = '$qid'", $db);
        if (mysql_num_rows($result) > 0) {
            $currentUserRating = mysql_result($result, 0, "rating");
            $currentUserReview = mysql_result($result, 0, "review");
        }
    }

    // Pull out the publication year and the full date.  If the date is
    // January 1 at midnight, it means that only the year is meaningful.
    // We fill in a non-midnight time portion to indicate that the full
    // date is meaningful.
    $pubYear = $published ? substr($published, 0, 4) : null;
    if (!$published || preg_match("/^\d{4}-01-01( 00:00:00)?$/", $published))
        $pubFull = null;
    else
        $pubFull = date("F j, Y", strtotime($published));

    // build the information into a giant array
    return array($ifids, $title, $author, $authorExt,
                 $pubYear, $pubFull, $license,
                 $system, $desc, $rawDesc,
                 $hasart, $genre, $seriesname, $seriesnum,
                 $forgiveness, $bafsid, $version,
                 $language, $languageNameOnly,
                 $website, $links,
                 $ratingAvgCnt, $ratingTotCnt, $ratingAvg, $memberReviewCnt,
                 $currentUserRating, $currentUserReview,
                 $editedbyid, $editedbyname, $moddate, $moddate2, $pagevsn,
                 $historyView,
                 $dlnotes, $extReviews, $extRevDisplayRank,
                 $ratingHisto, $xrefs, $inrefs, $flags);
}

// ----------------------------------------------------------------------------
//
// given a deserialized 'deltas' list from a games_history record,
// get a description of the changes made as a comma-separated list of
// column names
//
function getDeltaDesc($deltas)
{
    // columns -> descriptions
    $deltaDesc = array("title" => "title",
                       "author" => "author",
                       "authorExt" => "author",
                       "published" => "publication date",
                       "version" => "version number",
                       "license" => "license type",
                       "system" => "development system",
                       "language" => "language",
                       "desc" => "description",
                       "coverart" => "cover art",
                       "genre" => "genre",
                       "seriesname" => "series name",
                       "seriesnumber" => "episode number",
                       "forgiveness" => "forgiveness",
                       "bafsid" => "Baf's Guide ID",
                       "website" => "Web site URL",
                       "nodownloadreason" => "no-download explanation",
                       "ifids" => "IFIDs",
                       "links" => "download links",
                       "xrefs" => "cross-references",
                       "extreviews" => "external review links");

    // build an array keyed by the descriptive names of the columns
    $changeList = array();
    foreach ($deltas as $colname => $colval) {
        if (isset($deltaDesc[$colname]) && $deltaDesc[$colname] != "")
            $changeList[$deltaDesc[$colname]] = true;
    }

    // return it as a comma-separated list of column descriptions
    return implode(", ", array_keys($changeList));
}


?>