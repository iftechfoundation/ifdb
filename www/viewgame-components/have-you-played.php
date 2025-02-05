<table class=gamerightbar>
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
            ?>
            <script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
            <!--
function updateRating(rating)
{
    if (rating) {
        if (submitRating.style.display === 'block') return;
    } else {
        submitRating.style.display = 'none';
    }
    jsonSend("setrating?game=<?php echo $id?>&rating=" + rating,
             "ratingSaveMsg");
}
function updatePlayed(id, stat)
{
    jsonSend("setplayed?game=<?php echo $id?>&played="
             + (ckboxGetObj("ckPlayed").checked ? "1" : "0"),
             "ckPlaySaveMsg", (d) => {updatePlaylistCount(d.newCount);});
}
function updateWishList(id, stat)
{
    jsonSend("setwishlist?game=<?php echo $id?>&add="
             + (ckboxGetObj("ckWishList").checked ? "1" : "0"),
             "ckWishlistSaveMsg", (d) => {updateWishlistCount(d.newCount)});
}
function updateUnwishList(id, stat)
{
    jsonSend("setunwishlist?game=<?php echo $id?>&add="
             + (ckboxGetObj("ckUnwishList").checked ? "1" : "0"),
             "ckUnwishlistSaveMsg");
}
//-->
</script>
    <?php


    // check to see if I've made any explicit cross-recommendations
    // of my own
    if ($curuser)
    {
        $result = mysqli_execute_query($db,
            "select count(*) from crossrecs
             where userid = ? and fromgame = ?", [$curuser, $id]);
        list($myCrossRecs) = mysql_fetch_row($result);
    }

    initStarControls();
    global $nonce;
    echo "<style nonce='$nonce'>\n"
        . ".viewgame__playedSection { margin: 1.25ex 0 0.75ex 0; }\n"
        . "#submitRating { display: none; }\n"
        . "</style>\n";

    echo "<div class=indented>"

        . "<div class='viewgame__playedSection'>"
        . "<span id=\"ratingCaption\"><b>Rate it:</b></span> "
        . showStarCtl("ratingStars", $currentUserRating,
                      "updateRating", "mouseOutRating")
        . "<button id='submitRating' class='fancy-button'>Submit Rating</button>"
        . "<script nonce='$nonce'>\n"
        . "let arrowKeys = new Set(['ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown']);"
        . "ratingStars.addEventListener('keydown', e => {\n"
        . "  if (arrowKeys.has(e.key)) submitRating.style.display = 'block'\n"
        . "});\n"
        . "submitRating.addEventListener('click', () => {\n"
        . "submitRating.style.display = 'none';\n"
        . "updateRating(ratingStars.querySelector('input:checked').value);\n"
        . "});\n"
        . "</script>"
        . " <span id=\"ratingSaveMsg\" class=xmlstatmsg aria-live='polite'></span><br>"
        . "<a href=\"review?id=$id&userid=$curuser\">"
        . ($currentUserReview != "" ? "Revise my review" : "Review it")
        . "</a><br>"
        . "<a href=\"crossrec?game=$id&edit\">"
        . ($myCrossRecs ? "Edit my suggestions ($myCrossRecs)"
                        : "Suggest similar games")
        . "</a>"
        . "</div>"

        . "<div class='viewgame__playedSection'>";
    checkboxWrite("ckPlayed", "I've played it", $played, "updatePlayed");
    echo " (<a href=\"playlist?type=played\">view</a>)"
        . " &nbsp; <span id=\"ckPlaySaveMsg\" class=\"xmlstatmsg\">"
        . "</span><br>";
    checkboxWrite("ckWishList", "It's on my wish list", $wishlist,
                  "updateWishList");
    echo " (<a href=\"playlist?type=wishlist\">view</a>) "
        . " &nbsp; <span id=\"ckWishlistSaveMsg\" class=\"xmlstatmsg\">"
        . "</span><br>";
    checkboxWrite("ckUnwishList", "I'm not interested", $unwishlist,
                  "updateUnwishList");
    echo " (<a href=\"playlist?type=unwishlist\">view</a>) "
        . " &nbsp; <span id=\"ckUnwishlistSaveMsg\" class=\"xmlstatmsg\">"
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
<div id='timeVoteControls' class='viewgame__playedSection'><?php // There's an event listener attached to "timeVoteControls" ?>
<span id="timeVoteControlsCaption"><b>Estimated play time:</b></span>
<noscript>To use this feature, please enable JavaScript in your browser.</noscript>

<div id='submitTimeSection'>
<span id='howLong'>Vote on how long it takes to finish this game.</span><br>
<details><summary>Tips</summary>
<ul>
<li>Vote only if you've played the game.</li>
<li>Vote on how long it takes to get to one final ending without relying extensively on a walkthrough.</li>
<li>Time spent away from the game doesn't count.</li>
<li>To help players gauge how long a game with puzzles might take, you can note if your time is with or without hints.</li>
<li>Times don't need to be exact. Good-faith estimates are fine.</li>
<li>Your vote will be publicly attributed to you.</li>
</ul></details>
<p><label for='hours'>Hours:</label>
<input type='number' name='hours' id='hours' min=0 max=200>
<label for='minutes'>Minutes:</label>
<input type='number' name='minutes' id='minutes' min=0 max=59></p>
<p><label for='timeNote'>Time Note:</label>
<input type='text' name='timeNote' id='timeNote' maxlength='150' size='21' placeholder='With/without hints.'></p>
<button id='submitTimeButton' class='fancy-button' type='submit'>Submit Time</button>
</div>

<div id='removeTimeSection'>
<span id='yourTime'><?php echo $time_announcement?></span><br>
<button id='removeTimeButton' class='fancy-button'>Remove Time</button>
</div>

<span id="timeSaveMsg" class=xmlstatmsg aria-live='polite'></span><br>

<div id='timeSavedSection'>
<p id='timeSubmittedMessage'>Your time has been submitted.</p>
</div>


<script nonce="<?php global $nonce; echo $nonce; ?>">

// When the user clicks the "Submit Time" button, get the input 
// from the hours and minutes fields, validate it, convert it to 
// minutes only, and save it to the database (with the note, if present).
function submitTime() {
    var new_hours = document.getElementById('hours').value;
    var new_minutes = document.getElementById('minutes').value;
    var time_note = document.getElementById('timeNote').value.trim();
    if (new_hours == "") {    // If the hours field was blank, interpret it as 0
        new_hours = 0;
    }
    if (new_minutes == "") {  // If the minutes field was blank, interpret it as 0
        new_minutes = 0;
    }
    var hours_are_digits = isOnlyDigits(new_hours); 
    var minutes_are_digits = isOnlyDigits(new_minutes);
    if (hours_are_digits == false || minutes_are_digits == false) {
        document.getElementById("timeSaveMsg").innerHTML="Please enter valid numbers.";
        return;
    }
    if (new_hours > 200) {     // Negative numbers will hopefully be eliminated by "Digits only"
        document.getElementById("timeSaveMsg").innerHTML="For hours, please enter a number from 0 to 200.";
        return;
    }
    if (new_minutes > 59) {
        document.getElementById("timeSaveMsg").innerHTML="For minutes, please enter a number from 0 to 59.";
        return;
    }
    var new_time = convertToMinutes(new_hours, new_minutes);
    if (new_time < 1) {
        document.getElementById("timeSaveMsg").innerHTML="The total time must be at least one minute.";
        return;
    }
    // Time is valid, as far as we can tell.
    // If the game isn't already on the user's list of played games, add it.
    var played_it_checkbox = document.getElementById('ckBoxckPlayed');
    if (!played_it_checkbox.checked) {
        played_it_checkbox.checked = true;
        updatePlayed();
    }
    // Submit the time.
    jsonSend(`settime?game=<?= $id?>&newtime=${new_time}&note=${encodeURIComponent(time_note)}`, "timeSaveMsg", swapTimeSections);
}
// Attach the submitTime function to the "Submit Time" button.
document.getElementById("submitTimeButton").addEventListener("click", submitTime);


// Remove the user's time for this game from the database.
function removeTime() {
    jsonSend("settime?game=<?php echo $id?>&newtime=0", "timeSaveMsg", swapTimeSections);
}
// Attach the removeTime function to the Remove Time button.
document.getElementById("removeTimeButton").addEventListener("click", removeTime);


// Check whether a string contains only digits.
function isOnlyDigits(a_string) {
    if (/^\d+$/.test(a_string)) {
        return true;
    }
    return false;
}


// Take hours and minutes and convert them into just minutes
// so we can store the time as minutes in the database.
function convertToMinutes(hours, minutes) {
    // They need to be integers so they don't get concatenated when we're trying to add them
    hours = Number(hours); 
    minutes = Number(minutes);
    hours_into_minutes = hours * 60;
    total_minutes = hours_into_minutes + minutes;
    return total_minutes;
}


// The Estimated Play Time control panel has a "submitTimeSection," a "removeTimeSection," and
// a "timeSavedSection." Only one of the sections should be visible at a time. These functions
// show and hide the sections.

function showSubmitTimeSection() { 
    submitTimeSection.style.display = "block";
    removeTimeSection.style.display  = "none";
    timeSavedSection.style.display = "none";
}

function showRemoveTimeSection() { 
    submitTimeSection.style.display = "none";
    removeTimeSection.style.display = "block";
    timeSavedSection.style.display = "none";
}

function showTimeSavedSection() { 
    submitTimeSection.style.display = "none";
    removeTimeSection.style.display = "none";
    timeSavedSection.style.display = "block";
    
}

// After successfully submitting or removing a time, hide or show 
// the appropriate sections of the estimated play time control panel.
function swapTimeSections() {
    if (document.getElementById("timeSaveMsg").innerHTML == "Saved") { 
        if (submitTimeSection.style.display == "block") { 
            //The submit time section is visible, which means we just successfully saved a new time.        
            showTimeSavedSection();
        } else if (removeTimeSection.style.display == "block") {
            //The remove time section is visible, which means we just successfully removed our time.  
            showSubmitTimeSection();
        }
    } // Don't swap the time controls if there was an error saving.
}
// Attach the swapTimeSections function to the timeVoteControls div.
document.getElementById("timeVoteControls").addEventListener("load", swapTimeSections);


// When the page first loads, show the relevant time controls based on
// whether the user has a time already stored for this game. To find out, we
// check whether there's a message displaying the user's time.
function prepareTimeControls() {
    var your_time_message = document.getElementById("yourTime").innerHTML;
    if (your_time_message == "") {
        showSubmitTimeSection();
    } else if (your_time_message != "") {
        showRemoveTimeSection();
    }
}
prepareTimeControls();


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