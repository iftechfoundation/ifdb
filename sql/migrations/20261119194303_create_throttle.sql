-- migrate:up
CREATE TABLE `throttle` (
  `action` varchar(16) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `when` datetime NOT NULL,
  KEY `action_when` (`action`, `when`),
  KEY `when` (`when`),
  KEY `userid` (`userid`)
);

-- migrate:down
DROP TABLE `throttle`;
