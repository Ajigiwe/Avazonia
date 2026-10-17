-- 🏎️ AVAZONIA 'PRO CONSOLE' DATABASE RESTORATION
-- This script creates the missing 'sliders' table and restores high-fidelity Hero slides.

CREATE TABLE IF NOT EXISTS `sliders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `heading` varchar(255) NOT NULL,
  `subheading` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `cta_text` varchar(50) DEFAULT 'Shop Now',
  `cta_link` varchar(255) DEFAULT '/shop',
  `page_path` varchar(100) DEFAULT '/',
  `template_type` enum('split','full-width') DEFAULT 'split',
  `is_active` tinyint(1) DEFAULT 1,
  `order_priority` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 🍱 RESTORE DEFAULT HIGH-FIDELITY SLIDES
-- Guarded so re-running bin/setup.php does not duplicate the hero slides.
INSERT INTO `sliders` (heading, subheading, image_url, cta_text, cta_link, page_path, template_type, is_active, order_priority)
SELECT * FROM (
  SELECT 'SAMSUNG<br>S25 ULTRA' AS heading, 'Experience the pinnacle of mobile innovation. Available now for nationwide delivery.' AS subheading, 'public/assets/img/s25_promo.png' AS image_url, 'Shop Galaxy' AS cta_text, '/shop?cat=smartphones' AS cta_link, '/' AS page_path, 'split' AS template_type, 1 AS is_active, 0 AS order_priority
  UNION ALL
  SELECT 'NEXT GEN<br>AUDIO DROP', 'Immersive soundscapes. Unbeatable bass. The new 2026 collection has arrived.', 'public/uploads/products/p_1774474132_ac045d45.jpg', 'Explore Sound', '/shop?cat=audio', '/', 'full-width', 1, 1
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `sliders`);
