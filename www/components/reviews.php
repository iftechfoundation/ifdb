<div class=headline id='reviews'><h1 class='unset'>Reviews</h1></div>
<?php

// get the latest reviews
$review_items = getNewItems($db, 7, NEWITEMS_REVIEWS);
showNewItems($db, 0, 5, $review_items, false, false, NEWITEMS_REVIEWS);

?>
<p><span class='details'><a href='allnew?reviews'>See the full list...</a></span></p>