-- migrate:up
CREATE TABLE `databaseStats_mv` (
  `reviewCount` int unsigned NOT NULL,
  `ratingCount` int unsigned NOT NULL,
  `memberCount` int unsigned NOT NULL,
  `updated` datetime NOT NULL
) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO `databaseStats_mv`
SELECT
  COUNT(`review`),
  COUNT(IF(`review` IS NULL, `rating`, NULL)),
  (SELECT COUNT(*) FROM `users`
   WHERE `acctstatus` = 'A' AND IFNULL(`profilestatus`, ' ') != 'R'),
  NOW()
FROM `reviews`
WHERE `special` IS NULL
  AND IFNULL(NOW() >= `embargodate`, 1);

-- migrate:down
DROP TABLE `databaseStats_mv`;
