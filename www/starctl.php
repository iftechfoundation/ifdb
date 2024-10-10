<?php

include_once "session-start.php";
include_once "dbconnect.php";
include_once "login-persist.php";

$db = dbConnect();
$curuser = checkPersistentLogin();

function initStarControls()
{
    // Standard version - use the animated javascript star control,
    // with automatic mouse rollover highlighting.
    ?>
    <script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
        function setStarCtlValue(id, value) {
            if (value) {
                document.getElementById(`${id}__rating${value}`).checked = true;
            } else {
                var checked = [...document.querySelectorAll(`#${id} input[type=radio]`)].filter(i => i.checked)[0];
                if (checked) checked.checked = false;
            }
        }
    </script>
    <?php
}

function showStarCtl($id, $init, $clickFunc)
{
    
    if (!$init)
        $init = 0;

    $str = "<fieldset id='$id' class='star-rating'><div>\n";
    
    for ($i = 1; $i <= 5; $i++) {
        $checked = "";
        if ($i == $init) {
            $checked = "checked";
        }
        $str .= "<input type='radio' name='rating' value='$i' id='{$id}__rating$i' $checked>"
            . "<label for='{$id}__rating$i'><span>$i</span></label>";
    }

    $str .= addEventListener("change", "$clickFunc(event.target.value)");

    $str .= "</div></fieldset>";

    

    return $str;
}

?>
