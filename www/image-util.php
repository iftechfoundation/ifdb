<?php
include_once "dbconnect.php";

// --------------------------------------------------------------------------
// Fetch an image, given the ID.  Returns an array (image binary data,
// image format name).
//
function fetch_image($imageID, $getPic)
{
    // parse the image ID:  <dbnum>:<image key>
    list ($dbnum, $key) = explode(":", $imageID);

    // connect to the database encoded in the ID
    $db = imageDbConnect((int)$dbnum);
    if (!$db)
        return false;

    // set up to get or skip the image data stream
    $imgcol = ($getPic ? "img" : "null");

    // fetch the image at the given key
    $key = mysql_real_escape_string($key, $db);
    $result = mysql_query(
        "select $imgcol, format, copystat, copyright, userid
         from images where id='$key'", $db);

    // if we got a result, return it
    return mysql_fetch_row($result);
}

// ------------------------------------------------------------------------
//
// Create a transparent image resource of the given size
//
function newTransparentImage($x, $y)
{
    // create a true-color image of the given size
    $r = imagecreatetruecolor($x, $y);
    imagesavealpha($r, true);;

    // we want to set the transparency in the new image, not combine it
    imagealphablending($r, false);

    // allocate a transparent pixel color
    $transparent = imagecolorallocatealpha($r, 0, 0, 0, 127);

    // fill the whole image with the transparent color
    imagefilledrectangle($r, 0, 0, $x+1, $y+1, $transparent);

    // turn alpha blending back on
    imagealphablending($r, true);

    // return the new image
    return $r;
}

// --------------------------------------------------------------------------
// send an image, possibly with a thumbnail setting
//
define("SIF_RAW", 0x0001);
function sendImage($imgData, $imgFmt, $thumbnail, $flags = 0)
{
    // make sure there's an image
    if (is_null($imgData))
        exit("No image is available");

    // Get the MIME type, assuming for now that it's an image.  For image
    // types, the MIME type is simply "image/X", where X is our format code
    $mimeType = "image/$imgFmt";

    // if it's a font, check for image renderings
    if ($imgFmt == "ttf") {
        // It's a font.  If they want a thumbnail, just send the TTF icon.
        // Otherwise, if they want it as an image (not as the raw file),
        // render a sample page for the font.  If they want it raw, just
        // send the raw data.

        if (!is_null($thumbnail)) {
            // they want a thumbanil - simply send the TTF icon
            $imgData = file_get_contents("ttf_icon.gif");
            $thumbnail = null;
            $imgFmt = "gif";
            $mimeType = "image/gif";
        } else if (($flags & SIF_RAW) != 0) {
            // they want the raw font data - simply send the data,
            // with the TTF MIME type
            $mimeType = "application/x-font-ttf";
        } else {
            // They want an image rendering of the data.  Prepare a
            // sample character set page based on the font.

            // create a temporary file from the font
            $tmpfile = tempnam("/tmp", "ttf");
            $fp = fopen($tmpfile, "wb");
            fwrite($fp, $imgData);
            fclose($fp);

            // set up an image for the sample
            $wid = 500;
            $ht = 200;
            $im = imagecreatetruecolor($wid, $ht);

            // set up some colors
            $white = imagecolorallocate($im, 255, 255, 255);
            $black = imagecolorallocate($im, 0, 0, 0, 0);

            // render the sample page
            imagefilledrectangle($im, 0, 0, $wid, $ht, $white);
            imagettftext($im, 12, 0, 0, 17, $black, $tmpfile,
                         ttf_family_name($imgData));
            imageline($im, 0, 25, $wid, 25, $black);
            imagettftext($im, 20, 0, 0, 50, $black, $tmpfile,
                         "abcdefghijklmnopqrstuvwxyz\n"
                         . "ABCDEFGHIJKLMNOPQRSTUVWXYZ\n"
                         . "1234567890.:,;(:*!?'\")\n");

            // send the constructed image as a jpeg, and we're done
            imagejpeg($im);
            return;
        }
    }

    // if they only want the thumbnail, resize it
    if (!is_null($thumbnail)) {
        // get the thumbnail sizes
        list ($sx, $sy) = explode("x", $thumbnail);

        // get the image data
        $source = imagecreatefromstring($imgData);
        $ix = imagesx($source);
        $iy = imagesy($source);

        // only generate a thumbnail if it's SMALLER than the actual size
        if ($sx < $ix || $sy < $iy) {
            // apply the default and maximum settings
            if ($sx <= 0 || $sy <= 0)
                list($sx, $sy) = array(175, 175);
            else if ($sx > 250 || $sy > 250)
                list($sx, $sy) = array(250, 250);

            // Figure the scaled size, maintaining the aspect ratio.
            // First try scaling so that the width just fits the bounding
            // width.  If that makes the height too tall, rescale so that
            // the height fits the bounding height.
            $cx = $cy = 0;
            if ($iy != 0) {
                $cx = $sx;
                $cy = $iy * $sx / $ix;
            }
            if ($cy > $sy && $ix != 0) {
                $cy = $sy;
                $cx = $ix * $sy / $iy;
            }

            // generate the thumbnail image
            $thumb = newTransparentImage($sx, $sy);
            imagecopyresampled(
                $thumb,
                $source,
                ($sx - $cx) / 2,
                ($sy - $cy) / 2,
                0,
                0,
                $cx,
                $cy,
                $ix,
                $iy
            );

            // send it in the original format, or jpeg if unrecognized
            if ($imgFmt == "gif") {
                header("Content-type: image/gif");
                imagegif($thumb);
            } else if ($imgFmt == "png") {
                header("Content-type: image/png");
                imagepng($thumb, null, 0);
            } else {
                header("Content-type: image/jpeg");
                imagejpeg($thumb);
            }

            // we've finished sending the image
            return;
        }
    }

    // send the image exactly as it's stored
    header("Content-length: " . strlen($imgData));
    header("Content-type: $mimeType");
    echo $imgData;
}
