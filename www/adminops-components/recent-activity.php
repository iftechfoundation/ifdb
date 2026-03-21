// For long author and title strings, cut off the 
// string after a set number of characters and 
// add "..." if any characters were removed.
function shortenString($long_string) {
    $short_string = substr($long_string,0,50);
    if ($short_string != $long_string) {
        $short_string .= "...";
    }
    return $short_string;
}


if (isset($_REQUEST['recentactivity'])) {
    // Find how many days of recent activity to display.
    // If it's not specified, default to 3 days.
    $recent_days = (int)get_req_data('days');
    if ($recent_days == 0) {
        $recent_days = 3;
    }
    pageHeader("Recent activity by all users");    
    echo '<h1 id="recent-activity">Recent activity by all users</h1>';
    echo "<p>Showing activity from the past $recent_days days. For a different number of days, please edit the URL.</p>";
    // Horizontal menu to jump to different types of activity
    echo '<p>';
    echo '<a href="#recent-ratings">Ratings and reviews</a> | ';
    echo '<a href="#recent-review-votes">Review votes</a> | ';
    echo '<a href="#recent-edits">Page edits</a> | ';
    echo '<a href="#recent-tags">Tags</a> | ';
    echo '<a href="#recent-poll-votes">Poll votes</a>';
    echo '</p><hr>';


    // Find recent ratings and reviews
    $result = mysqli_execute_query($db, 
        'SELECT
            reviews.moddate AS reviewdatetime,
            CASE WHEN reviews.createdate = reviews.moddate
                THEN "added"
                ELSE "edited"
                END AS action,
            DATE_FORMAT(reviews.moddate, "%M %e, %Y") AS reviewdate,
            reviews.userid AS reviewerid,
            reviews.gameid,
            reviews.rating,
            reviews.review,
            reviews.id AS reviewid,
            CASE WHEN reviews.special = 4
                THEN "external "
                ELSE ""
                END AS externalreview,
            games.title AS gametitle,
            games.author AS gameauthor,
            users.name AS reviewername
        FROM reviews 
        LEFT JOIN games
            ON reviews.gameid = games.id
        LEFT JOIN users
            ON reviews.userid = users.id
        WHERE (reviews.moddate) > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY reviews.moddate DESC', [$recent_days]);


    // Display recent ratings and reviews    
    echo "<h2 id='recent-ratings'>Recent ratings and reviews:</h2>";
    if (mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $date_heading = "";
        foreach ($rows as $r) {
            $review_date = $r['reviewdate'];
            $reviewer_id = $r['reviewerid'];
            $reviewer_name = $r['reviewername'];
            $external_review = $r['externalreview'];
            $action = $r['action'];
            $rating = "";
            if ($r['rating']) {
                $rating = $r['rating'];
            } else {
                $rating = "no";
            }
            $review_id = $r['reviewid'];
            $rating_or_review = "";
            if ($r['review'] != "") {
                $rating_or_review = "review";
            } else {
                $rating_or_review = "rating";
            }
            $game_id = $r['gameid'];
            $game_title = shortenString($r['gametitle']);
            $game_author = shortenString($r['gameauthor']);

            // If the current rating/review has a different 
            // date than the last rating/review we printed, 
            // display a heading showing the new date 
            if ($review_date != $date_heading) {
                if ($date_heading != "") {
                    echo "</ul>";
                }
                $date_heading = $review_date;
                echo "<h3>$date_heading</h3>";
                echo "<ul>";
            }
            // Print a little report about this rating/review
            if ($reviewer_name) {
                echo "<li><a href='/showuser?id=$reviewer_id'>$reviewer_name</a> ";
            } else {
                echo "<li>Someone ";    // External reviews won't have a reviewer name or a regular reviewer id
            }
            if ($action == "added") {
                echo "added ";
            } else if ($action == "edited") {
                echo "edited a rating or review, now ";
            }
            echo "a {$rating}-star <a href='/viewgame?id={$game_id}&review={$review_id}'>{$external_review}{$rating_or_review}</a> ";
            echo "of <i><a href='/viewgame?id={$game_id}'>$game_title</a></i> by $game_author.</li><br>";
        }
        echo "</ul>";
    } else {
        echo "<p>No ratings or reviews in the past $recent_days days.</p>";
    }
    echo '<p><a href="#recent-activity">Return to top</a></p><hr>';


    // Find recent review helpfulness votes
    $result = mysqli_execute_query($db, 
        'SELECT
            reviewvotes.createdate AS votedatetime,
            DATE_FORMAT(reviewvotes.createdate, "%M %e, %Y") AS votedate,
            reviewvotes.userid as voterid,
            reviewvotes.vote,
            reviewvotes.reviewid,
            reviews.gameid,
            reviews.userid as reviewerid,
            users.name AS reviewername
        FROM reviewvotes
        LEFT JOIN reviews
            ON reviewvotes.reviewid = reviews.id
        LEFT JOIN users
            ON reviews.userid = users.id
        WHERE (reviewvotes.createdate) > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY reviewvotes.createdate DESC LIMIT 100', [$recent_days]);


    // Display recent review helpfulness votes    
    echo '<h2 id="recent-review-votes">Recent votes on review helpfulness:</h2>';
    if (mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $date_heading = "";
        foreach ($rows as $r) {
            $vote_date = $r['votedate'];
            $voter_id = $r['voterid'];
            $vote = "";
            if ($r['vote'] == "Y") {
                $vote = "Yes";
            } else if ($r['vote'] == "N") {
                $vote = "No";
            };
            $review_id = $r['reviewid'];
            $game_id = $r['gameid'];
            $reviewer_id = $r['reviewerid'];
            $reviewer_name = shortenString($r['reviewername']);
            
            // If the current vote has a different 
            // date than the last vote we printed, 
            // display a heading showing the new date
            if ($vote_date != $date_heading) {
                if ($date_heading != "") {
                    echo "</ul>";
                }
                $date_heading = $vote_date;
                echo "<h3>$date_heading</h3>";
                echo "<ul>";
            }
            // Print a little report about this review vote
            echo "<li>User <a href='/showuser?id={$voter_id}'>$voter_id</a> ";
            echo 'voted "' . $vote . '" on a ';
            echo "<a href='/viewgame?id={$game_id}&review={$review_id}'>review</a> ";
            echo "by $reviewer_name.</li><br>";
        }
        echo "</ul>";
    } else {
        echo "<p>No votes on review helpfulness in the past $recent_days days.</p>";
    }
    echo '<p><a href="#recent-activity">Return to top</a></p><hr>';


    // Find recent page edits
    $result = mysqli_execute_query($db, 
        'SELECT
            games_history.moddate AS editdatetime,
            DATE_FORMAT(games_history.moddate, "%M %e, %Y") AS editdate,
            games_history.id as gameid,
            games_history.editedby as editorid,
            games.title AS gametitle,
            games.author AS gameauthor,
            users.name AS editorname
        FROM games_history
        LEFT JOIN games
            ON games_history.id = games.id
        LEFT JOIN users
            ON games_history.editedby = users.id
        WHERE (games_history.moddate) > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY games_history.moddate DESC', [$recent_days]);


    // Display recent page edits
    echo "<h2 id='recent-edits'>Recent page edits:</h2>";
    if (mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $date_heading = "";
        foreach ($rows as $r) {
            $edit_date = $r['editdate'];
            $editor_id = $r['editorid'];
            $editor_name = $r['editorname'];
            $game_id = $r['gameid'];
            $game_title = shortenString($r['gametitle']);
            $game_author = shortenString($r['gameauthor']);

            // If this page edit has a different date
            // than the last page edit we printed, 
            // display a heading showing the new date
            if ($edit_date != $date_heading) {
                if ($date_heading != "") {
                    echo "</ul>";
                }
                $date_heading = $edit_date;
                echo "<h3>$date_heading</h3>";
                echo "<ul>";
            }
            // Print a little report about this page edit
            echo "<li><a href='/showuser?id=$editor_id'>$editor_name</a> ";
            echo "edited the page for ";
            echo "<i><a href='/viewgame?id={$game_id}'>$game_title</a></i> ";
            echo "by $game_author.</li></br>";
        }
        echo "</ul>";
    } else {
        echo "<p>No page edits in the past $recent_days days.</p>";
    }
    echo '<p><a href="#recent-activity">Return to top</a></p><hr>';


    // Find recent tagging activity
    $result = mysqli_execute_query($db, 
        'SELECT
            gametags.moddate AS tagdatetime,
            DATE_FORMAT(gametags.moddate, "%M %e, %Y") AS tagdate,
            gametags.gameid,
            gametags.userid as taggerid,
            gametags.tag,
            games.title AS gametitle,
            games.author AS gameauthor
        FROM gametags
        LEFT JOIN games
            ON gametags.gameid = games.id
        WHERE (gametags.moddate) > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY gametags.moddate DESC', [$recent_days]);


    // Display recent tagging activity
    echo "<h2 id='recent-tags'>Recent tags:</h2>";
    if (mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $date_heading = "";
        foreach ($rows as $r) {
            $tag_date = $r['tagdate'];
            $tagger_id = $r['taggerid'];
            $tag = $r['tag'];
            $game_id = $r['gameid'];
            $game_title = shortenString($r['gametitle']);
            $game_author = shortenString($r['gameauthor']);

            // If this tagging action has a different date
            // than the last tagging action we printed, 
            // display a heading showing the new date
            if ($tag_date != $date_heading) {
                if ($date_heading != "") {
                    echo "</ul>";
                }
                $date_heading = $tag_date;
                echo "<h3>$date_heading</h3>";
                echo "<ul>";
            }
            // Print a little report about this tagging action
            echo "<li>User <a href='/showuser?id=$tagger_id'>$tagger_id</a> ";
            echo 'added the tag "' . $tag . '" to ';
            echo "<i><a href='/viewgame?id={$game_id}'>$game_title</a></i> ";
            echo "by $game_author.</li></br>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tagging activity in the past $recent_days days.</p>";
    }
    echo '<p><a href="#recent-activity">Return to top</a></p><hr>';


    // Find recent poll votes
    $result = mysqli_execute_query($db, 
        'SELECT
            pollvotes.votedate AS votedatetime,
            DATE_FORMAT(pollvotes.votedate, "%M %e, %Y") AS votedate,
            pollvotes.userid as voterid,
            pollvotes.gameid,
            pollvotes.quickquote,
            pollvotes.notes,
            pollvotes.pollid,
            games.title AS gametitle,
            games.author AS gameauthor,
            users.name AS votername,
            polls.title AS polltitle,
            CASE WHEN polls.flags = 1
                THEN true
                ELSE false
                END AS voteisanonymous
        FROM pollvotes
        LEFT JOIN games
            ON pollvotes.gameid = games.id
        LEFT JOIN users
            ON pollvotes.userid = users.id
        LEFT JOIN polls
            on pollvotes.pollid = polls.pollid
        WHERE (pollvotes.votedate) > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY pollvotes.votedate DESC', [$recent_days]);

    // Display recent poll votes
    echo "<h2 id='recent-poll-votes'>Recent poll votes:</h2>";
    if (mysqli_num_rows($result) > 0) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $date_heading = "";
        foreach ($rows as $r) {
            $vote_date = $r['votedate'];
            $voter_id = $r['voterid'];
            $voter_name = $r['votername'];
            $quick_quote = $r['quickquote'];
            $notes = $r['notes'];
            $game_id = $r['gameid'];
            $game_title = shortenString($r['gametitle']);
            $game_author = shortenString($r['gameauthor']);
            $poll_id = $r['pollid'];
            $poll_title = shortenString($r['polltitle']);
            $vote_is_anonymous = ($r['voteisanonymous']);

            // If this poll vote has a different date
            // than the last poll vote we printed, 
            // display a heading showing the new date
            if ($vote_date != $date_heading) {
                if ($date_heading != "") {
                    echo "</ul>";
                }
                $date_heading = $vote_date;
                echo "<h3>$date_heading</h3>";
                echo "<ul>";
            }
            // Print a little report about this poll vote
            echo "<li>";
            if (!$vote_is_anonymous) {
                echo "<a href='/showuser?id=$voter_id'>$voter_name</a>";
            } else if ($vote_is_anonymous) {
                echo "User <a href='/showuser?id=$voter_id'>$voter_id</a>";
            }    
            echo " voted for ";
            echo "<i><a href='/viewgame?id={$game_id}'>$game_title</a></i> by $game_author ";
            echo "in the poll <a href='/poll?id={$poll_id}'>$poll_title</a>.";
            if ($quick_quote || $notes) {
                echo ' Comment: "' ;
                if ($quick_quote) {
                    echo $quick_quote;
                    if ($notes) {
                        echo ": $notes";
                    }
                } else {
                    echo $notes;
                }
                echo '" ';
            }
            echo "</li><br>";  
        }
        echo "</ul>";
    } else {
        echo "<p>No poll votes in the past $recent_days days.</p>";
    }
    echo '<p><a href="#recent-activity">Return to top</a></p>';
    echo '<hr>';
    echo '<p><a href="/adminops">Return to the System Maintenance Panel</a></p>';
    pageFooter();
    exit();
} // if (isset($_REQUEST['recentactivity']))
 