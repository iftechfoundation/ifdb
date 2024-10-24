<?php
//  ----------------------  poll sampler box - query -----------------------

$sandbox = 0;

// if the user is logged in, look up their sandbox
$curuser = $_SESSION['logged_in_as'] ?? null;
if ($curuser) {
    $result = mysqli_execute_query($db, "select sandbox from users where id=?", [$curuser]);
    [$sandbox] = mysql_fetch_row($result);
}

// set up the base query for polls
$baseQuery = "select
                p.pollid, p.title, p.`desc`, p.created, p.userid,
                count(v.gameid) as votecnt,
                count(distinct v.gameid) as gamecnt,
                max(v.votedate) as lastvotedate,
                u.name
              from
                polls as p
                left outer join pollvotes as v on v.pollid = p.pollid
                join users as u on u.id = p.userid
              where
                sandbox in (0, ?)
              group by
                p.pollid";
$limit = "limit 0, 5";

$rows1 = [];
$rows2 = [];
$rows3 = [];

// query the most recently created polls
$result = mysqli_execute_query($db,
    "$baseQuery order by p.created desc $limit", [$sandbox]);
while ($row = mysql_fetch_row($result))
    $rows1[] = $row;

// add the most recently active polls (i.e., latest vote)
$result = mysqli_execute_query($db,
    "$baseQuery order by lastvotedate desc $limit", [$sandbox]);
while ($row = mysql_fetch_row($result))
    $rows2[] = $row;

// add the most popular polls (most votes)
$result = mysqli_execute_query($db,
    "$baseQuery order by votecnt desc, gamecnt desc $limit", [$sandbox]);
while ($row = mysql_fetch_row($result))
    $rows3[] = $row;

function addPollRows(&$dst, &$src, $allRows, $amount)
{
    foreach (range(1, $amount) as $i) {
        // add the next element of src not in dst
        if (addUniquePollRow($dst, $src))
            continue;

        // if that failed, fall back to the master list
        addUniquePollRow($dst, $allRows);
    }
}
function addUniquePollRow(&$dst, &$src)
{
    // search for an element of src not in dst
    foreach ($src as $i => $srcEle) {
        // Remove items as they are scanned
        unset($src[$i]);

        if (!isset($dst[$srcEle[0]])) {
            $dst[$srcEle[0]] = $srcEle;
            return true;
        }
    }
    return false;
}
$allRows = array_merge($rows1, $rows2, $rows3);
$rows = [];
addPollRows($rows, $rows1, $allRows, 4);
addPollRows($rows, $rows2, $allRows, 4);
addPollRows($rows, $rows3, $allRows, 2);
// -------------------------- done with poll query --------------------------

//
//  Figure which columns we're showing in the extra stuff table
//
$colcnt = 0;

// count polls
if (count($rows) > 0)
    $colcnt++;

// count the "new to IF?" box
$colcnt++;

// figure the split
$colWidth = floor(100/$colcnt) . "%";
$colClass = "firstcol";

//
// if we found any polls, show the poll box
//
if (count($rows) > 0) {
    echo "<div class=\"block\"><div class=\"headline\">Vote!</div>"
        . "<p>Help other IFDB members find the games they're looking for "
        . "by voting in their polls.  Here are a few recent ones:</p>";
    
    global $nonce;
    echo "<style nonce='$nonce'>\n"
        . ".poll-sampler__poll { margin-left: 1em; text-indent: -1em; }\n"
        . ".poll-sampler__create { margin-top: 1ex; }\n"
        . "</style>\n";


    foreach ($rows as $row) {
        // retrieve the values
        [$pollID, $pollTitle, $pollDesc, $pollDate, $pollUserID,
         $pollVotes, $pollGames, $pollLastVoteDate, $pollUserName] = $row;

        // format for HTML
        $pollTitle = htmlspecialcharx($pollTitle);
        $pollUserName = htmlspecialcharx($pollUserName);

        // display it
        echo "<div class='poll-sampler__poll'>"
            . "<a href=\"poll?id=$pollID\"><b>$pollTitle</b></a>, "
            . "by <a href=\"showuser?id=$pollUserID\">$pollUserName</a>"
            . "</div>";
    }

    echo "<div class=\"details poll-sampler__create\">"
        . "<a href=\"search?browse&poll&sortby=new\">Browse all polls</a> | "
        . "<a href=\"poll?id=new\">Create a poll</a> | "
        . helpWinLink("help-polls", "What are polls?")
        . "</div></td></tr></table></div>";
}

//  ------------------------ end poll sampler box ---------------------------
?>
