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
