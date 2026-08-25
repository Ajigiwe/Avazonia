-- Avazonia — Local seed data
-- Minimal but realistic catalogue so a fresh `docker compose up` is immediately usable.

-- Brands
INSERT IGNORE INTO brands (id, name, slug) VALUES
(1,'Xiaomi','xiaomi'),(2,'Samsung','samsung'),(3,'Apple','apple'),
(4,'Hoco','hoco'),(5,'Oraimo','oraimo'),(6,'Borofone','borofone'),
(7,'Rogbid','rogbid'),(8,'Colmi','colmi'),(9,'Valdus','valdus'),
(10,'Baseus','baseus'),(11,'Ugreen','ugreen'),(12,'Anker','anker'),
(13,'Joyroom','joyroom'),(14,'Mcdodo','mcdodo');

-- Categories (parents)
INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, description, sort_order, is_active) VALUES
(1, NULL, 'Smartphones', 'smartphones', '📱', 'Latest mobile devices and flagships.', 0, 1),
(2, NULL, 'Laptops', 'laptops', '💻', 'High-performance computers for work and play.', 1, 1),
(3, NULL, 'Audio', 'audio', '🎧', 'Premium sound systems and headphones.', 2, 1),
(4, NULL, 'Wearables', 'wearables', '⌚', 'Smartwatches and fitness trackers.', 3, 1),
(6, NULL, 'Accessories', 'mobile-accessories', '📦', 'Phone accessories and essentials.', 4, 1),
(9, NULL, 'Smart Home', 'smart-home-devices', '🏠', 'Smart home devices.', 5, 1);

-- Subcategories (Jiji-style drill-down — shown when tapping a parent)
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

-- Delivery zones
INSERT IGNORE INTO delivery_zones (id, name, slug, price_ghs, estimated_days_min, estimated_days_max) VALUES
(1,'Accra & Greater Accra','accra', 15.00, 1, 2),
(2,'Kumasi','kumasi', 35.00, 2, 3),
(3,'Takoradi','takoradi', 35.00, 2, 3),
(4,'Tamale','tamale', 50.00, 3, 5),
(5,'Cape Coast','cape-coast', 40.00, 2, 4),
(6,'All Other Regions','other', 50.00, 3, 5),
(7,'Store Pickup (Accra)','pickup', 0.00, 0, 0);

-- Settings (defaults — mirrors config/app.php fallbacks)
INSERT IGNORE INTO settings (`key`, `value`) VALUES
('store_name','Avazonia'),
('support_email','hello@avazonia.local'),
('whatsapp_number','233240000000'),
('announcement_text','Free Delivery on all orders over ₵500 — Limited Time Offer'),
('primary_brand_color','#E5001A'),
('grid_density','4'),
('footer_notice','© 2026 AVAZONIA GH — CRAFTED IN ACCRA'),
('min_stock_threshold','1'),
('preorder_deposit_pct','5'),
('shipping_accra','15.00'),
('shipping_kumasi','35.00'),
('shipping_others','50.00'),
('shipping_pickup','FREE'),
('shipping_free_threshold','500.00'),
('usd_to_ghs_rate','11.35'),
('store_map_address','Spintex Road, Accra, Ghana'),
('support_title','Need Help?'),
('support_subtitle','Chat with an expert'),
('support_phone','+233 240 000 000'),
('support_hours','Mon–Sat 8am–8pm'),
('footer_address','Spintex Road, Near Shell Signboard, Greater Accra, Ghana');

-- Sliders
INSERT IGNORE INTO sliders (id, heading, subheading, image_url, cta_text, cta_link, page_path, template_type, is_active, order_priority) VALUES
(1, 'SAMSUNG<br>S25 ULTRA', 'Experience the pinnacle of mobile innovation. Available now for nationwide delivery.', 'public/assets/img/s25_promo.png', 'Shop Galaxy', '/shop?cat=smartphones', '/', 'split', 1, 0),
(2, 'NEXT GEN<br>AUDIO DROP', 'Immersive soundscapes. Unbeatable bass. The new 2026 collection has arrived.', 'public/assets/img/s25_promo.png', 'Explore Sound', '/shop?cat=audio', '/', 'full-width', 1, 1);

-- Users — password for both is `Admin123!` (bcrypt cost 12)
-- admin@avazonia.local  → admin
-- customer@avazonia.local → customer (for testing checkout)
INSERT IGNORE INTO users (id, email, password_hash, full_name, phone, role, is_active, email_verified) VALUES
(1, 'admin@avazonia.local', '$2y$12$eV5zgwfWRe9Wunq5/4Ti0OJEF2w5iGg5L6ZyfxvKHHSDm4UGnkVaa', 'Avazonia Admin', '0240000000', 'admin', 1, 1),
(2, 'customer@avazonia.local', '$2y$12$eV5zgwfWRe9Wunq5/4Ti0OJEF2w5iGg5L6ZyfxvKHHSDm4UGnkVaa', 'Test Customer', '0240000001', 'customer', 1, 1);

-- Demo products (3 — enough to test every code path)
INSERT IGNORE INTO products (id, category_id, brand_id, name, slug, description, price_ghs, compare_at_price_ghs, currency, stock_qty, is_featured, is_bestseller, is_active, is_preorder) VALUES
(1, 10, 2, 'Samsung Galaxy S25 Ultra 512GB', 'samsung-s25-ultra-512gb', 'The pinnacle of mobile innovation. Titanium frame, 200MP camera, S Pen included.', 8500.00, 9500.00, 'GHS', 12, 1, 1, 1, 0),
(2, 3, 5, 'Oraimo BoomPop Pro Wireless Headphones', 'oraimo-boompop-pro', 'Hybrid noise cancellation, 60-hour playtime, fast charge. Available in Grey.', 350.00, NULL, 'GHS', 50, 1, 0, 1, 0),
(3, 6, 5, 'Oraimo PowerBox 500 50000mAh Power Bank', 'oraimo-powerbox-500', '22.5W fast charging, triple output, LED display. Charge three devices at once.', 450.00, 600.00, 'GHS', 30, 0, 1, 1, 0);

-- Product images (point at existing files so cards render without re-uploading)
INSERT IGNORE INTO product_images (id, product_id, url, is_primary, sort_order) VALUES
(1, 1, 'public/uploads/products/p_1774337531_78ce85c1.png', 1, 0),
(2, 2, 'public/uploads/products/p_1774474132_ac045d45.jpg', 1, 0),
(3, 3, 'public/uploads/products/p_1774473373_fe5b501f.jpg', 1, 0);
