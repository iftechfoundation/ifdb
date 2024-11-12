USE ifdb;

-- use this script for pending changes to the production DB schema


CREATE TABLE `blockedtagsynonyms` (
    `blockedtagsynonymid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `blockedtag` varchar(255) COLLATE latin1_german2_ci NOT NULL,
    `preferredtag` varchar(255) COLLATE latin1_german2_ci NOT NULL,
    PRIMARY KEY (`blockedtagsynonymid`),
    UNIQUE KEY `blockedtag` (`blockedtag`)
) ENGINE = MyISAM DEFAULT CHARSET = latin1 COLLATE = latin1_german2_ci;

insert into blockedtagsynonyms (blockedtag, preferredtag)
values ('sci-fi', 'science fiction');


ALTER TABLE `stylesheets` ADD COLUMN `dark` tinyint(1) NOT NULL DEFAULT 0;

update stylesheets set contents = '@import url("/ifdb.css");', dark = 1, modified = now() where stylesheetid = 5;

ALTER DATABASE `ifdb` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
ALTER TABLE `audit` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `blockedtagsynonyms` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `compdivs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `competitions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `compgames` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `compprofilelinks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `comps_history` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `crossrecs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `downloadhelp` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `extreviews` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `filetypes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `formatprivs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gamefwds` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gamelinks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gameprofilelinks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gameRatingsSandbox0_mv` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `games_history` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `games` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gametags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gamexrefs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `gamexreftypes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ifids` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `iso639` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `iso639x` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `logins` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `mirrors` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `news` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `nonces` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `operatingsystems` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `osprivs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `osversions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `persistentsessions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `playedgames` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pollcomments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `polls` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pollvotes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `privileges` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reclistitems` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reclists` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reviewflags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reviews` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reviewtags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reviewvotes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `sitenews` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `specialreviewers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `stylepics` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `stylesheets` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tagstats` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `ucomments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `unwishlists` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `userfilters` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `users` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `userScores_mv` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `wishlists` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


--
-- Table structure for table `playertimes`
--

DROP TABLE IF EXISTS `playertimes`;
CREATE TABLE playertimes (
  id INT AUTO_INCREMENT,
  gameid VARCHAR(32) NOT NULL,
  userid VARCHAR(32) NOT NULL,
  time_in_minutes INT(5) unsigned not null,
  PRIMARY KEY (id),
  UNIQUE KEY `game_user` (`gameid`, `userid`)
);

-- Sample time values for The Tempest (by Grigg)
insert into playertimes (gameid, userid, time_in_minutes)
values ('59g5czw7izz7aoip', 'kaw2cas7dyiq2tmg', 63);

insert into playertimes (gameid, userid, time_in_minutes)
values ('59g5czw7izz7aoip', '0000000000000000', 128);

insert into playertimes (gameid, userid, time_in_minutes)
values ('59g5czw7izz7aoip', '0000000000000001', 55);


insert into playertimes (gameid, userid, time_in_minutes)
values ('59g5czw7izz7aoip', 'pwamtkqtbeyc8eyn', 37);

insert into playertimes (gameid, userid, time_in_minutes)
values ('59g5czw7izz7aoip', '6cfekbbjqeduww77', 76);

-- Sample time values for Ninja (by Panks)
insert into playertimes (gameid, userid, time_in_minutes)
values ('n93jonigjmva9e3g', 'kaw2cas7dyiq2tmg', 139);

insert into playertimes (gameid, userid, time_in_minutes)
values ('n93jonigjmva9e3g', '0000000000000001', 134);

insert into playertimes (gameid, userid, time_in_minutes)
values ('n93jonigjmva9e3g', '0000000000000000', 204);

insert into playertimes (gameid, userid, time_in_minutes)
values ('n93jonigjmva9e3g', 'pwamtkqtbeyc8eyn', 116);

-- Sample time values for Four Seconds (by Reigstad)
insert into playertimes (gameid, userid, time_in_minutes)
values ('bu6mmul5vxci5vqc', 'kaw2cas7dyiq2tmg', 1);

insert into playertimes (gameid, userid, time_in_minutes)
values ('bu6mmul5vxci5vqc', 'pwamtkqtbeyc8eyn', 6);



-- View to show the median play times of each game
CREATE OR REPLACE VIEW gametimes AS SELECT DISTINCT gameid, ( median(time_in_minutes) OVER (PARTITION BY gameid) ) as median_time_in_minutes FROM playertimes;
