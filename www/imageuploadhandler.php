<?php

include_once "images.php";
include_once "login-persist.php";

// set up the scripts for the image upload form
function imageUploadScripts()
{
    ?>

<div id="imageUploadCopyrightForm" class="edit-popup-frame"
  style="position:absolute;display:none;z-index:100;">
   <div class="edit-popup-win">
      <form name="imageUploadCopyrightForm" method="post"
          action="imageUpload" target="imageUploadCopyrightFormTarget">
         <input type="hidden" name="setCopyright" value="1">
         <input type="hidden" name="imageID" value=""
            id="imageCopyrightFormID">
         <table id="imageUploadCopyrightTab">
            <tr>
               <td style="padding-right: 1em;">
               </td>
               <td>
                  <b>Please indicate the copyright status of this image:</b><p>
                  <?php showImageCopyrightForm("U", ""); ?>
               </td>
            </tr>
         </table>
         <center>
            <input type="submit" name="okbtn" value="OK">
         </center>
      </form>
      <iframe src="" name="imageUploadCopyrightFormTarget"
          id="imageUploadCopyrightFormTarget" style="display:none;">
      </iframe>
   </div>
</div>

<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--

var imageUploadFocusCtl = null;
function imageUploadFocus(id, flag)
{
    if (flag)
        imageUploadFocusCtl = id;
    else if (imageUploadFocusCtl == id)
        imageUploadFocusCtl = null;
}

function imageUploadSelect(btn, fr)
{
    fr = document.getElementById(fr);
    var frdoc = fr.contentDocument || fr.contentWindow.document;
    var form = frdoc.getElementById("uplUploadForm");
    form.uplFile.click();
    if (imageUploadFocusCtl) {
        document.getElementById(btn).style.display = "none";
        fr.style.display = "block";
    }
}
function imageUploadReady(btn)
{
    document.getElementById(btn).style.display = "inline";
}
function imageUploadDone(radio, btn, fr, tab, imgName, errmsg,
                         images, firstCol, thumbSize)
{
    document.getElementById(btn).style.display = "inline";
    document.getElementById(fr).style.display = "none";

    if (errmsg)
        alert(errmsg);
    else if (imgName)
    {
        imageTableUpdate(tab, radio, images, firstCol, imgName, thumbSize);

        var cf = document.getElementById("imageUploadCopyrightForm");
        var ct = document.getElementById("imageUploadCopyrightTab");
        ct.rows[0].cells[0].innerHTML =
            "<img src=\"tempimage?id=" + imgName.substr(3)
            + "&thumbnail=120x120\">";
        cf.style.display = "block";
        document.getElementById("imageCopyrightFormID").value = imgName;
        initImageCopyrightForm();
        setTimeout(function() {
            var rcb = getObjectRect(document.getElementById(btn));
            var rci = getObjectRect(document.getElementById(tab).rows[0]);
            var rcw = getWindowRect();
            cf.style.width = (rcw.width - rcb.x - 20) + "px";
            moveObject(cf, rcb.x, rci.y + rci.height);
        }, 10);
    }
}
function imageTableUpdate(tab, radio, images, firstCol, curVal, thumbSize)
{
    tab = document.getElementById(tab);
    var row = tab.rows[0];
    for (var i = 0 ; i < images.length ; ++i)
    {
        var image = images[i];
        var href = "tempimage?id=" + image + "&thumbnail="
                   + thumbSize + "x" + thumbSize;
        var val = "tmp" + image;
        row.cells[i + firstCol].innerHTML =
            "<label><input type='radio' name=\""
            + radio + "\" value=\"" + val + "\" id=\"" + radio + "-tmp"
            + i + "\"" + (val == curVal ? " checked" : "")
            + "><label for=\"" + radio + "-tmp" + i + "\">&nbsp;<img src=\""
            + href + "\" align='absmiddle'></label></label><br>"
            + "<span class='details'><i>(Uploaded image)</i></span>";

        if (val == curVal)
        {
            var rb;
            if ((rb = document.getElementById(radio + "-old")) != null)
                rb.checked = false;
            if ((rb = document.getElementById(radio + "-none")) != null)
                rb.checked = false;
        }
    }
    for (var i = firstCol + images.length ; i < row.cells.length ; ++i)
        row.cells[i].innerHTML = "";

    tab.rows[1].cells[0].innerHTML =
        (firstCol == 0 && images.length == 0 ? "" : "&nbsp;<br>");
}
function imageCopyrightUpdated(errmsg)
{
    if (errmsg)
        alert("An error occurred setting the image copyright: " + errmsg);
    else
    {
        document.getElementById("imageUploadCopyrightForm").style.display =
            "none";
    }
}

//-->
</script>
    <?php
}

// display radio buttons for selecting from an existing image in the
// database, the current temporary upload images, or no image at all
//
// $originalUrl is the URL at which the original image can be found;
//    if the size can be specified, use $thumbSiz x $thumbSiz; use false
//    if there's no original image to display
// $noneLabel is the label to use for the "No Image" option; if false,
//    we'll simply say "No Image"
// $radioname is the name of the radio button group (for <input name=>)
// $curval is the current selected image: this is "old" if the original
//    image is selected, "tmpN" if one of the temporary images is selected
//    (N = 0..4), or "none" if the "no image" option is selected.
// $thumbSiz is the width and height we'll use to display thumbnails of
//     the temporary images
//
function imageUploadRadio($originalUrl, $noneLabel, $radioname, $curval,
                          $thumbSiz)
{
    // apply a default to $noneLabel
    if (!$noneLabel)
        $noneLabel = "No Image";

    // derive the control names
    $ubtn = "$radioname-uplbtn";
    $ufr = "$radioname-iframe";
    $utab = "$radioname-table";

    // start a table for the image buttons
    echo "<table border=0 cellpadding=0 cellspacing=0 id=\"$utab\">"
        . "<tr valign=middle>";

    // assume we'll have one column per available session image
    $tableCols = MAX_TEMP_IMAGES;
    $col0 = 0;

    // offer the current database value as an option, if set
    if ($originalUrl)
    {
        // display the column for the existing database image
        echo "<td style='padding-right: 4ex;'>"
            . "<label><input type=\"radio\" name=\"$radioname\" "
            . "value=\"old\" id=\"$radioname-old\"";
        if ($curval == "old")
            echo " checked";
        echo "><label for=\"$radioname-old\">&nbsp;<img align=absmiddle "
            . "src=\"$originalUrl\">"
            . "</label></label>"
            . "<br><span class=details><i>(Current image)</i></span></td>";

        // count it
        $col0 += 1;
        $tableCols += 1;
    }

    // add placeholders for the uploaded images
    for ($j = 0 ; $j < MAX_TEMP_IMAGES ; $j++)
        echo "<td style='padding-right: 4ex;'></td>";

    // add the "no image" option
    echo "</tr><tr><td>&nbsp;<br></td></tr>"
        . "<tr valign=middle><td colspan='$tableCols'>"
        . "<label><input align=middle type=\"radio\" name=\"$radioname\" "
        .    "value=\"none\" id=\"$radioname-none\""
        . ($curval == "none" || $curval == "" || is_null($curval)
           ? " checked" : "")
        . "><label for=\"$radioname-none\">&nbsp;$noneLabel</label></label>"
        . "</td></tr>";

    // add the upload button
    echo "</tr><tr><td>&nbsp;<br></td></tr><tr valign=middle>"
        . "<td colspan='$tableCols'>"
        . "<a href=\"#\" id=\"$ubtn\" "
        .   "onclick=\"javascript:imageUploadSelect('$ubtn','$ufr');"
        .      "return false;\" "
        .   "onfocus=\"javascript:imageUploadFocus('$ubtn', true);\" "
        .   "onblur=\"javascript:imageUploadFocus('$ubtn', false);\">"
        . "<img src=\"/img/blank.gif\" class=\"upload-image-button\"></a>"
        . "<iframe name=\"$ufr\" id=\"$ufr\" src=\"imageUpload?btn=$ubtn"
        .     "&fr=$ufr&thumbSize=$thumbSiz&radio=$radioname&tab=$utab"
        .     "&col0=$col0\" "
        .   "style=\"display:none; width: 80ex; height:2em; border: none;\">"
        . "</iframe>"
        . "</td>"
        . "</tr>";

    // end the table
    echo "</table>";

    // populate it via javascript
    ?>
<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--
imageTableUpdate("<?php echo "$utab"; ?>", "<?php echo $radioname; ?>", [<?php

   if (isset($_SESSION['temp_images']))
   {
       // get the image array
       $images = $_SESSION['temp_images'];

       // build the image list
       for ($i = 0, $iarr = array() ; $i < count($images) ; $i++)
           $iarr[] = "\"{$images[$i][2]}\"";

       // write it out
       echo implode(",", $iarr);
   }

   ?>], <?php echo $col0; ?>, "<?php echo $curval; ?>", <?php
   echo $thumbSiz ?>);
//-->
</script>
    <?php

}

// --------------------------------------------------------------------------
// If we just uploaded a picture during this request, get its identifier
// (its "tmpX" code).  Returns $prev if no image was uploaded during
// this request.
//
function getJustUploadedImage($prev)
{
    global $imgErrMsg;

    $images = isset($_SESSION['temp_images']) ? $_SESSION['temp_images'] : "";

    if (isset($_REQUEST['uploadimage'])
        && isset($_FILES['imagefile'])
        && strlen($_FILES['imagefile']['tmp_name']) > 0
        && is_uploaded_file($_FILES['imagefile']['tmp_name'])
        && !$imgErrMsg
        && count($images) > 0) {
        // it looks like we successfully uploaded an image during
        // this request, so the last image is the one we just uploaded
        return "tmp" . $images[count($images) - 1][2];
    }

    // no image uploaded on this round
    return $prev;
}

// --------------------------------------------------------------------------
//
// if we're uploading a file, add it to the session list
if (isset($_REQUEST['uploadimage']) && $_FILES['imagefile']['error']) {
    switch ($_FILES['imagefile']['error']) {
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
        $imgErrMsg = "This image is too large - image uploads are
             capped at 256k.";  // MAX_FILE_SIZE
        break;

    case UPLOAD_ERR_NO_FILE:
        // no file uploaded - treat this as a cancel request
        break;

    default:
        $imgErrMsg = "The upload was not successfully completed. Please
             try again.";
        break;
    }
}
else if (isset($_REQUEST['uploadimage'])
         && strlen($_FILES['imagefile']['tmp_name']) > 0
         && is_uploaded_file($_FILES['imagefile']['tmp_name']))
{
    // looks valid - load the file
    list($imgErrMsg) = addTempImageFile(
        $_FILES['imagefile']['tmp_name'],
        get_req_data("imgcopyright"),
        get_req_data("imgcopyrighttag"));
}

function showImageCopyrightForm($defStatus, $defOwner)
{
    if (isset($_REQUEST['imgcopyright']))
        $rbval = get_req_data("imgcopyright");
    else
        $rbval = $defStatus;

    if ($defOwner)
        $defOwner = "'Copyright " . jsSpecialChars($defOwner) . "'";

    $defCC = "''";
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
        $db = dbConnect();
        checkPersistentLogin();
        $uid = mysql_real_escape_string($_SESSION['logged_in_as'], $db);
        $result = mysql_query("select name from users where id='$uid'", $db);
        if (mysql_num_rows($result) > 0) {
            $username = mysql_result($result, 0, "name");
            $defCC = "Copyright $username";
            $defCC = "'" . jsSpecialChars($defCC) . "'";
        }
    }

    if (!$defOwner)
        $defOwner = $defCC;

    ?>
<script nonce="<?php global $nonce; echo $nonce; ?>">
<!--
var imageCopyrightState = {
    lastAutoSet: "",
    defOwner: <?php echo $defOwner ?>,
    defCC: <?php echo $defCC ?>
};
function initImageCopyrightForm()
{
    var rbs = ['C', 'L', 'U', 'F', 'P', 'NULL'];
    for (var i = 0 ; i < rbs.length ; ++i)
        document.getElementById("icr" + rbs[i]).checked = false;

    document.getElementById("icrNULL").checked = true;
    document.getElementById("imgcopyrighttag").value = "";
}
function setDefaultImageCopyright(def)
{
    var newval = (def == '' ? '' : imageCopyrightState[def]);
    var ele = document.getElementById("imgcopyrighttag");
    var oldval = ele.value;
    if (oldval == "" || oldval == imageCopyrightState.lastAutoSet) {
        ele.value = newval;
        imageCopyrightState.lastAutoSet = newval;
    }
}
//-->
   </script>

    <div class=indented style="font-size:90%;">

       <label><input type=radio name="imgcopyright" value="C" id="icrC"
          <?php if ($rbval == 'C') echo "checked"?>
          onclick="javascript:setDefaultImageCopyright('defCC');">
          <label for="icrC">I own the copyright and hereby license the
             image under a
             <a href="http://creativecommons.org/licenses/by/3.0/us/"
                target="_blank">Creative Commons Attribution 3.0
                United States</a> license.</label></label>

       <br><label><input type=radio name="imgcopyright" value="L" id="icrL"
          <?php if ($rbval == 'L') echo "checked"?>
          onclick="javascript:setDefaultImageCopyright('defOwner');">
          <label for="icrL">The image is covered by a "Free Software" license
          that allows its use on IFDB. For example, it's part of a free game.
          </label></label>

       <br><label><input type=radio name="imgcopyright" value="U" id="icrU"
          <?php if ($rbval == 'U') echo "checked"?>
          onclick="javascript:setDefaultImageCopyright('defOwner');">
          <label for="icrU">The copyright owner has granted special
             permission for the image to be used on IFDB.
          </label></label>

       <br><label><input type=radio name="imgcopyright" value="F" id="icrF"
          <?php if ($rbval == 'F') echo "checked"?>
          onclick="javascript:setDefaultImageCopyright('defOwner');">
          <label for="icrF">I believe this copyrighted image can be
             legally used on IFDB under the
             <a href="http://en.wikipedia.org/wiki/Fair_use"
                target="_blank">Fair Use</a>
             doctrine.
          </label></label>

       <br><label><input type=radio name="imgcopyright" value="P" id="icrP"
          <?php if ($rbval == 'P') echo "checked"?>
          onclick="javascript:setDefaultImageCopyright('');">
          <label for="icrP">This image is not copyrighted (it's in the
             <a href="http://en.wikipedia.org/wiki/Public_domain"
                target="_blank">public
             domain</a>).</label></label>

       <br><label><input type=radio name="imgcopyright" value="" id="icrNULL"
          <?php if ($rbval == '') echo "checked"?>
          onclick="javascript:setDefaultImageCopyright('');">
          <label for="icrNULL">Not specified.</label></label>


       <p>Enter the image's copyright description, if applicable.
       For example: "Copyright 2000 by Big IF Games"<br>
       <input type=text name="imgcopyrighttag" id="imgcopyrighttag" size=100
              value="<?php echo get_req_data("imgcopyrighttag") ?>">
    </div>

    <?php
}

?>
