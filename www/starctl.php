<?php

include_once "session-start.php";
include_once "dbconnect.php";
include_once "login-persist.php";

$curuser = checkPersistentLogin();

function showStarCtl($id)
{
    global $nonce;
    
    $str = "<div id=\"$id\" class=\"star-container\" role=\"group\">";
    // Button 0 is required for the CSS subsequent-sibling selectors to work.
    $str .= "<button data-value=\"0\"></button>";
    for ($i = 1; $i <= 5; $i++) {
        $str .= "<button data-value=\"$i\" role=\"button\" aria-label=\"Rate $i out of 5\">";
        $str .= "</button>";
    }
    $str .= "</div>";

    return $str;
}

?>
