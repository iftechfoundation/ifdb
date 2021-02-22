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

<script type="text/javascript">
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
<script type="text/javascript">
<!--
var starRatings = [];
function isMouseInStarCtl(id, e)
{
    var x, y;
    if (e.pageX) {
        x = e.pageX;
        y = e.pageY;
    } else if (e.clientX) {
        x = e.clientX
            + (document.documentElement.scrollLeft
               ? document.documentElement.scrollLeft
               : document.body.scrollLeft);
        y = e.clientY
            + (document.documentElement.scrollTop
               ? document.documentElement.scrollTop
               : document.body.scrollTop);
    }
    var ox = x, oy = y;
    var msg = "";
    for (i = 0 ; i <= 5 ; i++) {
        x = ox;
        y = oy;
        var sEle = document.getElementById(id + "_" + i);
        if (sEle.style.display == "inline") {
            for (var ele = sEle ; ele != document.body ;
                 x -= ele.offsetLeft, y -= ele.offsetTop,
                 ele = ele.offsetParent) ;
            if (x >= 0 && x <= sEle.offsetWidth
                && y >= 0 && y <= sEle.offsetHeight)
                return true;
        }
    }
    return false;
}
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
    var sEle;
    for (i = 0 ; i <= 5 ; i++) {
        sEle = document.getElementById(id + "_" + i);
        if (sEle.style.display == "inline")
            break;
    }
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
    if (!isMouseInStarCtl(id, e)) {
        var stars = starRatings[id];
        showStarCtlValue(id, stars);
        if (cbFunc != null)
            cbFunc(stars);
    }
}
function setStarCtlValue(id, val)
{
    starRatings[id] = val;
    showStarCtlValue(id, val);
}
function showStarCtlValue(id, val)
{
    var i;
    document.getElementById(id + "_" + val).style.display = "inline";
    for (i = 0 ; i <= 5 ; i++) {
        if (i != val)
            document.getElementById(id + "_" + i).style.display = "none";
    }
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

        $str = "<select id=\"$id\" "
               . "onchange=\"javascript:mouseClickStarCtl("
               .   "'$id', event, $clickFunc);\" "
               . "onblur=\"javascript:mouseOutStrCtl("
               .   "'$id', event, $leaveFunc);\">";
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

        $str = "<script type=\"text/javascript\">\r\n"
               . "<!--\r\n"
               . "starRatings['$id'] = $init;\r\n"
               . "//-->\r\n"
               . "</script>\r\n";
        
        for ($i = 0 ; $i <= 5 ; $i++)
            $str .= showStarCtlImg($id, $i, $init, $clickFunc, $leaveFunc);
    }

    return $str;
}

function showStarCtlImg($id, $val, $init, $clickFunc, $leaveFunc)
{
    return "<img id=\"{$id}_{$val}\" "
        . "style=\"vertical-align:middle;cursor:pointer;"
        . "display:" . ($val == $init ? "inline" : "none") . ";\""
        . "onmouseover=\"javascript:mouseOverStarCtl('$id', event);\" "
        . "onmousemove=\"javascript:mouseOverStarCtl('$id', event);\" "
        . "onmouseout=\"javascript:mouseOutStarCtl('$id', event, $leaveFunc);\" "
        . "onclick=\"javascript:mouseClickStarCtl('$id', event, $clickFunc);\" "
        . "src=\"blank.gif\" class=\"star$val\">";
}

?>