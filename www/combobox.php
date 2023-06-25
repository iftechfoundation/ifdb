<?php
// ------------------------------------------------------------------------
//
// Combo box handling
//
// HTML doesn't have combo boxes, so simulate a combo with a combination of
// a regular input field, a button, and a bunch of javascript
//
// The includer must define their own function checkClosePopup(event, byMouse).
// This is called at various times to check if *other* popups on the form
// should be closed.  If the only javascript popups that the form uses
// are the combo box popups, checkClosePopup() can simply call
// checkCloseCombo().



//
// Write a combo box into the document.
//
// $onSet is a javascript function to invoke each time the field value is
// changed by making a selection from the drop list.
//
function makeComboBox($name, $textlen, $curval, $vals, $onSet = "null")
{
    // write the input field
    $txt = "<input type=\"text\" name=\"$name\" id=\"$name\" "
           . "size=$textlen value=\"" . htmlspecialcharx($curval)
           . "\" onkeydown=\"javascript:return comboFieldKey("
           . "event,'$name','${name}CBSel','D');\" "
           . "onkeypress=\"javascript:return comboFieldKey("
           . "event,'$name','{$name}CBSel','P');\">";

    // add the drop arrow
    $txt .= "<a href=\"needjs\" onkeypress=\"javascript:return "
           . "comboArrowKey(event,'$name',true,'${name}CBSel');\" "
           . "onkeydown=\"javascript:return comboArrowKey("
           . "event,'$name',true,'${name}CBSel');\">"
           . "<img alt=\"Open List\" border=0 "
           . "src=\"/img/blank.gif\" class=\"combobox-arrow\" "
           . "onclick=\"javascript:postShowComboMenu("
           . "'$name',true,'{$name}CBSel');return false;\"></a>";

    // set up the hidden division for the list
    $txt .= "<div id=\"{$name}CBDiv\" "
            . "style=\"position:absolute;display:none;top:0px; "
            . "left:0px;z-index:20000\" "
            . "onmouseover=\"javascript:overComboMenu=true;return true;\" "
            . "onmouseout=\"javascript:overComboMenu=false;return true;\">";

    // add the hidden list
    $txt .= "<select size=10 id=\"{$name}CBSel\" "
            . "onclick=\"javascript:setComboText('$name',this.value,$onSet);\" "
            . "onkeypress=\"javascript:return comboKeyPress("
            . "event,'$name',this,$onSet);\" "
            . "onkeydown=\"javascript:return comboKeyPress("
            . "event,'$name',this,$onSet);\" "
            . "onblur=\"javascript:checkClosePopup(null, false);\">";

    // add the options
    for ($j = 0 ; $j < count($vals) ; $j++) {
        $txt .= "<option value=\"{$vals[$j]}\"";
        if ($curval == $vals[$j])
            $txt .= " selected";
        $txt .= ">$vals[$j]</option>";
    }

    // end the list and the enclosing division
    $txt .= "</select></div>";

    // return the text
    return $txt;
}

// -------------------------------------------------------------------------
//
// Write the combo box javascript support code to the document
//

function comboSupportFuncs()
{
?>

<script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
<!--
var activeCombo = false;
var overComboMenu = false;
var justDismissed = false;

function comboTarget(e)
{
    var targ = null;

    if (!e)
        e = window.event;
    if (!e)
        return null;

    if (e.target)
        targ = e.target;
    else if (e.srcElement)
        targ = e.srcElement;

    if (targ && targ.nodeType == 3)
        targ = targ.parentNode;

    return targ;
}

function checkCloseCombo(e, byMouse)
{
    var targ = comboTarget(e);

    if (activeCombo && (!byMouse || !overComboMenu))
    {
        overComboMenu = false;
        document.getElementById(activeCombo).style.display = "none";
        justDismissed = activeCombo;
        activeCombo = false;
        return false;
    }
    justDismissed = false;
    return true;
}

function comboArrowKey(event, idEdit, setWidth, idFocus)
{
    var ch = (window.event || event.keyCode ? event.keyCode : event.which);
    if (ch == 32 || ch == 10 || ch == 13)
    {
        postShowComboMenu(idEdit, setWidth, idFocus);
        return false;
    }
    return true;
}
var comboArrowKeyQueue = [];
function postShowComboMenu(idEdit, setWidth, idFocus)
{
    if (comboArrowKeyQueue.length == 0)
    {
        comboArrowKeyQueue.push(idEdit);
        setTimeout(function() {
            comboArrowKeyQueue.pop();
            showComboMenu(idEdit, setWidth, idFocus);
        }, 1);
    }
}
function showComboMenu(idEdit, setWidth, idFocus)
{
    idMenu = idEdit + 'CBDiv';

    if (activeCombo == idMenu)
        return checkClosePopup(null, false);
    else if (activeCombo)
        checkClosePopup(null, false);

    if (justDismissed == idMenu)
        return false;

    idSel = idEdit + 'CBSel'
    oMenu = document.getElementById(idMenu);
    oEdit = document.getElementById(idEdit);
    var fldVal = oEdit.value;
    oSel = document.getElementById(idSel);
    nTop = oEdit.offsetTop + oEdit.offsetHeight;
    nLeft = oEdit.offsetLeft;
    nWidth = oEdit.offsetWidth;
    while (oEdit.offsetParent != document.body
           && oEdit.offsetParent.style.position != "absolute")
    {
        oEdit = oEdit.offsetParent;
        nTop += oEdit.offsetTop;
        nLeft += oEdit.offsetLeft;
    }
    oMenu.style.left = nLeft + "px";
    oMenu.style.top = nTop + "px";
    if (setWidth)
    {
        oMenu.style.width = (nWidth + 18) + "px";
        oSel.style.width = (nWidth + 18) + "px";
    }
    oMenu.style.display = "";
    activeCombo = idMenu;
    if (idFocus)
        document.getElementById(idFocus).focus();

    if (fldVal)
    {
        var opts = oSel.options;
        for (var i = 0 ; i < opts.length ; ++i)
        {
            if (opts[i].text == fldVal)
            {
                opts.selectedIndex = i;
                break;
            }
        }
    }

    return false;
}

function setComboText(idEdit, text, onSet)
{
    document.getElementById(idEdit).value = text;
    overComboMenu = false;
    checkClosePopup(null, false);
    document.getElementById(idEdit).focus();
    document.getElementById(idEdit).select();
    if (onSet)
        onSet(text);
}

function comboFieldKey(event, idEdit, idSel, mode)
{
    // If this is a keydown event, the key code is reliably the special key
    var event = event || window.event;
    if (mode == 'D')
    {
        // check for an up arrow (38) or down arrow (40)
        var key = event.keyCode;
        if (key == 38 || key == 40)
        {
            // if the field's value matches one of the drop list selections,
            // go to the next/previous selection in the drop list
            var fld = document.getElementById(idEdit);
            var sel = document.getElementById(idSel);
            var fldVal = fld.value;
            var opts = sel.options;
            for (var i = 0 ; i < opts.length ; ++i)
            {
                if (opts[i].text == fldVal)
                {
                    // set the next/previous value
                    if (key == 38 && i > 0)
                        fld.value = opts[i-1].text;
                    else if (key == 40 && i + 1 < opts.length)
                        fld.value = opts[i+1].text;
                    setSelRange(fld, { start: 0, end: fld.value.length });

                    // handled - skip the default handling
                    return false;
                }
            }
        }
    }

    // not handled; let the browser use the default handling
    return true;
}

function comboKeyPress(event, idEdit, oSel, onSet)
{
    var ch = (window.event || event.keyCode ? event.keyCode : event.which);
    if (ch == 13 || ch == 10 || ch == 32)
    {
        setComboText(idEdit,oSel.value,onSet);
        return false;
    }
    else if (ch == 27)
    {
        checkClosePopup(null, false);
        document.getElementById(idEdit).focus();
        return false;
    }
    else
        return true;
}
//-->
</script>
<?php

}

?>
