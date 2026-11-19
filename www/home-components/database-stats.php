<div>

    <ul>
    <?php
    [$cnt] = mysqli_fetch_row(mysqli_execute_query($db,
        "select count(*) from games"));
    echo "<li><a href=\"search?browse\">$cnt Game Listings</a></li>";

    [$reviewCount, $ratingCount, $memberCount] = mysqli_fetch_row(mysqli_execute_query($db,
        "select reviewCount, ratingCount, memberCount
        from databaseStats_mv"));
    if ($reviewCount)
        echo "<li><a href='/allnew?reviews'>$reviewCount Member Reviews</a></li>";

    if ($ratingCount)
        echo "<li>$ratingCount Member Ratings</li>";

    if ($memberCount)
        echo "<li><a href=\"search?browse&member\">"
            . "$memberCount Registered Members</a></li>";

    [$cnt] = mysqli_fetch_row(mysqli_execute_query($db,
        "select count(*) from reclists"));
    if ($cnt)
        echo "<li><a href=\"search?browse&list\">"
            . "$cnt Recommended Lists</a></li>";

    [$cnt] = mysqli_fetch_row(mysqli_execute_query($db,
        "select count(*) from polls"));
    if ($cnt)
        echo "<li><a href=\"search?browse&poll\">"
            . "$cnt Polls</a></li>";

    [$cnt] = mysqli_fetch_row(mysqli_execute_query($db,
        "select count(*) from competitions"));
    if ($cnt)
        echo "<li><a href=\"search?browse&comp\">"
            . "$cnt Competition Listings</a></li>";
    ?>
    </ul>
</div>
