-- migrations/012_seller_contact_channels.sql
-- Optional seller contact channels used by product enquiry buttons.
-- Safe to run repeatedly; runtime schema checks also protect older production databases.

ALTER TABLE sellers ADD COLUMN whatsapp_number VARCHAR(30) NULL;
ALTER TABLE sellers ADD COLUMN wechat_id VARCHAR(100) NULL;
