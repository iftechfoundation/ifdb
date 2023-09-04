<?php

include_once "gameinfo.php";
include_once "rss.php";
include_once "reviews.php";
include_once "news.php";

define("GAME_RSS_ALL", 1);      // include all updates
define("GAME_RSS_REVIEWS", 2);  // include only member reviews
define("GAME_RSS_DLS", 3);      // include only updates to download links
define("GAME_RSS_NEWS", 4);     // include only news items


//
// Get the RSS items for this game.  Returns an array of items; each
// item is an array(date, XML item body text).
//
//   $db is the database connection to use
//   $id is the game ID
//   $feedType is one of the GAME_RSS_xxx identifiers
//   $gameTitle is the title of the game
//   $links is the set of download links, as returned from getGameInfo()
//   $extFeed is true if this is a feed for an item OTHER THAN the game
//       itself (for example, the feed for games linked to an author).
//       If this is true, we'll include the game's name in the title and
//       description of each news item.
//
function getGameRssItems($db, $id, $feedType, $gameTitle, $links, $extFeed)
{
    // start with an empty news item list
    $items = array();

    // quote the game ID
    $qid = mysql_real_escape_string($id, $db);

    // if they want reviews, add those items
    if ($feedType == GAME_RSS_REVIEWS || $feedType == GAME_RSS_ALL) {

        $selectMemberReviews = getReviewQueryByGame(
            $db, $id,
            "reviews.review is not null and reviews.special is null");

        // query the reviews
        $result = mysql_query(
            "$selectMemberReviews order by moddate desc", $db);

        // build the item
        for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {

            // fetch this review
            $r = mysql_fetch_array($result, MYSQL_ASSOC);

            // get the fields
            $rtitle = htmlspecialcharx($r['summary']);
            $rid = $r['reviewid'];
            $rlink = htmlspecialcharx(
                get_root_url() . "viewgame?id=$id&review=$rid");
            $rdate = date("D, j M Y H:i:s ", strtotime($r['moddate'])) . 'UT';
            list($rdesc, $len, $trunc) = summarizeHtml($r['review'], 210);
            $rdesc = htmlspecialcharx(fixDesc($rdesc));
            $rauth = htmlspecialcharx($r['username']);

            // for external feeds, add the game title to the title and desc
            $ofGame = ($extFeed ? " of $gameTitle" : "");

            // Set up the item title and body according to the feed type
            if ($feedType == GAME_RSS_REVIEWS) {
                // reviews only - no need to say "review" in each item,
                // but do show who wrote it in the body
                $rdesc = "Review$ofGame by $rauth: $rdesc";
            } else {
                // full feed - mark each item as a review in the title
                $rtitle = "New review$ofGame by $rauth: \"$rtitle\"";
            }

            // build the item XML
            $item = "<item>"
                    . "<title>$rtitle</title>"
                    . "<description>$rdesc</description>"
                    . "<link>$rlink</link>"
                    . "<pubDate>$rdate</pubDate>"
                    . "<guid>$rlink</guid>"
                    . "</item>";

            // add it to the master list
            $items[] = array($r['moddate'], $item);
        }
    }

    // get page updates if desired
    if ($feedType == GAME_RSS_DLS || $feedType == GAME_RSS_ALL) {

        // note the original links in the current record
        $nextLinks = array();
        foreach ($links as $l)
            $nextLinks[$l['url']] = $l;

        // query the update history
        $result = mysql_query(
            "select
               h.pagevsn, h.deltas, h.moddate, u.name
             from
               games_history as h
               join users as u on u.id = h.editedby
             where h.id = '$qid'
             order by h.pagevsn desc", $db);

        for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {

            // fetch the row
            list($nvsn, $deltas, $ndate, $nuser) =
                mysql_fetch_row($result);

            // the deltas in a version record store the OLD values for this
            // version that were updated in the NEXT version of the page,
            // so this record actually represents updates to the next version
            $nvsn = $nvsn + 1;

            // quote/reformat the fields
            $nuser = htmlspecialcharx($nuser);
            $rssdate = date("D, j M Y H:i:s ", strtotime($ndate)) . 'UT';
            $nlink = htmlspecialcharx(
                get_root_url() . "viewgame?id=$id&version=$nvsn");
            $deltas = unserialize($deltas);

            // build the item XML, according to what kind of update they want
            if ($feedType == GAME_RSS_DLS) {

                // they only want download link updates
                if (!isset($deltas["links"]))
                    continue;

                // rebuild it as a url-keyed array
                $oldLinks = array();
                foreach ($deltas['links'] as $l)
                    $oldLinks[$l['url']] = $l;

                // build a list of changes
                $linkDiffs = array();
                foreach ($nextLinks as $url=>$l) {
                    // if this one isn't set in the old links, it's new;
                    // if the title or description has changed, it's new
                    $o = isset($oldLinks[$url]) ? $oldLinks[$url] : false;
                    if (!$o)
                        $linkDiffs[] = array("added", $l);
                    else if ($o['desc'] != $l['desc']
                             || $o['title'] != $l['title'])
                        $linkDiffs[] = array("updated", $l);
                }

                // this is the basi for the next round
                $nextLinks = $oldLinks;

                // if there are no diffs, there's nothing to report
                if (count($linkDiffs) == 0)
                    continue;

                // build the display list of updated files
                $ltitles = array();
                $ldescs = array();
                foreach ($linkDiffs as $l) {
                    $mode = $l[0];
                    $l = $l[1];
                    $ltitles[] = htmlspecialcharx($l['title']);
                    $d = $l[0] . "$mode " . htmlspecialcharx($l['title']);
                    if ($l['desc'])
                        $d .= " (\"" . htmlspecialcharx($l['desc']) . "\")";
                    $ldescs[] = $d;
                }

                // build strings out of the lists
                $ltitles = implode(", ", $ltitles);
                $ldescs = implode("; ", $ldescs);

                // for an external listing, add the game title
                $ttl = ($extFeed ? "$gameTitle external links " : $ltitles);

                // build the item XML
                $item = "<item>"
                        . "<title>Updates to $ttl</title>"
                        . "<description>$nuser $ldescs</description>"
                        . "<pubDate>$rssdate</pubDate>"
                        . "<guid>$nlink</guid>"
                        . "</item>";

            } else {

                // they want all updates - get the change description
                $changeList = getDeltaDesc($deltas);

                // add the game title for external feed
                $to = ($extFeed ? " to $gameTitle" : "");

                // build the item XML
                $item = "<item>"
                        . "<title>Page update$to by $nuser</title>"
                        . "<description>New $changeList</description>"
                        . "<pubDate>$rssdate</pubDate>"
                        . "<guid>$nlink</guid>"
                        . "</item>";
            }

            // add it to the master list
            $items[] = array($ndate, $item);
        }
    }

    // get news item updates if desired
    if ($feedType == GAME_RSS_NEWS || $feedType == GAME_RSS_ALL) {

        // generate the title prefix - include the game title for external
        // feeds, but not for feeds for this game only (as it would be
        // redundant to repeat the same game title on every item)
        $onGame = ($extFeed ? " for $gameTitle" : "");

        // add the news history
        queryNewsRss($items, $db, "G", $qid, 50,
                     "makeGameRSSTitle", $onGame);
    }

    // return the item list
    return $items;
}

function makeGameRSSTitle($onGame, $headline)
{
    return "News$onGame: $headline";
}


?>
