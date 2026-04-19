-- schema.sql
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── CATEGORIES ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT UNSIGNED NULL REFERENCES categories(id) ON DELETE SET NULL,
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
  category_id         INT UNSIGNED REFERENCES categories(id) ON DELETE SET NULL,
  brand_id            INT UNSIGNED REFERENCES brands(id) ON DELETE SET NULL,
  name                VARCHAR(200) NOT NULL,
  brand_name          VARCHAR(100),
  slug                VARCHAR(220) UNIQUE NOT NULL,
  sku                 VARCHAR(80) UNIQUE,
  description         TEXT,
  features            JSON,
  specs               JSON,
  price_ghs           DECIMAL(10,2) NOT NULL,
  compare_at_price_ghs DECIMAL(10,2) DEFAULT NULL,
  stock_qty           INT DEFAULT 0,
  is_featured         TINYINT(1) DEFAULT 0,
  is_new_arrival      TINYINT(1) DEFAULT 0,
  is_active           TINYINT(1) DEFAULT 1,
  video_url           VARCHAR(500),
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_compare_price CHECK (
    compare_at_price_ghs IS NULL OR compare_at_price_ghs > price_ghs
  ),
  FULLTEXT KEY ft_product_search (name, description, brand_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PRODUCT IMAGES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  url        VARCHAR(500) NOT NULL,
  alt_text   VARCHAR(200),
  sort_order TINYINT DEFAULT 0,
  is_primary TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PRODUCT VARIANTS ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS variants (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id          INT UNSIGNED NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  color               VARCHAR(80),
  color_hex           VARCHAR(7),
  size                VARCHAR(50),
  sku                 VARCHAR(80) UNIQUE,
  stock_qty           INT DEFAULT 0,
  price_override_ghs  DECIMAL(10,2) DEFAULT NULL,
  image_url           VARCHAR(500)
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
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(200) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(150),
  phone         VARCHAR(20),
  role          ENUM('customer','admin') DEFAULT 'customer',
  is_active     TINYINT(1) DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login    TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── ORDERS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id             INT UNSIGNED NULL REFERENCES users(id) ON DELETE SET NULL,
  order_ref           VARCHAR(20) UNIQUE NOT NULL,
  status              ENUM('pending','paid','processing','shipped','delivered','cancelled','refunded','approved','arrived','paid-full') DEFAULT 'pending',
  subtotal_ghs        DECIMAL(10,2) NOT NULL,
  shipping_ghs        DECIMAL(8,2) NOT NULL DEFAULT 0,
  discount_ghs        DECIMAL(8,2) NOT NULL DEFAULT 0,
  total_ghs           DECIMAL(10,2) NOT NULL,
  paystack_reference  VARCHAR(100) UNIQUE,
  paystack_channel    ENUM('mobile_money','card','bank') NULL,
  momo_number         VARCHAR(20),
  momo_provider       ENUM('mtn','telecel','at') NULL,
  delivery_zone_id    INT UNSIGNED REFERENCES delivery_zones(id),
  customer_name       VARCHAR(150) NOT NULL,
  customer_email      VARCHAR(200) NOT NULL,
  customer_phone      VARCHAR(20) NOT NULL,
  shipping_address    VARCHAR(300),
  shipping_city       VARCHAR(80),
  shipping_region     VARCHAR(80),
  digital_address     VARCHAR(50),
  notes               TEXT,
  is_preorder         TINYINT(1) DEFAULT 0,
  deposit_amount_ghs  DECIMAL(10,2) DEFAULT 0.00,
  balance_amount_ghs  DECIMAL(10,2) DEFAULT 0.00,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── ORDER ITEMS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id       INT UNSIGNED NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id     INT UNSIGNED REFERENCES products(id) ON DELETE SET NULL,
  variant_id     INT UNSIGNED REFERENCES variants(id) ON DELETE SET NULL,
  product_name   VARCHAR(200) NOT NULL,
  variant_label  VARCHAR(100),
  sku            VARCHAR(80),
  qty            SMALLINT UNSIGNED NOT NULL,
  unit_price_ghs DECIMAL(10,2) NOT NULL,
  is_preorder    TINYINT(1) DEFAULT 0,
  deposit_paid_ghs DECIMAL(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── REVIEWS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id       INT UNSIGNED NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  user_id          INT UNSIGNED NULL REFERENCES users(id) ON DELETE SET NULL,
  reviewer_name    VARCHAR(100) NOT NULL,
  reviewer_location VARCHAR(100),
  rating           TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  body             TEXT,
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
  user_id     INT UNSIGNED NULL REFERENCES users(id) ON DELETE SET NULL,
  action      VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NULL,
  entity_id   INT UNSIGNED NULL,
  description TEXT,
  metadata    JSON,
  ip_address  VARCHAR(45),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_action (action),
  INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed initial data
INSERT IGNORE INTO brands (name, slug) VALUES
('Xiaomi','xiaomi'),('Samsung','samsung'),('Apple','apple'),
('Hoco','hoco'),('Oraimo','oraimo'),('Borofone','borofone'),
('Rogbid','rogbid'),('Colmi','colmi'),('Valdus','valdus'),
('Baseus','baseus'),('Ugreen','ugreen'),('Anker','anker'),
('Joyroom','joyroom'),('Mcdodo','mcdodo');

INSERT IGNORE INTO delivery_zones (name, slug, price_ghs, estimated_days_min, estimated_days_max) VALUES
('Accra & Greater Accra','accra', 15.00, 1, 2),
('Kumasi', 'kumasi', 35.00, 2, 3),
('Takoradi', 'takoradi', 35.00, 2, 3),
('Tamale', 'tamale', 50.00, 3, 5),
('Cape Coast', 'cape-coast', 40.00, 2, 4),
('All Other Regions', 'other', 50.00, 3, 5),
('Store Pickup (Accra)', 'pickup', 0.00, 0, 0);

SET FOREIGN_KEY_CHECKS = 1;
