-- Migration: Add VPN/Proxy detection to geo_cache table
-- Run this if you already have geo_cache table from previous version
-- Usage: Go to cPanel phpMyAdmin → SQL tab → paste and run this

ALTER TABLE `geo_cache` 
ADD COLUMN `is_proxy` TINYINT(1) NOT NULL DEFAULT 0 AFTER `country`,
ADD COLUMN `is_hosting` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_proxy`,
ADD KEY `idx_geo_checked` (`checked_at`);
