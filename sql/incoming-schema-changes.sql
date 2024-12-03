USE ifdb;

-- use this script for pending changes to the production DB schema



-- Add column for game search filter to the users table

ALTER TABLE `users` ADD COLUMN `game_search_filter` VARCHAR(150) DEFAULT '';
