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

-- Cart and orders (S4 Zamel): one order = one customer and one shop; its lines are in order_items (the cart itself
-- lives in the session, no table). The name and price of each product are copied into order_items when the order is
-- placed, so an old order keeps showing what was really bought even if the product changes later.
CREATE TABLE IF NOT EXISTS orders (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    customer_id    INT UNSIGNED  NOT NULL,
    shop_id        INT UNSIGNED  NOT NULL,
    status         ENUM('placed', 'preparing', 'ready', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed',
    subtotal       DECIMAL(10,3) NOT NULL,
    discount       DECIMAL(10,3) NOT NULL DEFAULT 0,
    total          DECIMAL(10,3) NOT NULL,
    coupon_code    VARCHAR(30)   NULL,
    payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    note           VARCHAR(300)  NULL,
    placed_at      DATETIME      NOT NULL,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_orders_customer (customer_id),
    KEY idx_orders_shop (shop_id, status),
    CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES users (id),
    CONSTRAINT fk_orders_shop FOREIGN KEY (shop_id) REFERENCES shops (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    order_id     INT UNSIGNED  NOT NULL,
    product_id   INT UNSIGNED  NOT NULL,
    product_name VARCHAR(120)  NOT NULL,
    unit_price   DECIMAL(10,3) NOT NULL,
    quantity     INT           NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id)
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

-- Cart and orders (S4 Zamel): two orders of the demo customer (id 3) in shop 1, with lines of products 1 to 3.
INSERT IGNORE INTO orders (id, customer_id, shop_id, status, subtotal, discount, total, coupon_code, payment_status, note, placed_at) VALUES
(1, 3, 1, 'delivered', 3.500, 0, 3.500, NULL, 'paid', 'Please ring twice.', '2026-11-10 10:15:00'),
(2, 3, 1, 'placed', 3.750, 0, 3.750, NULL, 'unpaid', NULL, '2026-11-25 18:40:00');
INSERT IGNORE INTO order_items (id, order_id, product_id, product_name, unit_price, quantity) VALUES
(1, 1, 1, 'Fresh milk 1 L', 1.350, 2),
(2, 1, 2, 'Baguette', 0.200, 4),
(3, 2, 3, 'Eggs, box of 6', 2.400, 1),
(4, 2, 1, 'Fresh milk 1 L', 1.350, 1);
