<div class="rightbar">
    <h3>In the Database</h3>

    <?php
    $result = mysql_query("select count(*) as c from games", $db);
    $cnt = mysql_result($result, 0, "c");
    echo "&bull; <a class=silent href=\"search?browse\">$cnt Game Listings</a><br>";

    $result = mysql_query(
        "select count(*) as c from reviews
        where special is null
        and review is not null
        and ifnull(now() >= embargodate, 1)", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "&bull; $cnt Member Reviews<br>";

    $result = mysql_query(
        "select count(*) as c from reviews
        where
        rating is not null
        and special is null
        and review is null
        and ifnull(now() >= embargodate, 1)", $db);
    $cnt2 = mysql_result($result, 0, "c");
    if ($cnt2)
        echo "&bull; $cnt2 Member Ratings<br>";

    $result = mysql_query(
        "select count(*) as c from users
        where acctstatus = 'A' and ifnull(profilestatus, ' ') != 'R' ", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "&bull; <a class=silent href=\"search?browse&member&sortby=new\">"
            . "$cnt Registered Members</a><br>";

    $result = mysql_query("select count(*) as c from reclists", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "&bull; <a class=silent href=\"search?browse&list&sortby=new\">"
            . "$cnt Recommended Lists</a><br>";

    $result = mysql_query("select count(*) as c from polls", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "&bull; <a class=silent href=\"search?browse&poll&sortby=new\">"
            . "$cnt Polls</a><br>";
        
    $result = mysql_query("select count(*) as c from competitions", $db);
    $cnt = mysql_result($result, 0, "c");
    if ($cnt)
        echo "&bull; <a class=silent href=\"search?browse&comp\">"
            . "$cnt Competition Listings</a><br>";
    ?>
    
</div>