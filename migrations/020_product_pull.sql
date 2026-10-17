-- migrations/020_product_pull.sql
-- Admin bulk "pull from site": take products off the storefront so they stop being
-- live and must be re-approved before they return.
--
-- status_market alone is NOT enough to do this: listings, search suggestions and
-- count queries key off is_active, so a pulled row sets is_active=0 as well.
-- pulled_at records that an admin (rather than a seller submission) took it down.
-- That marker lets APPROVE restore is_active only for pulled rows, so a product
-- the admin deliberately hid via the edit form stays hidden even after approval.
-- Safe on repeat runs (column check via information_schema).

SET @col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'products'
    AND COLUMN_NAME = 'pulled_at'
);

SET @ddl = IF(
  @col_exists = 0,
  'ALTER TABLE products ADD COLUMN pulled_at TIMESTAMP NULL DEFAULT NULL AFTER status_market',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
