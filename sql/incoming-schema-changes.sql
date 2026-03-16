USE ifdb;

-- use this script for pending changes to the production DB schema

DROP TABLE IF EXISTS `suspicious_domains`;
CREATE TABLE `suspicious_domains` ( 
  `suspicious_domain_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain` VARCHAR(255) NOT NULL,
  `suspicion_level` TINYINT(1) UNSIGNED NOT NULL,
  PRIMARY KEY (suspicious_domain_id),
  UNIQUE KEY `domain` (`domain`)
);

insert into suspicious_domains (domain, suspicion_level)
values ('gmail.com', '1');
insert into suspicious_domains (domain, suspicion_level)
values ('outlook.com', '2');
insert into suspicious_domains (domain, suspicion_level)
values ('yahoo.com', '3');


DROP TABLE IF EXISTS `suspicious_domains_history`;
CREATE TABLE `suspicious_domains_history` ( 
  `suspicious_domains_revision_id` BIGINT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain` VARCHAR(255) NOT NULL,
  `mod_date` DATETIME NOT NULL,
  `suspicion_level` VARCHAR(6) NOT NULL,
  `modified_by` VARCHAR(255) NOT NULL,
  PRIMARY KEY (suspicious_domains_revision_id)
);

insert into `suspicious_domains_history` (domain, mod_date, suspicion_level, modified_by)
values ('gmail.com', '1997-01-01 00:32:11.840', '3', 'dmmb5sjyxn8x6wf');
insert into suspicious_domains_history (domain, mod_date, suspicion_level, modified_by)
values ('gmail.com', '2019-12-31 08:28:59.840', '1', '35hnhtx0k51rr9j');  
insert into suspicious_domains_history (domain, mod_date, suspicion_level, modified_by)
values ('outlook.com', '2020-08-15 02:41:54.840', '2', 'dmmb5sjyxn8x6wf'); 
insert into suspicious_domains_history (domain, mod_date, suspicion_level, modified_by)
values ('yahoo.com', '2023-07-20 02:02:24.840', '3', '35hnhtx0k51rr9j');

