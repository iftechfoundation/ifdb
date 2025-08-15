<?php

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
        $str .= "<input type='radio' name='rating' value='$i' id='{$id}__rating$i' autocomplete='off' $checked>"
            . "<label for='{$id}__rating$i'><span>"
            . ($i === 1 ? "1 star": "$i stars")
            ."</span></label>";
    }

    $str .= addEventListener("change", "$clickFunc(event.target.value)");

    $str .= "</div></fieldset><button class='fancy-button remove-rating' type=button>Remove Rating"
        . addEventListener("click", "setStarCtlValue('$id', 0); $clickFunc(0);")
        ."</button>";

    

    return $str;
}

?>
