-- Add multi-currency support to products table
ALTER TABLE products
  ADD COLUMN price_usd DECIMAL(10,2) DEFAULT NULL AFTER compare_at_price_ghs,
  ADD COLUMN compare_at_price_usd DECIMAL(10,2) DEFAULT NULL AFTER price_usd,
  ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'GHS' AFTER compare_at_price_usd;
