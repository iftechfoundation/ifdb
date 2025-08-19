<div>
              <h3>External Links</h3>
                    <?php


if ($foundGame)
{
    echo "<div class='viewgame__foundGame'>";

    $playOnlineButton = "";

    // if we have an HTML playable game, set up a play online link
    if ($playOnlineInterpreterType == "HTML" && $primaryPlayOnlineURL) {
        $ptarget = (is_kindle() ? "" : "target=\"_blank\"");
        $playOnlineButton = "<a href=\"$primaryPlayOnlineURL\" $ptarget "
            . "title=\"Play this game right now in your browser. No "
            . "installation is required.\" class=\"fancy-button viewgame__playOnline\">Play Online"
            . "</a>";
        echo "$playOnlineButton<br>";
    }

    // if we have a Parchment-capable game, set up a play-with-Parchment link
    if ($playOnlineInterpreterType == "Parchment" && $primaryPlayOnlineURL) {
        $ptarget = (is_kindle() ? "" : "target=\"_blank\"");
        $playOnlineButton = "<a href=\"$primaryPlayOnlineURL\" $ptarget "
            . "title=\"Play this game right now in your browser, using "
            . "the Parchment interpreter. No installation is required.\" "
            . "class=\"fancy-button viewgame__playOnline\">Play Online"
            . "</a>";
        echo "$playOnlineButton<br>";
    }

    // if we have an ADRIFT game, set up a play-online link
    if ($playOnlineInterpreterType == "ADRIFT" && $primaryPlayOnlineURL) {
        $atarget = (is_kindle() ? "" : "target=\"_blank\"");
        $playOnlineButton = "<a href=\"{$primaryPlayOnlineURL}\" $atarget "
            . "title=\"Play this game right now in your browser. No "
            . "installation is required.\""
            . "class=\"fancy-button viewgame__playOnline\">Play Online"
            . "</a>";
        echo "$playOnlineButton<br>";
    }

    echo "</div>";
}
                    ?>
                 </div>

                   <?php
if (count($links) == 0 && $dlnotes == "") {
    echo "<i>There are no known download links for this game.</i>";
} else {
    // put the download notes at the top
    if ($dlnotes != "") {
        $dlnotes = preg_replace(
            "/([a-z0-9][\/.])([a-z0-9])/i", "$1$zwsp$2", $dlnotes);
        echo "<i>$dlnotes</i>";
    }

    // count compressed files
    $zips = [];
    $zipnotes = [];
    foreach ($links as $link) {
        $c = $link['compression'];
        if (!$c) {
            continue;
        }
        if (isset($zips[$c]) && $zips[$c]) {
            $zips[$c][0] += 1;
        }
        else {
            $result = mysqli_execute_query($db, "select `desc` from filetypes
                where id = ?", [$c]);
            $zips[$c] = [1, "", mysql_result($result, 0, "desc")];
            $zipnotes[] = $c;
        }
    }

    // use footnotes for any compression formats appearing more than once
    $footnoteid = 1;
    foreach ($zipnotes as $c) {
        if ($zips[$c][0] > 1) {
            $zips[$c][1] = getFootnoteStar($footnoteid++);
        }
    }

    // display the link list
    echo "<ul class=\"downloadlist\">";
    foreach ($links as $i => $link) {
        $url = $link['url'];
        $zipped = (strcasecmp(substr($url, -4), ".zip") == 0);
        $fmtisgame = $link['isgame'];
        $linktitle = zeroWidthSpaceUnderscores(
            htmlspecialcharx($link['title']
        ));
        $linkdesc = htmlspecialcharx($link['desc']);
        $fmtid = $link['fmtid'];
        $fmtexternid = $link['fmtexternid'];
        $fmtos = $link['osid'];
        $fmtosvsn = $link['osvsn'];
        $fmtname = $link['fmtname'];
        $fmtdesc = $link['fmtdesc'];
        $fmticon = $link['fmticon'];
        $fmtclass = $link['fmtclass'];
        $fmtcomp = $link['compression'];
        $compPri = zeroWidthSpaceUnderscores(
            htmlspecialcharx($link['compressedprimary'])
        );
        $osname = isset($link['osname']) ? $link['osname'] : "";
        $osvsnname = isset($link['osvsnname']) ? $link['osvsnname'] : "";
        $osicon = isset($link['osicon']) ? $link['osicon'] : "";
        [$isGameLink, $onlineInterpreterType, $playOnlineURL] = constructPlayOnlineURL($link);

        // if it's an executable format, qualify the format name with
        // the OS name, since an executable is native to a particular OS
        $typename = ($fmtclass == 'X' ? "$osname $fmtname" : "");

        // if the OS version name is distinct from the OS name, mention
        // the minimum version in the desription
        if ($fmtos != $fmtosvsn && $fmtosvsn != "")
            $typename .= " ($osvsnname and later)";

        // if there's no title, use the base name from the URL as the title
        if ($linktitle == "") {
            $prsurl = parse_url($url);
            $linktitle = htmlspecialcharx(basename($prsurl['path']));
        }

        // map the URL to the user's selected IF Archive mirror, if
        // applicable, and fix it up for HTML display
        $url = fixUrl(urlToMirror($url));

        // add a note about zip format if applicable
        $zipnote = "";
        $zipstar = "";
        if ($fmtcomp) {
            $z = $zips[$fmtcomp];
            if ($z[0] == 1) {
                $zipnote = " <i>({$z[2]})</i>";
            }
            else
                $zipstar = " <span class=zipstar>{$z[1]}</span>";
        }

        // generate the information
        echo "<li>";

        $iconsrc = ($osicon ? "opsys?geticon=$fmtos" :
                    ($fmticon ? "fileformat?geticon=$fmtid" :
                     false));
        if ($iconsrc) {
            echo "<a href=\"$url\"><img class=\"fileIcon\" src=\"$iconsrc\"></a>";
        } else {
            echo "<div class=\"fileIcon\"></div>";
        }

        echo "<div class='downloaditem'>
              <a href=\"$url\"><b>$linktitle</b></a>$zipstar<br>";

        $unbox_supported = [17, 22, 23, 41];

        if (in_array($fmtcomp, $unbox_supported) && (strpos($url, 'https://ifarchive.org') === 0)) {
            echo "<div class='details'><a href='https://unbox.ifarchive.org/?url=" . urlencode($url) . "'>View Contents</a></div>";
        }
        
        if ($compPri) {
            echo "<span class=dlzipmain><img src=\"/img/blank.gif\" "
                . "class=\"zip-contents-arrow\">Contains "
                . "<b>$compPri</b></span><br>";
        }
       
        if ($linkdesc) {
            $linkdesc = preg_replace(
                "/([a-z0-9][\/.])([a-z0-9])/i", "$1$zwsp$2", $linkdesc);
            echo "<span class=dlnotes>$linkdesc<br></span>";
        }

        // Don't show "Play Online" mini link for HTML links; that's what the link itself does
        //   ... unless the HTML is zipped, in which case we *do* need a Play Online link
        $playOnlineMiniLink = "";
        if ($playOnlineURL && ($onlineInterpreterType !== "HTML" || $compPri)) {
            $playOnlineMiniLink = "<div class='details'><a href='$playOnlineURL'>Play Online</a></div>";
            echo $playOnlineMiniLink;
        }

        // If we're linking to a game that needs an interpreter, expand the format 
        // description to include instructions for downloading and running it.
        if ($fmtclass == "G" && $fmtexternid != "tads3web" && $fmtexternid != "hypertextgame") {
            $format_instructions = "";
            if ($playOnlineMiniLink) {
                // These instructions are following a "play online" mini link, so we can begin with "Or"
                $format_instructions = "Or " ;
            } else {
                $format_instructions = "To play, you can ";
            }
            $format_instructions .= "download <a href=\"$url\">$linktitle</a>";
            if ($zipnote || $zipstar) {
                $format_instructions .= ", unzip it,";
            }
            $format_instructions .= " and run the game in " . $fmtdesc . ".";
            $fmtdesc = $format_instructions;
        }

        // build the full type description
        $typedesc = (($fmtdesc && $typename) ? "$typename: $fmtdesc" :
                     ($fmtdesc ? $fmtdesc : $typename));

        if ($typedesc || $zipnote) {
            echo "<span class=fmtnotes>$typedesc$zipnote</span>";
        }
        echo "</div></li>";
    }
    echo "</ul>";

    // add a footnote on each compressed format that appeared more than once
    foreach ($zipnotes as $zipnote) {
        $z = $zips[$zipnote];
        if ($z[0] > 1)
            echo "<div class=zipnote><span class=zipstar>{$z[1]}</span> "
                . "<span class=zipnote>{$z[2]}</span></div>";
    }
}
         ?>
