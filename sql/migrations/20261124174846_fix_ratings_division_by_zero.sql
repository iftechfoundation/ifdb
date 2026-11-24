-- migrate:up transaction:false
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
    ) / nullif(`averaged`.`numRatingsInAvg`, 0),
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
      ) / nullif(`rating_counts`.`numRatingsInAvg`, 0) AS `avgRating`
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
    ) / nullif(`averaged`.`numRatingsInAvg`, 0),
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
      ) / nullif(`rating_counts`.`numRatingsInAvg`, 0) AS `avgRating`
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

CREATE OR REPLACE VIEW `userScores` AS
select `userscoreitems`.`userid` AS `userid`,
  greatest(0, sum(`userscoreitems`.`score`)) AS `score`,
  greatest(0,
    max(`userscoreitems`.`isReview`) * sum(`userscoreitems`.`score`)
  ) AS `rankingScore`,
  sum(`userscoreitems`.`isReview`) AS `reviewCount`
from `userscoreitems`
group by `userscoreitems`.`userid`;

SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
DELETE FROM gameRatingsSandbox0_mv;
INSERT INTO gameRatingsSandbox0_mv SELECT *, now() FROM gameRatingsSandbox0
  ON DUPLICATE KEY UPDATE updated = updated;
COMMIT;

-- migrate:down transaction:false
CREATE OR REPLACE VIEW `userScores` AS
select `userscoreitems`.`userid` AS `userid`,
  sum(`userscoreitems`.`score`) AS `score`,
(
    max(`userscoreitems`.`isReview`) * sum(`userscoreitems`.`score`)
  ) AS `rankingScore`,
  sum(`userscoreitems`.`isReview`) AS `reviewCount`
from `userscoreitems`
group by `userscoreitems`.`userid`;

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
