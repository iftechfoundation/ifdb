-- migrate:up
ALTER TABLE `pollcomments`
  ADD COLUMN IF NOT EXISTS `moddate` datetime DEFAULT NULL;

-- migrate:down
ALTER TABLE `pollcomments`
  DROP COLUMN `moddate`;
