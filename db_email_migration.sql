-- db_email_migration.sql
-- Run this in phpMyAdmin or MySQL to add email verification + password reset support

-- 1. Add email verification columns to users table
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS email_verified    TINYINT(1)   DEFAULT 0   AFTER is_active,
  ADD COLUMN IF NOT EXISTS verification_token VARCHAR(100) NULL        AFTER email_verified;

-- 2. Create password_resets table
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(200) NOT NULL,
  token      VARCHAR(100) NOT NULL,
  expires_at DATETIME     NOT NULL,
  used       TINYINT(1)   DEFAULT 0,
  created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Done. No data is lost — existing users will have email_verified = 0 by default.
-- You can optionally verify all existing users automatically with:
-- UPDATE users SET email_verified = 1 WHERE created_at < NOW();
