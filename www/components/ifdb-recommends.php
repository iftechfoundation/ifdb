<?php
// ----------------------------- IFDB Recommends ----------------------

$recs = array();
$maxpicks = 12;
$overloaded = true;

// if our recommendation list is empty, pick some random 4- and 5-star
// games as recommendations
if (count($recs) == 0) {

    if ($debugflag) echo "falling back on default method<br>";

    // if we're logged in, don't pick games we've already rated
    $exclJoin = "";
    $exclWhere = "";
    if ($loggedIn) {
        $exclJoin = "left outer join reviews as r2"
                    . " on games.id = r2.gameid and r2.userid = '$quid' "
                    . "left outer join playedgames as pg"
                    . " on games.id = pg.gameid and pg.userid = '$quid' "
                    . "left outer join wishlists as wl"
                    . " on games.id = wl.gameid and wl.userid = '$quid' "
                    . "left outer join unwishlists as uw"
                    . " on games.id = uw.gameid and uw.userid = '$quid' ";
        $exclWhere = "and r2.userid is null "
                     . " and pg.userid is null"
                     . " and wl.userid is null"
                     . " and uw.userid is null";
    }
    $gameRatingsView = getGameRatingsView($db);

    // pick the top-rated games
    $result = mysql_query(
        "select
           games.id as gameid,
           games.title as title,
           games.author as author,
           games.`desc` as `desc`,
           (games.coverart is not null) as hasart,
           games.pagevsn,
           starsort
         from
           games
           join $gameRatingsView on games.id = gameid
           $exclJoin
         where
           not (games.flags & " . FLAG_SHOULD_HIDE . ")
           $exclWhere
         order by
           starsort desc
         limit
           0, $maxpicks", $db);

    // fetch the results
    for ($recs = array(), $i = 0 ; $i < mysql_num_rows($result) ; $i++)
        $recs[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // the source is just generic top-rated games
    $recsrc = 'generic';
}

function sortBySortorder($a, $b)
{
    return $a['sortorder'] - $b['sortorder'];
}

// show some recommendations
if (count($recs) >= 2) {

    // randomly re-sort the list
    for ($i = 0 ; $i < count($recs) ; $i++)
        $recs[$i]['sortorder'] = rand();
    usort($recs, "sortBySortorder");

    // stash the recommendations back in the session
    $_SESSION['ifdb_recommendations'] = $recs;
    $_SESSION['ifdb_recommendations_source'] = $recsrc;

    // start the section
    echo "<div class='headline' id='ifdb-recommends'><h1 class='unset'>IFDB Recommends</h1>"
      .($overloaded ? "<span class='headlineRss'><a href='/search?searchbar=played%3Ano+willplay%3Ano+wontplay%3Ano+reviewed%3Ano+rated%3Ano'>More recommendations</a></span>" : "")
      ."</div><div>";
    global $nonce;
    echo "<style nonce='$nonce'>\n"
        . ".ifdb-recommends__artLink { margin-right: 1em; }\n"
        . "</style>\n";


    // show the first three entries
    for ($i = 0 ; $i < count($recs) && $i < 5 ; $i++) {

        // get the fields from the game record
        $r = $recs[$i];
        $gameid = $r['gameid'];
        $title = htmlspecialcharx($r['title']);
        $author = htmlspecialcharx($r['author']);
        $author = collapsedAuthors($author);
        $hasart = $r['hasart'];
        $pagevsn = $r['pagevsn'];
        list($summary, $len, $trunc) = summarizeHtml($r['desc'], 140);
        $summary = fixDesc($summary);

         // display it
        echo "<p>";
        if ($hasart)
            echo "<table border=0 cellspacing=0 cellpadding=0>"
                . "<tr valign=top><td>"
                . "<a href=\"viewgame?id=$gameid\" class=\"ifdb-recommends__artLink\" aria-label=\"$title\">"
                . coverArtThumbnail($gameid, 70, $pagevsn)
                . "</a></td><td>";

        echo "<a href=\"viewgame?id=$gameid\"><i><b>$title</b></i></a>, "
            . "by $author<br>"
            . "<div class=indented><span class=details>"
            . "<i>$summary</i></span></div>";

        if ($hasart)
            echo "</td></tr></table>";
    }

    // explain the source
    echo "<p><span class=details><i>";
    if ($recsrc == 'generic' && !$overloaded) {
        echo "These are a few randomly-selected games with high
             average member ratings.  If you ";
        if (!$loggedIn)
            echo "<a href=\"login\">log in</a> and ";
        echo "rate a few games yourself, IFDB can offer customized
              recommendations (".helpWinLink("help-crossrec", "explain").").";
    } else {
        echo helpWinLink("help-crossrec", "Why did IFDB recommend these?");
    }
    echo "</i></span></div>";
}
// ---------------------------- end IFDB Recommends ------------------------
         ?>
