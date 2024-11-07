<?php

include_once "dbconnect.php";

// ------------------------------------------------------------------------
//
// Updates the tags column of a game with its list of tags from gametags,
// and return those tags.
//
function updateGameTagsColumn($gameid) {
    $db = dbConnect();

    $result = mysqli_execute_query($db,
        "select tag from gametags where gameid = ?", [$gameid]);

    $tagList = [];
    while ([$tag] = mysql_fetch_row($result)) {
        $tagList[] = $tag;
    }

    if (count($tagList) != 0) {
        $allTags = implode(",", $tagList);
    } else {
        $allTags = null;
    }

    $result = mysqli_execute_query($db,
        "update games set tags = ? where id = ?", [$allTags, $gameid]);

    // query the new counts
    $tagInfo = [];
    if ($result && $tagList) {

        $questionMarks = implode(',', array_fill(0, count($tagList), '?'));
        $result = mysqli_execute_query($db,
            "select
               tag,
               cast(sum(gameid = ?) as int) as tagcnt,
               count(distinct gameid) as gamecnt
             from gametags
             where tag in ($questionMarks)
             group by tag
             having tagcnt != 0", array_merge([$gameid], $tagList));

        while ([$tag, $tagCnt, $gameCnt] = mysql_fetch_row($result)) {
            $tagInfo[] = [
                'name' => $tag,
                'tagcnt' => $tagCnt,
                'gamecnt' => $gameCnt,
            ];
        }
    }

    return [$result, $tagInfo];
}

?>
