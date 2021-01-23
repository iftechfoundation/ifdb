<?php

include_once "dbconnect.php";
include_once "util.php";

function checkPersistentLogin()
{
    // if we're already logged in, there's no need to check for a persistent
    // session
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true)
        return $_SESSION['logged_in_as'];

    // check for the session ID
    if (isset($_COOKIE['IFDBSessionID'])) {
        // set up the database connection
        $db = dbConnect();
        if (!$db)
            return false;

        // see if we can find the session ID in the persistent session table
        $key = $_COOKIE['IFDBSessionID'];
        $key = mysql_real_escape_string($_COOKIE['IFDBSessionID'], $db);
        $result = mysql_query("select userid from persistentsessions
            where id = '$key'", $db);
        if (mysql_num_rows($result) > 0) {
            // Got it - the user is logged in.  Fetch the user ID.
            $userid = mysql_result($result, 0, "userid");

            // update the last login timestamp for the persistent session
            mysql_query("update persistentsessions set lastlogin = now()
                where id = '$key'", $db);

            // also update the last login time in the user record
            $result = mysql_query("update users set lastlogin = now()
                where id = '$userid'", $db);

            // save the user credentials in the session
            $_SESSION['logged_in'] = true;
            $_SESSION['logged_in_as'] = $userid;
            unset($_SESSION['provisional_logged_in_as']);
            
            // clear the recommendation cache
            shoot_recommendation_cache();

            // return the user number
            return $userid;
        }
    }

    // not logged in
    return false;
}

?>
