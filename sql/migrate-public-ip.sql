-- Migration: store the visitor's true external public IP (reported by the
-- browser via an IP-echo API), for sites hosted inside the ISP where the server
-- only sees an internal NAT address. Run once in cPanel → phpMyAdmin → SQL.

ALTER TABLE `visits` ADD COLUMN `public_ip` VARCHAR(45) NULL AFTER `ip`;
