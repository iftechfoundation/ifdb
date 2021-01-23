<?php

include_once "login-persist.php";

function logInAndReturnQuery()
{
    // come back to the current page when we're done
    $dest = urlencode($_SERVER['PHP_SELF']);

    // add the query portion of the current URL as well
    if (isset($_SERVER['QUERY_STRING'])) {
        // add the destination string
        $dest .= urlencode("?" . $_SERVER['QUERY_STRING']);

        // add the referrer as well
        if (isset($_SERVER['HTTP_REFERER']))
            $dest .= urlencode("&httpreferer=" .
                               urlencode($_SERVER['HTTP_REFERER']));
    }

    // return the result
    return $dest;
}

function switchUserAndReturnHRef()
{
    return "login?switch&dest=" . logInAndReturnQuery();
}

function logInAndReturnHRef($smallPage = false)
{
    $href = "login?dest=" . logInAndReturnQuery();
    if ($smallPage)
        $href .= "&small=1";
    return $href;
}

function provisionally_logged_in()
{
    return (isset($_SESSION['provisional_logged_in_as']));
}

function logged_in($smallPage = false)
{
    // if we have the session flag indicating we're logged in, we're good
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true)
        return true;

    // look for a persistent session
    $userid = checkPersistentLogin();
    if ($userid)
        return true;

    // redirect to the login page
    redirect_to_login_page();
    return false;
}

function redirect_to_login_page()
{
    $href = logInAndReturnHRef($smallPage);
    header("HTTP/1.1 301 Moved Permanently");
    header("Content-Type: text/html");
    header("Location: $href");

    echo "Redirecting to $href<br><br>";
    echo "<a href=\"$href\">Click here if your browser doesn't redirect
        automatically</a>";
}

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
function doLogin($db, $username, $password)
{
    // set up a quoted version of the user ID
    $quid = mysql_real_escape_string($username, $db);

    // look it up
    $result = mysql_query(
        "select id, password, pswsalt, acctstatus
        from users where email = '$quid'", $db);
    if (mysql_num_rows($result) == 0) {
        return array(false, "incorrect username or password",
                     "The email address and password you entered don't match "
                     . "our records.");
    }

    // generate the hash of the password as entered by the user
    $pswsalt = mysql_result($result, 0, "pswsalt");
    $hashpsw = sha1($pswsalt . $password);

    // verify that it matches what's stored in the database
    if (strcmp($hashpsw, mysql_result($result, 0, "password")) != 0) {
        return array(false, "incorrect username or password",
                     "The email address and password you entered don't match "
                     . "our records.");
    }

    // verify that the account is activated
    $stat = mysql_result($result, 0, "acctstatus");
    if ($stat == 'D')
    {
        // set the provisional login status
        $_SESSION['provisional_logged_in_as'] = mysql_result($result, 0, "id");

        return array(false, "this account has not yet been activated",
                     "You have not yet activated this account. Please "
                     . "check your email inbox for a confirmation message, "
                     . "and follow the instructions in the message to "
                     . "activate your account."
                     . "<p>Even though your account isn't activated yet, "
                     . "you can still <a href=\"editprofile\">"
                     . "edit your profile</a>.");
    }
    if ($stat == 'R') {
        $_SESSION['logged_in'] = false;
        $_SESSION['logged_in_as'] = null;
        unset($_SESSION['provisional_logged_in_as']);

        return array(false, "this account is pending review",
                     "This account is pending review. Activation "
                     . "instructions will be sent to the registered "
                     . "email address when the review has been "
                     . "completed.");
    }

    // verify that the account isn't disabled or closed
    if ($stat == 'B' || $stat == 'X') {
        $statName = ($stat == 'B' ? "disabled" : "closed");
        $_SESSION['logged_in'] = false;
        $_SESSION['logged_in_as'] = null;
        unset($_SESSION['provisional_logged_in_as']);

        return array(false, "this account has been $statname",
                     "This account has been $statName. Please contact "
                     . "the site's administrators for assistance. "
                     . "(<a href=\"contact\">Contact information</a>)");
    }

    // verify that the account has a valid Active code
    if ($stat != 'A') {
        $_SESSION['logged_in'] = false;
        $_SESSION['logged_in_as'] = null;
        unset($_SESSION['provisional_logged_in_as']);

        return array(false, "this account is not active",
                     "Our records for this account appear to "
                     . "have an internal error.  Please contact the site's "
                     . "administrators for assistance. "
                     . "(<a href=\"contact\">Contact information</a>)");
    }

    // note the user ID
    $usernum = mysql_result($result, 0, "id");

    // update the last-login time for the user in the database
    mysql_query("update users set lastlogin = now()
        where id = '$usernum'", $db);

    // make a note of the last login time, and the incoming IP address
    $ip = $_SERVER['REMOTE_ADDR'];
    mysql_query("insert into logins (uid, ip, `when`)
        values ('$usernum', '$ip', now())", $db);

    // It all looks good - we're logged in!  Set the session status.
    $_SESSION['logged_in'] = true;
    $_SESSION['logged_in_as'] = $usernum;
    unset($_SESSION['provisional_logged_in_as']);

    // clear the recommendation cache
    shoot_recommendation_cache();

    // success
    return array($usernum);
}

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
    setcookie("IFDBSessionID", $cookie, time() + 60*60*24*365*10, "/",
              isLocalDev() ? "" : ".tads.org",
              false, true);
}

?>