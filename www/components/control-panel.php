<?php
function cpbtn($icon, $label, $href, $top) {
    echo "<a href=\"$href\">"
    . "<div class=\"cpanelBtn" . ($top ? " cpanelBtnTop" : "") . "\"><img src=\"/img/blank.gif\" class=\"$icon cpanelBtnIcon\" alt=\"\">"
    . "$label"
    . "</div></a>";
}
?>
                  <div class="cpanelHeading cpanelHeadingTop">Find</div>
                  <?php cpbtn("game-search-icon", "Advanced Game Search",
                              "search", true) ?>
                  <?php cpbtn("list-search-icon", "Find a Recommended List",
                              "search?list", false) ?>
                  <?php cpbtn("poll-search-icon", "Find a Poll",
                              "search?poll", false) ?>
                  <?php cpbtn("new-poll-icon", "Create a Poll",
                              "poll?id=new", false) ?>
                  <?php cpbtn("competition-search-icon","Find a Competition",
                              "search?comp", false) ?>
                  <?php cpbtn("club-search-icon","Find a Club",
                              "search?club", false) ?>
                  <?php cpbtn("member-search-icon", "Look Up a Member",
                              "search?member", false) ?>

                  <div class="cpanelHeading">Browse</div>
                  <?php cpbtn("random-list-icon", "10 Random Games",
                              "random", true) ?>
                  <?php cpbtn("browse-games-icon", "Browse Games",
                              "search?browse", false) ?>
                  <?php cpbtn("browse-lists-icon", "Browse Lists",
                              "search?browse&list&sortby=new", false) ?>
                  <?php cpbtn("browse-polls-icon", "Browse Polls",
                              "search?browse&poll&sortby=new", false) ?>
                  <?php cpbtn("browse-competitions-icon", "Browse Competitions",
                              "search?browse&comp&sortby=awn", false) ?>
                  <?php cpbtn("browse-clubs-icon", "Browse Clubs",
                              "search?browse&club&sortby=new", false) ?>
                  <?php cpbtn("browse-members-icon", "Browse Members",
                              "search?browse&member&sortby=new", false) ?>

                  <div class="cpanelHeading">Contribute</div>
                  <?php cpbtn("create-review-icon", "Review a Game",
                              "review?browse", true) ?>
                  <?php cpbtn("create-list-icon", "Create a Recommended List",
                              "editlist?id=new", false) ?>
                  <?php cpbtn("create-game-icon", "Add a Game Listing",
                              "editgame?id=new", false) ?>
                  <?php cpbtn("edit-game-icon", "Edit a Game Listing",
                              "editgame?search", false) ?>
                  <?php cpbtn("create-competition-icon", "Add a Competition Page",
                              "editcomp?id=new", false) ?>
                  <?php cpbtn("create-club-icon", "Add a Club Listing",
                              "editclub?id=new", false) ?>

                  <div class="cpanelHeading">Personalize</div>
                  <?php cpbtn("my-profile-icon", "Edit Your Profile",
                              "editprofile", true) ?>
                  <?php cpbtn("style-gallery-icon", "Pick a Custom Display Style",
                              "styles", true) ?>

                  <?php
                  if ($adminPriv) {
                      echo "<div class='cpanelHeading'>Administration</div>";
                      cpbtn("empty-button-icon", "System Maintenance Panel",
                            "adminops", true);
                  }
                  ?>