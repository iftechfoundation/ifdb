<?php
// ----------------------------- IFDB Recommends ----------------------

include_once "searchutil.php";

// This is used to randomly shuffle the recommendations
function sortBySortorder($a, $b)
{
    return $a['sortorder'] - $b['sortorder'];
}


$recs = array();
$term = "";
if ($loggedIn) {
    // If the user is logged in, don't recommend games the user already knows about
    $term = "played:no willplay:no wontplay:no reviewed:no rated:no";
}
$searchType = "game";
$sortby = "ratu";  // Sort the highly rated games to the top of the results.
$maxpicks = 12;    // Get the first twelve results. (We want extras so we're not always displaying the same games.) 
$limit = "limit 0, $maxpicks";
$browse = 1;
$count_all_possible_rows = false;


// run the search for highly-rated games
list($recs, $rowcnt, $sortList, $errMsg, $summaryDesc, $badges,
    $specials, $specialsUsed, $orderBy) =
    doSearch($db, $term, $searchType, $sortby, $limit, $browse, $count_all_possible_rows);


// show some recommendations
if (count($recs) >= 2) {

    // randomly re-sort the list
    for ($i = 0 ; $i < count($recs) ; $i++)
        $recs[$i]['sortorder'] = rand();
    usort($recs, "sortBySortorder");

    // start the section
    echo "<div class='headline' id='ifdb-recommends'><h1 class='unset'>IFDB Recommends</h1></div>";
    echo "<div>";
    global $nonce;
    echo "<style nonce='$nonce'>\n"
        . ".ifdb-recommends__artLink { margin-right: 1em; }\n"
        . "</style>\n";

    // show the first five entries
    for ($i = 0 ; $i < count($recs) && $i < 5 ; $i++) {

        // get the fields from the game record
        $r = $recs[$i];
        $gameid = $r['id'];
        $title = htmlspecialcharx($r['title']);
        $author = htmlspecialcharx($r['author']);
        $author = collapsedAuthors($author);
        $hasart = $r['hasart'];
        $pagevsn = $r['pagevsn'];
        list($summary, $len, $trunc) = summarizeHtml($r['description'], 140);
        $summary = fixDesc($summary);

        // display the game information
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
    // link to more recommendations and explain the source
    echo "<p><span class=details>";
    echo "<a href='/search?searchbar=played%3Ano+willplay%3Ano+wontplay%3Ano+reviewed%3Ano+rated%3Ano&sortby=ratu'>More recommendations</a> | ";
    echo helpWinLink("help-crossrec", "Why did IFDB recommend these?");
    echo "</span></div>";
}
// ---------------------------- end IFDB Recommends ------------------------
         ?>
