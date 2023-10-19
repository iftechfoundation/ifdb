<?php

include_once "csp-nonce.php";
include_once "util.php";
include_once "login-persist.php";

function srcCacheBust($filename)
{
    $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $filename);
    return "$filename?t=$mtime";
}

function scriptSrc($filename)
{
    return "<script src=\"" . srcCacheBust($filename) . "\"></script>";
}

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
<html lang="en">
<head>
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/site.webmanifest">
    <link rel="shortcut icon" href="/favicon.ico">
    <meta name="theme-color" content="#000000">

   <link rel="search" type="application/opensearchdescription+xml"
         title="IFDB Search Plugin"
         href="<?php echo get_root_url() ?>plugins/ifdb-opensearchdesc.xml">
   <script src="<?php echo srcCacheBust('/ifdbutil.js')?>"></script>
   <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
   <meta name="description" content="IFDB is a game catalog and recommendation engine for Interactive Fiction, also known as Text Adventures. IFDB is a collaborative, wiki-style community project.  Members can contribute game listings, reviews, recommendations, and more.">
   <title><?php echo $title ?></title>
   <?php echoStylesheetLink(); ?>
   <?php echo $extraHead ?>
   <?php
       // add the checkbox javascript if requested
       if ($ckbox)
           ckboxSetup();
   ?>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <?php
   if ($focusCtl) {
        $extraOnLoad = "document.$focusCtl.focus();document.$focusCtl.select();$extraOnLoad";
   }
   if ($extraOnLoad) {
        global $nonce;
        echo "<script type='text/javascript' nonce='$nonce'>\n";
        echo "addEventListener('load', function () { $extraOnLoad });\n";
        echo "</script>";
   }
   ?>
</head>
<body<?php
    if ($bodyAttrs)
        echo " $bodyAttrs";
     ?>>
<?php
}

function helpWinLink($href, $text)
{
    return "<a href=\"$href\" target=\"IFDBHelp\">"
        . addEventListener("click", "helpWin('$href');return false;")
        . "$text</a>";
}

function pageHeader($title, $focusCtl = false, $extraOnLoad = false,
                    $extraHead = false, $ckbox = false, $bodyAttrs = "")
{
    checkPersistentLogin();
    $isLoggedIn = (isset($_SESSION['logged_in']) && $_SESSION['logged_in']);

    // show the basic header
    basePageHeader($title, $focusCtl, $extraOnLoad, $extraHead,
                   $ckbox, $bodyAttrs);

    // get the actual current page
    $pagescript = basename($_SERVER['SCRIPT_FILENAME']);
    parse_str($_SERVER['QUERY_STRING'], $query);
    $curuser = ((isset($_SESSION['logged_in']) && $_SESSION['logged_in'])
                ? $_SESSION['logged_in_as'] : false);
    // add the top bar for a regular window
?>

<div class="topctl">
    <a href="/" aria-label="Home">
        <div class="topbar"></div>
    </a>
    <div id="main-nav-wrapper">
        <nav id="main-nav" class="main-nav">
            <div class="nav-left">
                <a id="topbar-home" href="/">Home</a>
                <a id="topbar-browse" href="/search?browse">Browse</a>
                <form class= "searchbar-wrapper" method="get" action="/search" name="search">
                        <input id="topbar-searchbar" type="text" name="searchbar" placeholder="Search for games...">
                        <button class="" id="topbar-search-go-button" aria-label="Search">
                            <img src="/img/search_small.svg" alt="">
                        </button>
                </form>
            </div>
            <div class="nav-right">
                <ul>
                <?php if ($isLoggedIn) : ?>
                    <li class="<?= ($pagescript === 'showuser') ? 'page-active':''; ?>"><a id="topbar-profile" href="/showuser">Profile</a></li>
                    <li class="<?= ($pagescript === 'editprofile') ? 'page-active':''; ?>"><a id="topbar-edit" href="/editprofile">Settings</a></li>
                    <li class="<?= ($pagescript === 'personal') ? 'page-active':''; ?>"><a id="topbar-personal" href="/personal">My Activity</a></li>
                    <li class="<?= ($pagescript === 'commentlog') ? 'page-active':''; ?>"><a id="topbar-inbox" href="/commentlog?mode=inbox">Inbox</a></li>
                    <li><a id="topbar-logout" class="login-link" href="/logout">Log Out</a></li>
                <?php else : ?>
                    <li><a id="topbar-login" class="login-link" href="/login?dest=home">Log In</a></li>
                <?php endif ?>
                </ul>
            </div>
        </nav>
    <?php if ($isLoggedIn) : ?>
        <button type="button" id="mobile-menu-toggle-button" class="menu-toggle-button hidden" aria-label="Menu">
            <img src="/img/menu.svg" alt="" class="mobile-hidden">
            <img src="/img/close.svg" alt="">
            <span>Menu</span>
        </button>
    <?php endif ?>
    </div>
</div>

<script nonce="<?php global $nonce; echo $nonce; ?>">
    function ToggleMobileMenu() {
        document.querySelector('#main-nav ul').classList.toggle('mobile-hidden');
        document.querySelector('.login-link').classList.toggle('mobile-hidden');
        document.querySelectorAll('#mobile-menu-toggle-button img').forEach(item => item.classList.toggle('mobile-hidden'));
    }

    (()=> {

    // The mobile menu should be closed by default
    ToggleMobileMenu();

    // If javascript is enabled, un-hide the mobile menu button & add the 'mobile-menu' class to the main nav wrapper,
    const toggle = document.querySelector('#mobile-menu-toggle-button');
    if (toggle) {
        toggle.addEventListener('click', function () { ToggleMobileMenu(); });    
        toggle.classList.remove('hidden');
        document.querySelector('#main-nav-wrapper').classList.add('mobile-menu');
    }

})()

</script>

<div class="main">
<?php
}

function pageFooter()
{
?>

<div class="footer">
<a class="nav" id="footer-home" href="/">IFDB Home</a> |
<a class="nav" id="footer-tads" href="http://www.tads.org/">TADS.org</a> |
<a class="nav" id="footer-contact" href="/contact">Contact Us</a> |
<a class="nav" id="footer-coc" href="/code-of-conduct">Code of Conduct</a> |
<a class="nav" id="footer-tos" href="/tos">Terms of Service</a> |
<a class="nav" id="footer-privacy" href="/privacy">Privacy</a> |
<a class="nav" id="footer-copyright" href="/copyright">Copyrights &amp; Trademarks</a>
</div>

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
<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
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

// this function is intended to create a <script> tag to replace inline event attributes
function addEventListener($event, $code) {
    global $nonce;
    return "<script nonce='$nonce'>\n" .
        "document.currentScript.parentElement.addEventListener('$event', function (event) {\n" .
        "var result = (function(){ $code }).apply(event.target);\nif (result === false) event.preventDefault();" .
        "\n});\n" .
        "</script>";
}

function addSiblingEventListeners($listeners) {
    global $nonce;
    $result = "<script nonce='$nonce'>\n";
    foreach ($listeners as $listener) {
        $result .= "document.currentScript.previousElementSibling.addEventListener('".$listener[0]."', function (event) {\n" .
        "var result = (function(){ ". $listener[1] ." }).apply(event.target);\nif (result === false) event.preventDefault();" .
        "\n});\n";
    }
    $result .= "</script>";
    return $result;
}

// --------------------------------------------------------------------------
// Generate a checkbox
//
function ckRbString($id, $label, $checked, $onUpdateFunc, $isRadio)
{
    $label = htmlspecialcharx($label);
    echo "<span class=\"cklabel\" >"
        . addEventListener("mouseover", "ckboxOver('$id', $isRadio);")
        . addEventListener("mouseout", "ckboxLeave('$id', $isRadio);")
        . addEventListener("click", "ckboxClick('$id', $isRadio, $onUpdateFunc); return false")
        . "<img src=\"/img/blank.gif\" class=\""
        . ($isRadio
           ? ($checked ? "radio-checked" : "radio-unchecked")
           : ($checked ? "ckbox-checked" : "ckbox-unchecked"))
        . "\" id=\"ckImg$id\"> "
        . "<span id=\"ckLbl$id\"><a class=silent href=\"needjs\">"
        . addEventListener("keypress", "ckboxKey('$id', event, $isRadio, $onUpdateFunc); return false")
        . "$label</a></span>"
        . "</span>";
}
function ckRbWrite($id, $label, $checked, $onUpdateFunc, $isRadio)
{
    ckRbString($id, $label, $checked, $onUpdateFunc, $isRadio);
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
