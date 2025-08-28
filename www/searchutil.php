<?php

include_once "util.php";
include_once "login-persist.php";

// is the given string a name suffix?
function isNameSuffix($s)
{
    return in_array(strtolower($s),
                    array("jr", "jr.", "sr", "sr.", "phd", "ph.d.",
                          "md", "m.d."));
}

function unNegate($w)
{
    if (substr($w, 0, 1) == '-')
        $w = substr($w, 1);
    return $w;
}

// Convert a string in the format "1h45m" to the total number of minutes.
// If the string is empty or doesn't match the patterns, return "".
function convertTimeStringToMinutes($h_m_string) {
    $time_in_minutes = "";
    
    if (preg_match("/^([0-9.]+)h([0-9]+)m$/", $h_m_string, $matches)) {
         // String includes both hours and minutes.
         $time_in_minutes = ($matches[1] * 60) + $matches[2];
         
     } else if (preg_match("/^([0-9.]+)h$/", $h_m_string, $matches)) {
         // String includes only hours.
         $time_in_minutes = $matches[1] * 60;
            
     } else if (preg_match("/^([0-9]+)m$/", $h_m_string, $matches)) {
         // String includes only minutes.
         $time_in_minutes = $matches[1];
            
     } else if (preg_match("/^([0-9]+)$/", $h_m_string, $matches)) {
         // No units were given, so we'll assume minutes.
         $time_in_minutes = $matches[1];
     };
     return $time_in_minutes;
}


// Construct a message telling the user that the game results were filtered
function writeGamesFilteredAnnouncement($page, $sort_order, $search_term) {
    $games_filtered_announcement = 'Your account is set up to use a game filter by default, and that filter was applied on this page. You can ';
    if ($page == "search_games") {
        $games_filtered_announcement .= '<a href="search?sortby=' . $sort_order . '&searchfor=' . $search_term . '&nogamefilter=1">search again without the filter</a>.';
    } else if ($page == "browse_games") {
        $games_filtered_announcement .= 'also <a href="search?browse&sortby=' . $sort_order . '&nogamefilter=1">browse without the filter</a>.';
    } else if ($page == "all_new_reviews") {
        $games_filtered_announcement .= 'also <a href="allnew?reviews&nogamefilter=1">browse without the filter</a>.';
    }
    return $games_filtered_announcement;
}


function doSearch($db, $term, $searchType, $sortby, $limit, $browse, $count_all_possible_rows = false, $override_game_filter = 0)
{
    // we need the current user for some types of queries
    checkPersistentLogin();
    $curuser = mysql_real_escape_string($_SESSION['logged_in_as'] ?? '', $db);

    // set up the mute filter
    $andNotMuted = "";
    if ($curuser) {
        $andNotMuted = " and (select count(*) from userfilters "
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

    // So far, we haven't applied a custom game filter
    $games_were_filtered = false;
    
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
                                   $andNotMuted);
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
                     . str_replace('#USERID#', 'p.userid', $andNotMuted);
        $groupBy = "group by p.pollid";
        $baseOrderBy = "title";
        $matchCols = "title, keywords";
        $likeCol = "title";
        $summaryDesc = "Polls";
    }
    else if ($searchType == "member")
    {
        // special keywords for member search
        $specialMap = array(
            "location:" => array("location", 0),
            "hyperlinks:" => array("/hyperlinks/", 99));

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
                     . str_replace('#USERID#', 'u.id', $andNotMuted);
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
        $specialMap = [
            "tuid:" => ["tagtuid", 99],
            "mine:" => ["mine", 99],
        ];

        $selectList = "gt.tag as tag, count(distinct gt.gameid) as gamecnt";
        $tableList = "gametags as gt";
        $baseWhere = "";
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
            "published:" => array("published", 4),
            "added:" => array("created", 4),
            "system:" => array("system", 0),
            "series:" => array("seriesname", 0),
            "tag:" => array("tags", 5),
            "bafs:" => array("bafsid", 2),
            "rating:" => array("avgRating", 1, true),
            "#reviews:" => array("numMemberReviews",1, true),
            "ratingdev:" => array("stdDevRating", 1, true),
            "#ratings:" => array("numRatingsInAvg", 1, true),
            "forgiveness:" => array("forgiveness", 0),
            "language:" => array("language", 99),
            "author:" => array("author", 99),
            "authorid:" => array("authorid", 99),
            "ifid:" => array("/ifid/", 99),
            "tuid:" => array("/tuid/", 99),
            "downloadable:" => array("/downloadable/", 99),
            "playtime:" => array("rounded_median_time_in_minutes", 99),
            "played:" => array("played", 99),
            "willplay:" => array("willplay", 99),
            "wontplay:" => array("wontplay", 99),
            "reviewed:" => array("reviewed", 99),
            "rated:" => array("rated", 99),
            "license:" => array("license", 0),
            "competitionid:" => array("competitionid", 99),
            "format:" => array("/gameformat/", 99),
            // "lastreview:" is not documented for users. It's used in newitems.php
            // as part of finding new reviews to display when a custom game filter 
            // is applied.
            "lastreview:" => array("lastReviewDate", 4));


        // SELECT parameters for game queries
        $selectList = "games.id as id,
                       games.title as title,
                       games.author as author,
                       games.desc as description,
                       games.tags as tags,
                       games.created as createdate,
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
                       lastReviewDate,
                       starsort,
                       games.sort_title as sort_title,
                       games.sort_author as sort_author,
                       ifnull(games.published, '9999-12-31') as sort_pub,
                       games.pagevsn,
                       games.flags";
        $baseWhere = "";
        $groupBy = "";
        $baseOrderBy = "";
        $tableList = "games
                      join ".getGameRatingsView($db)." on games.id = gameid";
        $matchCols = "title, author, `desc`, tags";
        $likeCol = "title";
        $summaryDesc = "Games";
    

        // Handle custom game filters
        if ($curuser && $override_game_filter != 1) {
            // We're logged in, and haven't been told to override a custom game filter, so check for one
            $result = mysqli_execute_query($db, "select game_filter from users where id = ?", [$curuser]);
            if (!$result) throw new Exception("Error: " . mysqli_error($db));
            [$gameFilter] = mysql_fetch_row($result);
            if ($gameFilter) {
                // We've found a custom game filter, so add it to the end of the search term
                $games_were_filtered = true;
                $term .= " $gameFilter";
            }
        }
    } 

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
        if ($term[$ofs] == '"') {
            // we have a quoted word
            $quoted = true;

            // the word doesn't end until the matching quote - but
            // skip stuttered quotes
            for ($start++, $ofs++ ; $ofs < $len ; $ofs++) {
                // check for a quote
                if ($term[$ofs] == '"') {
                    // skip stuttered quotes
                    if ($ofs+1 < $len && $term[$ofs+1] == '"')
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
            && isset($specialMap[$m = mb_strtolower($match[2])]))
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
    $tagsToMatch = [];
    $tagsToNegate = [];

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
            if ($w != "" && $w[0] != '"') {
                if (strpos("+=<>\"", $w[0]) !== false)
                    $w = substr($w, 1);
                $likeSubExpr .= "$and $likeCol like '%$w%'";
                $and = " and";
            }
        }
        if ($likeSubExpr != "")
            $likeExpr .= " or ($likeSubExpr)";
        $likeExpr .= ")";

        // build the full expression, use it as the start of the WHERE clause
        $where = "$matchMode $matchExpr";

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
        $negate = ($s[2] === '-');

        // build the appropriate expression, based on the descriptor type
        switch ($typ) {
        case 0:
            // simple text match
            $txt = mysql_real_escape_string(quoteSqlRLike($txt), $db);
            $txt = str_replace(" ", " +", $txt);
            if ($txt != "")
                $expr = "ifnull($col, '') RLIKE '$txt'";
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
            // whole-word text matches - currently unused
            $expr = "$col rlike '[[:<:]]"
                    . mysql_real_escape_string(quoteSqlRLike($txt), $db)
                    . "[[:>:]]'";
            break;
        case 4:
            // timestamps
            $year = "date_format($col, '%Y')";
            if ($txt == "")
                $expr = "$col is null";
            else if (preg_match("/^([0-9.]+)-([0-9.]+)$/", $txt, $m))
                $expr = "$year >= '{$m[1]}' AND $year <= '{$m[2]}'";
            else if (preg_match("/^([0-9.]+)[+-]$/", $txt, $m))
                $expr = "$year >= '{$m[1]}'";
            else if (preg_match("/^-([0-9.]+)$/", $txt, $m))
                $expr = "$year <= '{$m[1]}'";
            else if (preg_match("/^[0-9.]+$/", $txt))
                $expr = "$year = '$txt'";
            else if (preg_match("/^([0-9.]+)d-([0-9.]+)d$/", $txt, $m))
                $expr = "$col >= date_sub(now(), interval {$m[1]} day) AND $col <= date_sub(now(), interval {$m[2]} day)";
            else if (preg_match("/^([0-9.]+)d[+-]$/", $txt, $m))
                $expr = "$col >= date_sub(now(), interval {$m[1]} day)";
            else if (preg_match("/^-([0-9.]+)d$/", $txt, $m))
                $expr = "$col <= date_sub(now(), interval {$m[1]} day)";
            else if (preg_match("/^([0-9.]+)d$/", $txt, $m)) {
                $day = $m[1];
                $dayBefore = $day + 1;
                $expr = "$col >= date_sub(now(), interval $dayBefore day) AND $col <= date_sub(now(), interval $day day)";
            } else
                $expr = "$year = '" . mysql_real_escape_string($txt, $db) . "'";
            break;
        case 5:
            // tags
            if ($negate) {
                $tagsToNegate[] = $txt;
                $negate = false; // Turn off negate flag as tags are handled separately
            } else {
                $tagsToMatch[] = $txt;
            }
            break;
        case 99:
            // special-case handling
            switch ($col) {
            case '/tuid/':
                $txt = mysql_real_escape_string($txt, $db);
                $expr = "games.id = lower('$txt')";
                break;
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
                    $expr = "lower_ifid = lower('$txt')";
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
                    $txt = mysql_real_escape_string($txt, $db);
                    $tableList .= " inner join gameprofilelinks as gpl "
                                  . "on gpl.gameid = games.id "
                                  . "and gpl.userid = '$txt'";
                }
                break;
            
            case 'competitionid':
                // need to join the compgames table to do this query
                if (!isset($extraJoins[$col])) {
                    $extraJoins[$col] = true;
                    $txt = mysql_real_escape_string($txt, $db);
                    $tableList .= " inner join (select distinct gameid from compgames where compid = '$txt') as compgames "
                                  . "on compgames.gameid = games.id";
                }
                break;

            case 'rounded_median_time_in_minutes':
                // we need to join the gametimes mv table for this query    
                if (!isset($extraJoins[$col])) {
                    $extraJoins[$col] = true;
                    $tableList .= " left outer join gametimes_mv "
                                  . "on games.id = gametimes_mv.gameid";
                }

                // Add estimated play time to the select list so we can display it in results
                $selectList .= ", rounded_median_time_in_minutes";

                // numeric range match
                // zero limit
                if ($txt == "") {
                    $expr = "$col is null";
                    break;
                };

                // Break $txt at the hyphen to find out what times were entered    
                $array_of_times = explode('-', $txt);
                if (count($array_of_times) == 2) {
                    // There's a hyphen dividing $txt into two parts, so we're 
                    // looking for a minimum time and a maximum time.
                    $minimum = convertTimeStringToMinutes($array_of_times[0]);
                    $maximum = convertTimeStringToMinutes($array_of_times[1]);
                    if ($minimum != "" && $maximum != "") {
                        // There's both a minimum number and a maximum number.
                        $expr = "$col >= '{$minimum}' AND $col <= '{$maximum}'";
                    } else if ($minimum != "" && $maximum == "") {
                        // There's only a minimum.
                        $expr = "$col >= '{$minimum}'";
                    } else if ($minimum == "" && $maximum != "") {
                        // There's only a maximum.
                        $expr = "$col <= '{$maximum}'";
                    } else {
                        // Neither minimum nor maximum time is valid, so ignore the whole thing.
                        $expr = "";
                    }
                } else if (count($array_of_times) == 1) {
                    // No hyphen was entered, so it's an exact time.
                    $exact_time = convertTimeStringToMinutes($txt);
                    if ($exact_time != "") {
                        $expr = "$col = '$exact_time'";
                    } else {
                        // The time didn't convert to a valid pattern, so ignore the whole thing
                        $expr = "";
                    }
                } else {
                    // We've got multiple hyphens, so ignore the whole thing. 
                    $expr = "";
                }
                break;

            case 'played':
                // Only use this query when the user is logged in
                if (!$curuser) {
                    break;
                }

                $not = (preg_match("/^y.*/i", $txt) ? "" : "not");
                $expr = "games.id $not in (select gameid from playedgames where userid = '$curuser')";
                break;

            case 'willplay':
                // Only use this query when the user is logged in
                if (!$curuser) {
                    break;
                }

                $not = (preg_match("/^y.*/i", $txt) ? "" : "not");
                $expr = "games.id $not in (select gameid from wishlists where userid = '$curuser')";
                break;

            case 'wontplay':
                // Only use this query when the user is logged in
                if (!$curuser) {
                    break;
                }

                $not = (preg_match("/^y.*/i", $txt) ? "" : "not");
                $expr = "games.id $not in (select gameid from unwishlists where userid = '$curuser')";
                break;


            case 'reviewed':
                // Only use this query when the user is logged in
                if (!$curuser) {
                    break;
                }

                $not = (preg_match("/^y.*/i", $txt) ? "" : "not");
                $expr = "games.id $not in (select gameid from reviews where review is not null and userid = '$curuser')";
                break;

            case 'rated':
                // Only use this query when the user is logged in
                if (!$curuser) {
                    break;
                }

                $not = (preg_match("/^y.*/i", $txt) ? "" : "not");
                $expr = "games.id $not in (select gameid from reviews where rating is not null and userid = '$curuser')";
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
                                     quoteSqlRLike($nl[1][0]), $db)
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

            case '/hyperlinks/':
                $expr = "u.profile rlike '<[[:space:]]*a[[:space:]]+href'";
                break;

            case "tagtuid":
                $txt = mysql_real_escape_string($txt, $db);
                $expr = "gt.gameid = '$txt'";
                break;

            case "mine":
                // Only use this query when the user is logged in
                if (!$curuser) {
                    break;
                }
                $op = (preg_match("/^y.*/i", $txt) ? "=" : "!=");
                $expr = "gt.userid $op '$curuser'";
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
                'long'  => array('rounded_median_time_in_minutes desc, starsort desc,', 'Longest First'),
                'short' => array('-rounded_median_time_in_minutes desc, starsort desc,', 'Shortest First'),
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
                'long'  => array('rounded_median_time_in_minutes desc, starsort desc,', 'Longest First'),
                'short' => array('-rounded_median_time_in_minutes desc, starsort desc,', 'Shortest First'),
                'recently_reviewed' => array('lastReviewDate desc,', 'Recently Reviewed First'),
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
            'ffpts' => array('userScores_mv.score desc,',
                             'Highest Frequent Fiction First'),
            'ffrank' => array('userScores_mv.rankingScore desc,',
                              'Top Reviewer Status First'),
            'nm' => array('name,', 'Sort by Name'),
            'loc' => array('location,', 'Sort by Location'),
            'new' => array('created desc,', 'Newest First'),
            'old' => array('created,', 'Oldest First'),
            'rand' => array('rand(),', 'Random Order'));
        $defSortBy = 'ffpts';
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

    case "tag":
        $sortList = [
            'games' => ['gamecnt desc,', 'Most Games First'],
            'name' => ['gt.tag,', 'Sort by Tag Name'],
            'rand' => ['rand(),', 'Random Order']
        ];
        $defSortBy = 'games';
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
                ($defSortBy ? $sortList[$defSortBy][0] : ""));

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
    if ($searchType == "member" && preg_match("/^userScores_mv\./", $orderBy)) {

        // add it to the select list and table list
        $selectList .= ", userScores_mv.score as score";
        $tableList .= " left outer join userScores_mv "
                      . "on userScores_mv.userid = u.id";
    }

    // If we're sorting by estimated play time, make sure we select the play time and 
    // join the gametimes materialized view
    if ($searchType == "game" && ($sortby == "short" || $sortby == "long")) {

        if (!isset($specialsUsed['playtime:'])) {
            $selectList .= ", rounded_median_time_in_minutes";
            $tableList .= " left outer join gametimes_mv on games.id = gametimes_mv.gameid";
        }
    }
    

    // Build tags join
    $tagsJoin = "";
    if ($tagsToMatch) {
        // Limit number of tags to avoid abuse
        $tagsToMatch = array_slice($tagsToMatch, 0, 20);

        $tagsTables = [];
        $tagsWhereParts = [];
        $tagsOnParts = [];
        for ($i = 0; $i < count($tagsToMatch); $i++) {
            $t = "gametags as t$i";
            if ($i > 0) {
                $t .= " on t0.gameid = t$i.gameid";
            }
            $tagsTables[] = $t;
            $tagsWhereParts[] = "t$i.tag = ?";
        }
        $tagsSubJoin = implode(" join ", $tagsTables);
        $tagsWhere = implode(" and ", $tagsWhereParts);
        $tagsJoin = "join (select distinct t0.gameid from $tagsSubJoin where $tagsWhere) as gt on games.id = gt.gameid";
    }

    if ($tagsToNegate) {
        $questionMarks = str_repeat("?,", count($tagsToNegate) - 1) . "?";
        $expr = "not exists (select 1 from gametags where games.id=gameid and tag in ($questionMarks))";
        $where .= ($where ? " and " : "") . $expr;
    }

    $logging_level = 0;

    // in game searches, implicitly match by TUID with an early query
    if ($searchType == "game" && count($words) == 1 && count($extraJoins) == 0) {
        $sql = "select games.id as gameid from games where games.id = ?";
        if ($logging_level) {
            error_log($sql);
            error_log($words[0]);
        }
        $result = mysqli_execute_query($db, $sql, [$words[0]]);
        if ($result) {
            $rows[] = mysql_fetch_array($result, MYSQL_ASSOC);
            if (isset($rows) && isset($rows[0])) {
                $where = "games.id = '" . $rows[0]['gameid'] . "'";
            }
        } else {
             error_log(mysql_error($db));
        }
    }

    // if there's no WHERE clause, select anything
    if ($where == "")
        $where = "1";

    // if there's a HAVING clause, plug in the HAVING phase
    if ($having != "")
        $having = "having $having";

    // strip trailing comma
    if ($baseOrderBy == "" && substr($orderBy, -1) == ",") {
        $orderBy = substr($orderBy, 0, -1);
    }

    $sql_calc_found_rows = "sql_calc_found_rows";

    if (($searchType === "game" && !$term) || !$count_all_possible_rows) {
        // `sql_calc_found_rows` forces the query to ignore the `limit` clause in order to
        // count all possible results, which means a full table scan, which can be slow. 
        // But if we're browsing all games, we can skip `sql_calc_found_rows` and do a fast 
        // `count(*)` query instead. If we're searching but we don't need the number of 
        // possible rows, we can skip the counting altogether.

        $sql_calc_found_rows = "";
    }

    // build the SELECT statement
    $sql = "select $sql_calc_found_rows
              $selectList
              $relevance
            from
              $tableList
              $tagsJoin
            where
              $where
              $baseWhere
            $groupBy
            $having
            order by
              $orderBy
              $baseOrderBy
            $limit";

    if ($logging_level) {
        error_log($sql);
    }

    $bindParameters = array_merge($tagsToMatch, $tagsToNegate);

    // run the query
    $result = mysql_execute_query($db, $sql, $bindParameters);
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
        if ($sql_calc_found_rows) {
            $result = mysql_query("select found_rows()", $db);
            [$rowcnt] = mysql_fetch_row($result);
        } else if ($searchType === "game" && !$term) {
            if ($logging_level) error_log("select count(*) from games");
            $result = mysql_query("select count(*) from games", $db);
            [$rowcnt] = mysql_fetch_row($result);
        } else {
            $rowcnt = count($rows);
        }

    } else {
        $rows = array();
        $rowcnt = 0;
        $errMsg = "<p><span class=errmsg>"
                  . "An error occurred searching the database.</span><p>";

// DEBUG
//      if (get_req_data('debug') == 'SEARCH')
//            $errMsg = "<p><span class=errmsg>Database error:</span><p>" . mysql_error($db)
//                      . "</p>Query:<p>" . htmlspecialcharx($sql) . "</p>";
    }

    // return the results
    return array($rows, $rowcnt, $sortList, $errMsg, $summaryDesc,
                 $badges, $specials, $specialsUsed, $orderBy, $games_were_filtered);
}

?>
