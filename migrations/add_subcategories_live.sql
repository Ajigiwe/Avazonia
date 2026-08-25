-- migrations/add_subcategories_live.sql — SAFE for production (uses slug lookup, no fixed IDs)
-- Run once in phpMyAdmin (or via `php bin/setup.php` will auto-apply SQLite version)
-- Idempotent: re-running does nothing if slugs already exist.

-- Smartphones
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Android Phones', 'android-phones', '📱', 0, 1 FROM categories p WHERE p.slug='smartphones' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='android-phones');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'iPhones', 'iphones', '🍎', 1, 1 FROM categories p WHERE p.slug='smartphones' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='iphones');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Feature Phones', 'feature-phones', '📞', 2, 1 FROM categories p WHERE p.slug='smartphones' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='feature-phones');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Phone Accessories', 'phone-accessories', '🔌', 3, 1 FROM categories p WHERE p.slug='smartphones' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='phone-accessories');

-- Laptops
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Gaming Laptops', 'gaming-laptops', '🎮', 0, 1 FROM categories p WHERE p.slug='laptops' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='gaming-laptops');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Business Laptops', 'business-laptops', '💼', 1, 1 FROM categories p WHERE p.slug='laptops' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='business-laptops');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Laptop Accessories', 'laptop-accessories', '⌨️', 2, 1 FROM categories p WHERE p.slug='laptops' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='laptop-accessories');

-- Audio (covers both 'audio' and 'audio-devices' slug variants)
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Headphones', 'headphones', '🎧', 0, 1 FROM categories p WHERE p.slug IN ('audio','audio-devices') AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='headphones') LIMIT 1;
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Speakers', 'speakers', '🔊', 1, 1 FROM categories p WHERE p.slug IN ('audio','audio-devices') AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='speakers') LIMIT 1;
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Earbuds', 'earbuds', '🎵', 2, 1 FROM categories p WHERE p.slug IN ('audio','audio-devices') AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='earbuds') LIMIT 1;

-- Wearables
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Smartwatches', 'smartwatches', '⌚', 0, 1 FROM categories p WHERE p.slug='wearables' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='smartwatches');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Fitness Trackers', 'fitness-trackers', '🏃', 1, 1 FROM categories p WHERE p.slug='wearables' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='fitness-trackers');

-- Accessories (mobile-accessories)
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Chargers', 'chargers', '🔋', 0, 1 FROM categories p WHERE p.slug='mobile-accessories' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='chargers');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Cases & Covers', 'cases-covers', '📱', 1, 1 FROM categories p WHERE p.slug='mobile-accessories' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='cases-covers');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Cables', 'cables', '🔌', 2, 1 FROM categories p WHERE p.slug='mobile-accessories' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='cables');

-- Smart Home
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Smart Lighting', 'smart-lighting', '💡', 0, 1 FROM categories p WHERE p.slug='smart-home-devices' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='smart-lighting');
INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active)
SELECT p.id, 'Security Cameras', 'security-cameras', '📷', 1, 1 FROM categories p WHERE p.slug='smart-home-devices' AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.slug='security-cameras');
