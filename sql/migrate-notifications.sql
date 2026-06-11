-- Migration: SMTP email + channel health checks
-- Run once on an existing database (cPanel phpMyAdmin → SQL tab).

ALTER TABLE `channels`
  ADD COLUMN `health_status`   ENUM('unknown','ok','down') NOT NULL DEFAULT 'unknown' AFTER `status`,
  ADD COLUMN `fail_count`      INT NOT NULL DEFAULT 0 AFTER `health_status`,
  ADD COLUMN `auto_hidden`     TINYINT(1) NOT NULL DEFAULT 0 AFTER `fail_count`,
  ADD COLUMN `last_checked_at` DATETIME NULL AFTER `auto_hidden`;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('smtp_host',''),('smtp_port','465'),('smtp_secure','ssl'),('smtp_user',''),
('smtp_pass',''),('smtp_from',''),('smtp_from_name',''),('notify_email',''),
('notify_errors','0'),('error_notify_last','0'),
('health_enabled','0'),('health_auto_hide','1'),('health_notify','1'),
('health_fail_threshold','2'),('health_cron_token','');
