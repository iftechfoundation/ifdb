<?php

include_once "session-start.php";
include_once "dbconnect.php";
include_once "login-links.php";
include_once "remember-me.php";

function current_user_id()
{
    // if we're already logged in, there's no need to check for a persistent
    // session
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) {
        $userid = $_SESSION['logged_in_as'];
        if (log_out_if_blocked($userid)) return false;
        return $userid;
    }

    $userid = remembered_user_id();
    if ($userid)
        start_login_session($userid);
    return $userid;
}

function log_out_if_blocked($userid) {
    $db = dbConnect();
    $result = mysqli_execute_query($db,
        "select 1 from users where id = ? and acctstatus = 'B'", [$userid]);
    $banned = mysql_num_rows($result) > 0;
    if ($banned) {
        error_log("user $user_id is banned; logging out");
        end_login_session();
    }
    return $banned;
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
    return $_SESSION['provisional_logged_in_as'] ?? require_login($smallPage);
}

function start_login_session($userid)
{
    $_SESSION['logged_in'] = true;
    $_SESSION['logged_in_as'] = $userid;
    unset($_SESSION['provisional_logged_in_as']);
}

function start_provisional_session($userid)
{
    $_SESSION['provisional_logged_in_as'] = $userid;
}

function end_login_session()
{
    $_SESSION['logged_in'] = false;
    $_SESSION['logged_in_as'] = null;
    unset($_SESSION['provisional_logged_in_as']);
}

function log_out($db)
{
    $userid = $_SESSION['logged_in_as'] ?? null;
    if ($userid)
        forget_remembered_logins($db, $userid);
    end_login_session();
}

?>
