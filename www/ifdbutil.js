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

async function jsonSend(url, statusSpanID, cbFunc, content, silentMode)
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
    try {
        const response = await fetch(url, options);
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
    if (cbFunc) {
        cbFunc(jsonResponse);
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

async function setTemporaryViewTransitionNames(entries, vtPromise) {
    for (const [$el, name] of entries) {
        if ($el) $el.style.viewTransitionName = name;
    }

    await vtPromise;

    for (const [$el, name] of entries) {
        if ($el) $el.style.viewTransitionName = '';
    }
}

window.addEventListener('pageswap', async (e) => {
    if (e.viewTransition) {
        const targetUrl = new URL(e.activation.entry.url);

        // Navigating to a game details page
        if (targetUrl.pathname === '/viewgame') {
            const id = targetUrl.searchParams.get('id');

            // Set view-transition-name values on the clicked row
            // Clean up after the page got replaced
            setTemporaryViewTransitionNames([
                [document.querySelector(`#coverart-${id}`), 'coverart'],
            ], e.viewTransition.finished);
        }
    }
});

window.addEventListener('pagereveal', async (e) => {
    if (e.viewTransition && window.navigation) {
        const fromURL = new URL(navigation.activation.from.url);
        const currentURL = new URL(navigation.activation.entry.url);

        // Navigating from a profile page back to the homepage
        if (fromURL.pathname === '/viewgame') {
            const id = fromURL.searchParams.get('id');

            // Set view-transition-name values on the elements in the list
            // Clean up after the snapshots have been taken
            setTemporaryViewTransitionNames([
                [document.querySelector(`#coverart-${id}`), 'coverart'],
            ], e.viewTransition.ready);
        }
    }
});