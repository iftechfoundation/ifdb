<?php

function sendActivationEmail($email, $actcode)
{
    // build the activation link
    $actlink = "http://ifdb.tads.org/userconfirm?a=$actcode&email="
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
        contact us, please see the Contact page at ifdb.tads.org.  Please do\n
        not reply to this email - replies to this address are not monitored.\n\n";

    // build the headers
    $hdrs = "From: IFDB <noreply@ifdb.tads.org>\r\n"
            . "Content-type: Text/HTML\r\n";

    // send the message
    return mail($email, "IFDB user activation", $msg, $hdrs);
}

function genNewUserAdminLinks($verbose, $actcode, $salt)
{
    $br = ($verbose ? "<p>" : "<br>");
    return "<a href=\"http://ifdb.tads.org/userconfirm?"
        . "a=$actcode&s=$salt&c=approve\">"
        . ($verbose ? "<b>Approve</b> - " : "")
        . "Activate account and send email notice</a>"
        . "$br<a href=\"http://ifdb.tads.org/userconfirm?"
        . "a=$actcode&s=$salt&c=delete\">"
        . ($verbose ? "<b>Reject</b> - " : "")
        . "Delete account and send notice by email</a>"
        . "$br<a href=\"http://ifdb.tads.org/userconfirm?"
        . "a=$actcode&s=$salt&c=flush\">"
        . ($verbose ? "<b>Flush</b> - " : "")
        . "Delete without notice</a>";
}

function new_user_review_list($db)
{
	$result = mysql_query(
		"select
  	      substr(n.nonceid, 21) as userid, n.hash, date_format('%Y-%M-%d %H:%i', n.created),
	      u.name, u.email
		from
		  nonces as n
		  left outer join users as u
			on u.id = substr(n.nonceid, 21)
		where
		  n.nonceid like 'review user profile %'
		order by
		  n.created desc
        limit
          0, 100", $db);

	$nrows = mysql_num_rows($result);

	if ($nrows == 0) {
		echo "None";
	} else {
		for ($i = 0 ; $i < $nrows ; $i++)
		{
			list($uid, $nonce, $date, $uname, $uemail) = mysql_fetch_row($result);
			$link = "showuser?id=$uid&unlock=$nonce";
			echo "<a href=\"$link\">$uname</a> &lt;$uemail&gt; $date<br>";
		}
	}
}

?>