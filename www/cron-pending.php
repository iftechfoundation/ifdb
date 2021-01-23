<?php

include_once "dbconnect.php";
include_once "util.php";

$db = dbConnect();
if (!$db)
    $output[] = "Unable to connect to database";

// Check for the -q option: don't show any output if it's set and there's
// nothing interesting to report.
$showOutput = true;
for ($i = 0 ; $i < $argc ; $i++) {
    if ($argv[$i] == "-q")
        $showOutput = false;
}

// start the output
$output = array();
$output[] = "IFDB Pending Download Scan";
$output[] = "";

// get the list of pending links
$result = mysql_query(
    "select
       l.gameid, g.title, l.title, l.url, l.displayorder, l.attrs
     from
       gamelinks as l
       join games as g on g.id = l.gameid
     where
       (l.attrs & " . GAMELINK_PENDING . ")", $db);

if (!$result) {
    $showOutput = true;
    $output[] = "MySQL error in SELECT: " . mysql_error($db);
}

// process the results
$rowcnt = mysql_num_rows($result);
$output[] = "$rowcnt pending link(s) found";
for ($i = 0 ; $i < $rowcnt ; $i++)
{
    // fetch this row
    list($gameID, $gameTitle, $linkTitle, $url, $ord, $attrs) =
        mysql_fetch_row($result);

    // if the URL doesn't start with http://, ignore it
    if (!preg_match("/^http:\/\//i", $url))
    {
        $output[] = "Non-http URL: gameID=$gameID, game title=$gameTitle, "
                    . "link title=$linkTitle, url=$url";
        continue;
    }

    // if it's an IFDB 'pending' URL, ignore it
    if (preg_match("/^http:\/\/ifdb.tads.org\/ifarchive-pending\?/i", $url))
    {
        $output[] = "ifarchive-pending URL: gameID=$gameID, "
                    . "game title=$gameTitle, link title=$linkTitle,"
                    . "url=$url";
        continue;
    }

    // get the resource headers from the remote server
    $info = x_http_head($url);
    if ($info)
        $info = explode("\r\n", $info);
    else
        $info = array("HTTP/1.1 404 Not Found");
    
    $ok = false;
    if (strstr($info[0], '200 OK') !== false)
    {
        // successful reply - update the link to remove the pending flag
        $ok = true;
        $newAttrs = $attrs & ~GAMELINK_PENDING;
        $url = mysql_real_escape_string($url, $db);
        $linkTitle = mysql_real_escape_string($linkTitle, $db);
        $ord = mysql_real_escape_string($ord, $db);
        $updres = mysql_query(
            "update gamelinks
             set attrs = '$newAttrs'
             where
               gameid = '$gameID'
               and url = '$url'
               and title = '$linkTitle'
               and displayorder = '$ord'
               and attrs = '$attrs'", $db);

        if (!$updres) {
            $showOutput = true;
            $output[] = "MySQL Error in UPDATE: " . mysql_error($db);
        }
    }

    // add the status
    $output[] = ($ok ? "*Working*" : "No change")
                . ": gameID=$gameID, game title=$gameTitle, "
                . "link title=$linkTitle, url=$url";
}

// show the output if desired
if ($showOutput) {
    header("Content-type: text/plain");
    echo implode("\n", $output);
    echo "\n\n";
}

// ------------------------------------------------------------------------
//
// Simple http HEAD.  Returns the headers.
//
function x_http_head($url)
{
    // parse the URL into the domain and resource path
    preg_match("/^http:\/\/([-a-z0-9.]+)(\/.*$)/i", $url, $match);
    $addr = $match[1];
    $res = $match[2];

    // open the socket
    $fp = fsockopen($addr, 80, $errno, $errstr, 30);
    if (!$fp)
        return false;

    // send the HEAD request
    $req = "HEAD $res HTTP/1.1\r\n"
           . "Host: $addr\r\n"
           . "Resource: $res\r\n"
           . "Connection: Close\r\n\r\n";
    fwrite($fp, $req);

    // read the reply 
    for ($msg = "" ; !feof($fp) ; $msg .= fgets($fp, 128)) ;

    // done with the socket
    fclose($fp);

    // the reply should consist entirely of headers, so return what we got
    return $msg;
}

?>