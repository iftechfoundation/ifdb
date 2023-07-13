<?php

include_once "../pagetpl.php";
include_once "../util.php";
include_once "../login-persist.php";
checkPersistentLogin();

function apiPageHeader($title)
{
    pageHeader("$title (IFDB API)");

    global $nonce;
    echo "<style nonce='$nonce'>\n"
        . ".api__header, .api__footer { margin-top: 2em; }\n"
        . "</style>\n";


    echo "<div class='details api__header'>"
        . "<a href='index'>IFDB APIs</a> &gt; $title"
        . "</div>"
        . "<h1>The $title API</h1>";
}

function apiPageFooter()
{
    echo "<div class='details api__footer'>"
        . "<a href='index'>API Index</a>"
        . "</div>";
    pageFooter();
}


?>
