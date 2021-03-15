USE ifdb;

DROP TABLE IF EXISTS `audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit` (
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `action` mediumtext COLLATE latin1_german2_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `clubs`
  ADD COLUMN `password` varchar(40) COLLATE latin1_german2_ci DEFAULT NULL,
  ADD COLUMN `pswsalt` varchar(32) COLLATE latin1_german2_ci DEFAULT NULL
;

ALTER TABLE `games`
  ADD COLUMN `flags` int(11) COLLATE latin1_german2_ci NOT NULL DEFAULT '0'
;

DROP TABLE IF EXISTS `userfilters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userfilters` (
  `userid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `targetuserid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `filtertype` char(1) COLLATE latin1_german2_ci NOT NULL,
  KEY `userid` (`userid`),
  KEY `targetuserid` (`targetuserid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `users`
    ADD COLUMN `email` varchar(255) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
    ADD COLUMN `emailflags` tinyint(2) NOT NULL DEFAULT '3',
    ADD COLUMN `profilestatus` varchar(1) COLLATE latin1_german2_ci DEFAULT NULL,
    ADD COLUMN `password` varchar(40) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
    ADD COLUMN `pswsalt` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
    ADD COLUMN `activationcode` varchar(40) COLLATE latin1_german2_ci DEFAULT NULL,
    ADD COLUMN `acctstatus` char(1) COLLATE latin1_german2_ci NOT NULL DEFAULT '0',
    ADD COLUMN `lastlogin` datetime DEFAULT NULL,
    ADD COLUMN `privileges` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '''''',
    ADD COLUMN `defaultos` int(11) unsigned DEFAULT NULL,
    ADD COLUMN `defaultosvsn` int(11) unsigned DEFAULT NULL,
    ADD COLUMN `noexedownloads` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `publiclists` varchar(10) COLLATE latin1_german2_ci DEFAULT NULL,
    ADD COLUMN `mirrorid` int(11) unsigned NOT NULL DEFAULT '100',
    ADD COLUMN `stylesheetid` bigint(20) DEFAULT NULL,
    ADD COLUMN `offsite_display` char(1) COLLATE latin1_german2_ci NOT NULL DEFAULT 'A',
    ADD COLUMN `accessibility` tinyint(1) DEFAULT NULL,
    ADD COLUMN `caughtupdate` datetime DEFAULT NULL,
    ADD COLUMN `remarks` mediumtext COLLATE latin1_german2_ci,
    ADD COLUMN `tosversion` int(11) NOT NULL DEFAULT '1',
    ADD COLUMN `Sandbox` int(11) NOT NULL DEFAULT '0',
    ADD KEY `email` (`email`)
;

--
-- Table structure for table `formatprivs`
--

DROP TABLE IF EXISTS `formatprivs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formatprivs` (
  `fmtid` int(11) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logins`
--

DROP TABLE IF EXISTS `logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logins` (
  `uid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `ip` varchar(16) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `when` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nonces`
--

DROP TABLE IF EXISTS `nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nonces` (
  `nonceid` varchar(256) COLLATE latin1_german2_ci NOT NULL,
  `hash` varchar(40) COLLATE latin1_german2_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `nonceid` (`nonceid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `osprivs`
--

DROP TABLE IF EXISTS `osprivs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `osprivs` (
  `osid` int(11) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `persistentsessions`
--

DROP TABLE IF EXISTS `persistentsessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `persistentsessions` (
  `id` varchar(64) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `lastlogin` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `privileges`
--

DROP TABLE IF EXISTS `privileges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privileges` (
  `code` char(1) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `name` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reviewflags`
--

DROP TABLE IF EXISTS `reviewflags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviewflags` (
  `reviewid` bigint(20) NOT NULL,
  `flagger` varchar(32) COLLATE latin1_german2_ci NOT NULL,
  `flagtype` char(1) COLLATE latin1_german2_ci NOT NULL,
  `notes` mediumtext COLLATE latin1_german2_ci,
  `created` datetime NOT NULL,
  KEY `reviewid` (`reviewid`),
  KEY `flagger` (`flagger`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `reviewvotes`
  ADD COLUMN `userid` char(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  ADD KEY `userid` (`userid`)
;

--
-- Table structure for table `stylepics`
--

DROP TABLE IF EXISTS `stylepics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stylepics` (
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `name` varchar(128) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `picture` varchar(64) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `desc` mediumtext COLLATE latin1_german2_ci,
  `picturebytes` int(11) DEFAULT NULL,
  PRIMARY KEY (`userid`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `ucomments`
  ADD COLUMN `private` varchar(32) COLLATE latin1_german2_ci DEFAULT NULL
;

--
-- Temporary table structure for view `userScores`
--

DROP TABLE IF EXISTS `userScores`;
/*!50001 DROP VIEW IF EXISTS `userScores`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `userScores` (
  `userid` tinyint NOT NULL,
  `score` tinyint NOT NULL,
  `rankingScore` tinyint NOT NULL,
  `reviewCount` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `userfilters`
--

DROP TABLE IF EXISTS `userfilters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userfilters` (
  `userid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `targetuserid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `filtertype` char(1) COLLATE latin1_german2_ci NOT NULL,
  KEY `userid` (`userid`),
  KEY `targetuserid` (`targetuserid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `userscoreitems`
--

DROP TABLE IF EXISTS `userscoreitems`;
/*!50001 DROP VIEW IF EXISTS `userscoreitems`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `userscoreitems` (
  `userid` tinyint NOT NULL,
  `score` tinyint NOT NULL,
  `isReview` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `visreviews`
--

DROP TABLE IF EXISTS `visreviews`;
/*!50001 DROP VIEW IF EXISTS `visreviews`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `visreviews` (
  `id` tinyint NOT NULL,
  `summary` tinyint NOT NULL,
  `review` tinyint NOT NULL,
  `rating` tinyint NOT NULL,
  `userid` tinyint NOT NULL,
  `createdate` tinyint NOT NULL,
  `moddate` tinyint NOT NULL,
  `embargodate` tinyint NOT NULL,
  `special` tinyint NOT NULL,
  `gameid` tinyint NOT NULL,
  `RFlags` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `gameRatings`
--

/*!50001 DROP TABLE IF EXISTS `gameRatings`*/;
/*!50001 DROP VIEW IF EXISTS `gameRatings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `gameRatings` AS select `reviews`.`gameid` AS `gameid`,avg(if((`reviews`.`RFlags` & 2),NULL,`reviews`.`rating`)) AS `avgRating`,std(if((`reviews`.`RFlags` & 2),NULL,`reviews`.`rating`)) AS `stdDevRating`,count(if((`reviews`.`RFlags` & 2),NULL,`reviews`.`rating`)) AS `numRatingsInAvg`,count(`reviews`.`rating`) AS `numRatingsTotal`,count(if(isnull(`reviews`.`special`),`reviews`.`review`,NULL)) AS `numMemberReviews` from `reviews` where ifnull((now() > `reviews`.`embargodate`),1) group by `reviews`.`gameid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `gameRatingsSandbox0`
--

/*!50001 DROP TABLE IF EXISTS `gameRatingsSandbox0`*/;
/*!50001 DROP VIEW IF EXISTS `gameRatingsSandbox0`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `gameRatingsSandbox0` AS select `averaged`.`gameid` AS `gameid`,`averaged`.`rated1` AS `rated1`,`averaged`.`rated2` AS `rated2`,`averaged`.`rated3` AS `rated3`,`averaged`.`rated4` AS `rated4`,`averaged`.`rated5` AS `rated5`,`averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,`averaged`.`numRatingsTotal` AS `numRatingsTotal`,`averaged`.`numMemberReviews` AS `numMemberReviews`,`averaged`.`avgRating` AS `avgRating`,pow((pow(1 - `averaged`.`avgRating`,2) * `averaged`.`rated1` + pow(2 - `averaged`.`avgRating`,2) * `averaged`.`rated2` + pow(3 - `averaged`.`avgRating`,2) * `averaged`.`rated3` + pow(4 - `averaged`.`avgRating`,2) * `averaged`.`rated4` + pow(5 - `averaged`.`avgRating`,2) * `averaged`.`rated5`) / `averaged`.`numRatingsInAvg`,0.5) AS `stdDevRating`,(5 * (`averaged`.`rated5` + 1) + 4 * (`averaged`.`rated4` + 1) + 3 * (`averaged`.`rated3` + 1) + 2 * (`averaged`.`rated2` + 1) + 1 * (`averaged`.`rated1` + 1)) / (5 + `averaged`.`numRatingsInAvg`) - 1.65 * sqrt(((25 * (`averaged`.`rated5` + 1) + 16 * (`averaged`.`rated4` + 1) + 9 * (`averaged`.`rated3` + 1) + 4 * (`averaged`.`rated2` + 1) + 1 * (`averaged`.`rated1` + 1)) / (5 + `averaged`.`numRatingsInAvg`) - pow((5 * (`averaged`.`rated5` + 1) + 4 * (`averaged`.`rated4` + 1) + 3 * (`averaged`.`rated3` + 1) + 2 * (`averaged`.`rated2` + 1) + 1 * (`averaged`.`rated1` + 1)) / (5 + `averaged`.`numRatingsInAvg`),2)) / (6 + `averaged`.`numRatingsInAvg`)) AS `starsort` from (select `rating_counts`.`gameid` AS `gameid`,`rating_counts`.`rated1` AS `rated1`,`rating_counts`.`rated2` AS `rated2`,`rating_counts`.`rated3` AS `rated3`,`rating_counts`.`rated4` AS `rated4`,`rating_counts`.`rated5` AS `rated5`,`rating_counts`.`numRatingsInAvg` AS `numRatingsInAvg`,`rating_counts`.`numRatingsTotal` AS `numRatingsTotal`,`rating_counts`.`numMemberReviews` AS `numMemberReviews`,(`rating_counts`.`rated1` + `rating_counts`.`rated2` * 2 + `rating_counts`.`rated3` * 3 + `rating_counts`.`rated4` * 4 + `rating_counts`.`rated5` * 5) / `rating_counts`.`numRatingsInAvg` AS `avgRating` from (select `grouped`.`gameid` AS `gameid`,sum(case when `grouped`.`rating` = 1 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated1`,sum(case when `grouped`.`rating` = 2 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated2`,sum(case when `grouped`.`rating` = 3 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated3`,sum(case when `grouped`.`rating` = 4 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated4`,sum(case when `grouped`.`rating` = 5 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated5`,sum(case when `grouped`.`rating` in (1,2,3,4,5) and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `numRatingsInAvg`,sum(case when `grouped`.`rating` in (1,2,3,4,5) then `grouped`.`count` else 0 end) AS `numRatingsTotal`,sum(case when `grouped`.`hasReview` then `grouped`.`count` else 0 end) AS `numMemberReviews` from (select count(`ifdb`.`reviews`.`id`) AS `count`,`ifdb`.`reviews`.`rating` AS `rating`,`ifdb`.`reviews`.`gameid` AS `gameid`,ifnull(`ifdb`.`reviews`.`RFlags`,0) & 2 AS `omitted`,ifnull(`ifdb`.`reviews`.`special`,`ifdb`.`reviews`.`review`) is not null AS `hasReview` from (`ifdb`.`reviews` left join `ifdb`.`users` on(`ifdb`.`reviews`.`userid` = `ifdb`.`users`.`id`)) where ifnull(`ifdb`.`users`.`Sandbox`,0) = 0 and ifnull(current_timestamp() > `ifdb`.`reviews`.`embargodate`,1) group by `ifdb`.`reviews`.`rating`,`ifdb`.`reviews`.`gameid`,ifnull(`ifdb`.`reviews`.`RFlags`,0) & 2,ifnull(`ifdb`.`reviews`.`special`,`ifdb`.`reviews`.`review`) is not null) `grouped` group by `grouped`.`gameid`) `rating_counts`) `averaged` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `gameRatingsSandbox01`
--

/*!50001 DROP TABLE IF EXISTS `gameRatingsSandbox01`*/;
/*!50001 DROP VIEW IF EXISTS `gameRatingsSandbox01`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `gameRatingsSandbox01` AS select `averaged`.`gameid` AS `gameid`,`averaged`.`rated1` AS `rated1`,`averaged`.`rated2` AS `rated2`,`averaged`.`rated3` AS `rated3`,`averaged`.`rated4` AS `rated4`,`averaged`.`rated5` AS `rated5`,`averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,`averaged`.`numRatingsTotal` AS `numRatingsTotal`,`averaged`.`numMemberReviews` AS `numMemberReviews`,`averaged`.`avgRating` AS `avgRating`,pow((pow(1 - `averaged`.`avgRating`,2) * `averaged`.`rated1` + pow(2 - `averaged`.`avgRating`,2) * `averaged`.`rated2` + pow(3 - `averaged`.`avgRating`,2) * `averaged`.`rated3` + pow(4 - `averaged`.`avgRating`,2) * `averaged`.`rated4` + pow(5 - `averaged`.`avgRating`,2) * `averaged`.`rated5`) / `averaged`.`numRatingsInAvg`,0.5) AS `stdDevRating`,(5 * (`averaged`.`rated5` + 1) + 4 * (`averaged`.`rated4` + 1) + 3 * (`averaged`.`rated3` + 1) + 2 * (`averaged`.`rated2` + 1) + 1 * (`averaged`.`rated1` + 1)) / (5 + `averaged`.`numRatingsInAvg`) - 1.65 * sqrt(((25 * (`averaged`.`rated5` + 1) + 16 * (`averaged`.`rated4` + 1) + 9 * (`averaged`.`rated3` + 1) + 4 * (`averaged`.`rated2` + 1) + 1 * (`averaged`.`rated1` + 1)) / (5 + `averaged`.`numRatingsInAvg`) - pow((5 * (`averaged`.`rated5` + 1) + 4 * (`averaged`.`rated4` + 1) + 3 * (`averaged`.`rated3` + 1) + 2 * (`averaged`.`rated2` + 1) + 1 * (`averaged`.`rated1` + 1)) / (5 + `averaged`.`numRatingsInAvg`),2)) / (6 + `averaged`.`numRatingsInAvg`)) AS `starsort` from (select `rating_counts`.`gameid` AS `gameid`,`rating_counts`.`rated1` AS `rated1`,`rating_counts`.`rated2` AS `rated2`,`rating_counts`.`rated3` AS `rated3`,`rating_counts`.`rated4` AS `rated4`,`rating_counts`.`rated5` AS `rated5`,`rating_counts`.`numRatingsInAvg` AS `numRatingsInAvg`,`rating_counts`.`numRatingsTotal` AS `numRatingsTotal`,`rating_counts`.`numMemberReviews` AS `numMemberReviews`,(`rating_counts`.`rated1` + `rating_counts`.`rated2` * 2 + `rating_counts`.`rated3` * 3 + `rating_counts`.`rated4` * 4 + `rating_counts`.`rated5` * 5) / `rating_counts`.`numRatingsInAvg` AS `avgRating` from (select `grouped`.`gameid` AS `gameid`,sum(case when `grouped`.`rating` = 1 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated1`,sum(case when `grouped`.`rating` = 2 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated2`,sum(case when `grouped`.`rating` = 3 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated3`,sum(case when `grouped`.`rating` = 4 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated4`,sum(case when `grouped`.`rating` = 5 and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `rated5`,sum(case when `grouped`.`rating` in (1,2,3,4,5) and `grouped`.`omitted` = 0 then `grouped`.`count` else 0 end) AS `numRatingsInAvg`,sum(case when `grouped`.`rating` in (1,2,3,4,5) then `grouped`.`count` else 0 end) AS `numRatingsTotal`,sum(case when `grouped`.`hasReview` then `grouped`.`count` else 0 end) AS `numMemberReviews` from (select count(`ifdb`.`reviews`.`id`) AS `count`,`ifdb`.`reviews`.`rating` AS `rating`,`ifdb`.`reviews`.`gameid` AS `gameid`,ifnull(`ifdb`.`reviews`.`RFlags`,0) & 2 AS `omitted`,ifnull(`ifdb`.`reviews`.`special`,`ifdb`.`reviews`.`review`) is not null AS `hasReview` from (`ifdb`.`reviews` left join `ifdb`.`users` on(`ifdb`.`reviews`.`userid` = `ifdb`.`users`.`id`)) where ifnull(`ifdb`.`users`.`Sandbox`,0) in (0,1) and ifnull(current_timestamp() > `ifdb`.`reviews`.`embargodate`,1) group by `ifdb`.`reviews`.`rating`,`ifdb`.`reviews`.`gameid`,ifnull(`ifdb`.`reviews`.`RFlags`,0) & 2,ifnull(`ifdb`.`reviews`.`special`,`ifdb`.`reviews`.`review`) is not null) `grouped` group by `grouped`.`gameid`) `rating_counts`) `averaged` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `gamelinkstats`
--

/*!50001 DROP TABLE IF EXISTS `gamelinkstats`*/;
/*!50001 DROP VIEW IF EXISTS `gamelinkstats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `gamelinkstats` AS select `games`.`id` AS `gameid`,count(`gamelinks`.`url`) AS `numLinks`,ifnull(sum((`gamelinks`.`attrs` & 1)),0) AS `numGameLinks` from (`games` left join `gamelinks` on((`gamelinks`.`gameid` = `games`.`id`))) group by `games`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `gameratings`
--

/*!50001 DROP TABLE IF EXISTS `gameratings`*/;
/*!50001 DROP VIEW IF EXISTS `gameratings`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `gameratings` AS select `reviews`.`gameid` AS `gameid`,avg(if((ifnull(`reviews`.`RFlags`,0) & 2),NULL,`reviews`.`rating`)) AS `avgRating`,std(if((ifnull(`reviews`.`RFlags`,0) & 2),NULL,`reviews`.`rating`)) AS `stdDevRating`,count(if((ifnull(`reviews`.`RFlags`,0) & 2),NULL,`reviews`.`rating`)) AS `numRatingsInAvg`,count(`reviews`.`rating`) AS `numRatingsTotal`,count(if(isnull(`reviews`.`special`),`reviews`.`review`,NULL)) AS `numMemberReviews`,`users`.`Sandbox` AS `sandbox` from (`reviews` left join `users` on((`users`.`id` = `reviews`.`userid`))) where ifnull((now() > `reviews`.`embargodate`),1) group by `reviews`.`gameid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `userScores`
--

/*!50001 DROP TABLE IF EXISTS `userScores`*/;
/*!50001 DROP VIEW IF EXISTS `userScores`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `userScores` AS select `userscoreitems`.`userid` AS `userid`,sum(`userscoreitems`.`score`) AS `score`,(max(`userscoreitems`.`isReview`) * sum(`userscoreitems`.`score`)) AS `rankingScore`,sum(`userscoreitems`.`isReview`) AS `reviewCount` from `userscoreitems` group by `userscoreitems`.`userid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `userscoreitems`
--

/*!50001 DROP TABLE IF EXISTS `userscoreitems`*/;
/*!50001 DROP VIEW IF EXISTS `userscoreitems`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `userscoreitems` AS select `reviews`.`userid` AS `userid`,(max(if(isnull(`reviews`.`review`),10,100)) + (5 * greatest(-(100),least(100,(ifnull(sum((`reviewvotes`.`vote` = _latin1'Y' and ifnull(`users`.`sandbox`, 0) = 0)),0) - ifnull(sum((`reviewvotes`.`vote` = _latin1'N' and ifnull(`users`.`sandbox`, 0) = 0)),0)))))) AS `score`,max(if(isnull(`reviews`.`review`),0,1)) AS `isReview` from ((`reviews` left join `reviewvotes` on((`reviewvotes`.`reviewid` = `reviews`.`id`)) left join `users` on ((`users`.`id` = `reviewvotes`.`userid`))) left join `specialreviewers` on((`reviews`.`special` = `specialreviewers`.`id`))) where ((isnull(`reviews`.`special`) or (not(`specialreviewers`.`editorial`))) and ifnull((now() >= `reviews`.`embargodate`),1)) group by `reviews`.`id` union all select `reviewvotes`.`userid` AS `userid`,count(`reviewvotes`.`vote`) AS `score`,0 AS `isReview` from `reviewvotes` group by `reviewvotes`.`userid` union all select `l`.`userid` AS `userid`,if((count(`i`.`gameid`) >= 5),25,0) AS `score`,0 AS `isReview` from (`reclists` `l` left join `reclistitems` `i` on((`i`.`listid` = `l`.`id`))) group by `l`.`userid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `visreviews`
--

/*!50001 DROP TABLE IF EXISTS `visreviews`*/;
/*!50001 DROP VIEW IF EXISTS `visreviews`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50001 VIEW `visreviews` AS select `reviews`.`id` AS `id`,`reviews`.`summary` AS `summary`,`reviews`.`review` AS `review`,`reviews`.`rating` AS `rating`,`reviews`.`userid` AS `userid`,`reviews`.`createdate` AS `createdate`,`reviews`.`moddate` AS `moddate`,`reviews`.`embargodate` AS `embargodate`,`reviews`.`special` AS `special`,`reviews`.`gameid` AS `gameid`,`reviews`.`RFlags` AS `RFlags` from `reviews` where ifnull((now() > `reviews`.`embargodate`),1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
