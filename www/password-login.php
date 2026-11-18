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
    [$usernum, $stat, $pswhash] = check_credentials($db, $username, $password);

    if ($stat != 'A') {
        if ($stat == 'D') {
            start_provisional_session($usernum, $pswhash);
        } else if ($usernum) {
            log_out($db);
        }
        [$errCode, $errMsg] = login_error($stat);
        return [false, $errCode, $errMsg];
    }

    record_login($db, $usernum);

    start_login_session($usernum, $pswhash);

    // success
    return [$usernum, null, null];
}

// Log in for a single API request without changing the stored session.
function log_in_for_api_request($db, $username, $password)
{
    [$usernum, $stat, $pswhash] = check_credentials($db, $username, $password);
    if ($stat != 'A') {
        [$errCode, $errMsg] = login_error($stat);
        return [false, $errCode, $errMsg];
    }

    record_login($db, $usernum);

    session_write_close();
    start_login_session($usernum, $pswhash);

    return [$usernum, null, null];
}

// Returns [user ID, account status, password hash] if the password
// matches, or [null, 'too-long', null] or [null, 'no-match', null] if it
// doesn't.
function check_credentials($db, $username, $password)
{
    if (strlen($password) > MAX_PASSWORD_LENGTH)
        return [null, 'too-long', null];

    // set up a quoted version of the user ID
    $quid = mysql_real_escape_string($username, $db);

    // look it up
    $result = mysql_query(
        "select id, password, pswsalt, acctstatus
        from users where email = '$quid'", $db);
    if (mysql_num_rows($result) == 0)
        return [null, 'no-match', null];

    // verify that it matches what's stored in the database
    $pswhash = mysql_result($result, 0, "password");
    if (!password_matches($password, $pswhash,
                          mysql_result($result, 0, "pswsalt")))
        return [null, 'no-match', null];

    $usernum = mysql_result($result, 0, "id");
    if (password_needs_upgrade($pswhash) && password_can_be_hashed($password)) {
        $newhash = hash_password($password);
        mysqli_execute_query($db,
            "update users set password = ? where id = ? and password = ?",
            [$newhash, $usernum, $pswhash]);
        if (mysqli_affected_rows($db) != 1)
            return [null, 'no-match', null];
        $pswhash = $newhash;
    }

    return [$usernum, mysql_result($result, 0, "acctstatus"), $pswhash];
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

// bcrypt ignores everything after 72 bytes, so prefer Argon2id
define("PASSWORD_ALGO",
       defined("PASSWORD_ARGON2ID") ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
define("MAX_HASHABLE_LENGTH",
       PASSWORD_ALGO == PASSWORD_BCRYPT ? 72 : MAX_PASSWORD_LENGTH);

function hash_password($password)
{
    return password_hash($password, PASSWORD_ALGO);
}

// Legacy hashes are salted SHA-1 hex strings
function password_matches($password, $hash, $salt)
{
    if (password_get_info($hash)['algo'] === PASSWORD_BCRYPT
        && strlen($password) > 72)
        return false;
    if (str_starts_with($hash, '$'))
        return password_verify($password, $hash);
    return hash_equals($hash, sha1($salt . $password));
}

function password_needs_upgrade($hash)
{
    return !str_starts_with($hash, '$')
        || password_needs_rehash($hash, PASSWORD_ALGO);
}

function password_can_be_hashed($password)
{
    return strlen($password) <= MAX_HASHABLE_LENGTH
        && strpos($password, "\0") === false;
}

// Returns an error message, or false if the new password is acceptable.
function new_password_error($psw, $psw2)
{
    if (strlen($psw) < 6)
        return "The password is too short - it must be at least
            six characters long.";
    if (strlen($psw) > MAX_HASHABLE_LENGTH)
        return "Your password is too long - please use no more than "
            . MAX_HASHABLE_LENGTH . " characters.";
    if (strpos($psw, "\0") !== false)
        return "Your password contains an invalid character.";
    if (strcmp($psw, $psw2) != 0)
        return "Password mismatch - you must enter the
            <b>identical</b> password two times to ensure that you
            didn't make any mistakes typing it.";
    return false;
}

// Sets the password and forgets the account's remembered logins.  Given
// $oldhash, the password must still be $oldhash, and this browser's
// remembered login is kept.  Returns the new hash, or false on failure.
function set_password($db, $userid, $psw, $oldhash = null)
{
    $hashpsw = hash_password($psw);

    // lock so that no remember-me login sees the new password while the
    // old logins remain
    $ok = mysql_query("lock tables users write, persistentsessions write", $db)
        && forget_remembered_logins($db, $userid, $oldhash !== null)
        && mysqli_execute_query($db,
            "update users set password = ?
             where id = ? and (? is null or password = ?)",
            [$hashpsw, $userid, $oldhash, $oldhash])
        && mysqli_affected_rows($db) == 1;
    mysql_query("unlock tables", $db);
    return $ok ? $hashpsw : false;
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
