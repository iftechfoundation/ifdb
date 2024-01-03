<?php

// Game Search Popup.  This implements a popup box that can be integrated
// into a form, to search for a game by title.
//
// To use the search popup:
//
//   1. Include this file in your php script.
//
//   2. In the pageHeader() extra headers, include
//      scriptSrc('/xmlreq.js')
//
//   2. Somewhere in the HTML body, call gameSearchPopupSupportFuncs() to
//      generate the javascript support functions.  This generates an
//      inline <script>, so it can be put almost anywhere in the body.
//
//   3. Somewhere in the HTML body, call gameSearchPopupDiv() to generate
//      the popup's HTML.  This generates a position:absolute DIV, so
//      it can go almost anywhere in the HTML body.
//
//   4. Create an <a href="needjs"> button with an onclick script
//      that calls openGameSearchPopup('eleID', doneFunc, defaultTitle,
//      openerBtn).
//
//      'eleID' is the ID of an object to align the popup under; this
//      is usually a text input or <span> where the game name that we're
//      searching for will be displayed after we make our selection.
//      'doneFunc'(id, title, author) is a callback we invoke when the
//      user makes a selection.  'defaultTitle' is the initial title
//      to put in the search box; this is usually blank, but if there's
//      a reason to provide a default, it can be provided here.  'openerBtn'
//      is the opener button, as an ID string or an element object; if this
//      isn't null, we'll restore focus to this object when the popup is
//      closed.
//

// ------------------------------------------------------------------------
//
// Generate the javascript support functions.
//
function gameSearchPopupSupportFuncs()
{
?>
<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--

var gameSearchPopupDoneFunc;
var gameSearchPopupOpener;
function openGameSearchPopup(ele, doneFunc, defaultTitle, openerBtn)
{
    gameSearchPopupDoneFunc = doneFunc;
    gameSearchPopupOpener = (typeof(openerBtn) == "string"
                             ? document.getElementById(openerBtn) : openerBtn);
    var rc = getObjectRect(document.getElementById(ele));

    document.getElementById("gameSearchPopupStep2").style.display = "none";
    var dlg = document.getElementById("gameSearchPopupDiv");
    moveObject(dlg, rc.x, rc.y + rc.height);
    dlg.style.display = "block";

    var fld = document.getElementById("gameSearchPopupSearchBox");
    fld.focus();
    fld.value = defaultTitle;
}
function gameSearchPopupKey(event)
{
    var ch = (window.event || event.keyCode ? event.keyCode : event.which);
    if (ch == 13 || ch == 10) {
        gameSearchPopupGo();
        return false;
    }
    if (ch == 27) {
        gameSearchPopupClose();
        return false;
    }
    return true;
}
function gameSearchPopupClose()
{
    document.getElementById("gameSearchPopupDiv").style.display = "none";
    if (gameSearchPopupOpener)
        setTimeout(function() { gameSearchPopupOpener.focus(); }, 1);
}
function gameSearchPopupGo()
{
    document.getElementById("gameSearchPopupStep2").style.display = "none";
    var txt = document.getElementById("gameSearchPopupSearchBox").value;

    if (txt.replace(/ /g, "") == "")
        return;

    xmlSend("search?xml&searchfor=" + encodeURI8859(txt),
            null, gameSearchPopupDone, null);
}
function gameSearchPopupDone(d)
{
    if (d)
    {
        var lst = d.getElementsByTagName('game');
        var s = "";
        for (var i = 0 ; i < lst.length ; i++)
        {
            var id = lst[i].getElementsByTagName('tuid')[0].firstChild.data;
            var title = lst[i].getElementsByTagName('title')[0].firstChild.data;
            var author = lst[i].getElementsByTagName('author')[0].firstChild.data;

            s += "<a href=\"needjs\" id='gameSearchPopupLink"+i+"'><i>"
                 + encodeHTML(title) + "</i></a>"
                 + ", by " + encodeHTML(author)
                 + " - <a href=\"viewgame?id=" + id + "\" target=\"_blank\">"
                 + "view game</a><br>";
        }
        if (s == "")
            s = "<b><i>(No matching games found.)</i></b>";

        document.getElementById("gameSearchPopupResults").innerHTML = s;
        for (var i = 0; i < lst.length; i++) {
            (function(i) {
                window['gameSearchPopupLink'+i].addEventListener('click', function(event) {
                    event.preventDefault();
                    var id = lst[i].getElementsByTagName('tuid')[0].firstChild.data;
                    var title = lst[i].getElementsByTagName('title')[0].firstChild.data;
                    var author = lst[i].getElementsByTagName('author')[0].firstChild.data;
                    gameSearchPopupSetID(id, title, author);
                })
            })(i);
        }
        document.getElementById("gameSearchPopupStep2").style.display = "block";
        setTimeout(function() {
            var ele = document.getElementById("gameSearchPopupDiv");
            var rc = getObjectRect(ele);
            var docrc = getObjectRect(document);
            if (rc.y + rc.height > docrc.height)
                moveObject(ele, null, docrc.height - rc.height);
        }, 10);
    }
}
function gameSearchPopupSetID(id, title, author)
{
    gameSearchPopupDoneFunc(id, title, author);
}

//-->
</script>
<?php
}

// ------------------------------------------------------------------------
//
// Generate the popup division for the search box
//
function gameSearchPopupDiv()
{
?>

    <style nonce="<?php global $nonce; echo $nonce; ?>">
        #gameSearchPopupDiv {
            display: none;
            position: absolute;
            width: 80ex;
            z-index: 10000;
        }

        #gameSearchPopupDiv .edit-popup-title {
            position: relative;
        }

        #gameSearchPopupDiv .edit-popup-title div {
            text-align: center;
        }
        #gameSearchPopupDiv .edit-popup-title div span {
            position: absolute;
            top: 2px;
            right: 2px;
            text-align: right;
        }
        #gameSearchPopupResults {
            margin-top:1ex;
            max-height: 20em;
            overflow: auto;
        }
    </style>
    <div id="gameSearchPopupDiv" class="edit-popup-frame">
       <div class="edit-popup-title">
          <div>
             <b>Select a Game</b>
             <span>
                <a href="needjs">
                   <script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
                      document.currentScript.parentElement.addEventListener('click', function (event) {
                        event.preventDefault();
                        gameSearchPopupClose();
                      })
                   </script>
                   Close<img src="img/blank.gif" class="popup-close-button"></a>
             </span>
          </div>
       </div>
       <div class="edit-popup-win">
          <p><b>Step 1:</b> Search for a game by title, <code>ifid:<?php
              echo helpWinLink("help-ifid", "IFID") ?></code>, or <code>tuid:<?php
              echo helpWinLink("help-tuid", "TUID") ?></code>:
          <br>
          <input id="gameSearchPopupSearchBox" type=text size=50>
          <input type=submit name="gameSearchPopupGoBtn"
              id="gameSearchPopupGoBtn" value="Search">
          <script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
            gameSearchPopupSearchBox.addEventListener('keypress', function (event) {
                var result = gameSearchPopupKey(event);
                if (result === false) event.preventDefault();
            });
            gameSearchPopupSearchBox.addEventListener('keydown', function (event) {
                var result = gameSearchPopupKey(event);
                if (result === false) event.preventDefault();
            });
            gameSearchPopupGoBtn.addEventListener('click', function (event) {
                event.preventDefault();
                gameSearchPopupGo();
            });
          </script>
          <div id="gameSearchPopupStep2" class="displayNone">
             <p><b>Step 2:</b> Click on a result below to select it:

             <div id="gameSearchPopupResults">
             </div>
          </div>
       </div>
    </div>

<?php
}

?>
