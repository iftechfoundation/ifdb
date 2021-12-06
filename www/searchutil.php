<?php

include_once "util.php";
include_once "login-persist.php";

function doSearch($db, $term, $searchType, $sortby, $limit, $browse)
{
    // we need the current user for some types of queries
    checkPersistentLogin();
    $curuser = mysql_real_escape_string($_SESSION['logged_in_as'], $db);

    // set up the plonk filter
    $andNotPlonked = "";
    if ($curuser) {
        $andNotPlonked = " and (select count(*) from userfilters "
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

    // assume no badge info
    $badges = false;

    // set up the parameters for the type of search we're performing
    if ($searchType == "list")
    {
        // Special keywords for list search
        //
        // This is a mapping from a keyword that can be used in a search,
        // which we always end in ":", to a descriptor that we use to modify
        // the query when we find the keyword.  The descriptor is an array:
        //
        //   [0] = name of column to query; we'll add this column to the
        //         WHERE clause with an appropriate expression
        //   [1] = 0 for text matches, 1 for numeric range matches, 2
        //         for exact numeric matches, 3 for full-word text
        //         matches, 99 for special-case handling
        //   [2] = true -> the match expression goes in the HAVING clause
        //         rather than the WHERE clause
        //
        $specialMap = array(
            "author:" => array("users.name", 0));

        // SELECT parameters for list search
        $selectList = "reclists.id as id,
                       title,
                       `desc`,
                       users.id as userid,
                       users.name as username,
                       count(gameid) as itemcount";
        $tableList = "reclists
                      left outer join reclistitems
                        on reclistitems.listid = reclists.id,
                      users";
        $baseWhere = "and reclists.userid = users.id "
                     . "and users.sandbox in ($sandbox) "
                     . str_replace('#USERID#', 'reclists.userid',
                                   $andNotPlonked);
        $groupBy = "group by reclistitems.listid";
        $baseOrderBy = "title";
        $matchCols = "title, keywords";
        $likeCol = "title";
        $summaryDesc = "Lists";
    }
    else if ($searchType == "poll")
    {
        // special keywords for polls
        $specialMap = array(
            "author:" => array("u.name", 0));

        // SELECT parameters for poll queries
        $selectList = "p.pollid as id,
                       p.title as title,
                       p.userid as userid,
                       u.name as username,
                       p.`desc` as `desc`,
                       count(v.gameid) as votecnt,
                       count(distinct v.gameid) as gamecnt,
                       max(v.votedate) as votedate,
                       date_format(p.created, '%M %e, %Y') as createdfmt,
                       date_format(max(v.votedate), '%M %e, %Y')
                         as votedatefmt";
        $tableList = "polls as p
                      left outer join pollvotes as v on v.pollid = p.pollid
                      join users as u on u.id = p.userid";
        $baseWhere = "and u.sandbox in $sandbox "
                     . str_replace('#USERID#', 'p.userid', $andNotPlonked);
        $groupBy = "group by p.pollid";
        $baseOrderBy = "title";
        $matchCols = "title, keywords";
        $likeCol = "title";
        $summaryDesc = "Polls";
    }
    else if ($searchType == "club")
    {
        // special keywords for clubs
        $specialMap = array(
            "#members:" => array("membercnt", 1, true),
            "contact:" => array("c.contacts_plain", 0),
            "contacts:" => array("c.contacts_plain", 0));

        // if we're logged in, we can see clubs we belong to
        $orMyClub = $joinMyClubs = "";
        if ($curuser) {
            $orMyClub = "or mcur.userid is not null";
            $joinMyClubs = "left outer join clubmembers as mcur "
                           . "on mcur.clubid = c.clubid "
                           . "and mcur.userid = '$curuser'";
        }

        // SELECT parameters for club search
        $selectList = "c.clubid as clubid,
                       c.contacts_plain as contacts,
                       c.name as name,
                       c.`desc` as `desc`,
                       date_format(c.created, '%M %e, %Y') as created,
                       if (c.members_public = 'Y' $orMyClub,
                           count(m.userid), 0) as membercnt";
        $tableList = "clubs as c
                      left outer join clubmembers as m
                        on m.clubid = c.clubid
                      $joinMyClubs";
        $baseWhere = "";
        $groupBy = "group by c.clubid";
        $baseOrderBy = "name";
        $matchCols = "c.name, c.keywords, c.`desc`";
        $likeCol = "c.name";
        $summaryDesc = "Clubs";
    }
    else if ($searchType == "member")
    {
        // special keywords for member search
        $specialMap = array(
            "location:" => array("location", 0),
            "hyperlinks:" => array("/hyperlinks/", 99),
            "club:" => array("/clubname/", 99));

        // SELECT parameters for member queries
        $selectList = "u.id as id,
                       u.name as name,
                       u.location as location,
                       u.profile profile,
                       date_format(u.created, '%M %e, %Y') as createdfmt,
                       (u.picture is not null) as haspic";
        $tableList = "users as u";
        $orCurUser = ($curuser ? "or u.id = '$curuser'" : "");
        $baseWhere = "and u.acctstatus = 'A' "
                     . "and u.sandbox in $sandbox "
                     . "and (ifnull(u.profilestatus, ' ') != 'R' $orCurUser)"
                     . str_replace('#USERID#', 'u.id', $andNotPlonked);
        $groupBy = "";
        $baseOrderBy = "name";
        $matchCols = "name, profile";
        $likeCol = "name";
        $summaryDesc = "Members";

        // get the reviewer badge status for the top 100 reviewers
        $badges = getUserScores($db, 100);
    }
    else if ($searchType == "comp")
    {
        // special keywords for competition searches
        $specialMap = array(
            "series:" => array("series", 0),
            "year:" => array("date_format(awarddate, '%Y')", 1),
            "organizer:" => array("organizers", 0),
            "organizers:" => array("organizers", 0),
            "judge:" => array("judges", 0),
            "judges:" => array("judges", 0),
            "#games:" => array("count(g.gameid)", 1, true),
            "#divisions:" => array("count(d.divid)", 1, true));

        $selectList = "c.compid as compid,
                       c.title as title,
                       c.`desc` as `desc`,
                       date_format(c.awarddate, '%M %e, %Y') as awarddate,
                       count(distinct g.gameid) as gamecnt,
                       count(g.gameid) as gamesbydiv,
                       count(distinct d.divid) as divcnt";
        $tableList = "competitions as c"
                     . "  join compdivs as d on d.compid = c.compid"
                     . "  join compgames as g"
                     . "    on g.compid = c.compid and g.divid = d.divid";
        $baseWhere = "";
        $groupBy = "group by c.compid";
        $baseOrderBy = "c.title";
        $matchCols = "c.title, c.series, c.keywords, c.`desc`";
        $likeCol = "c.title";
        $summaryDesc = "Competitions";
    }
    else if ($searchType == "tag")
    {
        // special keywords for tag search
        $specialMap = array(
            "tag:" => array("tag", 0));

        $selectList = "gt.tag as tag";
        $tableList = "gametags as gt";
        $groupBy = "group by gt.tag";
        $baseOrderBy = "gt.tag";
        $matchCols = "gt.tag";
        $likeCol = "gt.tag";
        $summaryDesc = "Tags";
    }
    else
    {
        // special keywords for game search:  "keyword:" => descriptor
        $specialMap = array(
            "genre:" => array("genre", 0),
            "published:" => array("date_format(published, '%Y')", 1),
            "added:" => array("date_format(created, '%Y')", 1),
            "system:" => array("system", 0),
            "series:" => array("seriesname", 0),
            "tag:" => array("tags", 3),
            "bafs:" => array("bafsid", 2),
            "rating:" => array("avgRating", 1, true),
            "#reviews:" => array("numMemberReviews",1, true),
            "ratingdev:" => array("stdDevRating", 1, true),
            "#ratings:" => array("numRatingsTotal", 1, true),
            "forgiveness:" => array("forgiveness", 0),
            "language:" => array("language", 99),
            "author:" => array("author", 99),
            "authorid:" => array("authorid", 99),
            "ifid:" => array("/ifid/", 99),
            "downloadable:" => array("/downloadable/", 99),
            "played:" => array("played", 99),
            "willplay:" => array("willplay", 99),
            "wontplay:" => array("wontplay", 99),
            "license:" => array("license", 0),
            "format:" => array("/gameformat/", 99));


        // SELECT parameters for game queries
        $selectList = "games.id as id,
                       games.title as title,
                       games.author as author,
                       games.tags as tags,
                       games.moddate as moddate,
                       games.system as devsys,
                       if (time(games.published) = '00:00:00',
                           date_format(games.published, '%Y'),
                           date_format(games.published, '%M %e, %Y'))
                         as pubfmt,
                       if (time(games.published) = '00:00:00',
                           date_format(games.published, '%Y'),
                           date_format(games.published, '%Y-%m-%d'))
                         as published,
                       date_format(games.published, '%Y') as pubyear,
                       (games.coverart is not null) as hasart,
                       avgRating as avgrating,
                       numRatingsInAvg as ratingcnt,
                       stdDevRating as ratingdev,
                       numRatingsTotal,
                       numMemberReviews,
                       games.sort_title as sort_title,
                       games.sort_author as sort_author,
                       ifnull(games.published, '9999-12-31') as sort_pub,
                       games.flags";
        $tableList = "games
                      left join ".getGameRatingsView($db)." on games.id = gameid";
        $baseWhere = "";
        $groupBy = "group by games.id";
        $baseOrderBy = "sort_title";
        $matchCols = "title, author, `desc`, tags";
        $likeCol = "title";
        $summaryDesc = "Games";
    }

    // list of exact phrases for &#xxxx; sequences - none so far
    $exacts = false;

    // parse the search
    for ($ofs = 0, $len = strlen($term), $words = array(),
         $specials = array(), $specialsUsed = array() ; ; $ofs++)
    {
        // skip spaces
        if (!preg_match("/\S/", $term, $m, PREG_OFFSET_CAPTURE, $ofs))
            break;

        // skip to the match
        $ofs = $m[0][1];

        // find the end of this word
        $start = $ofs;
        if ($term{$ofs} == '"') {
            // we have a quoted word
            $quoted = true;

            // the word doesn't end until the matching quote - but
            // skip stuttered quotes
            for ($start++, $ofs++ ; $ofs < $len ; $ofs++) {
                // check for a quote
                if ($term{$ofs} == '"') {
                    // skip stuttered quotes
                    if ($ofs+1 < $len && $term{$ofs+1} == '"')
                        $ofs++;
                    else
                        break;
                }
            }
        } else {
            // it's unquoted
            $quoted = false;

            // the word ends at the next whitespace character
            if (preg_match("/\s/", $term, $m, PREG_OFFSET_CAPTURE, $ofs))
                $ofs = $m[0][1];
            else
                $ofs = $len;
        }

        // pull out this word
        $w = substr($term, $start, $ofs - $start);

        // if it's unquoted, check for special prefixes
        if (!$quoted
            && preg_match("/^(-?)([#a-z]+:)/i", $w, $match)
            && isset($specialMap[$m = $match[2]]))
        {
            // set up the new special entry - this is an array with
            // element [0] giving the special descriptor, [1] giving
            // the search text, and [2] giving special flags (e.g.,
            // negation)
            $specials[] = array($specialMap[$m], "", $match[1]);

            // note that we've used this special item
            $specialsUsed[$m] = true;

            // resume parsing from the ":" after the keyword
            $ofs = $start + strlen($match[0]) - 1;
        }
        else
        {
            // Ordinary word - add it to the appropriate list.

            // if we're in a special, note it
            $s = (count($specials) == 0
                  ? null : $specials[count($specials)-1]);

            // if it's quoted, and not in a special list, add back the quotes
            if ($quoted && !$s)
                $w = "\"$w\"";

            // if we have any &#xxxx; sequences, quote the whole string
            if (preg_match("/&#[0-9a-z]{4};/i", $w, $match))
                $exacts[] = "'%" . quoteSqlLike($w) . "%'";

            // If we have any specials, it adds to the last special's
            // match text.  Otherwise, it adds a word to the full
            // text search list.
            if ($s) {
                if ($s[1])
                    $s[1] .= " $w";
                else
                    $s[1] = $w;
                $specials[count($specials)-1] = $s;
            }
            else
                $words[] = $w;
        }
    }

    // start out the WHERE and HAVING clauses as nothing
    $where = "";
    $having = "";
    $relevance = "";

    // add in the full-text part, if applicable
    if (count($words))
    {
        // Run through the match words to see if we have all negated
        // terms.  If we do, we want to invert the search with NOT MATCH,
        // because MySQL otherwise would fairly uselessly interpret the
        // query as "<empty set> minus <exclusion words>".  We want to
        // interpreter this as "<everything> minus <exclusions>" instead.
        $notCount = 0;
        $matchMode = "";
        foreach ($words as $w) {
            if (substr($w, 0, 1) == '-')
                $notCount++;
        }
        if ($notCount == count($words)) {
            $matchMode = "not";
            $words = array_map("unNegate", $words);
        }

        // build the MATCH..AGAINST expression
        $matchWords = mysql_real_escape_string(implode(" ", $words), $db);
        $matchExpr = "match ($matchCols) "
                     . "against ('$matchWords' in boolean mode)";

        // add the LIKE expression for exact title matching
        $likeWords =
            mysql_real_escape_string(quoteSqlLike(implode(" ", $words)), $db);
        $likeExpr = "($likeCol like '$likeWords' ";
        $likeSubExpr = $and = "";
        foreach ($words as $w) {
            $w = mysql_real_escape_string(quoteSqlLike($w), $db);
            if ($w != "" && $w{0} != '"') {
                if (strpos("+=<>\"", $w{0}) !== false)
                    $w = substr($w, 1);
                $likeSubExpr .= "$and $likeCol like '%$w%'";
                $and = " and";
            }
        }
        if ($likeSubExpr != "")
            $likeExpr .= " or ($likeSubExpr)";
        $likeExpr .= ")";

        // build the full expression
        $expr = "($matchMode ($matchExpr or $likeExpr))";

        // add the exact matches for &#xxxx; phrases
        if ($exacts) {
            $ec = false;
            foreach (explode(",", $matchCols) as $c) {
                $ec1 = false;
                foreach ($exacts as $e)
                    $ec1[] = "$c like $e";
                $ec[] = "(" . implode(" and ", $ec1) . ")";
            }
            $ec = implode(" or ", $ec);
            $expr = "($expr and ($ec))";
        }

        // use it as the start of the WHERE clause
        $where = $expr;

        // it's also the RELEVANCE column in the query
        $relevance = ", $matchExpr as relevance";
    }

    // add the specials
    $extraJoins = array();
    foreach ($specials as $s)
    {
        // pull out the descriptor and search text
        $desc = $s[0];
        $col = $desc[0];
        $typ = $desc[1];
        $txt = $s[1];
        $forHaving = count($desc) >= 3 && $desc[2];
        $expr = "1";
        $negate = (strpos($s[2], '-') !== false);

        // build the appropriate expression, based on the descriptor type
        switch ($typ) {
        case 0:
            // simple text match
            $txt = mysql_real_escape_string(quoteSqlRLike($txt), $db);
            $txt = str_replace(" ", " +", $txt);
            if ($txt != "")
                $expr = "$col RLIKE '$txt'";
            else
                $expr = "($col = '' or $col is null)";
            break;

        case 1:
            // numeric range match
            if ($txt == "")
                $expr = "$col is null";
            else if (preg_match("/^([0-9.]+)-([0-9.]+)$/", $txt, $m))
                $expr = "$col >= '{$m[1]}' AND $col <= '{$m[2]}'";
            else if (preg_match("/^([0-9.]+)[+-]$/", $txt, $m))
                $expr = "$col >= '{$m[1]}'";
            else if (preg_match("/^-([0-9.]+)$/", $txt, $m))
                $expr = "$col <= '{$m[1]}'";
            else if (preg_match("/^[0-9.]+$/", $txt))
                $expr = "$col = '$txt'";
            else
                $expr = "$col = '" . mysql_real_escape_string($txt, $db) . "'";
            break;

        case 2:
            // simple numeric equivalence
            if ($txt == "")
                $expr = "$col is null";
            else {
                $txt = (int)$txt;
                $expr = "$col = '$txt'";
            }
            break;

        case 3:
            // whole-word text matches
            $expr = "$col rlike '[[:<:]]"
                    . mysql_real_escape_string(quoteSqlRLike($txt), $db)
                    . "[[:>:]]'";
            break;

        case 99:
            // special-case handling
            switch ($col) {
            case '/ifid/':
                // we need to join the IFIDS table for this query
                if (!isset($extraJoins[$col])) {
                    $extraJoins[$col] = true;
                    $tableList .= " left outer join ifids "
                                  . "on games.id = ifids.gameid";
                }

                // add the condition - if the text is empty, we're looking
                // for games without any IFIDs, meaning the IFID column
                // comes up null; otherwise we're looking for an exact
                // match to the given IFID, but ignoring case
                $txt = mysql_real_escape_string($txt, $db);
                if ($txt != "")
                    $expr = "lower(ifids.ifid) = lower('$txt')";
                else
                    $expr = "ifids.ifid is null";
                break;

            case '/downloadable/':
                // add the gamelinkstats join if necessary
                if (!isset($extraJoins[$col])) {
                    $extraJoins[$col] = true;
                    $tableList .= " left outer join gamelinkstats as gls "
                                  . "on gls.gameid = games.id";
                }

                // we need yes=non-zero/no=zero game links
                $op = (preg_match("/^y.*/i", $txt) ? "!=" : "=");
                $expr = "gls.numGameLinks $op 0";
                break;

            case 'authorid':
                // need to join the gameprofilelinks table to do this query
                if (!isset($extraJoins[$col])) {
                    $extraJoins[$col] = true;
                    $tableList .= " inner join gameprofilelinks as gpl "
                                  . "on gpl.gameid = games.id "
                                  . "and gpl.userid = '$txt'";
                }
                break;

            case 'played':
                // Only use this query when the user is logged in
                if ($curuser) {
                    // need to join the playedgames table to do this query
                    if (!isset($extraJoins[$col])) {
                        $extraJoins[$col] = true;
                        $tableList .= " left join playedgames as pg "
                                      . "on games.id = pg.gameid "
                                      . "and pg.userid = '$curuser'";
                    }

                    // we need yes=not-null/no=null game ids
                    $op = (preg_match("/^y.*/i", $txt) ? "is not" : "is");
                    $expr = "pg.gameid $op null";
                }
                break;

            case 'willplay':
                // Only use this query when the user is logged in
                if ($curuser) {
                    // need to join the wishlists table to do this query
                    if (!isset($extraJoins[$col])) {
                        $extraJoins[$col] = true;
                        $tableList .= " left join wishlists as wl "
                                      . "on games.id = wl.gameid "
                                      . "and wl.userid = '$curuser'";
                    }

                    // we need yes=not-null/no=null game ids
                    $op = (preg_match("/^y.*/i", $txt) ? "is not" : "is");
                    $expr = "wl.gameid $op null";
                }
                break;

            case 'wontplay':
                // Only use this query when the user is logged in
                if ($curuser) {
                    // need to join the unwishlists table to do this query
                    if (!isset($extraJoins[$col])) {
                        $extraJoins[$col] = true;
                        $tableList .= " left join unwishlists as ul "
                                      . "on games.id = ul.gameid "
                                      . "and ul.userid = '$curuser'";
                    }

                    // we need yes=not-null/no=null game ids
                    $op = (preg_match("/^y.*/i", $txt) ? "is not" : "is");
                    $expr = "ul.gameid $op null";
                }
                break;

            case 'author':
                // get the names in sorting format
                $nameList = splitPersonalNameList($txt);

                // look for each name in the list
                $expr = "(";
                $or = "";
                foreach ($nameList as $n) {
                    // look for this exact name embedded in the author field
                    $expr .= "$or author like '%"
                             . mysql_real_escape_string(quoteSqlLike($n), $db)
                             . "%' ";

                    // get the sorting version of the name - LAST, SUFFIX,
                    // FIRST, MIDDLE, and split into an array
                    $nl = array_map("trim", explode(",",
                        getSortingPersonalName($n)));

                    // if the second element is a suffix, add it back to
                    // the last name
                    if (count($nl) > 1 && isNameSuffix($nl[1])) {
                        $nl[0] .= ", {$nl[1]}";
                        $nl = array_splice($nl, 1, 1);
                    }

                    // now look for <first initial>% <last name>
                    if (count($nl) >= 2) {
                        $expr .= " or author rlike '[[:<:]]"
                                 . mysql_real_escape_string(
                                     quoteSqlRLike($nl[1]{0}), $db)
                                 . ".*[[:space:]]+"
                                 . mysql_real_escape_string(
                                     quoteSqlRLike($nl[0]), $db)
                                     . "[[:>:]]'";
                    }

                    // join the next round with OR
                    $or = " or";
                }
                $expr .= ")";
                break;

            case 'language':
                // If it's a three-letter code, look up the two-letter
                // equivalent; if it's a two-letter code, look up all
                // three-letter equivalents; if it's longer, look up
                // the two- and three-letter codes by name
                $qtxt = mysql_real_escape_string($txt, $db);
                if (strlen($txt) == 0) {
                    $expr = "(language = '' or language is null)";
                } else {
                    if (strlen($txt) == 2)
                        $sql = "select id3 from iso639x where id2='$qtxt'";
                    else if (strlen($txt) == 3)
                        $sql = "select id2 from iso639x where id3='$qtxt'";
                    else if (strlen($txt) > 0) {
                        $qtxt = mysql_real_escape_string(
                            quoteSqlLike($txt), $db);
                        $sql =
                            "select id2 from iso639x where name like '%$qtxt%'
                             union
                             select id3 from iso639x where name like '%$qtxt%'";
                    }

                    $result = mysql_query($sql, $db);

                    // build the expression - match the original string or any
                    // of the results
                    $qtxt = mysql_real_escape_string(quoteSqlLike($txt), $db);
                    $expr = "(language rlike '(^|[, ]){$qtxt}[[:>:]]'";
                    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
                        list($cl) = mysql_fetch_row($result);
                        if ($cl)
                            $expr .= " or language rlike '(^|[, ]){$cl}[[:>:]]'";
                    }
                    $expr .= ")";
                }
                break;

            case '/gameformat/':
                // we need a join that looks for the selected formats
                if (!isset($extraJoins[$col]))
                {
                    // note that we've added the join for this query
                    $extraJoins[$col] = true;

                    // set up the join: join with the download links
                    // for this game
                    $tableList .= " join gamelinks as gl "
                                  .   "on gl.gameid = games.id "
                                  . "join filetypes as ft "
                                  .   "on ft.id = gl.fmtid "
                                  .   "and ft.fmtclass in ('X', 'G') "
                                  . "left outer join operatingsystems as os "
                                  .   "on os.id = gl.osid";
                }

                // Include only rows matching this format name.  For
                // executables, substitute the operating system for the
                // format name.
                $txt = mysql_real_escape_string(quoteSqlLike($txt), $db);
                $txt = str_replace('*', '%', $txt);
                $expr = "if(ft.fmtclass = 'G', ft.fmtname, os.name) "
                        . "like '$txt'";
                break;

            case '/clubname/':
                // we need club memberships added to the list
                if (!isset($extraJoins[$col]))
                {
                    // note that we've added the joins for this query
                    $extraJoins[$col] = true;

                    // if we're logged in, we can see the membership lists
                    // for clubs we belong to, even if they're private
                    $orMember = $joinMyClubs = "";
                    if ($curuser) {
                        $orMember = "or mcur.userid is not null";
                        $joinMyClub = "left outer join clubmembers as mcur "
                                      . "on mcur.clubid = m.clubid "
                                      . "and mcur.userid = '$curuser'";
                    }

                    // set up the join: join with each club membership
                    // per user, for each membership join with the club
                    // information to get the club name
                    $tableList .= " join clubmembers as m
                                      on m.userid = u.id
                                    $joinMyClub
                                    join clubs as c
                                      on c.clubid = m.clubid
                                      and (c.members_public = 'Y' $orMember)";

                    // ... and then group the results by user, since we
                    // only want to keep one row per user
                    $groupBy = "group by u.id";
                }

                // quote the club name
                $txt = mysql_real_escape_string(quoteSqlLike($txt), $db);

                // include only rows matching this club name
                $expr = "c.name like '%$txt%'";
                break;

            case '/hyperlinks/':
                $expr = "u.profile rlike '<[[:space:]]*a[[:space:]]+href'";
                break;
            }
        }

        // if the expression is negated, negate it
        if ($negate)
            $expr = " NOT ($expr)";

        // add it to the WHERE or HAVING clause under construction
        if ($forHaving) {
            if ($having)
                $having .= " AND $expr";
            else
                $having = $expr;
        } else {
            if ($where)
                $where .= " AND $expr";
            else
                $where = $expr;
        }
    }

    // the sorting control list depends on the table
    $defSortBy = false;
    switch ($searchType)
    {
    case "game":
        if ($browse) {
            $sortList = array(
                'ratu' => array('starsort desc,',
                                'Highest Rated First'),
                'ratd' => array('starsort,',
                                'Lowest Rated First'),
                'lnew' => array('games.created desc,', 'Newest Listing First'),
                'lold' => array('games.created,', 'Oldest Listing First'),
                'rcu' => array('ratingcnt desc, starsort desc,',
                               'Most Ratings First'),
                'rcd' => array('ratingcnt, starsort desc,',
                               'Fewest Ratings First'),
                'ttl' => array('sort_title,', 'Sort by Title'),
                'auth' => array('sort_author,', 'Sort by Author'),
                'pnew' => array('published desc,', 'Latest Publication First'),
                'pold' => array('sort_pub,', 'Earliest Publication First'),
                'rand' => array('rand(),', 'Random Order'));
            $defSortBy = 'ratu';
        } else {
            $sortList = array(
                'ratu' => array('starsort desc,', 'Highest Rated First'),
                'ratd' => array('starsort,', 'Lowest Rated First'),
                'lnew' => array('games.created desc,', 'Newest Listing First'),
                'lold' => array('games.created,', 'Oldest Listing First'),
                'ttl' => array('sort_title,', 'Sort by Title'),
                'auth' => array('sort_author,', 'Sort by Author'),
                'rcu' => array('ratingcnt desc, starsort desc,',
                               'Most Ratings First'),
                'rcd' => array('ratingcnt, starsort desc,',
                               'Fewest Ratings First'),
                'rsdu' => array('ratingdev desc, starsort desc,',
                                'Rating Deviation - High to Low'),
                'rsdd' => array('ratingdev, starsort desc,',
                                'Rating Deviation - Low to High'),
                'new' => array('published desc,', 'Latest Publication First'),
                'old' => array('sort_pub,', 'Earliest Publication First'),
                'rand' => array('rand(),', 'Random Order'));
            if (count($words)) {
                $defSortBy = 'rel';
            } else {
                $defSortBy = 'ratu';
            }
        }

        if (!isset($specialsUsed['ratingdev:'])) {
            unset($sortList['rsdu']);
            unset($sortList['rsdd']);
        }
        break;

    case "list":
        $sortList = array(
            'new' => array('moddate desc,', 'Newest First'),
            'old' => array('moddate,', 'Oldest First'),
            'ttl' => array('title,', 'Sort by List Title'),
            'usr' => array('username,', 'Sort by List Author'),
            'rand' => array('rand(),', 'Random Order'));
        $defSortBy = 'new';
        break;

    case "poll":
        $sortList = array(
            'new' => array('p.created desc,', 'Newest First'),
            'old' => array('p.created,', 'Oldest First'),
            'ttl' => array('title,', 'Sort by Poll Title'),
            'usr' => array('username,', 'Sort by Poll Author'),
            'newvote' => array('votedate desc,', 'Latest Vote First'),
            'oldvote' => array('votedate,', 'Longest Since Last Vote First'),
            'votes' => array('votecnt desc,', 'Most Votes First'),
            'votesd' => array('votecnt,', 'Least Votes First'),
            'games' => array('gamecnt desc,', 'Most Games First'),
            'gamesd' => array('gamecnt,', 'Least Games First'),
            'rand' => array('rand(),', 'Random Order'));
        $defSortBy = 'new';
        break;

    case "member":
        $sortList = array(
            'nm' => array('name,', 'Sort by Name'),
            'ffpts' => array('userScores.score desc,',
                             'Highest Frequent Fiction First'),
            'ffrank' => array('userScores.rankingScore desc,',
                              'Top Reviewer Status First'),
            'loc' => array('location,', 'Sort by Location'),
            'new' => array('created desc,', 'Newest First'),
            'old' => array('created,', 'Oldest First'),
            'rand' => array('rand(),', 'Random Order'));
        $defSortBy = 'nm';
        break;

    case "comp":
        $sortList = array(
            'awn' => array('ifnull(c.awarddate, c.qualclose) desc,', 'Newest First'),
            'awo' => array('ifnull(c.awarddate, c.qualclose),', 'Oldest First'),
            'nm' => array('lower(c.title),', 'Sort by Name'),
            'numgd' => array('count(g.gameid) desc,', 'Most Entries First'),
            'numgu' => array('count(g.gameid),', 'Fewest Entries First'),
            'org' => array('lower(c.organizers),', 'Sort by Organizer Name'),
            'rand' => array('rand(),', 'Random Order'));
        $defSortBy = 'awn';
        break;

    case "club":
        $sortList = array(
            'new' => array('c.created desc,', 'Newest First'),
            'old' => array('c.created,', 'Oldest First'),
            'name' => array('c.name,', 'Sort by Name'),
            'mcd' => array('membercnt desc,', 'Most Members First'),
            'mcu' => array('membercnt,', 'Fewest Members First'),
            'con' => array('lower(c.contacts),', 'Sort by Contact Name'),
            'rand' => array('rand(),', 'Random Order'));
        $defSortBy = 'new';
        break;

    case "tag":
        $sortList = array(
            'name' => array('gt.tag,', 'Sort by Tag Name'),
            'rand' => array('rand(),', 'Random Order'));
        $defSortBy = 'name';
        break;

    default:
        $sortList = array();
        break;
    }

    // add Relevance as a sort option if doing a search with terms
    if (!$browse && count($words)) {
        $sortList = array('rel' => array('', 'Sort by Relevance'))
                    + $sortList;
        $defSortBy = 'rel';
    }

    // figure the ordering
    $orderBy = ((isset($sortList[$sortby])) ? $sortList[$sortby][0] :
                ($defOrderBy ? $defOrderBy :
                 ($defSortBy ? $sortList[$defSortBy][0] :
                  "")));

    // Add the relevance to the ORDER BY.  Put exact matches to the LIKE
    // string at the top of the list; then add leading string matches;
    // then add substring matches; and finally, sort by MySQL's full-text
    // "relevance" ranking.
    if ($relevance)
        $orderBy .= "if($likeCol = '$likeWords',0,1),"
                    . "if($likeCol like '$likeWords%',0,1),"
                    . "if($likeExpr,0,1),"
                    . "relevance desc,";

    // If we're selecting from the user table, and we need the
    // Frequent Fiction information, set that up.
    if ($searchType == "member" && preg_match("/^userScores\./", $orderBy)) {

        // we need the UserScores temporary table - go build it
        createFFTempTable($db);

        // add it to the select list and table list
        $selectList .= ", userScores.score as score";
        $tableList .= " left outer join userScores "
                      . "on userScores.userid = u.id";
    }

    // if there's no WHERE clause, select anything
    if ($where == "")
        $where = "1";

    // if there's a HAVING clause, plug in the HAVING phase
    if ($having != "")
        $having = "having $having";

    // build the SELECT statement
    $sql = "select sql_calc_found_rows
              $selectList
              $relevance
            from
              $tableList
            where
              $where
              $baseWhere
            $groupBy
            $having
            order by
              $orderBy
              $baseOrderBy
            $limit";

    // run the query
    $result = mysql_query($sql, $db);
    if (!$result) error_log(mysql_error($db));
//    echo "<p>$sql<p>" . mysql_error($db) . "<p>";  // DIAGNOSTICS

    $errMsg = false;
    if ($result) {
        // fetch the results
        for ($rows = array(), $i = 0 ; $i < mysql_num_rows($result) ; $i++)
            $rows[] = mysql_fetch_array($result, MYSQL_ASSOC);

//        foreach ($rows as $row) {                 // DIAGNOSTICS
//            echo "{";
//            foreach ($row as $col=>$val) {
//                echo "$col=" . htmlspecialcharx($val) . "; ";
//            }
//            echo "}<br>";
//        }

        // get the total size of the result set
        $result = mysql_query("select found_rows()", $db);
        list($rowcnt) = mysql_fetch_row($result);

    } else {
        $rows = array();
        $rowcnt = 0;
        $errMsg = "<p><span class=errmsg>"
                  . "An error occurred searching the database.</span><p>";

// DEBUG
//        if (get_req_data('debug') == 'SEARCH')
//            $errMsg = "<p><span class=errmsg>Database error:</span><div style='margin: 1em 1em 1em 1em;'>" . mysql_error($db)
//                      . "</div>Query:<div style='margin: 1em 1em 1em 1em;'>" . htmlspecialcharx($sql) . "</div>";
    }

    // return the results
    return array($rows, $rowcnt, $sortList, $errMsg, $summaryDesc,
                 $badges, $specials, $specialsUsed, $orderBy);
}

?>
