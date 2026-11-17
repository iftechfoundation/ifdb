<?php

include_once "session-start.php";
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
    [$usernum, $errCode, $errMsg, $acctid, $stat] =
        check_credentials($db, $username, $password);

    if (!$usernum) {
        if ($stat == 'D') {
            $_SESSION['provisional_logged_in_as'] = $acctid;
        } else if ($stat !== null) {
            $_SESSION['logged_in'] = false;
            $_SESSION['logged_in_as'] = null;
            unset($_SESSION['provisional_logged_in_as']);
        }
        return [false, $errCode, $errMsg];
    }

    record_login($db, $usernum);

    // It all looks good - we're logged in!  Set the session status.
    $_SESSION['logged_in'] = true;
    $_SESSION['logged_in_as'] = $usernum;
    unset($_SESSION['provisional_logged_in_as']);

    // success
    return [$usernum, null, null];
}

// Log in for a single API request without changing the stored session.
function log_in_for_api_request($db, $username, $password)
{
    [$usernum, $errCode, $errMsg] =
        check_credentials($db, $username, $password);
    if (!$usernum)
        return [false, $errCode, $errMsg];

    record_login($db, $usernum);

    session_write_close();
    $_SESSION['logged_in'] = true;
    $_SESSION['logged_in_as'] = $usernum;

    return [$usernum, null, null];
}

// Returns the same values as log_in_with_password(), plus the account ID
// and status when the password matched.  Doesn't touch the session.
function check_credentials($db, $username, $password)
{
    if (strlen($password) > MAX_PASSWORD_LENGTH) {
        return [false, "incorrect username or password",
            "The password is too long. If this is your password, please reset it by using the \"Lost Password\" page.", null, null];
    }

    // set up a quoted version of the user ID
    $quid = mysql_real_escape_string($username, $db);

    // look it up
    $result = mysql_query(
        "select id, password, pswsalt, acctstatus
        from users where email = '$quid'", $db);
    if (mysql_num_rows($result) == 0) {
        return [false, "incorrect username or password",
            "The email address and password you entered don't match "
            . "our records.", null, null];
    }

    // generate the hash of the password as entered by the user
    $pswsalt = mysql_result($result, 0, "pswsalt");
    $hashpsw = sha1($pswsalt . $password);

    // verify that it matches what's stored in the database
    if (strcmp($hashpsw, mysql_result($result, 0, "password")) != 0) {
        return [false, "incorrect username or password",
            "The email address and password you entered don't match "
            . "our records.", null, null];
    }

    $acctid = mysql_result($result, 0, "id");

    // verify that the account is activated
    $stat = mysql_result($result, 0, "acctstatus");
    if ($stat == 'D')
    {
        return [false, "this account has not yet been activated",
            "You have not yet activated this account. Please "
            . "check your email inbox for a confirmation message, "
            . "and follow the instructions in the message to "
            . "activate your account."
            . "<p>Even though your account isn't activated yet, "
            . "you can still <a href=\"editprofile\">"
            . "edit your profile</a>.", $acctid, $stat];
    }
    if ($stat == 'R') {
        return [false, "this account is pending review",
            "This account is pending review. Activation "
            . "instructions will be sent to the registered "
            . "email address when the review has been "
            . "completed.", $acctid, $stat];
    }

    // verify that the account isn't disabled or closed
    if ($stat == 'B' || $stat == 'X') {
        $statName = ($stat == 'B' ? "disabled" : "closed");

        return [false, "this account has been $statName",
            "This account has been $statName. Please contact "
            . "the site's administrators for assistance. "
            . "(<a href=\"contact\">Contact information</a>)", $acctid, $stat];
    }

    // verify that the account has a valid Active code
    if ($stat != 'A') {
        return [false, "this account is not active",
            "Our records for this account appear to "
            . "have an internal error.  Please contact the site's "
            . "administrators for assistance. "
            . "(<a href=\"contact\">Contact information</a>)", $acctid, $stat];
    }

    return [$acctid, null, null, $acctid, $stat];
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
