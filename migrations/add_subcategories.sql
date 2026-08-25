-- migrations/add_subcategories.sql
-- Jiji-style drill-down: add subcategories for existing parents
-- Idempotent: safe to run multiple times (INSERT IGNORE)
-- Run on production via phpMyAdmin or: mysql -u USER -p DB < migrations/add_subcategories.sql

INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order, is_active) VALUES
(10, 1, 'Android Phones', 'android-phones', '📱', 0, 1),
(11, 1, 'iPhones', 'iphones', '🍎', 1, 1),
(12, 1, 'Feature Phones', 'feature-phones', '📞', 2, 1),
(13, 1, 'Phone Accessories', 'phone-accessories', '🔌', 3, 1),
(14, 2, 'Gaming Laptops', 'gaming-laptops', '🎮', 0, 1),
(15, 2, 'Business Laptops', 'business-laptops', '💼', 1, 1),
(16, 2, 'Laptop Accessories', 'laptop-accessories', '⌨️', 2, 1),
(17, 3, 'Headphones', 'headphones', '🎧', 0, 1),
(18, 3, 'Speakers', 'speakers', '🔊', 1, 1),
(19, 3, 'Earbuds', 'earbuds', '🎵', 2, 1),
(20, 4, 'Smartwatches', 'smartwatches', '⌚', 0, 1),
(21, 4, 'Fitness Trackers', 'fitness-trackers', '🏃', 1, 1),
(22, 6, 'Chargers', 'chargers', '🔋', 0, 1),
(23, 6, 'Cases & Covers', 'cases-covers', '📱', 1, 1),
(24, 6, 'Cables', 'cables', '🔌', 2, 1),
(25, 9, 'Smart Lighting', 'smart-lighting', '💡', 0, 1),
(26, 9, 'Security Cameras', 'security-cameras', '📷', 1, 1);

-- Optional: move one demo product to a subcategory to show count (uncomment if you want)
-- UPDATE products SET category_id = 10 WHERE id = 1 AND category_id = 1;
