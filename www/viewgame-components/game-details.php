         <h2>Game Details</h2>
         <div class=indented>
            <span class=notes>
               <?php if (!isEmpty($language))
                  echo "Language: " . htmlspecialcharx($language) . "<br>" ?>
               <?php if(!isEmpty($pubFull))
                  echo "First Publication Date: $pubFull<br>"; ?>
               Current Version: <?php
                  echo !isEmpty($version) ? htmlspecialcharx($version) : "<i>Unknown</i>"
               ?><br>
               <?php if (!isEmpty($license))
                   echo "License: $license<br>" ?>
               <?php if (!isEmpty($system))
                   echo "Development System: <a href=\"search?searchfor=system:" . urlencode($system) . "\">".htmlspecialchars($system)."</a><br>" ?>
               <?php if (!isEmpty($forgiveness))
                   echo helpWinLink("help-forgiveness", "Forgiveness Rating")
                       . ": $forgiveness<br>" ?>
               <?php
                    if (count($ifids) > 0) {
                        echo "<div id=\"ifwiki-link\" class=\"displayNone\"><a href=\"https://www.ifwiki.org/\">IFWiki</a>: <a href=\"https://www.ifwiki.org/index.php?title=IFID:"
                            .htmlspecialcharx($ifids[0])."\">$title</a><br></div>";
                        $jifid = htmlspecialcharx(str_replace("\"", "&#34;", $ifids[0]));
                        if (!$cssOverride) {
                        ?>
                        <script type="module" nonce="<?php global $nonce; echo $nonce; ?>">
                            if (await check_ifid_in_ifwiki("<?php echo $jifid ?>")) {
                                document.getElementById("ifwiki-link").style.display = "initial";
                            }
                        </script>
                        <?php
                        }
                    }
                ?>
               <?php if(!isEmpty($bafsid))
                   echo helpWinLink("help-bafs", "Baf's Guide ID")
                        . ":
                     <a class=silent
                      href=\"https://web.archive.org/web/20110100000000/http://www.wurb.com/if/game/$bafsid\"
                        title=\"Go to the Baf's Guide listing for this game\">
                        $bafsid</a>
                     <br>" ?>

            </span>
            <?php

$caption = helpWinLink("help-ifid", "IFID");

if (count($ifids) == 0 || isEmpty($ifids[0]))
    echo "<span class=notes>$caption: <i>Unknown</i></span><br>";
else if (count($ifids) == 1)
    echo "<span class=notes>$caption: ".htmlspecialcharx($ifids[0])."</span><br>";
else {
    echo "<table border=0 cellpadding=0 cellspacing=0>
        <tr><td><span class=notes>{$caption}s:&nbsp;</span></td>
        <td><span class=notes>".htmlspecialcharx($ifids[0])."</span></td></tr>";
    for ($i = 1 ; $i < count($ifids) ; $i++)
        echo "<tr><td></td><td><span class=notes>".htmlspecialcharx($ifids[$i])."</span></td></tr>";
    echo "</table>";
}
            ?>
            <span class=notes>
              <?php echo helpWinLink("help-tuid", "TUID");
                 ?>:
                 <?php echo htmlspecialcharx($id) ?>
            </span><br>

            <?php
// show the outbound references, if any
if (count($xrefs) != 0) {
    echo "<br>";
    foreach ($xrefs as $x) {
        $x['author'] = collapsedAuthors($x['author']);
        $fn = $x['fromname'];
        $fn = str_replace(array("<language>", "<system>"),
                          array($languageNameOnly, $system),
                          $fn);
        echo "<span class=notes>" . initCap($fn)
            . " <a href=\"viewgame?id={$x['toID']}\"><i>{$x['toTitle']}</i></a>"
            . ", by {$x['author']}</span><br>";
    }
}

// show the inbound references, if any
if (count($inrefs) != 0 && !$historyView) {
    echo "<br>";
    for ($i = 0 ; $i < count($inrefs) ; ) {
        $x = $inrefs[$i];
        for ($j = $i + 1 ;
             $j < count($inrefs) && $inrefs[$j]['reftype'] == $x['reftype'] ;
             $j++) ;

        $cnt = $j - $i;
        if ($cnt > 1) {
            echo "<span class=notes>" . initCap($x['tonames'])
                . ":</span><br><div class=\"indented\">";
        } else {
            $toname = str_replace(
                array('<language>', '<system>'),
                array($x['language'], $x['system']),
                $x['toname']);
            echo "<span class=notes>" . initCap($toname) . " </span>";
        }

        for ( ; $i < $j ; ++$i) {
            $x = $inrefs[$i];
            $x['author'] = collapsedAuthors($x['author']);
            echo "<span class=notes>"
                . "<a href=\"viewgame?id={$x['fromid']}\">"
                . "<i>{$x['title']}</i></a>"
                . ", by {$x['author']}</span><br>";
        }

        if ($cnt > 1)
            echo "</div>";
    }
}
            ?>

         </div>