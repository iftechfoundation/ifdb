USE ifdb;

-- use this script for pending changes to the production DB schema


-- Fix invalid published dates in games_history deltas (issue #1438).
-- parseDateOrYearVal used to turn empty dates into " 01:00:00"; replace with "".
UPDATE games_history
SET deltas = REPLACE(deltas, 's:9:"published";s:9:" 01:00:00"', 's:9:"published";s:0:""')
WHERE deltas LIKE '%s:9:"published";s:9:" 01:00:00"%';

