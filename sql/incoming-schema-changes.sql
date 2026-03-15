USE ifdb;

-- use this script for pending changes to the production DB schema



DROP TABLE IF EXISTS `suspicious_domains`;
CREATE TABLE `suspicious_domains` ( 
  `record_id` INT AUTO_INCREMENT,
  `domain` VARCHAR(255) NOT NULL,
  `mod_date` DATETIME DEFAULT now(),
  `suspicion_level` tinyint(1) UNSIGNED NOT NULL,
  `modified_by` varchar(255) NOT NULL,
  `admin_note` varchar(255) NOT NULL,
  PRIMARY KEY (record_id)
);

insert into `suspicious_domains` (domain, mod_date, suspicion_level, modified_by, admin_note)
values ('gmail.com', '1997-01-01 00:38:54.840', '3', 'kaw2cas7dyiq2tmg', 'My comment.');
insert into suspicious_domains (domain, mod_date, suspicion_level, modified_by, admin_note)
values ('gmail.com', '2019-12-31 08:38:54.840', '1', '35hnhtx0k51rr9j', 'My latest awesome comment');  
insert into suspicious_domains (domain, mod_date, suspicion_level, modified_by, admin_note)
values ('outlook.com', '2020-06-20 02:38:54.840', '1', 'kaw2cas7dyiq2tmg', 'Comment goes here.'); 
insert into suspicious_domains (domain, mod_date, suspicion_level, modified_by, admin_note)
values ('yahoo.com', '2023-07-20 02:38:54.840', '2', '35hnhtx0k51rr9j', 'Comment goes here.');
insert into suspicious_domains (domain, mod_date, suspicion_level, modified_by, admin_note)
values ('facebook.com', '2006-02-20 02:38:54.840', '2', 'kaw2cas7dyiq2tmg', 'Comment goes here.');
insert into suspicious_domains (domain, mod_date, suspicion_level, modified_by, admin_note)
values ('fakedomain.edu', '2010-09-20 02:38:54.840', '3', '35hnhtx0k51rr9j', 'Comment goes here.'); 
