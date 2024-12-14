<?php

// To use this module:
//
// include_once "pagetpl.php";
//
// call initReviewVote() somewhere in the <BODY> section, to insert the
// javascript code for review voting
//

// --------------------------------------------------------------------------
//
// Get the various forms of the review query

// make sure we process any persistent login state
include_once "login-persist.php";
include_once "commentutil.php";

// get the query given a WHERE condition
function getReviewQuery($db, $where)
{
    // check for a logged-in user
    $curuser = checkPersistentLogin();

    // If we're logged in, set up the review query modifiers for muting
    // and promotions and demotions.
    $notMuted = '1';
    $joinUserFilter = '';
    if ($curuser) {
        $notMuted = "(ifnull(userfilters.filtertype, '*') <> 'K')";
        $joinUserFilter = "left outer join userfilters "
                          . "on userfilters.targetuserid = reviews.userid "
                          . "and userfilters.userid = '$curuser'";
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

    // Return the full query.  This is a bit tricky, because we want to
    // aggregate the helpfulness votes into the results.  There might be
    // no votes for a given review, so we have to do an outer join to pick
    // up those reviews.  The overall helpfulness rating we want is the
    // sum of the votes, so we need to do a GROUP BY on the review ID to
    // aggregate the sum of the votes.  We also want to aggregate the
    // review flags, which we concatenate into one string field per
    // review with GROUP_CONCAT().
    //
    // When using this query, you can throw on an ORDER BY at the end to
    // sort by the desired metric - we provide the computed column
    // 'netHelpful' in the results specifically to allow sorting by
    // helpfulness.  We also select the mod date in raw form as well
    // as formatted for display, also to faciliate sorting.
    return
        "select sql_calc_found_rows
           reviews.id as reviewid, rating, summary, review,
           date_format(greatest(reviews.moddate, ifnull(embargodate, cast(0 as datetime))), '%M %e, %Y') as moddatefmt,
           greatest(reviews.createdate, ifnull(embargodate, cast(0 as datetime))) as publicationdate,
           date_format(greatest(reviews.createdate, ifnull(embargodate, cast(0 as datetime))), '%M %e, %Y') as publicationdatefmt,
           users.id as userid, users.name as username,
           users.location as location, special,
           sum(reviewvotes.vote = 'Y' and ifnull(rvu.sandbox, 0) in $sandbox) as helpful,
           sum(reviewvotes.vote = 'N' and ifnull(rvu.sandbox, 0) in $sandbox) as unhelpful,
           ifnull(sum(reviewvotes.vote = 'Y' and ifnull(rvu.sandbox, 0) in $sandbox), 0)
              - ifnull(sum(reviewvotes.vote = 'N' and ifnull(rvu.sandbox, 0) in $sandbox), 0)
              as netHelpful,
           group_concat(distinct reviewflags.flagtype separator '') as flags,
           reviews.gameid as gameid,
           reviews.RFlags as RFlags
         from
           reviews
           left outer join reviewvotes on reviewvotes.reviewid = reviews.id
           left outer join users on users.id = reviews.userid
           left outer join users rvu on rvu.id = reviewvotes.userid
           $joinUserFilter
           left outer join reviewflags on reviewflags.reviewid = reviews.id
         where
           ($where)
           and ifnull(now() >= reviews.embargodate, 1)
           and $notMuted
           and ifnull(users.sandbox, 0) in $sandbox
         group by
           reviews.id";
}

function getReviewQueryByReview($db, $revid)
{
    $revid = mysql_real_escape_string($revid, $db);
    return getReviewQuery($db, "reviews.id = '$revid'");
}

function getReviewQueryByGame($db, $gameID, $cond = '1')
{
    $gameID = mysql_real_escape_string($gameID, $db);
    return getReviewQuery($db, "reviews.gameid = '$gameID' and ($cond)");
}

// --------------------------------------------------------------------------
// initialize specialNames
global $specialCodes;
function initSpecialNames($db)
{
    global $specialCodes;

    // query the special name list from the database
    $result = mysql_query("select id, name, code from specialreviewers", $db);

    // load the array
    $specialNames = [];
    $specialCodes = [];
    while ([$id, $name, $code] = mysql_fetch_row($result)) {
        $specialNames[$id] = $name;
        $specialCodes[$id] = $code;
    }

    // return it
    return $specialNames;
}

// --------------------------------------------------------------------------
// initialize the review voting system
//
function initReviewVote()
{
?>
<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--

function sendReviewVote(reviewID, vote)
{
    jsonSend(`reviewvote?id=${reviewID}&vote=${vote}`,
             `voteMsg_${reviewID}`,
             () => displayReviewVote(reviewID, vote));
}
function displayReviewVote(reviewID, vote)
{
    if (vote != null)
    {
        if (vote == "R")
        {
            document.getElementById("voteStat_" + reviewID).innerHTML = "";
            document.getElementById("voteRemove_" + reviewID).innerHTML = "";
        }
        else {
            document.getElementById("voteStat_" + reviewID).innerHTML =
                    "<br>(You voted "
                    + (vote == 'Y' ? "Yes" : "No")
                    + ")";
            document.getElementById("voteRemove_" + reviewID).innerHTML = "<a href=\"needjs\">Remove vote</a> &nbsp; ";
            document.getElementById("voteRemove_" + reviewID).querySelector('a').addEventListener('click', function (event) {
                event.preventDefault();
                sendReviewVote(reviewID, 'R');
            })
        }
    }
    else {
        document.getElementById("voteRemove_" + reviewID).innerHTML = "";
    }
}


var curPopupMenu = null;
function popVoteMenu(reviewID)
{
    closePopupMenu(null);
    curPopupMenu = document.getElementById("voteMenu_" + reviewID);
    curPopupMenu.style.display = "inline";
}

var oldDocMouseDown = document.onmousedown;
document.onmousedown = mdClosePopupMenu;
function mdClosePopupMenu(event)
{
    var targ;
    if (!event)
        event = window.event;
    if (event.target)
        targ = event.target;
    else if (event.srcElement)
        targ = event.srcElement;
    else if (targ.nodeType == 3)
        targ = targ.parentNode;
    while (targ != null && targ != curPopupMenu)
        targ = targ.parentNode;

    closePopupMenu(targ);
    if (oldDocMouseDown)
        oldDocMouseDown();
}
function closePopupMenu(targ)
{
    if (curPopupMenu)
    {
        if (targ != curPopupMenu)
        {
            curPopupMenu.style.display = "none";
            curPopupMenu.parentNode.style.display = "none";
            curPopupMenu.parentNode.style.display = "inline";
            curPopupMenu = null;
        }
    }
}
function popupMenuKey(e, id)
{
    var ch = (window.event || e.keyCode ? e.keyCode : e.which);
    if (ch == 27)
    {
        closePopupMenu(null);
        document.getElementById('voteMenuLink_' + id).focus();
        return false;
    }
    return true;
}

//-->
</script>
<style nonce="<?php global $nonce; echo $nonce; ?>">
.reviews__moreOptions {
    display: inline;
    position: relative;
}
.reviews__voteMenu {
    display: none;
    position: absolute;
    left: 0px;
    z-index: 20000;
}
.reviews__separator {
    height: 1ex;
}
</style>
<?php
}

// --------------------------------------------------------------------------
// display a review
//
define("SHOWREVIEW_NOVOTECTLS", 0x0001);
define("SHOWREVIEW_NOCOMMENTCTLS", 0x0002);
define("SHOWREVIEW_COMMENTCTLSADDONLY", 0x0004);
define("SHOWREVIEW_ADMINREVIEWVOTESLINK", 0x0008);
define("SHOWREVIEW_COLLAPSELONG", 0x0010);

function showReview($db, $gameid, $rec, $specialNames, $optionFlags = 0)
{
    global $specialCodes;

    // note whether or not they want voting and/or comment controls
    $showVoteCtls = !($optionFlags & SHOWREVIEW_NOVOTECTLS);
    $showCommentCtls = !($optionFlags & SHOWREVIEW_NOCOMMENTCTLS);
    $addCommentOnly = ($optionFlags & SHOWREVIEW_COMMENTCTLSADDONLY);
    $adminReviewVotes = ($optionFlags & SHOWREVIEW_ADMINREVIEWVOTESLINK);
    $collapseLong = ($optionFlags & SHOWREVIEW_COLLAPSELONG);

    // get the current user, if we're logged in
    checkPersistentLogin();
    $curuser = isset($_SESSION['logged_in'])
               ? $_SESSION['logged_in_as'] : false;

    // pull out the fields from the review record
    $reviewid = $rec['reviewid'];
    $qreviewid = mysql_real_escape_string($reviewid, $db);
    $rating = $rec['rating'];
    $summary = htmlspecialcharx($rec['summary']);
    $review = fixDesc($rec['review'], FixDescSpoiler);
    $publicationdate = $rec['publicationdatefmt'];
    $moddate = $rec['moddatefmt'];
    $lastupdateFootnote = null;
    if ($moddate && $publicationdate != $moddate) {
        // Rating-only review
        if ($review == "") {
            $publicationdate .= " (last edited on $moddate)";
        } else {
            $lastupdateFootnote = "This review was last edited on $moddate";
            $publicationdate .= "<span title=\"$lastupdateFootnote\">*</span>";
        }
    }
    $specialName = isset($rec['specialname']) ? $rec['specialname'] : false;
    $specialID = $rec['special'];
    $userid = $rec['userid'];
    $username = htmlspecialcharx($rec['username']);
    $location = htmlspecialcharx($rec['location']);
    $helpful = $rec['helpful'];
    $unhelpful = $rec['unhelpful'];
    $totalvotes = $helpful + $unhelpful;
    $flags = $rec['flags'];
    $spoilerFlag = (strpos($flags, "S") !== false);
    $oldVersion = ($rec['RFlags'] & RFLAG_OLD_VERSION);
    $omitAvg = ($rec['RFlags'] & RFLAG_OMIT_AVG);

    // fetch the review tags
    $tags = queryReviewTags($db, $reviewid);

    // if we have a 'special' ID key but not the name, fetch the name
    if (!isEmpty($specialID) && isEmpty($specialName))
        $specialName = $specialNames[$specialID];

    $specialCode = (isset($specialCodes[$specialID])
                    ? $specialCodes[$specialID] : false);

    // if it's an external review, fetch the extra data
    if ($specialCode == 'external') {
        $result = mysql_query(
            "select url, sourcename, sourceurl from extreviews
             where reviewid = '$qreviewid'", $db);
        list($xurl, $xsrc, $xsrcurl) = mysql_fetch_row($result);
        $xsrc = htmlspecialcharx($xsrc);
    }

    // it's a special review if it has a special review name
    $isSpecial = !isEmpty($specialName);

    // by default, don't show comments for special reviews
    $dfltComments = !$isSpecial;

    // if this is a review-less rating, show the rating-only format
    if ($review == "") {
        echo "<p>" . showStars($rating)
            . " - <a href=\"showuser?id=$userid\">$username</a>"
            . (!isEmpty($location) ? " ($location)" : "")
            . "<span class=details>, $publicationdate</span><p>";
        return;
    }

    // show the headline according to whether this is regular or editorial
    if ($specialCode == 'external') {

        if ($xsrcurl)
            echo "<p><span class=xsrc><a href=\"$xsrcurl\">$xsrc</a>"
                . "</span><br>";
        else if ($xsrc)
            echo "<p><span class=xsrc>$xsrc</span><br>";

    } else if ($isSpecial) {

        // this is an editorial review - show the special name header
        echo "<h3>$specialName</h3>";

    } else {
        // this is an ordinary review - start with the helpfulness votes
        if ($helpful != 0 || $unhelpful != 0) {
            echo "<div class=smallhead><div class=details>$helpful of
                  $totalvotes people found the following review helpful:
                 ".($adminReviewVotes ? "<a href=\"/adminops?reviewvotes&reviewid=$reviewid\">Admin: Who?</a>" : "")."</div></div>";
        }
    }

    // show the rating
    echo showStars($rating);

    if ($specialCode == 'external') {
        // external - show the summary after the star rating, but with
        // no author or mod date
        if ($summary)
            echo " <b>$summary</b>";
        if ($summary || $rating)
            echo "<br>";
    } else if ($isSpecial) {

        // special - show the rating stars on a line by themselves
        // (if there's a rating at all), and skip the headline and author
        if ($rating)
            echo "<br>";

        // for Author or IFDB reviews, add the reviewer
        if (($specialCode == 'author' || $specialCode == 'ifdb') && $userid) {

            // add the reviewer by-line
            echo "<div class=smallhead><span class=details>by "
                . "<a href=\"showuser?id=$userid\">$username</a></span></div>";

            // allow comments for these reviews
            $dfltComments = true;
        }

    } else {
        // not special - show the headline and author
        echo " <b>$summary</b><span class=details>, $publicationdate</span><br>"
            . "<div class=smallhead><div class=details>"
            .   "by <a href=\"showuser?id=$userid\">$username</a>"
            . (!isEmpty($location) ? " ($location)" : "")
            . "</div>";
    }

    if ($tags && count($tags)) {
        echo "<div class=details>Related reviews: ";
        $sep = "";
        foreach ($tags as $t) {
            $tu = urlencode($t);
            $td = htmlspecialcharx($t);
            echo "$sep<a href=\"allreviews?id=$userid&tag=$tu\">$td</a>";
            $sep = ", ";
        }
        echo "</div>";

        if ($isSpecial)
            echo "<br>";
    }

    if (!$isSpecial)
        echo "</div>";

    // If there's a spoiler flag, put the whole review in a spoiler warning.
    if ($spoilerFlag) {
        $review = spoilerWarning(
            $review, "<b>Warning:</b> This review might contain spoilers. "
            . "Click to show the full review.");
        $review .= spoilerWarningScript();
    }

    // show the review body
    if ($collapseLong) {
        global $nonce;
        echo "<style nonce='$nonce'>.review__body { max-width: 60ch; }</style>"
            ."<div class=\"readMore review__body\" ><p>$review</p>"
            .'<div class="expand"><button>Read More</button></div></div>';
    } else {
        echo "<div class=\"review__body\" ><p>$review</p></div>";
    }
    if ($lastupdateFootnote) {
        echo "<small><i>* $lastupdateFootnote</i></small>";
    }

    // set up the comment controls, if applicable
    $commentCtls = $barCommentCtl = "";
    if ($showCommentCtls
        && ($curuser == $userid || ($dfltComments && $showVoteCtls))) {

        // if we want the View Comments control, check to see if it's needed
        if ($addCommentOnly) {
            // add only - pretend there are no comments
            $commentCount = 0;
        } else {
            // count the comments
            $commentCount = countComments($db, "R", $qreviewid);
        }

        // set up the <a href> for adding a comment
        $addCommentLink = "<a href=\"reviewcomment?review=$reviewid\">";

        // generate the controls, depending on the existing comment count
        if ($commentCount == 0) {
            $commentCtls = "{$addCommentLink}Add a comment</a>";
        } else {
            $commentCtls =
                "<a href=\"viewgame?id=$gameid&review=$reviewid#comments\">"
                . "View comments ($commentCount)</a> - "
                . "{$addCommentLink}Add comment</a>";
        }

        $barCommentCtls = "| $commentCtls";
    }

    // add any special notes
    if ($oldVersion || $omitAvg) {

        $notes = array();
        if ($oldVersion)
            $notes[] = "this review is based on older version of the game";
        if ($omitAvg)
            $notes[] = "this rating is not included in the game's average";

        $notes = implode("; ", $notes);
        echo "<div class=smallfoot><span class=details>"
            . "<i>Note: $notes.</i></span></div>";
    }

    // if this isn't a special review, offer Helpful/Unhelpful voting
    if ($curuser == $userid) {

        echo "<div class=smallfoot><span class=details>
               <i>You wrote this review -
               <a href=\"review?id=$gameid&userid=$userid\">Revise it</a></i>
               | <a href=\"viewgame?id=$gameid&review=$reviewid\">Direct link</a> $barCommentCtls</span></div>";

    } else if ($specialCode == 'external') {

        if ($xurl)
            echo "<span class=details>"
                . "<a href=\"$xurl\">See the full review</a></span><br>";

    } else if (!$isSpecial && $showVoteCtls) {

        // check for an existing vote for this user
        $oldvote = "null";
        if ($curuser) {
            $result = mysql_query(
                "select vote from reviewvotes
                 where reviewid = '$qreviewid' and userid = '$curuser'", $db);
            if (mysql_num_rows($result) > 0)
                $oldvote = "'" . mysql_result($result, 0, "vote") . "'";
        }
        global $nonce;

        echo "<div class=smallfoot><span class=details>";

        if (!$curuser) {
            echo "You can <a href=\"login?dest=viewgame%3Fid%3D$gameid%26review%3D$reviewid\">log in</a> "
               . "to rate this review, mute this user, or add a comment.";
        } else {
            echo "Was this review helpful to you? &nbsp; "
                . "<a href=\"needjs\">"
                . addEventListener('click', "sendReviewVote('$reviewid', 'Y'); return false;")
                . "Yes</a> &nbsp; "
                . "<a href=\"needjs\">"
                . addEventListener('click', "sendReviewVote('$reviewid', 'N'); return false;")
                . "No</a> &nbsp; "
                . "<span id=\"voteRemove_$reviewid\"><a href=\"needjs\">"
                . addEventListener('click', "sendReviewVote('$reviewid', 'R'); return false;")
                . "Remove vote</a> &nbsp; </span>";

                if (check_admin_privileges($db, $curuser)) {
                    echo "<a href=\"review?id=$gameid&userid=$userid\">Edit</a>&nbsp; ";
                }

            echo  "<div class='reviews__moreOptions'>"
                . "<a href=\"#\" id=\"voteMenuLink_$reviewid\">"
                . addEventListener('click', "popVoteMenu('$reviewid'); return false;")
                . "More Options<img src=\"/img/blank.gif\" "
                . "class=\"popup-menu-arrow\"></a>"

                . "<div id=\"voteMenu_$reviewid\" class='reviews__voteMenu'>"
                . addEventListener('click', "closePopupMenu(null);")
                . addEventListener('keypress', "return popupMenuKey(event,'$reviewid');")
                . "<br>"
                . "<div class=\"popupMenu\">"
                . "<table border=0 cellspacing=0 cellpadding=0>"
                . "<tr><td><a href=\"userfilter?user=$userid&action=promote\" "
                . "title=\"Show me this user's reviews first "
                . "(this will only be visible to you)\"><nobr>"
                . "Promote this user</nobr></a>"
                . "</td></tr>"
                . "<tr><td><a href=\"userfilter?user=$userid&action=demote\" "
                . "title=\"Show me this user's reviews last "
                . "(this will only be visible to you)\"><nobr>"
                . "Demote this user</nobr></a>"
                . "</td></tr>"
                . "<tr><td><a href=\"userfilter?user=$userid&action=mute\" "
                . "title=\"Never show me this user's "
                . "reviews at all\"><nobr>Mute this user</nobr></a>"
                . "</td></tr>"
                . "<tr><td><a href=\"reviewflag?review=$reviewid&type=spoilers\" "
                . "title=\"Warn other users that this review "
                . "contains unmarked spoilers\"><nobr>Flag spoilers</nobr></a>"
                . "</td></tr>"
                . "<tr><td><a href=\"reviewflag?review=$reviewid&type=inappropriate\" "
                . "title=\"Notify moderators that this review violates "
                . "the community guidelines\"><nobr>Flag as inappropriate</nobr></a>"
                . "</td></tr>"
                . "<tr><td><a href=\"viewgame?id=$gameid&review=$reviewid\" "
                . "title=\"Direct link to this review\"><nobr>Direct link</nobr></a>"
                . "</td></tr>"
                . "<tr class='reviews__separator'><td></td></tr>"
                . "<tr><td><a href=\"userfilter?list\">"
                . "<nobr>View my user filters</nobr></a>"
                . "</td></tr>"
                . "<tr><td>" . helpWinLink("help-review-votes", "<nobr>Explain these options</nobr>")
                . "</td></tr>"
                . "</table>"
                . "</div>"
                . "</div>"
                . "</div>"

                . "&nbsp;$barCommentCtls"
                . "&nbsp;<span id=\"voteMsg_$reviewid\" class=\"xmlstatmsg\">"
                . "</span><span id=\"voteStat_$reviewid\"></span>"
                . "<script type=\"text/javascript\" nonce=\"$nonce\">\r\n<!--\r\n"
                . "displayReviewVote('$reviewid', $oldvote);"
                . "\r\n//-->\r\n</script>\r\n";
        }

        echo "</span></div>";

    } else if ($specialCode == 'author' || $specialCode == 'ifdb') {
        echo "<div class=smallfoot><span class=details>$commentCtls"
            . "</span></div>";
    }


    echo "<br>";
}

// ------------------------------------------------------------------------
// Get the tags associated with a review, as an array.
function queryReviewTags($db, $reviewID)
{
    $tags = [];
    $result = mysqli_execute_query($db,
        "select tag from reviewtags where reviewid=?", [$reviewID]);
    if (mysql_num_rows($result) > 0) {
        while ([$tag] = mysql_fetch_row($result)) {
            $tags[] = $tag;
        }
    }
    return $tags;
}

?>
