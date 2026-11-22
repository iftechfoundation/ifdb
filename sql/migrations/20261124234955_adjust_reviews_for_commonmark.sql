-- migrate:up
-- CommonMark renders two reviews differently, ids 89426 and 87506. We're directly fixing those reviews here.
-- (In CommonMark, a tag alone on a line starts a raw HTML block, which skips Markdown until the next blank line. Joining <sup> and <CODE> to the next line fixes the rendering.)
-- (We have to set moddate to itself to keep ON UPDATE from changing it, since moddate decides whether a review is rendered as Markdown.)
UPDATE `reviews`
  SET `review` = REPLACE(`review`, CONCAT('<sup>', CHAR(13, 10), '¹'), '<sup>¹'), `moddate` = `moddate`
  WHERE `id` = 89426;
UPDATE `reviews`
  SET `review` = REPLACE(`review`, CONCAT('<CODE>', CHAR(13, 10), CHAR(92), '>'), CONCAT('<CODE>', CHAR(92), '>')), `moddate` = `moddate`
  WHERE `id` = 87506;

-- migrate:down
UPDATE `reviews`
  SET `review` = REPLACE(`review`, '<sup>¹', CONCAT('<sup>', CHAR(13, 10), '¹')), `moddate` = `moddate`
  WHERE `id` = 89426;
UPDATE `reviews`
  SET `review` = REPLACE(`review`, CONCAT('<CODE>', CHAR(92), '>'), CONCAT('<CODE>', CHAR(13, 10), CHAR(92), '>')), `moddate` = `moddate`
  WHERE `id` = 87506;
