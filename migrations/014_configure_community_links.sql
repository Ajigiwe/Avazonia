-- migrations/014_configure_community_links.sql
-- Configure the community invitations supplied for the current production rollout.
UPDATE settings SET `value` = '1' WHERE `key` = 'community_popup_enabled';
UPDATE settings SET `value` = 'https://t.me/avazonia' WHERE `key` = 'community_telegram_link';
UPDATE settings SET `value` = 'https://wa.me/233240987670' WHERE `key` = 'community_whatsapp_link';
UPDATE settings SET `value` = 'Join our Telegram community' WHERE `key` = 'community_telegram_title';
UPDATE settings SET `value` = 'Join our WhatsApp community' WHERE `key` = 'community_whatsapp_title';
UPDATE settings SET `value` = 'Get updates, new drops and offers.' WHERE `key` = 'community_telegram_text';
UPDATE settings SET `value` = 'Connect with the Avazonia community.' WHERE `key` = 'community_whatsapp_text';

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('community_popup_enabled', '1'),
  ('community_telegram_link', 'https://t.me/avazonia'),
  ('community_whatsapp_link', 'https://wa.me/233240987670'),
  ('community_telegram_title', 'Join our Telegram community'),
  ('community_whatsapp_title', 'Join our WhatsApp community'),
  ('community_telegram_text', 'Get updates, new drops and offers.'),
  ('community_whatsapp_text', 'Connect with the Avazonia community.');
