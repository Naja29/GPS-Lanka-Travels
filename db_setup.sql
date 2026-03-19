CREATE DATABASE IF NOT EXISTS `gps_lanka_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gps_lanka_db`;

-- Admin Users
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`    VARCHAR(80)  NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `full_name`   VARCHAR(120) DEFAULT NULL,
  `email`       VARCHAR(160) DEFAULT NULL,
  `role`        ENUM('super','admin') DEFAULT 'admin',
  `last_login`  DATETIME DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Default: admin / admin123
INSERT IGNORE INTO `admin_users` (`username`,`password`,`full_name`,`email`,`role`) VALUES
('admin','$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6ZykCVl3.WAu','GPS Lanka Admin','info@gpslankatravels.com','super');

-- Tour Categories
CREATE TABLE IF NOT EXISTS `tour_categories` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL UNIQUE,
  `icon`        VARCHAR(80)  DEFAULT 'fas fa-map',
  `description` TEXT DEFAULT NULL,
  `image`       VARCHAR(300) DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED DEFAULT 0,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tours
CREATE TABLE IF NOT EXISTS `tours` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id`  INT UNSIGNED DEFAULT NULL,
  `title`        VARCHAR(200) NOT NULL,
  `slug`         VARCHAR(220) NOT NULL UNIQUE,
  `short_desc`   TEXT,
  `description`  LONGTEXT,
  `itinerary`    LONGTEXT,
  `includes`     TEXT,
  `excludes`     TEXT,
  `highlights`   TEXT,
  `duration`     VARCHAR(60),
  `group_size`   VARCHAR(60) DEFAULT '1-15 people',
  `price_usd`    DECIMAL(10,2) DEFAULT 0.00,
  `price_note`   VARCHAR(120) DEFAULT 'Per person',
  `image`        VARCHAR(300) DEFAULT NULL,
  `gallery`      TEXT DEFAULT NULL,
  `map_embed`    TEXT DEFAULT NULL,
  `tips`         TEXT DEFAULT NULL,
  `is_featured`  TINYINT(1) DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  `sort_order`   SMALLINT UNSIGNED DEFAULT 0,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `tour_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Enquiries
CREATE TABLE IF NOT EXISTS `enquiries` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(120) NOT NULL,
  `email`       VARCHAR(160) NOT NULL,
  `phone`       VARCHAR(40)  DEFAULT NULL,
  `tour_type`   VARCHAR(120) DEFAULT NULL,
  `travel_date` DATE DEFAULT NULL,
  `persons`     TINYINT UNSIGNED DEFAULT 1,
  `budget`      VARCHAR(80)  DEFAULT NULL,
  `message`     TEXT,
  `is_read`     TINYINT(1) DEFAULT 0,
  `status`      ENUM('new','read','replied','closed') DEFAULT 'new',
  `notes`       TEXT DEFAULT NULL,
  `ip_address`  VARCHAR(60) DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Blog Categories
CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`      VARCHAR(100) NOT NULL,
  `slug`      VARCHAR(120) NOT NULL UNIQUE,
  `color`     VARCHAR(20) DEFAULT '#0f5252',
  `sort_order`TINYINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Blog Posts
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `title`       VARCHAR(250) NOT NULL,
  `slug`        VARCHAR(270) NOT NULL UNIQUE,
  `excerpt`     TEXT,
  `content`     LONGTEXT,
  `image`       VARCHAR(300) DEFAULT NULL,
  `author`      VARCHAR(100) DEFAULT 'GPS Lanka Travels',
  `read_time`   TINYINT UNSIGNED DEFAULT 5,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_published`TINYINT(1) DEFAULT 0,
  `views`       INT UNSIGNED DEFAULT 0,
  `published_at`DATETIME DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Gallery
CREATE TABLE IF NOT EXISTS `gallery` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `filename`   VARCHAR(300) NOT NULL,
  `caption`    VARCHAR(250) DEFAULT NULL,
  `category`   ENUM('guests','wildlife','nature','culture','beach','misc') DEFAULT 'misc',
  `alt_text`   VARCHAR(250) DEFAULT NULL,
  `sort_order` SMALLINT UNSIGNED DEFAULT 0,
  `is_active`  TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Testimonials
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(120) NOT NULL,
  `country`     VARCHAR(80)  DEFAULT NULL,
  `country_flag`VARCHAR(10)  DEFAULT NULL,
  `tour`        VARCHAR(200) DEFAULT NULL,
  `rating`      TINYINT UNSIGNED DEFAULT 5,
  `review`      TEXT NOT NULL,
  `photo`       VARCHAR(300) DEFAULT NULL,
  `source`      ENUM('google','tripadvisor','facebook','direct') DEFAULT 'google',
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_active`   TINYINT(1) DEFAULT 1,
  `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Partners
CREATE TABLE IF NOT EXISTS `partners` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(120) NOT NULL,
  `logo`        VARCHAR(300) DEFAULT NULL,
  `icon_class`  VARCHAR(80)  DEFAULT NULL,
  `icon_color`  VARCHAR(20)  DEFAULT '#0f5252',
  `label`       VARCHAR(120) DEFAULT NULL,
  `url`         VARCHAR(300) DEFAULT NULL,
  `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
  `is_active`   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Homepage Slider
CREATE TABLE IF NOT EXISTS `slider` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`      VARCHAR(200) NOT NULL,
  `subtitle`   VARCHAR(300) DEFAULT NULL,
  `image`      VARCHAR(300) NOT NULL,
  `btn1_text`  VARCHAR(80)  DEFAULT NULL,
  `btn1_url`   VARCHAR(300) DEFAULT '#',
  `btn2_text`  VARCHAR(80)  DEFAULT NULL,
  `btn2_url`   VARCHAR(300) DEFAULT '#',
  `sort_order` TINYINT UNSIGNED DEFAULT 0,
  `is_active`  TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Why Choose Us
CREATE TABLE IF NOT EXISTS `why_us` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `icon`        VARCHAR(80) DEFAULT 'fas fa-star',
  `title`       VARCHAR(120) NOT NULL,
  `description` TEXT,
  `sort_order`  TINYINT UNSIGNED DEFAULT 0,
  `is_active`   TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Team Members
CREATE TABLE IF NOT EXISTS `team` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(120) NOT NULL,
  `role`        VARCHAR(120) DEFAULT NULL,
  `bio`         TEXT DEFAULT NULL,
  `photo`       VARCHAR(300) DEFAULT NULL,
  `facebook`    VARCHAR(300) DEFAULT NULL,
  `instagram`   VARCHAR(300) DEFAULT NULL,
  `linkedin`    VARCHAR(300) DEFAULT NULL,
  `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Services (What We Offer)
CREATE TABLE IF NOT EXISTS `services` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon`        VARCHAR(80)  DEFAULT 'fas fa-star',
  `image`       VARCHAR(300) DEFAULT NULL,
  `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
  `is_active`   TINYINT(1) DEFAULT 1,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Destination Categories
CREATE TABLE IF NOT EXISTS `destination_categories` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL UNIQUE,
  `icon`        VARCHAR(80)  DEFAULT 'fas fa-map-marker-alt',
  `description` TEXT DEFAULT NULL,
  `sort_order`  TINYINT UNSIGNED DEFAULT 0,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Destinations
CREATE TABLE IF NOT EXISTS `destinations` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id`  INT UNSIGNED DEFAULT NULL,
  `title`        VARCHAR(200) NOT NULL,
  `slug`         VARCHAR(220) NOT NULL UNIQUE,
  `location`     VARCHAR(150) DEFAULT NULL,
  `region`       VARCHAR(100) DEFAULT NULL,
  `excerpt`      TEXT DEFAULT NULL,
  `description`  LONGTEXT DEFAULT NULL,
  `hero_image`   VARCHAR(300) DEFAULT NULL,
  `gallery`      TEXT DEFAULT NULL,
  `map_embed`    TEXT DEFAULT NULL,
  `read_time`    TINYINT UNSIGNED DEFAULT 5,
  `is_featured`  TINYINT(1) DEFAULT 0,
  `is_active`    TINYINT(1) DEFAULT 1,
  `sort_order`   SMALLINT UNSIGNED DEFAULT 0,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `destination_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Gallery Categories
CREATE TABLE IF NOT EXISTS `gallery_categories` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL UNIQUE,
  `sort_order`  TINYINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Newsletter Subscribers
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `email`         VARCHAR(255) NOT NULL,
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_active`     TINYINT(1) DEFAULT 1,
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tour Bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `tour_id`     INT DEFAULT NULL,
  `tour_title`  VARCHAR(300) NOT NULL,
  `name`        VARCHAR(120) NOT NULL,
  `email`       VARCHAR(255) NOT NULL,
  `phone`       VARCHAR(50)  DEFAULT NULL,
  `tour_date`   DATE DEFAULT NULL,
  `persons`     VARCHAR(50)  DEFAULT NULL,
  `message`     TEXT DEFAULT NULL,
  `status`      VARCHAR(20)  DEFAULT 'new',
  `is_read`     TINYINT(1)   DEFAULT 0,
  `notes`       TEXT DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `skey`  VARCHAR(100) PRIMARY KEY,
  `sval`  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `settings` (`skey`,`sval`) VALUES
('site_name',          'GPS Lanka Travels'),
('site_tagline',       'Sri Lanka''s Premier Tour Operator'),
('site_email',         'info@gpslankatravels.com'),
('site_phone',         '+94 77 048 9956'),
('site_whatsapp',      '94770489956'),
('site_address',       '289/1 Madampagama, Kuleegoda, Ambalangoda, Sri Lanka'),
('business_hours',     'Mon - Sun: 8:00 AM - 8:00 PM'),
('business_hours_json',''),
('footer_about',       'Your trusted travel companion for unforgettable Sri Lanka experiences. Licensed, reliable and passionate about sharing the beauty of our island with the world.'),
('footer_copyright',   '&copy; 2025 GPS Lanka Travels. All rights reserved.'),
('facebook_url',       'https://facebook.com/gpslankatravels'),
('instagram_url',      'https://instagram.com/gpslankatravels'),
('youtube_url',        ''),
('tiktok_url',         ''),
('twitter_url',        ''),
('tripadvisor_url',    '#'),
('google_maps_embed',  ''),
('maintenance_mode',   '0'),
('maintenance_message','We''re currently upgrading our website. We''ll be back shortly!'),
('meta_description',   'GPS Lanka Travels - Inbound tour operator in Sri Lanka. Custom tours, cultural experiences, wildlife safaris and honeymoon packages.');
