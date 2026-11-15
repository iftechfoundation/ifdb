-- migrate:up
ALTER TABLE `users`
  MODIFY `activationcode` varchar(40) DEFAULT NULL COMMENT 'Used for email activations and password resets',
  ADD COLUMN IF NOT EXISTS `resetexpires` datetime DEFAULT NULL COMMENT 'Set while activationcode is a password reset code' AFTER `activationcode`;

-- migrate:down
ALTER TABLE `users`
  MODIFY `activationcode` varchar(40) DEFAULT NULL,
  DROP COLUMN `resetexpires`;
