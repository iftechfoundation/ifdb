<div class=headline id='games'>
    <h1 class='unset'>Games</h1>
    <span class=headlineRss>
        <a href="/editgame?id=new">Add a game listing</a>
    </span>
</div>

<ul class='horizontal'>
    <li><a href="/search?browse&sortby=lnew">New</a><li>
    <li><a href="/search?browse">Top</a></li>
    <li><a href="/search?searchbar=added%3A60d-">Hot</a></li>
    <li><a href="/random">Random</a></li>
    <li><a href="/search?sortby=lnew&searchfor=%23reviews%3A0+wontplay%3Ano">Unreviewed</a></li>
    <li><a href="/search">Advanced Search</a></li>
</ul>
<?php
define("ENABLE_IMAGES", 1);

// get the latest games and game news
list($items, $game_filter_was_applied) = getNewItems($db, 6, NEWITEMS_GAMES | NEWITEMS_GAMENEWS);

// show the items
$totcnt = count($items);

for ($idx = 0 ; $idx <= 5; $idx++)
{
    // get this item
    [$pick, $rawDate, $row] = $items[$idx];

    $eager = ($idx < 4 ? "class='eager'" : "");

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

    if ($pick == 'G')
    {
        // it's a game
        $g = $row;

        // show the image: game cover art if available, otherwise the
        // generic game icon
        if (ENABLE_IMAGES) {
            if ($g["hasart"]) {
                echo "<a href=\"viewgame?id={$g['id']}\">"
                    . coverArtThumbnail($g['id'], 50, $g['pagevsn'])
                    . "</a>";
            } else {
                // echo "<a href=\"viewgame?id={$g['id']}\">"
                //     . "<img border=0 src=\"game50.gif\"></a>";
            }
            echo "</td><td>";
        }

        // summarize this game
        echo "<div class=\"new-game\">"
            . "<a $eager href=\"viewgame?id={$g['id']}\"><b><i>"
            . htmlspecialcharx($g['title'])
            . "</i></b></a>, by "
            . htmlspecialcharx($g['author']);

        if ($g['devsys']) echo " <div class=details>{$g['devsys']}</div>";

        echo "</div>";
    }
    else if ($pick == 'N')
    {
        // it's a news item
        $n = $row;

        // pull out the game news item
        $gid = $n['sourceID'];
        $gtitle = htmlspecialcharx($n['sourceTitle']);
        $nid = $n['newsID'];
        $ncre = $n['createdFmt'];
        $nmod = $n['modifiedFmt'];
        $nuid = $n['userID'];
        $nuname = htmlspecialcharx($n['userName']);
        $nuidOrig = $n['origUserID'];
        $nunameOrig = htmlspecialcharx($n['origUserName']);
        $nhead = htmlspecialcharx($n['headline']);

        $divclass = "new-game-news";
        $href = "viewgame?id=$gid";

        // show the image: user image if available, otherwise game
        // image, otherwise generic review icon
        if (ENABLE_IMAGES) {
            if (isset($n["haspic"]) && $n["haspic"]) {
                echo "<a href=\"showuser?id={$n['userID']}\">"
                    . "<img border=0 width=50 height=50 src=\"showuser?id={$n['userID']}&pic"
                    . "&thumbnail=50x50\"></a>";
            } else if ($n["hasart"]) {
                echo "<a href=\"viewgame?id={$n['gameid']}\">"
                    . coverArtThumbnail($gid, 50, $n['pagevsn'])
                    . "</a>";
            } else {
                // echo "<a href=\"newslog?newsid=$nid\">"
                //     . "<img border=0 src=\"news50.gif\"></a>";
            }
            echo "</td><td>";
        }

        // summarize the item
        echo "<div class=\"$divclass\">"
            . "News on <a href=\"$href\">$gtitle</a>: "
            . "<b>$nhead</b> "
            . " <span class=details>"
            . "<a href=\"newslog?newsid=$nid\">Details</a>"
            . "</span>"
            . "</div>";
    }

    if (ENABLE_IMAGES)
        echo "</tr></table>";
}

?>
<p><span class='details'><a href='search?browse&sortby=lnew'>See the full list...</a></span></p>