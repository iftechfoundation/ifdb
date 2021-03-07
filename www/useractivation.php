<?php

function sendActivationEmail($email, $actcode)
{
    // build the activation link
    $actlink = get_root_url() . "userconfirm?a=$actcode&email="
               . urlencode($email);

    // build the message
    $msg =
        "Welcome to IFDB!\n\n
        <p>You can start using your new account as soon as you activate it.\n
        To activate, click on the link below:\n\n
        <p><a href='$actlink'>$actlink</a>\n\n
        <p>If your email program doesn't let you open the link by\n
        clicking on it, copy and paste the entire link into your Web\n
        browser's Address bar.\n\n
        <p>Thank you for registering your new user account.  If you need to\n
        contact us, please see the Contact page at ifdb.org.  Please do\n
        not reply to this email - replies to this address are not monitored.\n\n";

    // build the headers
    $hdrs = "From: IFDB <noreply@ifdb.org>\r\n"
            . "Content-type: Text/HTML\r\n";

    // send the message
    return send_mail($email, "IFDB user activation", $msg, $hdrs);
}

function genNewUserAdminLinks($verbose, $actcode, $salt)
{
    $br = ($verbose ? "<p>" : "<br>");
    return "<a href=\"" . get_root_url() . "userconfirm?"
        . "a=$actcode&s=$salt&c=approve\">"
        . ($verbose ? "<b>Approve</b> - " : "")
        . "Send activation email</a>"
        . "$br<a href=\"" . get_root_url() . "userconfirm?"
        . "a=$actcode&s=$salt&c=flush\">"
        . ($verbose ? "<b>Delete</b> - " : "")
        . "Delete user completely</a>"
        . "$br<a href=\"" . get_root_url() . "userconfirm?"
        . "a=$actcode&s=$salt&c=ban\">"
        . ($verbose ? "<b>Ban</b> - " : "")
        . "Forbid login; hide reviews, comments, and profile</a>";
}

?>