<?php
//  ----------------------  poll sampler box - query -----------------------

$sandbox = 0;

// if the user is logged in, look up their sandbox
$curuser = $_SESSION['logged_in_as'];
if ($curuser) {
    $result = mysql_query("select sandbox from users where id='$curuser'", $db);
    list($sandbox) = mysql_fetch_row($result);
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
                sandbox in (0, $sandbox)
              group by
                p.pollid";
$limit = "limit 0, 5";

$rows1 = array();
$rows2 = array();
$rows3 = array();

// query the most recently created polls
$result = mysql_query(
    "$baseQuery order by p.created desc $limit", $db);
for ($i = 0, $cnt = mysql_num_rows($result) ; $i < $cnt ; $i++)
    $rows1[] = mysql_fetch_row($result);

// add the most recently active polls (i.e., latest vote)
$result = mysql_query(
    "$baseQuery order by lastvotedate desc $limit", $db);
for ($i = 0, $cnt = mysql_num_rows($result) ; $i < $cnt ; $i++)
    $rows2[] = mysql_fetch_row($result);

// add the most popular polls (most votes)
$result = mysql_query(
    "$baseQuery order by votecnt desc, gamecnt desc $limit", $db);
for ($i = 0, $cnt = mysql_num_rows($result) ; $i < $cnt ; $i++)
    $rows3[] = mysql_fetch_row($result);

// pick the top 2 of each, skipping duplicates
function addPollRows(&$dst, $src, $allRows)
{
    // add the next element of src not in dst
    addUniquePollRow($dst, $src);

    // if that failed, fall back to the master list
    addUniquePollRow($dst, $allRows);
}
function addUniquePollRow(&$dst, $src)
{
    // search for an element of src not in dst
    for ($i = 0 ; $i < count($src) ; $i++) {
        $srcEle = $src[$i];
        $srcID = $srcEle[0];
        for ($found = false, $j = 0 ; $j < count($dst) ; $j++) {
            $dstEle = $dst[$j];
            $dstID = $dstEle[0];
            if ($dstID == $srcID) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $dst[] = $srcEle;
            return;
        }
    }
}
$allRows = array_merge($rows1, $rows2, $rows3);
$rows = array();
addPollRows($rows, $rows1, $allRows);
addPollRows($rows, $rows1, $allRows);
addPollRows($rows, $rows2, $allRows);
addPollRows($rows, $rows2, $allRows);
addPollRows($rows, $rows3, $allRows);
$pollRows = $rows;
// -------------------------- done with poll query --------------------------

//
//  Figure which columns we're showing in the extra stuff table
//
$colcnt = 0;

// count polls
if (count($pollRows) > 0)
    $colcnt++;

// count the "new to IF?" box
$colcnt++;

// figure the split
$colWidth = floor(100/$colcnt) . "%";
$colClass = "firstcol";

// 
// if we found any polls, show the poll box
// 
$rows = $pollRows;
if (count($rows) > 0) {
    echo "<div class=\"block\"><div class=\"headline\">Vote!</div>"
        . "<p>Help other IFDB members find the games they're looking for "
        . "by voting in their polls.  Here are a few recent ones:</p>";
    
    for ($i = 0 ; $i < count($rows) ; $i++) {
        // retrieve the values
        list($pollID, $pollTitle, $pollDesc, $pollDate, $pollUserID,
             $pollVotes, $pollGames, $pollLastVoteDate, $pollUserName) =
                 $rows[$i];

        // format for HTML
        $pollTitle = htmlspecialcharx($pollTitle);
        $pollUserName = htmlspecialcharx($pollUserName);

        // display it
        echo "<div style=\"margin-left: 1em; text-indent: -1em;\">"
            . "<a href=\"poll?id=$pollID\"><b>$pollTitle</b></a>, "
            . "by <a href=\"showuser?id=$pollUserID\">$pollUserName</a>"
            . "</div>";
    }

    echo "<div class=\"details\" style=\"margin-top: 1ex;\">"
        . "<a href=\"search?browse&poll&sortby=new\">Browse all polls</a> | "
        . "<a href=\"poll?id=new\">Create a poll</a> | "
        . helpWinLink("help-polls", "What are polls?")
        . "</div></td></tr></table></div>";
}

//  ------------------------ end poll sampler box ---------------------------
?>
