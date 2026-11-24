-- migrate:up transaction:false
BEGIN NOT ATOMIC
  IF @@innodb_ft_min_token_size <> 1 OR NOT @@GLOBAL.innodb_ft_enable_stopword
      OR NOT @@GLOBAL.innodb_ft_server_stopword_table <=> 'ifdb/stopwords' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
      'Configure the server with innodb_ft_min_token_size=1, innodb_ft_enable_stopword=1 and innodb_ft_server_stopword_table=ifdb/stopwords first';
  END IF;
END;

CREATE TABLE IF NOT EXISTS `stopwords` (
  `value` varchar(30) NOT NULL,
  PRIMARY KEY (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
INSERT IGNORE INTO `stopwords` VALUES ('a'), ('an'), ('and'), ('of'), ('the');

ALTER TABLE `audit` ENGINE = InnoDB;
ALTER TABLE `blockedtagsynonyms` ENGINE = InnoDB;
ALTER TABLE `clubmembers` ENGINE = InnoDB;
ALTER TABLE `clubs` ENGINE = InnoDB;
ALTER TABLE `compdivs` ENGINE = InnoDB;
ALTER TABLE `competitions` ENGINE = InnoDB;
ALTER TABLE `compgames` ENGINE = InnoDB;
ALTER TABLE `compprofilelinks` ENGINE = InnoDB;
ALTER TABLE `comps_history` ENGINE = InnoDB;
ALTER TABLE `crossrecs` ENGINE = InnoDB;
ALTER TABLE `databaseStats_mv` ENGINE = InnoDB;
ALTER TABLE `extreviews` ENGINE = InnoDB;
ALTER TABLE `filetypes` ENGINE = InnoDB;
ALTER TABLE `formatprivs` ENGINE = InnoDB;
ALTER TABLE `gamefwds` ENGINE = InnoDB;
ALTER TABLE `gamelinks` ENGINE = InnoDB;
ALTER TABLE `gameprofilelinks` ENGINE = InnoDB;
ALTER TABLE `gameRatingsSandbox0_mv` ENGINE = InnoDB;
ALTER TABLE `games` ENGINE = InnoDB;
ALTER TABLE `games_history` ENGINE = InnoDB;
ALTER TABLE `gametags` ENGINE = InnoDB,
  ADD FULLTEXT KEY IF NOT EXISTS `tag_2` (`tag`);
ALTER TABLE `gamexrefs` ENGINE = InnoDB;
ALTER TABLE `gamexreftypes` ENGINE = InnoDB;
ALTER TABLE `ifids` ENGINE = InnoDB;
ALTER TABLE `iso639` ENGINE = InnoDB;
ALTER TABLE `iso639x` ENGINE = InnoDB;
ALTER TABLE `logins` ENGINE = InnoDB;
ALTER TABLE `mirrors` ENGINE = InnoDB;
ALTER TABLE `news` ENGINE = InnoDB;
ALTER TABLE `nonces` ENGINE = InnoDB;
ALTER TABLE `operatingsystems` ENGINE = InnoDB;
ALTER TABLE `osprivs` ENGINE = InnoDB;
ALTER TABLE `osversions` ENGINE = InnoDB;
ALTER TABLE `persistentsessions` ENGINE = InnoDB;
ALTER TABLE `playedgames` ENGINE = InnoDB;
ALTER TABLE `pollcomments` ENGINE = InnoDB;
ALTER TABLE `polls` ENGINE = InnoDB;
ALTER TABLE `pollvotes` ENGINE = InnoDB;
ALTER TABLE `privileges` ENGINE = InnoDB;
ALTER TABLE `reclistitems` ENGINE = InnoDB;
ALTER TABLE `reclists` ENGINE = InnoDB;
ALTER TABLE `reviewflags` ENGINE = InnoDB;
ALTER TABLE `reviews` ENGINE = InnoDB;
ALTER TABLE `reviewtags` ENGINE = InnoDB;
ALTER TABLE `reviewvotes` ENGINE = InnoDB;
ALTER TABLE `sitenews` ENGINE = InnoDB;
ALTER TABLE `specialreviewers` ENGINE = InnoDB;
ALTER TABLE `stylepics` ENGINE = InnoDB;
ALTER TABLE `stylesheets` ENGINE = InnoDB;
ALTER TABLE `tagstats` ENGINE = InnoDB;
ALTER TABLE `ucomments` ENGINE = InnoDB;
ALTER TABLE `unwishlists` ENGINE = InnoDB;
ALTER TABLE `userfilters` ENGINE = InnoDB;
ALTER TABLE `users` ENGINE = InnoDB,
  ADD FULLTEXT KEY IF NOT EXISTS `name_profile` (`name`, `profile`);
ALTER TABLE `userScores_mv` ENGINE = InnoDB;
ALTER TABLE `wishlists` ENGINE = InnoDB;

-- migrate:down transaction:false
ALTER TABLE `audit` ENGINE = MyISAM;
ALTER TABLE `blockedtagsynonyms` ENGINE = MyISAM;
ALTER TABLE `clubmembers` ENGINE = MyISAM;
ALTER TABLE `clubs` ENGINE = MyISAM;
ALTER TABLE `compdivs` ENGINE = MyISAM;
ALTER TABLE `competitions` ENGINE = MyISAM;
ALTER TABLE `compgames` ENGINE = MyISAM;
ALTER TABLE `compprofilelinks` ENGINE = MyISAM;
ALTER TABLE `comps_history` ENGINE = MyISAM;
ALTER TABLE `crossrecs` ENGINE = MyISAM;
ALTER TABLE `databaseStats_mv` ENGINE = MyISAM;
ALTER TABLE `extreviews` ENGINE = MyISAM;
ALTER TABLE `filetypes` ENGINE = MyISAM;
ALTER TABLE `formatprivs` ENGINE = MyISAM;
ALTER TABLE `gamefwds` ENGINE = MyISAM;
ALTER TABLE `gamelinks` ENGINE = MyISAM;
ALTER TABLE `gameprofilelinks` ENGINE = MyISAM;
ALTER TABLE `gameRatingsSandbox0_mv` ENGINE = MyISAM;
ALTER TABLE `games` ENGINE = MyISAM;
ALTER TABLE `games_history` ENGINE = MyISAM;
ALTER TABLE `gametags` DROP KEY IF EXISTS `tag_2`, ENGINE = MyISAM;
ALTER TABLE `gamexrefs` ENGINE = MyISAM;
ALTER TABLE `gamexreftypes` ENGINE = MyISAM;
ALTER TABLE `ifids` ENGINE = MyISAM;
ALTER TABLE `iso639` ENGINE = MyISAM;
ALTER TABLE `iso639x` ENGINE = MyISAM;
ALTER TABLE `logins` ENGINE = MyISAM;
ALTER TABLE `mirrors` ENGINE = MyISAM;
ALTER TABLE `news` ENGINE = MyISAM;
ALTER TABLE `nonces` ENGINE = MyISAM;
ALTER TABLE `operatingsystems` ENGINE = MyISAM;
ALTER TABLE `osprivs` ENGINE = MyISAM;
ALTER TABLE `osversions` ENGINE = MyISAM;
ALTER TABLE `persistentsessions` ENGINE = MyISAM;
ALTER TABLE `playedgames` ENGINE = MyISAM;
ALTER TABLE `pollcomments` ENGINE = MyISAM;
ALTER TABLE `polls` ENGINE = MyISAM;
ALTER TABLE `pollvotes` ENGINE = MyISAM;
ALTER TABLE `privileges` ENGINE = MyISAM;
ALTER TABLE `reclistitems` ENGINE = MyISAM;
ALTER TABLE `reclists` ENGINE = MyISAM;
ALTER TABLE `reviewflags` ENGINE = MyISAM;
ALTER TABLE `reviews` ENGINE = MyISAM;
ALTER TABLE `reviewtags` ENGINE = MyISAM;
ALTER TABLE `reviewvotes` ENGINE = MyISAM;
ALTER TABLE `sitenews` ENGINE = MyISAM;
ALTER TABLE `specialreviewers` ENGINE = MyISAM;
ALTER TABLE `stylepics` ENGINE = MyISAM;
ALTER TABLE `stylesheets` ENGINE = MyISAM;
ALTER TABLE `tagstats` ENGINE = MyISAM;
ALTER TABLE `ucomments` ENGINE = MyISAM;
ALTER TABLE `unwishlists` ENGINE = MyISAM;
ALTER TABLE `userfilters` ENGINE = MyISAM;
ALTER TABLE `users` DROP KEY IF EXISTS `name_profile`, ENGINE = MyISAM;
ALTER TABLE `userScores_mv` ENGINE = MyISAM;
ALTER TABLE `wishlists` ENGINE = MyISAM;
DROP TABLE IF EXISTS `stopwords`;
