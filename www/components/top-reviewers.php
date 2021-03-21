<?php
   // ------------------------ top reviewers insert --------------------
    ?>


         <table class=rightbar cellpadding=0 cellspacing=0>
            <tr class="boxhead">
               <td><h3>Reviewer Trophy Room</h3></td>
            </tr>
            <tr>
               <td>
                  <span class=details><i>IFDB's top reviewers, as determined
                     by <?php echo helpWinLink("help-ff", "Frequent Fiction")
                     ?> scores:</i></span><p>
                  <?php

$rlst = getTopReviewers($db, 4);
$n = 1;
foreach ($rlst as $r) {
    list($ruid, $runame, $rscore) = $r;
    $runame = htmlspecialcharx($runame);
    echo "<span style='padding: 0 1.5ex 0 1ex;' class=details><i>#$n</i></span>"
        . "<a class=silent href=\"showuser?id=$ruid\">$runame</a>"
        . "<br>";
    $n++;
}

                  ?>

                  <p><span class=details>
                     <a href="search?sortby=ffrank&browse=1&member=1">
                     See all</a> |
                  <?php echo helpWinLink(
                      "help-top-rev", "Who qualifies?") ?>
                  </span>
    
               </td>
            </tr>
         </table>

<?php
// -------------------- end reviewers insert ------------------------------
?>