<?php

include_once "session-start.php";

include_once "dbconnect.php";
include_once "util.php";

function checkPersistentLogin()
{
    // if we're already logged in, there's no need to check for a persistent
    // session
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) {
        $userid = $_SESSION['logged_in_as'];
        if (check_banned($userid)) return false;
        return $userid;
    }

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


function check_banned($userid) {
    $db = dbConnect();
    $result = mysqli_execute_query($db,
        "select 1 from users where id = ? and acctstatus = 'B'", [$userid]);
    $banned = mysql_num_rows($result) > 0;
    if ($banned) {
        error_log("user $user_id is banned; logging out");
        $_SESSION['logged_in'] = false;
        $_SESSION['logged_in_as'] = null;
        unset($_SESSION['provisional_logged_in_as']);
    }
    return $banned;
}

?>
