
if (isset($_REQUEST['cleanpix'])) {
    echo "<h1>Scanning for unreferenced images</h1>"
        . "<form name=\"fixpix\" method=post action=\"adminops\">"
        . "<input type=hidden name=cleanpix value=1>";

    $ignoreVsnNull = !isset($_REQUEST['showVsnNull']);
    $vsnNullCnt = 0;
    $missingImageCnt = 0;

    if (!$ignoreVsnNull)
        echo "<input type=hidden name='showVsnNull' value=1>";

    // fetch all the picture IDs
    $numImageDBs = (isLocalDev() ? 1 : 5);
    for ($pix = array(), $i = 0 ; $i < $numImageDBs ; $i++) {
        $dbpix = imageDbConnect($i);
        $result = mysql_query("select id from images", $dbpix);
        for ($j = 0 ; $j < mysql_num_rows($result) ; $j++) {
            $id = $i . ":" . mysql_result($result, $j, "id");
            $pix[$id] = 0;
        }
    }

    // now mark all of the referenced images
    $fldcnt = 0;
    $result = mysql_query(
        "select id, coverart from games where coverart is not null", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $id = mysql_result($result, $i, "id");
        $art = adjustImageName(mysql_result($result, $i, "coverart"));
        if (isset($pix[$art]))
            $pix[$art] += 1;
        else {
            echo "missing image $art for game <a href=\"viewgame?id=$id\">"
                . "$id</a> ";

            $fld = "missing-game-$id";
            if (isset($_REQUEST['fixempties'])
                && isset($_REQUEST[$fld])
                && ($newpix = $_REQUEST[$fld]) != ""
                && (strcasecmp($newpix, "none") == 0 || isset($pix[$newpix])))
            {
                $pix[$newpix] += 1;
                $newpix = mysql_real_escape_string($newpix, $db);
                $newpix = (strcasecmp($newpix, "none") == 0
                           ? "NULL" : "'$newpix'");
                $result2 = mysql_query(
                    "update games set coverart = $newpix where id='$id'",
                    $db);
                if ($result2)
                    echo " - successfully updated to $newpix<br>";
                else
                    echo " - error updating: " . mysql_error($db) . "<br>";

            } else {
                echo " - set image to: "
                    . "<input type=text name=\"$fld\" size=50><br>";
                $missingImageCnt += 1;
                $fldcnt += 1;
            }
        }
    }

    $result = mysql_query(
        "select
           games_history.id as id, games_history.deltas as deltas,
           games_history.pagevsn as pagevsn, games.title as title
         from
           games_history
           join games on games_history.id = games.id
         order by
           games.title, games_history.pagevsn", $db);

    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $id = mysql_result($result, $i, "id");
        $vsn = mysql_result($result, $i, "pagevsn");
        $title = htmlspecialcharx(mysql_result($result, $i, "title"));
        $deltas = unserialize(mysql_result($result, $i, "deltas"));
        $art = (isset($deltas["coverart"])
                ? adjustImageName($deltas["coverart"]) : "");
        if (isset($deltas["coverart"])
            && strlen($art) != 0
            && isset($pix[$art]))
        {
            $pix[$art] += 1;
        }
        else if (isset($deltas["coverart"]))
        {
            $errtype = (strlen($art) ? "missing" : "null");
            if (strlen($art) == 0)
            {
                if ($ignoreVsnNull)
                    continue;
                $vsnNullCnt += 1;
            }
            echo "$errtype image $art for game <a href=\"viewgame?"
                . "id=$id&version=$vsn\">$title ($id) v.$vsn</a> ";

            $fld = "empty-$id-$vsn";
            if (isset($_REQUEST['fixempties'])
                && isset($_REQUEST[$fld])
                && ($newpix = $_REQUEST[$fld]) != ""
                && (strcasecmp($newpix, "none") == 0 || isset($pix[$newpix])))
            {
                $pix[$newpix] += 1;
                $deltas["coverart"] = (strcasecmp($newpix, "none") == 0
                                       ? "" : $newpix);
                $sd = mysql_real_escape_string(serialize($deltas), $db);
                $result2 = mysql_query(
                    "update games_history set deltas='$sd'
                     where id='$id' and pagevsn='$vsn'", $db);
                if ($result2)
                    echo " - successfully updated to {$newpix}<br>";
                else
                    echo " - error updating: " . mysql_error($db) . "<br>";

            } else {
                echo " - set image to: "
                    . "<input type=text name=\"$fld\" size=50><br>";
                $missingImageCnt += 1;
                $fldcnt += 1;
            }
        }
    }

    $result = mysql_query(
        "select id, picture from users where picture is not null", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        $id = mysql_result($result, $i, "id");
        $art = adjustImageName(mysql_result($result, $i, "picture"));
        if (isset($pix[$art]))
            $pix[$art] += 1;
        else
            echo "missing image $art for user <a href=\"viewuser?id=$id\">$id</a><br>";
    }

    $result = mysql_query(
        "select p.userid, u.name, p.name, p.picture
        from stylepics as p
          join users as u on p.userid = u.id
        where p.picture is not null", $db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        list($picuid, $picuname, $picfname, $picid) = mysql_fetch_row($result);
        $art = adjustImageName($picid);
        if (isset($pix[$art]))
            $pix[$art] += 1;
        else
            echo "missing image $art for user <a href=\"viewuser?id=$picuid\">"
                . "$picuname</a>, filename \"$picfname\"<br>";
    }

    $cnt = 0;
    foreach ($pix as $id => $refs) {
        if ($refs == 0) {
            $cnt++;
            echo "<a href=\"adminops?showimage=$id\">"
                . "<img border=0 align=left "
                . "src=\"adminops?showimage=$id&thumbnail=100x100\"></a> "
                . "unreferenced image <a href=\"adminops?showimage=$id\">"
                . "$id</a><br clear=all>";

            if (isset($_REQUEST['delete'])) {
                if (delete_image($id))
                    echo " - deleted";
                else
                    echo " - error: not deleted";
            }
            echo "<br>";
        }
    }

    echo "<br><br>";

    if ($missingImageCnt)
        echo "<br>(Enter NONE to set a missing image link to no image.)";

    if (!isset($_REQUEST['delete']) && $cnt != 0) {
        echo "<br><a href=\"adminops?cleanpix&delete\">Delete all "
            . "unreferenced images</a>";
    } else {
        echo "<br><br><br>No unreferenced images found.";
    }

    if ($ignoreVsnNull)
        echo "<br><a href=\"adminops?cleanpix&showVsnNull\">Show "
            . "null image warnings for old versions</a>";

    if ($fldcnt != 0)
        echo "<p><input type=submit name=fixempties value=\"Apply Updates\">";

    echo "</form>";
    echo "<p><hr class=dots><br><br>";


} else if (isset($_REQUEST['reaper'])) {

    $limit = (int)$_REQUEST['reaper'];
    if ($limit < 0)
        $limit = 30;

    $result = mysql_query(
        "select persistentsessions.id, userid, persistentsessions.lastlogin,
           name, email,
           to_days(now()) - to_days(persistentsessions.lastlogin)
         from persistentsessions, users
         where
           persistentsessions.lastlogin <= date_sub(now(), interval $limit day)
            and users.id = persistentsessions.userid", $db);

    if (mysql_num_rows($result) == 0)
        echo "No sessions were found that have been inactive for "
            . "$limit day(s) or more.";
    else {
        echo "<table><tr><th>User Name</th><th>Email</th>"
            . "<th>Last Login</th><th>Days Inactive</th></tr>";

        for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
            list($sid, $uid, $login, $name, $email, $days) =
               mysql_fetch_row($result);

            $sid = mysql_real_escape_string($sid, $db);
            $name = htmlspecialcharx($name);
            $email = htmlspecialcharx($email);

            $bg = (($i % 2) == 0) ? "evenRow" : "oddRow";
            echo "<tr class=\"$bg;\">"
                . "<td><a href=\"showuser?id=$uid\">$name</a></td>"
                . "<td>$email</td>"
                . "<td>$login</td>"
                . "<td>$days</td>"
                . "</tr>";
        }

        echo "</table>";

        if (isset($_REQUEST['delete'])) {
            $result = mysql_query(
                "delete from persistentsessions
                 where lastlogin <= date_sub(now(), interval $limit day)",
                $db);

            if ($result)
                echo "<span class=success>The sessions above have been "
                    . "deleted.</span>";
            else
                echo "<span class=errmsg>An error occurred deleting inactive "
                    . "sessions.</span>";

            echo "<p><a href=\"adminops?reaper=$limit\">Refresh the list</a>";

        } else {

            echo "<p><a href=\"adminops?reaper=$limit&delete\">"
                . "Delete these sessions</a>";

        }

        echo "<br><br><hr class=dots><br><br>";
    }

} else if (isset($_REQUEST['fixsortkeys'])) {

    $errMsg = false;
    $rebuild = isset($_REQUEST['rebuild']);
    $where = ($rebuild
              ? ""
              : "where sort_title is null or sort_title = ''
                 or sort_author is null or sort_author = ''");

    $result = mysql_query(
        "select id, title, author, sort_title, sort_author
         from games $where", $db);
    if (!$result)
        $errMsg = "Query failed: " . mysql_error($db);

    for ($rows = array(), $i = 0 ; $i < mysql_num_rows($result) ; $i++)
        $rows[] = mysql_fetch_array($result, MYSQL_ASSOC);

    foreach ($rows as $r) {
        $gameid = mysql_real_escape_string($r['id'], $db);
        $title = $r['title'];
        $author = $r['author'];
        $sortTitle = $r['sort_title'];
        $sortAuthor = $r['sort_author'];

        $setVars = array();

        if ($rebuild || $sortTitle == "")
            $setVars[] = "sort_title = '"
                         . mysql_real_escape_string(strtoupper(
                             getSortingTitle($title)), $db) . "'";

        if ($rebuild || $sortAuthor == "")
            $setVars[] = "sort_author = '"
                         . mysql_real_escape_string(strtoupper(
                             getSortingPersonalNameList($author)), $db) . "'";

        $setVars = implode(",", $setVars);
        $result = mysql_query(
            "update games set $setVars where id='$gameid'", $db);
        if (!$result) {
            $errMsg = "Update failed: id=$gameid; " . mysql_error($db);
            break;
        }
    }

    echo "<h1>Fix/Rebuild GAMES table SORT keys</h1>";
    if ($errMsg)
        echo "<span class=errmsg>$errMsg</span>";
    else
        echo "<span class=success>Success: " . count($rows) . " row(s) updated
              in GAMES table</span>";

        echo "<br><br><hr class=dots><br><br>";

} else if (isset($_REQUEST['rebuildgametags'])) {

    // run through the GAMES table and rebuild each TAGS field
    $okCnt = 0;
    $result = mysql_query("select id from games", $db);
    for ($cnt = mysql_num_rows($result), $i = 0 ; $i < $cnt ; $i++) {

        // get this game ID
        $gameid = mysql_result($result, $i, "id");

        // load its tag list
        $tresult = mysql_query(
            "select tag from gametags where gameid = '$gameid'", $db);
        for ($tags = array(), $j = 0, $tcnt = mysql_num_rows($tresult) ;
             $j < $tcnt ; $j++)
            $tags[] = mysql_result($tresult, $j, "tag");

        // turn it into a flat string list
        if ($tcnt == 0)
            $tags = "null";
        else
            $tags = "'"
                    . mysql_real_escape_string(implode(",", $tags), $db)
                    . "'";

        // update the game's TAGS field
        $uresult = mysql_query(
            "update games set tags = $tags where id = '$gameid'", $db);
        if ($uresult)
            $okCnt++;
        else
            echo "<span class=errmsg>Error updating game $gameid, tags=$tags"
                . "</span><br>";
    }

    echo "<p>Rows successfully updated: $okCnt<br>";

} else if (isset($_REQUEST['rebuildgametags2'])) {

    // run through GAMES and look for missing GAMETAGS entries
    // run through the GAMES table and rebuild each TAGS field
    $okCnt = 0;
    $result = mysql_query(
        "select id, title, tags from games where tags <> ''", $db);
    for ($cnt = mysql_num_rows($result), $i = 0 ; $i < $cnt ; $i++) {

        // get this game ID and tag list
        $gameid = mysql_result($result, $i, "id");
        $tags = explode(",", mysql_result($result, $i, "tags"));
        $title = htmlspecialcharx(mysql_result($result, $i, "title"));

        // load the corresponding GAMETAGS list
        $tresult = mysql_query(
            "select tag from gametags where gameid = '$gameid'", $db);
        for ($gametags = array(), $j = 0, $tcnt = mysql_num_rows($tresult) ;
             $j < $tcnt ; $j++)
            $gametags[] = mysql_result($tresult, $j, "tag");

        // look for missing gametags entries
        $missingTags = array();
        foreach ($tags as $t) {
            if (!in_array($t, $gametags))
                $missingTags[] = $t;
        }

        if (count($missingTags) != 0) {
            echo "$title ($gameid): missing tags { "
                . htmlspecialcharx(implode(",", $missingTags))
                . " }<br>";
        }
    }

    echo "<p>Rows successfully updated: $okCnt<br>";


} else if (isset($_REQUEST['fixbafs'])) {

    // query up the Baf's reviews that contain tags
    $result = mysql_query(
        "select
           reviews.id as id, reviews.review as review, games.title as title
         from
           reviews, specialreviewers, games
         where
           reviews.special = specialreviewers.id
           and specialreviewers.code = 'bafs'
           and games.id = reviews.gameid
           and reviews.review like '%<%'", $db);

    // note if we're in APPLY mode
    $applyMode = isset($_REQUEST['apply']);

    // allowed tags - we keep these tags without modification
    $allowedTags = valuesToKeys(
        array('p', 'br',
              'i', 'b', 'u', 'strong', 'em',
              'big', 'small', 'tt', 'sup', 'sub',
              'cite', 'blockquote',
              'ul', 'ol', 'li', 'dl', 'dt', 'dd'), 1);

    $updateCount = 0;
    $errRows = false;
    $allBadTags = array();

    while (($row = mysql_fetch_array($result, MYSQL_ASSOC)) != false) {

        // decode the row
        $title = $row['title'];
        $rid = $row['id'];
        $txt = $origTxt = $row['review'];

        // no unknown tags for this row yet
        $badTags = array();

        // scan it for tags we actually need to fix
        $inAnchor = false;
        for ($ofs = 0 ; ($ofs = strpos($txt, '<', $ofs)) !== false ; ) {
            // remember where the tag starts
            $tagOfs = $ofs;

            // find the end of the tag
            if (($gt = strpos($txt, '>', $ofs+1)) === false) {
                ++$ofs;
                continue;
            }

            // note the length of the full tag from < to >
            $tagLen = $gt + 1 - $ofs;

            // pull out the tag name
            $tagName = trim(substr($txt, $ofs + 1, $gt - $ofs - 1));

            // if it's a close tag, drop the slash
            $isClose = false;
            if (substr($tagName, 0, 1) == '/') {
                $isClose = true;
                $tagName = trim(substr($tagName, 1));
            }

            // check for auto-closing tags
            $isAutoClose = false;
            if (substr($tagName, -1, 1) == '/') {
                $isAutoClose = true;
                $tagName = trim(substr($tagName, 0, -1));
            }

            // if we have parameters, pull them out
            $tagAttr = false;
            $sp = strpos($tagName, ' ');
            if ($sp !== false) {
                $tagAttr = trim(substr($tagName, $sp + 1));
                $tagName = substr($tagName, 0, $sp);
            }

            // canonicalize the case
            $tagName = strtolower($tagName);

            // check what we have
            if (isset($allowedTags[$tagName])) {
                // this one goes through unchanged - just skip it
                $ofs = $gt + 1;
            } else if ($tagName == 'a') {
                // Anchor tag - check for open/close
                if ($isClose) {
                    // close - if we're in an anchor, keep it; otherwise
                    // delete it
                    if ($inAnchor) {
                        $inAnchor = false;
                        $ofs = $gt;
                    } else {
                        $txt = substr_replace($txt, "", $tagOfs, $tagLen);
                    }
                } else {
                    // Open anchor - check the href.  If it looks like
                    // a baf's game reference, change it to an IFDB
                    // game reference.  Otherwise just delete it, since
                    // we don't allow off-site references in reviews.
                    $keepA = false;

                    // $$$ special cases just for our initial import - these
                    // fix a couple of items that are broken in the Baf's data
                    if ($tagAttr == "href=game/1822")
                        $tagAttr = "href=game/2277";
                    else if ($tagAttr == "hre=game/1")
                        $tagAttr = "href=game/1";

                    if (preg_match("/^href=([\"']?)game\/([0-9]+)\\1$/i",
                                   $tagAttr, $match, 0, 0)
                        || preg_match("/^href=([\"'])http:\/\/"
                                      . "(?:www\.)?wurb\.com"
                                      . "\/if\/game\/([0-9]+)\\1$/i",
                                      $tagAttr, $match, 0, 0)) {

                        // it's a Baf's game reference - look up the game
                        $qbafsID = mysql_real_escape_string($match[2], $db);
                        $result2 = mysql_query(
                            "select id from games where bafsid='$qbafsID'",
                            $db);

                        // if we found a match, rewrite it
                        if (mysql_num_rows($result2) > 0) {
                            $keepA = true;
                            $gameID = mysql_result($result2, 0, "id");
                            $newTag = "<a game=\"$gameID\">";

                            $txt = substr_replace(
                                $txt, $newTag, $tagOfs, $tagLen);
                            $ofs += strlen($newTag);

                            $inAnchor = true;
                        }
                    } else if (preg_match("/^game=([\"'])([a-z0-9]+)\\1$/i",
                                          $tagAttr, $match, 0, 0)) {

                        // it's already in our own format - keep it as-is
                        $keepA = true;
                        $ofs = $gt;
                        $inAnchor = true;
                    }

                    // if we're not keeping the tag, delete it
                    if (!$keepA) {
                        $badTags["a($tagAttr)"] = true;
                        $txt = substr_replace($txt, "", $tagOfs, $tagLen);
                    }
                }
            } else {
                // it's not an allowed tag - note it and keep going
                $badTags[$tagName] = true;
                $txt = substr_replace($txt, "", $tagOfs, $tagLen);
            }
        }

        // check for changes
        if (count($badTags) != 0 || $txt != $origTxt) {
            // count it
            $updateCount++;

            // note any bad tags
            echo "<p><b>Review ID=$rid (title=$title):</b><br>"
                . "<div class=indented>";
            if (count($badTags) != 0) {
                echo  "Bad tags found:<br><div class=indented>"
                    . implode("<br>", array_keys($badTags))
                    . "</div>";
            }
            echo "Updated review text:<div class=indented>"
                . fixDesc($txt)
                . "</div></div>";

            foreach ($badTags as $k=>$v)
                $allBadTags[$k] = true;
        }

        // if we're in APPLY mode, apply the changes
        if ($applyMode) {
            $qtxt = mysql_real_escape_string($txt, $db);
            $result2 = mysql_query(
                "update reviews set review='$qtxt' where id='$rid'", $db);

            if (!$result2) {
                $errRows[] = $rid;
                echo "<span class=errmsg>Error updating row ("
                    . mysql_error($db) . ")</span><br>";
            }
        }
    }

    if (count($allBadTags) != 0) {
        echo "<hr>Summary of bad tags found:<br><div class=indented>"
            . implode("<br>", array_keys($allBadTags))
            . "</div><p>";
    }

    if ($errRows) {
        echo "<p><span class=errmsg>Database update errors occurred for "
            . "the following review IDs:</span><br><div class=indented>";
        foreach ($errRows as $er)
            echo "<span class=errmsg>$er</span><br>";
        echo "</div>";
    }

    if ($updateCount == 0)
        echo "<p><b>No errors were found - no rows need to be updated</b>";
    else if (!$applyMode)
        echo "<p><b><a href=\"adminops?fixbafs&apply\">Apply these updates</a><p>";


} else if (isset($_REQUEST['filters'])) {

    echo "<h1>Game Filters</h1>";

    $result = mysql_query(
        "select
           filterID, filterName, ckBoxName, showName, endDate,
           filterType, explanation
         from filters", $db);
    echo mysql_error($db);
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        list($filterID, $filterName, $ckBoxName, $showName,
             $endDate, $filterType, $explanation) = mysql_fetch_row($result);

        echo "<p><hr class=dots>"
            . "<b>Name:</b> $filterName<br>"
            . "<b>\"Set\" checkbox label:</b> $ckBoxName<br>"
            . "<b>\"Opt out\" checkbox label:</b> $showName<br>"
            . "<b>\"End date:</b> " . ($endDate ? $endDate : "None") . "<br>"
            . "<b>Filter type:</b> " . $filterTypeMap[$filterType] . "<br>"
            . "<b>Explanation:</b> " . $explanation . "<br>";

        echo "<br><a href=\"adminops?editFilter&filterID=$filterID\">Edit</a>"
            . " - <a href=\"adminops?delFilter=$filterID\">Delete</a><br>";
    }

    echo "<p><hr class=dots><p>"
        . "<a href=\"adminops?addFilter\">Add a new filter</a>"
        . "<p><hr class=dots><p>";


} else if (isset($_REQUEST['utf8entities'])) {
    function find_columns_to_fix($db, $table, $columns) {
        $columns_to_fix = [];
        foreach ($columns as $column) {
            $result = mysqli_query($db, "SELECT count(`$column`) from `$table` where `$column` like '%&#%;%'");
            [$count] = mysqli_fetch_row($result);
            if ($count) $columns_to_fix[] = $column;
        }
        error_log("$table " . json_encode($columns_to_fix));
        return $columns_to_fix;
    }

    $result = mysqli_query($db, "SELECT table_name FROM information_schema.tables
        WHERE TABLE_SCHEMA = 'ifdb'
        and TABLE_TYPE='BASE TABLE'
        and table_name not like '%_mv'
        order by table_name
    ");
    if (!$result) echo(htmlspecialchars(mysqli_error($db)));
    $tables = array_merge(...mysqli_fetch_all($result, MYSQLI_NUM));
    echo "<ul>";
    $found = false;
    foreach ($tables as $table) {
        $result = mysqli_execute_query($db, "SELECT column_name FROM information_schema.columns
            WHERE TABLE_SCHEMA = 'ifdb'
            and TABLE_NAME=?
            and DATA_TYPE in ('varchar', 'mediumtext', 'longtext', 'blob')
        ", [$table]);
        if (!$result) echo(htmlspecialchars(mysqli_error($db)));
        $columns = array_merge(...mysqli_fetch_all($result, MYSQLI_NUM));
        if (!$columns) continue;
        $result = mysqli_execute_query($db, "SELECT column_name FROM information_schema.columns WHERE TABLE_SCHEMA='ifdb' and TABLE_NAME=? and COLUMN_KEY='PRI'", [$table]);
        if (!$result) echo(htmlspecialchars(mysqli_error($db)));
        $primary_keys = mysqli_fetch_row($result);

        if ($table === 'comps_history') {
            $primary_keys = ['compid', 'pagevsn'];
        }
        else if ($table === 'extreviews') {
            $primary_keys = ['gameid', 'reviewid'];
        }
        else if ($table === 'gamelinks') {
            $primary_keys = ['gameid', 'displayorder'];
        }
        else if ($table === 'games_history') {
            $primary_keys = ['id', 'pagevsn'];
        }
        else if ($table === 'gametags') {
            $primary_keys = ['gameid', 'userid', 'moddate'];
        }
        else if ($table === 'pollvotes') {
            $primary_keys = ['pollid', 'userid', 'gameid'];
        }
        else if ($table === 'reclistitems') {
            $primary_keys = ['listid', 'gameid'];
        }

        $columns_to_fix = [];
        foreach ($columns as $column) {
            $result = mysqli_query($db, "SELECT count(`$column`) from `$table` where `$column` like '%&#%;%'");
            [$count] = mysqli_fetch_row($result);
            if ($count) $columns_to_fix[] = $column;
        }
        error_log("$table " . json_encode($columns_to_fix));

        if (!$columns_to_fix) continue;
        $found = true;

        $logging_level = 1;
        if (isset($_POST['fix'])) {
            error_log("starting $table");
            foreach ($columns_to_fix as $column) {
                error_log("starting $table $column");
                $key_columns = join(", ", array_map(function($k) {return "`$k`";}, $primary_keys));
                $sql = "SELECT `$column`, $key_columns from `$table` where `$column` like '%&#%;%'";
                if ($logging_level) {
                    error_log($sql);
                }
                $result = mysqli_query($db, $sql);
                if (!$result) echo(htmlspecialchars(mysqli_error($db)));
                $rows = mysqli_fetch_all($result, MYSQLI_NUM);
                foreach ($rows as $row) {
                    // echo "BEFORE: " . htmlspecialchars($before). "<br>\n";
                    $before = $row[0];
                    $row[0] = $after = html_entity_decode($before, ENT_HTML5, "UTF-8");
                    $row[] = $before;
                    if (!$row[0]) {
                        echo "Failed decoding<br>\n";
                        continue;
                    }
                    // echo "AFTER: " . htmlspecialchars($after). "<br>\n";
                    $where = join(" and ", array_map(function($k) {return "`$k` = ?";}, $primary_keys));
                    $sql = "UPDATE $table SET `$column` = ? WHERE $where and `$column` = ?";
                    if ($logging_level) {
                        error_log($sql);
                        error_log(json_encode($row));
                    }
                    $stmt = $db->prepare($sql);
                    if (!$stmt->bind_param(str_repeat('s', count($row)), ...$row)) {
                        echo "error " . $stmt->error . "<br>\n";
                        exit();
                    }
                    if (!$stmt->execute()) {
                        echo("error " . htmlspecialchars(mysqli_error($db)));
                        exit();
                    }
                    if (mysqli_affected_rows($db) !== 1) {
                        echo "$table $column wrong number of rows: " . mysqli_affected_rows($db) . " row(s)<br>\n";
                        echo htmlspecialchars(json_encode($row));
                        exit();
                    }
                }
                echo "$table $column: " . count($rows) . "<br>\n";
            }
            $found = false;
        } else {
            echo "<li>$table (".join(", ", $primary_keys).")<ul>\n";
            foreach ($columns as $column) {
                $result = mysqli_query($db, "SELECT count(`$column`) from `$table` where `$column` like '%&#%;%'");
                if (!$result) echo(htmlspecialchars(mysqli_error($db)));
                [$count] = mysqli_fetch_row($result);
                echo "<li>$column ($count)";
            }
            echo "</ul>\n";
        }
    }
    if ($found) {
        echo "<p><form method=post action='/adminops?utf8entities'><input type=submit name=fix value='Fix'></form></p>";
    } else {
        echo "No rows left that needed fixing";
    }
    exit();



} else if (isset($_REQUEST['sysinfo'])) {

    $phpVsn = phpversion();

    $result = mysql_query("select version();", $db);
    list($mysqlVsn) = mysql_fetch_row($result);

    echo "System information:"
        . "<p>php version = $phpVsn"
        . "<br>MySQL version = $mysqlVsn";

    echo "<p><hr class=dots><p>";