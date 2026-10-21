<?php

include_once "searchutil.php";
require_once 'vendor/autoload.php';

define("NEWITEMS_SITENEWS", 0x0001);
define("NEWITEMS_GAMES", 0x0002);
define("NEWITEMS_LISTS", 0x0004);
define("NEWITEMS_POLLS", 0x0008);
define("NEWITEMS_REVIEWS", 0x0010);
define("NEWITEMS_COMPS", 0x0020);
define("NEWITEMS_GAMENEWS", 0x0080);
define("NEWITEMS_COMPNEWS", 0x0100);

define("NEWITEMS_ALLITEMS", 
    NEWITEMS_SITENEWS
    | NEWITEMS_GAMES
    | NEWITEMS_LISTS
    | NEWITEMS_POLLS
    | NEWITEMS_REVIEWS
    | NEWITEMS_COMPS
    | NEWITEMS_GAMENEWS
    | NEWITEMS_COMPNEWS
);

function getNewItems($db, $limit = 10, $itemTypes = NEWITEMS_ALLITEMS, $options = [])
{
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'] ?? null;

    $andNotMuted = "";
    if ($curuser) {
        $andNotMuted = "and (select count(*) from userfilters "
                         . "where userid = '$curuser' "
                         . "and targetuserid = #USERID# "
                         . "and filtertype = 'K') = 0";
    }

    $sandbox = "(0)";
    if ($curuser) {
        $result = mysql_query("select sandbox from users where id='$curuser'", $db);
        list($mysandbox) = mysql_fetch_row($result);
        if ($mysandbox != 0)
            $sandbox = "(0,$mysandbox)";
    }

    $limit++;
    $offset = (int)($options['offset'] ?? 0);

    $sources = newItemsSources($db, $options, $andNotMuted, $sandbox, $limit);
    $idQueries = [];
    foreach ($sources as $src) {
        if (!($itemTypes & $src['flag'])) {
            continue;
        }
        $idQueries[] = $src['idQuery'];
    }
    if (!$idQueries) {
        return [];
    }

    $result = mysql_query(
        "select kind, d, id from (" . implode(" union all ", $idQueries) . ") as newitems
         order by d desc
         limit " . (int)$limit . " offset $offset", $db);
    if (!$result) {
        error_log(mysql_error($db));
        return [];
    }

    $keys = [];
    $idsByKind = [];
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $id = (string)$row['id'];
        $keys[] = [$row['kind'], $id, $row['d']];
        $idsByKind[$row['kind']][] = $id;
    }

    $rowsByKind = [];
    foreach ($sources as $src) {
        $kind = $src['kind'];
        if (empty($idsByKind[$kind])) {
            continue;
        }
        $rowsByKind[$kind] = $src['hydrate']($db, $idsByKind[$kind], $andNotMuted, $sandbox);
    }

    $items = [];
    foreach ($keys as [$kind, $id, $d]) {
        $row = $rowsByKind[$kind][$id] ?? $rowsByKind[$kind][(int)$id] ?? null;
        if (!$row) {
            continue;
        }
        $pick = ($kind == 'NG' || $kind == 'NC') ? 'N' : $kind;
        $items[] = [$pick, $row['d'] ?? $d, $row];
    }

    usort($items, "sortNewItemsByDate");
    return $items;
}

function newItemsSqlIdList($db, $ids)
{
    $quoted = [];
    foreach ($ids as $id) {
        $quoted[] = "'" . mysql_real_escape_string((string)$id, $db) . "'";
    }
    return implode(",", $quoted);
}

function newItemsDayWhere($column, $days)
{
    if (!$days) {
        return "1";
    }
    return "$column > date_sub(now(), interval " . (int)$days . " day)";
}

function newItemsKeyedRows($db, $sql, $idField)
{
    $rows = [];
    $result = mysql_query($sql, $db);
    if (!$result) {
        error_log(mysql_error($db));
        return $rows;
    }
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $rows[(string)$row[$idField]] = $row;
    }
    return $rows;
}

function newItemsHydrateNews($db, $ids, $sourceType, $sourceJoin, $sourceCols, $andNotMuted, $sandbox)
{
    if (!$ids) {
        return [];
    }
    $anm = str_replace('#USERID#', 'n.userid', $andNotMuted);
    $idList = newItemsSqlIdList($db, $ids);
    $sql = "select
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
               and n.newsid in ($idList)
               $anm";
    $rows = newItemsKeyedRows($db, $sql, 'newsID');
    foreach ($rows as &$row) {
        $row['sourceType'] = $sourceType;
    }
    unset($row);
    return $rows;
}

function newItemsSources($db, $options, $andNotMuted, $sandbox, $limit = 10)
{
    $days = $options['days'] ?? null;
    $ratingsView = getGameRatingsView($db);
    $options += [
        'games_limit' => (int)$limit + (int)($options['offset'] ?? 0),
        'reviews_limit' => (int)$limit + (int)($options['offset'] ?? 0),
    ];
    $reviewsLimit = (int)$options['reviews_limit'];
    $slack = max($reviewsLimit * 3, $reviewsLimit + 16);

    $sources = [
        [
            'flag' => NEWITEMS_REVIEWS,
            'kind' => 'R',
            'limitKey' => 'reviews_limit',
            // we use a union query of reviews with and without embargo so we can use the index
            'idQuery' => "select 'R' as kind, t.d as d, cast(reviews.id as char) as id
                        from (
                          (select id, createdate as d
                           from reviews
                           where review is not null
                             and (embargodate is null or embargodate <= createdate)
                             and (embargodate is null or embargodate <= now())
                           order by createdate desc
                           limit $slack)
                          union all
                          (select id, embargodate as d
                           from reviews
                           where review is not null
                             and embargodate is not null
                             and embargodate > createdate
                             and embargodate <= now()
                           order by embargodate desc
                           limit $reviewsLimit)
                        ) as t
                        join reviews on reviews.id = t.id
                        join games on games.id = reviews.gameid
                        join users on users.id = reviews.userid
                        left outer join specialreviewers on specialreviewers.id = special
                        where reviews.review is not null
                          and ifnull(now() >= reviews.embargodate, 1)
                          and ifnull(specialreviewers.code, '') <> 'external'
                          and users.sandbox in $sandbox
                          and " . newItemsDayWhere("t.d", $days) . "
                          " . str_replace('#USERID#', 'reviews.userid', $andNotMuted),
            'hydrate' => function($db, $ids) {
                $idList = newItemsSqlIdList($db, $ids);
                return newItemsKeyedRows($db,
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
                       games.pagevsn,
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
                       and reviews.id in ($idList)",
                    'id');
            },
        ],
        [
            'flag' => NEWITEMS_SITENEWS,
            'kind' => 'S',
            'limitKey' => 'sitenews_limit',
            'idQuery' => "select 'S' as kind, posted as d, cast(itemid as char) as id
                        from sitenews
                        where " . newItemsDayWhere("posted", $days),
            'hydrate' => function($db, $ids) {
                $idList = newItemsSqlIdList($db, $ids);
                return newItemsKeyedRows($db,
                    "select
                       itemid as sitenewsid, title, ldesc as `desc`,
                       posted as d,
                       date_format(posted, '%M %e, %Y') as fmtdate,
                       (now() < date_add(posted, interval 3 day)) as freshest
                     from sitenews
                     where itemid in ($idList)",
                    'sitenewsid');
            },
        ],
        [
            'flag' => NEWITEMS_GAMES,
            'kind' => 'G',
            'limitKey' => 'games_limit',
            'idQuery' => "select 'G' as kind, games.created as d, cast(games.id as char) as id
                        from games
                        join $ratingsView on games.id = gameid
                        where " . newItemsDayWhere("games.created", $days),
            'hydrate' => function($db, $ids) {
                $idList = newItemsSqlIdList($db, $ids);
                return newItemsKeyedRows($db,
                    "select
                       games.id as id, games.title as title, games.author as author,
                       games.desc as `desc`, games.created as d,
                       date_format(games.created, '%M %e, %Y') as fmtdate,
                       games.system as devsys,
                       (games.coverart is not null) as hasart, games.pagevsn
                     from games
                     where games.id in ($idList)",
                    'id');
            },
        ],
        [
            'flag' => NEWITEMS_LISTS,
            'kind' => 'L',
            'limitKey' => 'lists_limit',
            'idQuery' => "select 'L' as kind, reclists.createdate as d, cast(reclists.id as char) as id
                        from reclists
                        join users on users.id = reclists.userid
                        where users.sandbox in $sandbox
                          and exists (select 1 from reclistitems
                                      where reclistitems.listid = reclists.id)
                          and " . newItemsDayWhere("reclists.createdate", $days) . "
                          " . str_replace('#USERID#', 'reclists.userid', $andNotMuted),
            'hydrate' => function($db, $ids) {
                $idList = newItemsSqlIdList($db, $ids);
                return newItemsKeyedRows($db,
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
                     where reclists.id in ($idList)
                     group by reclists.id",
                    'id');
            },
        ],
        [
            'flag' => NEWITEMS_POLLS,
            'kind' => 'P',
            'limitKey' => 'polls_limit',
            'idQuery' => "select 'P' as kind, p.created as d, cast(p.pollid as char) as id
                        from polls as p
                        join users as u on u.id = p.userid
                        where u.sandbox in $sandbox
                          and " . newItemsDayWhere("p.created", $days) . "
                          " . str_replace('#USERID#', 'p.userid', $andNotMuted),
            'hydrate' => function($db, $ids) {
                $idList = newItemsSqlIdList($db, $ids);
                return newItemsKeyedRows($db,
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
                     where p.pollid in ($idList)
                     group by p.pollid",
                    'pollid');
            },
        ],
        [
            'flag' => NEWITEMS_COMPS,
            'kind' => 'C',
            'limitKey' => 'comps_limit',
            'idQuery' => "select 'C' as kind, c.created as d, cast(c.compid as char) as id
                        from competitions as c
                        where " . newItemsDayWhere("c.created", $days),
            'hydrate' => function($db, $ids) {
                $idList = newItemsSqlIdList($db, $ids);
                return newItemsKeyedRows($db,
                    "select
                       c.compid as compid, c.title as title, c.`desc` as `desc`,
                       c.created as d,
                       date_format(c.created, '%M %e, %Y') as fmtdate
                     from competitions as c
                     where c.compid in ($idList)",
                    'compid');
            },
        ],
        [
            'flag' => NEWITEMS_GAMENEWS,
            'kind' => 'NG',
            'limitKey' => 'games_limit',
            'idQuery' => "select 'NG' as kind, n.created as d, cast(n.newsid as char) as id
                        from news as n
                        join news as norig on norig.newsid = ifnull(n.original, n.newsid)
                        join users as uorig on uorig.id = norig.userid
                        left outer join news as nsuper on nsuper.supersedes = n.newsid
                        join games as g on g.id = n.sourceid
                        where n.source = 'G'
                          and n.status = 'A'
                          and nsuper.newsid is null
                          and uorig.sandbox in $sandbox
                          and " . newItemsDayWhere("n.created", $days) . "
                          " . str_replace('#USERID#', 'n.userid', $andNotMuted),
            'hydrate' => function($db, $ids, $andNotMuted, $sandbox) {
                return newItemsHydrateNews(
                    $db, $ids, "G",
                    "join games as g on g.id = n.sourceid",
                    "g.id as sourceID, g.title as sourceTitle, "
                    . "(g.coverart is not null) as hasart, g.pagevsn",
                    $andNotMuted, $sandbox);
            },
        ],
        [
            'flag' => NEWITEMS_COMPNEWS,
            'kind' => 'NC',
            'limitKey' => 'comps_limit',
            'idQuery' => "select 'NC' as kind, n.created as d, cast(n.newsid as char) as id
                        from news as n
                        join news as norig on norig.newsid = ifnull(n.original, n.newsid)
                        join users as uorig on uorig.id = norig.userid
                        left outer join news as nsuper on nsuper.supersedes = n.newsid
                        join competitions as c on c.compid = n.sourceid
                        where n.source = 'C'
                          and n.status = 'A'
                          and nsuper.newsid is null
                          and uorig.sandbox in $sandbox
                          and " . newItemsDayWhere("n.created", $days) . "
                          " . str_replace('#USERID#', 'n.userid', $andNotMuted),
            'hydrate' => function($db, $ids, $andNotMuted, $sandbox) {
                return newItemsHydrateNews(
                    $db, $ids, "C",
                    "join competitions as c on c.compid = n.sourceid",
                    "c.compid as sourceID, c.title as sourceTitle",
                    $andNotMuted, $sandbox);
            },
        ],
    ];

    foreach ($sources as &$src) {
        $idQueryLimit = $options[$src['limitKey']] ?? null;
        if ($idQueryLimit !== null) {
            $src['idQuery'] = "(" . $src['idQuery']
                . " order by d desc limit " . (int)$idQueryLimit . ")";
        }
    }
    unset($src);
    return $sources;
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
    if ($a[1] == $b[1] && is_numeric($a[2]['id'] ?? null) && is_numeric($b[2]['id'] ?? null)) {
        return $b[2]['id'] - $a[2]['id'];
    } else {
        return strcmp($b[1], $a[1]);
    }
}

function showNewItems($db, $first, $last, $items, $options = [])
{
    $itemTypes = $options['itemTypes'] ?? NEWITEMS_ALLITEMS;
    // if the caller didn't provide the new item lists, query them
    if (!$items)
        $items = getNewItems($db, $last, $itemTypes, $options);

    // show them
    showNewItemList($db, $first, $last, $items, $options);

    // indicate whether there's more to come
    return count($items) > $last;
}

function showNewItemList($db, $first, $last, $items, $options)
{
    // The markdown parser library
    $parsedown = new Parsedown();
    $showFlagged = $options['showFlagged'] ?? false;
    $allowHiddenBanner = $options['allowHiddenBanner'] ?? true;
    $showDescriptions = $options['showDescriptions'] ?? true;
    $enableImages = $options['enableImages'] ?? true;

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


    for ($idx = $first ; $idx <= $last && $idx < $totcnt ; $idx++)
    {
        // get this item
        list($pick, $rawDate, $row) = $items[$idx];

        $eager = ($idx < 4 ? "class='eager'" : "");

        if (!$showFlagged && $pick == 'R' && ($row['flags'] & FLAG_SHOULD_HIDE)) {
            continue;
        }

        // display the item according to its type
        if ($enableImages) {
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
            if ($enableImages) {
                if ($r["haspic"]) {
                    echo "<a href=\"showuser?id={$r['userid']}\">"
                        . "<img border=0 width=50 height=50 src=\"showuser?id={$r['userid']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else if ($r["hasart"]) {
                    echo "<a href=\"viewgame?id={$r['gameid']}\">"
                        . coverArtThumbnail($r['gameid'], 50, $r['pagevsn'])
                        . "</a>";
                } else {
                    // echo "<a href=\"viewgame?id={$r['gameid']}"
                    //     . "&review={$r['reviewid']}\">"
                    //     . "<img border=0 src=\"review50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize this review
            echo "<div class=\"new-review\">";

            if (is_null($r['special']))
                echo "<a href=\"showuser?id={$r['userid']}\"><b>"
                    . htmlspecialcharx($r['username'])
                    . "</b></a> reviews ";
            else
                echo "A new review of ";

            echo "<a href=\"viewgame?id={$r['gameid']}\"><i><b>"
                . htmlspecialcharx($r['title'])
                . "</b></i></a>";

            if (!is_null($r['special'])) {
                $result = mysql_query("select name from specialreviewers
                    where id = '{$r['special']}'", $db);
                echo " - " . mysql_result($result, 0, "name");
            } else {
                echo ": \""
                    . htmlspecialcharx($r['summary'])
                    . "\" <span class=notes><i>{$r['fmtdate']}</i></span>";
            }

            $stars = showStars($r['rating']);
            list($summary, $len, $trunc) = summarizeHtml($parsedown->line($r['review']), 140);
            $summary = fixDesc($summary);
            if ($len != 0 || $stars != "")
            {
                echo "<br><div class=indented><span class=details>";

                if ($stars != "")
                    echo "$stars ";

                if ($len != 0)
                    echo "<i>\"$summary\"</i>";

                if ($trunc)
                    echo " - <a class=eager href=\"viewgame?id={$r['gameid']}"
                        . "&review={$r['id']}\">See full review</a>";

                echo "</span></div>";
            }

            echo "</div>";
            if ($enableImages)
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
            $title = htmlspecialcharx($l['title']);
            $username = htmlspecialcharx($l['username']);
            list($desc, $len, $trunc) = summarizeHtml($l['desc'], 210);
            $desc = fixDesc($desc);

            // show the image: user image if available, otherwise the
            // generic list icon
            if ($enableImages) {
                if ($l["haspic"]) {
                    echo "<a href=\"showuser?id={$l['userid']}\">"
                        . "<img border=0 width=50 height=50 src=\"showuser?id={$l['userid']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else {
                    // echo "<a href=\"viewlist?id={$l['id']}\">"
                    //     . "<img border=0 src=\"reclist50.gif\"></a>";
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
                . "<span class=details>$itemcnt item$itemS</span>"
                . ($showDescriptions ? "<br><span class=details><i>$desc</i></span>" : "")
                . "</div></div>";
        }
        else if ($pick == 'G')
        {
            // it's a game
            $g = $row;

            // show the image: game cover art if available, otherwise the
            // generic game icon
            if ($enableImages) {
                if ($g["hasart"]) {
                    echo "<a href=\"viewgame?id={$g['id']}\">"
                        . coverArtThumbnail($g['id'], 50, $g['pagevsn'])
                        . "</a>";
                } else {
                    // echo "<a href=\"viewgame?id={$g['id']}\">"
                    //     . "<img border=0 src=\"game50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize this game
            echo "<div class=\"new-game\">"
                . "A new listing for "
                . "<a class=eager href=\"viewgame?id={$g['id']}\"><b><i>"
                . htmlspecialcharx($g['title'])
                . "</i></b></a>, by "
                . htmlspecialcharx($g['author'])
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
            $uname = htmlspecialcharx($p['username']);
            $title = htmlspecialcharx($p['title']);
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
            if ($enableImages) {
                if ($p["haspic"]) {
                    echo "<a href=\"showuser?id={$p['userid']}\">"
                        . "<img border=0 width=50 height=50 src=\"showuser?id={$p['userid']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else {
                    // echo "<a href=\"viewpoll?id={$p['pollid']}\">"
                    //     . "<img border=0 src=\"poll50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize this poll
            echo "<div class=\"new-poll\">"
                . "A new poll by <a href=\"showuser?id=$uid\">"
                . "$uname</a>, "
                . "<a $eager href=\"poll?id=$pid\"><b>$title</b></a> "
                . "<span class=notes><i>created $fmtdate</i></span>"
                . ($showDescriptions ? "<br><div class=indented>"
                . "<span class=details>$cntdesc</span><br>"
                . "<span class=details><i>$desc</i></span>"
                . "</div>" : "")
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
            [$nbody, $len, $trunc] = summarizeHtml($n['body'], 210);
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
            }

            // show the image: user image if available, otherwise game
            // image, otherwise generic review icon
            if ($enableImages) {
                if (isset($n["haspic"]) && $n["haspic"]) {
                    echo "<a href=\"showuser?id={$n['userID']}\">"
                        . "<img border=0 width=50 height=50 src=\"showuser?id={$n['userID']}&pic"
                        . "&thumbnail=50x50\"></a>";
                } else if (!empty($n["hasart"])) {
                    echo "<a href=\"viewgame?id=$gid\">"
                        . coverArtThumbnail($gid, 50, $n['pagevsn'])
                        . "</a>";
                } else {
                    // echo "<a href=\"newslog?newsid=$nid\">"
                    //     . "<img border=0 src=\"news50.gif\"></a>";
                }
                echo "</td><td>";
            }

            // summarize the item
            echo "<div class=\"$divclass\">"
                . "News on <a href=\"$href\">$gtitle</a>: "
                . "<b>$nhead</b> "
                . "<span class=notes><i>$ncre</i></span><br>"
                . "<div class=indented><span class=details>"
                . ($trunc ? "$nbody - <a href=\"newslog?newsid=$nid\">Details</a>" : "$nbody")
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
            if ($enableImages) {
                // echo "<a href=\"viewcomp?id=$cid\">"
                //     . "<img border=0 src=\"competition50.gif\">"
                //     . "</a>";
                echo "</td><td>";
            }

            // summarize the item
            echo "<div class=\"new-competition\">"
                . "A new competition page: <a href=\"viewcomp?id=$cid\">"
                . "$ctitle</a> <span class=notes><i>created $cdate</i></span>"
                . ($showDescriptions ? "<br><div class=indented>"
                . "<span class=details><i>$cdesc</i></span>"
                . "</div>" : "")
                . "</div>";
        }

        if ($enableImages)
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

    // The markdown parser library
    $parsedown = new Parsedown();

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
                        $stars .= "\u{2605}"; // &starf;
                    } else {
                        $stars .= "\u{2606}"; // &star;
                    }
                }
                $title .= $stars;
            }
            $desc = fixDesc(parsedownMultilineText($r['review']), FixDescSpoiler | FixDescRSS);
            $link = get_root_url() . "viewgame?id={$r['gameid']}"
                    . "&review={$r['id']}";
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
