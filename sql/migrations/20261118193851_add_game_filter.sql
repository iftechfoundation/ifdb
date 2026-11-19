-- migrate:up transaction:false
ALTER TABLE `users` ADD COLUMN `game_filter` VARCHAR(150) DEFAULT '';

ALTER TABLE gameRatingsSandbox0_mv
  ADD COLUMN `lastReviewDate` datetime AFTER `numMemberReviews`,
  ADD KEY `lastReviewDate` (`lastReviewDate`);

CREATE OR REPLACE VIEW `gameRatingsSandbox0` AS
select `averaged`.`gameid` AS `gameid`,
  `averaged`.`rated1` AS `rated1`,
  `averaged`.`rated2` AS `rated2`,
  `averaged`.`rated3` AS `rated3`,
  `averaged`.`rated4` AS `rated4`,
  `averaged`.`rated5` AS `rated5`,
  `averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,
  `averaged`.`numRatingsTotal` AS `numRatingsTotal`,
  `averaged`.`numMemberReviews` AS `numMemberReviews`,
  (
    select max(greatest(`r`.`createdate`, ifnull(`r`.`embargodate`, `r`.`createdate`)))
    from `ifdb`.`reviews` `r`
      join `ifdb`.`users` `u` on `u`.`id` = `r`.`userid`
      left outer join `ifdb`.`specialreviewers` `s` on `s`.`id` = `r`.`special`
    where `r`.`gameid` = `averaged`.`gameid`
      and `r`.`review` is not null
      and ifnull(current_timestamp() >= `r`.`embargodate`, 1)
      and ifnull(`s`.`code`, '') <> 'external'
      and `u`.`Sandbox` in (0)
  ) AS `lastReviewDate`,
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
          ) AS `numMemberReviews`
        from (
            select count(`ifdb`.`reviews`.`id`) AS `count`,
              `ifdb`.`reviews`.`rating` AS `rating`,
              `ifdb`.`games`.`id` AS `gameid`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2 AS `omitted`,
              `ifdb`.`reviews`.`review` is not null AS `hasReview`
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
  ) `averaged`;

CREATE OR REPLACE VIEW `gameRatingsSandbox01` AS
select `averaged`.`gameid` AS `gameid`,
  `averaged`.`rated1` AS `rated1`,
  `averaged`.`rated2` AS `rated2`,
  `averaged`.`rated3` AS `rated3`,
  `averaged`.`rated4` AS `rated4`,
  `averaged`.`rated5` AS `rated5`,
  `averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,
  `averaged`.`numRatingsTotal` AS `numRatingsTotal`,
  `averaged`.`numMemberReviews` AS `numMemberReviews`,
  (
    select max(greatest(`r`.`createdate`, ifnull(`r`.`embargodate`, `r`.`createdate`)))
    from `ifdb`.`reviews` `r`
      join `ifdb`.`users` `u` on `u`.`id` = `r`.`userid`
      left outer join `ifdb`.`specialreviewers` `s` on `s`.`id` = `r`.`special`
    where `r`.`gameid` = `averaged`.`gameid`
      and `r`.`review` is not null
      and ifnull(current_timestamp() >= `r`.`embargodate`, 1)
      and ifnull(`s`.`code`, '') <> 'external'
      and `u`.`Sandbox` in (0, 1)
  ) AS `lastReviewDate`,
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
          ) AS `numMemberReviews`
        from (
            select count(`ifdb`.`reviews`.`id`) AS `count`,
              `ifdb`.`reviews`.`rating` AS `rating`,
              `ifdb`.`games`.`id` AS `gameid`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2 AS `omitted`,
              `ifdb`.`reviews`.`review` is not null AS `hasReview`
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
  ) `averaged`;

DROP PROCEDURE IF EXISTS refresh_gameRatingsSandbox0_mv;
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

TRUNCATE TABLE gameRatingsSandbox0_mv;
INSERT INTO gameRatingsSandbox0_mv SELECT *, now() FROM gameRatingsSandbox0;

CREATE OR REPLACE VIEW `recentgamenews` AS
    SELECT
        newsid AS news_id,
        sourceid AS game_id,
        created AS news_create_date
    FROM news
    WHERE source = 'G'
    AND status = 'A'
    AND newsid NOT IN (
        SELECT supersedes
        FROM news
        WHERE supersedes IS NOT NULL
    );

CREATE TABLE recentgamenews_mv (
  news_id BIGINT(20) unsigned NOT NULL,
  game_id VARCHAR(32) NOT NULL,
  news_create_date DATETIME NOT NULL,
  PRIMARY KEY (news_id),
  KEY (game_id)
);

DROP PROCEDURE IF EXISTS refresh_recentgamenews_mv;
CREATE PROCEDURE refresh_recentgamenews_mv (
    IN new_newsid BIGINT(20)
)
BEGIN
-- SELECT INTO leaves the variables unchanged when no row matches.
SET @news_id = NULL;
SET @game_id = NULL;
SET @news_create_date = NULL;
SELECT news_id, game_id, news_create_date
FROM recentgamenews
WHERE news_id = new_newsid
INTO @news_id, @game_id, @news_create_date;
IF @news_id IS NULL THEN
    DELETE FROM recentgamenews_mv WHERE news_id = new_newsid;
ELSE
    INSERT INTO recentgamenews_mv
    VALUES (@news_id, @game_id, @news_create_date)
    ON DUPLICATE KEY UPDATE
        news_id = @news_id,
        game_id = @game_id,
        news_create_date = @news_create_date;
END IF;
END;

CREATE TRIGGER recentgamenews_insert
AFTER INSERT ON news FOR EACH ROW
BEGIN
    CALL refresh_recentgamenews_mv(NEW.newsid);
    CALL refresh_recentgamenews_mv(NEW.supersedes);
END;

CREATE TRIGGER recentgamenews_update
AFTER UPDATE ON news FOR EACH ROW
BEGIN
    CALL refresh_recentgamenews_mv(NEW.newsid);
    CALL refresh_recentgamenews_mv(NEW.supersedes);
    CALL refresh_recentgamenews_mv(OLD.supersedes);
END;

CREATE TRIGGER recentgamenews_delete
AFTER DELETE ON news FOR EACH ROW
BEGIN
    CALL refresh_recentgamenews_mv(OLD.newsid);
    CALL refresh_recentgamenews_mv(OLD.supersedes);
END;

INSERT IGNORE INTO recentgamenews_mv SELECT * FROM recentgamenews;

-- migrate:down transaction:false
DROP TRIGGER IF EXISTS recentgamenews_insert;
DROP TRIGGER IF EXISTS recentgamenews_update;
DROP TRIGGER IF EXISTS recentgamenews_delete;
DROP PROCEDURE IF EXISTS refresh_recentgamenews_mv;
DROP TABLE IF EXISTS recentgamenews_mv;
DROP VIEW IF EXISTS recentgamenews;

ALTER TABLE gameRatingsSandbox0_mv
  DROP KEY `lastReviewDate`,
  DROP COLUMN `lastReviewDate`;

CREATE OR REPLACE VIEW `gameRatingsSandbox0` AS
select `averaged`.`gameid` AS `gameid`,
  `averaged`.`rated1` AS `rated1`,
  `averaged`.`rated2` AS `rated2`,
  `averaged`.`rated3` AS `rated3`,
  `averaged`.`rated4` AS `rated4`,
  `averaged`.`rated5` AS `rated5`,
  `averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,
  `averaged`.`numRatingsTotal` AS `numRatingsTotal`,
  `averaged`.`numMemberReviews` AS `numMemberReviews`,
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
          ) AS `numMemberReviews`
        from (
            select count(`ifdb`.`reviews`.`id`) AS `count`,
              `ifdb`.`reviews`.`rating` AS `rating`,
              `ifdb`.`games`.`id` AS `gameid`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2 AS `omitted`,
              `ifdb`.`reviews`.`review` is not null AS `hasReview`
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
  ) `averaged`;

CREATE OR REPLACE VIEW `gameRatingsSandbox01` AS
select `averaged`.`gameid` AS `gameid`,
  `averaged`.`rated1` AS `rated1`,
  `averaged`.`rated2` AS `rated2`,
  `averaged`.`rated3` AS `rated3`,
  `averaged`.`rated4` AS `rated4`,
  `averaged`.`rated5` AS `rated5`,
  `averaged`.`numRatingsInAvg` AS `numRatingsInAvg`,
  `averaged`.`numRatingsTotal` AS `numRatingsTotal`,
  `averaged`.`numMemberReviews` AS `numMemberReviews`,
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
          ) AS `numMemberReviews`
        from (
            select count(`ifdb`.`reviews`.`id`) AS `count`,
              `ifdb`.`reviews`.`rating` AS `rating`,
              `ifdb`.`games`.`id` AS `gameid`,
              ifnull(`ifdb`.`reviews`.`RFlags`, 0) & 2 AS `omitted`,
              `ifdb`.`reviews`.`review` is not null AS `hasReview`
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
  ) `averaged`;

DROP PROCEDURE IF EXISTS refresh_gameRatingsSandbox0_mv;
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
    avgRating = @avgRating,
    stdDevRating = @stdDevRating,
    starsort = @starsort,
    updated = now();
END IF;
END;

ALTER TABLE `users` DROP COLUMN `game_filter`;
