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
ALTER TABLE `clubmembers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `clubs` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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

ALTER TABLE `audit` Engine = InnoDB;
ALTER TABLE `blockedtagsynonyms` Engine = InnoDB;
ALTER TABLE `clubmembers` Engine = InnoDB;
ALTER TABLE `clubs` Engine = InnoDB;
ALTER TABLE `compdivs` Engine = InnoDB;
ALTER TABLE `competitions` Engine = InnoDB;
ALTER TABLE `compgames` Engine = InnoDB;
ALTER TABLE `compprofilelinks` Engine = InnoDB;
ALTER TABLE `comps_history` Engine = InnoDB;
ALTER TABLE `crossrecs` Engine = InnoDB;
ALTER TABLE `downloadhelp` Engine = InnoDB;
ALTER TABLE `extreviews` Engine = InnoDB;
ALTER TABLE `filetypes` Engine = InnoDB;
ALTER TABLE `formatprivs` Engine = InnoDB;
ALTER TABLE `gamefwds` Engine = InnoDB;
ALTER TABLE `gamelinks` Engine = InnoDB;
ALTER TABLE `gameprofilelinks` Engine = InnoDB;
ALTER TABLE `gameRatingsSandbox0_mv` Engine = InnoDB;
ALTER TABLE `games_history` Engine = InnoDB;
ALTER TABLE `games` Engine = InnoDB;
ALTER TABLE `gametags` Engine = InnoDB;
ALTER TABLE `gamexrefs` Engine = InnoDB;
ALTER TABLE `gamexreftypes` Engine = InnoDB;
ALTER TABLE `ifids` Engine = InnoDB;
ALTER TABLE `iso639` Engine = InnoDB;
ALTER TABLE `iso639x` Engine = InnoDB;
ALTER TABLE `logins` Engine = InnoDB;
ALTER TABLE `mirrors` Engine = InnoDB;
ALTER TABLE `news` Engine = InnoDB;
ALTER TABLE `nonces` Engine = InnoDB;
ALTER TABLE `operatingsystems` Engine = InnoDB;
ALTER TABLE `osprivs` Engine = InnoDB;
ALTER TABLE `osversions` Engine = InnoDB;
ALTER TABLE `persistentsessions` Engine = InnoDB;
ALTER TABLE `playedgames` Engine = InnoDB;
ALTER TABLE `pollcomments` Engine = InnoDB;
ALTER TABLE `polls` Engine = InnoDB;
ALTER TABLE `pollvotes` Engine = InnoDB;
ALTER TABLE `privileges` Engine = InnoDB;
ALTER TABLE `reclistitems` Engine = InnoDB;
ALTER TABLE `reclists` Engine = InnoDB;
ALTER TABLE `reviewflags` Engine = InnoDB;
ALTER TABLE `reviews` Engine = InnoDB;
ALTER TABLE `reviewtags` Engine = InnoDB;
ALTER TABLE `reviewvotes` Engine = InnoDB;
ALTER TABLE `sitenews` Engine = InnoDB;
ALTER TABLE `specialreviewers` Engine = InnoDB;
ALTER TABLE `stylepics` Engine = InnoDB;
ALTER TABLE `stylesheets` Engine = InnoDB;
ALTER TABLE `tagstats` Engine = InnoDB;
ALTER TABLE `ucomments` Engine = InnoDB;
ALTER TABLE `unwishlists` Engine = InnoDB;
ALTER TABLE `userfilters` Engine = InnoDB;
ALTER TABLE `users` Engine = InnoDB;
ALTER TABLE `userScores_mv` Engine = InnoDB;
ALTER TABLE `wishlists` Engine = InnoDB;
