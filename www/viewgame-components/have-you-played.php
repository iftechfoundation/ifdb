<?php

include_once "starctl.php";

function showHaveYouPlayedThisCtl($db, $curuser, $id, $currentUserRating, bool $currentUserHasReview, bool $hidden = false) {
?>

<table class="gamerightbar haveYouPlayed<?php if ($hidden) {echo " hidden";}?>" data-gameid="<?php echo $id?>">
   <tr>
      <td>
         <h3>Have you played this game?</h3>
            <?php
if ($curuser) {
    $result = mysqli_execute_query($db, "select userid from playedgames
        where userid=? and gameid=?", [$curuser, $id]);
    $played = mysql_num_rows($result) > 0;

    $result = mysqli_execute_query($db, "select userid from wishlists
        where userid=? and gameid=?", [$curuser, $id]);
    $wishlist = mysql_num_rows($result) > 0;

    $result = mysqli_execute_query($db, "select userid from unwishlists
        where userid=? and gameid=?", [$curuser, $id]);
    $unwishlist = mysql_num_rows($result) > 0;

    // check to see if I've made any explicit cross-recommendations
    // of my own
    if ($curuser)
    {
        $result = mysqli_execute_query($db,
            "select count(*) from crossrecs
             where userid = ? and fromgame = ?", [$curuser, $id]);
        list($myCrossRecs) = mysql_fetch_row($result);
    }

    echo "<div class=indented>"

        . "<div class='viewgame__playedSection'>"
        . "<span><b>Rate it:</b></span> "
        . showStarCtl($id, "ratingStars_$id", $currentUserRating, "updateRating")
        . "<button id='submitRating_$id' class='fancy-button hidden'>Submit Rating</button>"
        . " <span id=\"rating_${id}_SaveMsg\" class=xmlstatmsg aria-live='polite'></span><br>"
        . "<a href=\"review?id=$id&userid=$curuser\">"
        . ($currentUserHasReview ? "Revise my review" : "Review it")
        . "</a><br>"
        . "<a href=\"crossrec?game=$id&edit\">"
        . ($myCrossRecs ? "Edit my suggestions ($myCrossRecs)"
                        : "Suggest similar games")
        . "</a>"
        . "</div>"

        . "<div class='viewgame__playedSection'>";
    checkboxWrite("ckPlayed_$id", "I've played it", $played, "updatePlayed");
    echo " (<a href=\"playlist?type=played\">view</a>)"
        . " &nbsp; <span id=\"ckPlayed_${id}_SaveMsg\" class=\"xmlstatmsg\">"
        . "</span><br>";
    checkboxWrite("ckWishlist_$id", "It's on my wish list", $wishlist,
                  "updateWishlist");
    echo " (<a href=\"playlist?type=wishlist\">view</a>) "
        . " &nbsp; <span id=\"ckWishlist_${id}_SaveMsg\" class=\"xmlstatmsg\">"
        . "</span><br>";
    checkboxWrite("ckUnwishlist_$id", "I'm not interested", $unwishlist,
                  "updateUnwishlist");
    echo " (<a href=\"playlist?type=unwishlist\">view</a>) "
        . " &nbsp; <span id=\"ckUnwishlist_${id}_SaveMsg\" class=\"xmlstatmsg\">"
        . "</span><br></div></div>";

 
//------------------PHP for the estimated play time control panel---------------------------------

    // Check the database to see if the user has a time vote stored for this game.
    // If one exists, get the stored time in minutes (and the time note, if present).
    $mystoredtime=0;
    $mystoredtimenote="";
    $result = mysqli_execute_query($db, "select time_in_minutes, time_note from playertimes where gameid = ? and userid = ?", [$id, $curuser]);
    if (!$result) throw new Exception("Error: " . mysqli_error($db));
    [$mystoredtime, $mystoredtimenote] = mysql_fetch_row($result);

    // At this point $mystoredtime should reflect the player's actual stored time for this game
    $my_time_as_text = "";
    $time_announcement = ""; // The string shown in the Estimated Play Time control panel
    if ($mystoredtime >= 1) {
        $my_time_as_text = convertTimeToText($mystoredtime);
        $time_announcement = "Your vote: " . $my_time_as_text;
        if ($mystoredtimenote) {
            $time_announcement .= "—\"$mystoredtimenote\"";
        }
    }
   
//-----------------------Estimated play time control panel begins----------------------------
?>

<div class=indented>
<div class='timeVoteControls viewgame__playedSection'><?php // There's an event listener attached to "timeVoteControls" ?>
<span><b>Estimated play time:</b></span>
<noscript>To use this feature, please enable JavaScript in your browser.</noscript>

<div class='submitTimeSection'>
<span class='howLong'>Vote on how long it takes to finish this game.</span><br>
<details><summary>Tips</summary>
<ul>
<li>Vote only if you've played the game.</li>
<li>Vote on how long it takes to get to one final ending without relying extensively on a walkthrough.</li>
<li>Time spent away from the game doesn't count.</li>
<li>To help players gauge how long a game with puzzles might take, you can note if your time is with or without hints.</li>
<li>Times don't need to be exact. Good-faith estimates are fine.</li>
<li>Your vote will be publicly attributed to you.</li>
</ul></details>
<p><label>Hours: <input type='number' name='hours' class='timeSection_hours' min=0 max=200></label>
<label>Minutes: <input type='number' name='minutes' class='timeSection_minutes' min=0 max=59></label></p>
<p><label>Time Note: <input type='text' name='timeNote' class='timeNote' maxlength='150' size='21' placeholder='With/without hints.'></label></p>
<button class='submitTimeButton fancy-button' type='submit'>Submit Time</button>
</div>

<div class='removeTimeSection'>
<span class='yourTime'><?php echo $time_announcement?></span><br>
<button class='removeTimeButton fancy-button'>Remove Time</button>
</div>

<span id="time_SaveMsg_<?php echo $id?>" class=xmlstatmsg aria-live='polite'></span><br>

<div class='timeSavedSection'>
<p>Your time has been submitted.</p>
</div>

</div></div>

<script nonce="<?php global $nonce; echo $nonce; ?>">

prepareHaveYouPlayedBox("<?php echo $id?>");

</script>

<?php
//-----------------------Estimated play time control panel ends------------------------------


} else {
    echo "You can rate this game, record that you've played it, "
        . "or put it on your wish list after you "
        . "<a href=\"login?dest=viewgame?id=$id\">log in</a>.";
}

?>
      </td>
   </tr>
</table>

<?php
}
?>
