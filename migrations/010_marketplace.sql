-- migrations/010_marketplace.sql — ONE platform C2C/B2C/B2B
-- Idempotent: safe to run multiple times on MySQL/MariaDB
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- ── USERS: seller_type, buyer_type, verification_level ──────────────
-- Seller type per §5, buyer type per §6, verification per §13
-- Use TEXT/ENUM fallback via ALTER IGNORE pattern (MariaDB tolerates IF NOT EXISTS via procedure)

-- Add columns if not exists (MariaDB 10.4 has no IF NOT EXISTS for ADD COLUMN, so use conditional checks via information_schema in setup.php)
-- This file is also executed statement-by-statement by bin/setup.php which ignores errors for existing columns.

ALTER TABLE users ADD COLUMN seller_type ENUM('individual','business_retailer','wholesaler','manufacturer','international_supplier') NULL DEFAULT NULL AFTER role;
ALTER TABLE users ADD COLUMN buyer_type ENUM('individual','business') NOT NULL DEFAULT 'individual' AFTER seller_type;
ALTER TABLE users ADD COLUMN verification_level ENUM('unverified','phone_verified','business_verified','company_verified','avazonia_verified') NOT NULL DEFAULT 'unverified' AFTER buyer_type;
ALTER TABLE users ADD COLUMN country_code VARCHAR(5) DEFAULT 'GH' AFTER verification_level;
ALTER TABLE users ADD COLUMN company_name VARCHAR(200) NULL AFTER country_code;
ALTER TABLE users ADD COLUMN is_business TINYINT(1) DEFAULT 0 AFTER company_name;

-- ── SELLERS table ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sellers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  seller_type ENUM('individual','business_retailer','wholesaler','manufacturer','international_supplier') NOT NULL,
  business_name VARCHAR(200) NULL,
  slug VARCHAR(200) UNIQUE NULL,
  country_code VARCHAR(5) DEFAULT 'GH',
  city VARCHAR(100) NULL,
  region VARCHAR(100) NULL,
  verification_level ENUM('unverified','phone_verified','business_verified','company_verified','avazonia_verified') DEFAULT 'unverified',
  is_verified TINYINT(1) DEFAULT 0,
  docs JSON NULL,
  description TEXT NULL,
  logo_url VARCHAR(500) NULL,
  banner_url VARCHAR(500) NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user (user_id),
  KEY idx_seller_type (seller_type),
  KEY idx_country (country_code),
  KEY idx_verified (is_verified),
  CONSTRAINT fk_sellers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── STORES table (1:1 with sellers for V1) ───────────────────────────
CREATE TABLE IF NOT EXISTS stores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id INT UNSIGNED NOT NULL,
  slug VARCHAR(200) UNIQUE NOT NULL,
  name VARCHAR(200) NOT NULL,
  tagline VARCHAR(300) NULL,
  logo_url VARCHAR(500) NULL,
  banner_url VARCHAR(500) NULL,
  country_code VARCHAR(5) DEFAULT 'GH',
  city VARCHAR(100) NULL,
  description TEXT NULL,
  is_active TINYINT(1) DEFAULT 1,
  is_featured TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_seller (seller_id),
  KEY idx_slug (slug),
  CONSTRAINT fk_stores_seller FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PRODUCTS: marketplace columns ────────────────────────────────────
ALTER TABLE products ADD COLUMN seller_id INT UNSIGNED NULL AFTER brand_id;
ALTER TABLE products ADD COLUMN store_id INT UNSIGNED NULL AFTER seller_id;
ALTER TABLE products ADD COLUMN listing_type ENUM('retail','wholesale','rfq','export') DEFAULT 'retail' AFTER store_id;
ALTER TABLE products ADD COLUMN visibility ENUM('public','b2b_only','retail_only') DEFAULT 'public' AFTER listing_type;
ALTER TABLE products ADD COLUMN condition_type ENUM('new','used') DEFAULT 'new' AFTER visibility;
ALTER TABLE products ADD COLUMN moq INT UNSIGNED NULL AFTER condition_type;
ALTER TABLE products ADD COLUMN wholesale_price_ghs DECIMAL(10,2) NULL AFTER moq;
ALTER TABLE products ADD COLUMN fob_price_usd DECIMAL(10,2) NULL AFTER wholesale_price_ghs;
ALTER TABLE products ADD COLUMN incoterms ENUM('EXW','FOB','CIF') NULL AFTER fob_price_usd;
ALTER TABLE products ADD COLUMN production_capacity VARCHAR(100) NULL AFTER incoterms;
ALTER TABLE products ADD COLUMN oem_odm TINYINT(1) DEFAULT 0 AFTER production_capacity;
ALTER TABLE products ADD COLUMN export_markets JSON NULL AFTER oem_odm;
ALTER TABLE products ADD COLUMN certifications JSON NULL AFTER export_markets;
ALTER TABLE products ADD COLUMN location_country VARCHAR(5) DEFAULT 'GH' AFTER certifications;
ALTER TABLE products ADD COLUMN vehicle_origin ENUM('local','international_export') NULL AFTER location_country;
ALTER TABLE products ADD COLUMN status_market ENUM('draft','pending_review','active','rejected') DEFAULT 'active' AFTER vehicle_origin;
-- Allow same SKU per seller (drop global UNIQUE if exists, recreate as composite later via setup check)
-- Note: we keep sku UNIQUE for now but allow NULL; per-seller uniqueness enforced in app layer for V1 to avoid migration failures on existing data.

ALTER TABLE products ADD KEY idx_seller (seller_id);
ALTER TABLE products ADD KEY idx_store (store_id);
ALTER TABLE products ADD KEY idx_listing_type (listing_type);
ALTER TABLE products ADD KEY idx_visibility (visibility);

-- Backfill existing products to Avazonia Owned seller (will be created as user 1's seller if needed)
-- Handled in bin/setup.php seeding

-- ── ORDER ITEMS: seller attribution (V1 single checkout, attribution only) ──
ALTER TABLE order_items ADD COLUMN seller_id INT UNSIGNED NULL AFTER product_id;
ALTER TABLE order_items ADD COLUMN store_id INT UNSIGNED NULL AFTER seller_id;

-- ── RFQs table ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rfqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  seller_id INT UNSIGNED NOT NULL,
  store_id INT UNSIGNED NULL,
  qty INT UNSIGNED NOT NULL,
  specs TEXT NULL,
  destination VARCHAR(200) NULL,
  message TEXT NULL,
  status ENUM('pending','quoted','accepted','rejected','closed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_buyer (buyer_user_id),
  KEY idx_seller (seller_id),
  KEY idx_product (product_id),
  CONSTRAINT fk_rfqs_buyer FOREIGN KEY (buyer_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rfqs_seller FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CATEGORIES: expand to 9 verticals per §7 ────────────────────────
INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order, is_active) VALUES
(30, NULL, 'Vehicles', 'vehicles', '🚗', 6, 1),
(31, NULL, 'Fashion & Textiles', 'fashion-textiles', '👕', 7, 1),
(32, NULL, 'Beauty & Personal Care', 'beauty-personal-care', '💄', 8, 1),
(33, NULL, 'Health & Medical', 'health-medical', '🏥', 9, 1),
(34, NULL, 'Home & Living', 'home-living', '🏠', 10, 1),
(35, NULL, 'Energy', 'energy', '⚡', 11, 1),
(36, NULL, 'Industrial & Machinery', 'industrial-machinery', '🏭', 12, 1),
(37, NULL, 'Wholesale & General Merchandise', 'wholesale-general', '📦', 13, 1);

-- Vehicles children per §7
INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order, is_active) VALUES
(40, 30, 'Cars', 'vehicles-cars', '🚗', 0, 1),
(41, 30, 'SUVs', 'vehicles-suvs', '🚙', 1, 1),
(42, 30, 'EVs', 'vehicles-evs', '🔋', 2, 1),
(43, 30, 'Hybrid Vehicles', 'vehicles-hybrid', '⚡', 3, 1),
(44, 30, 'Motorcycles', 'vehicles-motorcycles', '🏍️', 4, 1),
(45, 30, 'Trucks', 'vehicles-trucks', '🚚', 5, 1),
(46, 30, 'Buses', 'vehicles-buses', '🚌', 6, 1),
(47, 30, 'Auto Parts', 'vehicles-auto-parts', '🔧', 7, 1),
(48, 30, 'Tyres', 'vehicles-tyres', '🛞', 8, 1),
(49, 30, 'Batteries', 'vehicles-batteries', '🔋', 9, 1),
(50, 30, 'Chargers & Accessories', 'vehicles-chargers', '🔌', 10, 1);

-- Keep existing showcase categories active

SET FOREIGN_KEY_CHECKS=1;
