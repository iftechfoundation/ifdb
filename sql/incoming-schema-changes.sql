USE ifdb;

-- use this script for pending changes to the production DB schema



-- Add column for game search filter to the users table

ALTER TABLE `users` ADD COLUMN `game_filter` VARCHAR(150) DEFAULT '';





-- Add a view to track the news items for each game.
-- "G" in the news table means the news is about a game.
-- Do not include versions of news items that have been 
-- superseded by later edits.

CREATE VIEW `recentgamenews` AS 
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
    )
    ORDER BY news_id DESC;

  

-- Create a materialized view to store the data from the recentgamenews view

CREATE TABLE recentgamenews_mv (
  news_id BIGINT(20) unsigned NOT NULL,
  game_id VARCHAR(32) NOT NULL,
  news_create_date DATETIME NOT NULL,
  PRIMARY KEY (news_id),
  KEY (game_id)
);



-- Populate the recentgamenews_mv materialized view from the recentgamenews view

lock tables recentgamenews_mv write, recentgamenews read;
truncate table recentgamenews_mv;
insert into recentgamenews_mv select * from recentgamenews;
unlock tables;



-- Procedure to update one row of the recentgamenews_mv materialized view

DROP PROCEDURE IF EXISTS refresh_recentgamenews_mv;
DELIMITER $$

CREATE PROCEDURE refresh_recentgamenews_mv (
    IN new_newsid BIGINT(20)
)
BEGIN
select *
from recentgamenews
where news_id = new_newsid
into @news_id,
     @game_id,
     @news_create_date;
if @news_id is null then
    delete from recentgamenews_mv where news_id = new_news_id;
else
insert into recentgamenews_mv
values (
        @news_id,
        @game_id,
        @news_create_date
    ) on duplicate key
update news_id = @news_id,
    game_id = @game_id,
    news_create_date = @news_create_date;
END IF;
END;
$$

DELIMITER ;



-- Create triggers so that when a news item for a game is added to the news table,
-- the latest news date and the latest news id for that game will automatically
-- be updated in the recentgamenews_mv materialized view. [Question: How do I limit this to just game news?]

CREATE TRIGGER recentgamenews_insert
AFTER INSERT ON news FOR EACH ROW
call refresh_recentgamenews_mv(NEW.newsid);


CREATE TRIGGER recentgamenews_update
AFTER UPDATE ON news FOR EACH ROW
call refresh_recentgamenews_mv(NEW.newsid);


CREATE TRIGGER recentgamenews_delete
AFTER DELETE ON news FOR EACH ROW
call refresh_recentgamenews_mv(OLD.newsid);
