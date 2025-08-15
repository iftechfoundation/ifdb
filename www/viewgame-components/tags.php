<h2><a name="tags"></a>Tags</h2><div class=indented>


<style nonce="<?php global $nonce; echo $nonce; ?>">
    .viewgame__commonTags { margin-left: 1ex; }
    #myTagList { margin-right: 1ex; }
    #tagStatusSpan { margin-left: 1ex; }
    .viewgame__tagEditorContainer { position:relative;height:0px; }
    .viewgame__tagEditorHeader {
        position:relative;
        padding:2px;
        font-size:80%;
        text-align:center;
    }
    .viewgame__cancel {
        position:absolute;
        top:2px;
        right:0px;
        text-align:right;
    }
    #tagEditor form[name=tagForm], #tagDeletor form[name=tagForm] {
        padding:1ex 1ex 0.5ex 1ex;
        margin:0;
    }
    #editTagTable, #deleteTagTable {
        font-size:85%;
    }
    #myTagFld, #viewgame-add-tags-button {
        vertical-align: middle;
    }
    #viewgame-save-tags-button, #viewgame-save-tags-button-delete {
        margin-top: 1ex;
    }
</style>

<p><span id="tagPre" class=details></span>
<span class='details viewgame__commonTags'>
   - <a href="/showtags?cloud=1&limit=100">View the most common tags</a>
   (<?php echo helpWinLink("help-tags", "What's a tag?")
   ?>)
</span></p>
<style nonce="<?php global $nonce; echo $nonce; ?>">
    .tagTable__container { max-width: 100%; }
</style>
<div class="readMore tagTable__container">
    <div id="tagTable" border=0 class=tags></div>
    <div class="expand"><button>Read More</button></div>
</div>
<?php
if ($curuser) {
?>
    <span class=details>
       <b>Your tags:</b>
       <span id="myTagList"></span> -
       <a href="needjs" id="myTagList_edit">Edit</a>
    <?php if ($adminPriv) { ?>
       <a href="needjs" id="myTagList_delete">Delete</a>
    <?php } ?>

       <span class="xmlstatmsg"
          id="tagStatusSpan"></span>
    </span>
<?php
} else {
    echo "<span class=details>"
        . "(<a href=\"login?dest=viewgame?id=$id%23tags\">Log in</a>"
        . " to add your own tags)</span>";
}
?>

<div class="viewgame__tagEditorContainer">
   <div id="tagEditor">
      <div class='viewgame__tagEditorHeader'>
         <div>
            <b>Edit Tags</b>
         </div>
         <div class='viewgame__cancel'>
            <a href="needjs">Cancel<img src="img/blank.gif"
                  class="popup-close-button"></a>
         </div>
      </div>
      <form name="tagForm" class="edittagsform" data-tag-button="add">
         <script type="text/javascript" nonce="<?php global $nonce; echo $nonce; ?>">
          document.currentScript.parentElement.addEventListener('submit', function (event) {
            event.preventDefault();
            addTags();
          })
        </script>
        <span class=details>
         <a href="/search?tag" target="_blank">Search all tags on IFDB</a> | <a href="/showtags" target="_blank">View all tags on IFDB</a>
        </span>
        <br><br>
         <span class=details>Tags you added are shown below with
            checkmarks.  To remove one of your tags, simply
            un-check it.
         </span>
         <table id="editTagTable" border=0 class=tags>
         </table>
         <br>
         <span class=details>
         Enter new tags here (use commas to separate tags):<br>
         </span>
         <input type=text size=50 id="myTagFld">
         <button class="fancy-button"
            id="viewgame-add-tags-button"
            name="addTags">Add</button>
        <br>
         <button src="img/blank.gif" class="fancy-button"
            id="viewgame-save-tags-button"
            name="saveTags">Save Changes</button>
      </form>
   </div>
</div>

<div class="viewgame__tagEditorContainer">
<div id="tagDeletor">
<div class='viewgame__tagEditorHeader'>
<div>
   <b>Delete Tags</b>
</div>
<div class='viewgame__cancel'>
   <a href="needjs">Cancel<img src="img/blank.gif"
         class="popup-close-button"></a>
</div>
</div>
<form name="tagForm" data-tag-button="add">
<span class=details></span>
<table id="deleteTagTable" border=0 class=tags>
</table>
<button class="fancy-button"
   id="viewgame-save-tags-button-delete"
   name="saveTags">Save Changes</button>
</form>
</div>
</div>

<script type="module" nonce="<?php global $nonce; echo $nonce; ?>">
import {initTagTable} from './viewgame.js';

var dbTagList = <?php
if ($curuser) {
    $isMine = "cast(sum(userid = ? and gameid = ?) as int)";
    $isMineParams = [$curuser, $id];
} else {
    $isMine = "0";
    $isMineParams = [];
}
$result = mysqli_execute_query($db,
    "select
       tag,
       cast(sum(gameid = ?) as int) as tagcnt,
       count(distinct gameid) as gamecnt,
       $isMine as isMine
     from gametags
     where tag in (select tag from gametags where gameid = ?)
     group by tag
     order by tag", [$id, ...$isMineParams, $id]);

echo json_encode(mysqli_fetch_all($result, MYSQLI_ASSOC));
?>
;

initTagTable("<?php echo $id ?>", dbTagList, <?php echo $adminPriv ?>);
//-->
</script>
<?php

echo "</div>";
