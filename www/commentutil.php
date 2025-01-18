<?php

include_once "util.php";
include_once "login-persist.php";

// Use globals for the inbox and the number of inbox messages 
// so that once we call queryComments(), we can use the results 
// in both pagetpl.php and check-inbox.php
$inbox = [];
$inboxCnt = 0;

function queryComments($db, $mode, $quid, $limit, $caughtUpDate, $keepMuted)
{
    // get the logged-in user
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'] ?? null;
    $orOwner = ($curuser ? "or '$curuser' in (c.userid, c.private)" : "");

    // if there's a caught-up date, only query messages newer than that date
    $andNew = "";
    if ($caughtUpDate) {
        $caughtUpDate = mysql_real_escape_string($caughtUpDate, $db);
        $andNew = "and c.created > '$caughtUpDate'";
    }

    // set up the query based on the mode
    $modeJoin = "";
    $modeWhere = "1";
    $modeGroup = "";
    $modeHaving = "";
    $myItem = "(r.userid = '$quid' "
              . "or u.id = '$quid' "
              . "or l.userid = '$quid' "
              . "or p.userid = '$quid')";

    // if we're logged in, we can check for muting
    $mutedCol = "0";
    if ($curuser) {
        $mutedCol = "(select count(*) from userfilters as uf "
                      . "where uf.userid = '$curuser' "
                      .   "and uf.targetuserid = c.userid "
                      .   "and uf.filtertype = 'K')";
    }
    $andNotMuted = ($keepMuted ? "" : "and $mutedCol = 0");

    // Include only reviews from our sandbox or sandbox 0 (all users)
    $inSandbox = "uc.sandbox = 0";
    if ($curuser)
    {
        // get my sandbox
        $sandbox = 0;
        $result = mysql_query("select sandbox from users where id='$curuser'", $db);
        list($sandbox) = mysql_fetch_row($result);
        if ($sandbox != 0)
            $inSandbox = "uc.sandbox in (0, $sandbox)";
    }

    switch ($mode) {
    case 'subscribed':
        // Add instructions to count the number of posts where this user
        // commented.  Include comments from any item written by this
        // user, OR where the count of comments by this user is non-zero.
        $modeJoin = "join ucomments as c2"
                    . " on c2.source = c.source and c2.sourceid = c.sourceid";
        $modeGroup =  "group by c.commentid";
        $modeHaving = "having sum(c2.userid = '$quid') <> 0 or $myItem";
        break;

    case 'inbox':
        // we only want comments for items created by this user, or
        // replies to this user's comments
        $modeJoin = "left outer join ucomments as cparent "
                    . " on c.parent = cparent.commentid";
        $modeWhere = "$myItem or cparent.userid = '$quid'";
        break;

    case 'comments':
        // we only want comments written by this user
        $modeWhere = "c.userid = '$quid'";
        break;
    }

    // query the comments
    $query =
        "select sql_calc_found_rows
           c.commentid, c.sourceid, c.source, c.comment,
           c.created, date_format(c.created, '%M %e, %Y'),
           c.modified, date_format(c.modified, '%M %e, %Y'),
           c.userid, uc.name, c.private,

           rg.id, rg.title, r.userid, ru.name,
           u.id, u.name,
           l.title, l.userid, lu.name,
           p.title, p.userid, pu.name,

           $mutedCol

         from
           ucomments as c
           join users as uc on uc.id = c.userid

           left outer join reviews as r
               on (c.source = 'R' and c.sourceid = r.id)
           left outer join users as ru on r.userid = ru.id
           left outer join games as rg on r.gameid = rg.id

           left outer join users as u
             on (c.source = 'U' and c.sourceid = u.id)

           left outer join reclists as l
             on (c.source = 'L' and c.sourceid = l.id)
           left outer join users as lu on l.userid = lu.id

           left outer join polls as p
             on (c.source = 'P' and c.sourceid = p.pollid)
           left outer join users as pu on p.userid = pu.id

           $modeJoin
         where
           (c.private is null $orOwner)
           and ($modeWhere)
           $andNew
           $andNotMuted
           and ($inSandbox)
         $modeGroup
         $modeHaving
         order by
           c.modified desc
         $limit";

    // run the query
    $result = mysql_query($query, $db);

    // count the total rows from the query
    $result2 = mysql_query("select found_rows()", $db);
    list($rowcnt) = mysql_fetch_row($result2);

    // build the results
    for ($comments = array(), $i = 0 ; $i < mysql_num_rows($result) ; $i++) {

        // fetch the next row
        $row = mysql_fetch_row($result);
        list($cid, $srcid, $src, $ctxt,
             $ccreDT, $ccre,
             $cmodDT, $cmod,
             $cuid, $cuname, $cprivate,
             $rgid, $rgtitle, $ruid, $runame,
             $uuid, $uuname,
             $ltitle, $luid, $luname,
             $ptitle, $puid, $puname,
             $muted) = $row;

        // html-ify some of the items
        $cuname = htmlspecialcharx($cuname);
        $rgtitle = htmlspecialcharx($rgtitle);
        $runame = htmlspecialcharx($runame);
        $uuname = htmlspecialcharx($uuname);
        $ltitle = htmlspecialcharx($ltitle);
        $ptitle = htmlspecialcharx($ptitle);
        $puname = htmlspecialcharx($puname);

        // build the item title and link, which vary by source type
        $title = "$cuname on ";
        $linktitle = "<a href=\"showuser?id=$cuid\">$cuname</a> on ";
        switch ($src)
        {
        case 'R':
            // review
            $title .= "$runame's review of $rgtitle";
            $link = "viewgame?id=$rgid&review=$srcid&pgForComment=$cid";
            $linktitle .= "<a href=\"$link\">$runame's review</a> of "
                       . "<a href=\"viewgame?id=$rgid\">$rgtitle</a>";
            break;

        case 'U':
            // user profile
            $link = "showuser?id=$srcid&comments&pgForComment=$cid";
            $title .= "$uuname's member profile";
            $linktitle .= "<a href=\"$link\">$uuname</a>'s member profile";
            break;

        case 'L':
            // recommended list
            $link = "viewlist?id=$srcid&comments&pgForComment=$cid";
            $title .= "$ltitle (a Recommended List by $luname)";
            $linktitle .= "<a href=\"$link\">$ltitle</a> (a Recommended List "
                       . "by <a href=\"showuser?id=$luid\">$luname</a>)";
            break;

        case 'P':
            // poll
            $link = "poll?id=$srcid&comments&pgForComment=$cid";
            $title .= "$ptitle (a poll by $puname)";
            $linktitle .= "<a href=\"$link\">$ptitle</a> (a poll by "
                       . "<a href=\"showuser?id=$puid\">$puname</a>)";
            break;
        }

        // build the return list
        $comments[] = array($row, $link, $title, $linktitle);

    }

    // return the results
    return array($comments, $rowcnt);
}

function showCommentPage($db, $itemAuthor, $srcID, $srcCode,
                         $mainPage, $commentPage, $perPage,
                         $title, $anchor)
{
    // get the logged-in user
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'];
    $orOwner = ($curuser ? "or '$curuser' in (c.userid, c.private)" : "");

    // if we're logged in, we can check for muting
    $mutedCol = "0";
    if ($curuser) {
        $mutedCol = "(select count(*) from userfilters as uf "
                      . "where uf.userid = '$curuser' "
                      .   "and uf.targetuserid = c.userid "
                      .   "and uf.filtertype = 'K')";
    }

    // Include only reviews from our sandbox or sandbox 0 (all users)
    $inSandbox = "uc.sandbox = 0";
    if ($curuser)
    {
        // get my sandbox
        $sandbox = 0;
        $result = mysql_query("select sandbox from users where id='$curuser'", $db);
        list($sandbox) = mysql_fetch_row($result);
        if ($sandbox != 0)
            $inSandbox = "uc.sandbox in (0, $sandbox)";
    }

    // query the comments
    $result = mysql_query(
        "select
           c.commentid, c.parent, c.comment, c.userid, uc.name,
           date_format(c.created, '%M %e, %Y'),
           date_format(c.modified, '%M %e, %Y'),
           c.private,
           $mutedCol
         from
           ucomments as c
           join users as uc on uc.id = c.userid
         where
           (c.private is null $orOwner)
           and c.source = '$srcCode'
           and c.sourceid = '$srcID'
           and ($inSandbox)
         order by
           c.created desc", $db);

    // fetch the comments
    $ccnt = mysql_num_rows($result);
    for ($comments = array(), $i = 0 ; $i < $ccnt ; $i++)
        $comments[] = mysql_fetch_row($result);

    // if there are comments, show them
    if ($ccnt != 0) {
        // Build an index of the comments by ID
        $cidx = array();
        foreach ($comments as $c) {
            $curCID = $c[0];
            $cidx[$curCID] = $c;
        }

        // figure the page range
        $lastPage = (int)floor(($ccnt + $perPage - 1)/$perPage);

        // get the desired page
        $pg = get_req_data('pg');
        $pgAll = false;

        if ($pg == 'all') {
            $pgAll = true;
            $pg = 1;
            $perPage = $ccnt;
        } else {
            if ($pg < 1)
                $pg = 1;
            else if ($pg > $lastPage)
                $pg = $lastPage;
        }

        // if there's a specific comment being requested, go to the page
        // for the desired comment
        if (!$pgAll && isset($_REQUEST['pgForComment'])) {

            // get the comment ID
            $targetID = get_req_data('pgForComment');

            // scan the comments for this ID
            for ($i = 0 ;
                 $i < count($comments) && $comments[$i][0] != $targetID ;
                 $i++) ;

            // if we found it, figure the page it's on
            if ($i < count($comments))
                $pg = 1 + (int)floor($i / $perPage);
        }

        // figure the display range for the page
        $firstOnPage = ($pg - 1)*$perPage;
        $lastOnPage = $firstOnPage + $perPage - 1;
        if ($lastOnPage >= $ccnt)
            $lastOnPage = $ccnt - 1;

        // set up the paging controls
        $pageCtl = "<span class=details>"
                   . makePageControl(
                       $mainPage, $pg, $lastPage, $firstOnPage, $lastOnPage,
                       $ccnt, true, true, $pgAll)
                   . "</span>";

        // if there's an anchor provided, make it into a <a name> tag
        if ($anchor)
            $anchor = "<a name=\"$anchor\"></a>";

        // start the section
        echo "<a name=\"reviewComments\"></a>"
            . "<h3>$anchor$title</h3><div class=indented>"
            . "<p>$pageCtl<br>";

        // build the nesting list
        $coutlst = array();
        for ($i = $firstOnPage ; $i <= $lastOnPage ; $i++)
            enlistComment($coutlst, $cidx, $comments[$i]);

        // show the output list
        showCommentList($db,$commentPage, $itemAuthor, $cidx, $coutlst);

        // end the section
        echo "$pageCtl<br></div>";
    }
}

function enlistComment(&$coutlst, &$cidx, $crec)
{
    // unpack the item
    list($cid, $cpar, $ctxt, $cuserid, $cusername,
         $ccreated, $cmodified, $cprivate) = $crec;

    // if I'm already listed, there's no need to list me again
    if ($cidx[$cid]['.listed'])
        return;

    // flag it as listed
    $cidx[$cid]['.listed'] = true;

    // If this item has a parent, add the item to its parent's child
    // list.  Otherwise, add it to the root list.
    if ($cpar && $cidx[$cpar]) {

        // it has a parent - add it to its parent's child list
        $cidx[$cpar]['.children'][] = $cid;

        // make sure the parent is listed
        enlistComment($coutlst, $cidx, $cidx[$cpar]);

    } else {

        // there's no parent - add it to the root list
        $coutlst[] = $cid;

    }
}

function showCommentList($db,$commentPage, $itemAuthor, $cidx, $coutlst)
{
    // show each item in the list
    for ($i = 0 ; $i < count($coutlst) ; $i++)
        showComment($db, $commentPage, $itemAuthor, $cidx, $coutlst, $i);
}

$mutedCommentNum = 0;
function showComment($db,$commentPage, $itemAuthor, $cidx, $coutlst, $i)
{
    // get the logged-in user
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'];

    // unpack the item
    $crec = $cidx[$coutlst[$i]];
    list($cid, $cpar, $ctxt, $cuserid, $cusername,
         $ccreated, $cmodified, $cprivate, $muted) = $crec;

    // quote the items
    $ctxt = fixDesc($ctxt, FixDescSpoiler);
    $cusername = htmlspecialcharx($cusername);

    // start with the reply control
    $ctls = "<a href=\"$commentPage&replyto=$cid\">"
            . "Reply</a>";

    $isAdmin = check_admin_privileges($db, $curuser);

    // add the Edit and Delete controls, if it's ours
    if ($curuser && $curuser == $cuserid) {
        $ctls .= " | <a href=\"$commentPage&edit=$cid\">Edit</a>";
    } else if ($isAdmin) {
        $ctls .= " | <a href=\"$commentPage&edit=$cid\">Edit</a>";
    }
    if ($curuser == $cuserid || $curuser == $itemAuthor) {
        $ctls .= " | <a href=\"$commentPage&delete=$cid\">Delete</a>";
    } else if ($isAdmin) {
        $ctls .= " | <a href=\"$commentPage&delete=$cid\">Delete</a>";
    }

    // add the Mute control if applicable
    if ($curuser && $curuser != $cuserid && !$muted) {
        $ctls .= " | <a href=\"userfilter?user=$cuserid&action=mute\">"
                 . "Mute User</a>";
    }

    // figure the class - first, last, or middle
    $divClass = "reviewComment"
                . (count($coutlst) == 1 ? "Only" :
                   ($i == 0 ? "First" :
                    ($i+1 == count($coutlst) ? "Last" :
                     "")));

    // start the comment section
    echo "<div class=$divClass>";

    // if it's muted, wrap it in a click-through hider
    if ($muted) {
        echo "<span class=details><i>You've muted this comment's author</i>"
            . " - <span><a href=\"needjs\">"
            . addEventListener("click", "revealMutedAuthor(this, '$cuserid', '"
                . str_replace(array('"', '\''),
                            array("'+String.fromCharCode(34)+'", "\\'"),
                            $cusername)
                . "');return false;")
            . "Reveal author</a></span>"
            . "<span class='displayNone'>"
            . " | <a href=\"needjs\">"
            . addEventListener(
                "click", "revealMutedComment(this);return false;"
            )
            . "Reveal comment</a>"
            . "</span>"
            . "</span>"
            . "<div class='displayNone'>";

        global $mutedCommentNum;
        if ($mutedCommentNum++ == 0) {
            ?>
<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
function revealMutedAuthor(ele, uid, uname)
{
    ele = ele.parentNode;
    ele.innerHTML = "<a href=\"showuser?id=" + uid + "\">"
                    + uname + "</a>";
    ele.nextSibling.style.display = "inline";
}
function revealMutedComment(ele)
{
    ele = ele.parentNode.parentNode;
    ele.style.display = "none";
    ele.nextSibling.style.display = "block";
}
</script>
            <?php
        }
    }

    // show the comment body
    echo "<span class=details>"
        . "<a href=\"showuser?id=$cuserid\">$cusername</a>, $ccreated"
        . ($cmodified != $ccreated
           ? " (updated $cmodified)" : "")
        . ($cprivate
           ? " <span class=\"reviewCommentPrivate\">Private</span>" : "")
        . " - $ctls</span><br>"
        . "$ctxt";

    // end the mute hider div, if present
    if ($muted)
        echo "</div>";

    // end the command section
    echo "</div>";

    // if there are children, show the child list
    $children = $crec['.children'];
    if ($children) {
        // open the reply division
        echo "<div class=reviewCommentReply>";

        // show the child list
        showCommentList($db, $commentPage, $itemAuthor, $cidx, $children);

        // close the reply division
        echo "</div>";
    }
}

function countComments($db, $srcCode, $qSrcID)
{
    // set up the owner join if the owner information is provided
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'] ?? null;
    $orOwner = ($curuser ? "or '$curuser' in (c.userid, c.private)" : "");

    // Include only reviews from our sandbox or sandbox 0 (all users)
    $inSandbox = "uc.sandbox = 0";
    if ($curuser)
    {
        // get my sandbox
        $sandbox = 0;
        $result = mysql_query("select sandbox from users where id='$curuser'", $db);
        list($sandbox) = mysql_fetch_row($result);
        if ($sandbox != 0)
            $inSandbox = "uc.sandbox in (0, $sandbox)";
    }

    $result = mysql_query(
        "select
           count(*)
         from
           ucomments as c
           join users as uc on uc.id = c.userid
         where
           (c.private is null $orOwner)
           and (c.source = '$srcCode' and c.sourceid = '$qSrcID')
           and ($inSandbox)",
        $db);

    list($cnt) = mysql_fetch_row($result);
    return $cnt;
}

?>
