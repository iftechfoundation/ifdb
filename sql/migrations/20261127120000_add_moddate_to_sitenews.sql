-- migrate:up
ALTER TABLE `sitenews`
  ADD COLUMN IF NOT EXISTS `moddate` datetime DEFAULT NULL AFTER `posted`;

-- migrate:down
ALTER TABLE `sitenews`
  DROP COLUMN `moddate`;
