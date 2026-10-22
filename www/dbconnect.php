<?php
include_once "local-credentials.php";

// local-credentials.php is a gitignored file
// it should contain something like this, replacing "username" and "password":

/*

    function localCredentials() {
        return array("127.0.0.1", "username", "password");
    }

    function localImageCredentials() {
        return localCredentials();
    }

    function localRecaptchaKeys() {
        return array(
            "public" => "public-key",
            "private" => "private-key",
        );
    }

    function localAkismetKey() {
        return "akismet-key";
    }

    function localIfArchiveKey() {
        return "ifarchive-key";
    }


*/

function dbConnect()
{
    $dbinfo = localCredentials();

    // connect and select the correct database
    $db = mysql_connect($dbinfo[0], $dbinfo[1], $dbinfo[2]);
    mysql_set_charset($db, "utf8mb4");
    if ($db != false)
        $result = mysql_select_db("ifdb", $db);

    // return the connection
    return $db;
}

// Note: images can be distributed across multiple MySql databases.  This
// is to work around a quota limitation on the size of each MySql database
// imposed by the original hosting service.  Each image database has an
// identical schema.  If the size of the MySql database file isn't
// artificially limited by quotas, there's probably no need to use
// multiple image databases.
function imageDbConnect($dbnum)
{
    $dbinfo = localImageCredentials();

    // connect and select the database
    $db = mysql_connect($dbinfo[0], $dbinfo[1], $dbinfo[2]);
    if ($db != false)
        mysql_select_db("ifdb_images" . $dbnum, $db);

    // return the connection
    return $db;
}

// --------------------------------------------------------------------------
// mysqli replacements (for compatibility across php versions)

define("MYSQL_ASSOC", MYSQLI_ASSOC);
define("MYSQL_BOTH",  MYSQLI_BOTH);

// PHP 8.1+ defaults to MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT, which
// turns failed queries into exceptions. Callers still check false returns.
mysqli_report(MYSQLI_REPORT_OFF);

$query_i = 0;

function mysql_connect($server, $user, $password) { return mysqli_connect($server, $user, $password); }
function mysql_set_charset($linkid, $charset) { return mysqli_set_charset($linkid, $charset); }
function mysql_select_db($db, $linkid = NULL) { return mysqli_select_db($linkid, $db); }
function mysql_real_escape_string($str, $db) { return mysqli_real_escape_string($db, $str); }
function mysql_query($query, $db) {
    $logging_level = 0;
    if ($logging_level == 0) {
        return mysqli_query($db, $query);
    } else if ($logging_level == 1) {
        $result = mysqli_query($db, $query);
        if (!$result) {
            error_log($_SERVER['REQUEST_URI']. " " . $query);
            error_log($_SERVER['REQUEST_URI']. " " . mysql_error($db));
        }
        return $result;
    } else {
        global $query_i;
        $start = microtime(true);
        $result = mysqli_query($db, $query);
        $elapsed = microtime(true) - $start;
        error_log($_SERVER['REQUEST_URI']. " " . $query_i++ . " ($elapsed): " . $query);
        return $result;
    }
}
function mysql_execute_query($db, $query, $params = null) {
    $logging_level = 0;
    if ($logging_level == 0) {
        return mysqli_execute_query($db, $query, $params);
    } else if ($logging_level == 1) {
        $result = mysqli_execute_query($db, $query, $params);
        if (!$result) {
            error_log($_SERVER['REQUEST_URI']. " " . $query);
            error_log($_SERVER['REQUEST_URI']. " " . mysql_error($db));
        }
        return $result;
    } else {
        global $query_i;
        $start = microtime(true);
        $result = mysqli_execute_query($db, $query, $params);
        $elapsed = microtime(true) - $start;
        error_log($_SERVER['REQUEST_URI']. " " . $query_i++ . " ($elapsed): " . $query . "\n\n[".json_encode($params)."]");
        return $result;
    }
}
function mysql_num_rows($result) { return $result ? mysqli_num_rows($result) : 0; }
function mysql_fetch_array($result, $match_type = MYSQLI_BOTH) { return mysqli_fetch_array($result, $match_type); }
function mysql_fetch_row($result) { return mysqli_fetch_row($result); }
function mysql_close($db) { return mysqli_close($db); }
function mysql_error($db) { return mysqli_error($db); }
function mysql_errno($db) { return mysqli_errno($db); }
function mysql_result($result, $row, $field = 0) {
    mysqli_data_seek($result, $row);
    $row = mysqli_fetch_assoc($result);
    return $row[$field];
}
function mysql_insert_id($linkid) { return mysqli_insert_id($linkid); }

?>
