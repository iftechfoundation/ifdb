-- Empty image databases for the public local-dev environment.
-- Production IFDB stores cover art and profile photos here; the IFArchive
-- backup does not include this data. These empty schemas let developers
-- upload their own images locally. Existing production artwork still
-- redirects to ifdb.org when the image is not present locally.

CREATE DATABASE IF NOT EXISTS ifdb_images0;
CREATE DATABASE IF NOT EXISTS ifdb_images1;
CREATE DATABASE IF NOT EXISTS ifdb_images2;
CREATE DATABASE IF NOT EXISTS ifdb_images3;
CREATE DATABASE IF NOT EXISTS ifdb_images4;

CREATE TABLE IF NOT EXISTS ifdb_images0.images (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `format` varchar(32) NOT NULL,
  `img` longblob NOT NULL,
  `copystat` char(1) DEFAULT NULL,
  `copyright` mediumtext DEFAULT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `createdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

CREATE TABLE IF NOT EXISTS ifdb_images1.images (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `format` varchar(32) NOT NULL,
  `img` longblob NOT NULL,
  `copystat` char(1) DEFAULT NULL,
  `copyright` mediumtext DEFAULT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `createdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

CREATE TABLE IF NOT EXISTS ifdb_images2.images (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `format` varchar(32) NOT NULL,
  `img` longblob NOT NULL,
  `copystat` char(1) DEFAULT NULL,
  `copyright` mediumtext DEFAULT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `createdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

CREATE TABLE IF NOT EXISTS ifdb_images3.images (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `format` varchar(32) NOT NULL,
  `img` longblob NOT NULL,
  `copystat` char(1) DEFAULT NULL,
  `copyright` mediumtext DEFAULT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `createdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;

CREATE TABLE IF NOT EXISTS ifdb_images4.images (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `format` varchar(32) NOT NULL,
  `img` longblob NOT NULL,
  `copystat` char(1) DEFAULT NULL,
  `copyright` mediumtext DEFAULT NULL,
  `userid` varchar(32) DEFAULT NULL,
  `createdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;
