<?php

include_once "session-start.php";
include_once "dbconnect.php";
include_once "util.php";

// ------------------------------------------------------------------------
// Set the persistent login cookie.  This must be called before showing
// any other information on the page, since cookies must be sent with the
// header information in the reply.
//
function remember_login($db, $usernum, $ip)
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

function remembered_user_id()
{
    // check for the session ID
    if (isset($_COOKIE['IFDBSessionID'])) {
        // set up the database connection
        $db = dbConnect();
        if (!$db)
            return false;

        // see if we can find the session ID in the persistent session table
        $key = $_COOKIE['IFDBSessionID'];
        $result = mysqli_execute_query($db, "select userid from persistentsessions
            where id = ?", [$key]);
        if (mysql_num_rows($result) > 0) {
            // Got it - the user is logged in.  Fetch the user ID.
            $userid = mysql_result($result, 0, "userid");

            // update the last login timestamp for the persistent session
            mysqli_execute_query($db, "update persistentsessions set lastlogin = now()
                where id = ?", [$key]);

            // also update the last login time in the user record
            $result = mysqli_execute_query($db, "update users set lastlogin = now()
                where id = ?", [$userid]);

            // return the user number
            return $userid;
        }
    }

    // no remembered login
    return false;
}

?>
