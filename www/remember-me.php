<?php

include_once "session-start.php";
include_once "util.php";

// ------------------------------------------------------------------------
// Set the persistent login cookie.  This must be called before showing
// any other information on the page, since cookies must be sent with the
// header information in the reply.
//
function setLoginCookie($db, $usernum, $ip)
{
    // generate a crypto-random identifier for the session - this
    // effectively becomes a password, so we need to make it hard
    // to guess; use a secure hash of some volatile values
    $cookie = sha1(md5_rand($ip) . md5_rand($ip) . md5_rand($ip));

    // add an entry to the session table for this user
    $result = mysql_query(
        "insert into persistentsessions
         (id, userid, lastlogin)
         values ('$cookie', '$usernum', now())", $db);

    // set the cookie in the browser
    setcookie("IFDBSessionID", $cookie, [
        "expires" => time() + 60*60*24*365*10,
        "path" => "/",
        "secure" => !isLocalDev(),
        "httponly" => true,
        "samesite" => "lax",
    ]);
}

?>
