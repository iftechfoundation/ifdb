
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
