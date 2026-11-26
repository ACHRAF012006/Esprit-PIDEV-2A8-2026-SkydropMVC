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

-- Shops and plan tiers (S2 Mili)
-- shop_plans: the tiers a shop can choose. rank_weight orders the front list (higher = shown first).
CREATE TABLE IF NOT EXISTS shop_plans (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name          VARCHAR(40)   NOT NULL,
    monthly_price DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    rank_weight   INT           NOT NULL DEFAULT 1,
    max_products  INT           NOT NULL DEFAULT 20,
    is_active     TINYINT(1)    NOT NULL DEFAULT 1,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shop_plans_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- shops: one shop per owner (uq_shops_owner), linked to its plan (shops.plan_id -> shop_plans.id).
-- A new shop is 'pending' and appears in the front office only once an admin approves it.
CREATE TABLE IF NOT EXISTS shops (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    owner_id    INT UNSIGNED  NOT NULL,
    plan_id     INT UNSIGNED  NOT NULL,
    name        VARCHAR(80)   NOT NULL,
    description VARCHAR(500)  NULL,
    address     VARCHAR(160)  NULL,
    phone       VARCHAR(20)   NULL,
    opens_at    TIME          NOT NULL,
    closes_at   TIME          NOT NULL,
    lat         DECIMAL(9,6)  NULL,
    lng         DECIMAL(9,6)  NULL,
    status      ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    review_note VARCHAR(255)  NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shops_name (name),
    UNIQUE KEY uq_shops_owner (owner_id),
    KEY idx_shops_status (status),
    CONSTRAINT fk_shops_owner FOREIGN KEY (owner_id) REFERENCES users (id),
    CONSTRAINT fk_shops_plan FOREIGN KEY (plan_id) REFERENCES shop_plans (id)
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

-- Shops and plan tiers (S2 Mili): plans 1 to 3, shop 1 (user 2, approved) and shop 2 (user 5, pending).
-- The other parts use shop 1 owned by user 2: keep these ids.
INSERT IGNORE INTO shop_plans (id, name, monthly_price, rank_weight, max_products, is_active, created_at) VALUES
(1, 'Silver',   0.000,  1, 20,  1, '2026-10-01 09:00:00'),
(2, 'Gold',     29.000, 2, 100, 1, '2026-10-01 09:00:00'),
(3, 'Platinum', 59.000, 3, 500, 1, '2026-10-01 09:00:00');

INSERT IGNORE INTO shops (id, owner_id, plan_id, name, description, address, phone, opens_at, closes_at, lat, lng, status, review_note, created_at) VALUES
(1, 2,  2, 'Epicerie Lac',          'Groceries, dairy and fresh bread every morning.', 'Rue du Lac Leman, Les Berges du Lac, Tunis', '71123456', '07:30:00', '21:00:00', 36.838100, 10.242000, 'approved', NULL, '2026-10-12 11:00:00'),
(2, 5,  1, 'Boulangerie Nour',      'Traditional bread and pastries.', 'Avenue de la Liberte, Tunis', '71654321', '06:00:00', '20:00:00', 36.806500, 10.181500, 'pending', NULL, '2026-10-20 14:30:00');
