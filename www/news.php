<?php

include_once "util.php";

// ------------------------------------------------------------------------
//
// News Source Type Codes:
//
//    G  - game
//    C  - competition
//    U  - club
//


// ------------------------------------------------------------------------
//
// Show a summary news list, as part of a detail view page
//
function newsSummary($db, $sourceType, $sourceID, $maxItems,
                     $sectionHeader, $sectionFooter)
{
    // set up the link source string
    $src = "&type=$sourceType&source=$sourceID";

    // query a few rows of news on this game
    $result = queryNews($db, $sourceType, $sourceID, false,
                        "limit 0, $maxItems", $rowcnt);
    if ($rowcnt > 0)
    {
        echo $sectionHeader;

        for ($i = 0 ; $i < $rowcnt && $i < $maxItems ; $i++) {

            // fetch it
            list($newsid, $newsidOrig, $newsstatus,
                 $newsdateRaw, $newsdate, $newsmodRaw, $newsmod,
                 $newsuid, $newsuname,
                 $newsuidOrig, $newsunameOrig,
                 $newshead, $newsbody) = mysql_fetch_row($result);

            $newshead = htmlspecialcharx($newshead);
            $newsbody = fixDesc($newsbody, FixDescSpoiler);
            $newsuname = htmlspecialcharx($newsuname);
            $newsunameOrig = htmlspecialcharx($newsunameOrig);

            $byline = "Reported by <a href=\"showuser?id=$newsuidOrig\">"
                      . "$newsunameOrig</a>";
            if ($newsuid != $newsuidOrig) {
                $byline .= " (updated by <a href=\"showuser?id=$newsuid\">"
                           . "$newsuname</a> on $newsmod)";
            } else if ($newsmodRaw != $newsdateRaw) {
                $byline .= " (updated on $newsmod)";
            }

            // display it
            echo "<div class=\"newsItemHeadline\">"
                . "<a href=\"needjs\" onclick=\"javascript:expandNews($i);"
                . "return false;\">$newshead</a>"
                . " <span class=\"newsItemDate\">$newsdate</span>"
                . "</div>"
                . "<div id=\"newsBody$i\" class=\"newsItemBody\" "
                . "style=\"display: none;\"><div>$newsbody</div>"
                . "<span class=details>$byline | "
                . "<a href=\"newslog?newsid=$newsid&history\">History</a> | "
                . "<a href=\"editnews?newsid=$newsid$src\">"
                . "Edit</a> | "
                . "<a href=\"editnews?newsid=$newsid$src&delete\">Delete</a>"
                . "</span>"
                . "</div>";
        }

?>
<script type="text/javascript">
<!--

function expandNews(n)
{
    var ele = document.getElementById("newsBody" + n);
    ele.style.display = (ele.style.display == "block" ? "none" : "block");
}

//-->
</script>
<?php

            echo "<span class=details>"
                . "<a href=\"newslog?$src\">"
                . ($rowcnt > $maxItems ? "More news..." : "Expand all")
                . "</a> | <a href=\"editnews?$src\">"
                . "Add a news item</a></span>$sectionFooter";
    }

    return $rowcnt;
}


// ------------------------------------------------------------------------
//
// Get news as RSS.
//
//   $items is an array - we add the queried items to this array on return
//   $db is the mysql connection
//   $sourceType is the source type - "G" for games, "C" for competitions
//   $maxItems is the maximum number of items to fetch; 0 means infinite
//   $titleFunc is a function($titleCtx, "headline") that we invoke to
//        generate the RSS title element for each headline
//   $titleCtx is context information that we simply pass to $titleFunc
//
function queryNewsRss(&$items, $db, $sourceType, $sourceID, $maxItems,
                      $titleFunc, $titleCtx)
{
    // set up a limit clause, if they specified an item limit
    $limit = $maxItems ? "limit 0, $maxItems" : "";

    // query the news history
    $result = queryNews($db, $sourceType, $sourceID, false,
                        $limit, $rowcnt);

    // process the results
    for ($items = array(), $i = 0 ; $i < $rowcnt ; $i++)
    {
        // fetch and decode this row
        list($nid, $norig, $nstatus,
             $ncre, $ncreFmt, $nmod, $nmodFmt,
             $nuid, $nuname, $nuidOrig, $nunameOrig,
             $nhead, $nbody) = mysql_fetch_row($result);

        // quote/reformat fields
        $nuname = htmlspecialcharx($nuname);
        $nunameOrig = htmlspecialcharx($nunameOrig);
        $rssdate = date("D, j M Y H:i:s ", strtotime($ncre)) . 'UT';
        list($rbody, $len, $trunc) = summarizeHtml($body, 210);
        $nbody = htmlspecialcharx(fixDesc($nbody));

        $nlink = htmlspecialcharx(
            get_root_url() . "newslog?newsid=$norig");

        // generate the title
        $nhead = htmlspecialcharx($titleFunc($titleCtx, $nhead));

        // build the item
        $item = "<item>"
                . "<title>$nhead</title>"
                . "<description>Posted by $nunameOrig: $nbody</description>"
                . "<pubDate>$rssdate</pubDate>"
                . "<guid>$nlink</guid>"
                . "</item>";

        // add the news
        $items[] = array($ncre, $item);
    }
}


// ------------------------------------------------------------------------
//
// Query news records for a given source.  Fills in rowcnt with the
// full calculated row count.
// 
function queryNews($db, $sourceType, $sourceID, $includeDeletions,
                   $limit, &$rowcnt)
{
    // get the logged-in user
    checkPersistentLogin();
    $curuser = $_SESSION['logged_in_as'];

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
    
    // if there's a limit clause, also calculate the row count
    $calcFoundRows = ($limit ? "sql_calc_found_rows" : "");

    // add some extra query parameters if we want deletions
    if ($includeDeletions)
    {
        // include deleted status
        $statusList = "('A', 'D')";

        // join the superseded row to get deleted headlines
        $headline = "if (n.status = 'A', n.headline, "
                    . " concat('[Deleted Item] ', nprv.headline))";
        $joinPrv = "left outer join news as nprv"
                   . "  on nprv.newsid = n.supersedes";
    }
    else
    {
        $statusList = "('A')";
        $headline = "n.headline";
        $joinPrv = "";
    }

    // run the query
    $result = mysql_query(
        "select $calcFoundRows
           n.newsid, ifnull(n.original, n.newsid), n.status,
           n.created, date_format(n.created, '%M %e, %Y'),
           n.modified, date_format(n.modified, '%M %e, %Y'),
           n.userid, u.name,
           norig.userid, uorig.name,
           $headline, n.body
         from
           news as n
           join users as u on u.id = n.userid
           join news as norig on norig.newsid = ifnull(n.original, n.newsid)
           join users as uorig on uorig.id = norig.userid
           left outer join news as nsuper on nsuper.supersedes = n.newsid
           $joinPrv
         where
           n.source = '$sourceType' and n.sourceid = '$sourceID'
           and n.status in $statusList
           and nsuper.newsid is null
           $andNotPlonked
           and u.sandbox in $sandbox
         order by
           n.created desc
         $limit", $db);

    // if there's a limit clause, query the row count separately; otherwise
    // the row count is simply what's returned from the query
    if ($limit) {
        $result2 = mysql_query("select found_rows()", $db);
        list($rowcnt) = mysql_fetch_row($result2);
    } else {
        $rowcnt = mysql_num_rows($result);
    }

    // return the result set
    return $result;
}

?>
