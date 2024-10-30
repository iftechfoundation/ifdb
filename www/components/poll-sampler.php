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
                p.pollid, p.title, p.userid, u.name
              from
                polls as p
                left outer join pollvotes as v on v.pollid = p.pollid
                join users as u on u.id = p.userid
              where
                sandbox in (0, ?)
              group by
                p.pollid";

$recently_created = [];
$recently_voted = [];

// query the most recently created polls
$result = mysqli_execute_query($db,
    "$baseQuery order by p.created desc limit 0, 5", [$sandbox]);
$recently_created = mysqli_fetch_all($result);

// add the most recently active polls (i.e., latest vote)
$result = mysqli_execute_query($db,
    "$baseQuery order by max(v.votedate) desc limit 0, 5", [$sandbox]);
$recently_voted = mysqli_fetch_all($result);

echo "<div class=\"block\"><div class=\"headline\">Vote!</div>"
    . "<p>Help other IFDB members find the games they're looking for "
    . "by voting in their polls.  Here are a few recent ones:</p>";

global $nonce;
echo "<style nonce='$nonce'>\n"
    . ".poll-sampler__create { margin-top: 1ex; }\n"
    . "</style>\n";

function displayPoll($row) {
    [$pollID, $pollTitle, $pollUserID, $pollUserName] = $row;

    // format for HTML
    $pollTitle = htmlspecialcharx($pollTitle);
    $pollUserName = htmlspecialcharx($pollUserName);

    // display it
    echo "<li>"
        . "<a href=\"poll?id=$pollID\"><b>$pollTitle</b></a>, "
        . "by <a href=\"showuser?id=$pollUserID\">$pollUserName</a>"
        . "</li>\n";
}

echo "<div>New Polls: <span class='details'><a href='/search?browse&poll'>See More</a></span><ul>\n";
foreach ($recently_created as $row) {
    displayPoll($row);
}
echo "</ul></div><div>Polls with Recent Votes: <span class='details'><a href='/search?browse&poll&sortby=newvote'>See More</a></span><ul>\n";
foreach ($recently_voted as $row) {
    displayPoll($row);
}

echo "</ul></div>\n<div class=\"details poll-sampler__create\">"
    . "<a href=\"search?browse&poll&sortby=votes\">Browse all polls</a> | "
    . "<a href=\"poll?id=new\">Create a poll</a> | "
    . helpWinLink("help-polls", "What are polls?")
    . "</div></td></tr></table></div>";

//  ------------------------ end poll sampler box ---------------------------
?>
