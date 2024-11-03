<div class=headline id='reviews'><h1 class='unset'>Reviews</h1></div>
<?php
// get the latest reviews
$items = getNewItems($db, 7, NEWITEMS_REVIEWS);

// show the items
$totcnt = count($items);

for ($idx = 0 ; $idx <= 5 ; $idx++)
{
    // get this item
    [$pick, $rawDate, $r] = $items[$idx];

    $eager = ($idx < 4 ? "class='eager'" : "");

    if (($r['flags'] & FLAG_SHOULD_HIDE)) {
        continue;
    }

    // display the item according to its type
    if (ENABLE_IMAGES) {
        global $nonce;
        echo "<style nonce='$nonce'>\n"
            . ".new-item tr:first-child { vertical-align: top }\n"
            . ".new-item td:first-child { padding-right: 1em }\n"
            . "</style>\n";

        echo "<table border=\"0\" cellpadding=\"0\" "
            . "cellspacing=\"0\" class=\"new-item\">"
            . "<tr>"
            . "<td>";
    }

    // show the image: user image if available, otherwise game
    // image, otherwise generic review icon
    if (ENABLE_IMAGES) {
        if ($r["haspic"]) {
            echo "<a href=\"showuser?id={$r['userid']}\">"
                . "<img border=0 width=50 height=50 src=\"showuser?id={$r['userid']}&pic"
                . "&thumbnail=50x50\"></a>";
        } else if ($r["hasart"]) {
            echo "<a href=\"viewgame?id={$r['gameid']}\">"
                . coverArtThumbnail($r['gameid'], 50, $r['pagevsn'])
                . "</a>";
        } else {
            // echo "<a href=\"viewgame?id={$r['gameid']}"
            //     . "&review={$r['reviewid']}\">"
            //     . "<img border=0 src=\"review50.gif\"></a>";
        }
        echo "</td><td>";
    }

    // summarize this review
    echo "<div class=\"new-review\">";

    if (is_null($r['special']))
        echo "<a href=\"showuser?id={$r['userid']}\"><b>"
            . output_encode(htmlspecialcharx($r['username']))
            . "</b></a> reviews ";
    else
        echo "A new review of ";

    echo "<a href=\"viewgame?id={$r['gameid']}\"><i><b>"
        . output_encode(htmlspecialcharx($r['title']))
        . "</b></i></a>";

    if (!is_null($r['special'])) {
        $result = mysql_query("select name from specialreviewers
            where id = '{$r['special']}'", $db);
        echo " - " . mysql_result($result, 0, "name");
    } else {
        echo ": \""
            . output_encode(htmlspecialcharx($r['summary']))
            . "\" ";
    }

    echo showStars($r['rating']);

    echo " - <a $eager href=\"viewgame?id={$r['gameid']}"
        . "&review={$r['id']}\">See full review</a>";

    echo "</div>";
    if (ENABLE_IMAGES)
        echo "</td>";

    if (ENABLE_IMAGES)
        echo "</tr></table>";
}



?>
<p><span class='details'><a href='allnew?reviews'>See the full list...</a></span></p>
