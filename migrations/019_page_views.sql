-- 019: Server-side page-view tracking — a lightweight, privacy-friendly visit
-- log that works even when visitors block Google scripts. Written once per
-- human page view; queried by the admin dashboard traffic widget.

CREATE TABLE IF NOT EXISTS page_views (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(255) NOT NULL,
  query_string VARCHAR(255) DEFAULT NULL,
  visitor_id CHAR(32) DEFAULT NULL,
  session_id CHAR(32) DEFAULT NULL,
  referrer_host VARCHAR(190) DEFAULT NULL,
  referrer_path VARCHAR(255) DEFAULT NULL,
  referrer VARCHAR(255) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  is_new_visitor TINYINT(1) DEFAULT 0,
  viewed_at DATETIME NOT NULL,
  INDEX idx_pv_viewed (viewed_at),
  INDEX idx_pv_visitor (visitor_id, viewed_at),
  INDEX idx_pv_path (path, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
