<div class=headline id='games'>
    <h1 class='unset'>Games</h1>
    <span class=headlineRss>
        <a href="/editgame?id=new">Add a game listing</a>
    </span>
</div>

<ul class='horizontal'>
    <li><a href="/search?browse&sortby=lnew">New</a><li>
    <li><a href="/search?browse">Top</a></li>
    <li><a href="/search?searchbar=added%3A60d-">Hot</a></li>
    <li><a href="/random">Random</a></li>
    <li><a href="/search?sortby=lnew&searchfor=%23reviews%3A0+wontplay%3Ano">Unreviewed</a></li>
    <li><a href="/search">Advanced Search</a></li>
</ul>
<?php

// get the latest games and game news
$game_items = getNewItems($db, 7, NEWITEMS_GAMES | NEWITEMS_GAMENEWS);
showNewItems($db, 0, 5, $game_items, false, false, NEWITEMS_GAMES | NEWITEMS_GAMENEWS);
?>
<p><span class='details'><a href='search?browse&sortby=lnew'>See the full list...</a></span></p>