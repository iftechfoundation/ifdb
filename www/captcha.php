<?php

include_once "dbconnect.php";

// CAPTCHA functions
//
// This module has two main uses:
//
//   A. Email masker (AJAX; in-line javascript and XML requests)
//   B. Submitted form protector (adds captcha to a regular POST form)
//
// General instructions for all functions:
//
//   1. Include this file in your php file
//
//   2. In your pageHeader() extra header text, include
//       "<script src=\"xmlreq.js\"></script>"
//
//   3. Generate a "key" for the captcha session (see below).  This is
//      a unique key for the $_SESSION array to identify this captcha
//      interaction.  A good formula is to use the name of the page
//      plus the object key that's used to generate the page.  It's
//      sufficient to just use a fixed string.  If you're using the Form
//      Protector functions, DON'T use a random number or the time or
//      anything else that will change on refresh, since the session
//      key has to survive the POST.
//
//   4. Call captchaSupportScripts($key) to generate support scripts
//      This can go almost anywhere - it just echoes some in-line
//      <script> code.
//
// For details on the individual functions, read on.
//
// ------------------------------------------------------------------------
//
// A. CAPTCHA Email Masker.  This lets you hide email addresses without
//    a separate form submissions step.  When the page initially loads,
//    emails are shown in a masked format, like "mj...@h...".  Each
//    masked email is a link; clicking the link displays a hidden CAPTCHA
//    form.  The form is AJAX-driven - if the user enters a code and
//    presses Return or clicks Submit, the form doesn't do a POST, but
//    simply sends an xml http request to the server, passing along
//    the code the user entered and asking the server for the missing
//    email information.  The server verifies the code and sends back
//    the full emails via XML; the page hides the CAPTCHA form and
//    replaces the masked emails with the real addresses, hyperlinked
//    with mailto: links.  The emails aren't included anywhere in the
//    initial page load - they're only on the server, so there's no way
//    for a bot to get at them by inspecting the html source.
//
//   1. Somewhere in your file, at a suitable point in the HTML, call
//      captchaAjaxForm($key) to generate the input form.  This is an
//      in-line DIV with the input form.  This will be hidden until
//      something triggers its display.
//
//   2. To mask an email: Use captchaMaskEmail($email, $msg) to generate
//      the masked display string.  Display this string at the spot where
//      you want the email to appear.  The form will automatically
//      rewrite it with the correct email on receiving the xml reply.
//
//   3. AFTER generating all masked emails, call captchaFinish($key) to
//      set up the session information.
//
// ------------------------------------------------------------------------
//
// B. CAPTCHA Form Protection.  You can use this to add a CAPTCHA image
// and input field to any regular POSTed form.  On processing the POST,
// you can then validate that the CAPTCHA code was entered correctly
// before accepting the submission.
//
//   1. After calling @session_start(), and before you've displayed any
//      of the page, call captchaCheckPost($key) to check for posted
//      parameters.  This returns array(ok, errmsg), where 'ok' is true
//      if this is a POST *and* the user entered the correct code, false
//      otherwise.  If this is a POST with an incorrect or missing code,
//      'errmsg' contains an explanatory message.  If 'ok' is true, you
//      should go about carrying out the POST action for the page.  If
//      not, you should simply re-display the form even if this is a POST,
//      since the CAPTCHA failed.
//
//   2. At the point in your form where you want the CAPTCHA subform,
//      call captchaSubForm($key, $errmsg).  This will echo the code
//      image and input field.
//
//   3. Call captchaFinish($key) to save the session data.
//

// now, based on reCAPTCHA!
include_once "recaptchalib.php";

$recaptcha_keys = localRecaptchaKeys();

define("RECAPTCHA_PUBLIC_KEY", $recaptcha_keys["public"]);
define("RECAPTCHA_PRIVATE_KEY", $recaptcha_keys["private"]);


// ------------------------------------------------------------------------
//
// Email Masker.  Given an email address, we'll generate and return a
// masked version, which a hyperlink that activates the CAPTCHA form
// and unmasks the email on success.
//
// If $maskMsg is null, we'll use a truncated "x..@.." format for the
// email address.  Otherwise we'll show the message.  If the message
// contains <a>...</a> tags, we'll show the part inside the <a> as
// the linked text, and the parts outside before and after the link.
//
global $captchaEmailList;
$captchaEmailList = false;

function captchaMaskEmail($email, $maskMsg)
{
    static $emailNum = 0;
    global $captchaEmailList;

    // add it to the list of emails to expand
    $captchaEmailList[] = $email;

    $maskA = $maskB = "";
    if ($maskMsg)
    {
        if (preg_match("/^(.*)<a>(.*)<\/a>(.*)$/i", $maskMsg, $m)) {
            $maskA = $m[1];
            $email = $m[2];
            $maskB = $m[3];
        } else {
            $email = $maskMsg;
        }
    }
    else
    {
        // no message - truncate the email to hide the domain
        $idx = strpos($email, "@");
        if ($idx !== false) {
            $user = substr($email, 0, $idx);
            $domain = substr($email, $idx + 1);

            $idx = strrpos($domain, '.');
            if ($idx === false)
                $idx = strlen($domain) - 3;

            $email = substr($user, 0, 2) . "...@"
                     . substr($domain, 0, 2) . "...";

            $email = htmlspecialcharx($email);
        }
    }

    // generate the reveal link
    $link = "<span id=\"emailMasker$emailNum\">$maskA<a href=\"#\" "
            . "onclick=\"javascript:showCaptchaForm();return false;\" "
            . "title=\"Click to reveal the full email address\">"
            . "$email</a>$maskB</span>";

    // advance the counter
    $emailNum++;

    // return the link
    return $link;
}

// ------------------------------------------------------------------------
//
// Generate javascript support scripts for this module
//
function captchaSupportScripts($sessionKey, $okcb = false)
{
?>
   <script src='https://www.google.com/recaptcha/api.js'></script>
<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--
var RecaptchaOptions = {
    theme: "white"
};
function showCaptchaForm()
{
    document.getElementById("captchaFormDiv").style.display = "block";
    document.getElementById("captchaFormCont").innerHTML =
        "<div class=\"g-recaptcha\" data-sitekey=\"<?php echo RECAPTCHA_PUBLIC_KEY ?>\"></div>";

    setCaptchaFocus();
}
function setCaptchaFocus()
{
    var fld = document.getElementById("recaptcha_response_field");
    if (fld)
        fld.focus();
    else
        setTimeout(setCaptchaFocus, 100);
}
function hideCaptchaForm()
{
    document.getElementById("captchaFormDiv").style.display = "none";
    captchaFormStatus("", "");
}
function submitCaptchaForm()
{
    var val = Recaptcha.get_response();
    var chal = Recaptcha.get_challenge();
    if (val == "")
    {
        hideCaptchaForm();
        return;
    }
    var url = "capreq?id=<?php echo $sessionKey ?>&code="
              + encodeURIComponent(val)
              + "&chal=" + encodeURIComponent(chal);
    xmlSend(url, null, function(resp) {

        var err = resp.getElementsByTagName("captchaError");
        if (err && err.length && err[0].firstChild)
        {
            captchaFormStatus(err[0].firstChild.data, "errmsg");
            Recaptcha.reload();
            return;
        }
        var emails = resp.getElementsByTagName("email");
        if (emails && emails.length)
        {
            for (var i = 0 ; i < emails.length ; i++)
            {
                var e = emails[i].firstChild.data;
                var ele = document.getElementById("emailMasker" + i);
                ele.innerHTML = "<a href=\"mailto:" + e + "\">"
                    + encodeHTML(e) + "</a>";
            }
            hideCaptchaForm();
        }
        <?php if ($okcb) echo "$okcb(resp);" ?>
    }, null);
}
function newCaptchaImage()
{
    Recaptcha.reload();
}
function captchaFormStatus(msg, cls)
{
    var ele = document.getElementById("captchaStatusMsg");
    ele.innerHTML = "" + msg + (msg != "" ? "<br>" : "");
    ele.className = cls;
}
function captchaSolved(response)
{
    var url = "capreq?id=<?php echo $sessionKey ?>&code="
        + encodeURIComponent(response);
    xmlSend(url, null, function(resp) {

        var err = resp.getElementsByTagName("captchaError");
        if (err && err.length && err[0].firstChild)
        {
            captchaFormStatus(err[0].firstChild.data, "errmsg");
            Recaptcha.reload();
            return;
        }
        var emails = resp.getElementsByTagName("email");
        if (emails && emails.length)
        {
            for (var i = 0 ; i < emails.length ; i++)
            {
                var e = emails[i].firstChild.data;
                var ele = document.getElementById("emailMasker" + i);
                ele.innerHTML = "<a href=\"mailto:" + e + "\">"
                    + encodeHTML(e) + "</a>";
            }
            hideCaptchaForm();
        }
        <?php if ($okcb) echo "$okcb(resp);" ?>
    }, null);
}
//-->
</script>
<?php
}

// ------------------------------------------------------------------------
//
//  Generate the captcha input form
//
function captchaAjaxForm($sessionKey)
{
    echo "<div id='captchaFormDiv' style='display:none;'>"
        . "<form name='captchaAjaxForm' "
        . "onsubmit='javascript:submitCaptchaForm();return false;'>"
//        . getCaptchaSubForm($sessionKey, false, false)
        . "<div id='captchaFormCont'></div>"
        . "<div><span id='captchaStatusMsg'></span></div>"
        . "<div class=\"g-recaptcha\" data-callback=\"captchaSolved\" data-sitekey=\"" . RECAPTCHA_PUBLIC_KEY . "\"></div>"
        . "</form>"
        . "</div>";
}


// ------------------------------------------------------------------------
//
// Store session information
//
function captchaFinish($sessionKey)
{
    global $captchaEmailList;

    // start with an empty XML reply
    $xml = "";

    // generate the reply with all the emails
    if ($captchaEmailList)
    {
        // make the list of <email> tags
        for ($i = 0, $xml = false ; $i < count($captchaEmailList) ; $i++)
        {
            $xml[] = "<email>"
                     . htmlspecialcharx($captchaEmailList[$i])
                     . "</email>";
        }

        // wrap the list in an <emails> section and add it to the result
        $xml .= "<emails>" . implode("", $xml) . "</emails>";
    }

    // set the XML in the session
    $_SESSION["CAPTCHA.$sessionKey"] = array($xml);
}

// ------------------------------------------------------------------------
//
// Check submitted form data for a valid CAPTCHA entry
//
function captchaCheckPost($sessionKey)
{
    if (isLocalDev()) return array(true, false);
    if (isset($_POST["g-recaptcha-response"]))
    {
        $resp = recaptcha_check_answer(
            RECAPTCHA_PRIVATE_KEY,
            $_SERVER["REMOTE_ADDR"],
            $_POST["g-recaptcha-response"]);

        return array($resp->is_valid, $resp->error);
    }
    else
        return array(false, false);
}

// ------------------------------------------------------------------------
//
//  Show the CAPTCHA form elements for a posted form
//
function captchaSubForm($sessionKey, $errMsg, $requiredCaption)
{
    echo getCaptchaSubForm($sessionKey, $errMsg, $requiredCaption);
}

function getCaptchaSubForm($sessionKey, $errMsg, $requiredCaption)
{
    $result = "";

    if ($errMsg == "incorrect-captcha-sol")
        $result .= "<div><span class=\"errmsg\">Please verify that you're not a robot</span></div>";
    else if ($errMsg)
        $result .= "<div><span class=\"errmsg\">Something went wrong with the robot test (error code: {$errMsg})</span></div>";

    $result .= "<div class=\"g-recaptcha\" data-sitekey=\"" . RECAPTCHA_PUBLIC_KEY . "\"></div>";
    return $result;
}

?>
