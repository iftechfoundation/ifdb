<?php

include_once "../pagetpl.php";
include_once "../util.php";
include_once "../login-persist.php";
checkPersistentLogin();

function apiPageHeader($title)
{
    pageHeader("$title (IFDB API)");

    echo "<div style='margin-top: 2em;' class='details'>"
        . "<a href='index'>IFDB APIs</a> &gt; $title"
        . "</div>"
        . "<h1>The $title API</h1>";
}

function apiPageFooter()
{
    echo "<div style='margin-top: 2em;' class='details'>"
        . "<a href='index'>API Index</a>"
        . "</div>";
    pageFooter();
}


?>