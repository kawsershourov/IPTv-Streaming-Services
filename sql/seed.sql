-- SunPlex.live — seed data
-- Run AFTER schema.sql:  USE sunplex;  SOURCE sql/seed.sql;
--
-- NOTE: stream_url values are PUBLIC TEST HLS streams used only so the player
-- works out of the box. Replace them with your own LICENSED stream URLs via the
-- admin panel before going live.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Admin user  (email: admin@sunplex.live  /  password: admin123)
-- Change the password immediately after first login.
-- ---------------------------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `status`) VALUES
('Administrator', 'admin@sunplex.live', '$2y$10$exvinBhjLbbg3mETd6FcYOSid8pK.QmrQO8guA673RJ6FUJTOgdhC', 'admin', 'active');

-- ---------------------------------------------------------------------------
-- Subscription plans
-- ---------------------------------------------------------------------------
INSERT INTO `plans` (`name`, `price`, `duration_days`, `description`, `is_active`, `sort_order`) VALUES
('Free',    0.00,   3650, 'Access to free channels.',                       1, 1),
('Premium', 4.99,   30,   'All free channels plus premium live channels.',  1, 2),
('VIP',     9.99,   30,   'Everything in Premium plus early access and HD.', 1, 3);

-- ---------------------------------------------------------------------------
-- Categories (from the live sunplex.live structure)
-- ---------------------------------------------------------------------------
INSERT INTO `categories` (`name`, `slug`, `sort_order`, `is_active`) VALUES
('Live Events',           'live-events',  1, 1),
('Sports',                'sports',       2, 1),
('News',                  'news',         3, 1),
('Entertainment',         'entertainment',4, 1),
('Local Channels',        'local',        5, 1),
('Documentary & Lifestyle','documentary', 6, 1);

-- Public test streams (placeholders) ----------------------------------------
SET @hls1 = 'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8';
SET @hls2 = 'https://demo.unified-streaming.com/k8s/features/stable/video/tears-of-steel/tears-of-steel.ism/.m3u8';
SET @hls3 = 'https://test-streams.mux.dev/pts_shift/master.m3u8';

-- ---------------------------------------------------------------------------
-- Channels  (category resolved by slug)
-- ---------------------------------------------------------------------------

-- Live Events
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'Live Event Main','live-event-main',@hls1,'hls',1,1,1 FROM categories WHERE slug='live-events';

-- Sports
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'T-Sports','t-sports',@hls1,'hls',1,0,1 FROM categories WHERE slug='sports';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'Star Sports Select 1','star-sports-select-1',@hls2,'hls',1,1,2 FROM categories WHERE slug='sports';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'ESPN','espn',@hls3,'hls',1,1,3 FROM categories WHERE slug='sports';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'PTV Sports','ptv-sports',@hls1,'hls',1,0,4 FROM categories WHERE slug='sports';

-- News
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'BBC News','bbc-news',@hls2,'hls',1,0,1 FROM categories WHERE slug='news';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'Al Jazeera','al-jazeera',@hls1,'hls',1,0,2 FROM categories WHERE slug='news';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'CNN','cnn',@hls3,'hls',1,0,3 FROM categories WHERE slug='news';

-- Entertainment
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'Bollywood HD','bollywood-hd',@hls2,'hls',1,1,1 FROM categories WHERE slug='entertainment';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'Hum TV','hum-tv',@hls1,'hls',1,0,2 FROM categories WHERE slug='entertainment';

-- Local Channels
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'NTV','ntv',@hls1,'hls',1,0,1 FROM categories WHERE slug='local';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'Channel i HD','channel-i-hd',@hls2,'hls',1,0,2 FROM categories WHERE slug='local';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'Jamuna TV','jamuna-tv',@hls3,'hls',1,0,3 FROM categories WHERE slug='local';

-- Documentary & Lifestyle
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'BBC Earth','bbc-earth',@hls2,'hls',1,1,1 FROM categories WHERE slug='documentary';
INSERT INTO `channels` (`category_id`,`name`,`slug`,`stream_url`,`stream_type`,`is_live`,`is_premium`,`sort_order`)
SELECT id,'History TV','history-tv',@hls3,'hls',1,0,2 FROM categories WHERE slug='documentary';

-- ---------------------------------------------------------------------------
-- Settings
-- ---------------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name',          'SunPlex'),
('site_tagline',       'Live TV & Streaming'),
('registration_open',  '1'),
('default_skin',       'minimal_skin_dark'),
('player_width',       '960'),
('player_height',      '540');
