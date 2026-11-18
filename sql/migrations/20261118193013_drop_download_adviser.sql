-- migrate:up
DROP TABLE IF EXISTS `downloadhelp`;

ALTER TABLE `osversions`
  DROP COLUMN IF EXISTS `browserid`,
  DROP COLUMN IF EXISTS `dlinstructions`,
  DROP COLUMN IF EXISTS `dlruninstructions`,
  DROP COLUMN IF EXISTS `runinstructions`;

-- migrate:down
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Irreversible: the Download Adviser data is gone';
