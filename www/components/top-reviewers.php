<div id="top-reviewers-widget" class="aside-box">
   <h3>Reviewer Trophy Room</h3>
   <span class=details><i>IFDB's top reviewers, as determined by <?php echo helpWinLink("help-ff", "Frequent Fiction")?> scores:</i></span><p>

   <ol>
   <?php
   global $nonce;
   echo "<style nonce='$nonce'>\n"
      . ".top-reviewers__reviewer { padding: 0 1.5ex 0 1ex; }\n"
      . "</style>\n";

   $topReviewersCacheFile = sys_get_temp_dir() . '/top-reviewers-cache';
   $isFresh = filemtime($topReviewersCacheFile) > time()-25*3600;
   $rlst = null;
   if ($isFresh) {
      $input = file_get_contents($topReviewersCacheFile);
      if ($input) {
         $rlst = unserialize($input);
      }
   }
   if (!$rlst) $rlst = getTopReviewers($db, 4);
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
         <a href="search?sortby=ffrank&browse=1&member=1">See all</a> |
         <?php echo helpWinLink("help-top-rev", "Who qualifies?") ?>
      </span>
</div>
