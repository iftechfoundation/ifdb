USE ifdb;

-- use this script for pending changes to the production DB schema


CREATE TABLE `tagsynonyms` (
    `tagsynonymid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `fromtag` varchar(255) COLLATE latin1_german2_ci NOT NULL,
    `totag` varchar(255) COLLATE latin1_german2_ci NOT NULL,
    PRIMARY KEY (`tagsynonymid`),
    KEY `fromtag` (`fromtag`),
    UNIQUE KEY `from_to` (`fromtag`, `totag`)
) ENGINE = MyISAM DEFAULT CHARSET = latin1 COLLATE = latin1_german2_ci;

insert into tagsynonyms (fromtag, totag) values ('science fiction', 'sci-fi');
