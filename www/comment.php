<?php

// we have to be logged in to do this
include_once "session-start.php";
include_once "login-check.php";
include_once "captcha.php";
include_once "akismet.php";

if (!logged_in())
    exit();

$curuser = $_SESSION['logged_in_as'];

// include some utility modules
include_once "pagetpl.php";
include_once "util.php";

// connect to the database
include_once "dbconnect.php";
$db = dbConnect();

// get the globals from the including file, specifying the type of comment
// we're operating on, and the comment table description
global $type, $srcParamName, $srcCode, $ownSrc, $typeName, $typeTitle,
   $ownerDesc, $baseUrl;

// Check to see if this is a recently created user account.  If so,
// require a CAPTCHA test before allowing a new posting.  Also get the
// username and email for Akismet's use.
$result = mysql_query(
    "select acctstatus, datediff(now(), created), name, email from users
     where id = '$curuser'", $db);
list($userAccountStatus, $userAccountAge,
     $userAccountName, $userAccountEmail) =
         mysql_fetch_row($result);
$isNewUser = ($userAccountAge < 30);

// we only allow private comments on user profiles
$privateOption = ($srcCode == 'U');

// get the request parameters
$srcID = get_req_data($srcParamName);
$commentID = get_req_data('edit');
$deleteID = get_req_data('delete');
$parentID = get_req_data('replyto');
$commentText = get_req_data('text');
$submit = get_req_data('submit');
$confirm = get_req_data('confirm');
$private = ($privateOption && get_req_data('private') == 'Y');
$privateTo = false;
$privateToName = false;


// generate a captcha session key, in case we need it
$captchaKey = "NewComment.$srcCode.$srcID";

// build the URL with the source ID
$urlWithSrc = "$baseUrl?$srcParamName=$srcID";

if ($deleteID)
    $commentID = $deleteID;

// quote some parameters
$qSrcID = mysql_real_escape_string($srcID, $db);
$qParentID = mysql_real_escape_string($parentID, $db);
$qCommentID = mysql_real_escape_string($commentID, $db);

// if there's no return page in the parameters, use the referer
$srcpage = get_req_data('src');
if (!$srcpage)
    $srcpage = get_req_data('httpreferer');
if (!$srcpage)
    $srcpage = $_SERVER['HTTP_REFERER'];
$srcpageParam = urlencode($srcpage);

$errMsg = false;
$captchaOK = true;
$captchaErrMsg = false;
$errFatal = false;
$succMsg = false;

// query the reference record
list($refRec, $refOwner, $refOwnerName, $errFatal, $errMsg) =
    getCommentReference($db, $srcID);

// if we're replying, fetch the parent
$parentRow = false;
if ($parentID) {
    // query the parent comment
    $result = mysql_query(
        "select
           c.userid, u.name, c.comment,
           date_format(c.created, '%M %e, %Y'),
           date_format(c.modified, '%M %e, %Y'),
           c.private
         from
           ucomments as c
           join users as u on u.id = c.userid
         where
           c.commentid = '$qParentID'", $db);

    $parentRow = mysql_fetch_row($result);

    // if the parent is private, and this isn't a form submission,
    // set the new message to private by default
    if ($parent[5] && !$submit)
        $private = true;
}

// if we're editing a comment, query the old comment
$oldText = false;
$created = false;
$modified = false;
$editing = false;
if ($commentID || $deleteID) {

    $editing = true;
    $result = mysql_query(
        "select
           c.comment, c.parent, c.userid,
           date_format(c.created, '%M %e, %Y'),
           date_format(c.modified, '%M %e, %y'),
           u.name,
           c.private, pu.name
         from
           ucomments as c
           join users as u on u.id = c.userid
           left outer join users as pu on pu.id = c.private
         where
           c.commentid = '$qCommentID'
           and c.source = '$srcCode'
           and c.sourceid = '$qSrcID'", $db);

    if (mysql_num_rows($result) == 0) {
        $errMsg = "The specified comment is not in the database. The "
                  . "comment might have been deleted recently, or the link "
                  . "you used to reach this page might be broken.";
        $errFatal = true;
    } else {
        // fetch the row
        list($oldText, $oldParent, $author, $created, $modified, $authorName,
             $oldPrivateTo, $oldPrivateToName) =
            mysql_fetch_row($result);

        // don't allow updating the parent
        $parentID = $oldParent;
        $qParentID = mysql_real_escape_string($parentID, $db);

        // Deleting is allowed if the current user is the author of the
        // comment OR the author of the base object.  Editing is allowed only
        // if the current user is the author of the comment.
        if ($deleteID) {
            // deleting - must be the author of the comment or source object
            if ($curuser != $author
                && (!$curuser || $curuser != $refOwner) && (!check_admin_privileges($db, $curuser))) {
                $errMsg = "This comment was entered by another user. You "
                          . "can only delete your own comments (or comments "
                          . "posted to $ownSrc).";
                $errFatal = true;
            }
        } else {
            // editing - must be the author of the comment
            if ((!$deleteID && $author != $curuser) && (!check_admin_privileges($db, $curuser))) {
                $errMsg = "This comment was entered by another user. You "
                          . "can only edit your own comments.";
                $errFatal = true;
            }
        }
    }
}

// if this isn't a form submission, start with the database comment
if (!$submit && $editing) {
    $commentText = $oldText;
    if ($oldPrivateTo && $privateOption) {
        $private = true;
        $privateTo = $oldPrivateTo;
        $privateToName = $oldPrivateToName;
    } else {
        $private = false;
        $privateTo = false;
        $privateToName = false;
    }
}

// If there's a private option, figure the default private recipient.
$privateVal = "null";
if ($privateOption) {

    // Start with the owner of the object we're commenting on.  For example,
    // a private message on a profile is private to the owner of the profile.
    $privateTo = $refOwner;
    $privateToName = $refOwnerName;

    // If the reference owner is the same as the current user, then we'd
    // be the only ones who could read this message.  In this case, if
    // we're replying to a message, look for another user somewhere in
    // the parent chain, and use that other user.  This allows for
    // private exchanges between the object owner and another user who
    // posts a private message to the object.
    if ($privateTo == $curuser && $qParentID)
    {
        // look up the parent chain for anyone else in the conversation
        for ($par = $qParentID ; $par ; $par = $ppar) {

            // query this parent record
            $result = mysql_query(
                "select
                   c.userid, u.name, c.parent
                 from
                   ucomments as c
                   join users as u on u.id = c.userid
                 where
                   c.commentid = '$par'", $db);

            // stop if that failed
            if (mysql_num_rows($result) == 0)
                break;

            // fetch it
            list($puid, $puname, $ppar) = mysql_fetch_row($result);

            // if this is a different user, this is the recipient
            if ($puid != $curuser) {
                "...using this one!<br>";
                $privateTo = $puid;
                $privateToName = $puname;
                break;
            }
        }
    }

    // if this leaves us with a private message to ourselves, remove
    // privacy option
    if ($privateTo == $curuser)
        $privateOption = false;

    // if we still can and want to set the private option, set the db params
    if ($private && $privateOption && $privateTo)
        $privateVal = "'$privateTo'";
}

// quote the comment text
$qCommentText = mysql_real_escape_string($commentText, $db);

// figure the title
if ($parentID)
    $title = "Reply to Comment";
else
    $title = "Comment on $typeTitle";

// check what we're doing
if ($errMsg) {
    // ignore any $submit setting if an error has already been encounted
} else if ($deleteID) {

    $title = "Delete Comment";

    if ($confirm == 'Y') {

        // confirmed - delete the comment
        $progress = "RVD203";
        $result = mysql_query(
            "delete from ucomments where commentid = '$qCommentID'", $db);

        if ($result) {

            // success
            $succMsg = ($curuser == $author ? "Your" : "The")
                       . " comment has been deleted.";

        } else {

            // the delete failed
            $errMsg = "An error occurred updating the database (error "
                      . "code $progress). You might try again in a few "
                      . "minutes; if the problem persists, you can "
                      . "<a href=\"contact\">contact us</a>.";

        }

    } else {

        // prepare the results for display
        $authorName = htmlspecialcharx($authorName);
        $oldText = fixDesc($oldText, FixDescSpoiler);
        $time = ($created == $modified
                 ? $created : "$created (last updated $modified)");

        pageHeader($title);

        echo "<h1>$title</h1>";

        $authorLink = ($curuser == $author
                       ? "Your"
                       : "<a href=\"showuser?id=$author\">$authorName</a>'s");

        // show the comment
        echo "$authorLink comment from $time:<br>"
            . "<div class=reviewCommentReply>$oldText</div>";

        if ($curuser != $author) {
            if ($curuser == $refOwner) {
                echo "<p><b>Note:</b> This comment was posted by another "
                    . "user. As the $ownerDesc, you are the "
                    . "official moderator of this discussion, so "
                    . "you're free to delete other users' comments without "
                    . "their permission.  However, in the interest of open "
                    . "discussion, we suggest that you exercise restraint, "
                    . "and allow dissenting comments as long as they remain "
                    . "civil and constructive.  (Of course, we encourage "
                    . "you to delete spam, flames, and trolls with "
                    . "abandon.)";
            } else if ($curuser != $author) {
                $errMsg = "You are not authorized to delete this comment.";
                $errFatal = true;
            }
        }

        echo "<p>Are you sure you want to <b>permanently</b> "
            . "delete this comment?"
            . "<p><a href=\"$urlWithSrc&delete=$commentID"
            . "&confirm=Y&src=$srcpageParam\">Yes, delete this comment</a>"
            . "<p><a href=\"$srcpage\">No, return without deleting</a>";

        pageFooter();
        exit();
    }

} else if ($submit == 'Save Changes') {

    // make sure there's a comment
    if (strlen($commentText) == 0) {
        $errMsg = "You didn't enter anything for the comment text.";
    }

    // Double-check that the user is still active in the database; we've
    // had a situation where a spammer had an active session that they
    // used to keep blasting us with spam in real time even after I had
    // banned them in the database.
    if ($userAccountStatus != 'A')
    {
        $_SESSION['logged_in'] = false;
        $_SESSION['logged_in_as'] = null;
        $errMsg = "Sorry, an error occurred updating the "
                  . "database. (RVN6611)";
    }

    // limit new message postings for new users
    if (!$errMsg && !$commentID)
    {
        // set a daily comment limit based on the account age
        $limit = ($userAccountAge < 7 ? 7 :
                  ($userAccountAge < 30 ? 25 : 100));

        // check that the account isn't over this limit
        $ures = mysql_query(
            "select count(*)
             from ucomments
             where
              userid = '$curuser'
              and created + interval 24 hour > now()", $db);
        list($numRecent) = mysql_fetch_row($ures);

        if ($numRecent > $limit)
            $errMsg = "Sorry, but due to spam, we've had to impose a limit "
                      . "on the number of comments posted per user per day. "
                      . "You'll be able to post more comments in about "
                      . "24 hours. Sorry for the inconvenience.";
    }

    // check for spam
    if (!$errMsg) {
        $ak = akNew();
        $ak->setCommentAuthor($userAccountName);
        $ak->setCommentAuthorEmail($userAccountEmail);
        $ak->setCommentContent($commentText);

        // notify ifdbadmin if it looks like spam
        if ($ak->isCommentSpam()
            || preg_match("/(https?:|www\.)/i", $commentText))
        {
            $spamErr = "An error occurred updating the database - changes "
                       . "were not saved. You might try again in a few "
                       . "moments, or <a href=\"contact\">contact us</a> "
                       . "if the problem persists. (Error code ";

            $adminUrl = get_root_url() . "adminops?user=$curuser";

            // send email
            if (!$errMsg && !send_mail(
                "ifdbadmin@ifdb.org",
                "IFDB comment spam flag",

                "<b>Possible spam comment posted</b><br><br>User: "
                . "<a href=\"" . get_root_url() . "showuser?id=$curuser\">"
                . htmlspecialchars($userAccountName) . "</a> - "
                . "<a href=\"" . get_root_url() . "commentlog?user=$curuser\">"
                . "user comment log</a>"
                . "<br>Email: "
                . htmlspecialchars($userAccountEmail)
                . "<br>Source code: $srcCode"
                . "<br>Source ID: $qSrcID<br>"

                . "<br>Message: "
                . htmlspecialchars($commentText) . "<br><br>"

                . "<a href=\"{$adminUrl}\">Manage this user</a>"

                . "<br><br>",

                "From: IFDB <noreply@ifdb.org>\r\n"
                . "Content-type: Text/HTML\r\n"))
            {
                $errMsg = "$spamErr RVE1928)";
            }
        }
    }

    // if this is a new user account, and we're adding a new comment,
    // check the captcha result
    if ($isNewUser && !$commentID && !$errMsg)
        list($captchaOK, $captchaErrMsg) = captchaCheckPost($captchaKey);

    // confirming an action
    if (!$errMsg && $captchaOK)
    {
        // create or update the comment, as applicable
        if ($commentID) {

            // updating
            $progress = "RVU201";
            $result = mysql_query(
                "update ucomments
                 set
                   comment = '$qCommentText',
                   modified = now(),
                   private = $privateVal
                 where
                   commentid = '$qCommentID'", $db);

        } else {

            // creating

            // get the parent as a string or NULL
            $parentVal = ($parentID ? "'$qParentID'" : "null");

            // do the insert
            $progress = "RVI202";
            $result = mysql_query(
                "insert into ucomments
                 (parent, source, sourceid, userid, comment, private,
                  created, modified)
                 values ($parentVal, '$srcCode', '$qSrcID', '$curuser',
                         '$qCommentText', $privateVal, now(), now())", $db);
        }

        // check the results
        if ($result) {

            // success
            $succMsg = "Your comment has been recorded.";

        } else {
            // set up the error and fall through to the confirmation form
            $errMsg = "An error occurred updating the database (error "
                      . "code $progress). You might try again in a few "
                      . "minutes; if the problem persists, you can "
                      . "<a href=\"contact\">contact us</a>.";
        }
    }
}

if ($succMsg) {
    pageHeader($title);
    echo "<h1>$title</h1>"
        . "<p><span class=success>$succMsg</span>"
        . "<p><a href=\"$srcpage\">Return</a>";

    pageFooter();
    exit();
}

pageHeader($title, "commentForm.commentField", false,
           "<script src=\"xmlreq.js\"></script>");
captchaSupportScripts($captchaKey);

if (!$submit || $errMsg || !$captchaOK) {
    // pre-confirmation - explain the plan and ask for confirmation

    echo "<h1>$title</h1>";

    if ($errMsg)
        echo "<span class=errmsg>$errMsg</span><p>";
    if ($succMsg)
        echo "<span class=success>$succMsg</span><p>";

    if ($errFatal) {
        echo "<p><a href=\"$srcpage\">Return</a>";
        pageFooter();
        exit();
    }

?>
<b>Guidelines:</b>
<ul class=doublespace>

   <li>Follow our <?php
echo helpWinLink("code-of-conduct", "code of conduct")
   ?>.</li>

   <li>Be courteous.

   <li>If you have criticisms, try to make them constructive.

   <li>Avoid spoilers.  Use the <?php
echo helpWinLink("help-formatting?comment#spoilertags", "&lt;SPOILER&gt; tag")
   ?> if you include anything that gives away a secret about a game.

   <li>Stay on topic.  Your comments should be about this specific
   <?php echo $typeName ?>, or the specific comment you're replying to.

</ul>
<?php

    // if we're commenting on another comment, show it
    $pPrivate = false;
    if ($parentID) {
        if ($parentRow) {

            // decode the parent row
            list($pUserID, $pUserName, $pComment, $pCreated, $pMod, $pPrivate)
                = $parentRow;

            // prepare the results for display
            $pUserName = htmlspecialcharx($pUserName);
            $pComment = fixDesc($pComment, FixDescSpoiler);
            $pTime = ($pCreated == $pMod
                      ? $pCreated : "$pCreated (last updated $pMod)");

            // if we're replying to a private message, this is a private
            // reply
            if ($pPrivate)
                $private = true;

            // show the comment
            echo "<p>You are replying to "
                . "<a href=\"showuser?id=$pUserID\">$pUserName</a>'s "
                . "comment from $pTime:<br>"
                . "<div class=reviewCommentReply>$pComment</div>";

        } else {
            // couldn't find the parent comment
            echo "<p><b>Note:</b> The original comment you're replying to "
                . "wasn't found in the database - it might have been "
                . "recently deleted.  You can still add your comment, "
                . "but it will be shown as a new comment rather than "
                . "a reply.";
        }
    }

    $privateCheckbox = "";
    if ($privateOption) {
        $privateCheckbox =
            "<p><label>"
            . "<input type=\"checkbox\" id=\"ckPrivate\" name=\"private\" "
            . ($private ? "checked " : "")
            . "value=\"Y\"> <label for=\"ckPrivate\">"
            . "Private message</label></label><br>"
            . "<span class=details>If you check this box, the message will "
            . "only be visible to you and "
            . htmlspecialcharx($privateToName) . ".</span>";
    }

    global $nonce;
    echo "<form name=\"comment\" id=\"commentForm\" "
        . "method=\"post\" action=\"$baseUrl\">"
        .  "<style nonce='$nonce'>\n"
        . "#commentForm { margin-top: 1em; }\n"
        . "</style>\n"
        . "<input type=hidden name=$srcParamName value=\"$srcID\">"
        . "<input type=hidden name=edit value=\"$commentID\">"
        . "<input type=hidden name=replyto value=\"$parentID\">"
        . "<input type=hidden name=src value=\"$srcpage\">"
        . "<input type=hidden name=submit value=\"Save Changes\">"

        . "<b>Enter your comment:</b> &nbsp;&nbsp;&nbsp;&nbsp; "
        . "<span class=details>"
        . helpWinLink("help-formatting?comment", "Formatting Hints")
        . "</span><br><textarea rows=10 cols=80 name=text id=commentField>"
        . htmlspecialcharx($commentText)
        . "</textarea>"

        . $privateCheckbox;

    // if this is a recently created user account, and we're adding a new
    // comment, require a captcha test
    if ($isNewUser && !$commentID) {
        echo "<p><b>Enter the code below:</b> <span class=details><i>"
            . "(this is required because "
            . "your user account was created recently; it's to discourage "
            . "spammers from creating new accounts just to post spam "
            . "comments)</span><br>";
        captchaSubForm($captchaKey, $captchaErrMsg, "Verify Code");
    }

    echo "<p><input type=submit name=submitBtn value=\"Save Changes\"> ";

    echo "<p>";

    if ($commentID) {
        echo "<a href=\"$urlWithSrc&delete=$commentID"
            . "&src=$srcpageParam\">Delete this comment</a><p>";
    }

    echo "<a href=\"$srcpage\">Return without saving changes</a>"
        . "</form>";

    // show the reference item if applicable
    showCommentReference($db, $refRec);
}

pageFooter();

?>
