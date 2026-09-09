-- migrations/013_community_popups.sql
-- Settings are key/value rows, so this migration is safe to run repeatedly.
INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('community_popup_enabled', '1'),
  ('community_telegram_link', ''),
  ('community_whatsapp_link', ''),
  ('community_telegram_title', 'Join our Telegram community'),
  ('community_whatsapp_title', 'Join our WhatsApp community'),
  ('community_telegram_text', 'Get updates, new drops and offers.'),
  ('community_whatsapp_text', 'Connect with the Avazonia community.')
