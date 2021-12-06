<?php
// -------------------------- inbox ----------------------------

// check the inbox
if ($quid) {
    list($inbox, $inboxCnt) =
        queryComments($db, "inbox", $quid, "limit 0, 1", $caughtUpDate, false);
    if (!$newCaughtUp || $srow[4] > $newCaughtUp)
        $newCaughtUp = $srow[4];

    if ($inboxCnt) {

        echo "<div class=headline style=\"clear:right;\">"
            . "<span class=headlineRss>"
            . "<a href=\"commentlog?user=$quid&mode=inbox&rss\">"
            . "<img src=\"img/blank.gif\" class=\"rss-icon\">Your Inbox (RSS)</a>"
            . "</span>"
            . "Your Discussions</div>";

        $new = $since = "";
        if ($caughtUpDate) {
            $new = " new";
            $since = "since ". date("F j, Y", strtotime($caughtUpDate));
        }
        
        list($crow) = $inbox[0];
        $cid = $crow[0];
        $cdate = $crow[5];
        $newCaughtUp = $crow[4];
        echo "You have $inboxCnt $new item" . ($inboxCnt == 1 ? "" : "s")
            . " $since in your <a href=\"commentlog?mode=inbox\">"
            . "comment inbox</a>"
            . ($caughtUpDate ? "" : " (latest on $cdate)")
            . ".<br>";
    }
}

         ?>
