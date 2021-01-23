DROP TABLE IF EXISTS `audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit` (
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `action` mediumtext COLLATE latin1_german2_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `userfilters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userfilters` (
  `userid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `targetuserid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `filtertype` char(1) COLLATE latin1_german2_ci NOT NULL,
  KEY `userid` (`userid`),
  KEY `targetuserid` (`targetuserid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `users`
    ADD COLUMN `email` varchar(255) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
    ADD COLUMN `emailflags` tinyint(2) NOT NULL DEFAULT '3',
    ADD COLUMN `profilestatus` varchar(1) COLLATE latin1_german2_ci DEFAULT NULL,
    ADD COLUMN `password` varchar(40) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
    ADD COLUMN `pswsalt` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
    ADD COLUMN `activationcode` varchar(40) COLLATE latin1_german2_ci DEFAULT NULL,
    ADD COLUMN `acctstatus` char(1) COLLATE latin1_german2_ci NOT NULL DEFAULT '0',
    ADD COLUMN `lastlogin` datetime DEFAULT NULL,
    ADD COLUMN `privileges` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '''''',
    ADD COLUMN `defaultos` int(11) unsigned DEFAULT NULL,
    ADD COLUMN `defaultosvsn` int(11) unsigned DEFAULT NULL,
    ADD COLUMN `noexedownloads` tinyint(1) NOT NULL DEFAULT '0',
    ADD COLUMN `publiclists` varchar(10) COLLATE latin1_german2_ci DEFAULT NULL,
    ADD COLUMN `mirrorid` int(11) unsigned NOT NULL DEFAULT '100',
    ADD COLUMN `stylesheetid` bigint(20) DEFAULT NULL,
    ADD COLUMN `offsite_display` char(1) COLLATE latin1_german2_ci NOT NULL DEFAULT 'A',
    ADD COLUMN `accessibility` tinyint(1) DEFAULT NULL,
    ADD COLUMN `caughtupdate` datetime DEFAULT NULL,
    ADD COLUMN `remarks` mediumtext COLLATE latin1_german2_ci,
    ADD COLUMN `tosversion` int(11) NOT NULL DEFAULT '1',
    ADD COLUMN `Sandbox` int(11) NOT NULL DEFAULT '0',
    ADD KEY `email` (`email`)
;

--
-- Table structure for table `formatprivs`
--

DROP TABLE IF EXISTS `formatprivs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formatprivs` (
  `fmtid` int(11) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logins`
--

DROP TABLE IF EXISTS `logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logins` (
  `uid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `ip` varchar(16) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `when` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nonces`
--

DROP TABLE IF EXISTS `nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nonces` (
  `nonceid` varchar(256) COLLATE latin1_german2_ci NOT NULL,
  `hash` varchar(40) COLLATE latin1_german2_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `nonceid` (`nonceid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `osprivs`
--

DROP TABLE IF EXISTS `osprivs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `osprivs` (
  `osid` int(11) unsigned NOT NULL DEFAULT '0',
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `persistentsessions`
--

DROP TABLE IF EXISTS `persistentsessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `persistentsessions` (
  `id` varchar(64) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `lastlogin` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `privileges`
--

DROP TABLE IF EXISTS `privileges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privileges` (
  `code` char(1) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `name` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reviewflags`
--

DROP TABLE IF EXISTS `reviewflags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviewflags` (
  `reviewid` bigint(20) NOT NULL,
  `flagger` varchar(32) COLLATE latin1_german2_ci NOT NULL,
  `flagtype` char(1) COLLATE latin1_german2_ci NOT NULL,
  `notes` mediumtext COLLATE latin1_german2_ci,
  `created` datetime NOT NULL,
  KEY `reviewid` (`reviewid`),
  KEY `flagger` (`flagger`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `reviewvotes`
  ADD COLUMN `userid` char(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  ADD KEY `userid` (`userid`)
;

--
-- Table structure for table `stylepics`
--

DROP TABLE IF EXISTS `stylepics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stylepics` (
  `userid` varchar(32) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `name` varchar(128) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `picture` varchar(64) COLLATE latin1_german2_ci NOT NULL DEFAULT '',
  `desc` mediumtext COLLATE latin1_german2_ci,
  `picturebytes` int(11) DEFAULT NULL,
  PRIMARY KEY (`userid`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE `ucomments`
  ADD COLUMN `private` varchar(32) COLLATE latin1_german2_ci DEFAULT NULL
;

--
-- Temporary table structure for view `userScores`
--

DROP TABLE IF EXISTS `userScores`;
/*!50001 DROP VIEW IF EXISTS `userScores`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `userScores` (
  `userid` tinyint NOT NULL,
  `score` tinyint NOT NULL,
  `rankingScore` tinyint NOT NULL,
  `reviewCount` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `userfilters`
--

DROP TABLE IF EXISTS `userfilters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userfilters` (
  `userid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `targetuserid` char(32) COLLATE latin1_german2_ci NOT NULL,
  `filtertype` char(1) COLLATE latin1_german2_ci NOT NULL,
  KEY `userid` (`userid`),
  KEY `targetuserid` (`targetuserid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
