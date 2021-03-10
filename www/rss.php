<?php

//
// An RSS item list is an array of RSS items.  Each item is an
// array(date, body), where "date" is the timestamp for the item
// in the base MySQL datetime select format (i.e., without a
// date_format() wrapper), and "body" is the item's body text as
// a string.  Each body must be a complete <item>...</item> XML
// fragment.
//

// send RSS
function sendRSS($title, $link, $desc, $items, $limit)
{
    // sort the items by date descending
    usort($items, "sortRssByItemDate");

    // if a limit was given limit to the first $limit items
    if ($limit)
        array_splice($items, $limit);

    // get just the item bodies, and combine into a string
    $body = implode("", array_map("getRssItemBody", $items));
    
    // send the RSS content-type header
    header("Content-Type: application/rss+xml");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // rss-encode the parameters
    $title = rss_encode(htmlspecialcharx($title));
    $link = rss_encode(htmlspecialcharx($link));
    $desc = rss_encode(htmlspecialcharx($desc));
    $body = rss_encode($body);

    // send the channel header, followed by the body
    echo "<?xml version=\"1.0\"?>"
        . "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">"
        . "<channel>"
        . "<title>$title</title>"
        . "<link>$link</link>"
        . " <atom:link href=\"$link&amp;rss=gamenews\" rel=\"self\" type=\"application/rss+xml\" />"
        . "<description>$desc</description>"
        . "<language>en-us</language>"
        . $body
        . "</channel>"
        . "</rss>";
}

// Sort RSS by date descending
function sortRssByItemDate($a, $b)
{
    // do the reversed compare to get descending order
    return strcmp($b[0], $a[0]);
}

// get the body of an rss item
function getRssItemBody($item)
{
    return $item[1];
}



?>