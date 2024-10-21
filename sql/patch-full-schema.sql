/* Recreate stored procedures, not included in IFDB backup */
USE ifdb;

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

