<?php

include_once "session-start.php";
include_once "dbconnect.php";
include_once "images.php";
include_once "current-user.php";
include_once "login-links.php";
include_once "pagetpl.php";
include_once "image-util.php";
require_once 'vendor/autoload.php';

// https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#implement-proper-password-strength-controls
// "It is important to set a maximum password length to prevent long password Denial of Service attacks."
// https://www.acunetix.com/vulnerabilities/web/long-password-denial-of-service/
define("MAX_PASSWORD_LENGTH", 1024);

// --------------------------------------------------------------------------
//
// Terms of Service version number
//
define("TOS_VERSION", 2);
define("TOS_DATE", "September, 2010");

// --------------------------------------------------------------------------
// USERS table EMAILFLAGS column bit values
//
define("EMAIL_CAPTCHA",  0x0001);
define("EMAIL_CLOAKED",  0x0002);

// --------------------------------------------------------------------------
// REVIEWS table RFLAGS column bit values
//
define("RFLAG_OLD_VERSION",  0x0001);
define("RFLAG_OMIT_AVG",     0x0002);

// --------------------------------------------------------------------------
// GAMELINKS table ATTRS column bit values
//
define("GAMELINK_IS_GAME",  0x0001);
define("GAMELINK_PENDING",  0x0002);

// --------------------------------------------------------------------------
// GAMES table FLAGS column bit values
//
define("FLAG_SHOULD_HIDE", 0x0001);

// --------------------------------------------------------------------------
// POLLS table FLAGS column bit values
//
define("POLL_FLAG_ANONYMOUS", 0x0001);
define("POLL_FLAG_LOCKED", 0x0002);

define("STYLESHEET_DARKFORCE_DARK", 1);
define("STYLESHEET_DARKFORCE_LIGHT", 2);

// ------------------------------------------------------------------------
//
// Encode output for RSS.  We send RSS in UTF-8 format, so this converts
// to UTF-8.
//
function rss_encode($str)
{
    return $str;
}

// ------------------------------------------------------------------------
//
// XML serialization of arrays. Assumes UTF-8 data.
//
function serialize_xml($array) {
    $parts = [];

    foreach ($array as $key => $value) {
        if (is_int($key)) {
            $parts[] = serialize_xml($value);
            continue;
        }

        $attrs_str = '';
        if ($attrs = $value['_attrs'] ?? null) {
            $attrs_parts = [];
            foreach ($attrs as $akey => $avalue) {
                $avalue = htmlspecialcharx($avalue);
                $attrs_parts[] = " {$akey}=\"{$avalue}\"";
            }
            $attrs_str = implode('', $attrs_parts);

            $value = $value['_contents'];
        }

        // Booleans are just empty tags
        if (is_bool($value)) {
            if ($value) {
                $parts[] = "<$key$attrs_str/>";
            }
            continue;
        }

        $parts[] = "<$key$attrs_str>";
        if (is_array($value)) {
            $parts[] = serialize_xml($value);
        } else {
            if (is_string($value)) {
                $value = htmlspecialcharx($value);
            } else if (is_bool($value)) {
                $value = $value ? 'yes' : 'no';
            }
            $parts[] = $value;
        }
        $parts[] = "</$key>";
    }

    return implode('', $parts);
}

// ------------------------------------------------------------------------
//
// Given a UTF-8 string, substitute ASCII approximations for certain
// characters that we'd otherwise lose on translation.
//
function approx_utf8($str)
{
    return str_replace(
        ["\xE2\x80\x99", "\xC2\xB4", " \xEF\xBF\xBD ", "\xE2\x80\xA6",
            "\xEF\xBF\xBDs"],
        ["'", "'", " - ", "...", "'s"],
        $str);
}

/**
 * htmlspecialchars() replacement that also coerces null to ''.
 *
 * @deprecated Use htmlspecialchars() instead. TODO delete.
 */
function htmlspecialcharx($str)
{
    return htmlspecialchars($str ?? '');
}

// sometimes we want to display long file names containing underscores
// but we want those strings to wrap properly, so we insert
// zero-width space characters
function zeroWidthSpaceUnderscores($str) {
    return str_replace('/', '&#8203;/', str_replace('_', '&#8203;_', $str));
}

// --------------------------------------------------------------------------
// are we on an iPhone or Android device?
//
function is_mobile()
{
    // get the browser ID string
    $b = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // check for the relevant strings
    return preg_match(
        "/^Mozilla\/[0-9.]+ \((iPhone;|iPod;|iPhone Simulator;|Linux; Android)/",
        $b, $match, 0, 0);
}

// are we on a Kindle?
function is_kindle()
{
    // get the browser ID string
    $b = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // check for the relevant strings
    return preg_match("#Kindle/[0-9]#", $b, $match, 0, 0);
}

// is the browser Opera?
function is_opera()
{
    // get the browser ID string
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // check for the telltale substring
    return stristr($ua, 'opera') !== false;
}

// is the browser firefox?
function is_firefox()
{
    // get the browser ID string
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // check for the telltale substring
    return stristr($ua, 'Firefox') !== false;
}

function is_ie()
{
    // get the browser ID string
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // check for the telltale substring
    return stristr($ua, 'MSIE') !== false;
}

// --------------------------------------------------------------------------
// get request data, stripping "magic quotes" as needed
//
function get_req_data($id)
{
    // get the raw value from the posted data
    $val = (isset($_POST[$id]) && $_POST[$id]) ? $_POST[$id] :
           (isset($_REQUEST[$id]) ? $_REQUEST[$id] : "");

    // return the result
    return $val;
}

// Parse a pager `pg` request value. Returns [true, 1] for Show All,
// otherwise [false, N] with N at least 1. Non-scalars (e.g. pg[])
// are treated as page 1.
function parse_page($pg)
{
    if (!is_scalar($pg)) {
        $pg = "";
    }

    if ($pg === 'all') {
        return [true, 1];
    }

    $pg = (int)$pg;
    if ($pg < 1) {
        $pg = 1;
    }
    return [false, $pg];
}

// --------------------------------------------------------------------------
// quote a SQL LIKE term - escape "%" and "_" characters
//
function quoteSqlLike($s)
{
    return preg_replace("/[%_]/", "\\$0", $s);
}

function fulltextWords($s)
{
    return preg_split("/[^\\p{L}\\p{M}\\p{N}_']+/u", $s, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

// InnoDB checks each apostrophe-separated part of a word against the stopwords
function fulltextStopwordParts($w, $stopwords)
{
    $parts = preg_split("/'+/", mb_strtolower($w), -1, PREG_SPLIT_NO_EMPTY);
    return [count(array_intersect($parts, $stopwords)), count($parts)];
}

// make one search term safe for InnoDB's boolean-mode MATCH parser
function fulltextTerm($term, $stopwords = [])
{
    preg_match('/^([-+~<>]?)("?)(.*?)"?$/s', $term, $m);
    [, $op, $quote, $text] = $m;
    $words = fulltextWords($text);
    $isStopword = function ($w) use ($stopwords) {
        [$n, $total] = fulltextStopwordParts($w, $stopwords);
        return $n == $total;
    };
    if ($quote) {
        if (!array_filter($words, fn($w) => !$isStopword($w)))
            return "";
        return "$op\"" . implode(" ", $words) . "\"";
    }
    $star = preg_match("/\\*[^\\p{L}\\p{M}\\p{N}_']*$/u", $text) ? "*" : "";
    $last = count($words) - 1;
    $words = array_values(array_filter($words, fn($w, $i) => ($star && $i == $last) || !$isStopword($w), ARRAY_FILTER_USE_BOTH));
    if (!$words)
        return "";
    if ($op == "+" && !($star && count($words) == 1) && fulltextStopwordParts($words[0], $stopwords)[0])
        $op = "";
    return $op . implode(" ", $words) . $star;
}

function fulltextTerms($s, $stopwords = [])
{
    preg_match_all('/[-+~<>]?\(|\)|[-+~<>]?"[^"]*"?|[^\s"()]+/', $s, $m);
    $terms = [];
    $depth = 0;
    $balanced = true;
    foreach ($m[0] as $t) {
        if (substr($t, -1) == "(")
            $depth++;
        else if ($t == ")")
            $balanced = $balanced && --$depth >= 0;
        else
            $t = fulltextTerm($t, $stopwords);
        if ($t !== "")
            $terms[] = $t;
    }
    if (!$balanced || $depth)
        $terms = array_filter($terms, fn($t) => $t != ")" && substr($t, -1) != "(");
    $out = [];
    foreach ($terms as $t) {
        if ($t == ")" && $out && substr(end($out), -1) == "(")
            array_pop($out);
        else
            $out[] = $t;
    }
    return $out;
}

function fulltextAllWords($s, $stopwords = [])
{
    $terms = [];
    foreach (fulltextWords($s) as $w) {
        [$n, $total] = fulltextStopwordParts($w, $stopwords);
        if ($n < $total)
            $terms[] = ($n ? "" : "+") . $w;
    }
    return implode(" ", $terms);
}

function fulltextStopwords($db)
{
    static $stopwords = null;
    if ($stopwords === null) {
        $stopwords = [];
        $result = mysql_query("select value from stopwords", $db);
        while ($row = mysql_fetch_row($result))
            $stopwords[] = mb_strtolower($row[0]);
    }
    return $stopwords;
}

// quote a SQL RLIKE term - escape regular-expression characters
function quoteSqlRLike($s)
{
    // https://stackoverflow.com/a/53986553/54829
    return preg_quote($s, '&');
}

// --------------------------------------------------------------------------
// escape a string for javascript purposes: converts \ -> \\, " -> \",
// newline -> \n
//
function jsSpecialChars($s)
{
    $s = preg_replace("/[\\\"\']/", "\\\\$0", $s);
    return str_replace(["\r", "\n"], ["\\r", "\\n"], $s);
}

// --------------------------------------------------------------------------
// Generate a TUID (a Tads.org Unique ID: a random 16-character alphanumeric
// key).  If $tableCol and $maxtries are defined, we'll check the given
// "table.column" items for collisions with the number we choose, and
// we'll pick again if we find a collision.  We'll retry up to $maxtries
// times before giving up and returning false.  If $tableCol is false,
// we'll simply generate one random TUID and return it.
//
// $tableCol is given as "table.col", naming the table and column to
// check.  To check multiple tables, provide a list of table.col entries
// separated by commas.
//
function generateTUID($db, $tableCol, $maxtries)
{
    // always try once
    if (!($maxtries > 1))
        $maxtries = 1;

    // make a list out of the table.column entries
    if ($tableCol)
        $tableCol = explode(",", $tableCol);

    // try up to $maxtries times
    for ($tries = 0, $result = 1 ; $result && $tries < $maxtries ; $tries++)
    {
        $tuid = "";
        // generate a random ID
        for ($i = 0; $i < 16; $i++) {
            $tuid .= base_convert(strval(rand(0, 35)), 10, 36);
        }

        if (!$tableCol)
            return $tuid;

        // check each table for an existing entry with this TUID
        $found = false;
        foreach ($tableCol as $tc)
        {
            // get this table and column name
            [$table, $col] = explode(".", $tc);

            // look for a row in $table with $col equal to this TUID value
            $result = mysql_query("select count(*) as c from `$table`
                where `$col` = '$tuid'", $db);

            // if we found it, keep iterating
            if (!$result || mysql_result($result, 0, "c") > 0) {
                $found = true;
                break;
            }
        }

        // if we didn't find a collision, return the TUID
        if (!$found)
            return $tuid;
    }

    // couldn't find a non-colliding TUID - give up
    return false;
}

// --------------------------------------------------------------------------
// send the stylesheet <link>
//
function echoStylesheetLink()
{
    global $cssOverride, $nonce, $ssdarkforce;
    $db = dbConnect();

    // check for a profile style
    $userid = current_user_id();
    $ssid = false;
    if ($userid && !$cssOverride) {
        $result = mysqli_execute_query($db,
            "select u.stylesheetid, s.userid, s.modified, s.dark
             from users as u
               join stylesheets as s on s.stylesheetid = u.stylesheetid
             where u.id=?", [$userid]);
        if (mysql_num_rows($result) > 0)
            [$ssid, $ssauthor, $ssmodified, $ssdarkforce] = mysql_fetch_row($result);
    }

    // check for a temporary CSS override
    if ($cssOverride) {
        $ssid = (int)$cssOverride;
        $result = mysqli_execute_query($db,
            "select userid, dark, modified from stylesheets
             where stylesheetid = ?", [$ssid]);
        [$ssauthor, $ssdarkforce, $ssmodified] = mysql_fetch_row($result);
    }

    // If we found a custom style sheet selection, use it;
    if ($ssid) {
        $stylesheet = "/users/$ssauthor/css/$ssid.css";
        $mtime = strtotime($ssmodified);
        echo "<link rel=\"stylesheet\" href=\"$stylesheet?t=$mtime\">";
    }
    // or the regular stylesheet if we're not
    else {
        $stylesheet = "/ifdb.css";
        $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $stylesheet);
        echo "<link rel=\"stylesheet\" href=\"$stylesheet?t=$mtime\">";
    }

    if ($ssdarkforce) {
        echo "<script nonce='$nonce'>forceDarkMode($ssdarkforce);</script>";
    }

}

// --------------------------------------------------------------------------
// send an image description page - this includes the image and the
// copyright information. Options for $image_type are "profile" or "cover"
function sendImageLdesc($page_title, $imageID, $image_type, $cover_art_description = "", $game_title = "", $gameID = "")
{
    global $copyrightStatList;
    $curuser = current_user_id();

    // parse the image ID:  <dbnum>:<image key>
    $parts = explode(":", (string)$imageID, 2);
    if (count($parts) < 2) {
        exit("No image is available");
    }
    [$dbnum, $key] = $parts;

    // connect to the database encoded in the ID
    $db = imageDbConnect((int)$dbnum);
    if (!$db) {
        redirectMissingImageToProductionIfLocal();
        return false;
    }

    // fetch the copyright information
    $key = mysql_real_escape_string($key, $db);
    $result = mysql_query(
        "select
           copystat, copyright, userid, date_format(createdate, '%M %e, %Y')
         from images where id='$key'", $db);
    $row = $result ? mysql_fetch_row($result) : false;
    if (!$row) {
        redirectMissingImageToProductionIfLocal();
        exit("No image is available");
    }
    [$copystat, $copymsg, $userid, $created] = $row;

    $username = false;
    if ($userid) {
        $db = dbConnect();
        $quid = mysql_real_escape_string($userid, $db);
        $result = mysql_query("select name from users where id='$quid'", $db);
        if (mysql_num_rows($result) > 0)
            [$username] = mysql_fetch_row($result);
    }

    // If this is cover art, generate the alt text for the full-sized cover art
    $large_cover_alt_text = "";
    if ($image_type == "cover") {
        if ($cover_art_description) {
            $large_cover_alt_text = $cover_art_description;
        } else {
            $large_cover_alt_text = "Cover art for $game_title.";
        }
    }
    // send the page
?>
<html>
<head>
   <?php echoStylesheetLink(); ?>
   <title><?php echo htmlspecialcharx($page_title) ?></title>
</head>
<body>
<div class=main>
   <img src="showimage?id=<?php echo urlencode($imageID) ?>" alt="<?php echo htmlspecialcharx($large_cover_alt_text) ?>">
   <?php
      if ($copymsg || ($copystat && isset($copyrightStatList[$copystat]))
          || $username || $created) {
          echo "<p><hr class=dots><span class=details>";

          if ($copymsg)
              echo "<p>" . htmlspecialcharx($copymsg) . "<br>";

          if (isset($copyrightStatList[$copystat]))
              echo $copyrightStatList[$copystat] . "<br>";

          if ($curuser && $userid == $curuser) {
              echo "<p>You uploaded this image";
              if ($created)
                  echo " on $created";
              echo " - <a href=\"editImageCopyright?id="
                  . urlencode($imageID)
                  . "\">Edit the copyright data</a><br>";

          } else if ($username || $created) {
              echo "<p>Uploaded to IFDB";
              if ($username) {
                  echo " by <a href=\"showuser?id=$userid\">"
                      . htmlspecialcharx($username) . "</a>";
              }
              if ($created)
                  echo " on $created";
              echo "<br>";
          }

          echo "</span>";
      }

      if ($image_type == "cover") {
          if ($cover_art_description) {
              global $nonce;
              echo "<style nonce='$nonce'>.image_description_box { border: 1px solid gray; padding: 0 1em; max-width: var(--paragraph-width);}</style>";
              echo "<div class='image_description_box'>";
              echo "<p><b>Image description:</b></p><p>" . htmlspecialchars($cover_art_description) . "</p>";
              echo "<p><em>Cover art descriptions are contributed by IFDB members.</em></p></div>";
                 
          } else {
              echo "<p>No image description is available. To help make IFDB more accessible, ";
              echo "you can <a href=\"editgame?&id=$gameID\">add a cover art description</a>.</p>";
          }
      }
   ?>
</div>
</body>
</html>
<?php
    exit();
}

// --------------------------------------------------------------------------
// get a string for showing a rating star image
//
function showStars($num, $allowEmpty = false)
{
    global $ssdarkforce;

    if (!$allowEmpty && isEmpty($num)) {
        return "";
    }

    // round to the nearest half star
    $stars = roundStars($num);
    if ($stars === false) {
        return "";
    }
    [$roundedNum, $starimg, $startxt] = $stars;

    // return the image string
    $result = "<span role='img' class='nobr' ";
    if ($startxt) {
        $result .= "aria-label='$startxt out of 5'>";
    } else {
        $result .= "aria-role='none'>";
    }
    for ($i = 1; $i <= $roundedNum; $i++) {
        $result .= "<img height=13 src='/img/star-checked.svg'>";
    }
    // adding unused class='star-half-checked' and 'star-unchecked' so users can override them in IFDB custom stylesheets
    if (floor($roundedNum) != $roundedNum) {
        if ($ssdarkforce === STYLESHEET_DARKFORCE_DARK) {
            $result .= "<img height=13 class='star-half-checked' src='/img/dark-images/star-half-checked.svg'>";
        } else if ($ssdarkforce === STYLESHEET_DARKFORCE_LIGHT) {
            $result .= "<img height=13 class='star-half-checked' src='/img/star-half-checked.svg'>";
        } else {
            $result .= "<picture><source srcset='/img/dark-images/star-half-checked.svg' media='(prefers-color-scheme: dark)'>"
                ."<img height=13 class='star-half-checked' src='/img/star-half-checked.svg'></picture>";
        }
        $i++;
    }
    for (; $i <=5; $i++) {
        if ($ssdarkforce === STYLESHEET_DARKFORCE_DARK) {
            $result .= "<img height=13 class='star-unchecked' src='/img/dark-images/star-unchecked.svg'>";
        } else if ($ssdarkforce === STYLESHEET_DARKFORCE_LIGHT) {
            $result .= "<img height=13 class='star-unchecked' src='/img/star-unchecked.svg'>";
        } else {
            $result .= "<picture><source srcset='/img/dark-images/star-unchecked.svg' media='(prefers-color-scheme: dark)'>"
                ."<img height=13 class='star-unchecked' src='/img/star-unchecked.svg'></picture>";
        }
    }

    $result .= "</span>";
    return $result;
}

function roundStars($num)
{
    if (isEmpty($num))
        return false;
    else if ($num < 0.25)
        return [0, "0", "0 Stars"];
    else if ($num < 0.75)
        return [0.5, "0h", "&frac12; Stars"];
    else if ($num < 1.25)
        return [1, "1", "1 Star"];
    else if ($num < 1.75)
        return [1.5, "1h", "1&frac12; Stars"];
    else if ($num < 2.25)
        return [2, "2", "2 Stars"];
    else if ($num < 2.75)
        return [2.5, "2h", "2&frac12; Stars"];
    else if ($num < 3.25)
        return [3, "3", "3 Stars"];
    else if ($num < 3.75)
        return [3.5, "3h", "3&frac12; Stars"];
    else if ($num < 4.25)
        return [4, "4", "4 Stars"];
    else if ($num < 4.75)
        return [4.5, "4h", "4&frac12; Stars"];
    else
        return [5, "5", "5 Stars"];
}

// -------------------------------------------------------------------------
// Generate pagination controls.  The URL must already contain a query
// string - if no other query string is needed, simply end it with a "?".
//
// The page numbers are 1-based.  The item numbers are 0-based, since we
// assume that these are row numbers or array indices.
//
function makePageControl($baseUrl, $curPage, $lastPage,
                         $firstItemOnPage, $lastItemOnPage, $totalItems,
                         $showPageList, $showAllButton, $showingAll)
{
    $baseUrl = htmlspecialchars($baseUrl);
    $p = "";

    // if we're currently showing all items, set up a control to revert
    // to a page-by-page view
    if ($showingAll)
        return "Showing All | <a title=\"Show results by page\" "
            . "href=\"$baseUrl&pg=1\">Show by Page</a>";

    // add the 'previous' button, if we're not on the first page
    if ($curPage > 1)
        $p .= "<a title=\"Go to the previous page\" "
              . "href=\"$baseUrl&pg=" . ($curPage-1) . "\">Previous</a> | ";
    else if ($showPageList)
        $p .= "<span class=disabledCtl>Previous</span> | ";

    if ($showPageList) {
        // line up the page buttons - go for 10 buttons total, centered on
        // the current page
        $lo = ($curPage > 4 ? $curPage - 4 : 1);
        $hi = ($lo + 9 < $lastPage ? $lo + 9 : $lastPage);

        // start with an ellipsis if there's more before this
        if ($lo != 1) {
            if ($curPage > 10) {
                $i = $curPage - 10;
                $ttl = "Go back 10 pages";
            } else {
                $i = 1;
                $ttl = "Go to the first page";
            }
            $p .= "<a title=\"$ttl\" href=\"$baseUrl&pg=$i\">&lt;&lt;</a> ";
        }
        else
            $p .= "<span class=disabledCtl>&lt;&lt;</span> ";

        // show the page buttons
        for ($i = $lo ; $i <= $hi ; $i++) {
            if ($i == $curPage)
                $p .= "$i ";
            else
                $p .= "<a title=\"Go to page #$i\" "
                      . "href=\"$baseUrl&pg=$i\">$i</a> ";
        }

        // add an ellipsis if there's more after that
        if ($hi < $lastPage) {
            if ($curPage + 10 < $lastPage) {
                $i = $curPage + 10;
                $ttl = "Skip ahead 10 pages";
            } else {
                $i = $lastPage;
                $ttl = "Go to the last page";
            }
            $p .= "<a title=\"$ttl\" href=\"$baseUrl&pg=$i\">&gt;&gt;</a> ";
        }
        else
            $p .= "<span class=disabledCtl>&gt;&gt;</span> ";

    } else {

        // add the current page/item indicator
        $p .= ($firstItemOnPage+1) . "&ndash;" . ($lastItemOnPage+1)
              . ($totalItems >= 0 ? " of $totalItems" : "");

    }

    // add the 'next' button, if this isn't the last page
    if ($curPage < $lastPage)
        $p .= " | <a title=\"Go to the next page\" "
              . "href=\"$baseUrl&pg=" . ($curPage + 1) . "\">Next</a>";
    else if ($showPageList)
        $p .= " | <span class=disabledCtl>Next</span>";

    // add the 'Show All' button if desired, and if there are more to show
    if ($showAllButton && ($curPage < $lastPage || $curPage > 1))
        $p .= " | <a title=\"Show all results on a single page\" "
              . "href=\"$baseUrl&pg=all\">Show All</a>";

    // return the control
    return $p;
}

// --------------------------------------------------------------------------
// Show a sorting control form: this shows a drop-down list with various
// sort settings.  $dropName is the name of the drop-down control -
// on a change in sorting order, $_REQUEST[$dropName] will give the
// new key in the $sortMap indicating the new sorting order.  Each
// element of $sortMap is keyed on the value to use in the request
// to indicate that sorting order, and contains an array as the
// value; $sortMap[key][1] is the display name of the sorting order.
// Other elements of $sortMap[key] are up to the caller; in most cases,
// $sortMap[key][0] is the SQL ORDER BY clause.  $curSortBy is the
// current value of the sort-by key for the current request.
// $hidden is a map of name=>value pairs for hidden fields that we'll
// add to the form, to transmit any additional needed request parameters.
//
function showSortingControls($formName, $dropName, $sortMap, $curSortBy,
                             $hidden, $url)
{
    static $idSerial = 0;

    // start the form
    echo "<form name=\"$formName\" method=\"get\" action=\"$url\" "
        . "class=\"sortingControls\">";

    // add the drop-down list
    echo "<select name=\"$dropName\">"
        . addEventListener("change", "document.getElementById('sort-go-button-$idSerial').click();");

    // add the option values for the list
    foreach ($sortMap as $key => $val) {
        echo "<option value=\"$key\"";
        if ($key == $curSortBy)
            echo " selected";
        echo ">$val[1]</option>";
    }

    // end the drop-down list
    echo "</select>";

    // add the GO button
    echo " <button id=\"sort-go-button-$idSerial\">Go</button>";

    // consume the serial number
    $idSerial += 1;

    // add the hidden fields
    foreach ($hidden as $id => $val)
        echo "<input type=hidden name=\"$id\" value=\""
            . htmlspecialcharx($val) . "\">";

    // end the form
    echo "</form>";
}

// -------------------------------------------------------------------------
// equivalent of php5 array_fill_keys
function valuesToKeys($arr, $val)
{
    $ret = [];
    foreach ($arr as $v)
        $ret[$v] = $val;
    return $ret;
}

// -------------------------------------------------------------------------
// Put a block of text inside a spoiler protector
//
function spoilerWarning($text, $label = "Spoiler - click to show")
{
    return spoilerWarningOpen($label, true) . $text . spoilerWarningClose(true);
}

function spoilerWarningOpen($label = "Spoiler - click to show", $block = false)
{
    // each spoiler warning needs its own ID number
    static $spoilerNum = 0;

    // set up the text
    $ret = "<span class=\"spoilerButton\" "
           . "id=\"a_spoiler$spoilerNum\">("
           . "<a href=\"#\" data-num='$spoilerNum'>$label</a>)</span>"
           . "<" . ($block ? "div" : "span") . " class=\"hiddenSpoiler\" "
           . "id=\"s_spoiler$spoilerNum\" hidden>";

    // consume the spoiler number
    $spoilerNum++;

    // return the text we generated
    return $ret;
}

function spoilerWarningClose($block = false)
{
    return $block ? "</div>" : "</span>";
}

// Generate the script to handle spoiler buttons.  This should be called
// at a suitable point in the HTML when a spoiler warning is generated.
//
function spoilerWarningScript()
{
    static $didSpoilerScript = 0;
    global $nonce;
    $result = "\n<script type=\"text/javascript\" nonce=\"$nonce\">\n";
    if ($didSpoilerScript++ == 0) {
        $result .= "function showSpoiler(id) { "
            . "document.getElementById(\"a_spoiler\" + id).style.display = "
            . "\"none\";"
            . "var s = document.getElementById(\"s_spoiler\" + id);"
            . "s.hidden = false;"
            . "s.classList.remove(\"hiddenSpoiler\");"
            . "};\n";
    }
    $result .= "document.currentScript.parentElement.querySelectorAll('.spoilerButton a').forEach(function (link) {\n"
            . "  link.addEventListener('click', function (event) {\n"
            . "    event.preventDefault();\n"
            . "    showSpoiler(event.target.dataset.num);\n"
            . "  });"
            . "});"
            . "//-->\n"
            . "</script>";
    return $result;
}

// -------------------------------------------------------------------------
// Fix descriptive text to ensure proper formatting in an HTML page
//
// $specials, if given, is a combination of FixDescXxx flags.
//
define("FixDescSpoiler", 0x0001);
define("FixDescRSS", 0x0002);
define("FixDescIfic", 0x0004);
define("FixDescDemoteHeadings", 0x0008);
function fixDesc($desc, $specials = 0)
{
    $foundSpoiler = false;

    // We haven't yet found any <br>, <br/>, or <p> tags, so assume we'll
    // obey hard newlines in the text, by converting them to <br> tags.
    // As we scan, we'll change this to " " if we encounter any of those
    // explicit line control tags.
    $nlSub = "<br>";
    $nlSubIfic = "<br/>";

    // whatever happens, strip newlines from the beginning and and
    for ($i = 0 ;
         $i < strlen($desc) && strpos("\n\r", $desc[$i]) !== false ;
         $i++) ;
    for ($j = strlen($desc) ;
         $j > 0 && strpos("\n\r", $desc[$j-1]) !== false ;
         $j--) ;

    $desc = substr($desc, $i, $j - $i);

    // allowed tag list - we keep these tags as-is
    $allowedTags = valuesToKeys(
        ['p', 'br',
            'i', 'b', 'u', 'strong', 'em', 'hr',
            'big', 'small', 'tt', 'sup', 'sub',
            'cite', 'blockquote', 'code', 'pre',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'dl', 'dt', 'dd'], 1);

    // tags that trigger explicit line control mode
    $lineTags = valuesToKeys(['p', 'br'], 1);

    // non-stacking tags - these are either tags that aren't containers
    // (such as <br>), or that are containers but don't need to be
    // explicitly closed because they'll be closed implicitly by other
    // tags (e.g., <li> is implicitly closed by another <li> or by
    // the closing </ol> or </ul>)
    $nonStackingTags = valuesToKeys(['br', 'li', '-spoiler'], 1);

    // reformat for allowed tags and markup-specific characters
    $inAnchor = false;
    $tagStack = [];
    $tagSp = -1;
    for ($ofs = 0 ; $ofs < strlen($desc) ; $ofs++) {
        // check what we're looking at
        switch ($desc[$ofs]) {
        case '<':
            // presume we won't need to quote it
            $quoteIt = false;

            // find the next '>'
            $gt = strpos($desc, '>', $ofs);
            if ($gt !== false) {
                // remember where the tag starts
                $tagOfs = $ofs;

                // note the length of the full tag from < to >
                $tagLen = $gt + 1 - $ofs;

                // pull out the tag name
                $tagName = trim(substr($desc, $ofs + 1, $gt - $ofs - 1));

                // presume we'll need to check for a close tag
                $checkForClose = true;

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
                if ($specials & FixDescIfic) {

                    // Simply strip out all HTML *except* for <p> tags.
                    // Turn <p> into <br/>.  Also turn <br><br> or just
                    // <br> into <p>.
                    if ($tagName == 'p' || $tagName == 'br')
                    {
                        // turn it into <br/>
                        $desc = substr_replace(
                            $desc, "<br/>", $tagOfs, $gt - $tagOfs + 1);
                        $ofs = $tagOfs + 4;

                        // remove an immediately adjacent <br>
                        if (preg_match("/\s*<\s*br\s*\/?\s*>/i", $desc,
                                       $match, PREG_OFFSET_CAPTURE, $ofs + 1)
                            && $match[0][1] == $ofs + 1)
                        {
                            $desc = substr_replace(
                                $desc, "", $ofs, strlen($match[0][0]));
                        }
                    }
                    else if ($tagName == 'spoiler' && !$isClose)
                    {
                        // turn this into " [spoilers] "
                        $desc = substr_replace(
                            $desc, " [Spoilers] ", $tagOfs, $gt - $tagOfs + 1);
                        $ofs = $tagOfs + 11;
                    }
                    else
                    {
                        // strip it out entirely
                        $desc = substr_replace(
                            $desc, "", $tagOfs, $gt - $tagOfs + 1);
                        $ofs = $tagOfs - 1;
                    }

                    // We've either removed the tag entirely or replaced it
                    // with a self-closing tag.  In either case, we don't
                    // need to check for a matching close tag for this tag.
                    $checkForClose = false;

                } else if ($tagName == 'br' && $isAutoClose) {

                    // it's an iFiction-style <br/> paragraph break - convert
                    // it to <p> and note that we have explicit line control
                    $desc = substr_replace($desc, "<p>", $ofs, $tagLen);
                    $nlSub = "\n";
                    $nlSubIfic = "\n";
                    $ofs += 2;

                } else if ($tagName == 'spoiler') {

                    // If <spoiler> tags are allowed, fix it up; otherwise
                    // remove the tag and its contents entirely.  In RSS
                    // mode, substitute "(spoilers)" without any JavaScript.
                    if (($specials & FixDescSpoiler) != 0
                        && ($specials & FixDescRSS) == 0) {

                        // start or end of the spoiler span as appropriate
                        if ($isClose) {
                            // close tag - end the span
                            for ($k = $tagSp ; $k >= 0 && !isSpoilerTag($tagStack[$k]) ; $k--) ;
                            if ($k >= 0) {
                                $tagName = $tagStack[$k];
                                $repl = spoilerWarningClose($tagName == 'spoilerdiv');
                            } else {
                                // no open spoiler - drop the stray close tag
                                $tagName = '-spoiler';
                                $repl = "";
                            }
                        } else {
                            // open tag - add the link and start the span,
                            // or a div if the spoiler holds block content
                            $end = findEndTag($desc, "spoiler", $gt);
                            $content = substr($desc, $gt + 1, $end ? $end[0] - $gt - 1 : null);
                            if (preg_match('/<\s*(p|ul|ol|li|blockquote|h[1-6]|pre|dl|dt|dd|hr)\b|<\s*br\b[^>]*\//i', $content))
                                $tagName = 'spoilerdiv';
                            $repl = spoilerWarningOpen(block: $tagName == 'spoilerdiv');
                            $foundSpoiler = true;
                        }

                        // apply the substitution
                        $desc = substr_replace($desc, $repl, $tagOfs, $tagLen);
                        $tagLen = strlen($repl);
                        $ofs = $gt = $tagOfs + $tagLen - 1;

                    } else {

                        // spoilers aren't allowed, or we're in RSS mode -
                        // scan for the end tag
                        if ($isClose) {
                            $ofs2 = $gt + 1;
                        } else {
                            $ofs2 = findEndTag($desc, "spoiler", $gt);

                            // if we didn't find it, delete the whole rest of
                            // the string
                            $ofs2 = ($ofs2 == false ? strlen($desc) : $ofs2[1]);
                        }

                        // Remove everything from the start to end tag.  In
                        // RSS mode, substitute "(spoilers)".
                        $repl = (($specials & FixDescRSS) != 0 && !$isClose
                                 ? "[spoilers]" : "");

                        // make the substitution
                        $desc = substr_replace(
                            $desc, $repl, $tagOfs, $ofs2 - $tagOfs);

                        // back up to reconsider the current character
                        $ofs = $tagOfs + strlen($repl) - 1;

                        // set a fake non-stacking tag name
                        $tagName = '-spoiler';
                    }

                } else if (isset($allowedTags[$tagName])) {

                    // If we're demoting headings, convert <h1> to <h4>, <h2> to <h5> and so on
                    $outName = $tagName;
                    if (($specials & FixDescDemoteHeadings) && preg_match("/^h(\d)$/", $tagName, $matches)) {
                        $outName = "h" . min(6, ((int)$matches[1]) + 3);
                    }

                    // keep the tag, rewriting it unless it's already clean
                    $repl = "<" . ($isClose ? "/" : "") . $outName
                        . ($isClose ? "" : fixDescTagAttrs($tagName, $tagAttr))
                        . ($isAutoClose ? "/" : "") . ">";
                    if (strcasecmp($repl, substr($desc, $tagOfs, $tagLen)) != 0) {
                        $desc = substr_replace($desc, $repl, $tagOfs, $tagLen);
                        $tagLen = strlen($repl);
                        $gt = $tagOfs + $tagLen - 1;
                        $tagName = $outName;
                    }
                    $ofs = $gt;

                    // if it's a line tag, switch to explicit line control
                    // mode - this means that we don't replace hard newlines
                    // in the source text with <br>'s
                    if (isset($lineTags[$tagName])) {
                        $nlSub = "\n";
                        $nlSubIfic = "\n";
                    }

                } else if ($tagName == 'a') {

                    // valid HREF links: allow properly quoted links to
                    // http://, ftp:// and news: URLs
                    $upat = "(https?:\/\/|ftp:\/\/|news:)";
                    $upatSq = "/^href='({$upat}[^']+)'$/i";
                    $upatDq = "/^href=\"({$upat}[^\"]+)\"$/i";

                    // anchor
                    if ($isClose) {
                        // if we're in an open anchor we kept, allow it
                        if ($inAnchor) {
                            $ofs = $gt;
                            $inAnchor = false;
                        }
                        else
                            $quoteIt = true;
                    } else {

                        // keep our internal references of the form
                        // game="tuid"; also allow limited href="..." links
                        if (preg_match("/^game=([\"'])([a-z0-9]+)\\1$/i",
                                       $tagAttr, $match, 0, 0)) {

                            // it's an internal game reference - keep it
                            $desc = substr_replace(
                                $desc, "<a href=\"viewgame?id={$match[2]}\">",
                                $ofs, $tagLen);

                            $ofs = strpos($desc, ">", $ofs);

                            // note that we're in a retained <a>
                            $inAnchor = true;

                        } else if (preg_match($upatSq, $tagAttr, $match, 0, 0)
                                   || preg_match($upatDq, $tagAttr, $match, 0, 0)) {

                            // it's a regular <a href> - allow it
                            $ofs = $gt;

                            // note that we're in a retained <a>
                            $inAnchor = true;

                        } else {
                            // note a valid reference - quote it
                            $quoteIt = true;
                        }
                    }
                } else {
                    // it's not an allowed tag - quote the '<'
                    $quoteIt = true;
                }

                // if we're not quoting it, and it's a stacking tag,
                // manage the tag stack
                if ($checkForClose
                    && !$quoteIt
                    && !$isAutoClose
                    && !isset($nonStackingTags[$tagName]))
                {
                    // handle close or open as applicable
                    if ($isClose) {
                        // If it's in the stack, close back to it.  Assume
                        // that the close tags for any nested tags were
                        // either forgotten or are implied, so explicitly
                        // insert them.
                        //
                        // If it's NOT in the stack, assume that this is
                        // a typo that was meant to close the current
                        // open tag.
                        if (in_array($tagName, $tagStack)) {
                            // close tags until we match up with the open
                            while ($tagSp >= 0) {
                                // pop the current tag
                                $curTag = $tagStack[$tagSp--];

                                // if it's our matching tag, we're done
                                if ($tagStack[$tagSp+1] == $tagName)
                                    break;

                                // If it's SPOILER, never close implicitly.
                                // Simply assume the current close tag is
                                // an error, and delete it.
                                if (isSpoilerTag($curTag))
                                {
                                    // delete the errant close tag
                                    $desc = substr_replace(
                                        $desc, "", $tagOfs, $tagLen);

                                    $ofs -= $tagLen;
                                    $gt -= $tagLen;

                                    // re-stack the popped close tag
                                    $tagStack[++$tagSp] = $curTag;
                                    break;
                                }

                                // no match, so insert the implied close
                                $curTag = closeTagForStackedTag($curTag);
                                $desc = substr_replace(
                                    $desc, "</$curTag>", $tagOfs, 0);

                                // move past it
                                $len = strlen($curTag) + 3;
                                $tagOfs += $len;
                                $ofs += $len;
                                $gt += $len;
                            }
                        } else if ($tagSp >= 0 && isSpoilerTag($tagStack[$tagSp])) {
                            // never let a typo close a spoiler
                            $desc = substr_replace($desc, "", $tagOfs, $tagLen);
                            $ofs = $tagOfs - 1;
                        } else if ($tagSp >= 0) {
                            // it's not in the stack, so assume it's a typo:
                            // delete this tag and replace it with the
                            // correct close tag from the stack
                            $curTag = $tagStack[$tagSp--];
                            $curTag = closeTagForStackedTag($curTag);
                            $desc = substr_replace(
                                $desc, "</$curTag>", $tagOfs, $tagLen);

                            // move past it
                            $len = strlen($curTag) + 3;
                            $tagOfs += $len - $tagLen;
                            $ofs += $len - $tagLen;
                            $gt += $len - $tagLen;
                        }
                    } else {
                        // it's an open tag - stack it
                        $tagStack[++$tagSp] = $tagName;
                    }
                }
            } else {
                // no '>', so this isn't a tag - quote the '<'
                $quoteIt = true;
            }

            // if we decided to quote it, do so
            if ($quoteIt)
                $desc = substr_replace($desc, "&lt;", $ofs, 1);

            // done with the '<'
            break;

        case '>':
            // convert these to "&gt"
            $desc = substr_replace($desc, "&gt;", $ofs, 1);
            break;

        case '&':
            // if it's &lt;, &gt;, &quot; or &amp;, leave it;
            // otherwise convert the & to &amp;
            $therest = substr($desc, $ofs + 1, 5);
            if (strncasecmp($therest, "lt;", 3) == 0
                || strncasecmp($therest, "gt;", 3) == 0) {
                $ofs += 3;
            } else if (strncasecmp($therest, "amp;", 4) == 0) {
                $ofs += 4;
            } else if (strncasecmp($therest, "quot;", 5) == 0) {
                $ofs += 5;
            } else {
                // not recognized - make it an explicit &amp;
                $desc = substr_replace($desc, "&amp;", $ofs, 1);
                $ofs += 4;
            }
            break;

        case '"':
            // convert to &quot;
            $desc = substr_replace($desc, "&quot;", $ofs, 1);
            break;

        case "'":
            // convert to &#039;
            $desc = substr_replace($desc, "&#039;", $ofs, 1);
            break;

        case "\n":
            // leave these alone for now - we'll fix them up shortly...
            break;

        default:
            // remove all other control characters
            if ($desc[$ofs] < ' ')
                $desc[$ofs] = ' ';
        }
    }

    // close all tags left open
    for ( ; $tagSp >= 0 ; $tagSp--) {
        // get the tag, translating stacked tags to actual close tags
        $tag = closeTagForStackedTag($tagStack[$tagSp]);

        // append the close tag to the result
        $desc .= "</$tag>";
    }

    // Change hard newlines to whatever we decided upon - if we found
    // any explicit line control tags, we'll consider hard newlines
    // to be simply whitespace for the sake of formatting the source
    // text, so we'll change them to spaces; otherwise we'll obey
    // them by changing them to <br> tags.  For iFiction records,
    // treat single line breaks as spaces, and turn double line breaks
    // into <br/> paragraph break markups.
    if ($specials & FixDescIfic) {
        $desc = preg_replace("/\n\s*\n/", $nlSubIfic, $desc);
        $desc = str_replace("\n", " ", $desc);
    } else {
        $desc = str_replace("\n", $nlSub, $desc);
    }

    // if we found a spoiler tag, add the necessary script if we haven't
    // already done so
    if ($foundSpoiler)
        $desc .= spoilerWarningScript();

    // return the result
    return $desc;
}

// Get the attributes fixDesc keeps for an allowed tag
//
function fixDescTagAttrs($tagName, $attrs)
{
    if ($tagName == 'ol' && $attrs !== false
        && preg_match("/^start\s*=\s*([\"']?)(\d{1,9})\\1$/i", $attrs, $match))
        return " start=\"{$match[2]}\"";

    return "";
}

function isSpoilerTag($tag)
{
    return $tag == 'spoiler' || $tag == 'spoilerdiv';
}

// Translate a stacked close tag to the actual HTML close tag
//
function closeTagForStackedTag($tag)
{
    switch ($tag)
    {
    case "spoiler":
        return "span";

    case "spoilerdiv":
        return "div";

    default:
        return $tag;
    }
}

function parsedownMultilineText($text) {
    // parsedown->text converts "1st line \n 2nd line" to "<p>1st line <br /> 2nd line</p>"
    // that's unfortunate, because fixDesc has a special rule for `<br/>`.
    // We assume that `<br/>` is an iFiction *paragraph* break
    // We *do* have real `<br/>` iFiction paragraph breaks in the DB, which need to be rendered as
    // paragraph breaks.
    // So, we start by replacing all instances of `<br/>` with </p><p>, then run
    // parsedown, which may generate some new `<br />` tokens. We'll replace those with `<br>`
    // (so fixDesc won't turn them into paragraph breaks)

    // example:
    // in: "1st line \n 2nd line <br /> 3rd line" -> "<p>1st line<br>\n2nd line <p> 3rd line</p>"
    //   (fixDesc will leave that line alone)

    // pre-process iFiction-style <br/> paragraph breaks
    $replaced_ific_br = preg_replace("/<br *\\/>/", "</p><p>", $text);
    $parsedown = new Parsedown();
    $parsedown->setBreaksEnabled(true);
    $markdown_converted = $parsedown->text($replaced_ific_br);
    $output = preg_replace("/<br \\/>/", "<br>", $markdown_converted);
    return $output;
}

// --------------------------------------------------------------------------
// Find an end tag starting from a given position.  Returns an array:
// [offset of '<' of close tag, offset of next character after '>' of close].
// If we don't find the tag at all, returns false.
//
function findEndTag($str, $tag, $ofs)
{
    // lower-case the tag for comparisons
    $tag = strtolower($tag);

    // we're not nested yet
    $nesting = 0;

    // scan from the given offset
    for ($len = strlen($str) ; $ofs < $len ; $ofs++)
    {
        if ($str[$ofs] == '<') {
            // find the matching '>'
            $gt = strpos($str, '>', $ofs);

            // if we found it, we have a tag
            if ($gt !== false) {
                // pull out the tag
                $curTag = trim(substr($str, $ofs + 1, $gt - $ofs - 1));

                // check for a close tag
                $isClose = false;
                if (substr($curTag, 0, 1) == '/') {
                    $isClose = true;
                    $curTag = trim(substr($curTag, 1));
                }

                // check for auto-closing tags
                $isAutoClose = false;
                if (substr($curTag, -1, 1) == '/') {
                    $isAutoClose = true;
                    $curTag = trim(substr($curTag, 0, -1));
                }

                // if we have parameters, drop them
                $sp = strpos($curTag, ' ');
                if ($sp !== false)
                    $curTag = substr($curTag, 0, $sp);

                // lower-case the tag name
                $curTag = strtolower($curTag);

                // if this is our target tag, process it
                if ($curTag == $tag) {
                    if ($isAutoClose) {
                        // it's an auto-closing tag, so it adds and removes
                        // a nesting level - just ignore it
                    } else if ($isClose) {
                        // it's a close tag - drop a nesting level; if our
                        // nesting level is already zero, we're done
                        if ($nesting-- == 0)
                            return [$ofs, $gt + 1];
                    } else {
                        // it's an open tag - add a nesting level
                        $nesting++;
                    }
                }
            }
        }
    }

    // didn't find the tag
    return false;
}

// --------------------------------------------------------------------------
// Show a summary of an HTML string, limiting it to the given
// character length.
function summarizeHtml($str, $maxlen)
{
    // leave room for our "..." suffix
    if ($maxlen > 3)
        $maxlen -= 3;

    // change any embedded newlines to spaces
    $str = preg_replace("/[\n\r]/", " ", $str);

    // remove any leading line breaks
    $str = preg_replace("/^(\s*<\s*\/?\s*(p|br)\s*\/?\s*>)+/i", " ", $str);

    // trim off leading whitespace
    $str = preg_replace("/^\s+/", "", $str);

    // run through the string, up to the given length; don't count
    // characters within markup sequences
    for ($ofs = 0, $outlen = 0, $inTag = false, $inQu = false,
         $inEnt = false, $lastBrk = 0 ;
         $ofs < strlen($str) ; $ofs++)
    {
        // get the current character
        $c = $str[$ofs];

        // process the tag or ordinary text, as appropriate
        if ($inEnt)
        {
            // the entity ends at the ';'
            if ($c == ';')
                $inEnt = false;
        }
        else if ($inQu)
        {
            // check for leaving the quoted section
            if ($c == $inQu)
                $inQu = false;
        }
        else if ($inTag)
        {
            // check to see if we're leaving the tag or entering a quoted part
            if ($c == '>')
                $inTag = false;
            else if ($c == '"' || $c == '\'')
                $inQu = $c;
        }
        else
        {
            // presume it's an ordinary character
            $ordinary = true;

            // not in a tag - check for starting one
            if ($c == '<')
            {
                // we're now in a tag
                $inTag = true;

                // this is not an ordinary character after all
                $ordinary = false;

                // if we've reached a line break, stop here
                $nxt = substr($str, $ofs + 1, 10);
                if (preg_match("/^\s*(p|br)\s*\/?\s*>/i", $nxt))
                {
                    // stop here and return what we have so far
                    return [substr($str, 0, $ofs) . "...",
                        $outlen + 3, true];
                }
            }
            else if ($c == '&')
            {
                // if it's &lt;, &gt;, &quot; or &amp;, it's an entity
                $entTxt = substr($str, $ofs + 1, 5);
                if (strncasecmp($entTxt, "lt;", 3) == 0
                    || strncasecmp($entTxt, "gt;", 3) == 0
                    || strncasecmp($entTxt, "amp;", 4) == 0
                    || strncasecmp($entTxt, "quot;", 5) == 0) {

                    // it's an entity markup, not an ordinary character
                    $inEnt = true;
                    $ordinary = false;

                    // entity markups count as one character of output
                    $outlen++;
                }
            }

            // if it's an ordinary character, process it
            if ($ordinary)
            {
                // if we're at a space, note it as a possible break point;
                if ($c == ' ')
                {
                    // only count it if there's non-whitespace before it
                    if ($outlen > 0)
                    {
                        // count it in the output
                        $outlen++;

                        // it's a possible breakpoint
                        $lastBrk = $ofs;
                    }

                    // skip consecutive spaces - they don't count as
                    // separate output length, since HTML collapses
                    // source-text whitespace on display
                    while ($ofs+1 < strlen($str) && $str[$ofs+1] == ' ')
                        $ofs++;
                }
                else
                {
                    //  it's not a space, so it counts in the output
                    $outlen++;
                }

                // if this pushes us over the limit, and we've found
                // a breakpoint, return the part up to the breakpoint
                if ($outlen > $maxlen && $lastBrk)
                {
                    // return the string truncated up to the last breakpoint
                    return [substr($str, 0, $lastBrk) . "...",
                        $outlen + 3, true];
                }
            }
        }
    }

    // didn't find a truncation point - return the whole string
    return [$str, $outlen, false];
}


// --------------------------------------------------------------------------
//
// Determine if a value is "empty" - null, false, or an empty string
//
function isEmpty($val) {
    return is_null($val) || $val == false || $val == "";
}

// --------------------------------------------------------------------------
//
// Upper-case the first character of a string
//
function initCap($str)
{
    return strtoupper(substr($str, 0, 1)) . substr($str, 1);
}

// --------------------------------------------------------------------------
//
// Don't allow new users who haven't been approved yet to edit pages
//
function check_editing_privileges($db)
{
    // make sure we're logged in
    $curuser = current_user_id();
    if (!$curuser)
    {
        redirect_to_login();
        return false;
    }

    // look up the user's account status in the database
    if (!($result = mysql_query("select acctstatus, sandbox from users where id='$curuser'", $db))
        || mysql_num_rows($result) == 0)
    {
        redirect_to_login();
        return false;
    }
    [$acctstatus, $sandbox] = mysql_fetch_row($result);

    // don't allow editing if the user has been flagged as a troll
    if ($sandbox == 1) // troll sandbox
    {
        pageHeader("Service Unavailable");
        echo "This service is currently unavailable. We apologize for the inconvenience."
            . "<p>(Diagnostic information: code TCE0916)\n";
        pageFooter();
        return false;
    }

    // check the current account status
    switch($acctstatus)
    {
    case 'A':
        // active account - editing approved
        break;

    case 'B':
    case 'X':
        // banned/closed
        pageHeader("Account closed");
        echo "Your user account has been closed. "
            . "Editing is not available with this account. ";
        pageFooter();
        return false;

    case 'D':
        // pending activation
        pageHeader("Pending activation");
        echo "Your user account has not yet been activated. "
            . "You must complete the activation process before you can use this account for editing. ";
        pageFooter();
        return false;

    default:
        // other
        pageHeader("Editing not available");
        echo "Editing is not available with this account. ";
        pageFooter();
        return false;
    }

    // no objections found
    return true;
}


// --------------------------------------------------------------------------
//
// Get the view to use to select game ratings for the current user.
// We have the following views:
//
//    gameRatingsSandbox0   - only ratings from users in sandbox 0 (all public)
//    gameRatingsSandbox01  - ratings for users in sandboxes 0 AND 1 (trolls)
//
// The idea is that we only want to see statistics for users who are visible
// through the sandboxing mechanism.  Users in sandbox 1 (trolls) are hidden
// from everyone else, so normal users in sandbox 0 shouldn't see them.  The
// xxxSandbox0 view accomplishes that.  However, we do want the trolls to see
// themselves and other trolls, so for them we use xxxSandbox01, which includes
// the statistics from normal users as well as sandbox 1 users.  Currently,
// these are the only two sandboxes used.
//
function getGameRatingsView($db)
{
    // assume sandbox 0
    $sandbox = 0;

    // if the user is logged in, look up their sandbox
    $curuser = current_user_id();
    if ($curuser) {
        $result = mysql_query("select sandbox from users where id='$curuser'", $db);
        [$sandbox] = mysql_fetch_row($result);
    }

    // figure the table based on the user's sandbox
    switch ($sandbox)
    {
    default:
    case 0:
        // normal user - show only reviews and ratings from other normal users
        return "gameRatingsSandbox0_mv";

    case 1:
        // troll - show ratings from normal users plus trolls
        return "gameRatingsSandbox01";
    }
}

class TransactionAborted extends RuntimeException {}

// Runs $fn($db) in a transaction, committing if it returns a truthy value
// and rolling back otherwise.  A nested call uses a savepoint, so its
// rollback undoes only its own work, and its commit waits for the outer
// transaction; if the whole transaction is lost inside a nested call, the
// outermost call rolls back.  Returns $fn's result, or false if the
// transaction fails, setting $error to the database error, if any.
function with_transaction($db, $fn, &$error = null)
{
    static $states = null;
    $states ??= new WeakMap();
    $state = $states[$db] ??= (object)['depth' => 0, 'error' => null];
    $depth = $state->depth;
    $savepoint = "with_transaction_$depth";

    $error = null;
    if (!($depth
            ? mysqli_savepoint($db, $savepoint)
            : mysqli_begin_transaction($db))) {
        $error = mysqli_error($db);
        if ($depth)
            throw new TransactionAborted($error);
        return false;
    }

    $state->depth++;
    try {
        $result = $fn($db);
        if ($result && ($depth
                ? mysqli_release_savepoint($db, $savepoint)
                : mysqli_commit($db)))
            return $result;

        $error = mysqli_error($db) ?: $state->error;
        if (!$depth) {
            mysqli_rollback($db);
            return false;
        }
        $state->error = $error;
        if (!mysqli_query($db, "rollback to savepoint $savepoint"))
            throw new TransactionAborted($error);
        return false;
    } catch (TransactionAborted $e) {
        if ($depth)
            throw $e;
        mysqli_rollback($db);
        $error = $e->getMessage();
        return false;
    } catch (Throwable $e) {
        if ($depth)
            mysqli_query($db, "rollback to savepoint $savepoint");
        else
            mysqli_rollback($db);
        throw $e;
    } finally {
        $state->depth = $depth;
        if (!$depth)
            $state->error = null;
    }
}

// This sets the isolation level, so it can't run inside a transaction.
function regenerateMaterializedView($db, $mv, $view)
{
    if (!mysqli_execute_query($db,
            "set transaction isolation level read committed")) {
        error_log("regenerating $mv failed: " . mysqli_error($db));
        return false;
    }
    $result = with_transaction($db, fn($db) =>
        mysqli_execute_query($db, "delete from $mv")
        && mysqli_execute_query($db, "insert into $mv select *, now() from $view
                                      on duplicate key update updated = updated"),
        $error);
    if (!$result)
        error_log("regenerating $mv failed: $error");
    return $result;
}

// --------------------------------------------------------------------------
//
// Calculate a user's Frequent Fiction score
//
function userScore($uid)
{
    $db = dbConnect();
    $quid = mysql_real_escape_string($uid, $db);

    // determine the requested user's score
    $result = mysql_query(
        "select score, rankingScore, reviewCount
         from userScores_mv
         where userid = '$quid'", $db);
    if (mysql_num_rows($result) == 1)
        [$score, $rscore, $reviewCount] = mysql_fetch_row($result);
    else
        $score = $rscore = $reviewCount = 0;

    // Determine the user's rank: this is simply the number of users
    // with higher ranking scores, plus 1.
    $result = mysql_query(
        "select count(userid) as rank
         from userScores_mv
         where rankingScore > $rscore", $db);
    $rank = mysql_result($result, 0, "rank") + 1;

    // Figure their Top-N rank status.  A user who hasn't written any
    // reviews doesn't get a rank regardless of their score.
    if ($reviewCount == 0)
        $rankName = false;
    else if ($rank <= 10)
        $rankName = "Top 10 Reviewer";
    else if ($rank <= 25)
        $rankName = "Top 25 Reviewer";
    else if ($rank <= 50)
        $rankName = "Top 50 Reviewer";
    else if ($rank <= 100)
        $rankName = "Top 100 Reviewer";
    else
        $rankName = false;

    // return the score and rank
    return [$score, $rank, $rankName];
}

//
// Get a table of the top N reviewers.  This returns a lookup table indexed
// user ID.  Each entry gives an array [score, badge], where 'badge' is
// the "Top 50 Reviewer" (etc) status badge text.
//
function getUserScores($db, $n)
{
    // get the top reviewers
    $arr = getTopReviewers($db, $n);

    // build a lookup table by user ID
    $tab = [];
    for ($i = 0 ; $i < count($arr) ; $i++) {

        // decompose this record
        $rec = $arr[$i];
        [$uid, $uanme, $score] = $rec;
        $n = ($i < 10 ? 10 : ($i < 25 ? 25 : ($i < 50 ? 50 : ($i < 100 ? 100 : 0))));
        $badge = ($n ? "Top $n Reviewer" : false);

        $tab[$uid] = [$score, $badge];
    }

    // return the table
    return $tab;
}

//
// Get an array of the top N reviewers, sorted in descending rank order.
//
function getTopReviewers($db, $n)
{
    // Get the top N, sorted in descending rank order.  Only count
    // people who have actually written reviews.
    $result = mysql_query(
        "select
           s.userid, u.name, s.score
           from userScores_mv as s join users as u on u.id = s.userid
           where s.reviewCount > 0
           order by s.score desc
           limit 0, $n", $db);

    // fetch the rows
    for ($i = 0, $nrows = mysql_num_rows($result), $arr = [] ;
         $i < $n && $i < $nrows ; ++$i)
        $arr[] = mysql_fetch_row($result);

    // return the user list
    return $arr;
}

// --------------------------------------------------------------------------
// Personal name list splitter.  This analyzes a possible list of personal
// names (such as an author name list), and breaks it up into an array
// of individual names.
//
// We look for the separators ",", "and", and ", and".  However, we
// don't treat "," as a separator if it's followed by a single word
// at the end of the whole string, or by a single word followed by
// another comma or "and" - this prevents treating suffixes such as
// Jr., Sr., III, DDS, PhD, etc. as separate names.
//
// We return the list of names as an array.
//
function splitPersonalNameList($name)
{
    // split the list wherever there's a ",", "and", or ",and" - but to
    // prevent splitting suffixes (Jr, Sr, PhD, etc) as separate names,
    // only treat "," as a delimiter if it's followed by two or more
    // words, the second of which can't be "and"
    return preg_split(
//        "/(\s*\band\s+|,\s*and\s+|,\s*(?=[\w.]+\s+(?!and)[\w.]+))/i", $name,
        "/(\s*\band\s+|,\s*and\s+|,\s*(?=[^\s,;]+\s+(?!and)[^\s,;]+))/i", $name,
        -1, PREG_SPLIT_NO_EMPTY);
}

// Put a personal name into sorting form.  This takes a name of the
// form "First Middle... Last" and coverts it to "Last, First Middle...".
// If there's a ",", we consider what follows to be a suffix that's
// part of the last name: "Bob Smith, Jr." -> "Smith, Jr., Bob".
//
function getSortingPersonalName($name)
{
    // strip parentheses
    // rationale: some games have author set to: "Real Name (as Pseudonym)"
    //            clicking on their name will launch a search, we want the
    //            search results to include results for "Real Name"
    $name = preg_replace("/\([^)]+\)/","",$name);

    // split it on spaces that aren't preceded by commas
    $names = preg_split("/(?<!,)\s+/", $name, -1, PREG_SPLIT_NO_EMPTY);
    $c = count($names);

    // if our last name looks like a roman numeral suffix, combine it with
    // the previous element
    if ($c >= 2
        && preg_match("/^(II|III|IV|V|VI|VII|VIII|IX)(?![.])\b/",
                      $names[$c-1])) {

        // looks like a roman numeral suffix - combine the last two elements
        $names[$c-2] .= " " . $names[$c-1];
        unset($names[$c-1]);
        $c -= 1;
    }

    // if there's only one name, just return the original
    if ($c < 2)
        return $name;

    // move the last element into first position, adding a comma
    $names = array_merge([$names[$c-1] . ","],
                         array_slice($names, 0, $c-1));

    // put the name string back together and return the result
    return implode(" ", $names);
}

// Get the sorting form of a personal name list.
//
function getSortingPersonalNameList($name)
{
    // split the list into an array
    $names = splitPersonalNameList($name);

    // put all of the names into sorting format
    $names = array_map("getSortingPersonalName", $names);

    // reassemble the list
    return implode("; ", $names);
}

// --------------------------------------------------------------------------
// Convert a title to sorting format.  This removes any "a", "an", or "the"
// from the beginning of the name and moves it to the end: "The Title"
// becomes "Title, The".
//
function getSortingTitle($title)
{
    return preg_replace("/^(a|an|the)\b\s*(.*)$/i", "\\2, \\1", $title, 1);
}

// --------------------------------------------------------------------------
// Store a picture.  This randomly chooses one of the picture databases and
// stores the image.  The ID is the database number plus the table key.
// Takes the image binary data as input, returns the ID.
//
function store_image($imageData, $imageFormat,
                     $copyrightStat, $copyrightMsg, $uid)
{
    // choose randomly from one of the secondary databases
    $dbnum = rand(0, 4);
    $db = imageDbConnect($dbnum);
    if (!$db) {
        return false;
    }

    // store the image
    $imageData = mysql_real_escape_string($imageData, $db);
    $imageFormat = mysql_real_escape_string($imageFormat, $db);
    $copyrightMsg = mysql_real_escape_string($copyrightMsg, $db);
    $copyrightStat = mysql_real_escape_string($copyrightStat, $db);
    $uid = mysql_real_escape_string($uid, $db);
    $result = mysql_query(
        "insert into images
          (img, format, copystat, copyright, userid, createdate)
          values ('$imageData', '$imageFormat', '$copyrightStat',
                 '$copyrightMsg', '$uid', now())", $db);

    // get the key from the insert - this is the base ID of the image
    $key = mysql_insert_id($db);

    // if we were successful, return the image ID; otherwise return null
    return ($result ? "$dbnum:$key" : false);
}

// --------------------------------------------------------------------------
// Tentatively store an image.  This creates an image and stores it as
// normal, but adds the image to a rollback list.  If commit_image() is
// called on the image before the request completes, the image is
// permanently stored.  Otherwise, at the end of the request, we delete
// the image.  This allows a caller to create an image as part of a
// transaction before determining if the transaction will succeed.  If
// the transaction fails for some reason, the caller doesn't need to
// do anything; the image will be automatically discarded.
//
$tentativeImageList = false;
function tentative_store_image($imageData, $imageFormat,
                               $copyrightStat, $copyrightMsg, $uid)
{
    global $tentativeImageList;

    // if we haven't already set up for tentative images, do so now
    if (!$tentativeImageList) {
        // set up our table of uncommitted images
        $tentativeImageList = [];

        // register our callback
        register_shutdown_function("tentative_image_shutdown");
    }

    // store the image
    $id = store_image($imageData, $imageFormat,
                      $copyrightStat, $copyrightMsg, $uid);

    // add it to the tentative image list
    $tentativeImageList[$id] = true;

    // return the ID
    return $id;
}

// commit a tentative image
function commit_image($imageID)
{
    global $tentativeImageList;

    // remove the image ID from the tentative image list
    unset($tentativeImageList[$imageID]);
}

// shutdown callback - discard any uncommitted tentative images
function tentative_image_shutdown()
{
    global $tentativeImageList;

    foreach ($tentativeImageList as $id=>$v)
        delete_image($id);
}

// --------------------------------------------------------------------------
// update an image's copyright status
function update_image_copyright($id, $userid, $date,
                                $copyStat, $copyMsg, &$errMsg)
{
    global $copyrightStatList;

    // parse the image ID:  <dbnum>:<image key>
    [$dbnum, $key] = explode(":", $id);

    // connect to the database encoded in the ID
    $db = imageDbConnect((int)$dbnum);
    if (!$db) {
        $errMsg = "Unable to connect to database.";
        return false;
    }

    // validate the status
    if ($copyStat !== false && !isset($copyrightStatList[$copyStat])) {
        $errMsg = "The specified copyright status code is invalid.";
        return false;
    }

    // generate the SET clause
    $vars = [];
    if ($userid !== false)
        $vars[] = "userid = " . sqlStringOrNull($userid, $db);
    if ($date !== false)
        $vars[] = "createdate = " . sqlStringOrNull($date, $db);
    if ($copyStat !== false)
        $vars[] = "copystat = '" . mysql_real_escape_string($copyStat, $db). "'";
    if ($copyMsg !== false)
        $vars[] = "copyright = " . sqlStringOrNull($copyMsg, $db);

    if (count($vars) == 0) {
        $errMsg = "No changes were specified.";
        return false;
    }

    // update the row
    $key = mysql_real_escape_string($key, $db);
    $vars = implode(",", $vars);
    $result = mysql_query(
        "update images set $vars where id='$key'", $db);
    if (!$result)
        $errMsg = "An error occurred updating the copyright settings "
                  . "in the database.";
    return $result;
}

// return the given value as a quoted sql string, or as NULL if it's empty
function sqlStringOrNull($val, $db)
{
    if ($val != "")
        return "'" . mysql_real_escape_string($val, $db) . "'";
    else
        return "null";
}


// --------------------------------------------------------------------------
// Delete an image
//
function delete_image($imageID)
{
    // parse the image ID:  <dbnum>:<image key>
    [$dbnum, $key] = explode(":", $imageID);

    // connect to the database encoded in the ID
    $db = imageDbConnect((int)$dbnum);
    if (!$db)
        return false;

    // delete the image at the given key
    $key = mysql_real_escape_string($key, $db);
    $result = mysql_query("delete from images where id='$key'", $db);

    // return the result
    return $result;
}

// --------------------------------------------------------------------------
// Parse an integer value.  If the value is empty, we'll return null.
//
function parseIntVal($val) {
    // remove spaces
    $val = trim($val);

    // if it's empty, return null
    if (is_null($val) || $val == "")
        return null;

    // do the normal php conversion
    return (int)$val;
}

// --------------------------------------------------------------------------
// Parse a date value, returning a MySQL-comptatible date in the
// format YYYY-MM-DD.  If it just looks like a year (YYYY), convert it
// to YYYY-01-01; otherwise try to parse as a full date.
function parseDateVal($val) {
    // remove spaces
    $val = trim($val);

    // if it's empty, return null
    if (is_null($val) || $val == "")
        return null;

    // look for a simple YYYY year value
    if (strlen($val) == 4 && preg_match("/[0-9][0-9][0-9][0-9]$/AD", $val))
        return $val . "-01-01";

    // parse it as a date
    $dt = strtotime($val);
    if ($dt == false)
        return false;

    // format it as YYYY-MM-DD for passing to MySQL
    return date("Y-m-d", $dt);
}

// ------------------------------------------------------------------------
// are two arrays equivalent?
function arraysEqual($a, $b)
{
    // if they don't have the same number of elements, they don't match
    if (count($a) != count($b))
        return false;

    // compare each element
    foreach ($a as $key => $aval) {
        // if this element isn't set in the other array, it can't match
        if (!isset($b[$key]))
            return false;

        // get the other value
        $bval = $b[$key];

        // if both values are arrays, recursively compare the arrays
        if (gettype($aval) == "array" && gettype($bval) == "array") {
            if (!arraysEqual($aval, $bval))
                return false;
        } else {
            // they're not both arrays, so compare directly
            if ($aval != $bval)
                return false;
        }
    }

    // no differences found
    return true;
}

// compare arrays, checking only the specific key elements given
function arrayValsEqual($a, $b, $keys)
{
    // check each key in the list
    foreach ($keys as $k) {
        // get the value; treat not-set as an empty value
        $aVal = (isset($a[$k]) ? $a[$k] : "");
        $bVal = (isset($b[$k]) ? $b[$k] : "");

        // the values must be equal
        if ($aVal != $bVal)
            return false;
    }

    // no differences found
    return true;
}

// compare arrays of arrays, checking the values in each one
function arrayOfArrayValsEqual($a, $b, $keys)
{
    if (!is_array($a)) {
        $a = [];
    }
    if (!is_array($b)) {
        $b = [];
    }

    // make sure the two arrays have the same number of elements
    if (count($a) != count($b))
        return false;

    // compare each element
    for ($i = 0 ; $i < count($a) ; $i++) {
        if (!arrayValsEqual($a[$i], $b[$i], $keys))
            return false;
    }

    // no differences found
    return true;
}

// --------------------------------------------------------------------------
// Validate the contents of a TTF file
//
function validate_ttf($data)
{
    // unpack the offset table
    [$numTables, $vsn, $ohdr] = ttf_unpack_header($data);

    // set up a list of valid IDs
    $validIDs = ['cmap', 'head', 'hhea', 'hmtx', 'maxp', 'name',
        'OS/2', 'post', 'cvt ', 'fpgm', 'glyf', 'loca',
        'prep', 'CFF ', 'VORG', 'EBDT', 'EBLC', 'EBSC',
        'BASE', 'GDEF', 'GPOS', 'GSUB', 'JSTF', 'DSIG',
        'gasp', 'hdmx', 'kern', 'LTSH', 'PCLT', 'VDMX',
        'vhea', 'vmtx'];

    // check the tables
    for ($i = 0 ; $i < $numTables ; $i++) {

        // unpack the table header
        [$tid, $tsum, $tofs, $tlen] = ttf_unpack_dir($data, $i);

        // calculate the size of the table
        $tend = $tofs + 4*(int)((($tlen + 3) & ~3) / 4);

        // check the ID
        if (array_search($tid, $validIDs, true) === false) {
            // echo "ttf: bad header<br>";
            return false;
        }

        // calculate the checksum for the table
        for ($sum = 0, $j = $tofs ; $j < $tend ; $j += 4)
        {
            $b = unpack("Ncur/", substr($data, $j, 4));
            $sum += $b['cur'];
        }

        // if this is the 'head' table, adjust the checksum by deleting
        // the checkSumAdjustment field (at offset 8 in the data block)
        if ($tid == 'head') {
            $b = unpack("Ncur/", substr($data, $tofs + 8, 4));
            $sum -= $b['cur'];
        }

        $sum &= 0xFFFFFFFF;
        if ($sum != $tsum) {
            // echo "ttf: bad checksum for $tid block<br>";
            return false;
        }
    }

    // calculate the file checksum
    $flen = strlen($data);
    for ($fsum = 0, $j = 0 ; $j < $flen ; $j += 4)
    {
        $b = unpack("Ncur/", substr($data, $j));
        $fsum += $b['cur'];
    }

    $fsum &= 0xFFFFFFFF;
    if ((int)$fsum != (int)0xB1B0AFBA) {
        // echo "ttf: bad file checksum $fsum<br>";
        return false;
    }

    // no errors found
    return true;
}

// ---------------------------------------------------------------------------
// Get the font family name from a TTF file
//
function ttf_family_name($data)
{
    // unpack the offset table
    [$numTables, $vsn, $ohdr] = ttf_unpack_header($data);
    $vsnName = ($vsn == 0x4F54544f ? "OpenType" : "TrueType");

    // find the Naming table (tag 'name')
    for ($i = 0 ; $i < $numTables ; $i++) {

        // unpack the table header
        [$tid, $tsum, $tofs, $tlen] = ttf_unpack_dir($data, $i);

        // if it's the naming table, parse it
        if ($tid == 'name') {
            // get the number of naming table entries
            $b = unpack("ncnt", substr($data, $tofs + 2, 2));
            $nameCnt = $b['cnt'];

            // scan the entries for names #1 (family name) and #2 (sub name)
            $names = [];
            $storageOfs = $tofs + 6 + $nameCnt*12;
            for ($j = 0 ; $j < $nameCnt ; $j++) {
                // parse out this name record
                $b = unpack("npform/nenc/nlang/nnameID/nlen/nofs",
                            substr($data, $tofs + 6 + $j*12));
                $nameID = $b['nameID'];
                $nameLen = $b['len'];
                $nameOfs = $b['ofs'];

                // only keep the first instance of each name
                if (!isset($names[$nameID])) {
                    // pull out the name
                    $b = unpack("a{$nameLen}name",
                                substr($data, $storageOfs + $nameOfs));
                    $names[$nameID] = $b['name'];
                }
            }

            // put together names #1 and #2
            $ret = $names[1];
            if ($names[2])
                $ret .= " " . $names[2];

            $ret .= " ($vsnName Font)";

            // return the name
            return $ret;
        }
    }

    // we didn't find the naming table - return failure
    return "Untitled $vsnName Font";
}

// ---------------------------------------------------------------------------
//
// Unpack a TTF header
//
function ttf_unpack_header($data)
{
    // unpack the offset table
    $ohdr = unpack("Nvsn/nnumTables/n3range", substr($data, 0, 12));
    $vsn = $ohdr['vsn'];
    $numTables = $ohdr['numTables'];

    return [$numTables, $vsn, $ohdr];
}


//
// Unpack a TTF table directory
//
function ttf_unpack_dir($data, $tblnum)
{
    // calculate the offset of the table
    $ofs = 12 + $tblnum*16;

    // unpack the table directory
    $thdr = unpack("a4id/Ncksum/Nofs/Nlen/", substr($data, $ofs, 16));
    $tid = $thdr['id'];
    $tsum = $thdr['cksum'];
    $tofs = $thdr['ofs'];
    $tlen = $thdr['len'];

    // return the data
    return [$tid, $tsum, $tofs, $tlen];
}

// ------------------------------------------------------------------------
//
// Generate a random hex token
//
function random_token($bytes = 20)
{
    return bin2hex(random_bytes($bytes));
}

// ------------------------------------------------------------------------
//
// Calculate a SHA-256 hash value
//
function sha256($val)
{
    return hash("sha256", $val);
}

// ------------------------------------------------------------------------
//
// Ban or close a user account.  The disposition is determined by $stat:
// pass in 'X' to close the account at the user's request, 'B' to ban it.
// Banning preserves the user table entry but marks it as banned, to
// ensure that the same user can't re-enroll another account with the
// same email.  Closing keeps the account but removes the email address,
// to allow later re-enrollment.  Both banning and closing delete
// discussions and other traces of the user in other tables.
//
function close_user_acct($db, $uid, $stat, &$progress)
{
    $result = with_transaction($db, function ($db) use ($uid, $stat, &$progress) {
        // If closing the account, set the email to the TUID (which will
        // definitely be unique, as everything else in the column is
        // either a valid email address or another TUID), and save the
        // email with the profile text.
        $setClosed = "";
        if ($stat == 'X') {
            $setClosed = ", profile = concat('[Closed account: email was ', "
                         . "email, ']\n', profile), email = id";
        }

        // update the status in the user record
        $progress = "updating USERS";
        $result = mysqli_execute_query($db,
            "update users
             set acctstatus = ? $setClosed
             where id = ?", [$stat, $uid]);

        // cancel any persistent sessions
        if ($result) {
            $progress = "deleting user's persistent login sessions";
            $result = forget_remembered_logins($db, $uid);
        }

        // delete discussions, part 1: delete postings by user
        if ($result) {
            $progress = "deleting user's discussions";
            $result = mysqli_execute_query($db,
                "delete from ucomments
                 where userid = ?", [$uid]);
        }

        // delete discussions, part 2: delete posting on this user's profile
        if ($result) {
            $progress = "deleting comments on user's profile";
            $result = mysqli_execute_query($db,
                "delete from ucomments
                 where source = 'U' and sourceid = ?", [$uid]);
        }

        // delete comments on this user's review comments
        if ($result) {
            $progress = "deleting comments on user's reviews";
            $result = mysqli_execute_query($db,
                "delete from ucomments
                 where source = 'R' and sourceid in
                   (select id from reviews where userid = ?)", [$uid]);
        }

        // delete this user's reviews
        if ($result) {
            $progress = "deleting user's reviews";
            $result = mysqli_execute_query($db,
                "delete from reviews, reviewtags, reviewvotes
                 using reviews
                   left outer join reviewtags
                     on reviewtags.reviewid = reviews.id
                   left outer join reviewvotes
                     on reviewvotes.reviewid = reviews.id
                 where reviews.userid = ?", [$uid]);
        }

        // delete review flags by user
        if ($result) {
            $progress = "deleting user's review flags";
            $result = mysqli_execute_query($db,
                "delete from reviewflags where flagger = ?", [$uid]);
        }

        // delete review votes by user
        if ($result) {
            $progress = "deleting user's review votes";
            $result = mysqli_execute_query($db,
                "delete from reviewvotes where userid = ?", [$uid]);
        }

        // rebuild the tag list for any games this user has tagged, without
        // this user's tags
        if ($result) {
            $progress = "rebuilding games.tags for deleting user's game tags";
            $result = mysqli_execute_query($db,
                "update games
                 set tags = (
                   select group_concat(tag separator ',')
                   from gametags as gt1
                   where gameid = id and userid != ?)
                 where id in (
                   select distinct gameid
                   from gametags as gt2
                   where userid = ?)", [$uid, $uid]);
        }

        // delete this user's tags
        if ($result) {
            $progress = "deleting user's game tags";
            $result = mysqli_execute_query($db,
                "delete from gametags
                 where userid = ?", [$uid]);
        }

        // delete cross-recommendations
        if ($result) {
            $progress = "deleting user's cross-recommendations";
            $result = mysqli_execute_query($db,
                "delete from crossrecs where userid = ?", [$uid]);
        }

        // delete wish lists, play lists, and unwishlists
        if ($result) {
            $progress = "deleting user's wishlist";
            $result = mysqli_execute_query($db,
                "delete from wishlists where userid = ?", [$uid]);
        }
        if ($result) {
            $progress = "deleting user's played games list";
            $result = mysqli_execute_query($db,
                "delete from playedgames where userid = ?", [$uid]);
        }
        if ($result) {
            $progress = "deleting user's un-wishlist";
            $result = mysqli_execute_query($db,
                "delete from unwishlists where userid = ?", [$uid]);
        }

        // delete the user's recommended list comments
        if ($result) {
            $progress = "deleting user's recommended list comments";
            $result = mysqli_execute_query($db,
                "delete from ucomments
                 where source = 'L' and sourceid in
                   (select id from reclists where userid = ?)", [$uid]);
        }

        // delete the user's recommended lists
        if ($result) {
            $progress = "deleting user's recommended lists";
            $result = mysqli_execute_query($db,
                "delete from reclists, reclistitems
                 using reclists
                   left outer join reclistitems
                     on reclistitems.listid = reclists.id
                 where reclists.userid = ?", [$uid]);
        }

        // delete the user's poll comments
        if ($result) {
            $progress = "deleting user's poll list comments";
            $result = mysqli_execute_query($db,
                "delete from ucomments
                 where source = 'P' and sourceid in
                   (select pollid from polls where userid = ?)", [$uid]);
        }

        // delete the user's polls
        if ($result) {
            $progress = "deleting user's polls";
            $result = mysqli_execute_query($db,
                "delete from polls, pollvotes, pollcomments
                 using polls
                   left outer join pollvotes
                     on pollvotes.pollid = polls.pollid
                   left outer join pollcomments
                     on pollcomments.pollid = polls.pollid
                 where polls.userid = ?", [$uid]);
        }

        // delete the user's poll votes
        if ($result) {
            $progress = "deleting user's poll votes";
            $result = mysqli_execute_query($db,
                "delete from pollvotes where userid = ?", [$uid]);
        }

        // delete the user's news items
        if ($result) {
            $progress = "deleting user's news items";
            $result = mysqli_execute_query($db,
                "delete from news where userid = ?", [$uid]);
        }

        // delete the user's stylesheets
        if ($result) {
            $progress = "deleting user's stylesheets";
            $result = mysqli_execute_query($db,
                "delete from stylesheets where userid = ?", [$uid]);
        }

        // delete the user's stylesheet images
        if ($result) {
            $progress = "deleting user's stylesheet graphics";
            $result = mysqli_execute_query($db,
                "delete from stylepics where userid = ?", [$uid]);
        }

        return $result;
    }, $error);

    // note the database error
    if (!$result)
        $progress .= " (db error: " . htmlspecialchars($error) . ")";

    return $result;
}


// ------------------------------------------------------------------------
//
// Throttling for failed password checks ('login') and for account
// emails ('email')
//
define("LOGIN_THROTTLE_MINUTES", 15);
define("LOGIN_FAILURES_PER_ACCOUNT", 10);
define("LOGIN_FAILURES_PER_IP", 50);
define("ACCOUNT_EMAILS_PER_DAY", 12);
define("ACCOUNT_EMAILS_PER_IP_PER_HOUR", 20);

function throttle_record($db, $action, $userid = null)
{
    mysqli_execute_query($db,
        "delete from throttle where `when` < date_sub(now(), interval 1 day)");
    mysqli_execute_query($db,
        "insert into throttle (action, ip, userid, `when`)
         values (?, ?, ?, now())",
        [$action, $_SERVER['REMOTE_ADDR'], $userid]);
}

// Clears the account's count but keeps the per-IP history
function throttle_clear($db, $action, $userid)
{
    mysqli_execute_query($db,
        "update throttle set userid = null where action = ? and userid = ?",
        [$action, $userid]);
}

function login_throttled($db, $userid)
{
    $result = mysqli_execute_query($db,
        "select ifnull(sum(userid = ?), 0), ifnull(sum(ip = ?), 0)
         from throttle
         where action = 'login'
           and `when` > date_sub(now(), interval "
           . LOGIN_THROTTLE_MINUTES . " minute)",
        [$userid, $_SERVER['REMOTE_ADDR']]);
    [$byUser, $byIp] = mysql_fetch_row($result);
    return $byUser >= LOGIN_FAILURES_PER_ACCOUNT
        || $byIp >= LOGIN_FAILURES_PER_IP;
}

function login_throttled_message()
{
    return "There have been too many failed password attempts. "
        . "Please wait "
        . LOGIN_THROTTLE_MINUTES . " minutes and try again, or "
        . "<a href=\"lostpass\">reset your password</a>.";
}

// Minutes to wait before sending another account email to this user.
// Each email in the last day doubles the wait.
function account_email_wait($db, $userid)
{
    $result = mysqli_execute_query($db,
        "select count(*),
           timestampdiff(second, now(), max(`when`)),
           timestampdiff(second, now(), date_add(min(`when`), interval 1 day))
         from throttle
         where action = 'email' and userid = ?
           and `when` > date_sub(now(), interval 1 day)", [$userid]);
    [$n, $untilLast, $untilFirstExpires] = mysql_fetch_row($result);
    if ($n >= ACCOUNT_EMAILS_PER_DAY)
        $wait = $untilFirstExpires;
    else if ($n > 0)
        $wait = $untilLast + 60 * 2 ** ($n - 1);
    else
        $wait = 0;

    $result = mysqli_execute_query($db,
        "select count(*), timestampdiff(second, now(),
           date_add(min(`when`), interval 1 hour))
         from throttle
         where action = 'email' and ip = ?
           and `when` > date_sub(now(), interval 1 hour)",
        [$_SERVER['REMOTE_ADDR']]);
    [$byIp, $untilIpExpires] = mysql_fetch_row($result);
    if ($byIp >= ACCOUNT_EMAILS_PER_IP_PER_HOUR)
        $wait = max($wait, $untilIpExpires);

    return max(0, (int)ceil($wait / 60));
}

function throttle_lock($db, $name)
{
    $result = mysqli_execute_query($db, "select get_lock(?, 10)", [$name]);
    return mysql_fetch_row($result)[0] == 1;
}

function throttle_account_email($db, $userid)
{
    if (!throttle_lock($db, 'throttle_email'))
        return 1;
    $wait = account_email_wait($db, $userid);
    if (!$wait)
        throttle_record($db, 'email', $userid);
    mysqli_execute_query($db, "select release_lock('throttle_email')");
    return $wait;
}


// ------------------------------------------------------------------------
//
// Simple http_get.  The timeout is specified in milliseconds.  On return,
// $timeout is set to true if we in fact timed out, false if not.
//
// $headersIn is an optional string with any custom headers to send with
// the request.  This is appended to our standard headers (Host, Resource,
// and Connection).  The headers must be given with a CR-LF after each
// custom header, including after the last header.  Pass an empty string
// or null if no custom headers are needed.
//
function x_http_get($url, $headersIn = null, &$headersOut = null,
                    &$timeout = 30000)
{
    // Figure the expiration time: this is the current system time plus the
    // timeout.  Note that the system time is in seconds (and fractions of
    // a second), whereas the timeout is in milliseconds, so divide the
    // timeout by 1000 to get it in seconds.
    $end_time = microtime(true) + $timeout/1000;

    // we haven't timed out yet
    $timeout = false;

    // parse the http://domain:port/resource string
    preg_match("/^http:\/\/([-a-z0-9.]+)(:\d+)?(\/.*$)/i", $url, $match);
    $addr = $match[1];
    $port = $match[2];
    $res = $match[3];

    // get the port number if specified, otherwise default to port 80
    if ($port)
        $port = (int)substr($port, 1);
    else
        $port = 80;

    // Figure the timeout for the socket open, based on the interval between
    // the current system time and the ending time limit.
    $cur_time = microtime(true);
    $open_timeout = ($end_time > $cur_time ? $end_time - $cur_time : 0);

    // open a socket to the specified server
    $fp = fsockopen($addr, $port, $errno, $errstr, $open_timeout);
    if (!$fp)
    {
        // return failure
        return false;
    }

    // send the GET
    $req = "GET $res HTTP/1.1\r\n"
           . "Host: $addr\r\n"
           . "Resource: $res\r\n"
           . "Connection: Close\r\n"
           . $headersIn
           . "\r\n";
    fwrite($fp, $req);
    fflush($fp);

    // read the reply
    for ($msg = "" ;; )
    {
        // figure the remaining interval until the timeout limit
        $cur_time = microtime(true);
        $interval = ($end_time > $cur_time ? $end_time - $cur_time : 0);
        stream_set_timeout($fp, 0, $interval * 1000000);

        // read the next 4k fragment
        $buf = fgets($fp, 4096);

        // 'false' means error, timeout, or EOF
        if ($buf === false)
        {
            // check for a timeout
            $info = stream_get_meta_data($fp);
            if ($info["timed_out"])
                $timeout = true;

            // in any case, we're done
            break;
        }

        // add this fragment to the message
        $msg .= $buf;
    }

    // we're done with the socket
    fclose($fp);

    // break the reply into headers and body at the double CR-LF
    if ($msg)
    {
        // get the headers and message
        $msg = explode("\r\n\r\n", $msg);
        $headersOut = $msg[0];

        // make a table out of the header
        $htab = [];
        $hh = explode("\r\n", $headersOut);
        foreach ($hh as $hdr)
        {
            $hdr = explode(": ", $hdr);
            if (count($hdr) == 2)
                $htab[strtolower($hdr[0])] = $hdr[1];
        }

        // check for chunked transfer encoding
        if (isset($htab["transfer-encoding"])
            && strtolower($htab["transfer-encoding"]) == "chunked")
        {
            // they blew chunks at us - un-chunk it
            $body = "";
            for ($i = 1 ; $i < count($msg) ; $i++)
            {
                if (preg_match("/^(\d+)\r\n(.*)$/", $msg[$i], $match))
                    $body .= substr($match[2], 0, $match[1]);
            }
        }
        else
        {
            // regular transfer encoding
            $body = $msg[1];
        }
    }
    else
    {
        $headersOut = "";
        $body = false;
    }

    // return the body
    return $body;
}

// ----------------------------------------------------------------------------
//
// Check an insertion/update for links and send admin email, for spam review
//
function send_admin_email_if_links($txt, $context, $contextLink)
{
    if (preg_match("/https?:\/\//i", $txt))
    {
        $userid = current_user_id();
        $hdrs = "From: IFDB <noreply@ifdb.org>\r\n"
                . "Content-type: Text/HTML\r\n";
        send_mail("ifdbadmin@ifdb.org", "IFDB hyperlink review",
             "User: <a href=\"" . get_root_url() . "showuser?id=$userid\">$userid</a><br>\n"
             . "Context: <a href=\"" . get_root_url() . "{$contextLink}\">$context</a><br>\n"
             . "Text:<br>\n<br>\n"
             . $txt,
             $hdrs);
    }
}

function send_mail($to, $subject, $message, $additional_headers) {
    $wrapped = wordwrap($message, 78, "\r\n");
    if (isLocalDev()) {
        error_log("EMAIL: NOT SENDING EMAIL IN LOCAL DEVELOPMENT MODE");
        error_log("To: $to");
        error_log("Subject: $subject");
        error_log(print_r($additional_headers, true));
        error_log($wrapped);
        return true;
    } else {
        return mail($to, $subject, $wrapped, $additional_headers);
    }
}

function get_root_url() {
    if (isProduction()) {
        return "https://ifdb.org/";
    } else if (isStaging()) {
        return "https://dev.ifdb.org/";
    } else {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/";
    }
}

// --------------------------------------------------------------------------
//
// Checks if the userid is an admin
//
function check_admin_privileges($db, $userid) {
    if (!$userid) return false;
    $result = mysqli_execute_query($db,
        "select privileges from users where id = ?", [$userid]);
    $row = mysql_fetch_row($result);
    return $row && strpos($row[0], "A") !== false;
}

function coverArtThumbnail($id, $size, $version, $params = "", $alt_text = "") {
    $thumbnail = "/coverart?id=$id&thumbnail=";
    $x15 = round($size * 3 / 2);
    $x2 = $size * 2;
    $x3 = $size * 3;
    if ($version) {
        $params .= "&version=" . (int)$version;
    }
    global $nonce;
    return "<style nonce='$nonce'>.coverart__img { max-width: 35vw; height: auto; }</style>"
        ."<img class='coverart__img' loading='lazy' srcset=\"$thumbnail{$size}x$size$params, $thumbnail{$x15}x$x15$params 1.5x, $thumbnail{$x2}x$x2$params 2x, $thumbnail{$x3}x$x3$params 3x\" src=\"$thumbnail{$size}x$size$params\" height=$size width=$size border=0 alt=\"".htmlspecialchars($alt_text)."\">";
}

// ----------------------------------------------------------------------------
//
// Turns a comma-separated list of more than twenty-five authors into
// a collapsed, expandable list of authors
//

function collapsedAuthors($authors) {
    $authorArr = explode(', ', $authors);
    $str = "";

    if (count($authorArr) > 25) {
        $firstAuthor = array_shift($authorArr);
        $secondAuthor = array_shift($authorArr);

        $str =  "$firstAuthor, $secondAuthor et al."
                . "<details><summary>Show other authors</summary>"
                . implode(', ', $authorArr)
                . "</details>";
    }
    else {
        $str = $authors;
    }
    return $str;
}

// ----------------------------------------------------------------------------
//
// Convert total minutes of play time into hours and minutes,
// and then into text for display
//
function convertTimeToText($total_minutes) {
    // Convert total minutes of play time into hours and minutes
 	$h = floor($total_minutes/60);   
 	$m = $total_minutes - ($h * 60);
    // Make a string to display the time
    $text="";
    if ($h >= 1) {
        $text = "$h ";
        if ($h == 1) {
            $text .= "hour";
        } else {
            $text .= "hours";
        }
        if ($m >= 1) {
            $text .= " and ";
        }
    }
    if ($m >= 1) {
        $text .= "$m ";
        if ($m == 1) {
            $text .= "minute";
        } else {
            $text .= "minutes";
        }
    }
    return $text;
}

// ----------------------------------------------------------------------------
//
// Sends a JSON response, used by the API
//

function send_json_response($data) {
    header("Content-Type: application/json");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");

    echo json_encode($data);
}

// ----------------------------------------------------------------------------
//
// Sends a response to small update operations made by users
//

function send_action_response($label, $error = null, $extra = null) {
    $arr = [];
    if ($label)
        $arr['label'] = $label;
    if ($error)
        $arr['error'] = $error;
    if ($extra) {
        foreach ($extra as $k => $v) {
            $arr[$k] = $v;
        }
    }

    send_json_response($arr);
    exit();
}

?>
