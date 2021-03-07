<?php

include_once "commentutil.php";


function listMatchItem($match, $num, $showArt, $rating, $hrefTarget)
{
    $id = htmlspecialcharx($match['id']);
    $title = htmlspecialcharx($match['title']);
    $author = htmlspecialcharx($match['author']);
    $pubyear = $match['pubyear'];
    $art = $showArt && $match['hasart'];

    if ($hrefTarget != "")
        $hrefTarget = " target=\"$hrefTarget\"";

    if ($art)
        echo "<a href=\"viewgame?id=$id\"$hrefTarget>"
            . "<img src=\"viewgame?id=$id&coverart&thumbnail=100x100\""
            . " align=left border=0 "
            . " style=\"margin-right: 1em; margin-bottom: 0.5em;\"></a>";

    if ($num)
        echo "<b>$num</b>. ";

    $nl = ($art ? "<br>" : " ");
    $comma = ($art ? "<br>" : ", ");

    echo "<a href=\"viewgame?id=$id\"$hrefTarget><b>$title</b></a>$comma"
        . "by $author"
        . ($pubyear != "" ? "$nl<span class=details>($pubyear)</span>" : "")
        . ($rating && $rating[1] != 0
           ? ("<br><span class=details>Average member rating: "
              . showStars($rating[0])
              . " ($rating[1] rating" . ($rating[1] > 1 ? "s" : "") . ")"
              . "</span>")
           : "")
        . ($art ? "<br clear=left>" : "");
}

function showRecList($db, $qlistid, $ownerid, $ownername, $ownerloc,
                     $title, $desc, $moddate, $items, $hrefTarget,
                     $showComments, $showFlagged)
{
    // fix up the items for display
    $ownername = htmlspecialcharx($ownername);
    $ownerloc = htmlspecialcharx($ownerloc);
    $title = htmlspecialcharx($title);
    $desc = fixdesc($desc);

    // show the list name, author, and overview
    echo "<h1>$title</h1>"
        . "<span class=notes><i>Recommendations by "
        . "<a href=\"showuser?id=$ownerid\">$ownername</a>"
        . ($ownerloc != "" ? " ($ownerloc)" : "")
        . "</i></span><p>$desc<p>";

    // generate the comment controls if desired
    $reqComments = isset($_REQUEST['comments']);
    if ($showComments) {

        // count the comments
        $commentCnt = countComments($db, "L", $qlistid);

        // generate the controls
        $commentCtl = "";
        if ($reqComments) {
            // currently viewing comments - show a Return-to-List control
            $commentCtl .= "<a href=\"viewlist?id=$qlistid\">Return to "
                           . "the list</a> - ";
        } else if ($commentCnt > 0) {
            // there are existing comments - show a link to view them
            $commentCtl .= "<a href=\"viewlist?id=$qlistid&comments\">"
                           . "View comments ($commentCnt)</a> - ";
        }

        // always offer to add a new comment
        $commentCtl .= "<a href=\"listcomment?list=$qlistid\">"
                       . "Add a comment</a>";

        // add the controls
        echo "<p><span class=details>$commentCtl</span><p>";

        // if we're viewing the comments, show the comment page
        if ($reqComments) {

            // show the comments
            showCommentPage($db, $ownerid, $qlistid, "L",
                            "viewlist?id=$qlistid",
                            "listcomment?list=$qlistid",
                            25, "Comments on this list", "comments");

            // in comment mode, we show only the comment page, not the list,
            // so we're done now
            return;
        }
    }

    // show the items
    if (!$showFlagged) {
        for ($i = $num = 0 ; $i < count($items) ; $i++) {
            $item = $items[$i];
            $match = $item['matches'][0];
            $flags = $match['flags'];
            $shouldHide = $flags & FLAG_SHOULD_HIDE;
            if ($shouldHide) {
                $currentUrl = $_SERVER['REQUEST_URI'];
                if (strpos($currentUrl, '?') === false) {
                    $currentUrl .= "?";
                }
                $showAllLink = htmlspecialchars( $currentUrl, ENT_QUOTES, 'UTF-8' ) . "&showFlagged=1";
                echo "<p><div class=restricted>Some results were hidden. "
                        . "<a href=\"$showAllLink\">See all results</a></div></p>";
                break;
            }
        }
    }

    for ($i = $num = 0 ; $i < count($items) ; $i++) {
        // get the item
        $item = $items[$i];
        $tuid = mysql_real_escape_string($item['tuid'], $db);

        // if the item is empty or has been deleted, skip it
        if ($tuid == "" || $tuid == "(deleted)")
            continue;

        // count the item
        $num++;

        // Get the game info, which is in a separate 'matches'
        // sub-array.  This is a sub-array because we can have
        // several potential matches when resolving a title; but
        // by the time we display a list or preview a list, the
        // matches will have been resolved down to a single item.
        $match = $item['matches'][0];
        $flags = $match['flags'];
        $shouldHide = $flags & FLAG_SHOULD_HIDE;

        if (!$showFlagged && $shouldHide) {
            continue;
        }

        // get the comments, formatted for display
        $comments = fixdesc($item['comments']);

	    // get the game ratings table, taking into account the user's sandbox
		$gameRatingsView = getGameRatingsView($db);

        // get the game's average rating
        $rating = false;
        if (!$shouldHide) {
            $result = mysql_query(
                "select avgRating, numRatingsInAvg
                from $gameRatingsView
                where gameid='$tuid'", $db);
            if (mysql_num_rows($result) > 0)
                $rating = mysql_fetch_row($result);
        }

        // show the number and list the item
        echo "<p>";
        listMatchItem($match, $num, true, $rating, $hrefTarget);

        // show the item comment, if any
        if ($comments) {
            echo "<br><span class=details><i>$ownername says:</i><br>"
                . "</span><div class=indented>$comments<br></div>";
        }
    }
}
?>