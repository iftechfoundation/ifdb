<div class=headline id='lists'><h1 class='unset'>Lists</h1>
    <span class=headlineRss>
        <a href="/editlist?id=new">Create a recommended list</a>
    </span>
</div>

<p><span class=details></span></p>
<?php

// get the latest lists
$items = getNewItems($db, 10, NEWITEMS_LISTS);

// show the items

for ($idx = 0 ; $idx <= 8; $idx++)
{
    // get this item
    [$pick, $rawDate, $l] = $items[$idx];

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

    // pull out the list record
    $itemcnt = $l['itemcnt'];
    $itemS = $itemcnt == 1 ? "" : "s";
    $title = htmlspecialcharx($l['title']);
    $username = htmlspecialcharx($l['username']);

    // show the image: user image if available, otherwise the
    // generic list icon
    if (ENABLE_IMAGES) {
        if ($l["haspic"]) {
            echo "<a href=\"showuser?id={$l['userid']}\">"
                . "<img border=0 width=50 height=50 src=\"showuser?id={$l['userid']}&pic"
                . "&thumbnail=50x50\"></a>";
        } else {
            // echo "<a href=\"viewlist?id={$l['id']}\">"
            //     . "<img border=0 src=\"reclist50.gif\"></a>";
        }
        echo "</td><td>";
    }

    // summarize it
    echo "<div class=\"new-list\">"
        . "<a href=\"viewlist?id={$l['id']}\"><b>$title</b></a> "
        . "by <a href=\"showuser?id={$l['userid']}\"><b>$username</b></a>, "
        . "<span class=details>$itemcnt item$itemS</span></div>";

    if (ENABLE_IMAGES)
        echo "</tr></table>";
}
?>
<p><span class=details>
    <a href="/search?browse&list">Browse lists</a> |
    <a href="/search?list">Search lists</a>
</span></p>