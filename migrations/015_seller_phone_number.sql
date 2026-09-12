-- migrations/015_seller_phone_number.sql
-- Sellers store a callable phone number for the product-page Call button.
-- Safe on repeat runs (column check via information_schema).

SET @col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sellers'
    AND COLUMN_NAME = 'phone_number'
);

SET @ddl = IF(
  @col_exists = 0,
  'ALTER TABLE sellers ADD COLUMN phone_number VARCHAR(30) NULL AFTER wechat_id',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
DEALLOCATE PREPARE stmt;

-- Seed existing sellers: phone starts as their WhatsApp number (editable later)
UPDATE sellers
SET phone_number = whatsapp_number
WHERE (phone_number IS NULL OR phone_number = '')
  AND whatsapp_number IS NOT NULL
  AND whatsapp_number <> '';
