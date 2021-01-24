<?php

include_once "dbconnect.php";
include_once "images.php";
include_once "login-persist.php";

// --------------------------------------------------------------------------
// mysqli replacements (for compatibility across php versions)

function mysql_connect($server, $user, $password) { return mysqli_connect($server, $user, $password); }
function mysql_select_db($db, $linkid = NULL) { return mysqli_select_db($linkid, $db); }
function mysql_real_escape_string($str, $db) { return mysqli_real_escape_string($db, $str); }
function mysql_query($query, $db) { return mysqli_query($db, $query); }
function mysql_num_rows($result) { return $result ? mysqli_num_rows($result) : 0; }
function mysql_fetch_array($result, $match_type = MYSQLI_BOTH) { return mysqli_fetch_array($result, $match_type); }
function mysql_fetch_row($result) { return mysqli_fetch_row($result); }
function mysql_close($db) { return mysqli_close($db); }
function mysql_error($db) { return mysqli_error($db); }
function mysql_errno($db) { return mysqli_errno($db); }
function mysql_result($result, $row, $field = 0) {
    mysqli_data_seek($result, $row);
    $row = mysqli_fetch_assoc($result);
    return $row[$field];
}
function mysql_insert_id($linkid) { return mysqli_insert_id($linkid); }
define("MYSQL_ASSOC", MYSQLI_ASSOC);
define("MYSQL_BOTH",  MYSQLI_BOTH);

define("PRODUCTION_SERVER_NAME", "ifdb.tads.org");

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
// shoot the recommendation cache
//
function shoot_recommendation_cache()
{
    unset($_SESSION['ifdb_recommendations']);
}

// --------------------------------------------------------------------------
// Encode for output.
//
// CURRENTLY, this does nothing, since we output in ISO-8859-1, just like
// PHP's internal string representation.
//
// This function exists mainly so that we can change to UTF-8 or another
// encoding more easily at some point in the future.  Many of the output
// points where we display strings are covered by output_encode() calls -
// we didn't catch them all, but we caught many of them, so changing to
// another output character set would just involve doing the translation
// here.
//
function output_encode($str)
{
    return $str;
}

// ------------------------------------------------------------------------
//
// Encode output for RSS.  We send RSS in UTF-8 format, so this converts
// to UTF-8.
//
function rss_encode($str)
{
    return iconv("ISO-8859-1", "UTF-8", $str);
}

// ------------------------------------------------------------------------
//
// Determine if the string is UTF-8 encoded.
//
function is_utf8($str)
{
    $f = "\x80\x81\x82\x83\x84\x85\x86\x87\x88\x89\x8A\x8B\x8C\x8D\x8E\x8F"
         . "\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F"
         . "\xA0\xA1\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xAB\xAC\xAD\xAE\xAF"
         . "\xB0\xB1\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xBB\xBC\xBD\xBE\xBF"
         . "\xC0\xC1\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xCF"
         . "\xD0\xD1\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xDB\xDC\xDD\xDE\xDF"
         . "\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF"
         . "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
    $t = "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f"
         . "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f"
         . "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f"
         . "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f"
         . "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f"
         . "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f"
         . "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f"
         . "\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f\x7f";

    return (strtr($str, $f, $t) != $str
            && iconv("UTF-8", "UTF-8", $str) == $str);
}

// ------------------------------------------------------------------------
//
// Given a UTF-8 string, substitute 8859-1 approximations for certain
// characters that we'd otherwise lose on translation.
//
function approx_utf8($str)
{
    return str_replace(
        array("\xE2\x80\x99", "\xC2\xB4", " \xEF\xBF\xBD ", "\xE2\x80\xA6",
              "\xEF\xBF\xBDs"),
        array("'", "'", " - ", "...", "'s"),
        $str);
}

// ------------------------------------------------------------------------
//
// htmlspecialchars() replacement.  This converts everything that
// the standard htmlspecialchars() does, *except* that it leaves
// &#nnnn; sequences intact.  Since we don't store Unicode directly,
// this provides a rudimentary way of storing these characters.
//
function htmlspecialcharx($str)
{
    // first do the ampersands
    $str = preg_replace("/&(?!#[0-9]{1,5};)/", "&amp;", $str);

    // now do the rest of the characters and return the result
    return str_replace(
        array('"', '<', '>'), array('&quot;', '&lt;', '&gt;'), $str);
}

// extended URL encoding, with UTF8 conversion
function urlencodex($str)
{
    return urlencode(utf8_encode($str));
}

// URL-encode from a string that's been HTML quoted
function urlencodeFromHTML($str)
{
	return urlencode(utf8_encode(htmlspecialchars_decode($str)));
}

// --------------------------------------------------------------------------
// OS detect - this attempts to guess the user's OS based on the browser
// identification string.  Returns an (OS ID, Version ID) pair.
//
function browser_os_detect()
{
    // get the browser ID string
    $b = strtolower($_SERVER['HTTP_USER_AGENT']);

    // get the list of version strings to try
    $db = dbConnect();
    $result = mysql_query(
        "select
           id, vsnid, operatingsystems.name as name,
           browserid, seq, displaypriority
         from operatingsystems
           left outer join osversions
           on operatingsystems.id = osversions.osid
         order by displaypriority desc, name, seq desc", $db);

    // look for the first match
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        list($osid, $vsnid, $nm, $pat, $seq, $pri) = mysql_fetch_row($result);
        $pat = str_replace("/", "\\/", $pat);
        if ($pat != "" && preg_match("/$pat/i", $b))
            return array($osid, $vsnid);
    }

    // no match found
    return false;
}

// --------------------------------------------------------------------------
// are we on an iPod or iPhone?
//
function is_ipod_or_iphone()
{
    // get the browser ID string
    $b = $_SERVER['HTTP_USER_AGENT'];

    // check for the relevant strings
    return preg_match(
        "/^Mozilla\/[0-9.]+ \((iPhone|iPod|iPhone Simulator);/",
        $b, $match, 0, 0);
}

// are we on a Kindle?
function is_kindle()
{
    // get the browser ID string
    $b = $_SERVER['HTTP_USER_AGENT'];

    // check for the relevant strings
    return preg_match("#Kindle/[0-9]#", $b, $match, 0, 0);
}

// is the browser Opera?
function is_opera()
{
    // get the browser ID string
    $ua = $_SERVER['HTTP_USER_AGENT'];

    // check for the telltale substring
    return stristr($ua, 'opera') !== false;
}

// is the browser firefox?
function is_firefox()
{
    // get the browser ID string
    $ua = $_SERVER['HTTP_USER_AGENT'];

    // check for the telltale substring
    return stristr($ua, 'Firefox') !== false;
}

function is_ie()
{
    // get the browser ID string
    $ua = $_SERVER['HTTP_USER_AGENT'];

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

    // if magic quotes are on, strip slashes
    if (get_magic_quotes_gpc())
        $val = stripslashes($val);

    // Opera has a bug (at least, it looks like a bug to me) that we need
    // to work around here.  If the form data contain certain extended
    // characters, Opera will encode them as &#dddd; entities.  This is
    // problematic because it *doesn't* encode ampersands the same way,
    // so we can't be sure whether a &#dddd; sequence was originally a
    // special character, or is that literal sequence typed in by the
    // user.  As a workaround, we just flatly assume that all such
    // sequences are Opera encodings, since it seems so unlikely that
    // a user would type such a thing literally.  We only apply this for
    // curly quotes.
    if (is_opera()) {
        $val = str_replace(
            array('&#8220;', '&#8221;', '&#8216;', '&#8217;'),
            array("\223", "\224", "\221", "\222"),
            $val);
    }

    // if this looks like UTF-8, it probably came from javascript encoding;
    // translate into 8859-1
    //   [ Disabled for now - we've solved the problem another way, by
    //     using our own custom encodeURIComponent()  substitute called
    //     encodeURI8859() that we define in ifdbutil.js.  The problem we
    //     were having was that encodeURIComponent() encodes 8-bit
    //     characters in UTF-8, whereas we expect our URLs to be in
    //     8859-1.  I don't fully trust is_utf() to be able to reliably
    //     distinguish UTF-8 from 8859-1 in all cases, and I'm worried
    //     about obscure side effects of changing this routine that
    //     practically everyone calls.  So it seems much better to avoid
    //     creating the problem in the first place.  The only place it
    //     seemed to be coming from was our own javascript generated
    //     URLs, and it was easy enough to fix those. ]
//    if (is_utf8($val))
//        $val = iconv("UTF-8", "ISO-8859-1//IGNORE", approx_utf8($val));

    // return the result
    return $val;
}

// --------------------------------------------------------------------------
// quote a SQL LIKE term - escape "%" and "_" characters
//
function quoteSqlLike($s)
{
    return preg_replace("/[%_]/", "\\$0", $s);
}

// quote a SQL RLIKE term - escape regular-expression characters
function quoteSqlRLike($s)
{
    for ($i = 0 ; $i < strlen($s) ; $i++) {
        if (strpos('^$.*+?|(){}[]\\', $s{$i}) !== false) {
            $repl = '[[.' . $s{$i} . '.]]';
            $s = substr_replace($s, $repl, $i, 1);
            $i += 6;
        }
    }
    return $s;
}

// --------------------------------------------------------------------------
// escape a string for javascript purposes: converts \ -> \\, " -> \",
// newline -> \n
//
function jsSpecialChars($s)
{
    $s = preg_replace("/[\\\"\']/", "\\\\$0", $s);
    return str_replace(array("\r", "\n"), array("\\r", "\\n"), $s);
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
        // generate a random ID
        $tuid = base_convert(rand(0, 46655), 10, 36)
                . base_convert(rand(0, 46655), 10, 36)
                . base_convert(rand(0, 46655), 10, 36)
                . base_convert(rand(0, 46655), 10, 36)
                . base_convert(rand(0, 46655), 10, 36)
                . base_convert(rand(0, 35), 10, 36);

        if (!$tableCol)
            return $tuid;
        
        // check each table for an existing entry with this TUID
        $found = false;
        foreach ($tableCol as $tc)
        {
            // get this table and column name
            list($table, $col) = explode(".", $tc);

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
    global $cssOverride;
    $db = dbConnect();
    
    // check for a profile style
    $userid = checkPersistentLogin();
    $ssid = false;
    if ($userid && !$cssOverride) {
        $result = mysql_query(
            "select u.stylesheetid, s.userid
             from users as u
               join stylesheets as s on s.stylesheetid = u.stylesheetid
             where u.id='$userid'", $db);
        if (mysql_num_rows($result) > 0)
            list($ssid, $ssauthor) = mysql_fetch_row($result);
    }

    // failing that, check to see if we're on an iPod or iPhone - if so,
    // use the special default style sheet for those devices
    if (!$ssid && is_ipod_or_iphone()) {
        // the iPod/iPhone style sheet has ID=6 - it's really an ordinary
        // style sheet, created by Craig Smith (craig@ni.com), but it's
        // distinguished as the default style sheet for these devices
        $result = mysql_query(
            "select stylesheetid, userid from stylesheets
             where stylesheetid = '6'", $db);
        if (mysql_num_rows($result) > 0)
            list($ssid, $ssauthor) = mysql_fetch_row($result);
    }

    // check for a temporary CSS override
    if ($cssOverride) {
        $db = dbConnect();
        $ssid = mysql_real_escape_string($cssOverride, $db);
        $result = mysql_query(
            "select userid from stylesheets
             where stylesheetid = '$ssid'", $db);
        $ssauthor = mysql_result($result, 0, "userid");
    }

    // If we found a custom style sheet selection, use it; otherwise use
    // the default style sheet.
    if ($ssid)
        echo "<link rel=\"stylesheet\"
               href=\"/users/$ssauthor/css/$ssid.css\">";
    else
        echo "<link rel=\"stylesheet\" href=\"/ifdb.css\">";
//        echo "<link rel=\"stylesheet\" href=\"/ifdb-default.css\">";
}

// --------------------------------------------------------------------------
// send an image description page - this includes the image and the
// copyright information
function sendImageLdesc($title, $imageID)
{
    global $copyrightStatList;
    checkPersistentLogin();
    $curuser = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'])
               ? $_SESSION['logged_in_as'] : false;
    
    // parse the image ID:  <dbnum>:<image key>
    list ($dbnum, $key) = explode(":", $imageID);

    // connect to the database encoded in the ID
    $db = imageDbConnect((int)$dbnum);
    if (!$db)
        return false;

    // fetch the copyright information
    $key = mysql_real_escape_string($key, $db);
    $result = mysql_query(
        "select
           copystat, copyright, userid, date_format(createdate, '%M %e, %Y')
         from images where id='$key'", $db);
    list ($copystat, $copymsg, $userid, $created) = mysql_fetch_row($result);

    $username = false;
    if ($userid) {
        $db = dbConnect();
        $quid = mysql_real_escape_string($userid, $db);
        $result = mysql_query("select name from users where id='$quid'", $db);
        if (mysql_num_rows($result) > 0)
            list($username) = mysql_fetch_row($result);
    }

    // send the page
?>
<html>
<head>
   <?php echoStylesheetLink(); ?>
   <title><?php echo htmlspecialcharx($title) ?></title>
</head>
<body>
<div class=main>
   <img src="showimage?id=<?php echo urlencode($imageID) ?>">
   <?php
      if ($copymsg || ($copystat && isset($copyrightStatList[$copystat]))
          || $username || $created) {
          echo "<p><hr class=dots><span class=details>";

          if ($copymsg)
              echo htmlspecialcharx($copymsg) . "<br>";

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

          echo "</span><br>";
      }
   ?>
</div>
</body>
</html>
<?php
    exit();
}

// --------------------------------------------------------------------------
// send an image, possibly with a thumbnail setting
//
define("SIF_RAW", 0x0001);
function sendImage($imgData, $imgFmt, $thumbnail, $flags = 0)
{
    // make sure there's an image
    if (is_null($imgData))
        exit("No image is available");

    // Get the MIME type, assuming for now that it's an image.  For image
    // types, the MIME type is simply "image/X", where X is our format code
    $mimeType = "image/$imgFmt";

    // if it's a font, check for image renderings
    if ($imgFmt == "ttf") {
        // It's a font.  If they want a thumbnail, just send the TTF icon.
        // Otherwise, if they want it as an image (not as the raw file),
        // render a sample page for the font.  If they want it raw, just
        // send the raw data.

        if (!is_null($thumbnail)) {
            // they want a thumbanil - simply send the TTF icon
            $imgData = file_get_contents("ttf_icon.gif");
            $thumbnail = null;
            $imgFmt = "gif";
            $mimeType = "image/gif";
        } else if (($flags & SIF_RAW) != 0) {
            // they want the raw font data - simply send the data,
            // with the TTF MIME type
            $mimeType = "application/x-font-ttf";
        } else {
            // They want an image rendering of the data.  Prepare a
            // sample character set page based on the font.

            // create a temporary file from the font
            $tmpfile = tempnam("/tmp", "ttf");
            $fp = fopen($tmpfile, "wb");
            fwrite($fp, $imgData);
            fclose($fp);

            // set up an image for the sample
            $wid = 500;
            $ht = 200;
            $im = imagecreatetruecolor($wid, $ht);

            // set up some colors
            $white = imagecolorallocate($im, 255, 255, 255);
            $black = imagecolorallocate($im, 0, 0, 0, 0);

            // render the sample page
            imagefilledrectangle($im, 0, 0, $wid, $ht, $white);
            imagettftext($im, 12, 0, 0, 17, $black, $tmpfile,
                         ttf_family_name($imgData));
            imageline($im, 0, 25, $wid, 25, $black);
            imagettftext($im, 20, 0, 0, 50, $black, $tmpfile,
                         "abcdefghijklmnopqrstuvwxyz\n"
                         . "ABCDEFGHIJKLMNOPQRSTUVWXYZ\n"
                         . "1234567890.:,;(:*!?'\")\n");

            // send the constructed image as a jpeg, and we're done
            imagejpeg($im);
            return;
        }
    }

    // if they only want the thumbnail, resize it
    if (!is_null($thumbnail)) {
        // get the thumbnail sizes
        list ($sx, $sy) = explode("x", $thumbnail);
    
        // get the image data
        $source = imagecreatefromstring($imgData);
        $ix = imagesx($source);
        $iy = imagesy($source);

        // only generate a thumbnail if it's SMALLER than the actual size
        if ($sx < $ix || $sy < $iy) {
            // apply the default and maximum settings
            if ($sx <= 0 || $sy <= 0)
                list($sx, $sy) = array(175, 175);
            else if ($sx > 250 || $sy > 250)
                list($sx, $sy) = array(250, 250);

            // Figure the scaled size, maintaining the aspect ratio.
            // First try scaling so that the width just fits the bounding
            // width.  If that makes the height too tall, rescale so that
            // the height fits the bounding height.
            $cx = $cy = 0;
            if ($iy != 0) {
                $cx = $sx;
                $cy = $iy * $sx / $ix;
            }
            if ($cy > $sy && $ix != 0) {
                $cy = $sy;
                $cx = $ix * $sy / $iy;
            }

            // generate the thumbnail image
            $thumb = imagecreatetruecolor($sx, $sy);
            imagefilledrectangle($thumb, 0, 0, $sx, $sy,
                                 imagecolorallocate($thumb, 255,255,255));
            imagecopyresampled($thumb, $source,
                               ($sx - $cx)/2, ($sy - $cy)/2, 0, 0,
                               $cx, $cy, $ix, $iy);

            // send it in the original format, or jpeg if unrecognized
            if ($imgFmt == "gif") {
                header("Content-type: image/gif");
                imagegif($thumb);
            }
            else if ($imgFmt == "png") {
                header("Content-type: image/png");
                imagepng($thumb, null, 0);
            }
            else {
                header("Content-type: image/jpeg");
                imagejpeg($thumb);
            }

            // we've finished sending the image
            return;
        }
    }

    // send the image exactly as it's stored
    header("Content-length: " . strlen($imgData));
    header("Content-type: $mimeType");
    echo $imgData;
}

// ------------------------------------------------------------------------
//
// Create a transparent image resource of the given size
//
function newTransparentImage($x, $y)
{
    // create a true-color image of the given size
    $r = imagecreatetruecolor($x, $y);
    
    // we want to set the transparency in the new image, not combine it
    imagealphablending($r, false);

    // allocate a transparent pixel color
    $transparent = imagecolorallocatealpha($r, 0, 0, 0, 127);

    // fill the whole image with the transparent color
    imagefilledrectangle($r, 0, 0, $x+1, $y+1, $transparent);

    // turn alpha blending back on
    imagealphablending($r, true);

    // return the new image
    return $r;
}

// --------------------------------------------------------------------------
// get a string for showing a rating star image
//
function showStars($num)
{
    // show no star image if there's no average
    if (isEmpty($num))
        return "";

    // round to the nearest half star
    list($roundedNum, $starimg, $startxt) = roundStars($num);

    // return the image string
    return "<img src=\"/blank.gif\" border=0 "
        . "class=\"star{$starimg}\" title=\"$startxt\">";
}

function roundStars($num)
{
    if (isEmpty($num))
        return false;
    else if ($num < 0.25)
        return array(0, "0", "0 Stars");
    else if ($num < 0.75)
        return array(0.5, "0h", "&frac12; Stars");
    else if ($num < 1.25)
        return array(1, "1", "1 Star");
    else if ($num < 1.75)
        return array(1.5, "1h", "1&frac12; Stars");
    else if ($num < 2.25)
        return array(2, "2", "2 Stars");
    else if ($num < 2.75)
        return array(2.5, "2h", "2&frac12; Stars");
    else if ($num < 3.25)
        return array(3, "3", "3 Stars");
    else if ($num < 3.75)
        return array(3.5, "3h", "3&frac12; Stars");
    else if ($num < 4.25)
        return array(4, "4", "4 Stars");
    else if ($num < 4.75)
        return array(4.5, "4h", "4&frac12; Stars");
    else
        return array(5, "5", "5 Stars");
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
        $p .= ($firstItemOnPage+1) . "-" . ($lastItemOnPage+1)
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
    echo "<select name=\"$dropName\" "
        . "onchange=\"javascript:document.getElementById("
        . "'sort-go-button-$idSerial').click();\">";

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
    echo " <input type=image src=\"/blank.gif\" name=\"newSortBy\"
            class=\"go-button\" id=\"sort-go-button-$idSerial\">";

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
    $ret = array();
    foreach ($arr as $v)
        $ret[$v] = $val;
    return $ret;
}

// -------------------------------------------------------------------------
// Put a block of text inside a spoiler protector
//
function spoilerWarning($text, $label = "Spoiler - click to show")
{
    return spoilerWarningOpen($label) . $text . spoilerWarningClose();
}

function spoilerWarningOpen($label = "Spoiler - click to show")
{
    // each spoiler warning needs its own ID number
    static $spoilerNum = 0;

    // set up the text
    $ret = "<span class=\"spoilerButton\" "
           . "id=\"a_spoiler$spoilerNum\">("
           . "<a href=\"#\" onclick=\"javascript:"
           . "showSpoiler('$spoilerNum');"
           . "return false;\">$label"
           . "</a>)</span>"
           . "<span class=\"hiddenSpoiler\" "
           . "id=\"s_spoiler$spoilerNum\">";
    
    // consume the spoiler number
    $spoilerNum++;

    // return the text we generated
    return $ret;
}

function spoilerWarningClose()
{
    return "</span>";
}

// Generate the script to handle spoiler buttons.  This should be called
// at a suitable point in the HTML when a spoiler warning is generated.
//
function spoilerWarningScript()
{
    static $didSpoilerScript = 0;
    if ($didSpoilerScript++ != 0)
        return "";
    else
        return "\n<script type=\"text/javascript\">\n"
            . "<!--\n"
            . "function showSpoiler(id) { "
            . "document.getElementById(\"a_spoiler\" + id).style.display = "
            . "\"none\";"
            . "document.getElementById(\"s_spoiler\" + id).style.display = "
            . "\"inline\";"
            . "}\n"
            . "//-->\n"
            . "</script>";
}
    
// -------------------------------------------------------------------------
// Fix descriptive text to ensure proper formatting in an HTML page
//
// $specials, if given, is a combination of FixDescXxx flags.
//
define("FixDescSpoiler", 0x0001);
define("FixDescRSS", 0x0002);
define("FixDescIfic", 0x0004);
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
        array('p', 'br',
              'i', 'b', 'u', 'strong', 'em',
              'big', 'small', 'tt', 'sup', 'sub',
              'cite', 'blockquote',
              'ul', 'ol', 'li', 'dl', 'dt', 'dd'), 1);
    
    // tags that trigger explicit line control mode
    $lineTags = valuesToKeys(array('p', 'br'), 1);

    // non-stacking tags - these are either tags that aren't containers
    // (such as <br>), or that are containers but don't need to be
    // explicitly closed because they'll be closed implicitly by other
    // tags (e.g., <li> is implicitly closed by another <li> or by
    // the closing </ol> or </ul>)
    $nonStackingTags = valuesToKeys(array('br', 'li', '-spoiler'), 1);

    // reformat for allowed tags and markup-specific characters
    $inAnchor = false;
    $tagStack = array();
    $tagSp = -1;
    for ($ofs = 0 ; $ofs < strlen($desc) ; $ofs++) {
        // check what we're looking at
        switch ($desc{$ofs}) {
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
                    $nlSub = " ";
                    $nlSubIfic = " ";
                    $ofs += 2;

                } else if ($tagName == 'spoiler') {

                    // If <spoiler> tags are allowed, fix it up; otherwise
                    // remove the tag and its contents entirely.  In RSS
                    // mode, substitute "(spoilers)" without any JavaScript.
                    if (($specials & FixDescSpoiler) != 0
                        && ($specials & FixDescRSS) == 0) {

                        // note that we found a spoiler tag
                        $foundSpoiler = true;

                        // start or end of the spoiler span as appropriate
                        if ($isClose) {
                            // close tag - end the span
                            $repl = spoilerWarningClose();
                        } else {
                            // open tag - add the link and start the span
                            $repl = spoilerWarningOpen();
                        }

                        // apply the substitution
                        $desc = substr_replace($desc, $repl, $tagOfs, $tagLen);
                        $ofs = $tagOfs + strlen($repl) - 1;

                    } else {

                        // spoilers aren't allowed, or we're in RSS mode -
                        // scan for the end tag
                        $ofs2 = findEndTag($desc, "spoiler", $gt);

                        // if we didn't find it, delete the whole rest of
                        // the string
                        $ofs2 = ($ofs2 == false ? strlen($desc) : $ofs2[1]);

                        // Remove everything from the start to end tag.  In
                        // RSS mode, substitute "(spoilers)".
                        $repl = (($specials & FixDescRSS) != 0
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

                    // it's an allowed tag - keep it
                    $ofs = $gt;

                    // if it's a line tag, switch to explicit line control
                    // mode - this means that we replace any hard newlines
                    // in the source text with spaces rather than <br>'s
                    if (isset($lineTags[$tagName])) {
                        $nlSub = " ";
                        $nlSubIfic = " ";
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
                            while ($tagSp > 0) {
                                // pop the current tag
                                $curTag = $tagStack[$tagSp--];

                                // if it's our matching tag, we're done
                                if ($tagStack[$tagSp+1] == $tagName)
                                    break;

                                // If it's SPOILER, never close implicitly.
                                // Simply assume the current close tag is
                                // an error, and delete it.
                                if ($curTag == 'spoiler')
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
                        } else if (count($tagStack) != 0) {
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
            // if it's &lt;, &gt;, &#xxxxx;, &quot; or &amp;, leave it;
            // otherwise convert the & to &amp;
            $therest = substr($desc, $ofs + 1, 7);
            if (strncasecmp($therest, "lt;", 3) == 0
                || strncasecmp($therest, "gt;", 3) == 0) {
                $ofs += 3;
            } else if (strncasecmp($desc, "amp;", 4) == 0) {
                $ofs += 4;
            } else if (strncasecmp($desc, "quot;", 5) == 0) {
                $ofs += 5;
            } else if (preg_match("/^#[0-9]{1,5};/", $therest, $match)) {
                $ofs += strlen($match);
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
            if ($desc{$ofs} < ' ')
                $desc{$ofs} = ' ';
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
        $desc = spoilerWarningScript() . $desc;

    // return the result
    return output_encode($desc);
}

// Translate a stacked close tag to the actual HTML close tag
//
function closeTagForStackedTag($tag)
{
    switch ($tag)
    {
    case "spoiler":
        return "span";

    default:
        return $tag;
    }
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
        if ($str{$ofs} == '<') {
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
                            return array($ofs, $gt + 1);
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
        $c = $str{$ofs};
        
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
                    return array(substr($str, 0, $ofs) . "...",
                                 $outlen + 3, true);
                }
            }
            else if ($c == '&')
            {
                // if it's &lt;, &gt;, or &amp;, it's an entity
                $entTxt = substr($str, $ofs + 1, 7);
                if (strncasecmp($entTxt, "lt;", 3) == 0
                    || strncasecmp($entTxt, "gt;", 3) == 0
                    || strncasecmp($entTxt, "amp;", 4) == 0
                    || strncasecmp($entTxt, "quot;", 5) == 0
                    || preg_match("/^#[0-9]{1,5};/", $entTxt)) {

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
                    while ($ofs+1 < strlen($str) && $str{$ofs+1} == ' ')
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
                    return array(substr($str, 0, $lastBrk) . "...",
                                 $outlen + 3, true);
                }
            }
        }
    }

    // didn't find a truncation point - return the whole string
    return array($str, $outlen, false);
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
    // make sure we're logged in at the session level
	$curuser = $_SESSION['logged_in_as'];
	if (!$curuser)
    {
        redirect_to_login_page();
        return false;
    }
    
    // look up the user's account status in the database
    if (!($result = mysql_query("select acctstatus, profilestatus, sandbox from users where id='$curuser'", $db))
        || mysql_num_rows($result) == 0)
    {
        redirect_to_login_page();
        return false;
    }
    list($acctstatus, $profilestatus, $sandbox) = mysql_fetch_row($result);

    // don't allow editing if the user has been flagged as a troll
    if ($sandbox == 1) // troll sandbox
    {
        pageHeader("Service Unavailable");
        echo "This service is currently unavailable. We apologize for the inconvenience."
            . "<p>(Diagnostic information: code TCE0916)\n";
        pageFooter();
        return false;
    }

    // check if review is pending for a new account
    if ($profilestatus == 'R')
    {
        // pending review
        pageHeader("Account pending review");
        echo "Your new user account is still pending review. "
            . "Editing is not available until the account has "
            . "been approved.";
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
    $curuser = $_SESSION['logged_in_as'];
	if ($curuser) {
		$result = mysql_query("select sandbox from users where id='$curuser'", $db);
		list($sandbox) = mysql_fetch_row($result);
	}

	// figure the table based on the user's sandbox
	switch ($sandbox)
	{
	default:
	case 0:
	    // normal user - show only reviews and ratings from other normal users
		return "gameRatingsSandbox0";

	case 1:
		// troll - show ratings from normal users plus trolls
		return "gameRatingsSandbox01";
	}
}

// --------------------------------------------------------------------------
//
// Calculate a user's Frequent Fiction score
//
function userScore($uid)
{
    $db = dbConnect();
    $quid = mysql_real_escape_string($uid, $db);

    // start by setting up the temporary tables for the user scores
    createFFTempTable($db);

    // determine the requested user's score
    $result = mysql_query(
        "select score, rankingScore, reviewCount
         from userScores
         where userid = '$quid'", $db);
    if (mysql_num_rows($result) == 1)
        list($score, $rscore, $reviewCount) = mysql_fetch_row($result);
    else
        $score = $rscore = $reviewCount = 0;

    // Determine the user's rank: this is simply the number of users
    // with higher ranking scores, plus 1.
    $result = mysql_query(
        "select count(userid) as rank
         from userScores
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
    return array($score, $rank, $rankName);
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
    $tab = array();
    for ($i = 0 ; $i < count($arr) ; $i++) {

        // decompose this record
        $rec = $arr[$i];
        list($uid, $uanme, $score) = $rec;
        $n = ($i < 10 ? 10 : ($i < 25 ? 25 : ($i < 50 ? 50 : ($i < 100 ? 100 : 0))));
        $badge = ($n ? "Top $n Reviewer" : false);
    
        $tab[$uid] = array($score, $badge);
    }

    // return the table
    return $tab;
}
    
//
// Get an array of the top N reviewers, sorted in descending rank order.
//
function getTopReviewers($db, $n)
{
    // start by setting up the temporary tables for the user scores
    createFFTempTable($db);

    // Get the top N, sorted in descending rank order.  Only count
    // people who have actually written reviews.
    $result = mysql_query(
        "select
           s.userid, u.name, s.score
           from userScores as s join users as u on u.id = s.userid
           where s.reviewCount > 0
           order by s.score desc
           limit 0, $n", $db);

    // fetch the rows
    for ($i = 0, $nrows = mysql_num_rows($result), $arr = array() ;
         $i < $n && $i < $nrows ; ++$i)
        $arr[] = mysql_fetch_row($result);

    // return the user list
    return $arr;
}

//
// Set up temporary tables for calculating Frequent Fiction scores.
// This sets up the temp table UserScores (userid, score), giving each
// user's current FF score.  This can be used to get an individual user's
// score or to generate a list of the top N users.
//
function createFFTempTable($db)
{
    // $$$ obsolete - we have views for these now
    
//    static $ffCreated = 0;
//
//    // only do this once per http transaction
//    if ($ffCreated++)
//        return;
//    
//    // Set up a helper table with the various score components.
//    // Some of the components are non-trivial to calculate, so we
//    // pre-calculate them here to make it easy to add everything up
//    // per user.
//    mysql_query(
//        "create temporary table userScoreItems as "
//
//        // +100 points per review
//        //   +5 for each Helpful vote (max +100) on the review
//        //   -5 for each Unhelpful vote (min -100) on the review
//        . "select
//           reviews.userid as userid,
//           max(if(review is null, 10, 100)) 
//             + 5*(greatest(
//                  -100,
//                  least(
//                   100,
//                   ifnull(sum(reviewvotes.vote = 'Y'), 0)
//                   - ifnull(sum(reviewvotes.vote = 'N'), 0)))) as score,
//           max(if(review is null, 0, 1)) as isReview,
//           concat('review ', reviews.id) as scoreType
//        from
//           reviews
//           left outer join reviewvotes
//              on reviewvotes.reviewid = reviews.id
//           left outer join specialreviewers
//              on special = specialreviewers.id
//        where
//           (special is null or not editorial)
//           and ifnull(now() >= reviews.embargodate, 1)
//        group by
//           reviews.id "
//
//        // +1 point for each helpful/unhelpful vote we've cast
//        . "union all
//        select
//           userid,
//           count(vote) as score,
//           0 as isReview,
//           concat('vote ', reviewid) as scoreType
//        from
//           reviewvotes
//        group by
//           userid "
//
//        // +25 points per recommended list we've written with at least 5 items
//        . "union all
//        select
//           l.userid,
//           if (count(i.gameid) >= 5, 25, 0) as score,
//           0 as isReview,
//           concat('list ', l.id) as scoreType
//        from
//           reclists as l
//           left outer join reclistitems as i
//             on i.listid = l.id
//        group by
//           l.userid", $db);
//
//    // Create the temp table with each user's score.  This is simply
//    // a summation by user of the score components in the preliminary
//    // table we created above.
//    //
//    // The "ranking score" is the score, or zero for a user who hasn't
//    // written any reviews.  At least one review is required to be
//    // ranked as a Top N reviewer.
//    mysql_query(
//        "create temporary table userScores as
//         select
//             userid,
//             sum(score) as score,
//             max(isReview)*sum(score) as rankingScore,
//             sum(isReview) as reviewCount
//         from
//             userScoreItems
//         group by
//             userid", $db);
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
    $names = array_merge(array($names[$c-1] . ","),
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
// show a popup list for selecting an OS
//
define("OSPOP_NONE_OPTION",  0x0001);
define("OSPOP_GENERIC_VSNS", 0x0002);
define("OSPOP_DEFOS_OPTION", 0x0004);

function showOSPopup($db, $fldName, $curOS, $flags)
{
    // start the selector control
    echo "<select name=\"$fldName\" id=\"$fldName\">";

    // show the "None" option, if desired
    if ($flags & OSPOP_NONE_OPTION)
        echo "<option value=\"\"" . ($curOS == "" ? " selected" : "")
            . ">(None)</option>";

    $joinFilter = ($flags & OSPOP_GENERIC_VSNS
                   ? "" : "and osversions.name != '*'");
    $defFilter = ($flags & OSPOP_DEFOS_OPTION
                  ? "1" : "id != 0");

    // query up the OS list
    $result = mysql_query(
        "select
           concat(id, '.', ifnull(vsnid, '')),
           operatingsystems.name as osname,
           osversions.name,
           seq,
           displaypriority,
           if(id=1, 0, 1) as orderkey
         from
            operatingsystems 
            left outer join osversions
              on operatingsystems.id = osversions.osid $joinFilter
         where
            $defFilter
            and displaypriority >= 0
         order by
            orderkey, displaypriority desc, osname, seq", $db);
    
    // show each OS.version combination
    for ($i = 0 ; $i < mysql_num_rows($result) ; $i++) {
        list ($osid, $osname, $vsnname, $seq, $pri, $orderkey) =
            mysql_fetch_row($result);

        if ($vsnname == "*")
            $vsnname = "$osname (All Versions)";
        else if ($flags & OSPOP_GENERIC_VSNS)
            $vsnname = "&nbsp;&nbsp;&nbsp;$vsnname";

        echo "<option value=\"$osid\""
            . ($osid == $curOS ? " selected" : "")
            . ">"
            . ($vsnname == "" ? $osname : $vsnname)
            . "</option>";
    }

    // that's it for the selector control
    echo "</select>";
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
        $tentativeImageList = array();

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
// Fetch an image, given the ID.  Returns an array (image binary data,
// image format name).
//
function fetch_image($imageID, $getPic)
{
    // parse the image ID:  <dbnum>:<image key>
    list ($dbnum, $key) = explode(":", $imageID);

    // connect to the database encoded in the ID
    $db = imageDbConnect((int)$dbnum);
    if (!$db)
        return false;

    // set up to get or skip the image data stream
    $imgcol = ($getPic ? "img" : "null");

    // fetch the image at the given key
    $key = mysql_real_escape_string($key, $db);
    $result = mysql_query(
        "select $imgcol, format, copystat, copyright, userid
         from images where id='$key'", $db);

    // if we got a result, return it
    return mysql_fetch_row($result);
}

// --------------------------------------------------------------------------
// update an image's copyright status
function update_image_copyright($id, $userid, $date,
                                $copyStat, $copyMsg, &$errMsg)
{
    global $copyrightStatList;
    
    // parse the image ID:  <dbnum>:<image key>
    list ($dbnum, $key) = explode(":", $id);

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
    $vars = array();
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
    list ($dbnum, $key) = explode(":", $imageID);

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
    return strftime("%Y-%m-%d", $dt);
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
    list($numTables, $vsn, $ohdr) = ttf_unpack_header($data);

    // set up a list of valid IDs
    $validIDs = array('cmap', 'head', 'hhea', 'hmtx', 'maxp', 'name',
                      'OS/2', 'post', 'cvt ', 'fpgm', 'glyf', 'loca',
                      'prep', 'CFF ', 'VORG', 'EBDT', 'EBLC', 'EBSC',
                      'BASE', 'GDEF', 'GPOS', 'GSUB', 'JSTF', 'DSIG',
                      'gasp', 'hdmx', 'kern', 'LTSH', 'PCLT', 'VDMX',
                      'vhea', 'vmtx');

    // check the tables 
    for ($i = 0 ; $i < $numTables ; $i++) {

        // unpack the table header
        list($tid, $tsum, $tofs, $tlen) = ttf_unpack_dir($data, $i);

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
    list($numTables, $vsn, $ohdr) = ttf_unpack_header($data);
    $vsnName = ($vsn == 0x4F54544f ? "OpenType" : "TrueType");

    // find the Naming table (tag 'name')
    for ($i = 0 ; $i < $numTables ; $i++) {

        // unpack the table header
        list($tid, $tsum, $tofs, $tlen) = ttf_unpack_dir($data, $i);

        // if it's the naming table, parse it
        if ($tid == 'name') {
            // get the number of naming table entries
            $b = unpack("ncnt", substr($data, $tofs + 2, 2));
            $nameCnt = $b['cnt'];

            // scan the entries for names #1 (family name) and #2 (sub name)
            $names = array();
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

    return array($numTables, $vsn, $ohdr);
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
    return array($tid, $tsum, $tofs, $tlen);
}

// ------------------------------------------------------------------------
//
// Create an administrative nonce value.  Nonces are basically randomly
// generated passwords for single-purpose, single-use admin tasks.  These
// allow the system to send email to the admin with links that carry out
// selected tasks.  The nonces in the email are only valid for the specific
// task for which they're created, so if the email is intercepted, the
// interceptor can only perform the tasks linked in the email, and can't
// use them for any other purpose.  Per the usual protocol for passwords,
// the nonces themselves are not stored in the database; instead, we store
// the hashed value.  As a further precaution, nonces are timestamped so
// that we can expire them if they're not used quickly enough.
//
// 'id' is an arbitrary string identifying the task.  This usually includes
// a brief descriptive name for the task and the primary key for the record
// that the task updates.  For example, for the profile review task, the
// ID might look like "review user profile j13t0913ng3190", where that last
// bit is the TUID for the user record.
//
// Returns the plaintext nonce value to include in the email link.  Returns
// false if the nonce could not be inserted into the database.
//
function create_nonce($db, $id)
{
    // generate a random nonce value
    $nonce = md5_rand($id);

    // calculate the hash value for storage
    $hash = sha1("$id:$nonce");

    // quote strings
    $qid = mysql_real_escape_string($id, $db);
    $qhash = mysql_real_escape_string($hash, $db);

    // insert the row
    $result = mysql_query(
        "insert into nonces (nonceid, hash, created)
         values ('$qid', '$qhash', now())", $db);

    // return the nonce on success, or false if we couldn't insert the row
    return ($result ? $nonce : false);
}

// Validate a nonce.  On success, we'll delete the nonce from the database
// (since each nonce is for a single use only) and return true.  On failure,
// we'll fill in $errmsg with an error message and return false.
function validate_nonce($db, $id, $nonce, &$errmsg, $singleUse)
{
	// note if this is a "review user profile" task
	$reviewUserProfile = preg_match("/^review user profile /", $id);
	
    // calculate the hash value for the nonce
    $hash = sha1("$id:$nonce");

    // quote values
    $qid = mysql_real_escape_string($id, $db);
    $qhash = mysql_real_escape_string($hash, $db);

    // clean up expired nonces first
    $result = mysql_query(
        "delete from nonces where now() > created + interval 7 day", $db);
    if (!$result) {
        $errmsg = "Error deleting expired nonces.";
        return false;
    }

    // search for a record matching this nonce
    $result = mysql_query(
        "select * from nonces
         where nonceid = '$qid' and hash = '$qhash'", $db);

    // make sure we found a row
    if (mysql_num_rows($result) == 0)
	{
		// Check for session validation.  If this is a user review
		// request, and the session has been validated for a previous
		// user review, allow further user reviews without the nonce.
		if ($reviewUserProfile && $_SESSION['admin_for_user_review'])
		{
			// It's a user profile review - allow it.  If we're
			// deleting a nonce, look up the actual hash value.
			if ($singleUse)
			{
				$result = mysql_query(
					"select hash from nonces where nonceid = '$qid'", $db);
				list($qhash) = mysql_fetch_row($result);
			}
		}
		else
		{
			// authorized for the session even without nonces
			$errFlagged = false;
			$errmsg = "The nonce value was not found.";
			return false;
		}
    }

	// validate the session
	if ($reviewUserProfile)
		$_SESSION['admin_for_user_review'] = true;

    // if it's a single-use nonce, delete it
    if ($singleUse)
    {
        $result = mysql_query(
            "delete from nonces
             where nonceid = '$qid' and hash = '$qhash'", $db);
        if (!$result) {
            $errmsg = "Error deleting the used nonce from the database.";
            return false;
        }
    }

    // success
    return true;
}

// ------------------------------------------------------------------------
//
// Generate a random MD5 string.  This concatenates the given "key" string
// with a number of system randomness sources and MD5-hashes the result.
//
function md5_rand($key)
{
    return md5(rand() . $key . mt_rand() . time() . microtime() . mt_rand());
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
    // quote parameters
    $quid = mysql_real_escape_string($uid, $db);

    // no table locks yet
    $tableLocks = false;

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
    $result = mysql_query(
        "update users
         set acctstatus = '$stat' $setClosed
         where id = '$quid'", $db);

    // cancel any persistent sessions
    if ($result) {
        $progress = "deleting user's persistent login sessions";
        $result = mysql_query(
            "delete from persistentsessions where userid='$quid'", $db);
    }

    // delete discussions, part 1: delete postings by user
    if ($result) {
        $progress = "deleting user's discussions";
        $result = mysql_query(
            "delete from ucomments
             where userid = '$quid'", $db);
    }

    // delete discussions, part 2: delete posting on this user's profile
    if ($result) {
        $progress = "deleting comments on user's profile";
        $result = mysql_query(
            "delete from ucomments
             where source = 'U' and sourceid = '$quid'", $db);
    }

    // delete comments on this user's review comments
    if ($result) {
        $progress = "deleting comments on user's reviews";
        $result = mysql_query(
            "delete from ucomments
             where source = 'R' and sourceid in
               (select id from reviews where userid = '$quid')", $db);
    }

    // delete this user's reviews
    if ($result) {
        $progress = "deleting user's reviews";
        $result = mysql_query(
            "delete from reviews, reviewtags, reviewvotes
             using reviews
               left outer join reviewtags
                 on reviewtags.reviewid = reviews.id
               left outer join reviewvotes
                 on reviewvotes.reviewid = reviews.id
             where reviews.userid = '$quid'", $db);
    }

    // delete review flags by user
    if ($result) {
        $progress = "deleting user's review flags";
        $result = mysql_query(
            "delete from reviewflags where flagger = '$quid'", $db);
    }

    // delete review votes by user
    if ($result) {
        $progress = "deleting user's review votes";
        $result = mysql_query(
            "delete from reviewvotes where userid = '$quid'", $db);
    }

    // lock tables for deleting tags - we need atomic writes to games
    // and gametags
    if ($result) {
        $progress = "locking games, gametags";
        $result = mysql_query(
            "lock tables games write, gametags write,
                gametags as gt1 read, gametags as gt2 read", $db);

        if ($result)
            $tableLocks = true;
    }

    // rebuild the tag list for any games this user has tagged, without
    // this user's tags
    if ($result) {
        $progress = "rebuilding games.tags for deleting user's game tags";
        $result = mysql_query(
            "update games
             set tags = (
               select group_concat(tag separator ',')
               from gametags as gt1
               where gameid = id and userid != '$quid')
             where id in (
               select distinct gameid
               from gametags as gt2
               where userid = '$quid')", $db);
    }

    // delete this user's tags
    if ($result) {
        $progress = "deleting user's game tags";
        $result = mysql_query(
            "delete from gametags
             where userid = '$quid'", $db);
    }

    // done with the table locks
    if ($result) {
        $progress = "unlocking games, gametags";
        $result = mysql_query("unlock tables", $db);
        $tableLocks = false;
    }

    // delete cross-recommendations
    if ($result) {
        $progress = "deleting user's cross-recommendations";
        $result = mysql_query(
            "delete from crossrecs where userid = '$quid'", $db);
    }

    // delete wish lists, play lists, and unwishlists
    if ($result) {
        $progress = "deleting user's wishlist";
        $result = mysql_query(
            "delete from wishlists where userid = '$quid'", $db);
    }
    if ($result) {
        $progress = "deleting user's played games list";
        $result = mysql_query(
            "delete from playedgames where userid = '$quid'", $db);
    }
    if ($result) {
        $progress = "deleting user's un-wishlist";
        $result = mysql_query(
            "delete from unwishlists where userid = '$quid'", $db);
    }

    // delete the user's recommended list comments
    if ($result) {
        $progress = "deleting user's recommended list comments";
        $result = mysql_query(
            "delete from ucomments
             where source = 'L' and sourceid in
               (select id from reclists where userid = '$quid')", $db);
    }

    // delete the user's recommended lists
    if ($result) {
        $progress = "deleting user's recommended lists";
        $result = mysql_query(
            "delete from reclists, reclistitems
             using reclists
               left outer join reclistitems
                 on reclistitems.listid = reclists.id
             where reclists.userid = '$quid'", $db);
    }

    // delete the user's poll comments
    if ($result) {
        $progress = "deleting user's poll list comments";
        $result = mysql_query(
            "delete from ucomments
             where source = 'P' and sourceid in
               (select pollid from polls where userid = '$quid')", $db);
    }

    // delete the user's polls
    if ($result) {
        $progress = "deleting user's polls";
        $result = mysql_query(
            "delete from polls, pollvotes, pollcomments
             using polls
               left outer join pollvotes
                 on pollvotes.pollid = polls.pollid
               left outer join pollcomments
                 on pollcomments.pollid = polls.pollid
             where polls.userid = '$quid'", $db);
    }

    // delete the user's poll votes
    if ($result) {
        $progress = "deleting user's poll votes";
        $result = mysql_query(
            "delete from pollvotes where userid = '$quid'", $db);
    }

    // delete the user's news items
    if ($result) {
        $progress = "deleting user's news items";
        $result = mysql_query(
            "delete from news where userid = '$quid'", $db);
    }

    // delete the user's stylesheets
    if ($result) {
        $progress = "deleting user's stylesheets";
        $result = mysql_query(
            "delete from stylesheets where userid = '$quid'", $db);
    }

    // delete the user's stylesheet images
    if ($result) {
        $progress = "deleting user's stylesheet graphics";
        $result = mysql_query(
            "delete from stylepics where userid = '$quid'", $db);
    }

    // delete club memberships
    if ($result) {
        $progress = "deleting user's club memberships";
        $result = mysql_query(
            "delete from clubmembers where userid = '$quid'", $db);
    }

    // note the database error
    if (!$result)
        $progress .= " (db error: " . htmlspecialcharx(mysql_error($db)) . ")";

    // unlock tables, if we haven't already
    if ($tableLocks)
        mysql_query("unlock tables");

    // return the success/failure indication
    return $result;
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
        $htab = array();
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
        $userid = $_SESSION['logged_in_as'];
        $hdrs = "From: IFDB <noreply@ifdb.tads.org>\r\n"
                . "Content-type: Text/HTML\r\n";
        mail("ifdbadmin@ifdb.tads.org", "IFDB hyperlink review",
             "User: <a href=\"https://ifdb.tads.org/showuser?id=$userid\">$userid</a><br>\n"
             . "Context: <a href=\"https://ifdb.tads.org/{$contextLink}\">$context</a><br>\n"
             . "Text:<br>\n<br>\n"
             . $txt,
             $hdrs);
    }
}

function isProduction() {
    return $_SERVER['SERVER_NAME'] === PRODUCTION_SERVER_NAME;
}

function isLocalDev() {
    return !isProduction();
}

?>