USE ifdb;

-- use this script for pending changes to the production DB schema


CREATE TABLE `blockedtagsynonyms` (
    `blockedtagsynonymid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `blockedtag` varchar(255) COLLATE latin1_german2_ci NOT NULL,
    `preferredtag` varchar(255) COLLATE latin1_german2_ci NOT NULL,
    PRIMARY KEY (`blockedtagsynonymid`),
    UNIQUE KEY `blockedtag` (`blockedtag`)
) ENGINE = MyISAM DEFAULT CHARSET = latin1 COLLATE = latin1_german2_ci;

insert into blockedtagsynonyms (blockedtag, preferredtag)
values ('sci-fi', 'science fiction');


--
-- Table structure for table `playertimes`
--

DROP TABLE IF EXISTS `playertimes`;
CREATE TABLE playertimes (
  id INT AUTO_INCREMENT,
  gameid VARCHAR(32) NOT NULL,
  userid VARCHAR(32) NOT NULL,
  timevote INT(5) unsigned not null,
  PRIMARY KEY (id)
);

-- Sample time values for The Tempest (by Grigg)
insert into playertimes (gameid, userid, timevote)
values ('59g5czw7izz7aoip', 'kaw2cas7dyiq2tmg', 63);

insert into playertimes (gameid, userid, timevote)
values ('59g5czw7izz7aoip', '0000000000000000', 128);

insert into playertimes (gameid, userid, timevote)
values ('59g5czw7izz7aoip', '0000000000000001', 55);


insert into playertimes (gameid, userid, timevote)
values ('59g5czw7izz7aoip', 'pwamtkqtbeyc8eyn', 37);

insert into playertimes (gameid, userid, timevote)
values ('59g5czw7izz7aoip', '6cfekbbjqeduww77', 76);

-- Sample time values for Ninja (by Panks)
insert into playertimes (gameid, userid, timevote)
values ('n93jonigjmva9e3g', 'kaw2cas7dyiq2tmg', 139);

insert into playertimes (gameid, userid, timevote)
values ('59g5czw7izz7aoip', '0000000000000001', 134);

insert into playertimes (gameid, userid, timevote)
values ('59g5czw7izz7aoip', '0000000000000000', 204);

insert into playertimes (gameid, userid, timevote)
values ('n93jonigjmva9e3g', 'pwamtkqtbeyc8eyn', 116);

-- Sample time values for Four Seconds (by Reigstad)
insert into playertimes (gameid, userid, timevote)
values ('bu6mmul5vxci5vqc', 'kaw2cas7dyiq2tmg', 1);

insert into playertimes (gameid, userid, timevote)
values ('bu6mmul5vxci5vqc', 'pwamtkqtbeyc8eyn', 6);

