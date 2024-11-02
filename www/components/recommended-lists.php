<div class=headline id='lists'><h1 class='unset'>Lists</h1>
    <span class=headlineRss>
        <a href="/editlist?id=new">Create a recommended list</a>
    </span>
</div>

<p><span class=details></span></p>
<?php

// get the latest lists
showNewItems($db, 0, 4, false, false, false, NEWITEMS_LISTS);
?>
<p><span class=details>
    <a href="/search?browse&list">Browse lists</a> |
    <a href="/search?list">Search lists</a>
</span></p>