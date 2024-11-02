<div class=headline id='competitions'><h1 class='unset'>Competitions</h1>
    <span class=headlineRss>
        <a href="/editcomp?id=new">Add a competition listing</a>
    </span>
</div>

<p><a href="https://ifcomp.org/">IF Comp</a> | <a href="https://www.springthing.net/">Spring Thing</a> | <a href="https://xyzzyawards.org/">XYZZY Awards</a></p>

<?php

// get the latest competitions and competition news
showNewItems($db, 0, 4, false, false, false, NEWITEMS_COMPS | NEWITEMS_COMPNEWS);

?>
<p><span class=details>
    <a href="/search?browse&comp">Browse competitions</a> |
    <a href="/search?comp">Search competitions</a>
</span></p>