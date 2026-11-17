<?php

function logInAndReturnQuery()
{
    // come back to the current page when we're done
    $dest = urlencode($_SERVER['PHP_SELF']);

    // add the query portion of the current URL as well
    if (isset($_SERVER['QUERY_STRING'])) {
        // add the destination string
        $dest .= urlencode("?" . $_SERVER['QUERY_STRING']);

        // add the referrer as well
        if (isset($_SERVER['HTTP_REFERER']))
            $dest .= urlencode("&httpreferer=" .
                               urlencode($_SERVER['HTTP_REFERER']));
    }

    // return the result
    return $dest;
}

function switchUserAndReturnHRef()
{
    return "login?switch&dest=" . logInAndReturnQuery();
}

function logInAndReturnHRef($smallPage = false)
{
    $href = "login?dest=" . logInAndReturnQuery();
    if ($smallPage)
        $href .= "&small=1";
    return $href;
}

function redirect_to_login_page($smallPage = false)
{
    $href = logInAndReturnHRef($smallPage);
    header("HTTP/1.1 301 Moved Permanently");
    header("Content-Type: text/html");
    header("Location: $href");

    echo "Redirecting to $href<br><br>";
    echo "<a href=\"$href\">Click here if your browser doesn't redirect
        automatically</a>";
}

?>
