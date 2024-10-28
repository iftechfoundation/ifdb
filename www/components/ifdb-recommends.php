<?php
// ----------------------------- IFDB Recommends ----------------------

$recs = array();
$maxpicks = 12;
$overloaded = true;
if ($loggedIn && !$overloaded) {

    // We're logged in, so we can try to come up with some collaborative
    // filtering selections.  First, look to see if we already have some
    // selections cached in this session.  If we don't, try to generate
    // some new selections.

    if ($debugflag) unset($_SESSION['ifdb_recommendations']);

    if (isset($_SESSION['ifdb_recommendations'])) {

        // we have pre-cached recommendations
        $recs = $_SESSION['ifdb_recommendations'];
        $recsrc = $_SESSION['ifdb_recommendations_source'];

    } else if ($async) {

        // No recommendations are cached, so come up with some new ones
        // First, generate a list giving the "rating distance" from each
        // other user.  The rating distance between me and user U is the
        // average of the squares of the differences of ratings we gave
        // to games that we both rated.  We also need to know the number
        // of ratings that we have in common, because that's another
        // factor in determining how much overlap there is - the more
        // ratings we have in common, and the closer those ratings, the
        // more similarity there is between our preferences.
        //
        // It's possible that even the closest users by these metrics
        // will have little in common with us.  To avoid reporting bad
        // results from these closest-but-not-actually-close matches,
        // throw out anyone who doesn't have at least a few common
        // reviews and who isn't within a reasonable average distance.
        // (An average distance of 2.5 or greater is essentially equal
        // to random chance, so we definitely want to stay within that.
        // However, it's better to be a little stricter still, so we
        // go with 1.5 as the limit.)
        $result = mysql_query(
            "create temporary table rating_space as
             select
               r2.userid as userid,
               sum(power(cast(r1.rating as signed) - cast(r2.rating as signed), 2))
                 / count(r2.gameid) as dist,
               count(r2.gameid) as cnt
             from
               reviews as r1, reviews as r2
             where
               r1.userid = '$quid'
               and r2.userid != '$quid'
               and r2.gameid = r1.gameid
               and ifnull(now() >= r2.embargodate, 1)
             group by
               r2.userid
             having
               cnt >= 1 && dist <= 1.5", $db);

        if ($debugflag) {
            echo "<b>rating_space:</b><div class=indented>";
            $result = mysql_query("select userid, dist, cnt from rating_space", $db);
            for ($ii = 0 ; $ii < mysql_num_rows($result) ; $ii++) {
                list($du, $dd, $dc) = mysql_fetch_row($result);
                echo "userid=$du, dist=$dd, cnt=$dc<br>";
            }
            echo "</div>";
        }

        // Now we have a two-dimensional space to search: average distance
        // vs number of reviews in common.  The ideal match is the one that
        // maximizes number in common and minimizes average distance.
        //
        // There might not be anyone at this ideal point .  We might have one
        // user with 100 games in common and opposite ratings on every one,
        // and another user with only 1 game in common but an exact match on
        // rating: in this case, the ideal point of (100 games, 0 distance)
        // wouldn't have a user occupying it.  However, we can use this
        // point as a reference, and find the users with the smallest
        // distance in our 2-space from this point: this will give us the
        // best overall combination of number in common and rating distance.
        $result = mysql_query(
            "select max(cnt), min(dist) from rating_space", $db);
        list($maxcnt, $mindist) = mysql_fetch_row($result);

        // we can only proceed if we have any users in the rating space
        if ($maxcnt != "") {

            // create the proximity table
            $result = mysql_query(
                "create temporary table user_proximity as
                 select
                   userid,
                   (cnt - $maxcnt)*(cnt - $maxcnt)
                   + (dist - $mindist)*(dist - $mindist) as dist
                 from
                   rating_space", $db);

            if ($debugflag) {
                echo "<b>user_proximity</b><div class=indented>";
                $result = mysql_query("select userid, dist from user_proximity", $db);
                for ($ii = 0 ; $ii < mysql_num_rows($result) ; $ii++) {
                    list($du, $dd) = mysql_fetch_row($result);
                    echo "userid=$du, dist=$dd<br>";
                }
                echo "</div>";
            }

            // randomly pick method 1 or method 2 initially
            $method = (rand(0, 100) > 50 ? 1 : 2);

            // we don't have anything in our list yet
            $recs = array();

            // if we chose method 1, apply it
            if ($method == 1) {
                if ($debugflag) echo "method 1<br>";

                // Method 1: Pick games according to a CONSENSUS of like-minded
                // users.  Calculate the weighted average rating for each game
                // rated by like-minded users, weighting by "closeness," which
                // we define as (max_distance - distance).
                //
                // Calculate the maximum distance, which we need to determine
                // each user's closeness.
                $result = mysql_query(
                    "select max(dist) from user_proximity", $db);
                list($maxdist) = mysql_fetch_row($result);

                // Now calculate the weighted average rating for each game
                // that we haven't rated ourselves.  The weighting factor
                // for a user U is closeness[U]/sum(closeness), where the
                // sum includes the other users who rated that game (i.e.,
                // we want to normalize it by closeness).  If everyone's
                // at closeness zero, we have a special case where we simply
                // take an unweighted average, since everyone has the same
                // weight.  (We have to treat this as a special case to
                // avoid dividing by zero, since the normalization denominator
                // is the sum of the closenesses.)
                $result = mysql_query(
                    "select
                       games.id as gameid,
                       games.title as title,
                       games.author as author,
                       games.`desc` as `desc`,
                       (games.coverart is not null) as hasart,
                       if (sum($maxdist - user_proximity.dist) = 0,
                           avg(reviews.rating),
                           sum(reviews.rating
                               *($maxdist - user_proximity.dist))
                            / sum($maxdist - user_proximity.dist))
                         as avgrating
                     from
                       games
                       join reviews
                       join user_proximity
                       left outer join reviews as r2
                         on games.id = r2.gameid and r2.userid = '$quid'
                       left outer join playedgames as pg
                         on games.id = pg.gameid and pg.userid = '$quid'
                       left outer join wishlists as wl
                         on games.id = wl.gameid and wl.userid = '$quid'
                       left outer join unwishlists as uw
                         on games.id = uw.gameid and uw.userid = '$quid'
                     where
                       games.id = reviews.gameid
                       and user_proximity.userid = reviews.userid
                       and r2.userid is null
                       and pg.userid is null
                       and wl.userid is null
                       and uw.userid is null
                       and not (reviews.RFlags & " . RFLAG_OMIT_AVG . ")
                       and ifnull(now() >= reviews.embargodate, 1)
                       and not (games.flags & " . FLAG_SHOULD_HIDE . ")
                     group by
                       games.id
                     having
                       count(reviews.rating) >= 5
                     order by
                       avgrating desc
                     limit
                       0, $maxpicks", $db);

                if ($debugflag) echo "error = " . mysql_error($db) . "<br>";

                // fetch the results
                for ($i = 0 ; $i < mysql_num_rows($result) ; $i++)
                    $recs[] = mysql_fetch_array($result, MYSQL_ASSOC);

                // remember the source as collaborative filter #1
                $recsrc = "collab1";

                if ($debugflag) echo "record count=" . count($recs) . "<br>";
            }

            // Method 1 doesn't always produce any results, since there
            // might not be a consensus among nearby users about any
            // games.  We might do better with Method 2 in this case.
            if (count($recs) < 3)
                $method = 2;

            // apply method 2 if applicable
            if ($method == 2) {
                if ($debugflag) echo "method 2<br>";

                // Method 2: Pick games that anyone in our proximity list
                // rated highly (4-5 stars).  This method looks for INDIVIDUAL
                // RECOMMENDATIONS from like-minded users, but doesn't take
                // into account disagreements among these users, since only
                // one user has to have given a game a high rating.
                //
                // Find games that (a) other nearby users rated highly (4 or 5
                // starts), and (b) we HAVEN'T rated.  Group by game so that
                // we only keep unique game entries (as it's likely that the
                // same games will be recommended by multiple like-minded users,
                // at least if the theory behind this mechanism works at all
                // in practice!).  Order by distance, so that the top of the
                // the list consists of the recommendations from the most
                // similar users, and only get the top set of games.
                $result = mysql_query(
                    "select
                       games.id as gameid,
                       games.title as title,
                       games.author as author,
                       games.`desc` as `desc`,
                       (games.coverart is not null) as hasart,
                       min(user_proximity.dist) as dist
                     from
                       user_proximity
                       join reviews as r1
                       join games
                       left outer join reviews as r2
                         on games.id = r2.gameid and r2.userid = '$quid'
                       left outer join playedgames as pg
                         on games.id = pg.gameid and pg.userid = '$quid'
                       left outer join wishlists as wl
                         on games.id = wl.gameid and wl.userid = '$quid'
                       left outer join unwishlists as uw
                         on games.id = uw.gameid and uw.userid = '$quid'
                     where
                       r1.userid = user_proximity.userid
                       and games.id = r1.gameid
                       and r2.userid is null
                       and pg.userid is null
                       and wl.userid is null
                       and uw.userid is null
                       and r1.rating >= 4
                       and ifnull(now() >= r1.embargodate, 1)
                       and not (games.flags & " . FLAG_SHOULD_HIDE . ")
                     group by
                       r1.gameid
                     order by
                       dist
                     limit
                       0, $maxpicks", $db);

                if ($debugflag) echo "error = " . mysql_error($db) . "<br>";

                // fetch the results
                for ($i = 0 ; $i < mysql_num_rows($result) ; $i++)
                    $recs[] = mysql_fetch_array($result, MYSQL_ASSOC);

                // the source of these recommendations is the collab filter #2
                $recsrc = "collab2";

                if ($debugflag) echo "record count=" . count($recs) . "<br>";
            }
        }
    } else {
      ?>
      <div id="recommendations">Loading...</div>
      <script nonce="<?php global $nonce; echo $nonce; ?>">
        void function() {
          var element = document.getElementById('recommendations')
          var xhr = new XMLHttpRequest();
          xhr.open("GET", '/async-recommendations<?= ($debugflag ? '?debug=yesDebug' : '')?>', true);
          xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status == 200) {
              element.innerHTML = xhr.responseText;
            } else {
              element.innerHTML = "<span class=errmsg>There was an error loading recommendations.</span>";
            }
          }
          xhr.send();
        }();
      </script>
      <?php
      return;
    }
}

// if our recommendation list is empty, pick some random 4- and 5-star
// games as recommendations
if (count($recs) == 0) {

    if ($debugflag) echo "falling back on default method<br>";

    // if we're logged in, don't pick games we've already rated
    $exclJoin = "";
    $exclWhere = "";
    if ($loggedIn) {
        $exclJoin = "left outer join reviews as r2"
                    . " on games.id = r2.gameid and r2.userid = '$quid' "
                    . "left outer join playedgames as pg"
                    . " on games.id = pg.gameid and pg.userid = '$quid' "
                    . "left outer join wishlists as wl"
                    . " on games.id = wl.gameid and wl.userid = '$quid' "
                    . "left outer join unwishlists as uw"
                    . " on games.id = uw.gameid and uw.userid = '$quid' ";
        $exclWhere = "and r2.userid is null "
                     . " and pg.userid is null"
                     . " and wl.userid is null"
                     . " and uw.userid is null";
    }
    $gameRatingsView = getGameRatingsView($db);

    // pick the top-rated games
    $result = mysql_query(
        "select
           games.id as gameid,
           games.title as title,
           games.author as author,
           games.`desc` as `desc`,
           (games.coverart is not null) as hasart,
           games.pagevsn,
           starsort
         from
           games
           join $gameRatingsView on games.id = gameid
           $exclJoin
         where
           starsort >= 4
           and not (games.flags & " . FLAG_SHOULD_HIDE . ")
           $exclWhere
         order by
           starsort desc
         limit
           0, $maxpicks", $db);

    // fetch the results
    for ($recs = array(), $i = 0 ; $i < mysql_num_rows($result) ; $i++)
        $recs[] = mysql_fetch_array($result, MYSQL_ASSOC);

    // the source is just generic top-rated games
    $recsrc = 'generic';
}

function sortBySortorder($a, $b)
{
    return $a['sortorder'] - $b['sortorder'];
}

// show some recommendations
if (count($recs) >= 2) {

    // randomly re-sort the list
    for ($i = 0 ; $i < count($recs) ; $i++)
        $recs[$i]['sortorder'] = rand();
    usort($recs, "sortBySortorder");

    // stash the recommendations back in the session
    $_SESSION['ifdb_recommendations'] = $recs;
    $_SESSION['ifdb_recommendations_source'] = $recsrc;

    // start the section
    echo "<div class=headline>IFDB Recommends..."
      .($overloaded ? "<span class='headlineRss'><a href='/search?searchbar=played%3Ano+willplay%3Ano+wontplay%3Ano+reviewed%3Ano+rated%3Ano'>More like this</a></span>" : "")
      ."</div><div>";
    global $nonce;
    echo "<style nonce='$nonce'>\n"
        . ".ifdb-recommends__artLink { margin-right: 1em; }\n"
        . "</style>\n";


    // show the first three entries
    for ($i = 0 ; $i < count($recs) && $i < 3 ; $i++) {

        // get the fields from the game record
        $r = $recs[$i];
        $gameid = $r['gameid'];
        $title = htmlspecialcharx($r['title']);
        $author = htmlspecialcharx($r['author']);
        $author = collapsedAuthors($author);
        $hasart = $r['hasart'];
        $pagevsn = $r['pagevsn'];
        list($summary, $len, $trunc) = summarizeHtml($r['desc'], 140);
        $summary = fixDesc($summary);

         // display it
        echo "<p>";
        if ($hasart)
            echo "<table border=0 cellspacing=0 cellpadding=0>"
                . "<tr valign=top><td>"
                . "<a href=\"viewgame?id=$gameid\" class=\"ifdb-recommends__artLink\" aria-label=\"$title\">"
                . coverArtThumbnail($gameid, 70, $pagevsn)
                . "</a></td><td>";

        echo "<a href=\"viewgame?id=$gameid\"><i><b>$title</b></i></a>, "
            . "by $author<br>"
            . "<div class=indented><span class=details>"
            . "<i>$summary</i></span></div>";

        if ($hasart)
            echo "</td></tr></table>";
    }

    // explain the source
    echo "<p><span class=details><i>";
    if ($recsrc == 'generic' && !$overloaded) {
        echo "These are a few randomly-selected games with high
             average member ratings.  If you ";
        if (!$loggedIn)
            echo "<a href=\"login\">log in</a> and ";
        echo "rate a few games yourself, IFDB can offer customized
              recommendations (".helpWinLink("help-crossrec", "explain").").";
    } else {
        echo helpWinLink("help-crossrec", "Why did IFDB recommend these?");
    }
    echo "</i></span></div>";
}
// ---------------------------- end IFDB Recommends ------------------------
         ?>
