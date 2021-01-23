<?php
global $mirrorUrlPref, $mirrorID;

// cache the current user's URL preference - this must be called once,
// before mapping any URLs
function cacheUrlPref($db, $uid, $requestMirrorID = false)
{
    global $mirrorUrlPref, $mirrorIDPref;

    // presume we won't find a URL
    $mirrorIDPref = "";
    $mirrorUrlPref = "";

    // if there's a user ID, look for a preference setting
    if ($uid) {
        // query the mirror URL for the current user
        $quid = mysql_real_escape_string($uid, $db);
        $result = mysql_query(
            "select
               mirrors.mirrorid as id, mirrors.baseurl as baseurl
             from mirrors
               join users on mirrors.mirrorid = users.mirrorid
             where
               users.id = '$quid'", $db);

        // if we got a URL, cache it in a global for later use
        if ($result && mysql_num_rows($result) > 0) {
            $mirrorIDPref = mysql_result($result, 0, "id");
            $mirrorUrlPref = mysql_result($result, 0, "baseurl");
        }
    }

    // if there was a mirror ID specified in the request, use it if
    // it refers to a valid mirror
    if ($requestMirrorID) {

        // look for a mirror matching the given ID
        $qid = mysql_real_escape_string($requestMirrorID, $db);
        $result = mysql_query(
            "select baseurl from mirrors where mirrorid = '$qid'", $db);

        if (mysql_num_rows($result) > 0) {
            // got it - override the preference settings
            $mirrorIDPref = $qid;
            $mirrorUrlPref = mysql_result($result, 0, "baseurl");
        }
    }

    // If we still don't have a mirror, try the default mirror ID 100.
    if ($mirrorIDPref == "") {

        // make sure default mirror ID 100 is valid
        $qid = 100;
        $result = mysql_query(
            "select baseurl from mirrors where mirrorid = '$qid'", $db);

        if (mysql_num_rows($result) > 0) {
            // got it - use this mirror
            $mirrorIDPref = $qid;
            $mirrorUrlPref = mysql_result($result, 0, "baseurl");
        }
    }
}

// translate a URL - if the URL points to the IF Archive, we'll rewrite it
// to point to the user's current mirror preference
function urlToMirror($url)
{
    global $mirrorUrlPref;

    // if we have a mirror preference, look for the main IF Archive prefix;
    // if we find it, replace it with the mirror preference setting
    $prefix = "http://www.ifarchive.org/if-archive/";
    $prelen = strlen($prefix);
    if ($mirrorUrlPref != "" && substr($url, 0, $prelen) == $prefix)
        $url = $mirrorUrlPref . substr($url, $prelen);

    // return what we found
    return $url;
}


?>