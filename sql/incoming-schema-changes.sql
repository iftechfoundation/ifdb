USE ifdb;

-- use this script for pending changes to the production DB schema


DROP TABLE IF EXISTS `global_settings`;
CREATE TABLE `global_settings` ( 
  `setting_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_name` VARCHAR(255) NOT NULL,
  `current_value` VARCHAR(255) NOT NULL,
  PRIMARY KEY (setting_id),
  UNIQUE KEY `setting_name` (`setting_name`)
);

insert into global_settings (setting_name, current_value)
values
  ('account approval', 'normal');




DROP TABLE IF EXISTS `suspicious_domains`;
CREATE TABLE `suspicious_domains` ( 
  `suspicious_domain_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain` VARCHAR(255) NOT NULL,
  `suspicion_level` TINYINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (suspicious_domain_id),
  UNIQUE KEY `domain` (`domain`)
);

insert into suspicious_domains (domain, suspicion_level)
values
  ('gmail.com', '1'),
  ('googlemail.com', '1'),
  ('ymail.com', '1'),
  ('hotmail.com', '1'),
  ('live.com', '1'),
  ('aol.com', '1'),
  ('windowslive.com', '1'),
  ('thes.ttct.edu.tw', '2'),
  ('bigmir.net', '3')
;

DROP TABLE IF EXISTS `suspicious_domains_history`;
CREATE TABLE `suspicious_domains_history` ( 
  `suspicious_domains_revision_id` BIGINT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mod_date` DATETIME NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `suspicion_level` VARCHAR(14) NOT NULL,
  `modified_by` VARCHAR(255) NOT NULL,
  PRIMARY KEY (suspicious_domains_revision_id)
);

insert into `suspicious_domains_history` (domain, mod_date, suspicion_level, modified_by)
values
  ('gmail.com', '2007-07-31 00:00:00.000', '1', 'asdf90813lkjf09813'),
  ('googlemail.com', '2007-07-31 00:00:00.000', '1', 'asdf90813lkjf09813'),
  ('ymail.com', '2007-07-31 00:00:00.000', '1', 'asdf90813lkjf09813'),
  ('hotmail.com', '2007-07-31 00:00:00.000', '1', 'asdf90813lkjf09813'),
  ('live.com', '2007-07-31 00:00:00.000', '1', 'asdf90813lkjf09813'),
  ('aol.com', '2007-07-31 00:00:00.000', '1', 'asdf90813lkjf09813'),
  ('windowslive.com', '2007-07-31 00:00:00.000', '1', 'asdf90813lkjf09813'),
  ('thes.ttct.edu.tw', '2026-03-10 17:47:43', '2', 'oyrrw74upu8n2dds'),
  ('bigmir.net', '2007-07-31 00:00:00.000', '3', 'asdf90813lkjf09813')
;
