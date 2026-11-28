-- migrate:up
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `profilemoddate` datetime DEFAULT NULL AFTER `profile`;

-- migrate:down
ALTER TABLE `users`
  DROP COLUMN `profilemoddate`;
