-- migrations/011_rfq_quotes.sql — RFQ quote replies + message thread
-- Idempotent: safe to run multiple times on MySQL/MariaDB
SET NAMES utf8mb4;

-- ── RFQS: quote columns ──────────────────────────────────────────────
-- MariaDB 10.4 has no ADD COLUMN IF NOT EXISTS; bin/setup.php runs
-- statements one-by-one and ignores duplicate-column errors.

ALTER TABLE rfqs ADD COLUMN quote_unit_price DECIMAL(12,2) NULL AFTER status;
ALTER TABLE rfqs ADD COLUMN quote_qty INT UNSIGNED NULL AFTER quote_unit_price;
ALTER TABLE rfqs ADD COLUMN quote_lead_time_days INT UNSIGNED NULL AFTER quote_qty;
ALTER TABLE rfqs ADD COLUMN quote_note TEXT NULL AFTER quote_lead_time_days;
ALTER TABLE rfqs ADD COLUMN quote_at TIMESTAMP NULL DEFAULT NULL AFTER quote_note;

-- ── RFQ message thread ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rfq_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rfq_id INT UNSIGNED NOT NULL,
  sender_user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rfq (rfq_id),
  CONSTRAINT fk_rfqm_rfq FOREIGN KEY (rfq_id) REFERENCES rfqs(id) ON DELETE CASCADE,
  CONSTRAINT fk_rfqm_user FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
