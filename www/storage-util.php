<?php

include_once "util.php";

// storage server utilities for IFDB

// --------------------------------------------------------------------------
//
// Base directory for storage
//
if (isLocalDev()) {
    define("STORAGE_DIR", "c:/website/storageServer/files/");
    define("STORAGE_LOG_FILE", "c:/website/storageServer/logs/debuglog.txt");
} else {
    define("STORAGE_DIR", "../storage/files/");
    define("STORAGE_LOG_FILE", "../storage/logs/debuglog.txt");
}


// ------------------------------------------------------------------------
//
// Save file metadata parser
//
function parseSaveMetaData($fname)
{
    // open the file
    $fp = fopen($fname, "rb");
    if (!$fp)
        return false;

    // skip the signature, stream size, checksum, timestamp
    fseek($fp, 17+4+4+24, SEEK_CUR);

    // skip the image filename and metadata byte length
    fseek($fp, read_uint2($fp) + 2, SEEK_CUR);

    // read the number of metadata entries
    $n = read_uint2($fp);

    // read the entries
    for ($tab = array() ; $n != 0 ; $n--)
    {
        // read the name/value pair
        $namelen = read_uint2($fp);
        $name = fread($fp, $namelen);

        $vallen = read_uint2($fp);
        $val = fread($fp, $vallen);

        // add the pair to the table
        $tab[$name] = $val;
    }

    // done with the file
    fclose($fp);

    // return the table
    return $tab;
}

// read a UINT2 from a tads binary file
function read_uint2($fp)
{
    $str = fread($fp, 2);
    $u = unpack("vx", $str);
    return $u["x"];
}

// given a saved game filename, get the slot number
function saveFileSlotNum($fname)
{
    if (preg_match("/s(\d+)\.t3v/i", $fname, $m))
        return (int)$m[1];
    else
        return 0;
}

function saveFileSlotNumStr($fname, $tpl = "#\$: ")
{
    // if the template is an array, pull out the slot number template
    // and no-number template
    $blank = "";
    if (gettype($tpl) == "array")
    {
        $blank = $tpl[1];
        $tpl = $tpl[0];
    }

    // get the slot number
    $i = saveFileSlotNum($fname);

    // if we didn't get a slot number, return an empty string
    if ($i == 0)
        return $blank;

    // apply the template
    return str_replace("\$", $i, $tpl);
}

//-------------------------------------------------------------------------
//
// Get a list of files in the given directory.  Returns a list of file
// entry objects per getStorageFileInfo().  If the path ends in "/save",
// we'll sort by save file slot number; otherwise we'll sort by filename.
//
function getStorageFileList($path)
{
    // start with an empty file list
    $files = array();

    // open the folder
    if (($sh = @opendir($path)) !== false)
    {
        // scan the folder's contents
        while (($sname = readdir($sh)) !== false)
        {
            // get the details on this file
            $spath = "$path/$sname";
            $info = getStorageFileInfo($spath, $sname);

            // list only files
            if (filetype($spath) == "file")
                $files[] = $info;
        }

        // done with the subdirectory traversal
        closedir($sh);
    }

    // sort according to the path type
    if (preg_match("#/save$#i", $path))
        usort($files, "compareSaveSlotNum");
    else
        usort($files, "compareStorageFileName");

    // return the file list
    return $files;
}

function compareSaveSlotNum($a, $b)
{
    return saveFileSlotNum($a["path"]) - saveFileSlotNum($b["path"]);
}

function compareStorageFileName($a, $b)
{
    return strcasecmp($a["path"], $b["path"]);
}

// ------------------------------------------------------------------------
//
// Get the file data for a directory entry
//
function getStorageFileInfo($fpath, $fname)
{
    // pull out the extension
    $ext = false;
    if (preg_match("/\.[a-z0-9]+$/i", $fname, $m))
        $ext = $m[0];

    // find out when "today", "yesterday", and this week started
    $now = getdate();
    $today = mktime(0, 0, 0, $now["mon"], $now["mday"], $now["year"]);
    $secsInDay = 24*60*60;
    $yesterday = $today - $secsInDay;
    $sunday = $today - ($secsInDay * $now["wday"]);

    // get the file's modification date, and generate a printable
    // version in a relative format based on the age
    $mtime = filemtime($fpath);
    $diff = time() - $mtime;
    if ($mtime >= $today) {
        // it's today - just show the time
        $date = date("@g:i a", $mtime);
    } else if ($mtime >= $yesterday) {
        // yesterday - show "Yesterday"
        $date = "yesterday@" . date("g:i a", $mtime);
    } else if ($mtime >= $sunday) {
        // this week - show the day of the week and the time
        $date = date(">l@g:i a", $mtime);
    } else if ($diff < 15*24*60*60) {
        // within a couple of weeks; show the weekday and date
        $date = date(">D M j@g:i a", $mtime);
    } else {
        // otherwise, show the full date with year
           $date = date(">M j, Y@g:i a", $mtime);
    }

    $atDate = preg_replace(
        array("/>/", "/^@/", "/@/"),
        array("on ", "at ", " at "),
        $date);
    $date = preg_replace(
        array("/>/", "/^@/", "/@/"),
        array("", "", ", "),
        $date);
    $date = strtoupper($date{0}) . substr($date, 1);

    // figure the plain formatted date as well
    $plainDate = date("n/j/Y g:i a", $mtime);

    // get the size in bytes, and figure the display size
    $size = filesize($fpath);
    if ($size == 1)
        $dsize = "$size byte";
    else if ($size < 1024)
        $dsize = "$size bytes";
    else if ($size < 10*1024)
        $dsize = round($size/1024.0, 1) . " KB";
    else if ($size < 1024*1024)
        $dsize = round($size/1024.0, 0) . " KB";
    else if ($size < 10*1024*1024)
        $dsize = round($size/(1024.0*1024.0), 1) . " MB";
    else if ($size < 1024*1024*1024)
        $dsize = round($size/(1024.0*1024.0), 0) . " MB";
    else
        $dsize = round($size/(1024.0*1024.0*1024.0), 2) . " GB";

    // build and return the record
    return array("name" => $fname,
                 "path" => $fpath,
                 "ext" => $ext,
                 "mtime" => $mtime,
                 "mtime-at" => $atDate,
                 "mtime-fancy" => $date,
                 "mtime-plain" => $plainDate,
                 "size" => $size,
                 "size-disp" => $dsize);
}

// ------------------------------------------------------------------------
//
// Get a printable description of a saved game file
//
function getSaveFileDesc($f, $slotNumTemplate = "#\$: ", $links = false)
{
    // get the information
    $fpath = $f["path"];
    $fname = htmlspecialchars($f["name"]);
    $fsize = $f["size-disp"];
    $ftime = $f["mtime-fancy"];
    $dlpath = urlencode(
        substr($f['path'], strlen($path) + 1));

    // if we have links, turn them into a printable list
    if ($links && count($links))
    {
        $linkStr = "";
        foreach ($links as $l)
        {
            if ($linkStr != "")
                $linkStr .= " | ";
            $linkStr .= $l;
        }
        $linkStr = "<span class=\"sfl-save-links\">$linkStr</span>";
    }

    // generate the slot number header
    $slotno = saveFileSlotNumStr($fname, $slotNumTemplate);

    // parse the metadata
    $meta = parseSaveMetaData($fpath);

    // find the title elements
    $desc = array();
    $udesc = $meta['UserDesc'];
    $desc[] = "<span class=\"sfl-save-userdesc\">"
              . htmlspecialchars($udesc)
              . "</span>"
              . $linkStr;
    if (isset($meta['AutoDesc'])) {
        $desc[] = "<span class=\"sfl-save-autodesc\">"
                  . $meta['AutoDesc']
                  . "</span>";
    }
    $desc[] = "<span class=\"sfl-save-date\">"
              . "saved $ftime"
              . "</span>";


    // build the description
    return $slotno . implode("<br>", $desc);
}

?>
