<?php
        // show the header if (a) we have any recommendations to show,
        // or (b) we're in the explicit Recommendations view mode - in
        // the latter case, since the user asked for recommendations,
        // show the header even if we don't have anything to list
        $divStarted = false;
        if (!$crossrecs && !$ecrossrecs
            && !$recView && ($lists || $polls)) {
            $divStarted = true;
            echo "<h2><i>$title</i> on IFDB</h2>"
                . "<div class=indented>";
        }
        else if ($lists || $polls || $crossrecs || $ecrossrecs || $recView) {
            $divStarted = true;
            echo "<h2>If you enjoyed <i>$title</i>...</h2>"
                . "<div class=indented>";
        }

        // if we have recommendations, show them
        if ($lists || $polls || $crossrecs || $ecrossrecs) {

            // display the cross-recommendations for games
            if ($crossrecs || $ecrossrecs) {
                echo "<h3>Related Games</h3>";

                $these = (count($ecrossrecs) + count($crossrecs) == 1
                          ? "this game" : "these games");

                if ($ecrossrecs && $crossrecs && count($ecrossrecs) < 3) {
                    echo "Other members recommend $these for "
                        . "people who like <i>$title</i>, or gave both "
                        . "high ratings:";
                } else if ($ecrossrecs) {
                    echo "Other members recommend $these for "
                        . "people who like <i>$title</i>:";
                } else {
                    echo "People who like <i>$title</i> also gave high "
                        . "ratings to $these:";
                }

                echo "<div class=indented>";

                // merge the two arrays, starting with explicit suggestions
                $allrecs = array_merge($ecrossrecs, $crossrecs);

                // show up to three items
                $allcnt = min(count($allrecs), 3);
                for ($i = 0 ; $i < $allcnt ; $i++) {
                    // get this item
                    $c = $allrecs[$i];

                    // format the data for display
                    $crid = mysql_real_escape_string($c['id'], $db);
                    $crtitle = htmlspecialcharx($c['title']);
                    $crauthor = htmlspecialcharx($c['author']);
                    $crdescInfo = summarizeHtml($c['desc'], 240);
                    $crdesc = fixDesc($crdescInfo[0]);
                    $crart = $c['hasart'];
                    $crversion = $c['pagevsn'];

                    $gameRatingsView = getGameRatingsView($db);
                    $result = mysql_query(
                        "select avgRating, numRatingsInAvg, numRatingsTotal
                         from $gameRatingsView
                         where gameid = '$crid'", $db);
                    if ($result)
                        list($crravg, $crrcnt, $crrtot) =
                            mysql_fetch_row($result);
                    else
                        $crravg = $crrcnt = $crrtot = 0;

                    echo "<p>";
                    if ($crart)
                        echo "<table class=grid cellspacing=0 cellpadding=0 "
                            . "border=0><tr><td>"
                            . "<a href=\"viewgame?id=$crid\">"
                            . coverArtThumbnail($crid, 80, $crversion)
                            . "</a></td><td>";

                    echo "<a href=\"viewgame?id=$crid\"><b>$crtitle</b></a>"
                        . ", by $crauthor<br>";

                    if ($crrcnt)
                        echo "<span class=details>"
                            . "Average member rating: " .showStars($crravg)
                            . " ($crrcnt rating" . ($crrcnt == 1 ? "" : "s")
                            . ")</span><br>";

                    echo "<span class=details>$crdesc</span>";

                    if ($crart)
                        echo "</td></tr></table>";
                }

                echo "<p><span class=details>";
                if (count($ecrossrecs) > 3) {
                    echo "<a href=\"crossrec?game=$id\">"
                        . "See more suggestions</a> - ";
                }
                echo "<a href=\"crossrec?game=$id&edit\">"
                    . ($myCrossRecs ?
                       "View or edit my suggestions ($myCrossRecs)" :
                       "Suggest a game")
                    . "</a></span>"

                    . "</div>";
            }

            // display the lists
            if ($lists) {
                echo "<h3>Recommended Lists</h3>"
                    . "<i>$title</i> appears in the "
                    . "following Recommended Lists:"
                    . "<div class=indented>";

                for ($i = 0 ; $i < 3 && $i < count($lists) ; $i++) {
                    // format the list data for display
                    $l = $lists[$i];
                    $listid = $l['listid'];
                    $listname = htmlspecialcharx($l['title']);
                    $listdescInfo = summarizeHtml($l['desc'], 240);
                    $listdesc = fixDesc($listdescInfo[0]);
                    $listuserid = $l['userid'];
                    $listusername = htmlspecialcharx($l['username']);

                    // display it
                    echo "<p><a href=\"viewlist?id=$listid\">"
                        . "<b>$listname</b></a>"
                        . " by <a href=\"showuser?id=$listuserid\">"
                        . "$listusername</a><br>"
                        . "<span class=notes>$listdesc</span>";
                }

                if (count($lists) > 3)
                    echo "<p><span class=details>"
                        . "<a href=\"alllists?game=$id\">See all lists "
                        . "mentioning this game</a></span>";

                echo "</div>";
            }

            // display the polls
            if ($polls) {
                echo "<h3>Polls</h3>"
                    . "The following polls include votes for <i>$title</i>:"
                    . "<div class=indented>";

                for ($i = 0 ; $i < 3 && $i < count($polls) ; $i++) {
                    // format the poll data for display
                    $p = $polls[$i];
                    $pollid = $p['pollid'];
                    $pollname = htmlspecialcharx($p['title']);
                    $polldescInfo = summarizeHtml($p['desc'], 240);
                    $polldesc = fixDesc($polldescInfo[0]);
                    $polluserid = $p['userid'];
                    $pollusername = htmlspecialcharx($p['username']);

                    // display it
                    echo "<p><a href=\"poll?id=$pollid\"><b>$pollname</b></a>"
                        . " by <a href=\"showuser?id=$polluserid\">"
                        . "$pollusername</a><br>"
                        . "<span class=notes>$polldesc</span>";
                }

                if (count($polls) > 3)
                    echo "<p><span class=details>"
                        . "<a href=\"allpolls?game=$id\">"
                        . "See all polls with votes for this game</a></span>";

                echo "</div>";
            }

        } else if ($recView) {

            // We're explicitly in the Recommendations view, but we don't
            // have any recommendations to show.

            // check to see if we've reviewed this game
            $reviewIt = false;
            if ($curuser) {
                $result = mysql_query(
                    "select id from reviews
                     where gameid='$qid' and userid='$curuser'", $db);
                if (mysql_num_rows($result) > 0) {
                    $reviewIt = "rating a few other games you like";
                }
            }
            if (!$reviewIt) {
                $reviewIt = "rating this game and a few other games "
                            . "you also like";
            }

            ?>
            IFDB doesn't currently have any recommendations for
            similar games.  Our recommendations depend on input
            from our members, so you can help!  If you know of other
            games that you'd recommend to people who liked
            <i><?php echo $title ?></i>, you can help by...

            <ul class=doublespace>
               <li><a href="editlist?id=new">creating a Recommended
                  List</a> that includes <i><?php echo $title ?></i> and
                  other games you like for similar reasons;

               <li><?php echo $reviewIt ?>.
            </ul>

            <p>We use member ratings and Recommended Lists to
            offer suggestions, so the more input our members provide,
            the better IFDB's suggestions will become.

            <?php
        }

        // end the indented division if we started it
        if ($divStarted)
            echo "</div>";

        if ($recView)
            echo "<p><a href=\"viewgame?id=$id\">Go to the "
                . "main page for this game</a>";
?>