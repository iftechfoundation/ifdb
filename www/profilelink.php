<?php
//
// Profile Link Popups.  This provides the javascript support for
// the little controls that we use to insert a member profile
// link into a text field.
//
// To use profile links, do the following:
//
//  1. Include this file
//
//  2. In the pageHeader call, include "<script src='xmlreq.js'></script>"
//     in the extra header text.
// 
//  3. Somewhere in the file, call profileLinkSupportFuncs() to generate
//     the javascript support functions.  This generates a <script> section,
//     so it can go almost anywhere in the main body of the html.
//
//  4. Somewhere in the file, call profileLinkDiv() to generate the
//     HTML for the popup itself.  This is a position:absolute DIV, so
//     it can go almost anywhere in the main body of the html.
//
//  5. For each field where you want to be able to insert a profile
//     link, use
//       onclick="javascript:aplOpen('fieldID', 'fieldName');return false;"
//     as the link's script.  This will pop up the link box just under
//     the field with the given ID.
// 


// ------------------------------------------------------------------------
//
// Generate the javascript support functions for profile link popups
//
function profileLinkSupportFuncs()
{
?>
<script type="text/javascript">
<!--

var aplFieldEle = null, aplFieldRange = null, aplFieldFocus = null;
function aplOpen(fieldID, fieldName)
{
    aplFieldEle = document.getElementById(fieldID);
    aplFieldRange = null;
    aplFieldFocus = aplFieldEle.onfocus;
    document.getElementById("aplFieldName").innerHTML = fieldName;
    var aplDiv = document.getElementById("aplDiv");
    aplDiv.style.display = "";
    var au = aplFieldEle.value;

    // get the selection range, normalized so end>start
    var r = getSelRange(aplFieldEle);
    if (r && r.end < r.start)
        r = { start: r.end, end: r.start };

    // if there's no selection, put the caret at the end of the text
    if (r == null)
        r = { start: au.length, end: au.length };

    // set up the initial search text
    if (r.end > r.start)
    {
        // we have selected text - use the exact selected text
        au = au.substring(r.start, r.end);
        aplFieldRange = { start: r.end, end: r.end };
    }
    else
    {
        // No selection - if there's an author before the caret, select
        // that author; otherwise select the next author.  Start by
        // finding the first author that starts after the caret.
        var authors = aplParseAuthors(au);
        var i;
        for (i = 0 ; i < authors.length && authors[i].index < r.start ; ++i) ;

        // if there's an earlier author, use that; otherwise use the
        // next author
        if (i > 0)
            --i;

        // use the selected author
        if (i < authors.length)
        {
            // use this author as the initial search text
            au = authors[i].author;

            // move just after the author
            var endIdx = authors[i].index + au.length;
            aplFieldRange = { start: endIdx, end: endIdx };

            // if there's an existing profile tag, select that range
            var txt = aplFieldEle.value.substring(endIdx);
            var p = /\s*\{[a-z0-9]+\}/i.exec(txt);
            if (p && p.index == 0)
                aplFieldRange = { start: endIdx, end: endIdx + p[0].length };
        }
    }

    // load the search box with the author we pulled out
    var searchBox = document.getElementById("aplSearchBox");
    searchBox.value = au;

    // move the popup to just under the field we're updating
    var rc = getObjectRect(aplFieldEle);
    moveObject(aplDiv, rc.x, rc.y + rc.height);

    // if the author field is empty, 
    if (au == "")
    {
        alert("Note: Remember that you need to enter the name to display "
              + "as well as the profile link.  Use the format \"Arthur "
              + "Dent {a10x00139ke9041j}\".");
    }

    // focus on the search box and select the whole thing
    searchBox.focus();
    setSelRange(searchBox, { start: 0, end: au.length });

    // if they focus back in the field while the popup is up, don't
    // set our default range
    setTimeout(function() {
        aplFieldEle.onfocus = function() { aplFieldRange = null; };
    }, 0);
}
function aplParseAuthors(txt)
{
    var authors = [];
    var pat = /\s*(,(?!\s*(jr|sr|phd|ph\.d))|\s*\{[a-z0-9]+\}\s*|\s*and\W)/i;
    var idx = 0;
    while (txt != "")
    {
        // search for the next separator
        var p = pat.exec(txt);
        if (p)
        {
            // pull out the part up to the separator as the next author
            var auIdx = idx;
            var au = txt.substring(0, p.index);

            // pull out the separator
            var sep = p[0];

            // trim to the remainder after the separator
            txt = txt.substring(p.index + sep.length);
            idx += p.index + sep.length;

            // combine successive separators
            while ((p = pat.exec(txt)) != null
                   && p.index == 0 && p[0].length > 0)
            {
                sep += p[0];
                txt = txt.substring(p.index + p[0].length);
                idx += p.index + p[0].length;
            }

            // add this author to the list
            authors.push({ author: au, separator: sep, index: auIdx });
        }
        else
        {
            authors.push({ author: txt, separator: "", index: idx });
            txt = "";
        }
    }
    return authors;
}
function aplClose()
{
    aplFieldEle.onfocus = aplFieldFocus;
    document.getElementById("aplStep2").style.display = "none";
    document.getElementById("aplDiv").style.display = "none";
}
function aplPopupKey(event)
{
    var ch = (window.event || event.keyCode ? event.keyCode : event.which);
    if (ch == 13 || ch == 10) {
        aplSearch();
        return false;
    }
    if (ch == 27) {
        aplClose();
        return false;
    }
    return true;
}
function aplSearch()
{
    document.getElementById("aplStep2").style.display = "none";
    xmlSend("search?xml&member&searchfor="
            + encodeURI8859(document.getElementById("aplSearchBox").value),
            null, aplSearchDone, null);
}
function aplInsertID(id)
{
    if (aplFieldRange)
    {
        var r = aplFieldRange;
        aplFieldEle.focus();
        setSelRange(aplFieldEle, r);
    }
    replaceSelRange(aplFieldEle, " {" + id + "}", true);
    aplClose();
}
function aplSearchDone(d)
{
    if (d)
    {
        var lst = d.getElementsByTagName('member');
        var s = "";
        for (var i = 0 ; i < lst.length ; i++)
        {
            var nm = lst[i].getElementsByTagName('name')[0].firstChild.data;
            var id = lst[i].getElementsByTagName('tuid')[0].firstChild.data;
            s += "<a href=\"needjs\" onclick=\"aplInsertID('" + id + "');"
                 + "return false;\">" + encodeHTML(nm) + "</a>"
                 + " - <a href=\"showuser?id=" + id + "\" target=\"_blank\">"
                 + "view profile</a><br>";
        }
        if (s == "")
            s = "<b><i>(No member profiles found.)</i></b>";
        document.getElementById("aplSearchResults").innerHTML = s;
        document.getElementById("aplStep2").style.display = "";
    }
}

//-->
</script>
<?php
}

// ------------------------------------------------------------------------
//
// Generate the <DIV> for the profile link popup.  This should be inserted
// somewhere in the page body; it doesn't matter too much where, since it's
// a position:absolute division, but for flexibility we leave it up to the
// main includer to determine where to put this.
//
function profileLinkDiv()
{
?>
        
<div id="aplDiv" class="edit-popup-frame"
  style="display:none; position:absolute; z-index:10000;">
   <div class="edit-popup-title" style="position:relative;">
      <div style="text-align:center;">
         <b>Add Link to Member Profile</b>
         <span style="position:absolute;top:2px;right:2px;
               text-align:right;">
            <a href="needjs"
             onclick="javascript:aplClose();return false;">
               Close<img src="/blank.gif" class="popup-close-button"></a>
         </span>
      </div>
   </div>
   <div class="edit-popup-win">
      <p><b>Step 1:</b> Search for an IFDB profile by member name.
      <br>
      <input id="aplSearchBox" type=text size=50
         onkeypress="javascript:return aplPopupKey(event);">
      <input type=submit name="aplSearchGo" id="aplSearchGo"
         value="Search" onclick="javascript:aplSearch();return false;">
      <div id="aplStep2" style="display:none;">
         <p><b>Step 2:</b> Click in the <span id="aplFieldName">text</span>
            field above to move the caret <b>just after</b> the displayed
            name of the person you're linking to.

         <p><b>Step 3:</b> Click on a result below to insert a
         link to that member's profile.

         <div id="aplSearchResults" style="margin-top:1ex;">
         </div>
      </div>
   </div>
</div>

<?php
}


?>