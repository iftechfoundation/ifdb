USE ifdb;

-- use this script for pending changes to the production DB schema


alter table users add column welcomeopen tinyint(1) not null default 1;