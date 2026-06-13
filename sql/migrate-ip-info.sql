-- Migration: cached ISP lookups (ip-api.com) for the visitor report's ISP column.
-- Run once in cPanel → phpMyAdmin → SQL.

CREATE TABLE IF NOT EXISTS `ip_info` (
  `ip`         VARCHAR(45)  NOT NULL,
  `isp`        VARCHAR(190) NOT NULL DEFAULT '',
  `checked_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
