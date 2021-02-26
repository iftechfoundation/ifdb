<?php

include_once "util.php";
include_once "login-persist.php";

function basePageHeader($title, $focusCtl, $extraOnLoad, $extraHead,
                        $ckbox, $bodyAttrs)
{
//<?xml version="1.0" encoding="iso-8859-1" ? >
//<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
//  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
//<html xmlns="http://www.w3.org/1999/xhtml">

// $$$ UTF8 encoding - NOT CURRENTLY ACTIVE: 
// iconv_set_encoding("output_encoding", "UTF-8");
// NOTE: if changing to UTF-8, also change the <meta> below to set
// the charset clause in the Content_Type parameter to UTF-8.

// instead, use ISO-8859-1
	ini_set("default_charset", "ISO-8859-1");
    iconv_set_encoding("output_encoding", "ISO-8859-1");
    iconv_set_encoding("input_encoding", "ISO-8859-1");
?>
<html>
<head>
   <link rel="icon" type="image/png" href="/ifdb-favicon.png">
   <link rel="shortcut icon" href="/favicon.ico">
   <link rel="search" type="application/opensearchdescription+xml"
         title="IFDB Search Plugin"
         href="<?php echo get_root_url() ?>plugins/ifdb-opensearchdesc.xml">
   <script src="ifdbutil.js"></script>
   <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
   <meta name="description" content="IFDB is a game catalog and recommendation engine for Interactive Fiction, also known as Text Adventures. IFDB is a collaborative, wiki-style community project.  Members can contribute game listings, reviews, recommendations, and more.">
   <title><?php echo $title ?></title>
   <?php echoStylesheetLink(); ?>
   <?php echo $extraHead ?>
   <?php
       // add the checkbox javascript if requested
       if ($ckbox)
           ckboxSetup();

       // add iPod/iPhone parameters if applicable
       if (is_ipod_or_iphone())
           echo "<meta name=\"viewport\" content=\"width=device-width\">"
               . "<meta name=\"viewport\" content=\"initial-scale=1.0\">";

   ?>
</head>
<body<?php

    if ($focusCtl) {
        echo " onLoad=\"document.$focusCtl.focus();
            document.$focusCtl.select();$extraOnLoad\"";
    } else if ($extraOnLoad) {
        echo " onLoad=\"$extraOnLoad\"";
    }

    if ($bodyAttrs)
        echo " $bodyAttrs";
     ?>>
<?php
}

function helpWinHRef($href)
{
    return "href=\"$href\" target=\"IFDBHelp\" "
        . "onclick=\"javascript:helpWin('$href');return false;\"";
}

function helpWinLink($href, $text)
{
    $href = helpWinHRef($href);
    return "<a $href>$text</a>";
}

function pageHeader($title, $focusCtl = false, $extraOnLoad = false,
                    $extraHead = false, $ckbox = false, $bodyAttrs = "")
{
    // show the basic header
    basePageHeader($title, $focusCtl, $extraOnLoad, $extraHead,
                   $ckbox, $bodyAttrs);

    // get the actual current page
    $pagescript = basename($_SERVER['SCRIPT_FILENAME']);
    parse_str($_SERVER['QUERY_STRING'], $query);
    checkPersistentLogin();
    $curuser = ((isset($_SESSION['logged_in']) && $_SESSION['logged_in'])
                ? $_SESSION['logged_in_as'] : false);
    $curarrow = "<img src=\"/blank.gif\" class=\"topbarcurarrow\">";
    $homearrow = $profarrow = $editprofarrow = $yourarrow = $commentarrow =
        false;
    switch ($pagescript) {
    case "home":
        $homearrow = $curarrow;
        break;

    case "showuser":
        if (!isset($query['id']) || $query['id'] == $curuser)
            $profarrow = $curarrow;
        break;

    case "editprofile":
        $editprofarrow = $curarrow;
        break;

    case "personal":
        $yourarrow = $curarrow;
        break;

    case "commentlog":
        $commentarrow = $curarrow;
        break;
    }

    // add the top bar for a regular window
?>
<div class="topbar">
   <div class="topbar-right">
      <a class="topbar" href="/"><!--
         <img src="/ifdb-topbar.jpg" border=0> --></a>
   </div>
</div>

<div class="topctl">
   <form method="get" action="/search" name="search">
      <table width="100%" border=0 cellspacing=0 cellpadding=0>
         <tr valign=baseline>
            <td align=left>
               <?php echo $homearrow ?>
               <a href="/">Home</a>
               | <?php echo $profarrow ?><a href="/showuser">Profile</a>
               - <?php echo $editprofarrow ?><a href="/editprofile">Edit</a>
               | <?php echo $yourarrow ?><a href="/personal"><b>Your Page</b></a>
               | <?php echo $commentarrow ?><a href="/commentlog?mode=inbox">
                  Your Inbox</a>
            </td>
            <td align=right>
               <a href="/search?browse">Browse</a> |
               <a href="/search">Search</a> Games
               <input type="text" size=30 name="searchbar" value="">
               <input type=image src="/blank.gif"
                   class="go-button" id="topbar-search-go-button"
                   name="searchGo"
                   style="margin:0 0 0 0;padding:0 0 0 0;">
               &nbsp; | &nbsp; <?php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'])
    echo "<a href=\"/logout\">Log Out</a>";
else
    echo "<a href=\"/login\">Log In</a>";
               ?>
            </td>
         </tr>
      </table>
   </form>
</div>

<div class="main">
<?php
}

function pageFooter()
{
?>

<div class="footer">
<a class="nav" href="/">IFDB Home</a> |
<a class="nav" href="http://www.tads.org/">TADS.org</a> |
<a class="nav" href="/contact">Contact Us</a> |
<a class="nav" href="/code-of-conduct">Code of Conduct</a> |
<a class="nav" href="/tos">Terms of Service</a> |
<a class="nav" href="/privacy">Privacy</a> |
<a class="nav" href="/copyright">Copyrights &amp; Trademarks</a>
</a></div>

</div>
</body>
</html>

<?php
}

function smallPageHeader($title, $focusCtl = false, $extraOnLoad = false,
                         $extraHead = false)
{
    basePageHeader($title, $focusCtl, $extraOnLoad, $extraHead, "", "");
    echo "<div class=\"smalltopbar\">"
        // <img src=\"/ifdb-smalltopbar.jpg\">
        . "</div><div class=\"smallmain\">";
}

function smallPageFooter()
{
    echo "</div></body></html>";
}

function errExit($msg)
{
    pageHeader("Error");
    echo $msg;
    pageFooter();
    exit();
}

function varPageHeader($title, $focusCtl, $smallPage,
                       $extraOnLoad = false, $extraHead = false)
{
    if ($smallPage)
        smallPageHeader($title, $focusCtl, $extraOnLoad, $extraHead);
    else
        pageHeader($title, $focusCtl, $extraOnLoad, $extraHead);
}

function varPageFooter($smallPage)
{
    if ($smallPage)
        smallPageFooter();
    else
        pageFooter();
}

function helpPageHeader($title)
{
    basePageHeader($title, false, false, false, false, "");
    echo "<div class=\"smalltopbar\">"
        // <img src=\"/ifdb-smalltopbar.jpg\">
        . "</div><div class=\"helpmain\">";
}

function helpPageFooter()
{
}

// --------------------------------------------------------------------------
// Insert the <script> code for setting up for active checkboxes.
// 
function ckboxSetup()
{
?>
<script type="text/javascript">
<!--

var ckboxStatus = [];
function ckboxGetObj(id)
{
    var stat = ckboxStatus[id];
    if (stat == null)
    {
        var img = document.getElementById('ckImg' + id);
        ckboxStatus[id] = stat = new Object();
        stat.checked = (img.className == "ckbox-checked"
                        || img.className == "radio-checked");
    }
    return stat;
}
function ckboxGetLabel(id) { return document.getElementById('ckLbl' + id); }
function ckboxGetImage(id) { return document.getElementById('ckImg' + id); }

function ckboxCheck(id, isRadio, checked)
{
    var img = ckboxGetImage(id);
    var stat = ckboxGetObj(id);
    stat.checked = checked;
    img.className = (isRadio
               ? (checked ? "radio-checked" : "radio-unchecked")
               : (checked ? "ckbox-checked" : "ckbox-unchecked"));
}
function ckboxIsChecked(id)
{
    var stat = ckboxGetObj(id);
    return stat.checked;
}
function ckboxOver(id, isRadio)
{
    var img = ckboxGetImage(id);
    var lbl = ckboxGetLabel(id);
    var stat = ckboxGetObj(id);
    img.className = (isRadio ? "radio-hovering" : "ckbox-hovering");
    lbl.style.textDecoration = "underline";
}
function ckboxLeave(id, isRadio)
{
    var img = ckboxGetImage(id);
    var lbl = ckboxGetLabel(id);
    var stat = ckboxGetObj(id);
    img.className = (isRadio
                     ? (stat.checked ? "radio-checked" : "radio-unchecked")
                     : (stat.checked ? "ckbox-checked" : "ckbox-unchecked"));
    lbl.style.textDecoration = "none";
}

var ckboxReq, ckboxReqID;
function ckboxClick(id, isRadio, onUpdateFunc)
{
    var stat = ckboxGetObj(id);
    var newchecked = (isRadio ? true : !stat.checked);
    if (isRadio && stat.checked)
        return;
    ckboxCheck(id, isRadio, newchecked);
    if (onUpdateFunc)
        onUpdateFunc(id, newchecked);
}
function ckboxKey(id, event, isRadio, onUpdateFunc)
{
    var ch = (window.event || event.keyCode ? event.keyCode : event.which);
    if (ch == 32)
        ckboxClick(id, isRadio, onUpdateFunc);
}
//-->
</script>
<?php
}

// --------------------------------------------------------------------------
// Generate a checkbox
//
function ckRbString($id, $label, $checked, $onUpdateFunc, $isRadio)
{
    $label = htmlspecialcharx($label);
    return "<span class=\"cklabel\" "
        . "onmouseover=\"javascript:ckboxOver('$id', $isRadio);return true;\" "
        . "onmouseout=\"javascript:ckboxLeave('$id', $isRadio);return true;\" "
        . "onclick=\"javascript:ckboxClick('$id', $isRadio, $onUpdateFunc);"
        . "return false;\"><img src=\"/blank.gif\" class=\""
        . ($isRadio
           ? ($checked ? "radio-checked" : "radio-unchecked")
           : ($checked ? "ckbox-checked" : "ckbox-unchecked"))
        . "\" id=\"ckImg$id\"> "
        . "<span id=\"ckLbl$id\"><a class=silent href=\"needjs\" "
        . "onkeypress=\"javascript:ckboxKey("
        . "'$id', event, $isRadio, $onUpdateFunc);return false;\""
        . ">$label</a></span>"
        . "</span>";
}
function ckRbWrite($id, $label, $checked, $onUpdateFunc, $isRadio)
{
    echo ckRbString($id, $label, $checked, $onUpdateFunc, $isRadio);
}
function checkboxWrite($id, $label, $checked, $onUpdateFunc)
{
    ckRbWrite($id, $label, $checked, $onUpdateFunc, 0);
}
function radioBtnWrite($id, $label, $checked, $onUpdateFunc)
{
    ckRbWrite($id, $label, $checked, $onUpdateFunc, 1);
}

?>