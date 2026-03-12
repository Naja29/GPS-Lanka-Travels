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
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(120) NOT NULL UNIQUE,
  `icon`       VARCHAR(80)  DEFAULT 'fas fa-map',
  `sort_order` TINYINT UNSIGNED DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
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


-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `skey`  VARCHAR(100) PRIMARY KEY,
  `sval`  TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `settings` (`skey`,`sval`) VALUES
('site_name','GPS Lanka Travels'),
('site_tagline','Sri Lanka''s Premier Tour Operator'),
('site_email','info@gpslankatravels.com'),
('site_phone','+94 77 048 9956'),
('site_whatsapp','94770489956'),
('site_address','289/1 Madampagama, Kuleegoda, Ambalangoda, Sri Lanka'),
('business_hours','Mon - Sun: 8:00 AM - 8:00 PM'),
('facebook_url','https://facebook.com/gpslankatravels'),
('instagram_url','https://instagram.com/gpslankatravels'),
('tripadvisor_url','#'),
('google_maps_embed',''),
('meta_description','GPS Lanka Travels - Inbound tour operator in Sri Lanka. Custom tours, cultural experiences, wildlife safaris and honeymoon packages.');
