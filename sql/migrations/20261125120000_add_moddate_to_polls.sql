-- migrate:up
ALTER TABLE `polls`
  ADD COLUMN IF NOT EXISTS `moddate` datetime DEFAULT NULL AFTER `created`;

-- migrate:down
ALTER TABLE `polls`
  DROP COLUMN `moddate`;
