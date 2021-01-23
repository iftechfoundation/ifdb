<?php
include_once "local-credentials.php";
include_once "Akismet.class.php";

function akNew()
{
    return new Akismet("http://ifdb.tads.org", localAkismetKey());
}

function akSpamError($objname)
{
    return "Our anti-spam service has flagged your $objname, meaning "
        . "that it appears to contain commercial advertising in "
        . "violation of the IFDB <a href=\"tos\">Terms of Service</a>. "
        . "You can revise your text and try again; removing any hyperlinks "
        . "that point to commercial sites might reduce the chances of "
        . "being flagged again. If you feel that your entry was "
        . "unfairly classified as spam, feel free to <a href=\"contact\">"
        . "contact us</a> about the problem.";
}

?>