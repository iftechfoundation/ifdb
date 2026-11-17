<?php

include_once "session-start.php";
include_once "dbconnect.php";
include_once "login-links.php";
include_once "remember-me.php";

function current_user_id()
{
    // if we're already logged in, there's no need to check for a persistent
    // session
    static $revoked = null;
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) {
        $userid = $_SESSION['logged_in_as'];
        if (!is_login_revoked($userid))
            return $userid;
        error_log("login for user $userid revoked; logging out");
        $_SESSION['logged_in'] = false;
        $_SESSION['logged_in_as'] = null;
        $revoked = $userid;
    }

    // don't switch to a different remembered account mid-request
    [$userid, $pswhash] = remembered_login();
    if (!$userid || ($revoked && $userid != $revoked))
        return false;
    start_login_session($userid, $pswhash);
    return $userid;
}

// Logins end when the account is banned, closed or pending review, or
// when the password has changed since the session logged in.
function is_login_revoked($userid)
{
    $db = dbConnect();
    $result = mysqli_execute_query($db,
        "select acctstatus, password from users where id = ?", [$userid]);
    $row = mysql_fetch_row($result);
    return !$row || ($row[0] != 'A' && $row[0] != 'D')
        || ($_SESSION['pswver'][$userid] ?? null) !== password_version($row[1]);
}

function password_version($pswhash)
{
    return hash('sha256', $pswhash);
}

function require_login($smallPage = false)
{
    $userid = current_user_id();
    if (!$userid) {
        redirect_to_login($smallPage);
        exit();
    }
    return $userid;
}

// Accounts pending activation can still edit their profile.
function require_login_or_provisional($smallPage = false)
{
    $userid = $_SESSION['provisional_logged_in_as'] ?? null;
    if (!$userid)
        return require_login($smallPage);
    if (!is_login_revoked($userid))
        return $userid;

    unset($_SESSION['provisional_logged_in_as']);
    redirect_to_login($smallPage);
    exit();
}

function start_login_session($userid, $pswhash)
{
    $_SESSION['logged_in'] = true;
    $_SESSION['logged_in_as'] = $userid;
    $_SESSION['pswver'][$userid] = password_version($pswhash);
    unset($_SESSION['provisional_logged_in_as']);
}

function start_provisional_session($userid, $pswhash)
{
    $_SESSION['provisional_logged_in_as'] = $userid;
    $_SESSION['pswver'][$userid] = password_version($pswhash);
}

function end_login_session()
{
    $_SESSION['logged_in'] = false;
    $_SESSION['logged_in_as'] = null;
    unset($_SESSION['provisional_logged_in_as'], $_SESSION['pswver']);
}

function log_out($db)
{
    $userid = $_SESSION['logged_in_as'] ?? null;
    if ($userid)
        forget_remembered_logins($db, $userid);
    end_login_session();
}

?>
