<div>

    <ul>
    <?php
    $result = mysql_query("select count(*) as c from games", $db);
    $cnt = mysql_result($result, 0, "c");
    echo "<li><a href=\"search?browse\">$cnt Game Listings</a></li>";

    $result = mysql_query(
        "select count(*) as c from reviews
        where special is null
        and review is not null
        and ifnull(now() >= embargodate, 1)", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "<li><a href='/allnew?reviews'>$cnt Member Reviews</a></li>";

    $result = mysql_query(
        "select count(*) as c from reviews
        where
        rating is not null
        and special is null
        and review is null
        and ifnull(now() >= embargodate, 1)", $db);
    $cnt2 = mysql_result($result, 0, "c");
    if ($cnt2)
        echo "<li>$cnt2 Member Ratings</li>";

    $result = mysql_query(
        "select count(*) as c from users
        where acctstatus = 'A' and ifnull(profilestatus, ' ') != 'R' ", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "<li><a href=\"search?browse&member\">"
            . "$cnt Registered Members</a></li>";

    $result = mysql_query("select count(*) as c from reclists", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "<li><a href=\"search?browse&list\">"
            . "$cnt Recommended Lists</a></li>";

    $result = mysql_query("select count(*) as c from polls", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "<li><a href=\"search?browse&poll\">"
            . "$cnt Polls</a></li>";

    $result = mysql_query("select count(*) as c from competitions", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "<li><a href=\"search?browse&comp\">"
            . "$cnt Competition Listings</a></li>";
    ?>
    </ul>
</div>
