<?php

// --------------------------------------------------------------------------
//
// Constants
//

// maximum number of temporary images loaded at one time; if we upload
// more than this, we'll drop the oldest image
define("MAX_TEMP_IMAGES", 5);

// --------------------------------------------------------------------------
//
// image copyright status list
//
global $copyrightStatList;
$copyrightStatList = array(
    "U" => "Used by permission.",
    "C" => "Licensed under a "
         . "<a href=\"http://creativecommons.org/licenses/by/3.0/us/\">"
         . "Creative Commons Attribution 3.0 United States</a> license.",
    "L" => "Used under a free software (or equivalent) license.",
    "F" => "This copyrighted image is used on IFDB under Fair Use "
         . "principles. Further reproduction in other contexts might "
         . "not qualify as fair use; contact the copyright owner "
         . "with any reprint inquiries.",
    "P" => "This image is in the public domain.",
    "" => "The copyright status of this image has not been specified."
);

// --------------------------------------------------------------------------
//
// Add an uploaded image file to the temporary image list in the session.
//
function addTempImageFile($filename, $cprStat, $cprTag)
{
    global $copyrightStatList;

    // presume no error
    $imgErrMsg = $imgErrShort = $imgErrCode = false;

    // ...but also presume no image will be stored
    $imageName = false;
    
    // load the file
    $imgdata = file_get_contents($filename);
    $imginfo = getimagesize($filename);
    $imgtype = $imginfo[2];

    // translate the image type to an image/xxx name
    $xlat = array(IMAGETYPE_JPEG => "jpeg",
                  IMAGETYPE_GIF => "gif",
                  IMAGETYPE_PNG => "png");
    $imgtype = isset($xlat[$imgtype]) ? $xlat[$imgtype] : null;

    // validate the copyright status
    if (!isset($copyrightStatList[$cprStat])) {
        $imgErrCode = "ImageCprMissing";
        $imgErrShort = "image copyright status must be specified";
        $imgErrMsg = "Please select a valid copyright status.";
        unset($_FILES['imagefile']);
    }

    // validate the format
    else if (is_null($imgtype))
    {
        $imgErrCode = "ImageFormatError";
        $imgErrShort = "the image isn't in a recognized format "
                       . "(it must be JPEG, PNG, or GIF), or contains "
                       . "invalid image data";
        $imgErrMsg = "The image you selected for upload is not a
             valid image or is not one of the allowed formats. Please
             specify a valid JPEG, PNG, or GIF image.";

        // forget the uploaded image for later processing
        unset($_FILES['imagefile']);
    }
    else if (strlen($imgdata) > 262144) // MAX_FILE_SIZE
    {
        $imgErrCode = "ImageTooBig";
        $imgErrShort = "the image is too large (the maximum size is 256k)";
        $imgErrMsg = "The image you selected for upload is too large.
            Image uploads are limited to 256k. If you have a photo
            editing program, you might be able to reduce the image's
            data size while preserving its overall appearance by
            reducing the display dimensions. We recommend images
            no larger than 960x960 for this site.";

        // forget the uploaded image for later processing
        unset($_FILES['imagefile']);
    }
    else
    {
        // get the current file list, if any
        $arr = isset($_SESSION['temp_images'])
               ? $_SESSION['temp_images'] : array();

        // assign the next ID - this is the last extant image ID plus 1
        $newImageID = (count($arr) != 0 ? $arr[count($arr)-1][2] + 1 : 1);

        // if the temp list is already full, drop the oldest image
        $cnt = count($arr);
        if ($cnt >= MAX_TEMP_IMAGES) {
            // shift everything down a notch
            for ($i = 0 ; $i < 4 ; $i++)
                $arr[$i] = $arr[$i+1];

            // drop everything past this point
            for ($i = 4 ; $i < $cnt ; $i++)
                unset($arr[$i]);
        }
        
        // save the image in the array
        $arr[count($arr)] = array($imgdata, $imgtype, $newImageID,
                                  $cprStat, $cprTag);

        // update the session
        $_SESSION['temp_images'] = $arr;

        // generate the image name in "tmpX" format
        $imageName = "tmp$newImageID";
    }

    // return the error indication
    return array($imgErrMsg, $imgErrShort, $imgErrCode, $imageName);
}


// --------------------------------------------------------------------------
//
// Update the copyright status information for a given temporary image.
//
function setTempImageCopyright($name, $cprStat, $cprTag)
{
    global $copyrightStatList;

    // find the temporary image
    $idx = findTempImageIndex($name);
    if ($idx < 0)
        return "The specified image isn't available.";

    // pull out the image data
    $img = $_SESSION['temp_images'][$idx];

    // validate the copyright status
    if (!isset($copyrightStatList[$cprStat]))
        return "Invalid or missing copyright status.";

    // update the image copyright data
    $img[3] = $cprStat;
    $img[4] = $cprTag;

    // update the session image list with the updated image object
    $_SESSION['temp_images'][$idx] = $img;
}

// --------------------------------------------------------------------------
//
// Find a temporary image given the "tmpX" identifier - these are the
// identifiers used for the radio buttons in the image upload form.
//
function findTempImage($name)
{
    // find the image index
    $idx = findTempImageIndex($name);

    // if we found an image, return the image object
    if ($idx >= 0)
        return $_SESSION['temp_images'][$idx];
    else
        return false;
}

function findTempImageIndex($name)
{
    // make sure it looks like a temp image ID
    if (substr($name, 0, 3) != "tmp")
        return -1;

    // if there are no images, we're not going to find anything
    if (!isset($_SESSION['temp_images']))
        return -1;
    
    // get the image list
    $images = $_SESSION['temp_images'];

    // pull the ID out of the name - it's the integer after "tmp"
    $tmpId = (int)substr($name, 3);
    for ($i = 0 ; $i < count($images) ; $i++) {
        // if this is the one, return the image data object
        if ($images[$i][2] == $tmpId)
            return $i;
    }

    // didn't find it
    return -1;
}

?>
