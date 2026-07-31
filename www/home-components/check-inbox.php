<?php
// -------------------------- inbox ----------------------------

// check the inbox
if ($quid) {
    if (!$newCaughtUp || $srow[4] > $newCaughtUp)
        $newCaughtUp = $srow[4];

    // $inboxCnt is returned by the queryComments function, which is
    // defined in commentutil.php and called in pagetpl.php
    if ($inboxCnt) {

        global $nonce;
        echo "<style nonce='$nonce'>\n"
            . ".check-inbox__headline { clear: right; }\n"
            . "</style>\n";

        echo "<div class='headline check-inbox__headline'>"
            . "<span class=headlineRss>"
            . "<a class=\"rss-icon\" href=\"commentlog?user=$quid&mode=inbox&rss\">"
            . "Your Inbox (RSS)</a>"
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
