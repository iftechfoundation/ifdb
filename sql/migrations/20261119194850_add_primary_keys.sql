-- migrate:up
ALTER TABLE `audit`
  ADD COLUMN IF NOT EXISTS `auditid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `clubmembers`
  ADD COLUMN IF NOT EXISTS `clubmemberid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `compgames`
  ADD COLUMN IF NOT EXISTS `compgameid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `compprofilelinks`
  ADD COLUMN IF NOT EXISTS `compprofilelinkid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `comps_history`
  ADD COLUMN IF NOT EXISTS `comphistoryid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `databaseStats_mv`
  ADD COLUMN IF NOT EXISTS `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `extreviews`
  ADD COLUMN IF NOT EXISTS `extreviewid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `formatprivs`
  ADD COLUMN IF NOT EXISTS `formatprivid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `gamelinks`
  ADD COLUMN IF NOT EXISTS `gamelinkid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `gameprofilelinks`
  ADD COLUMN IF NOT EXISTS `gameprofilelinkid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `games_history`
  ADD COLUMN IF NOT EXISTS `gamehistoryid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `gametags`
  ADD COLUMN IF NOT EXISTS `gametagid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `gamexrefs`
  ADD COLUMN IF NOT EXISTS `gamexrefid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `ifids`
  ADD COLUMN IF NOT EXISTS `ifidid` bigint(20) unsigned NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`ifidid`),
  ADD UNIQUE KEY IF NOT EXISTS `ifid` (`ifid`);
ALTER TABLE `iso639x`
  ADD COLUMN IF NOT EXISTS `iso639xid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `logins`
  ADD COLUMN IF NOT EXISTS `loginid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `nonces`
  ADD COLUMN IF NOT EXISTS `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `osprivs`
  ADD COLUMN IF NOT EXISTS `osprivid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `playedgames`
  ADD COLUMN IF NOT EXISTS `playedgameid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `pollcomments`
  ADD COLUMN IF NOT EXISTS `pollcommentid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `pollvotes`
  ADD COLUMN IF NOT EXISTS `pollvoteid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `privileges`
  ADD COLUMN IF NOT EXISTS `privilegeid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `reclistitems`
  ADD COLUMN IF NOT EXISTS `reclistitemid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `reviewflags`
  ADD COLUMN IF NOT EXISTS `reviewflagid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `reviewtags`
  ADD COLUMN IF NOT EXISTS `reviewtagid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `tagstats`
  ADD COLUMN IF NOT EXISTS `tagstatid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `unwishlists`
  ADD COLUMN IF NOT EXISTS `unwishlistid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `userfilters`
  ADD COLUMN IF NOT EXISTS `userfilterid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `wishlists`
  ADD COLUMN IF NOT EXISTS `wishlistid` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

-- migrate:down
ALTER TABLE `audit` DROP COLUMN IF EXISTS `auditid`;
ALTER TABLE `clubmembers` DROP COLUMN IF EXISTS `clubmemberid`;
ALTER TABLE `compgames` DROP COLUMN IF EXISTS `compgameid`;
ALTER TABLE `compprofilelinks` DROP COLUMN IF EXISTS `compprofilelinkid`;
ALTER TABLE `comps_history` DROP COLUMN IF EXISTS `comphistoryid`;
ALTER TABLE `databaseStats_mv` DROP COLUMN IF EXISTS `id`;
ALTER TABLE `extreviews` DROP COLUMN IF EXISTS `extreviewid`;
ALTER TABLE `formatprivs` DROP COLUMN IF EXISTS `formatprivid`;
ALTER TABLE `gamelinks` DROP COLUMN IF EXISTS `gamelinkid`;
ALTER TABLE `gameprofilelinks` DROP COLUMN IF EXISTS `gameprofilelinkid`;
ALTER TABLE `games_history` DROP COLUMN IF EXISTS `gamehistoryid`;
ALTER TABLE `gametags` DROP COLUMN IF EXISTS `gametagid`;
ALTER TABLE `gamexrefs` DROP COLUMN IF EXISTS `gamexrefid`;
ALTER TABLE `ifids`
  DROP COLUMN `ifidid`,
  DROP KEY `ifid`,
  ADD PRIMARY KEY (`ifid`);
ALTER TABLE `iso639x` DROP COLUMN IF EXISTS `iso639xid`;
ALTER TABLE `logins` DROP COLUMN IF EXISTS `loginid`;
ALTER TABLE `nonces` DROP COLUMN IF EXISTS `id`;
ALTER TABLE `osprivs` DROP COLUMN IF EXISTS `osprivid`;
ALTER TABLE `playedgames` DROP COLUMN IF EXISTS `playedgameid`;
ALTER TABLE `pollcomments` DROP COLUMN IF EXISTS `pollcommentid`;
ALTER TABLE `pollvotes` DROP COLUMN IF EXISTS `pollvoteid`;
ALTER TABLE `privileges` DROP COLUMN IF EXISTS `privilegeid`;
ALTER TABLE `reclistitems` DROP COLUMN IF EXISTS `reclistitemid`;
ALTER TABLE `reviewflags` DROP COLUMN IF EXISTS `reviewflagid`;
ALTER TABLE `reviewtags` DROP COLUMN IF EXISTS `reviewtagid`;
ALTER TABLE `tagstats` DROP COLUMN IF EXISTS `tagstatid`;
ALTER TABLE `unwishlists` DROP COLUMN IF EXISTS `unwishlistid`;
ALTER TABLE `userfilters` DROP COLUMN IF EXISTS `userfilterid`;
ALTER TABLE `wishlists` DROP COLUMN IF EXISTS `wishlistid`;
