-- migrations/017_product_ghana.sql
-- "Available in Ghana" flag: admins/sellers mark products that are stocked locally.
-- Renders as a green "IN GHANA" tag on product cards. Safe on repeat runs (column check
-- via information_schema, same pattern as 015_seller_phone_number.sql).

SET @col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'products'
    AND COLUMN_NAME = 'available_in_ghana'
);

SET @ddl = IF(
  @col_exists = 0,
  'ALTER TABLE products ADD COLUMN available_in_ghana TINYINT(1) DEFAULT 0 AFTER is_dropshipping',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
