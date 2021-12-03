<?php

// --------------------------------------------------------------------------
// Get the list of file types.  This returns a map of types keyed by
// TypeID, with the value as a descriptor array:
//
//   [0] = format name for UI purposes
//   [1] = viewgame description of the format
//   [2] = help about the format
//   [3] = icon file
//
function getFileTypeList()
{
    return array(
        "Game" => array("Story File",
                        false,
                        "Interpreter-based story file - requires an "
                        . "interpreter for the user's OS to play.",
                        false),
        "GameExe" => array("Story Program",
                           "Download and run this application to play "
                           . "the game.",
                           "Stand-alone executable game application.",
                           false),
        "Inst" => array("Story Installer",
                        "This Setup program automatically installs the "
                        . "game on your system.",
                        "Self-contained, executable SETUP program that "
                        . "installs the game.",
                        "img/setupicon.gif"),
        "Hints" => array("Hints",
                         "Hints for the game (this may contain spoilers).",
                         false,
                         "img/hintfileicon.gif"),
        "Misc" => array("Miscellaneous",
                        false,
                        "Miscellaneous file(s).",
                        false),
        "ReadMe" => array("ReadMe",
                          "Brief overview and installation notes.",
                          false,
                          "img/readmeicon.gif"),
        "Src" => array("Source Code",
                       "The source file(s) for the game.",
                       false,
                       "img/srcfileicon.gif"),
        "Walk" => array("Walkthrough",
                        "Full instructions to solve the game. <b>Warning: "
                        . "contains spoilers.</b>",
                        false,
                        "img/walkthruicon.gif"));
}

?>