-- Add multi-currency support to products table
-- Idempotent: all three columns already ship in docker/mysql/init/01-schema.sql,
-- so this only applies to older production dumps. MariaDB 10.4 (the target
-- engine — see docker-compose.yml / README) supports IF NOT EXISTS.
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS price_usd DECIMAL(10,2) DEFAULT NULL AFTER compare_at_price_ghs,
  ADD COLUMN IF NOT EXISTS compare_at_price_usd DECIMAL(10,2) DEFAULT NULL AFTER price_usd,
  ADD COLUMN IF NOT EXISTS currency VARCHAR(3) NOT NULL DEFAULT 'GHS' AFTER compare_at_price_usd;
