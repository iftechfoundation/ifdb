<?php
$json = isset($_REQUEST['json']);
if (isset($_REQUEST['ifiction']) || $json)
{
    if (!$json) {
        header("Content-Type: text/xml");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
    }

    // if an error occurred, show the error
    if ($errMsg) {
        $error_fields = [
            'errorCode' => $errCode,
            'errorMessage' => $errMsg,
        ];
        if ($json) {
            send_json_response($error_fields);
        } else {
            echo "<viewgame xmlns=\"http://ifdb.org/api/xmlns\">";
            echo serialize_xml($error_fields);
            echo "</viewgame>";
        }
        exit();
    }

    $identification = [];
    $bibliographic = [];
    $contacts = [];
    $ifdb_section = [];

    $story_obj = [
        'colophon' => [
            'generator' => 'ifdb.org/viewgame',
            'generatorversion' => 1,
            'originated' => date('Y-m-d', strtotime($moddate2)),
        ],
        'identification' => &$identification,
        'bibliographic' => &$bibliographic,
        'contacts' => &$contacts,
    ];

    if ($json) {
        $identification['ifids'] = $ifids;
        if ($bafsid)
            $identification['bafn'] = $bafsid;

        $story_obj['ifdb'] = &$ifdb_section;
    } else {
        foreach($ifids as $i)
            $identification[] = ['ifid' => $i];
        if ($bafsid)
            $identification[] = ['bafn' => $bafsid];

        $story_obj['ifdb'] = [
            '_attrs' => ['xmlns' => 'http://ifdb.org/api/xmlns'],
            '_contents' => &$ifdb_section,
        ];
    }

    // Figure the format.  This is actually a bit tricky, since we keep
    // different data from babel on this count.  First, try basing this
    // on the development system ID, since many development systems map
    // to specific output formats.
    $formatMap = [
        "TADS 2" => "tads2",
        "TADS 3" => "tads3",
        "Hugo" => "hugo",
        "AGT" => "agt",
        "Alan 2" => "alan2",
        "Alan 3" => "alan3",
        "ADRIFT" => "adrift",
        "AdvSys" => "advsys",
        "Level 9" => "level9",
        "Magnetic Scrolls" => "magscrolls"
    ];
    $format = false;
    if (isset($formatMap[$system])) {
        $format = $formatMap[$system];
    }

    // If that didn't work, scan the download links.  Check the format ID
    // for each link: most of our story file formats map to babel formats.
    // So, if we can find a story file among the downloads, we'll know the
    // babel format.
    if (!$format) {
        $linkTypeMap = [
            "tads2" => "tads2",
            "tads3" => "tads3",
            "zcode" => "zcode",
            "blorb/zcode" => "zcode",
            "glulx" => "glulx",
            "blorb/glulx" => "glulx",
            "hugo" => "hugo",
            "alan2" => "alan2",
            "alan3" => "alan3",
            "agt" => "agt",
            "adrift" => "adrift",
            "advsys" => "advsys",
            "executable" => "executable"
        ];

        foreach ($links as $l) {
            $xid = $l['fmtexternid'];
            if (isset($linkTypeMap[$xid])) {
                $format = $linkTypeMap[$xid];
                break;
            }
        }
    }

    if ($format) {
        if ($json) {
            $identification['format'] = $format;
        } else {
            $identification[] = ['format' => $format];
        }
    }

    $bibliographic['title'] = $title;
    $bibliographic['author'] = $author;

    // Pull out the language code from the language string, which
    // we've formatted as something like "English (en-US)".  If there
    // are no parens, we must not have found a name for the language,
    // so the whole language string is the identifier.
    if (preg_match("/\(([-a-z]+)\)$/i", $language, $match))
        $language = $match[1];
    if ($language)
        $bibliographic['language'] = $language;

    $firstpublished = $pubFull ? date("Y-m-d", strtotime($pubFull)) : $pubYear;
    if ($firstpublished)
        $bibliographic['firstpublished'] = $firstpublished;

    if ($genre)
        $bibliographic['genre'] = $genre;

    $desc = fixDesc($rawDesc, $json ? 0 : FixDescIfic);
    if ($desc)
        $bibliographic['description'] = $desc;

    if ($website)
        $contacts['url'] = $website;

    $ifdb_section['tuid'] = $id;
    $ifdb_section['pageversion'] = $pagevsn;
    $ifdb_section['link'] = get_root_url() . "viewgame?id=$id";
    if ($hasart) {
        $ifdb_section['coverart'] = [
            'url' => get_root_url() . "coverart?id=$id&version=$pagevsn",
        ];
    }

    if ($rounded_median_time >= 1) {
        if ($json) {
            $ifdb_section['playTimeInMinutes'] = $rounded_median_time;
        } else {
            $ifdb_section[] = ['playTimeInMinutes' => $rounded_median_time];
        }
     }

    // Include the same URL that we use for the big "Play Online" button
    if ($primaryPlayOnlineURL) {
        if ($json) {
            $ifdb_section['primaryPlayOnlineUrl'] = $primaryPlayOnlineURL;
        } else {
            $ifdb_section[] = ['primaryPlayOnlineUrl' => $primaryPlayOnlineURL];
        }
    }
        
    if ($links) {
        // load the translation table for the file formats
        $result = mysqli_execute_query($db,
            "select id, externid from filetypes");
        $fmtMap = [];
        while ([$id, $ext] = mysql_fetch_row($result)) {
            $fmtMap[$id] = $ext;
        }
        $links_section = [];
        $ifdb_section['downloads'] = ['links' => &$links_section];
        foreach ($links as $l) {
            $link_obj = [
                'url' => $l['url'],
            ];
            [$isGameLink, $onlineInterpreterType, $playOnlineURL] = constructPlayOnlineURL($l);
            if ($isGameLink && $playOnlineURL) $link_obj['playOnlineUrl'] = $playOnlineURL;
            if ($l['title']) $link_obj['title'] = $l['title'];
            if ($l['desc']) $link_obj['desc'] = $l['desc'];
            if ($l['isgame']) $link_obj['isGame'] = true;
            if ($l['fmtexternid']) $link_obj['format'] = $l['fmtexternid'];
            if (isset($l['osext'])) {
                if (isset($l['osvsn'])) {
                    $link_obj['os'] = $l['osext'] . "." . $l['osvsnext'];
                } else {
                    $link_obj['os'] = $l['osext'] . ".";
                }
            }
            if ($l['compression']) $link_obj['compression'] = $fmtMap[$l['compression']];
            if ($l['compressedprimary']) $link_obj['compressedPrimary'] = $l['compressedprimary'];

            if ($json) {
                $links_section[] = $link_obj;
            } else {
                $links_section[] = ['link' => $link_obj];
            }
        }
    }
    if (!$should_hide) {
        $ifdb_section['averageRating'] = $ratingAvg;
        [$stars] = roundStars($ratingAvg);
        if ($stars) {
            $ifdb_section['starRating'] = $stars;
        }
        $ifdb_section['ratingCountAvg'] = $ratingCntAvg;
        $ifdb_section['ratingCountTot'] = $ratingCntTot;
    }

    $result = mysqli_execute_query($db,
        "select
           tag,
           cast(sum(gameid = ?) as int) as tagcnt,
           count(distinct gameid) as gamecnt
         from gametags
         where tag in (select tag from gametags where gameid = ?)
         group by tag", [$ifdb_section['tuid'], $ifdb_section['tuid']]);

    $tagInfo = [];
    while ([$tag, $tagCnt, $gameCnt] = mysql_fetch_row($result)) {
        $tagInfo[] = [
            'name' => $tag,
            'tagcnt' => $tagCnt,
            'gamecnt' => $gameCnt,
        ];
    }

    if ($json) {
        $ifdb_section['tags'] = $tagInfo;
    } else {
        $ifdb_section['tags'] = array_map(function ($tag) { return ['tag' => $tag]; }, $tagInfo);
    }

    if ($json) {
        send_json_response($story_obj);
    } else {
        echo "<ifindex version=\"1.0\" "
            .    "xmlns=\"http://babel.ifarchive.org/protocol/iFiction/\">";
        echo serialize_xml(['story' => $story_obj]);
        echo '</ifindex>';
    }
    exit();
}
?>