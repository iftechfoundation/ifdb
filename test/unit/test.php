<?php
include_once "../../www/util.php";

$tests = [
    # Do nothing
    ["hello world", "hello world"],
    ["hello <I>world</I>", "hello <I>world</I>"],
    ["hello <I>world</I> <I>bla</i> bla", "hello <I>world</I> <I>bla</i> bla"],
    ["hello <b><I>world</I></b> <I>bla</i> bla", "hello <b><I>world</I></b> <I>bla</i> bla"],
    ["hello <b><I><u>world</u></I></b> <I>bla</i> bla", "hello <b><I><u>world</u></I></b> <I>bla</i> bla"],
    ["hello <p>world</p>", "hello <p>world</p>"],

    # Add tags at the end
    ["hello <I>world bla bla", "hello <I>world bla bla</i>"],
    ["hello <I><b>world</b> bla bla", "hello <I><b>world</b> bla bla</i>"],
    ["hello <I><u><b>world</b> bla bla", "hello <I><u><b>world</b> bla bla</u></i>"],

    # Tags in the wrong order -- this is just documenting the current behavior. I don't know if it's desired.
    # It's probably not this function's job to fix bad HTML
    ["hello <I><B>world</I></B>", "hello <I><B>world</b></I></B>"],

    # Unknown tag
    ["hello <unknown><I>world</I>", "hello &lt;unknown&gt;<I>world</I>"],

    # Removed spoiler tag (in regular mode)
    ["hello <spoiler>secret</spoiler> <I>world</I>", "hello  <I>world</I>"],

    # Replaced spoiler tag (in RSS mode)
    ["hello <spoiler>secret</spoiler> <I>world</I>", "hello [spoilers] <I>world</I>", FixDescRSS],

    # Strip newlines
    ["\nhello world\n", "hello world"],
    ["\r\nhello world\r\n", "hello world"],
];

foreach ($tests as &$test) {
    $output = fixDesc($test[0], count($test) == 3 ? $test[2] : 0);
    if ($output != $test[1]) {
        echo "Failed test.\nInput: {$test[0]}\nOutput: {$output}\nExpected: {$test[1]}\n";
    }
}