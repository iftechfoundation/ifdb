USE ifdb;

DROP TABLE IF EXISTS `audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit` (
  `userid` varchar(32) NOT NULL DEFAULT '',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `action` mediumtext NOT NULL
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `userfilters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userfilters` (
  `userid` char(32) NOT NULL,
  `targetuserid` char(32) NOT NULL,
  `filtertype` char(1) NOT NULL,
  KEY `userid` (`userid`),
  KEY `targetuserid` (`targetuserid`)
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `users`
    ADD COLUMN `email` varchar(255) NOT NULL DEFAULT '',
    ADD COLUMN `emailflags` tinyint(2) NOT NULL DEFAULT '3',
    ADD COLUMN `profilestatus` varchar(1) DEFAULT NULL,
    ADD COLUMN `password` varchar(40) NOT NULL DEFAULT '',
    ADD COLUMN `pswsalt` varchar(32) NOT NULL DEFAULT '',
    ADD COLUMN `activationcode` varchar(40) DEFAULT NULL,
    ADD COLUMN `acctstatus` char(1) NOT NULL DEFAULT 'A',
    ADD COLUMN `lastlogin` datetime DEFAULT NULL,
    ADD COLUMN `privileges` varchar(32) NOT NULL DEFAULT '''''',
    ADD COLUMN `defaultos` int(11) unsigned DEFAULT NULL,
    ADD COLUMN `defaultosvsn` int(11) unsigned DEFAULT NULL,
    ADD COLUMN `noexedownloads` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `publiclists` varchar(10) DEFAULT NULL,
    ADD COLUMN `mirrorid` int(11) unsigned NOT NULL DEFAULT '100',
    ADD COLUMN `stylesheetid` bigint(20) DEFAULT NULL,
    ADD COLUMN `offsite_display` char(1) NOT NULL DEFAULT 'A',
    ADD COLUMN `accessibility` tinyint(1) DEFAULT NULL,
    ADD COLUMN `caughtupdate` datetime DEFAULT NULL,
    ADD COLUMN `remarks` mediumtext,
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
  `userid` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logins`
--

DROP TABLE IF EXISTS `logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logins` (
  `uid` varchar(32) NOT NULL DEFAULT '',
  `ip` varchar(16) NOT NULL DEFAULT '',
  `when` datetime NOT NULL,
  KEY `uid` (`uid`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nonces`
--

DROP TABLE IF EXISTS `nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nonces` (
  `nonceid` varchar(256) NOT NULL,
  `hash` varchar(40) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `nonceid` (`nonceid`)
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `osprivs`
--

DROP TABLE IF EXISTS `osprivs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `osprivs` (
  `osid` int(11) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `persistentsessions`
--

DROP TABLE IF EXISTS `persistentsessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `persistentsessions` (
  `id` varchar(64) NOT NULL DEFAULT '',
  `userid` varchar(32) NOT NULL DEFAULT '',
  `lastlogin` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `privileges`
--

DROP TABLE IF EXISTS `privileges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privileges` (
  `code` char(1) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT ''
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reviewflags`
--

DROP TABLE IF EXISTS `reviewflags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviewflags` (
  `reviewid` bigint(20) NOT NULL,
  `flagger` varchar(32) NOT NULL,
  `flagtype` char(1) NOT NULL,
  `notes` mediumtext,
  `created` datetime NOT NULL,
  KEY `reviewid` (`reviewid`),
  KEY `flagger` (`flagger`)
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `reviewvotes`
  ADD COLUMN `userid` char(32) NOT NULL DEFAULT '',
  ADD KEY `userid` (`userid`)
;

--
-- Table structure for table `stylepics`
--

DROP TABLE IF EXISTS `stylepics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stylepics` (
  `userid` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `picture` varchar(64) NOT NULL DEFAULT '',
  `desc` mediumtext,
  `picturebytes` int(11) DEFAULT NULL,
  PRIMARY KEY (`userid`,`name`)
) ENGINE=MyISAM;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `ucomments`
  ADD COLUMN `private` varchar(32) DEFAULT NULL
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
  `userid` char(32) NOT NULL,
  `targetuserid` char(32) NOT NULL,
  `filtertype` char(1) NOT NULL,
  KEY `userid` (`userid`),
  KEY `targetuserid` (`targetuserid`)
) ENGINE=MyISAM;
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
/*!50001 VIEW `gameRatings` AS
select `reviews`.`gameid` AS `gameid`,
  avg(
    if((`reviews`.`RFlags` & 2), NULL, `reviews`.`rating`)
  ) AS `avgRating`,
  std(
    if((`reviews`.`RFlags` & 2), NULL, `reviews`.`rating`)
  ) AS `stdDevRating`,
  count(
    if((`reviews`.`RFlags` & 2), NULL, `reviews`.`rating`)
  ) AS `numRatingsInAvg`,
  count(`reviews`.`rating`) AS `numRatingsTotal`,
  count(
    if(
      isnull(`reviews`.`special`),
      `reviews`.`review`,
      NULL
    )
  ) AS `numMemberReviews`
from `reviews`
where ifnull((now() > `reviews`.`embargodate`), 1)
group by `reviews`.`gameid`
*/;
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
/*!50001 VIEW `gameRatingsSandbox0` AS
select `averaged`.`gameid` AS `gameid`,
  `averaged`.`rated1` AS `rated1`,
  `averaged`.`rated2` AS `rated2`,
  `averaged`.`rated3` AS `rated3`,
  `averaged`.`rated4` AS `rated4`,
  `averaged`.`rated5` AS `rated5`,
  `averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,
  `averaged`.`numRatingsTotal` AS `numRatingsTotal`,
  `averaged`.`numMemberReviews` AS `numMemberReviews`,
  `averaged`.`lastReviewDate` AS `lastReviewDate`,
  `averaged`.`avgRating` AS `avgRating`,
  pow(
    (
      pow(1 - `averaged`.`avgRating`, 2) * `averaged`.`rated1` 
      + pow(2 - `averaged`.`avgRating`, 2) * `averaged`.`rated2` 
      + pow(3 - `averaged`.`avgRating`, 2) * `averaged`.`rated3`
      + pow(4 - `averaged`.`avgRating`, 2) * `averaged`.`rated4`
      + pow(5 - `averaged`.`avgRating`, 2) * `averaged`.`rated5`
    ) / `averaged`.`numRatingsInAvg`,
    0.5
  ) AS `stdDevRating`,
(
    5 * (`averaged`.`rated5` + 1)
    + 4 * (`averaged`.`rated4` + 1)
    + 3 * (`averaged`.`rated3` + 1)
    + 2 * (`averaged`.`rated2` + 1)
    + 1 * (`averaged`.`rated1` + 1)
  ) / (5 + `averaged`.`numRatingsInAvg`) - 1.65 * sqrt(
    (
      (
        25 * (`averaged`.`rated5` + 1)
        + 16 * (`averaged`.`rated4` + 1)
        + 9 * (`averaged`.`rated3` + 1)
        + 4 * (`averaged`.`rated2` + 1)
        + 1 * (`averaged`.`rated1` + 1)
      ) / (5 + `averaged`.`numRatingsInAvg`) - pow(
        (
          5 * (`averaged`.`rated5` + 1)
          + 4 * (`averaged`.`rated4` + 1)
          + 3 * (`averaged`.`rated3` + 1)
          + 2 * (`averaged`.`rated2` + 1)
          + 1 * (`averaged`.`rated1` + 1)
        ) / (5 + `averaged`.`numRatingsInAvg`),
        2
      )
    ) / (6 + `averaged`.`numRatingsInAvg`)
  ) AS `starsort`
from (
    select `rating_counts`.`gameid` AS `gameid`,
      `rating_counts`.`rated1` AS `rated1`,
      `rating_counts`.`rated2` AS `rated2`,
      `rating_counts`.`rated3` AS `rated3`,
      `rating_counts`.`rated4` AS `rated4`,
      `rating_counts`.`rated5` AS `rated5`,
      `rating_counts`.`numRatingsInAvg` AS `numRatingsInAvg`,
      `rating_counts`.`numRatingsTotal` AS `numRatingsTotal`,
      `rating_counts`.`numMemberReviews` AS `numMemberReviews`,
      `rating_counts`.`lastReviewDate` AS `lastReviewDate`,
(
        `rating_counts`.`rated1`
        + `rating_counts`.`rated2` * 2
        + `rating_counts`.`rated3` * 3
        + `rating_counts`.`rated4` * 4
        + `rating_counts`.`rated5` * 5
      ) / `rating_counts`.`numRatingsInAvg` AS `avgRating`
    from (
        select `grouped`.`gameid` AS `gameid`,
          sum(
            case
              when `grouped`.`rating` = 1
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated1`,
          sum(
            case
              when `grouped`.`rating` = 2
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated2`,
          sum(
            case
              when `grouped`.`rating` = 3
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated3`,
          sum(
            case
              when `grouped`.`rating` = 4
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated4`,
          sum(
            case
              when `grouped`.`rating` = 5
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated5`,
          sum(
            case
              when `grouped`.`rating` in (1, 2, 3, 4, 5)
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `numRatingsInAvg`,
          sum(
            case
              when `grouped`.`rating` in (1, 2, 3, 4, 5) then `grouped`.`count`
              else 0
            end
          ) AS `numRatingsTotal`,
          sum(
            case
              when `grouped`.`hasReview` then `grouped`.`count`
              else 0
            end
          ) AS `numMemberReviews`,
          max(
            case
              when `grouped`.`hasReview` then `grouped`.`lastRatingOrReviewDate`
              else null
            end
          ) AS `lastReviewDate`
        from (
            select count(`ifdb`.`reviews`.`id`) AS `count`,
              `ifdb`.`reviews`.`rating` AS `rating`,
              `ifdb`.`games`.`id` AS `gameid`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2 AS `omitted`,
              `ifdb`.`reviews`.`review` is not null AS `hasReview`,
              max(ifnull(embargodate, createdate)) AS `lastRatingOrReviewDate`
            from (
                `ifdb`.`games`
                left outer join `ifdb`.`reviews` on (
                  `ifdb`.`games`.`id` = `ifdb`.`reviews`.`gameid`
                  and ifnull(
                    current_timestamp() > `ifdb`.`reviews`.`embargodate`,
                    1
                  )
                  and `ifdb`.`reviews`.`special` is null
                  and `ifdb`.`reviews`.`userid` not in (select `ifdb`.`users`.`id` from `ifdb`.`users` where `ifdb`.`users`.`Sandbox` = 1)
                )
              )
            group by `ifdb`.`reviews`.`rating`,
              `ifdb`.`games`.`id`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2,
              ifnull(
                `ifdb`.`reviews`.`special`,
                `ifdb`.`reviews`.`review`
              ) is not null
          ) `grouped`
        group by `grouped`.`gameid`
      ) `rating_counts`
  ) `averaged`
*/;
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
/*!50001 VIEW `gameRatingsSandbox01` AS
select `averaged`.`gameid` AS `gameid`,
  `averaged`.`rated1` AS `rated1`,
  `averaged`.`rated2` AS `rated2`,
  `averaged`.`rated3` AS `rated3`,
  `averaged`.`rated4` AS `rated4`,
  `averaged`.`rated5` AS `rated5`,
  `averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,
  `averaged`.`numRatingsTotal` AS `numRatingsTotal`,
  `averaged`.`numMemberReviews` AS `numMemberReviews`,
  `averaged`.`lastReviewDate` AS `lastReviewDate`,
  `averaged`.`avgRating` AS `avgRating`,
  pow(
    (
      pow(1 - `averaged`.`avgRating`, 2) * `averaged`.`rated1` 
      + pow(2 - `averaged`.`avgRating`, 2) * `averaged`.`rated2` 
      + pow(3 - `averaged`.`avgRating`, 2) * `averaged`.`rated3`
      + pow(4 - `averaged`.`avgRating`, 2) * `averaged`.`rated4`
      + pow(5 - `averaged`.`avgRating`, 2) * `averaged`.`rated5`
    ) / `averaged`.`numRatingsInAvg`,
    0.5
  ) AS `stdDevRating`,
(
    5 * (`averaged`.`rated5` + 1)
    + 4 * (`averaged`.`rated4` + 1)
    + 3 * (`averaged`.`rated3` + 1)
    + 2 * (`averaged`.`rated2` + 1)
    + 1 * (`averaged`.`rated1` + 1)
  ) / (5 + `averaged`.`numRatingsInAvg`) - 1.65 * sqrt(
    (
      (
        25 * (`averaged`.`rated5` + 1)
        + 16 * (`averaged`.`rated4` + 1)
        + 9 * (`averaged`.`rated3` + 1)
        + 4 * (`averaged`.`rated2` + 1)
        + 1 * (`averaged`.`rated1` + 1)
      ) / (5 + `averaged`.`numRatingsInAvg`) - pow(
        (
          5 * (`averaged`.`rated5` + 1)
          + 4 * (`averaged`.`rated4` + 1)
          + 3 * (`averaged`.`rated3` + 1)
          + 2 * (`averaged`.`rated2` + 1)
          + 1 * (`averaged`.`rated1` + 1)
        ) / (5 + `averaged`.`numRatingsInAvg`),
        2
      )
    ) / (6 + `averaged`.`numRatingsInAvg`)
  ) AS `starsort`
from (
    select `rating_counts`.`gameid` AS `gameid`,
      `rating_counts`.`rated1` AS `rated1`,
      `rating_counts`.`rated2` AS `rated2`,
      `rating_counts`.`rated3` AS `rated3`,
      `rating_counts`.`rated4` AS `rated4`,
      `rating_counts`.`rated5` AS `rated5`,
      `rating_counts`.`numRatingsInAvg` AS `numRatingsInAvg`,
      `rating_counts`.`numRatingsTotal` AS `numRatingsTotal`,
      `rating_counts`.`numMemberReviews` AS `numMemberReviews`,
      `rating_counts`.`lastReviewDate` AS `lastReviewDate`,
(
        `rating_counts`.`rated1`
        + `rating_counts`.`rated2` * 2
        + `rating_counts`.`rated3` * 3
        + `rating_counts`.`rated4` * 4
        + `rating_counts`.`rated5` * 5
      ) / `rating_counts`.`numRatingsInAvg` AS `avgRating`
    from (
        select `grouped`.`gameid` AS `gameid`,
          sum(
            case
              when `grouped`.`rating` = 1
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated1`,
          sum(
            case
              when `grouped`.`rating` = 2
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated2`,
          sum(
            case
              when `grouped`.`rating` = 3
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated3`,
          sum(
            case
              when `grouped`.`rating` = 4
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated4`,
          sum(
            case
              when `grouped`.`rating` = 5
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `rated5`,
          sum(
            case
              when `grouped`.`rating` in (1, 2, 3, 4, 5)
              and `grouped`.`omitted` = 0 then `grouped`.`count`
              else 0
            end
          ) AS `numRatingsInAvg`,
          sum(
            case
              when `grouped`.`rating` in (1, 2, 3, 4, 5) then `grouped`.`count`
              else 0
            end
          ) AS `numRatingsTotal`,
          sum(
            case
              when `grouped`.`hasReview` then `grouped`.`count`
              else 0
            end
          ) AS `numMemberReviews`,
          max(
            case
              when `grouped`.`hasReview` then `grouped`.`lastRatingOrReviewDate`
              else null
            end
          ) AS `lastReviewDate`
        from (
            select count(`ifdb`.`reviews`.`id`) AS `count`,
              `ifdb`.`reviews`.`rating` AS `rating`,
              `ifdb`.`games`.`id` AS `gameid`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2 AS `omitted`,
              `ifdb`.`reviews`.`review` is not null AS `hasReview`,
              max(ifnull(embargodate, createdate)) AS `lastRatingOrReviewDate`
            from (
                `ifdb`.`games`
                left outer join `ifdb`.`reviews` on (
                  `ifdb`.`games`.`id` = `ifdb`.`reviews`.`gameid`
                  and ifnull(
                    current_timestamp() > `ifdb`.`reviews`.`embargodate`,
                    1
                  )
                  and `ifdb`.`reviews`.`special` is null
                )
              )
            group by `ifdb`.`reviews`.`rating`,
              `ifdb`.`games`.`id`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2,
              ifnull(
                `ifdb`.`reviews`.`special`,
                `ifdb`.`reviews`.`review`
              ) is not null
          ) `grouped`
        group by `grouped`.`gameid`
      ) `rating_counts`
  ) `averaged`
*/;
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
/*!50001 VIEW `gamelinkstats` AS
select `games`.`id` AS `gameid`,
  count(`gamelinks`.`url`) AS `numLinks`,
  ifnull(sum((`gamelinks`.`attrs` & 1)), 0) AS `numGameLinks`
from (
    `games`
    left join `gamelinks` on((`gamelinks`.`gameid` = `games`.`id`))
  )
group by `games`.`id`
*/;
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
/*!50001 VIEW `gameratings` AS
select `reviews`.`gameid` AS `gameid`,
  avg(
    if(
      (ifnull(`reviews`.`RFlags`, 0) & 2),
      NULL,
      `reviews`.`rating`
    )
  ) AS `avgRating`,
  std(
    if(
      (ifnull(`reviews`.`RFlags`, 0) & 2),
      NULL,
      `reviews`.`rating`
    )
  ) AS `stdDevRating`,
  count(
    if(
      (ifnull(`reviews`.`RFlags`, 0) & 2),
      NULL,
      `reviews`.`rating`
    )
  ) AS `numRatingsInAvg`,
  count(`reviews`.`rating`) AS `numRatingsTotal`,
  count(
    if(
      isnull(`reviews`.`special`),
      `reviews`.`review`,
      NULL
    )
  ) AS `numMemberReviews`,
  `users`.`Sandbox` AS `sandbox`
from (
    `reviews`
    left join `users` on((`users`.`id` = `reviews`.`userid`))
  )
where ifnull((now() > `reviews`.`embargodate`), 1)
group by `reviews`.`gameid`
*/;
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
/*!50001 VIEW `userScores` AS
select `userscoreitems`.`userid` AS `userid`,
  sum(`userscoreitems`.`score`) AS `score`,
(
    max(`userscoreitems`.`isReview`) * sum(`userscoreitems`.`score`)
  ) AS `rankingScore`,
  sum(`userscoreitems`.`isReview`) AS `reviewCount`
from `userscoreitems`
group by `userscoreitems`.`userid`
*/;
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
/*!50001 VIEW `userscoreitems` AS
select `reviews`.`userid` AS `userid`,
(
    max(if(isnull(`reviews`.`review`), 10, 100)) + (
      5 * greatest(
        -(100),
        least(
          100,
(
            ifnull(
              sum(
                (
                  `reviewvotes`.`vote` = 'Y'
                  and ifnull(`users`.`sandbox`, 0) = 0
                )
              ),
              0
            ) - ifnull(
              sum(
                (
                  `reviewvotes`.`vote` = 'N'
                  and ifnull(`users`.`sandbox`, 0) = 0
                )
              ),
              0
            )
          )
        )
      )
    )
  ) AS `score`,
  max(if(isnull(`reviews`.`review`), 0, 1)) AS `isReview`
from (
    (
      `reviews`
      left join `reviewvotes` on((`reviewvotes`.`reviewid` = `reviews`.`id`))
      left join `users` on ((`users`.`id` = `reviewvotes`.`userid`))
    )
    left join `specialreviewers` on((`reviews`.`special` = `specialreviewers`.`id`))
  )
where (
    (
      isnull(`reviews`.`special`)
      or (not(`specialreviewers`.`editorial`))
    )
    and ifnull((now() >= `reviews`.`embargodate`), 1)
  )
group by `reviews`.`id`
union all
select `reviewvotes`.`userid` AS `userid`,
  count(`reviewvotes`.`vote`) AS `score`,
  0 AS `isReview`
from `reviewvotes`
group by `reviewvotes`.`userid`
union all
select `l`.`userid` AS `userid`,
  if((count(`i`.`gameid`) >= 5), 25, 0) AS `score`,
  0 AS `isReview`
from (
    `reclists` `l`
    left join `reclistitems` `i` on((`i`.`listid` = `l`.`id`))
  )
group by `l`.`userid`
*/;
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
/*!50001 VIEW `visreviews` AS
select `reviews`.`id` AS `id`,
  `reviews`.`summary` AS `summary`,
  `reviews`.`review` AS `review`,
  `reviews`.`rating` AS `rating`,
  `reviews`.`userid` AS `userid`,
  `reviews`.`createdate` AS `createdate`,
  `reviews`.`moddate` AS `moddate`,
  `reviews`.`embargodate` AS `embargodate`,
  `reviews`.`special` AS `special`,
  `reviews`.`gameid` AS `gameid`,
  `reviews`.`RFlags` AS `RFlags`
from `reviews`
where ifnull((now() > `reviews`.`embargodate`), 1)
*/;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

drop table if exists userScores_mv;
create table userScores_mv (
    `userid` varchar(32) NOT NULL DEFAULT '',
    `score` int unsigned,
    `rankingScore` int unsigned,
    `reviewCount` int unsigned,
    `updated` date,
    PRIMARY KEY (`userid`),
    KEY `score` (`score`),
    KEY `rankingScore` (`rankingScore`),
    KEY `reviewCount` (`reviewCount`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

lock tables userScores_mv write, userScores read;
truncate table userScores_mv;
insert into userScores_mv select *, now() from userScores;
unlock tables;

drop table if exists gameRatingsSandbox0_mv;
create table gameRatingsSandbox0_mv (
  `gameid` varchar(32) NOT NULL DEFAULT '',
  `rated1` int unsigned,
  `rated2` int unsigned,
  `rated3` int unsigned,
  `rated4` int unsigned,
  `rated5` int unsigned,
  `numRatingsInAvg` int unsigned,
  `numRatingsTotal` int unsigned,
  `numMemberReviews` int unsigned,
  `lastReviewDate` datetime NOT NULL,
  `avgRating` double,
  `stdDevRating` double,
  `starsort` double,
  `updated` date,
  PRIMARY KEY (`gameid`),
  KEY `numRatingsInAvg` (`numRatingsInAvg`),
  KEY `numRatingsTotal` (`numRatingsTotal`),
  KEY `numMemberReviews` (`numMemberReviews`),
  KEY `avgRating` (`avgRating`),
  KEY `stdDevRating` (`stdDevRating`),
  KEY `starsort` (`starsort`),
  KEY `lastReviewDate` (`lastReviewDate`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

lock tables gameRatingsSandbox0_mv write, gameRatingsSandbox0 read;
truncate table gameRatingsSandbox0_mv;
insert into gameRatingsSandbox0_mv select *, now() from gameRatingsSandbox0;
unlock tables;

DROP PROCEDURE IF EXISTS refresh_gameRatingsSandbox0_mv;
DELIMITER $$

CREATE PROCEDURE refresh_gameRatingsSandbox0_mv (
    IN new_gameid varchar(32)
)
BEGIN
select *
from gameRatingsSandbox0
where gameid = new_gameid into @gameid,
    @rated1,
    @rated2,
    @rated3,
    @rated4,
    @rated5,
    @numRatingsInAvg,
    @numRatingsTotal,
    @numMemberReviews,
    @lastReviewDate,
    @avgRating,
    @stdDevRating,
    @starsort;
if @gameid is null then
    delete from gameRatingsSandbox0_mv where gameid = new_gameid;
else
insert into gameRatingsSandbox0_mv
values (
        @gameid,
        @rated1,
        @rated2,
        @rated3,
        @rated4,
        @rated5,
        @numRatingsInAvg,
        @numRatingsTotal,
        @numMemberReviews,
        @lastReviewDate,
        @avgRating,
        @stdDevRating,
        @starsort,
        now()
    ) on duplicate key
update gameid = @gameid,
    rated1 = @rated1,
    rated2 = @rated2,
    rated3 = @rated3,
    rated4 = @rated4,
    rated5 = @rated5,
    numRatingsInAvg = @numRatingsInAvg,
    numRatingsTotal = @numRatingsTotal,
    numMemberReviews = @numMemberReviews,
    lastReviewDate = @lastReviewDate,
    avgRating = @avgRating,
    stdDevRating = @stdDevRating,
    starsort = @starsort,
    updated = now();
END IF;
END;
$$

DELIMITER ;

CREATE TRIGGER reviews_insert
AFTER INSERT ON reviews FOR EACH ROW
call refresh_gameRatingsSandbox0_mv(NEW.gameid);

CREATE TRIGGER reviews_update
AFTER UPDATE ON reviews FOR EACH ROW
call refresh_gameRatingsSandbox0_mv(NEW.gameid);

CREATE TRIGGER reviews_delete
AFTER DELETE ON reviews FOR EACH ROW
call refresh_gameRatingsSandbox0_mv(OLD.gameid);

-- The roundMedianTime function takes the exact median time in minutes
-- and rounds it. If the time is over an hour, round to the nearest 5 minutes.
-- Otherwise, round to the nearest minute.

DELIMITER $$

CREATE FUNCTION roundMedianTime(
    exact_median_in_minutes DECIMAL(5)
)
RETURNS INT(5)
DETERMINISTIC
BEGIN
    DECLARE rounded_median_in_minutes INT(5);
    IF exact_median_in_minutes > 60 THEN
        SET rounded_median_in_minutes = (round(exact_median_in_minutes/5))*5;
    ELSE
        SET rounded_median_in_minutes = round(exact_median_in_minutes);
    END IF;
    RETURN (rounded_median_in_minutes);
END $$

DELIMITER ;


-- View to calculate the estimated play time (the rounded median time) of each game
CREATE VIEW `gametimes` AS 
    SELECT 
        DISTINCT gameid, 
        roundMedianTime( median(time_in_minutes) OVER (PARTITION BY gameid) )  as `rounded_median_time_in_minutes`
    FROM playertimes;


-- Create a materialized view to store the data from the gametimes view
CREATE TABLE gametimes_mv (
  gameid VARCHAR(32) NOT NULL,
  rounded_median_time_in_minutes INT(5) unsigned not null,
  PRIMARY KEY (gameid),
  KEY (rounded_median_time_in_minutes)
);



-- Populate the gametimes_mv materialized view from the gametimes view
lock tables gametimes_mv write, gametimes read;
truncate table gametimes_mv;
insert into gametimes_mv select * from gametimes;
unlock tables;


-- Procedure to update one row of the gametimes_mv materialized view
DROP PROCEDURE IF EXISTS refresh_gametimes_mv;
DELIMITER $$

CREATE PROCEDURE refresh_gametimes_mv (
    IN new_gameid varchar(32)
)
BEGIN
select *
from gametimes
where gameid = new_gameid
into @gameid,
    @rounded_median_time_in_minutes;
if @gameid is null then
    delete from gametimes_mv where gameid = new_gameid;
else
insert into gametimes_mv
values (
        @gameid,
        @rounded_median_time_in_minutes
    ) on duplicate key
update gameid = @gameid,
    rounded_median_time_in_minutes = @rounded_median_time_in_minutes;
END IF;
END;
$$

DELIMITER ;


-- Create triggers so that when an individual player time in the playertimes table is
-- updated, the rounded median time for that game (in the gametimes_mv materialized view)
-- will also be updated.
CREATE TRIGGER playertime_insert
AFTER INSERT ON playertimes FOR EACH ROW
call refresh_gametimes_mv(NEW.gameid);

CREATE TRIGGER playertime_update
AFTER UPDATE ON playertimes FOR EACH ROW
call refresh_gametimes_mv(NEW.gameid);

CREATE TRIGGER playertime_delete
AFTER DELETE ON playertimes FOR EACH ROW
call refresh_gametimes_mv(OLD.gameid);
