<?php
include_once "util.php";
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

    function localStorageCredentials() {
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
    mysql_set_charset($db, "latin1");
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

function storageDbConnect()
{
    // Turn off exceptions in case we're in a development environemnt
    mysqli_report(MYSQLI_REPORT_ERROR);

    // connect to the appropriate server, depending on whether we're running
    // on the real system or on our local test bed
    $sdbinfo = localStorageCredentials();

    // connect and select the correct database
    $sdb = mysql_connect($sdbinfo[0], $sdbinfo[1], $sdbinfo[2]);
    if ($sdb != false)
        $result = mysql_select_db("storage", $sdb);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // return the connection
    return $sdb;
}

?>
