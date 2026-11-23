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
