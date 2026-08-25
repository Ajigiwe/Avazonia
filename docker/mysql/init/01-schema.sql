-- Avazonia — Local DB schema (consolidated)
-- MariaDB 10.4 compatible. Derived from schema.sql + production deltas + missing tables.
-- This file is run automatically by MariaDB's /docker-entrypoint-initdb.d on first `docker compose up`.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── CATEGORIES ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT UNSIGNED NULL,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) UNIQUE NOT NULL,
  icon        VARCHAR(10) DEFAULT '📦',
  description TEXT,
  image_url   VARCHAR(500),
  sort_order  TINYINT UNSIGNED DEFAULT 0,
  is_active   TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── BRANDS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS brands (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  slug       VARCHAR(120) UNIQUE NOT NULL,
  logo_url   VARCHAR(500),
  is_active  TINYINT(1) DEFAULT 1,
  sort_order TINYINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PRODUCTS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id         INT UNSIGNED NULL,
  brand_id            INT UNSIGNED NULL,
  name                VARCHAR(200) NOT NULL,
  brand_name          VARCHAR(100) DEFAULT NULL,
  slug                VARCHAR(220) UNIQUE NOT NULL,
  sku                 VARCHAR(80) UNIQUE DEFAULT NULL,
  description         TEXT DEFAULT NULL,
  tags                TEXT DEFAULT NULL,
  meta_title          VARCHAR(255) DEFAULT NULL,
  meta_description    TEXT DEFAULT NULL,
  meta_keywords       TEXT DEFAULT NULL,
  features            LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  specs               LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  price_ghs           DECIMAL(10,2) NOT NULL DEFAULT 0,
  compare_at_price_ghs DECIMAL(10,2) DEFAULT NULL,
  price_usd           DECIMAL(10,2) DEFAULT NULL,
  compare_at_price_usd DECIMAL(10,2) DEFAULT NULL,
  currency            VARCHAR(3) NOT NULL DEFAULT 'GHS',
  stock_qty           INT DEFAULT 0,
  is_featured         TINYINT(1) DEFAULT 0,
  is_new_arrival      TINYINT(1) DEFAULT 0,
  is_bestseller       TINYINT(1) DEFAULT 0,
  is_active           TINYINT(1) DEFAULT 1,
  video_url           VARCHAR(500) DEFAULT NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  is_preorder         TINYINT(1) DEFAULT 0,
  is_dropshipping     TINYINT(1) DEFAULT 0,
  lead_time_days      INT DEFAULT NULL,
  CONSTRAINT chk_compare_price CHECK (
    (currency = 'GHS' AND (compare_at_price_ghs IS NULL OR compare_at_price_ghs > price_ghs))
    OR
    (currency = 'USD' AND (compare_at_price_usd IS NULL OR compare_at_price_usd > price_usd))
  ),
  FULLTEXT KEY ft_product_search (name, description, brand_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PRODUCT IMAGES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  url        VARCHAR(500) NOT NULL,
  alt_text   VARCHAR(200) DEFAULT NULL,
  sort_order TINYINT DEFAULT 0,
  is_primary TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PRODUCT VARIANTS ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS variants (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id          INT UNSIGNED NOT NULL,
  color               VARCHAR(80) DEFAULT NULL,
  color_hex           VARCHAR(7) DEFAULT NULL,
  size                VARCHAR(50) DEFAULT NULL,
  sku                 VARCHAR(80) UNIQUE DEFAULT NULL,
  stock_qty           INT DEFAULT 0,
  price_override_ghs  DECIMAL(10,2) DEFAULT NULL,
  image_url           VARCHAR(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── DELIVERY ZONES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS delivery_zones (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name               VARCHAR(150) NOT NULL,
  slug               VARCHAR(100) UNIQUE NOT NULL,
  price_ghs          DECIMAL(8,2) NOT NULL,
  estimated_days_min TINYINT UNSIGNED DEFAULT 1,
  estimated_days_max TINYINT UNSIGNED DEFAULT 5,
  is_active          TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── USERS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email             VARCHAR(200) UNIQUE NOT NULL,
  password_hash     VARCHAR(255) NOT NULL,
  full_name         VARCHAR(150) DEFAULT NULL,
  phone             VARCHAR(20) DEFAULT NULL,
  role              ENUM('customer','admin') DEFAULT 'customer',
  is_active         TINYINT(1) DEFAULT 1,
  email_verified    TINYINT(1) DEFAULT 0,
  verification_token VARCHAR(100) DEFAULT NULL,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login        TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── ORDERS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id             INT UNSIGNED NULL,
  order_ref           VARCHAR(20) UNIQUE NOT NULL,
  status              ENUM('pending','paid','processing','shipped','delivered','cancelled','refunded','approved','arrived','paid-full') DEFAULT 'pending',
  subtotal_ghs        DECIMAL(10,2) NOT NULL,
  shipping_ghs        DECIMAL(8,2) NOT NULL DEFAULT 0,
  discount_ghs        DECIMAL(8,2) NOT NULL DEFAULT 0,
  total_ghs           DECIMAL(10,2) NOT NULL,
  paystack_reference  VARCHAR(100) UNIQUE DEFAULT NULL,
  paystack_channel    ENUM('mobile_money','card','bank') NULL DEFAULT NULL,
  momo_number         VARCHAR(20) DEFAULT NULL,
  momo_provider       ENUM('mtn','telecel','at') NULL DEFAULT NULL,
  delivery_zone_id    INT UNSIGNED NULL,
  payment_method      VARCHAR(50) DEFAULT 'paystack',
  payment_status      VARCHAR(50) DEFAULT 'unpaid',
  customer_name       VARCHAR(150) NOT NULL,
  customer_email      VARCHAR(200) NOT NULL,
  customer_phone      VARCHAR(20) NOT NULL,
  shipping_address    VARCHAR(300) DEFAULT NULL,
  shipping_city       VARCHAR(80) DEFAULT NULL,
  shipping_region     VARCHAR(80) DEFAULT NULL,
  digital_address     VARCHAR(50) DEFAULT NULL,
  notes               TEXT DEFAULT NULL,
  is_preorder         TINYINT(1) DEFAULT 0,
  deposit_amount_ghs  DECIMAL(10,2) DEFAULT 0.00,
  balance_amount_ghs  DECIMAL(10,2) DEFAULT 0.00,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── ORDER ITEMS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id       INT UNSIGNED NOT NULL,
  product_id     INT UNSIGNED NULL,
  variant_id     INT UNSIGNED NULL,
  product_name   VARCHAR(200) NOT NULL,
  variant_label  VARCHAR(100) DEFAULT NULL,
  sku            VARCHAR(80) DEFAULT NULL,
  qty            SMALLINT UNSIGNED NOT NULL,
  unit_price_ghs DECIMAL(10,2) NOT NULL,
  is_preorder    TINYINT(1) DEFAULT 0,
  deposit_paid_ghs DECIMAL(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── REVIEWS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id       INT UNSIGNED NOT NULL,
  user_id          INT UNSIGNED NULL,
  reviewer_name    VARCHAR(100) NOT NULL,
  reviewer_location VARCHAR(100) DEFAULT NULL,
  rating           TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  body             TEXT DEFAULT NULL,
  verified_purchase TINYINT(1) DEFAULT 0,
  is_approved      TINYINT(1) DEFAULT 0,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PROMO CODES ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS promo_codes (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code             VARCHAR(50) UNIQUE NOT NULL,
  discount_type    ENUM('percent','fixed') DEFAULT 'percent',
  discount_value   DECIMAL(8,2) NOT NULL,
  min_order_ghs    DECIMAL(10,2) DEFAULT 0,
  max_uses         INT DEFAULT NULL,
  current_uses     INT DEFAULT 0,
  expires_at       DATETIME DEFAULT NULL,
  is_active        TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SYSTEM LOGS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL,
  action      VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NULL,
  entity_id   INT UNSIGNED NULL,
  description TEXT NOT NULL,
  metadata    LONGTEXT DEFAULT NULL,
  ip_address  VARCHAR(45) DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_action (action),
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SETTINGS (key/value) ──────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
  `key`      VARCHAR(100) NOT NULL PRIMARY KEY,
  `value`    TEXT DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PASSWORD RESETS ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(200) NOT NULL,
  token      VARCHAR(100) NOT NULL,
  expires_at DATETIME     NOT NULL,
  used       TINYINT(1)   DEFAULT 0,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SLIDERS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sliders (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  heading        VARCHAR(255) NOT NULL,
  subheading     TEXT DEFAULT NULL,
  image_url      VARCHAR(500) DEFAULT NULL,
  cta_text       VARCHAR(50) DEFAULT 'Shop Now',
  cta_link       VARCHAR(255) DEFAULT '/shop',
  page_path      VARCHAR(100) DEFAULT '/',
  template_type  ENUM('split','full-width') DEFAULT 'split',
  is_active      TINYINT(1) DEFAULT 1,
  order_priority INT DEFAULT 0,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── WISHLIST ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wishlist (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY user_prod_idx (user_id, product_id),
  KEY product_id (product_id),
  CONSTRAINT wishlist_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT wishlist_ibfk_2 FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── NOTIFICATIONS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type       VARCHAR(50) NOT NULL,
  message    TEXT NOT NULL,
  data       JSON DEFAULT NULL,
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_is_read (is_read),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── NEWSLETTER SUBSCRIPTIONS ──────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(255) UNIQUE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SCHEMA MIGRATIONS (for future) ────────────────────────
CREATE TABLE IF NOT EXISTS schema_migrations (
  version    VARCHAR(50) NOT NULL PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
