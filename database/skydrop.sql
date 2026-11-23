-- SKYDROP: the whole database (user management: tables, then demo rows).
-- Run: php database/install.php   (or import this file in phpMyAdmin into the database skydrop_mvc)

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

-- S3 Islem: activity log of the administrators' actions on accounts (who did what, to which account, when).
-- actor_id = the administrator, target_id = the account. Both become NULL when that account is deleted, so the log stays.
-- INT UNSIGNED like users.id (a foreign key needs the same type on both sides).
CREATE TABLE IF NOT EXISTS user_activity (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_id   INT UNSIGNED NULL,
    target_id  INT UNSIGNED NULL,
    action     VARCHAR(40)  NOT NULL,
    details    VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_activity_target (target_id),
    KEY idx_user_activity_created (created_at),
    CONSTRAINT fk_user_activity_actor FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_user_activity_target FOREIGN KEY (target_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupons and coupon types (S1 Achref)
-- coupon_types: what a coupon does. key_name is the fixed kind the code understands (percent, fixed amount, free
-- delivery); needs_value says whether a coupon of this kind needs a value (free delivery has none).
CREATE TABLE IF NOT EXISTS coupon_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    key_name    ENUM('percent','fixed','free_delivery') NOT NULL,
    name        VARCHAR(60)  NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    needs_value TINYINT(1)   NOT NULL DEFAULT 1,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_coupon_types_key (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- coupons: one code customers type at checkout. type_id links each coupon to its type (the JOIN of this part).
-- max_uses NULL = no total limit; per_user_limit NULL = no limit per customer.
CREATE TABLE IF NOT EXISTS coupons (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    code           VARCHAR(20)   NOT NULL,
    type_id        INT UNSIGNED  NOT NULL,
    value          DECIMAL(10,3) NULL,
    min_order      DECIMAL(10,3) NOT NULL DEFAULT 0,
    starts_at      DATE          NOT NULL,
    ends_at        DATE          NOT NULL,
    max_uses       INT UNSIGNED  NULL,
    per_user_limit INT UNSIGNED  NULL DEFAULT 1,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_by     INT UNSIGNED  NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_coupons_code (code),
    KEY idx_coupons_type (type_id),
    CONSTRAINT fk_coupons_type FOREIGN KEY (type_id) REFERENCES coupon_types (id),
    CONSTRAINT fk_coupons_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- coupon_uses: one row each time a customer uses a coupon (written by the order part through CouponController::recordUse).
CREATE TABLE IF NOT EXISTS coupon_uses (
    id        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    coupon_id INT UNSIGNED  NOT NULL,
    user_id   INT UNSIGNED  NOT NULL,
    order_id  INT UNSIGNED  NULL,
    discount  DECIMAL(10,3) NOT NULL DEFAULT 0,
    used_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_coupon_uses_coupon_user (coupon_id, user_id),
    CONSTRAINT fk_coupon_uses_coupon FOREIGN KEY (coupon_id) REFERENCES coupons (id),
    CONSTRAINT fk_coupon_uses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
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

-- S3 Islem: a few demo rows of the activity log (actor 1 = the demo admin).
INSERT IGNORE INTO user_activity (id, actor_id, target_id, action, details, created_at) VALUES
(1, 1, 5, 'created',        'Second Shop Owner (@owner2), shop_owner',        '2026-10-20 14:00:00'),
(2, 1, 4, 'role_changed',   'customer -> advertiser',                         '2026-11-05 11:20:00'),
(3, 1, 6, 'deactivated',    'Deactivated Customer (@blocked)',                '2026-11-09 10:15:00'),
(4, 1, 3, 'password_reset', 'Temporary password, changed at the next login', '2026-11-12 16:40:00');

-- Coupons and coupon types (S1 Achref): the 3 types, 4 coupons (one expired), one use by the demo customer.
INSERT IGNORE INTO coupon_types (id, key_name, name, description, needs_value, is_active) VALUES
(1, 'percent',       'Percentage off',   'Takes a percentage off the order subtotal.',        1, 1),
(2, 'fixed',         'Fixed amount off', 'Takes a fixed amount in TND off the order subtotal.', 1, 1),
(3, 'free_delivery', 'Free delivery',    'The delivery of the order is free.',                0, 1);

INSERT IGNORE INTO coupons (id, code, type_id, value, min_order, starts_at, ends_at, max_uses, per_user_limit, is_active, created_by) VALUES
(1, 'WELCOME10', 1, 10.000, 20.000, '2026-11-01', '2027-06-30', NULL, 1,    1, 1),
(2, 'SAVE5',     2,  5.000, 15.000, '2026-11-01', '2027-03-31', 200,  2,    1, 1),
(3, 'FREESHIP',  3,   NULL,  0.000, '2026-11-15', '2027-01-31', 100,  1,    1, 1),
(4, 'SUMMER25',  1, 25.000, 30.000, '2026-07-01', '2026-09-30', 50,   1,    1, 1);

INSERT IGNORE INTO coupon_uses (id, coupon_id, user_id, order_id, discount, used_at) VALUES
(1, 4, 3, NULL, 9.250, '2026-08-14 18:20:00');
