<div id="top-reviewers-widget">
   <span class=details><i>IFDB's top reviewers, as determined by <?php echo helpWinLink("help-ff", "Frequent Fiction")?> scores:</i></span><p>

   <ol>
   <?php
   global $nonce;
   echo "<style nonce='$nonce'>\n"
      . ".top-reviewers__reviewer { padding: 0 1.5ex 0 1ex; }\n"
      . "</style>\n";

   $rlst = getTopReviewers($db, 4);
   $n = 1;
   foreach ($rlst as $r) {
      list($ruid, $runame, $rscore) = $r;
      $runame = htmlspecialcharx($runame);
      echo "<li><span class='details top-reviewers__reviewer'><i>#$n</i></span>"
         . "<a class=silent href=\"showuser?id=$ruid\">$runame</a></li>";
      $n++;
   }
   ?>
   </ol>

   <p><span class=details>
         <a href="search?browse&member">Browse reviewers</a> |
         <a href="search?member">Search reviewers</a> |
         <?php echo helpWinLink("help-top-rev", "Who qualifies?") ?>
      </span>
</div>
