<?php

include_once "session-start.php";
include_once "dbconnect.php";
include_once "login-persist.php";

$db = dbConnect();
$curuser = checkPersistentLogin();
global $accessibility;
$accessibility = false;

if ($curuser) {
    $result = mysql_query(
        "select accessibility from users where id='$curuser'", $db);
    list($accessibility) = mysql_fetch_row($result);
}

function initStarControls()
{
    global $accessibility;

    if ($accessibility) {
        // Accessible version - use a drop list for the rating selector.

        ?>

<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--
function mouseClickStarCtl(id, e, clickFunc)
{
    var stars = document.getElementById(id).value;
    clickFunc(stars);
}
function mouseOutStarCtl(id, e, cbFunc)
{
    var stars = document.getElementById(id).value;
    if (cbFunc != null)
        cbFunc(stars);
}
function setStarCtlValue(id, val)
{
    document.getElementById(id).value = val;
}
//-->
</script>

        <?php

    } else {
        // Standard version - use the animated javascript star control,
        // with automatic mouse rollover highlighting.

        ?>
<span style="position:absolute;width:1px;left:-10000px;">To rate games using a keyboard, please visit the settings page and turn on the "Use Accessible Controls" setting.</span>
<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--
var starRatings = {};
function starsFromMouse(id, e)
{
    var x;
    if (e.pageX)
        x = e.pageX;
    else if (e.clientX)
        x = e.clientX
            + (document.documentElement.scrollLeft
               ? document.documentElement.scrollLeft
               : document.body.scrollLeft);
    var sEle = document.getElementById(id);
    for (var ele = sEle ; ele != document.body ;
         x -= ele.offsetLeft, ele = ele.offsetParent) ;

    var stars = Math.round(x/(sEle.offsetWidth/5) + 0.5);
    return (stars < 1 ? 1 : stars > 5 ? 5 : stars);
}
function mouseOverStarCtl(id, e)
{
    var stars = starsFromMouse(id, e);
    showStarCtlValue(id, stars);
}
function mouseClickStarCtl(id, e, clickFunc)
{
    var stars = starRatings[id] = starsFromMouse(id, e);
    showStarCtlValue(id, stars);
    clickFunc(starRatings[id]);
}
function mouseOutStarCtl(id, e, cbFunc)
{
   var stars = starRatings[id];
   showStarCtlValue(id, stars);
   if (cbFunc != null)
       cbFunc(stars);
}
function setStarCtlValue(id, val)
{
    starRatings[id] = val;
    showStarCtlValue(id, val);
}
function showStarCtlValue(id, val)
{
    document.getElementById(id).className = "star" + val;
}
//-->
</script>
        <?php
    }
}

function showStarCtl($id, $init, $clickFunc, $leaveFunc)
{
    global $accessibility;

    if (!$init)
        $init = 0;

    if ($accessibility) {
        // accessible version - use a simple drop list

        $str = "<select id=\"$id\">"
               . addEventListener('change', "mouseClickStarCtl('$id', event, $clickFunc);")
               . addEventListener('blur', "mouseOutStarCtl('$id', event, $leaveFunc);")
               ;
        $disps = array("Not Rated", "1 Star", "2 Stars", "3 Stars",
                       "4 Stars", "5 Stars");
        for ($i = 0 ; $i <= 5 ; $i++) {
            $str .= "<option value=\"$i\"";
            if ($i == $init)
                $str .= " selected";
            $str .= ">{$disps[$i]}</option>";
        }
        $str .= "</select>";

    } else {
        // standard version - use the star images
        global $nonce;
        $str = "<script type=\"text/javascript\" nonce=\"$nonce\">\r\n"
               . "<!--\r\n"
               . "starRatings['$id'] = $init;\r\n"
               . "//-->\r\n"
               . "</script>\r\n"
               . "<style nonce='$nonce'>\n"
               . "#$id { vertical-align:middle;cursor:pointer; display: inline; }\n"
               . "</style>\n";

        $str .= "<img id=\"{$id}\" "
                . "src=\"img/blank.gif\" class=\"star$init\">"
                . addSiblingEventListeners([
                    ['mouseover', "mouseOverStarCtl('$id', event);"],
                    ['mousemove', "mouseOverStarCtl('$id', event);"],
                    ['mouseout', "mouseOutStarCtl('$id', event, $leaveFunc);"],
                    ['click', "mouseClickStarCtl('$id', event, $clickFunc);"],
                ]);
    }

    return $str;
}

?>
