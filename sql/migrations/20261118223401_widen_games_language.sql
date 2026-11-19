-- migrate:up
ALTER TABLE `games` MODIFY COLUMN `language` VARCHAR(128) DEFAULT NULL;

-- migrate:down
ALTER TABLE `games` MODIFY COLUMN `language` VARCHAR(16) DEFAULT NULL;
