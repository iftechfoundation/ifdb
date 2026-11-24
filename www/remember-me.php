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
    // the table only stores a hash of the token
    $cookie = random_token(32);

    // add an entry to the session table for this user, unless the password
    // has changed since this session logged in
    $result = mysqli_execute_query($db,
        "insert into persistentsessions (id, userid, lastlogin)
         select ?, id, now() from users
         where id = ? and sha2(password, 256) = ?
           and acctstatus in ('A', 'D')
         lock in share mode",
        [sha256($cookie), $usernum, $_SESSION['pswver'][$usernum] ?? '']);
    if (!$result || mysqli_affected_rows($db) != 1)
        return;

    set_remember_me_cookie($cookie);
}

define("PERSISTENT_SESSION_DAYS", 365);

function remember_me_key()
{
    $cookie = $_COOKIE['IFDBSessionID'] ?? '';
    return is_string($cookie) && $cookie !== '' ? sha256($cookie) : '';
}

function set_remember_me_cookie($cookie)
{
    if (headers_sent())
        return;
    setcookie("IFDBSessionID", $cookie, [
        "expires" => time() + 60*60*24*PERSISTENT_SESSION_DAYS,
        "path" => "/",
        "secure" => !isLocalDev(),
        "httponly" => true,
        "samesite" => "lax",
    ]);
}

function remembered_login()
{
    // check for the session ID
    if (isset($_COOKIE['IFDBSessionID'])) {
        // set up the database connection
        $db = dbConnect();
        if (!$db)
            return [false, null];

        // see if we can find the session ID in the persistent session table
        $key = remember_me_key();
        $result = mysqli_execute_query($db,
            "select p.userid, u.password
             from persistentsessions p join users u on u.id = p.userid
             where p.id = ? and u.acctstatus in ('A', 'D')
               and p.lastlogin > date_sub(now(), interval "
               . PERSISTENT_SESSION_DAYS . " day)", [$key]);
        if (mysql_num_rows($result) > 0) {
            // Got it - the user is logged in.  Fetch the user ID.
            $userid = mysql_result($result, 0, "userid");
            $pswhash = mysql_result($result, 0, "password");

            // update the last login timestamp for the persistent session
            mysqli_execute_query($db, "update persistentsessions set lastlogin = now()
                where id = ?", [$key]);

            // also update the last login time in the user record
            $result = mysqli_execute_query($db, "update users set lastlogin = now()
                where id = ?", [$userid]);

            set_remember_me_cookie($_COOKIE['IFDBSessionID']);

            return [$userid, $pswhash];
        }
    }

    // no remembered login
    return [false, null];
}

function forget_remembered_logins($db, $userid, $keepCurrent = false)
{
    $keep = $keepCurrent ? remember_me_key() : '';
    return mysqli_execute_query($db,
        "delete from persistentsessions where userid = ? and id <> ?",
        [$userid, $keep]);
}

?>
