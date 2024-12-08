USE ifdb;

-- use this script for pending changes to the production DB schema

alter table reviewvotes
    add column `reviewvoteid` bigint(20) unsigned NOT NULL AUTO_INCREMENT FIRST,
    add column `createdate` datetime NOT NULL DEFAULT current_timestamp(),
    add PRIMARY KEY (`reviewvoteid`)
;

-- Add column for game search filter to the users table

ALTER TABLE `users` ADD COLUMN `game_filter` VARCHAR(150) DEFAULT '';
