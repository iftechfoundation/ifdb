USE ifdb;

DROP TABLE IF EXISTS `global_settings`;
CREATE TABLE `global_settings` (
  `setting_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_name` VARCHAR(255) NOT NULL,
  `current_value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (setting_id),
  UNIQUE KEY `setting_name` (`setting_name`)
);
insert into global_settings (setting_name, current_value)
values ('account approval', 'normal');

-- Fix invalid published dates in games_history deltas (issue #1438).
-- parseDateOrYearVal used to turn empty dates into " 01:00:00"; replace with "".
UPDATE games_history
SET deltas = REPLACE(deltas, 's:9:"published";s:9:" 01:00:00"', 's:9:"published";s:0:""')
WHERE deltas LIKE '%s:9:"published";s:9:" 01:00:00"%';
