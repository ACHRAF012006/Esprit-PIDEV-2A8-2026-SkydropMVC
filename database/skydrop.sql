-- SKYDROP: the whole database (user management: tables, then demo rows).
-- Run: php database/install.php   (or import this file in phpMyAdmin into the database skydrop_v2)

-- ============================================================
-- TABLES
-- ============================================================

-- Module User (S1 Achref): accounts. Run by `php database/install.php`.
-- Based on the security workshop's users table (id, username, email, password, role, created_at), with the extra
-- columns SKYDROP needs. "password" holds the password_hash() result, never the password itself.
-- Other modules link to users.id with their own foreign keys (shops.owner_id, orders.customer_id, ads.advertiser_id...).

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(150) NOT NULL,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('customer', 'shop_owner', 'advertiser', 'admin') NOT NULL DEFAULT 'customer',
    full_name     VARCHAR(120) NOT NULL,
    phone         VARCHAR(20)  NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME     NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0, -- set when an admin gives a new password
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed logins (security: password guessing). 5 failures on one email within 15 minutes block that email for 15 minutes.
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email        VARCHAR(150) NOT NULL,
    ip_address   VARCHAR(45)  NOT NULL DEFAULT '',
    attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_email (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ads and placements (S5 Karim)
-- A placement is a place of the site where text ads are shown (home banner, search results), with its price per day
-- and the number of ads shown there at the same time (max_ads).
CREATE TABLE IF NOT EXISTS ad_placements (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name          VARCHAR(60)   NOT NULL,
    description   VARCHAR(255)  NOT NULL DEFAULT '',
    price_per_day DECIMAL(10,3) NOT NULL,
    max_ads       INT UNSIGNED  NOT NULL DEFAULT 1,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ad_placements_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A text ad of an advertiser (or shop owner) on one placement, between two dates. total_price is computed by the server
-- (days x price_per_day). An administrator approves or rejects it; a rejected ad keeps the reason in review_note.
CREATE TABLE IF NOT EXISTS ads (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    advertiser_id INT UNSIGNED  NOT NULL,
    placement_id  INT UNSIGNED  NOT NULL,
    title         VARCHAR(80)   NOT NULL,
    body          VARCHAR(300)  NOT NULL,
    link          VARCHAR(255)  NULL,
    starts_at     DATE          NOT NULL,
    ends_at       DATE          NOT NULL,
    total_price   DECIMAL(10,3) NOT NULL,
    status        ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    review_note   VARCHAR(255)  NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ads_advertiser (advertiser_id),
    KEY idx_ads_placement_running (placement_id, status, starts_at, ends_at),
    CONSTRAINT fk_ads_advertiser FOREIGN KEY (advertiser_id) REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_ads_placement FOREIGN KEY (placement_id) REFERENCES ad_placements (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forgotten password (S5 Karim, grid item 1.3): one reset link per row. Only the SHA-256 hash of the token is stored,
-- never the token itself. A link expires after 30 minutes and works once (used_at is set when it is used).
-- user_id is INT UNSIGNED like users.id: a foreign key needs the same type on both sides.
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_resets_token (token_hash),
    KEY idx_password_resets_user (user_id),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEMO DATA
-- ============================================================

-- Module User (S1 Achref): demo accounts (test values only, listed in database/test-accounts.php).
-- Password of every demo account: Skydrop2026 (stored as a password_hash() value).
-- 7 to 13: owners of the demo shops 3 to 9 of the Shop module (owner7@skydrop.test ... owner13@skydrop.test).
INSERT IGNORE INTO users (id, username, email, password, role, full_name, phone, is_active, created_at) VALUES
(1, 'admin',      'admin@skydrop.test',      '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'admin',      'Demo Admin',        NULL,       1, '2026-10-01 09:00:00'),
(2, 'owner',      'owner@skydrop.test',      '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Demo Shop Owner',   '20123456', 1, '2026-10-12 10:30:00'),
(3, 'customer',   'customer@skydrop.test',   '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'customer',   'Demo Customer',     '50123456', 1, '2026-11-02 18:15:00'),
(4, 'advertiser', 'advertiser@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'advertiser', 'Demo Advertiser',   '70123456', 1, '2026-11-05 11:00:00'),
(5, 'owner2',     'owner2@skydrop.test',     '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Second Shop Owner', NULL,       1, '2026-10-20 14:00:00'),
(6, 'blocked',    'blocked@skydrop.test',    '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'customer',   'Deactivated Customer', NULL,     0, '2026-11-08 09:30:00'),
(7, 'owner7', 'owner7@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Owner of Boulangerie Ennasr', NULL, 1, '2026-10-10 09:00:00'),
(8, 'owner8', 'owner8@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Owner of Librairie du Lac', NULL, 1, '2026-10-10 09:00:00'),
(9, 'owner9', 'owner9@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Owner of Bio Market La Marsa', NULL, 1, '2026-10-10 09:00:00'),
(10, 'owner10', 'owner10@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Owner of Fleurs du Bardo', NULL, 1, '2026-10-10 09:00:00'),
(11, 'owner11', 'owner11@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Owner of Tech Corner', NULL, 1, '2026-10-10 09:00:00'),
(12, 'owner12', 'owner12@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Owner of Pharmacie Carthage', NULL, 1, '2026-10-10 09:00:00'),
(13, 'owner13', 'owner13@skydrop.test', '$2y$10$CEf2IyxlyZA4L5IhNZy.buVfkQPR/.hJM/SDAdXUdi3gubsHeaCp.', 'shop_owner', 'Owner of Primeur Ariana', NULL, 1, '2026-10-10 09:00:00');

-- Ads and placements (S5 Karim): two placements, then three ads of the demo advertiser (id 4).
-- The ad dates follow the day of the install, so ad 1 is always running now.
INSERT IGNORE INTO ad_placements (id, name, description, price_per_day, max_ads, is_active, created_at) VALUES
(1, 'Home banner',    'Text ads under the search box of the home page.', 12.000, 2, 1, '2026-11-01 09:00:00'),
(2, 'Search results', 'Text ads above the products found by a search.',    8.000, 3, 1, '2026-11-01 09:00:00');

INSERT IGNORE INTO ads (id, advertiser_id, placement_id, title, body, link, starts_at, ends_at, total_price, status, review_note, created_at) VALUES
(1, 4, 1, 'Fresh bread every morning', 'Boulangerie Ennasr bakes bread, croissants and cakes from 6 am. Order before 9 am and pick it up warm.',
    'https://boulangerie-ennasr.skydrop.test', CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 20 DAY, 288.000, 'approved', NULL, '2026-11-20 10:00:00'),
(2, 4, 1, 'Winter school supplies', 'Notebooks, pens and school bags for the second term at Librairie du Lac. Ten percent off the full list.',
    NULL, CURDATE() + INTERVAL 5 DAY, CURDATE() + INTERVAL 14 DAY, 120.000, 'pending', NULL, '2026-11-25 15:30:00'),
(3, 4, 2, 'Best prices in Tunisia', 'The cheapest phones of the whole country, guaranteed, with free delivery everywhere today only.',
    'https://tech-corner.skydrop.test', CURDATE() + INTERVAL 2 DAY, CURDATE() + INTERVAL 8 DAY, 56.000, 'rejected',
    'The text promises prices and a free delivery that the shop cannot prove. Write the real offer and send a new ad.', '2026-11-22 09:15:00');
