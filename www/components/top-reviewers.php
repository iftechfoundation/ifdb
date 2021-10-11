<div id="top-reviewers-widget" class="aside-box">
   <h3>Reviewer Trophy Room</h3>
   <span class=details><i>IFDB's top reviewers, as determined by <?php echo helpWinLink("help-ff", "Frequent Fiction")?> scores:</i></span><p>

   <ol>
   <?php
   $topReviewersCacheFile = sys_get_temp_dir() . '/top-reviewers-cache';
   $isFresh = filemtime($topReviewersCacheFile) > time()-25*3600;
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
      echo "<li><span style='padding: 0 1.5ex 0 1ex;' class=details><i>#$n</i></span>"
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