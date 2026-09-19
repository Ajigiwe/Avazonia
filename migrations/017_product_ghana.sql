-- migrations/017_product_ghana.sql
-- "Available in Ghana" flag: admins/sellers mark products that are stocked locally.
-- Renders as a green "IN GHANA" tag on product cards.
-- Single plain statement: re-runs fail with "Duplicate column", which both the
-- migration runner and bin/setup.php treat as already-applied (idempotent).

ALTER TABLE products ADD COLUMN available_in_ghana TINYINT(1) DEFAULT 0 AFTER is_dropshipping;
