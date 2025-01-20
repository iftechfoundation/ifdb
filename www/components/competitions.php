<div class=headline id='competitions'><h1 class='unset'>Competitions</h1>
    <span class=headlineRss>
        <a href="/editcomp?id=new">Add a competition listing</a>
    </span>
</div>

<p><a href="https://ifcomp.org/">IF Comp</a> | <a href="https://www.springthing.net/">Spring Thing</a> | <a href="https://xyzzyawards.org/">XYZZY Awards</a></p>

<?php

// get the latest competitions and competition news
list($items, $game_filter_was_applied) = getNewItems($db, 7, NEWITEMS_COMPS | NEWITEMS_COMPNEWS);

for ($idx = 0 ; $idx <= 7; $idx++)
{
    // get this item
    [$pick, $rawDate, $row] = $items[$idx];

    $eager = ($idx < 4 ? "class='eager'" : "");

    if ($pick == 'N')
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

        $href = "viewcomp?id=$gid";
        $divclass = "new-comp-news";

        // summarize the item
        echo "<div class=\"$divclass\">"
            . "News on <a href=\"$href\">$gtitle</a>: "
            . "<b>$nhead</b> "
            . "<span class=notes><i>$ncre</i></span> "
            . "<a href=\"newslog?newsid=$nid\">Details</a>"
            . "</span></div>";
    }
    else if ($pick == 'C')
    {
        // it's a competition
        $c = $row;

        // pull out the competition item
        $cid = $c["compid"];
        $ctitle = htmlspecialcharx($c["title"]);
        $cdate = $c["fmtdate"];


        // summarize the item
        echo "<div class=\"new-competition\">"
            . "<a href=\"viewcomp?id=$cid\">"
            . "$ctitle</a> <span class=notes><i>created $cdate</i></span>"
            . "<br><div class=indented>"
            . "</div>"
            . "</div>";
    }

}

?>
<p><span class=details>
    <a href="/search?browse&comp">Browse competitions</a> |
    <a href="/search?comp">Search competitions</a>
</span></p>
