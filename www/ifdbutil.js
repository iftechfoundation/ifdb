// Copyright 2009 Michael J Roberts

function helpWin(url)
{
    win = window.open(url, "IFDBHelp",
                      'width=400,height=400,left=10,top=10,scrollbars=1,resizable=1');
}
function encodeHTML(str)
{
    return str.replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/'/g, "&#39;")
        .replace(/"/g, "&#34;");
}
function encodeURI8859(str)
{
    return encodeURIComponent(str);
}
function jsQuote(str)
{
    return str.replace(/"/g, "&#34");
}
function getWindowRect()
{
    var wid = 1000000, ht = 1000000;
    var x = window.innerWidth, y = window.innerHeight;
    if (typeof(x) == "number" && x > 0)
    {
        wid = x;
        ht = y;
    }

    x = document.documentElement.clientWidth;
    y = document.documentElement.clientHeight;
    if (typeof(x) == "number" && x > 0)
    {
        // keep the smallest so far
        wid = Math.min(wid, x);
        ht = Math.min(wid, y);
    }

    x = document.body.clientWidth;
    y = document.body.clientHeight;
    if (typeof(x) == "number" && x > 0)
    {
        wid = Math.min(wid, x);
        ht = Math.min(wid, y);
    }

    return { x: 0, y: 0, width: wid, height: ht };
}
function getObjectRect(obj)
{
    if (!obj)
        return null;

    if (obj.getBoundingClientRect)
    {
        var r = obj.getBoundingClientRect();
        var de = document.documentElement;
        var dx = de.scrollLeft, dy = de.scrollTop;
        if (dx == 0 && dy == 0)
        {
            de = document.body;
            dx = de.scrollLeft;
            dy = de.scrollTop;
        }
        return { x: r.left + dx, y: r.top + dy,
                 width: r.right - r.left, height: r.bottom - r.top };
    }

    var twid = obj.offsetWidth;
    var tht = obj.offsetHeight;
    var tx = obj.offsetLeft;
    var ty = obj.offsetTop;

    for (var par = obj.offsetParent ; par != null && par != document.body ;
         par = par.offsetParent)
    {
        tx += par.offsetLeft;
        ty += par.offsetTop;
    }

    return { x: tx, y: ty, width: twid, height: tht };
}
function moveObject(obj, x, y)
{
    var parent;
    for (parent = obj.parentNode ; parent != null && parent != document ;
         parent = parent.parentNode)
    {
        var s = parent.currentStyle
                || (document.defaultView
                    && document.defaultView.getComputedStyle
                    && document.defaultView.getComputedStyle(parent, ""));
        if (s)
            s = s.position;
        if (s == "absolute" || s == "relative" || s == "fixed")
            break;
    }
    if (parent == document)
        parent = null;

    var dx = 0, dy = 0;
    if (parent)
    {
        var prc = getObjectRect(parent);
        dx = prc.x;
        dy = prc.y;
    }

    if (x != null)
        obj.style.left = (x - dx) + "px";
    if (y != null)
        obj.style.top = (y - dy) + "px";
}

// get the selection range in a given element
function getSelRange(ele)
{
    // check for browser variations
    if (document.selection)
    {
        // IE - use a TextRange object, adjusted to be element-relative
        var r, r2;
        try
        {
            if (ele.nodeName == "INPUT" && ele.type.toLowerCase() == "text"
                || ele.nodeName == "TEXTAREA")
            {
                ele.focus();
                r = document.selection.createRange();
                r2 = ele.createTextRange();
            }
            else
            {
                r = document.selection.createRange();
                r2 = r.duplicate();
                r2.moveToElementText(ele);
            }
            r2.setEndPoint('EndToEnd', r);

            var s = r2.text.length - r.text.length;
            var e = s + r.text.length;
            return { start: s, end: e };
        }
        catch (exc)
        {
        }
    }

    if (ele.selectionStart || ele.selectionStart == '0')
        return { start: ele.selectionStart, end: ele.selectionEnd };

    return null;
}

// Set the selection range in a given element
function setSelRange(ele, range)
{
    // check for browser variations
    if (ele.setSelectionRange)
    {
        // non-IE - there's a method that does exactly what we want
        ele.setSelectionRange(range.start, range.end);
    }
    else if (ele.createTextRange)
    {
        // IE - we have to do this indirectly through a TextRange object
        var r = ele.createTextRange();
        if (ele.nodeName == "INPUT" && ele.type.toLowerCase() == "text"
            || ele.nodeName == "TEXTAREA")
            ele.focus();
        r.collapse(true);
        r.moveEnd('character', range.end);
        r.moveStart('character', range.start);
        r.select();
    }
}

// Replace the selection in the given control with the given text
function replaceSelRange(ele, txt, selectNewText)
{
    // get the current selection range
    var r = getSelRange(ele);
    if (r)
    {
        // replace the selection range with the new text
        ele.value = ele.value.substr(0, r.start)
                    + txt
                    + ele.value.substr(r.end);

        // select the new text if desired, or move the selection to the
        // end of the new text if not
        setSelRange(ele,
                    { start: selectNewText ? r.start : r.start + txt.length,
                      end: r.start + txt.length });
    }
}

// if the user has opted into a dark-mode stylesheet, activate all of the dark mode rules
function forceDarkMode(force) {
    if (!force) return;
    var dark = force === 1;
    function parseSheet(sheet) {
        for (var i = sheet.cssRules.length - 1; i >=0 ; i--) {
            var rule = sheet.cssRules[i];
            if (rule.type === CSSRule.IMPORT_RULE) {
                parseSheet(rule.styleSheet);
                continue;
            }
            if (rule.media?.mediaText?.includes("prefers-color-scheme: dark")) {
                if (dark) {
                    rule.media.appendMedium("(prefers-color-scheme: light)");
                } else {
                    sheet.deleteRule(i);
                }
            }
        }
    }
    for (const sheet of document.styleSheets) {
        parseSheet(sheet);
    }
}

async function jsonSend(url, statusSpanID, cbFunc, content, silentMode, cbFuncArgs)
{
    if (statusSpanID)
        document.getElementById(statusSpanID).innerHTML = "";

    const options = {
        method: 'POST',
    };
    if (content != null) {
        options.body = JSON.stringify(content);
    }

    let jsonResponse = null;
    let msgspan = null;
    let response;
    try {
        response = await fetch(url, options);
        jsonResponse = await response.json();
        msgspan = (statusSpanID
                ? document.getElementById(statusSpanID)
                : null);

        if (!response || !response.ok)
            throw new Error();

        if (msgspan) {
            const lbl = jsonResponse.label;
            if (lbl)
                msgspan.innerHTML = lbl;
        }

        const errmsg = jsonResponse.error;
        if (errmsg && !silentMode)
            alert(errmsg);
    } catch (e) {
        if (msgspan)
            msgspan.innerHTML = "Not Saved";
        if (!silentMode)
            alert("An error occurred sending the update to the server. "
                   + "(" + response.status + ") "
                   + "Please try again later.");
    }
    if (cbFunc && response?.ok) {
        cbFunc(jsonResponse, cbFuncArgs);
    }
}

async function check_ifid_in_ifwiki(ifid) {
    ifid = escape(ifid).toUpperCase();
    try {
        const response = await fetch(`https://www.ifwiki.org/api.php?action=query&format=json&prop=info&origin=*&titles=IFID:${ifid}`);
        if (response.ok)
            return (await response.json()).query.pages["-1"] === undefined;
    } catch (e) {}
    return false;
}

function setStarCtlValue(id, value) {
    if (value) {
        document.getElementById(`${id}__rating${value}`).checked = true;
    } else {
        const checked = [...document.querySelectorAll(`#${id} input[type=radio]`)].filter(i => i.checked)[0];
        if (checked) checked.checked = false;
    }
}

// Used by the have-you-played widget

function ckboxGetObj(id)
{
    return document.getElementById('ckBox' + id);
}
function ckboxClick(id, onUpdateFunc)
{
    const elem = ckboxGetObj(id);
    if (onUpdateFunc)
        onUpdateFunc(id, elem.checked);
}
function updateRating(game_id, rating)
{
    const submitRating = document.getElementById(`submitRating_${game_id}`);
    if (rating) {
        if (submitRating.style.display === 'block') return;
    } else {
        submitRating.style.display = 'none';
    }
    jsonSend(`setrating?game=${game_id}&rating=${rating}`,
             `rating_${game_id}_SaveMsg`);
}
function updatePlayed(id, stat)
{
    const elem = ckboxGetObj(id);
    const game_id = elem.closest('[data-gameid]').dataset.gameid;
    jsonSend(`setplayed?game=${game_id}&played=`
             + (elem.checked ? "1" : "0"),
             `${id}_SaveMsg`, (d) => {if (typeof updatePlaylistCount == "function") updatePlaylistCount(d.newCount);});
}
function updateWishlist(id, stat)
{
    const elem = ckboxGetObj(id);
    const game_id = elem.closest('[data-gameid]').dataset.gameid;
    jsonSend(`setwishlist?game=${game_id}&add=`
             + (elem.checked ? "1" : "0"),
             `${id}_SaveMsg`, (d) => {if (typeof updateWishlistCount == "function") updateWishlistCount(d.newCount)});
}
function updateUnwishlist(id, stat)
{
    const elem = ckboxGetObj(id);
    const game_id = elem.closest('[data-gameid]').dataset.gameid;
    jsonSend(`setunwishlist?game=${game_id}&add=`
             + (elem.checked ? "1" : "0"),
             `${id}_SaveMsg`);
}

// When the user clicks the "Submit Time" button, get the input 
// from the hours and minutes fields, validate it, convert it to 
// minutes only, and save it to the database (with the note, if present).
function submitTime() {
    const gameElem = event.target.closest('[data-gameid]');
    const game_id = gameElem.dataset.gameid;

    let new_hours = gameElem.querySelector('.timeSection_hours').value;
    let new_minutes = gameElem.querySelector('.timeSection_minutes').value;
    const time_note = gameElem.querySelector('.timeNote').value.trim();
    if (new_hours == "") {    // If the hours field was blank, interpret it as 0
        new_hours = 0;
    }
    if (new_minutes == "") {  // If the minutes field was blank, interpret it as 0
        new_minutes = 0;
    }

    const saveMsgId = `time_SaveMsg_${game_id}`;
    const saveMsgElem = document.getElementById(saveMsgId);

    const hours_are_digits = isOnlyDigits(new_hours); 
    const minutes_are_digits = isOnlyDigits(new_minutes);
    if (hours_are_digits == false || minutes_are_digits == false) {
        saveMsgElem.innerHTML="Please enter valid numbers.";
        return;
    }
    if (new_hours > 200) {     // Negative numbers will hopefully be eliminated by "Digits only"
        saveMsgElem.innerHTML="For hours, please enter a number from 0 to 200.";
        return;
    }
    if (new_minutes > 59) {
        saveMsgElem.innerHTML="For minutes, please enter a number from 0 to 59.";
        return;
    }
    const new_time = convertToMinutes(new_hours, new_minutes);
    if (new_time < 1) {
        saveMsgElem.innerHTML="The total time must be at least one minute.";
        return;
    }
    // Time is valid, as far as we can tell.
    // If the game isn't already on the user's list of played games, add it.
    const played_it_checkbox = document.getElementById(`ckBoxckPlayed_${game_id}`);
    if (!played_it_checkbox.checked) {
        played_it_checkbox.checked = true;
        updatePlayed(`ckPlayed_${game_id}`);
    }
    // Submit the time.
    jsonSend(`settime?game=${game_id}&newtime=${new_time}&note=${encodeURIComponent(time_note)}`, saveMsgId, swapTimeSections, null, false, {'gameid': game_id});
}

// Remove the user's time for this game from the database.
function removeTime() {
    const game_id = event.target.closest('[data-gameid]').dataset.gameid;
    jsonSend(`settime?game=${game_id}&newtime=0`, `time_SaveMsg_${game_id}`, swapTimeSections, null, false, {'gameid': game_id});
}

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

function showSubmitTimeSection(game_id) { 
    document.querySelector(`[data-gameid='${game_id}'] .submitTimeSection`).style.display = "block";
    document.querySelector(`[data-gameid='${game_id}'] .removeTimeSection`).style.display  = "none";
    document.querySelector(`[data-gameid='${game_id}'] .timeSavedSection`).style.display = "none";
}

function showRemoveTimeSection(game_id) { 
    document.querySelector(`[data-gameid='${game_id}'] .submitTimeSection`).style.display = "none";
    document.querySelector(`[data-gameid='${game_id}'] .removeTimeSection`).style.display = "block";
    document.querySelector(`[data-gameid='${game_id}'] .timeSavedSection`).style.display = "none";
}

function showTimeSavedSection(game_id) {
    document.querySelector(`[data-gameid='${game_id}'] .submitTimeSection`).style.display = "none";
    document.querySelector(`[data-gameid='${game_id}'] .removeTimeSection`).style.display = "none";
    document.querySelector(`[data-gameid='${game_id}'] .timeSavedSection`).style.display = "block";
}

// After successfully submitting or removing a time, hide or show 
// the appropriate sections of the estimated play time control panel.
function swapTimeSectionsEventListener(event) {
    const game_id = event.target.closest('[data-gameid]').dataset.gameid;
    swapTimeSections(null, {'gameid': game_id});
}

function swapTimeSections(_response, args) {
    const game_id = args.gameid;

    if (document.getElementById(`time_SaveMsg_${game_id}`).innerHTML == "Saved") { 
        if (document.querySelector(`[data-gameid='${game_id}'] .submitTimeSection`).style.display == "block") { 
            //The submit time section is visible, which means we just successfully saved a new time.        
            showTimeSavedSection(game_id);
        } else if (document.querySelector(`[data-gameid='${game_id}'] .removeTimeSection`).style.display == "block") {
            //The remove time section is visible, which means we just successfully removed our time.  
            showSubmitTimeSection(game_id);
        }
    } // Don't swap the time controls if there was an error saving.
}

function prepareHaveYouPlayedBox(game_id) {
    // Set up star rating control so it submits as soon as a star is clicked
    const arrowKeys = new Set(['ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown']);
    const ratingStars = document.getElementById(`ratingStars_${game_id}`);
    const submitRating = document.getElementById(`submitRating_${game_id}`);
    ratingStars.addEventListener('keydown', e => {
        if (arrowKeys.has(e.key)) submitRating.style.display = 'block';
    });
    submitRating.addEventListener('click', () => {
        submitRating.style.display = 'none';
        updateRating(game_id, ratingStars.querySelector('input:checked').value);
    });

    // Attach the submitTime function to the "Submit Time" button.
    document.querySelector(`[data-gameid='${game_id}'] .submitTimeButton`).addEventListener("click", submitTime);


    // Attach the removeTime function to the Remove Time button.
    document.querySelector(`[data-gameid='${game_id}'] .removeTimeButton`).addEventListener("click", removeTime);


    // Attach the swapTimeSections function to the timeVoteControls div.
    document.querySelector(`[data-gameid='${game_id}'] .timeVoteControls`).addEventListener("load", swapTimeSectionsEventListener);


    // Show the relevant time controls based on whether the user has a time already stored for this game.
    // To find out, we check whether there's a message displaying the user's time.
    const your_time_message = document.querySelector(`[data-gameid='${game_id}'] .yourTime`).innerHTML;
    if (your_time_message == "") {
        showSubmitTimeSection(game_id);
    } else {
        showRemoveTimeSection(game_id);
    }
}
