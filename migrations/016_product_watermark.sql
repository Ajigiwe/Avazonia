-- migrations/016_product_watermark.sql
-- Product image anti-theft watermark: on by default; admins can switch it off in Settings.
-- Settings are key/value rows, so this migration is safe to run repeatedly.
INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('product_watermark_enabled', '1');
