USE ifdb;

-- use this script for pending changes to the production DB schema

alter table games add fulltext key `author` (`author`);

alter table reviews
    add column `embargopastdate` date DEFAULT NULL,
    add key `embargodate` (`embargodate`),
    add key `embargopastdate` (`embargopastdate`)
;

update reviews r1
join reviews r2 using (id)
set r1.embargopastdate = now(),
    r1.moddate = r2.moddate
where
    r1.embargodate < now()
    and (
        r1.embargopastdate is null
        or r1.embargopastdate < r1.embargodate
    )
;

drop table if exists gameRatingsSandbox0_mv;
create table gameRatingsSandbox0_mv (
  `gameid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `rated1` int unsigned,
  `rated2` int unsigned,
  `rated3` int unsigned,
  `rated4` int unsigned,
  `rated5` int unsigned,
  `numRatingsInAvg` int unsigned,
  `numRatingsTotal` int unsigned,
  `numMemberReviews` int unsigned,
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
  KEY `starsort` (`starsort`)
) ENGINE = MyISAM DEFAULT CHARSET = latin1 COLLATE = latin1_german2_ci;

lock tables gameRatingsSandbox0_mv write, gameRatingsSandbox0 read;
truncate table gameRatingsSandbox0_mv;
insert into gameRatingsSandbox0_mv select *, now() from gameRatingsSandbox0;
unlock tables;

DROP PROCEDURE IF EXISTS refresh_gameRatingsSandbox0_mv;
DELIMITER $$

CREATE PROCEDURE refresh_gameRatingsSandbox0_mv (
    IN new_gameid varchar(32) COLLATE latin1_german2_ci
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
