-- migrate:up
UPDATE games_history
SET deltas = REPLACE(deltas, 's:9:"published";s:9:" 01:00:00"', 's:9:"published";s:0:""')
WHERE deltas LIKE '%s:9:"published";s:9:" 01:00:00"%';

-- migrate:down
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Irreversible: the old deltas are gone';
