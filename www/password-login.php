<?php

include_once "current-user.php";
include_once "dbconnect.php";
include_once "util.php";

// ------------------------------------------------------------------------
// Log in the given username identified by the given password.  This
// sets up the session login as though the user had logged in manually
// via the login form.
//
// On success, sets up the session parameters to record the login, and
// returns an array:
//
//    (user ID)
//
// You can tell that the login was successful if the first element of
// the array is not false.
//
// On failure, returns an array:
//
//    (false, errorCode, errorMessage)
//
// errorCode is a short-form error message, in plain text format;
// errorMessage is the long-form message, in HTML format, suitable for
// display in the error message slot in the interactive login form.
//
function log_in_with_password($db, $username, $password)
{
    [$usernum, $stat] = check_credentials($db, $username, $password);

    if ($stat != 'A') {
        if ($stat == 'D') {
            start_provisional_session($usernum);
        } else if ($usernum) {
            log_out($db);
        }
        [$errCode, $errMsg] = login_error($stat);
        return [false, $errCode, $errMsg];
    }

    record_login($db, $usernum);

    start_login_session($usernum);

    // success
    return [$usernum, null, null];
}

// Log in for a single API request without changing the stored session.
function log_in_for_api_request($db, $username, $password)
{
    [$usernum, $stat] = check_credentials($db, $username, $password);
    if ($stat != 'A') {
        [$errCode, $errMsg] = login_error($stat);
        return [false, $errCode, $errMsg];
    }

    record_login($db, $usernum);

    session_write_close();
    start_login_session($usernum);

    return [$usernum, null, null];
}

// Returns [user ID, account status] if the password matches, or
// [null, 'too-long'] or [null, 'no-match'] if it doesn't.
function check_credentials($db, $username, $password)
{
    if (strlen($password) > MAX_PASSWORD_LENGTH)
        return [null, 'too-long'];

    // set up a quoted version of the user ID
    $quid = mysql_real_escape_string($username, $db);

    // look it up
    $result = mysql_query(
        "select id, password, pswsalt, acctstatus
        from users where email = '$quid'", $db);
    if (mysql_num_rows($result) == 0)
        return [null, 'no-match'];

    // generate the hash of the password as entered by the user
    $pswsalt = mysql_result($result, 0, "pswsalt");
    $hashpsw = sha1($pswsalt . $password);

    // verify that it matches what's stored in the database
    if (strcmp($hashpsw, mysql_result($result, 0, "password")) != 0)
        return [null, 'no-match'];

    return [mysql_result($result, 0, "id"),
            mysql_result($result, 0, "acctstatus")];
}

// Returns [errorCode, errorMessage] for a login that check_credentials()
// didn't accept.
function login_error($reason)
{
    switch ($reason) {
    case 'too-long':
        return ["incorrect username or password",
            "The password is too long. If this is your password, please reset it by using the \"Lost Password\" page."];

    case 'no-match':
        return ["incorrect username or password",
            "The email address and password you entered don't match "
            . "our records."];

    case 'D':
        return ["this account has not yet been activated",
            "You have not yet activated this account. Please "
            . "check your email inbox for a confirmation message, "
            . "and follow the instructions in the message to "
            . "activate your account."
            . "<p>Even though your account isn't activated yet, "
            . "you can still <a href=\"editprofile\">"
            . "edit your profile</a>."];

    case 'R':
        return ["this account is pending review",
            "This account is pending review. Activation "
            . "instructions will be sent to the registered "
            . "email address when the review has been "
            . "completed."];

    case 'B':
    case 'X':
        $statName = ($reason == 'B' ? "disabled" : "closed");
        return ["this account has been $statName",
            "This account has been $statName. Please contact "
            . "the site's administrators for assistance. "
            . "(<a href=\"contact\">Contact information</a>)"];

    default:
        return ["this account is not active",
            "Our records for this account appear to "
            . "have an internal error.  Please contact the site's "
            . "administrators for assistance. "
            . "(<a href=\"contact\">Contact information</a>)"];
    }
}

function record_login($db, $usernum)
{
    // update the last-login time for the user in the database
    mysql_query("update users set lastlogin = now()
        where id = '$usernum'", $db);

    // make a note of the last login time, and the incoming IP address
    $ip = $_SERVER['REMOTE_ADDR'];
    mysql_query("insert into logins (uid, ip, `when`)
        values ('$usernum', '$ip', now())", $db);
}

?>
