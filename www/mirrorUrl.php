<?php

// translate a URL - if the URL points to the IF Archive, we'll rewrite it
// to point to the canonical URL
function urlToMirror($url)
{
    $result = preg_replace('!^https?://((www\.|mirror\.)?ifarchive(\.[a-z]+?)?\.org)/!', 'https://ifarchive.org/', $url);
    return $result;
}

?>