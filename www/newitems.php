<?php

define("ENABLE_IMAGES", 0);
define("NEWITEMS_SITENEWS", 0x0001);
define("NEWITEMS_GAMES", 0x0002);
define("NEWITEMS_LISTS", 0x0004);
define("NEWITEMS_POLLS", 0x0008);
define("NEWITEMS_REVIEWS", 0x0010);
define("NEWITEMS_COMPS", 0x0020);
define("NEWITEMS_CLUBS", 0x0040);
define("NEWITEMS_GAMENEWS", 0x0080);
define("NEWITEMS_COMPNEWS", 0x0100);
define("NEWITEMS_CLUBNEWS", 0x0200);

define("NEWITEMS_ALLITEMS", 
    NEWITEMS_SITENEWS
    | NEWITEMS_GAMES
    | NEWITEMS_LISTS
    | NEWITEMS_POLLS
    | NEWITEMS_REVIEWS
    | NEWITEMS_COMPS
    | NEWITEMS_CLUBS
    | NEWITEMS_GAMENEWS
    | NEWITEMS_COMPNEWS
    | NEWITEMS_CLUBNEWS
);

function getNewItems($db, $limit, $itemTypes = NEWITEMS_ALLITEMS)
{
    // get the logged-in user
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'] ?? null;

    // filter out plonked users, if applicable
    $andNotPlonked = "";
    if ($curuser) {
        $andNotPlonked = "and (select count(*) from userfilters "
                         . "where userid = '$curuser' "
                         . "and targetuserid = #USERID# "
                         . "and filtertype = 'K') = 0";
    }

    // Include only reviews from our sandbox or sandbox 0 (all users)
    $sandbox = "(0)";
    if ($curuser)
    {
        // get my sandbox
        $mysandbox = 0;
        $result = mysql_query("select sandbox from users where id='$curuser'", $db);
        list($mysandbox) = mysql_fetch_row($result);
        if ($mysandbox != 0)
            $sandbox = "(0,$mysandbox)";
    }

    // figure the LIMIT clause, if a row count limit was given
    $limit = ($limit ? "limit 0, " . ($limit + 1) : "");

    // start with an empty list
    $items = array();

    if ($itemTypes & NEWITEMS_SITENEWS) {
        // query site news
        $result = mysql_query(
            "select
               itemid as sitenewsid, title, ldesc as `desc`,
               posted as d,
               date_format(posted, '%M %e, %Y') as fmtdate,
               (now() < date_add(posted, interval 7 day)) as freshest
             from
               sitenews
             order by
               d desc
             $limit", $db);
        $sitenewscnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $sitenewscnt ; $i++) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            if ($i) $row['freshest'] = 0;
            $items[] = array('S', $row['d'], $row);
        }
    }

    if ($itemTypes & NEWITEMS_GAMES) {
        // query the recent games
        $result = mysql_query(
            "select id, title, author, `desc`, created as d,
               date_format(created, '%M %e, %Y') as fmtdate,
               (coverart is not null) as hasart
             from games
             order by created desc
             $limit", $db);
        $gamecnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $gamecnt ; $i++) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            $items[] = array('G', $row['d'], $row);
        }
    }

    if ($itemTypes & NEWITEMS_LISTS) {
        // query the recent recommended lists (minus plonked users)
        $anp = str_replace('#USERID#', 'reclists.userid', $andNotPlonked);
        $result = mysql_query(
            "select
               reclists.id as id, reclists.title as title,
               reclists.`desc` as `desc`,
               reclists.createdate as d,
               date_format(reclists.createdate, '%M %e, %Y') as fmtdate,
               count(reclists.id) as itemcnt,
               reclists.userid, users.name as `username`,
               (users.picture is not null) as haspic
             from reclists
               join reclistitems on reclistitems.listid=reclists.id
               join users on users.id = reclists.userid
             where
               users.sandbox in $sandbox
               $anp
             group by reclists.id
             order by reclists.createdate desc
             $limit", $db);
        $listcnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $listcnt ; $i++) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            $items[] = array('L', $row['d'], $row);
        }
    }

    if ($itemTypes & NEWITEMS_POLLS) {
        // query the recent polls (minus plonked users)
        $anp = str_replace('#USERID#', 'p.userid', $andNotPlonked);
        $result = mysql_query(
            "select
               p.pollid as pollid, p.title as title, p.`desc` as `desc`,
               p.created as d,
               date_format(p.created, '%M %e, %Y') as fmtdate,
               count(v.gameid) as votecnt, count(distinct v.gameid) as gamecnt,
               p.userid as userid, u.name as `username`,
               (u.picture is not null) as haspic
             from
               polls as p
               left outer join pollvotes as v on v.pollid = p.pollid
               join users as u on u.id = p.userid
             where
               u.sandbox in $sandbox
               $anp
             group by
               p.pollid
             order by
               p.created desc
             $limit", $db);
        $pollcnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $pollcnt ; $i++) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            $items[] = array('P', $row['d'], $row);
        }
    }

    if ($itemTypes & NEWITEMS_REVIEWS) {
        // query the recent reviews (minus plonks)
        $anp = str_replace('#USERID#', 'reviews.userid', $andNotPlonked);
        $result = mysql_query(
            "select
               reviews.id as id, gameid, summary, review, rating, special,
               games.title as title,
               users.id as userid, users.name as username,
               greatest(reviews.createdate,
                        ifnull(reviews.embargodate, '0000-00-00')) as d,
               date_format(greatest(reviews.createdate,
                           ifnull(reviews.embargodate, '0000-00-00')),
                           '%M %e, %Y') as fmtdate,
               (games.coverart is not null) as hasart,
               (users.picture is not null) as haspic,
               games.flags
             from
               reviews
               join games
               join users
               left outer join specialreviewers on specialreviewers.id = special
             where
               games.id = reviews.gameid
               and users.id = reviews.userid
               and reviews.review is not null
               and ifnull(now() >= reviews.embargodate, 1)
               and ifnull(specialreviewers.code, '') <> 'external'
               and users.sandbox in $sandbox
               $anp
             order by d desc, id desc
             $limit", $db);
        $revcnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $revcnt ; $i++) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            $items[] = array('R', $row['d'], $row);
        }
    }

    if ($itemTypes & NEWITEMS_COMPS) {
        // query recent competition page additions
        $result = mysql_query(
            "select
               c.compid as compid, c.title as title, c.`desc` as `desc`,
               c.created as d,
               date_format(c.created, '%M %e, %Y') as fmtdate
             from
               competitions as c
             order by
               d desc
             $limit", $db);
        $compcnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $compcnt ; $i++) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            $items[] = array('C', $row['d'], $row);
        }
    }

    if ($itemTypes & NEWITEMS_CLUBS) {
        // query recent club page additions
        $result = mysql_query(
            "select
               c.clubid as clubid, c.name as name, c.`desc` as `desc`,
               c.created as d,
               date_format(c.created, '%M %e, %Y') as fmtdate
             from
               clubs as c
             order by
               d desc
             $limit", $db);
        $clubcnt = mysql_num_rows($result);
        for ($i = 0 ; $i < $clubcnt ; $i++) {
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            $items[] = array('U', $row['d'], $row);
        }
    }

    if ($itemTypes & NEWITEMS_GAMENEWS) {
        // query game news updates
        queryNewNews(
            $items, $db, $limit, "G",
            "join games as g on g.id = n.sourceid",
            "g.id as sourceID, g.title as sourceTitle, "
            . "(g.coverart is not null) as hasart");
    }

    if ($itemTypes & NEWITEMS_COMPNEWS) {
        // add the competition news updates
        queryNewNews(
            $items, $db, $limit, "C",
            "join competitions as c on c.compid = n.sourceid",
            "c.compid as sourceID, c.title as sourceTitle");
    }

    if ($itemTypes & NEWITEMS_CLUBNEWS) {
        // add club news updates
        queryNewNews(
            $items, $db, $limit, "U",
            "join clubs as c on c.clubid = n.sourceid",
            "c.clubid as sourceID, c.name as sourceTitle");
    }

    // sort by date
    usort($items, "sortNewItemsByDate");

    // return the item list
    return $items;
}

// sorting callback: sort from newest to oldest
function sortNewItemsByDate($a, $b)
{
    // pin "freshest" items to the top of the list
    $aFreshest = isset($a[2]['freshest']) ? $a[2]['freshest'] : 0;
    $bFreshest = isset($b[2]['freshest']) ? $b[2]['freshest'] : 0;
    $freshest = $bFreshest - $aFreshest;
    if ($freshest) return $freshest;

    // Compare the date fields (element [1]) of the two rows.  These are
    // in the mysql raw date format, which collates like an ascii string,
    // so we can compare with strcmp.  Reverse the sense of the test so
    // that we sort newest first.
    if ($a[1] == $b[1]) {
        return $b[2]['id'] - $a[2]['id'];
    } else {
        return strcmp($b[1], $a[1]);
    }
}

function queryNewNews(&$items, $db, $limit, $sourceType,
                      $sourceJoin, $sourceCols)
{
    // get the logged-in user
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'] ?? null;

    // filter out plonked users, if applicable
    $andNotPlonked = "";
    if ($curuser) {
        $andNotPlonked = "and (select count(*) from userfilters "
                         . "where userid = '$curuser' "
                         . "and targetuserid = n.userid "
                         . "and filtertype = 'K') = 0";
    }

    // Include only reviews from our sandbox or sandbox 0 (all users)
    $sandbox = "(0)";
    if ($curuser)
    {
        // get my sandbox
        $mysandbox = 0;
        $result = mysql_query("select sandbox from users where id='$curuser'", $db);
        list($mysandbox) = mysql_fetch_row($result);
        if ($mysandbox != 0)
            $sandbox = "(0,$mysandbox)";
    }

    // query the data
    $result = mysql_query(
        "select
           n.newsid as newsID,
           n.created as d,
           date_format(n.created, '%M %e, %Y') as createdFmt,
           n.modified as modified,
           date_format(n.modified, '%M %e, %Y') as modifiedFmt,
           n.userid as userID, u.name as userName,
           norig.userid as origUserID, uorig.name as origUserName,
           n.headline as headline, n.body as body,
           (u.picture is not null) as haspic,
           $sourceCols
         from
           news as n
           join users as u on u.id = n.userid
           join news as norig on norig.newsid = ifnull(n.original, n.newsid)
           join users as uorig on uorig.id = norig.userid
           left outer join news as nsuper on nsuper.supersedes = n.newsid
           $sourceJoin
         where
           n.source = '$sourceType'
           and n.status = 'A'
           and nsuper.newsid is null
           and uorig.sandbox in $sandbox
           $andNotPlonked
         order by
           n.created desc
         $limit", $db);

    $cnt = mysql_num_rows($result);
    for ($i = 0 ; $i < $cnt ; $i++) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        $row["sourceType"] = $sourceType;
        $items[] = array('N', $row['d'], $row);
    }
}

function showNewItems($db, $first, $last, $items, $showFlagged = false, $allowHiddenBanner = true, $itemTypes = NEWITEMS_ALLITEMS)
{
    // if the caller didn't provide the new item lists, query them
    if (!$items)
        $items = getNewItems($db, $last, $itemTypes);

    // show them
    showNewItemList($db, $items, $first, $last, $showFlagged, $allowHiddenBanner, $itemTypes);

    // indicate whether there's more to come
    return count($items) > $last;
}

function showNewItemList($db, $items, $first, $last, $showFlagged, $allowHiddenBanner, $itemTypes)
{
    // show the items
    $totcnt = count($items);

    $showHiddenBanner = false;
    if (!$showFlagged && $allowHiddenBanner) {
        for ($idx = $first ; $idx <= $last && $idx < $totcnt ; $idx++)
        {
            list($pick, $rawDate, $row) = $items[$idx];
            if ($pick == 'R' && ($row['flags'] & FLAG_SHOULD_HIDE)) {
                $showHiddenBanner = true;
                break;
            }
        }
    }

    if ($showHiddenBanner) {
        $currentUrl = $_SERVER['REQUEST_URI'];
        if (strpos($currentUrl, '?') === false) {
            $currentUrl .= "?";
        }
        $showAllLink = htmlspecialchars( $currentUrl, ENT_QUOTES, 'UTF-8' ) . "&showFlagged=1";
        echo "<p><div class=restricted>Some results were hidden. "
            . "<a href=\"$showAllLink\">See all results</a></div></p>";
    }

    $eager = ($itemTypes === NEWITEMS_ALLITEMS ? "class='eager'" : "");

    for ($idx = $first ; $idx <= $last && $idx < $totcnt ; $idx++)
    {
        // get this item
        list($pick, $rawDate, $row) = $items[$idx];

        if (!$showFlagged && $pick == 'R' && ($row['flags'] & FLAG_SHOULD_HIDE)) {
            continue;
        }

        // display the item according to its type
        if (ENABLE_IMAGES) {
            global $nonce;
            echo "<style nonce='$nonce'>\n"
                . ".new-item tr:first-child { vertical-align: top }\n"
                . ".new-item td:first-child { padding-right: 1em }\n"
                . "</style>\n";

            echo "<table border=\"0\" cellpadding=\"0\" "
                . "cellspacing=\"0\" class=\"new-item\">"
                . "<tr>"
                . "<td>";
        }

        if ($pick == 'R')
        {
            // it's a review
            $r = $row;

            // show the image: user image if available, otherwise game
            // image, otherwise generic review icon
            if (ENABLE_IMAGES) {
                if ($r["haspic"]) {
                    echo "<a href=\"showuser?id={$r['userid']}\">"
                        . "<img border=0 src=\"showuser?id={$r['userid']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else if ($r["hasart"]) {
                    echo "<a href=\"viewgame?id={$r['gameid']}\">"
                        . coverArtThumbnail($r['gameid'], 50, null)
                        . "</a>";
                } else {
                    echo "<a href=\"viewgame?id={$r['gameid']}"
                        . "&review={$r['reviewid']}\">"
                        . "<img border=0 src=\"review50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize this review
            echo "<div class=\"new-review\">";

            if (is_null($r['special']))
                echo "<a href=\"showuser?id={$r['userid']}\"><b>"
                    . output_encode(htmlspecialcharx($r['username']))
                    . "</b></a> reviews ";
            else
                echo "A new review of ";

            echo "<a href=\"viewgame?id={$r['gameid']}\"><i><b>"
                . output_encode(htmlspecialcharx($r['title']))
                . "</b></i></a>";

            if (!is_null($r['special'])) {
                $result = mysql_query("select name from specialreviewers
                    where id = '{$r['special']}'", $db);
                echo " - " . mysql_result($result, 0, "name");
            } else {
                echo ": \""
                    . output_encode(htmlspecialcharx($r['summary']))
                    . "\" <span class=notes><i>{$r['fmtdate']}</i></span>";
            }

            $stars = showStars($r['rating']);
            list($summary, $len, $trunc) = summarizeHtml($r['review'], 140);
            $summary = fixDesc($summary);
            if ($len != 0 || $stars != "")
            {
                echo "<br><div class=indented><span class=details>";

                if ($stars != "")
                    echo "$stars ";

                if ($len != 0)
                    echo "<i>\"$summary\"</i>";

                if ($trunc)
                    echo " - <a $eager href=\"viewgame?id={$r['gameid']}"
                        . "&review={$r['id']}\">See full review</a>";

                echo "</span></div>";
            }

            echo "</div>";
            if (ENABLE_IMAGES)
                echo "</td>";
        }
        else if ($pick == 'S')
        {
            // it's site news
            echo "<div class=\"site-news\">IFDB <a href='/news'>site news</a> <span class=notes><i>{$row['fmtdate']}</i></span>"
                . "<br><div class=indented><b>{$row['title']}</b>: {$row['desc']}</div></div>";
        }
        else if ($pick == 'L')
        {
            // it's a list
            $l = $row;

            // pull out the list record
            $itemcnt = $l['itemcnt'];
            $itemS = $itemcnt == 1 ? "" : "s";
            $title = output_encode(htmlspecialcharx($l['title']));
            $username = output_encode(htmlspecialcharx($l['username']));
            list($desc, $len, $trunc) = summarizeHtml($l['desc'], 210);
            $desc = fixDesc($desc);

            // show the image: user image if available, otherwise the
            // generic list icon
            if (ENABLE_IMAGES) {
                if ($l["haspic"]) {
                    echo "<a href=\"showuser?id={$l['userid']}\">"
                        . "<img border=0 src=\"showuser?id={$l['userid']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else {
                    echo "<a href=\"viewlist?id={$l['id']}\">"
                        . "<img border=0 src=\"reclist50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize it
            echo "<div class=\"new-list\">"
                . "A new Recommended List by <a href=\"showuser"
                . "?id={$l['userid']}\"><b>$username</b></a>, "
                . "<a href=\"viewlist?id={$l['id']}\"><b>$title</b></a> "
                . "<span class=notes><i>{$l['fmtdate']}</i></span><br>"
                . "<div class=indented>"
                . "<span class=details>$itemcnt item$itemS</span><br>"
                . "<span class=details><i>$desc</i></span></div></div>";
        }
        else if ($pick == 'G')
        {
            // it's a game
            $g = $row;

            // show the image: game cover art if available, otherwise the
            // generic game icon
            if (ENABLE_IMAGES) {
                if ($g["hasart"]) {
                    echo "<a href=\"viewgame?id={$g['id']}\">"
                        . coverArtThumbnail($g['id'], 50, null)
                        . "</a>";
                } else {
                    echo "<a href=\"viewgame?id={$g['id']}\">"
                        . "<img border=0 src=\"game50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize this game
            echo "<div class=\"new-game\">"
                . "A new listing for "
                . "<a $eager href=\"viewgame?id={$g['id']}\"><b><i>"
                . output_encode(htmlspecialcharx($g['title']))
                . "</i></b></a>, by "
                . output_encode(htmlspecialcharx($g['author']))
                . " <span class=notes><i>{$g['fmtdate']}</i></span>";

            list($summary, $len, $trunc) = summarizeHtml($g['desc'], 210);
            $summary = fixDesc($summary);
            if ($len != 0)
                echo "<br><div class=indented><span class=details><i>"
                    . $summary
                    . "</i></span></div>";

            echo "</div>";
        }
        else if ($pick == 'P')
        {
            // it's a poll
            $p = $row;

            // pull out the poll record
            $pid = $p['pollid'];
            $uid = $p['userid'];
            $uname = output_encode(htmlspecialcharx($p['username']));
            $title = output_encode(htmlspecialcharx($p['title']));
            list($desc, $len, $trunc) = summarizeHtml($p['desc'], 210);
            $desc = fixDesc($desc);
            $votecnt = $p['votecnt'];
            $gamecnt = $p['gamecnt'];
            $cntdesc = ($votecnt == 0 ? "No votes" :
                        ($votecnt == 1 ? "1 vote" :
                         ("$votecnt votes for $gamecnt game"
                          . ($gamecnt == 1 ? "" : "s"))));
            $fmtdate = $p['fmtdate'];

            // show the image: user image if available, otherwise the
            // generic list icon
            if (ENABLE_IMAGES) {
                if ($p["haspic"]) {
                    echo "<a href=\"showuser?id={$p['userid']}\">"
                        . "<img border=0 src=\"showuser?id={$p['userid']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else {
                    echo "<a href=\"viewpoll?id={$p['pollid']}\">"
                        . "<img border=0 src=\"poll50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize this poll
            echo "<div class=\"new-poll\">"
                . "A new poll by <a href=\"showuser?id=$uid\">"
                . "$uname</a>, "
                . "<a $eager href=\"poll?id=$pid\"><b>$title</b></a> "
                . "<span class=notes><i>created $fmtdate</i></span>"
                . "<br><div class=indented>"
                . "<span class=details>$cntdesc</span><br>"
                . "<span class=details><i>$desc</i></span>"
                . "</div>"
                . "</div>";
        }
        else if ($pick == 'N')
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
            list($nbody, $len, $trunc) = summarizeHtml($n['body'], 210);
            $nbody = fixDesc($nbody);

            switch ($n['sourceType'])
            {
            case 'G':
                $divclass = "new-game-news";
                $href = "viewgame?id=$gid";
                break;

            case 'C':
                $href = "viewcomp?id=$gid";
                $divclass = "new-comp-news";
                break;

            case 'U':
                $href = "club?id=$gid";
                $divclass = "new-club-news";
                break;
            }

            // show the image: user image if available, otherwise game
            // image, otherwise generic review icon
            if (ENABLE_IMAGES) {
                if (isset($n["haspic"]) && $n["haspic"]) {
                    echo "<a href=\"showuser?id={$n['userID']}\">"
                        . "<img border=0 src=\"showuser?id={$n['userID']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else if ($n["hasart"]) {
                    echo "<a href=\"viewgame?id={$n['gameid']}\">"
                        . coverArtThumbnail($gid, 50, null)
                        . "</a>";
                } else {
                    echo "<a href=\"newslog?newsid=$nid\">"
                        . "<img border=0 src=\"news50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize the item
            echo "<div class=\"$divclass\">"
                . "News on <a href=\"$href\">$gtitle</a>: "
                . "<b>$nhead</b> "
                . "<span class=notes><i>$ncre</i></span><br>"
                . "<div class=indented><span class=details>$nbody - "
                . "<a href=\"newslog?newsid=$nid\">Details</a>"
                . "</span></div>"
                . "</div>";
        }
        else if ($pick == 'C')
        {
            // it's a competition
            $c = $row;

            // pull out the competition item
            $cid = $c["compid"];
            $ctitle = htmlspecialcharx($c["title"]);
            list($cdesc, $len, $trunc) = summarizeHtml($c["desc"], 210);
            $cdesc = fixDesc($cdesc);
            $cdate = $c["fmtdate"];

            // show the generic competition icon
            if (ENABLE_IMAGES) {
                echo "<a href=\"viewcomp?id=$cid\">"
                    . "<img border=0 src=\"competition50.gif\">"
                    . "</a>";
                echo "</td><td>";
            }

            // summarize the item
            echo "<div class=\"new-competition\">"
                . "A new competition page: <a href=\"viewcomp?id=$cid\">"
                . "$ctitle</a> <span class=notes><i>created $cdate</i></span>"
                . "<br><div class=indented>"
                . "<span class=details><i>$cdesc</i></span>"
                . "</div>"
                . "</div>";
        }
        else if ($pick == 'U')
        {
            // it's a club
            $c = $row;

            // pull out the club item
            $cid = $c["clubid"];
            $ctitle = htmlspecialcharx($c["name"]);
            list($cdesc, $len, $trunc) = summarizeHtml($c["desc"], 210);
            $cdesc = fixDesc($cdesc);
            $cdate = $c["fmtdate"];

            // show the generic club icon
            if (ENABLE_IMAGES) {
                echo "<a href=\"viewcomp?id=$cid\">"
                    . "<img border=0 src=\"club50.gif\">"
                    . "</a>";
                echo "</td><td>";
            }

            // summarize the item
            echo "<div class=\"new-club\">"
                . "A new club listing: <a href=\"club?id=$cid\">"
                . "$ctitle</a> <span class=notes><i>created $cdate</i></span>"
                . "<br><div class=indented>"
                . "<span class=details><i>$cdesc</i></span>"
                . "</div>"
                . "</div>";
        }

        if (ENABLE_IMAGES)
            echo "</tr></table>";
    }

    // indicate if there are more items
    return $idx < $totcnt;
}

function showNewItemsRSS($db, $showcnt)
{
    // query the new items
    $items = getNewItems($db, $showcnt - 1);
    $totcnt = count($items);

    $lastBuildDate = false;
    for ($idx = 0 ; $idx < $showcnt && $idx < $totcnt ; $idx++)
    {
        list($pick, $rawDate, $row) = $items[$idx];
        if (!$lastBuildDate) {
            $lastBuildDate = $rawDate;
        } else if ($rawDate < $lastBuildDate) {
            $fmtDate = date("D, j M Y H:i:s ", strtotime($lastBuildDate)) . 'UT';
            echo "<lastBuildDate>$fmtDate</lastBuildDate>\r\n";
            break;
        } else {
            $lastBuildDate = $rawDate;
        }
    }

    // show the items
    for ($idx = 0 ; $idx < $showcnt && $idx < $totcnt ; $idx++)
    {
        // decode this item
        list($pick, $rawDate, $row) = $items[$idx];

        // get the details on the next item
        if ($pick == 'R')
        {
            $r = $row;
            if (is_null($r['special'])) {
                $title = "{$r['username']} reviews \"{$r['title']}\"";
            } else {
                $title = "A review of \"{$r['title']}\"";
            }
            if (!is_null($r['summary'])) {
                $title .= ": \"{$r['summary']}\"";
            }
            if ($r['rating']) {
                $stars = " ";
                for ($i = 0; $i < 5; $i++) {
                    if ($i < $r['rating']) {
                        $stars .= "&#9733;"; // &starf;
                    } else {
                        $stars .= "&#9734;"; // &star;
                    }
                }
                $title .= $stars;
            }
            $desc = fixDesc($r['review'], FixDescSpoiler | FixDescRSS);
            $link = get_root_url() . "viewgame?id={$r['gameid']}"
                    . "&review={$r['reviewid']}";
            $pubDate = $r['d'];
        }
        else if ($pick == 'L')
        {
            $l = $row;
            $title = "A Recommended List by {$l['username']}: {$l['title']}";
            list($desc, $len, $trunc) = summarizeHtml($l['desc'], 210);
            $desc = fixDesc($desc);
            $link = get_root_url() . "viewlist?id={$l['id']}";
            $pubDate = $l['d'];
        }
        else if ($pick == 'P')
        {
            $p = $row;
            $title = "A poll by {$p['username']}: {$p['title']}";
            list($desc, $len, $trunc) = summarizeHtml($p['desc'], 210);
            $desc = fixDesc($desc);
            $link = get_root_url() . "poll?id={$p['pollid']}";
            $pubDate = $p['d'];
        }
        else if ($pick == 'N')
        {
            $n = $row;
            $title = "News on {$n['sourceTitle']}: {$n['headline']}";
            list($desc, $len, $trunc) = summarizeHtml($n['body'], 210);
            $desc = fixDesc($desc);
            $desc = "Reported by {$n['origUserName']}: $desc";
            $link = get_root_url() . "newslog?newsid={$n['newsID']}";
            $pubDate = $n['d'];
        }
        else if ($pick == 'C')
        {
            $c = $row;
            $title = "A new competition page: {$c['title']}";
            list($desc, $len, $trunc) = summarizeHtml($c['desc'], 210);
            $desc = fixDesc($desc);
            $link = get_root_url() . "viewcomp?id={$c['compid']}";
            $pubDate = $c['d'];
        }
        else if ($pick == 'U')
        {
            $c = $row;
            $title = "A new club page: {$c['name']}";
            list($desc, $len, $trunc) = summarizeHtml($c['desc'], 210);
            $desc = fixDesc($desc);
            $link = get_root_url() . "club?id={$c['clubid']}";
            $pubDate = $c['d'];
        }
        else if ($pick == 'G')
        {
            $g = $row;
            $title = "A new listing for {$g['title']} by {$g['author']}";
            list($desc, $len, $trunc) = summarizeHtml($g['desc'], 210);
            $desc = fixDesc($desc);
            $link = get_root_url() . "viewgame?id={$g['id']}";
            $pubDate = $g['d'];
        }
        else if ($pick == 'S')
        {
            // format the items for RSS
            // copied and pasted from /news
            $title = rss_encode("IFDB site news: " . htmlspecialcharx($row['title']));
            $ldesc = rss_encode(htmlspecialcharx($row['desc']));
            $pub = date("D, j M Y H:i:s ", strtotime($row['d'])) . 'UT';

            $link = get_root_url() . "news?item=" . $row['sitenewsid'];
            $link = rss_encode(htmlspecialcharx($link));

            // send the item without escaping links
            echo "<item>\r\n"
                . "<title>$title</title>\r\n"
                . "<description>$ldesc</description>\r\n"
                . "<link>$link</link>\r\n"
                . "<pubDate>$pub</pubDate>\r\n"
                . "<guid>$link</guid>\r\n"
                . "</item>\r\n";
            continue;
        }

        // format the item's publication date properly
        $pubDate = date("D, j M Y H:i:s ", strtotime($pubDate)) . 'UT';

        // send the item
        echo "<item>\r\n"
            . "<title>" . rss_encode(htmlspecialcharx($title))
            . "</title>\r\n"
            . "<description>" . rss_encode(htmlspecialcharx($desc))
            . "</description>\r\n"
            . "<link>" . rss_encode(htmlspecialcharx($link)) . "</link>\r\n"
            . "<pubDate>$pubDate</pubDate>\r\n"
            . "<guid>" . rss_encode(htmlspecialcharx($link)) . "</guid>\r\n"
            . "</item>\r\n";
    }
}

?>
