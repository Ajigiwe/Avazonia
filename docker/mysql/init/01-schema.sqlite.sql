-- Avazonia — SQLite schema (fallback for local dev without MySQL)
-- Used when config/database.php falls back to storage/database.sqlite

PRAGMA foreign_keys = OFF;

CREATE TABLE IF NOT EXISTS categories (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  parent_id   INTEGER,
  name        TEXT NOT NULL,
  slug        TEXT UNIQUE NOT NULL,
  icon        TEXT DEFAULT '📦',
  description TEXT,
  image_url   TEXT,
  sort_order  INTEGER DEFAULT 0,
  is_active   INTEGER DEFAULT 1,
  created_at  TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS brands (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  name       TEXT NOT NULL,
  slug       TEXT UNIQUE NOT NULL,
  logo_url   TEXT,
  is_active  INTEGER DEFAULT 1,
  sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS products (
  id                  INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id         INTEGER,
  brand_id            INTEGER,
  seller_id           INTEGER,
  store_id            INTEGER,
  name                TEXT NOT NULL,
  brand_name          TEXT,
  slug                TEXT UNIQUE NOT NULL,
  sku                 TEXT UNIQUE,
  description         TEXT,
  tags                TEXT,
  meta_title          TEXT,
  meta_description    TEXT,
  meta_keywords       TEXT,
  features            TEXT,
  specs               TEXT,
  price_ghs           REAL NOT NULL DEFAULT 0,
  compare_at_price_ghs REAL,
  price_usd           REAL,
  compare_at_price_usd REAL,
  currency            TEXT NOT NULL DEFAULT 'GHS',
  stock_qty           INTEGER DEFAULT 0,
  is_featured         INTEGER DEFAULT 0,
  is_new_arrival      INTEGER DEFAULT 0,
  is_bestseller       INTEGER DEFAULT 0,
  is_active           INTEGER DEFAULT 1,
  video_url           TEXT,
  created_at          TEXT DEFAULT (datetime('now')),
  updated_at          TEXT DEFAULT (datetime('now')),
  is_preorder         INTEGER DEFAULT 0,
  is_dropshipping     INTEGER DEFAULT 0,
  lead_time_days      INTEGER,
  listing_type        TEXT DEFAULT 'retail',
  visibility          TEXT DEFAULT 'public',
  condition_type      TEXT DEFAULT 'new',
  moq                 INTEGER,
  wholesale_price_ghs REAL,
  fob_price_usd       REAL,
  incoterms           TEXT,
  production_capacity TEXT,
  oem_odm             INTEGER DEFAULT 0,
  export_markets      TEXT,
  certifications      TEXT,
  location_country    TEXT DEFAULT 'GH',
  vehicle_origin      TEXT,
  status_market       TEXT DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS product_images (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  url        TEXT NOT NULL,
  alt_text   TEXT,
  sort_order INTEGER DEFAULT 0,
  is_primary INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS variants (
  id                  INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id          INTEGER NOT NULL,
  color               TEXT,
  color_hex           TEXT,
  size                TEXT,
  sku                 TEXT UNIQUE,
  stock_qty           INTEGER DEFAULT 0,
  price_override_ghs  REAL,
  image_url           TEXT
);

CREATE TABLE IF NOT EXISTS delivery_zones (
  id                 INTEGER PRIMARY KEY AUTOINCREMENT,
  name               TEXT NOT NULL,
  slug               TEXT UNIQUE NOT NULL,
  price_ghs          REAL NOT NULL,
  estimated_days_min INTEGER DEFAULT 1,
  estimated_days_max INTEGER DEFAULT 5,
  is_active          INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS users (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  email             TEXT UNIQUE NOT NULL,
  password_hash     TEXT NOT NULL,
  full_name         TEXT,
  phone             TEXT,
  role              TEXT DEFAULT 'customer',
  seller_type       TEXT,
  buyer_type        TEXT DEFAULT 'individual',
  verification_level TEXT DEFAULT 'unverified',
  country_code      TEXT DEFAULT 'GH',
  company_name      TEXT,
  is_business       INTEGER DEFAULT 0,
  is_active         INTEGER DEFAULT 1,
  email_verified    INTEGER DEFAULT 0,
  verification_token TEXT,
  created_at        TEXT DEFAULT (datetime('now')),
  last_login        TEXT
);

CREATE TABLE IF NOT EXISTS orders (
  id                  INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id             INTEGER,
  order_ref           TEXT UNIQUE NOT NULL,
  status              TEXT DEFAULT 'pending',
  subtotal_ghs        REAL NOT NULL,
  shipping_ghs        REAL NOT NULL DEFAULT 0,
  discount_ghs        REAL NOT NULL DEFAULT 0,
  total_ghs           REAL NOT NULL,
  paystack_reference  TEXT UNIQUE,
  paystack_channel    TEXT,
  momo_number         TEXT,
  momo_provider       TEXT,
  delivery_zone_id    INTEGER,
  payment_method      TEXT DEFAULT 'paystack',
  payment_status      TEXT DEFAULT 'unpaid',
  customer_name       TEXT NOT NULL,
  customer_email      TEXT NOT NULL,
  customer_phone      TEXT NOT NULL,
  shipping_address    TEXT,
  shipping_city       TEXT,
  shipping_region     TEXT,
  digital_address     TEXT,
  notes               TEXT,
  is_preorder         INTEGER DEFAULT 0,
  deposit_amount_ghs  REAL DEFAULT 0,
  balance_amount_ghs  REAL DEFAULT 0,
  created_at          TEXT DEFAULT (datetime('now')),
  updated_at          TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS order_items (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id       INTEGER NOT NULL,
  product_id     INTEGER,
  seller_id      INTEGER,
  store_id       INTEGER,
  variant_id     INTEGER,
  product_name   TEXT NOT NULL,
  variant_label  TEXT,
  sku            TEXT,
  qty            INTEGER NOT NULL,
  unit_price_ghs REAL NOT NULL,
  is_preorder    INTEGER DEFAULT 0,
  deposit_paid_ghs REAL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS reviews (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id       INTEGER NOT NULL,
  user_id          INTEGER,
  reviewer_name    TEXT NOT NULL,
  reviewer_location TEXT,
  rating           INTEGER NOT NULL,
  body             TEXT,
  verified_purchase INTEGER DEFAULT 0,
  is_approved      INTEGER DEFAULT 0,
  created_at       TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS promo_codes (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  code             TEXT UNIQUE NOT NULL,
  discount_type    TEXT DEFAULT 'percent',
  discount_value   REAL NOT NULL,
  min_order_ghs    REAL DEFAULT 0,
  max_uses         INTEGER,
  current_uses     INTEGER DEFAULT 0,
  expires_at       TEXT,
  is_active        INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS system_logs (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id     INTEGER,
  action      TEXT NOT NULL,
  entity_type TEXT,
  entity_id   INTEGER,
  description TEXT NOT NULL,
  metadata    TEXT,
  ip_address  TEXT,
  created_at  TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_action ON system_logs(action);
CREATE INDEX IF NOT EXISTS idx_entity ON system_logs(entity_type, entity_id);

CREATE TABLE IF NOT EXISTS settings (
  key        TEXT PRIMARY KEY,
  value      TEXT,
  updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS password_resets (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  email      TEXT NOT NULL,
  token      TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  used       INTEGER DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_token ON password_resets(token);
CREATE INDEX IF NOT EXISTS idx_email ON password_resets(email);

CREATE TABLE IF NOT EXISTS sliders (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  heading        TEXT NOT NULL,
  subheading     TEXT,
  image_url      TEXT,
  cta_text       TEXT DEFAULT 'Shop Now',
  cta_link       TEXT DEFAULT '/shop',
  page_path      TEXT DEFAULT '/',
  template_type  TEXT DEFAULT 'split',
  is_active      INTEGER DEFAULT 1,
  order_priority INTEGER DEFAULT 0,
  created_at     TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS wishlist (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  created_at TEXT DEFAULT (datetime('now')),
  UNIQUE(user_id, product_id)
);

CREATE TABLE IF NOT EXISTS notifications (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  type       TEXT NOT NULL,
  message    TEXT NOT NULL,
  data       TEXT,
  is_read    INTEGER DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  email      TEXT UNIQUE NOT NULL,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sellers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  seller_type TEXT NOT NULL,
  business_name TEXT,
  slug TEXT UNIQUE,
  country_code TEXT DEFAULT 'GH',
  city TEXT,
  region TEXT,
  verification_level TEXT DEFAULT 'unverified',
  is_verified INTEGER DEFAULT 0,
  docs TEXT,
  description TEXT,
  logo_url TEXT,
  banner_url TEXT,
  is_active INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS stores (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  seller_id INTEGER NOT NULL UNIQUE,
  slug TEXT UNIQUE NOT NULL,
  name TEXT NOT NULL,
  tagline TEXT,
  logo_url TEXT,
  banner_url TEXT,
  country_code TEXT DEFAULT 'GH',
  city TEXT,
  description TEXT,
  is_active INTEGER DEFAULT 1,
  is_featured INTEGER DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS rfqs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  buyer_user_id INTEGER NOT NULL,
  product_id INTEGER,
  seller_id INTEGER NOT NULL,
  store_id INTEGER,
  qty INTEGER NOT NULL,
  specs TEXT,
  destination TEXT,
  message TEXT,
  status TEXT DEFAULT 'pending',
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

-- Marketplace additions to products/users (added via ALTER in MySQL, pre-added here for fresh SQLite)
-- users additions
-- products additions handled via seed migration: columns added below if fresh DB needs them via CREATE, else ALTER fallback

CREATE TABLE IF NOT EXISTS schema_migrations (
  version    TEXT PRIMARY KEY,
  applied_at TEXT DEFAULT (datetime('now'))
);
