USE ifdb;

ALTER TABLE `users` ADD COLUMN `game_filter` VARCHAR(150) DEFAULT '';

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
