-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: avazonia
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Xiaomi','xiaomi',NULL,1,0),(2,'Samsung','samsung',NULL,1,0),(3,'Apple','apple',NULL,1,0),(4,'Hoco','hoco',NULL,1,0),(5,'Oraimo','oraimo',NULL,1,0),(6,'Borofone','borofone',NULL,1,0),(7,'Rogbid','rogbid',NULL,1,0),(8,'Colmi','colmi',NULL,1,0),(9,'Valdus','valdus',NULL,1,0),(10,'Baseus','baseus',NULL,1,0),(11,'Ugreen','ugreen',NULL,1,0),(12,'Anker','anker',NULL,1,0),(13,'Joyroom','joyroom',NULL,1,0),(14,'Mcdodo','mcdodo',NULL,1,0),(15,'Dell','dell','',1,0),(16,'Lenovo','lenovo','',1,0),(17,'Asus','asus','',1,0);
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `icon` varchar(10) DEFAULT '?',
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `sort_order` tinyint(3) unsigned DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Smartphones','smartphones','≡ƒô▒','Latest mobile devices and flagships.',NULL,0,1,'2026-03-24 07:41:26'),(2,NULL,'Laptops','laptops','≡ƒÆ╗','High-performance computers for work and play.',NULL,0,1,'2026-03-24 07:41:26'),(3,NULL,'Audio','audio','≡ƒÄº','Premium sound systems and headphones.',NULL,0,1,'2026-03-24 07:41:26'),(4,NULL,'Wearables','wearables','ΓîÜ','Smartwatches and fitness trackers.',NULL,0,1,'2026-03-24 07:41:26'),(6,NULL,'Accessories','mobile-accessories','≡ƒôª',NULL,NULL,0,1,'2026-03-26 05:50:34'),(9,NULL,'Smart Home','smart-home-devices','≡ƒôª',NULL,NULL,0,1,'2026-03-26 05:50:34');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_zones`
--

DROP TABLE IF EXISTS `delivery_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_zones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `price_ghs` decimal(8,2) NOT NULL,
  `estimated_days_min` tinyint(3) unsigned DEFAULT 1,
  `estimated_days_max` tinyint(3) unsigned DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_zones`
--

LOCK TABLES `delivery_zones` WRITE;
/*!40000 ALTER TABLE `delivery_zones` DISABLE KEYS */;
INSERT INTO `delivery_zones` VALUES (1,'Accra & Greater Accra','accra',15.00,1,2,1),(2,'Kumasi','kumasi',35.00,2,3,1),(3,'Takoradi','takoradi',35.00,2,3,1),(4,'Tamale','tamale',50.00,3,5,1),(5,'Cape Coast','cape-coast',40.00,2,4,1),(6,'All Other Regions','other',50.00,3,5,1),(7,'Store Pickup (Accra)','pickup',0.00,0,0,1);
/*!40000 ALTER TABLE `delivery_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `variant_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `variant_label` varchar(100) DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `qty` smallint(5) unsigned NOT NULL,
  `unit_price_ghs` decimal(10,2) NOT NULL,
  `is_preorder` tinyint(1) DEFAULT 0,
  `deposit_paid_ghs` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,1,NULL,'Samsung s25 ultra',NULL,NULL,1,8000.00,0,0.00),(2,2,2,NULL,'IPhone 16 Pro Max',NULL,NULL,1,20000.00,0,0.00),(3,2,5,NULL,'Oraimo Power Box 500 50000mah 22.5w Power Bank',NULL,NULL,1,450.00,0,0.00),(4,3,2,NULL,'IPhone 16 Pro Max',NULL,NULL,1,20000.00,0,0.00),(5,3,5,NULL,'Oraimo Power Box 500 50000mah 22.5w Power Bank',NULL,NULL,1,450.00,0,0.00),(6,4,8,NULL,'ORAIMO WIRELESS HEADSET STRONG BASS BLACK OEB-E33',NULL,NULL,1,150.00,0,0.00),(7,5,8,NULL,'ORAIMO WIRELESS HEADSET STRONG BASS BLACK OEB-E33',NULL,NULL,1,150.00,0,0.00),(8,6,8,NULL,'ORAIMO WIRELESS HEADSET STRONG BASS BLACK OEB-E33',NULL,NULL,1,150.00,0,0.00),(9,7,12,NULL,'Samsung s25 ultra new',NULL,NULL,2,5000.00,1,1000.00),(10,8,12,NULL,'Samsung s25 ultra new',NULL,NULL,1,5000.00,1,500.00),(11,9,9,NULL,'Dell Gaming Laptop',NULL,NULL,1,10000.00,0,0.00),(12,9,12,NULL,'Samsung s25 ultra new (Silver, 128GB)',NULL,NULL,1,1000.00,0,0.00),(13,10,12,NULL,'Samsung s25 ultra new (Silver, 128GB)',NULL,NULL,2,1000.00,1,100.00),(14,11,9,NULL,'Dell Gaming Laptop',NULL,NULL,1,10000.00,0,0.00),(15,12,8,NULL,'ORAIMO WIRELESS HEADSET STRONG BASS BLACK OEB-E33',NULL,NULL,1,150.00,0,0.00),(16,13,5,NULL,'Oraimo Power Box 500 50000mah 22.5w Power Bank',NULL,NULL,1,450.00,0,0.00),(17,14,12,NULL,'Samsung s25 ultra new (Silver, 128GB)',NULL,NULL,1,1000.00,1,50.00),(18,15,2,NULL,'IPhone 16 Pro Max',NULL,NULL,1,20000.00,0,0.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `is_preorder` tinyint(1) DEFAULT 0,
  `order_ref` varchar(20) NOT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled','refunded','arrived','paid-full') DEFAULT 'pending',
  `subtotal_ghs` decimal(10,2) NOT NULL,
  `shipping_ghs` decimal(8,2) NOT NULL DEFAULT 0.00,
  `discount_ghs` decimal(8,2) NOT NULL DEFAULT 0.00,
  `total_ghs` decimal(10,2) NOT NULL,
  `deposit_amount_ghs` decimal(10,2) DEFAULT 0.00,
  `balance_amount_ghs` decimal(10,2) DEFAULT 0.00,
  `paystack_reference` varchar(100) DEFAULT NULL,
  `paystack_channel` enum('mobile_money','card','bank') DEFAULT NULL,
  `momo_number` varchar(20) DEFAULT NULL,
  `momo_provider` enum('mtn','telecel','at') DEFAULT NULL,
  `delivery_zone_id` int(10) unsigned DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'paystack',
  `payment_status` varchar(50) DEFAULT 'unpaid',
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(200) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `shipping_address` varchar(300) DEFAULT NULL,
  `shipping_city` varchar(80) DEFAULT NULL,
  `shipping_region` varchar(80) DEFAULT NULL,
  `digital_address` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_ref` (`order_ref`),
  UNIQUE KEY `paystack_reference` (`paystack_reference`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,0,'NX-2DA8F139','delivered',8000.00,15.00,0.00,7215.00,0.00,0.00,NULL,NULL,NULL,NULL,1,'paystack','unpaid','Emmanuel Ajigiwe Atio','minatoflash82@gmail.com','0235750693','Old john','Takoradi','western',NULL,NULL,'2026-03-25 05:08:50','2026-03-25 19:32:40'),(2,2,0,'NX-CA9F9B32','delivered',20450.00,15.00,0.00,18420.00,0.00,0.00,NULL,NULL,NULL,NULL,1,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','western',NULL,NULL,'2026-03-25 23:34:04','2026-03-27 04:48:38'),(3,2,0,'NX-0543B170','cancelled',20450.00,15.00,0.00,18420.00,0.00,0.00,NULL,NULL,NULL,NULL,1,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','western',NULL,NULL,'2026-03-25 23:34:24','2026-03-27 04:48:45'),(4,2,0,'NX-9357BF27','',150.00,15.00,0.00,150.00,0.00,0.00,NULL,NULL,NULL,NULL,1,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-03-26 01:04:09','2026-03-27 04:48:51'),(5,2,0,'NX-45C02881','',150.00,15.00,0.00,150.00,0.00,0.00,NULL,NULL,NULL,NULL,1,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-03-26 01:07:48','2026-03-27 04:49:05'),(6,2,0,'NX-BB79D626','refunded',150.00,15.00,0.00,150.00,0.00,0.00,'NX-BB79D626',NULL,NULL,NULL,1,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-03-26 01:12:27','2026-03-30 23:47:33'),(7,2,1,'NX-AEF84E64','refunded',10000.00,45.00,0.00,10045.00,1045.00,0.00,'NX-AEF84E64',NULL,NULL,NULL,3,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-03-26 04:48:58','2026-03-31 02:08:05'),(8,2,1,'NX-FD9A1511','paid',5000.00,15.00,0.00,5015.00,515.00,4500.00,'NX-FD9A1511',NULL,NULL,NULL,1,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-03-26 05:35:43','2026-03-26 05:35:57'),(9,2,0,'NX-BD99BF32','cancelled',11000.00,0.00,0.00,11000.00,11000.00,0.00,'NX-BD99BF32',NULL,NULL,NULL,4,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-03-30 17:12:59','2026-03-30 17:14:00'),(10,2,1,'NX-C9B52A21','cancelled',2000.00,0.00,0.00,2000.00,100.00,1900.00,'NX-C9B52A21',NULL,NULL,NULL,4,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-03-30 18:06:04','2026-03-30 18:08:01'),(11,3,0,'NX-E1CF8B99','paid',10000.00,0.00,0.00,10000.00,10000.00,0.00,'NX-E1CF8B99',NULL,NULL,NULL,1,'paystack','unpaid','Emmanuel Ajigiwe Atio','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-04-01 00:21:18','2026-04-01 00:21:41'),(12,2,0,'NX-1758F813','cancelled',150.00,10.00,0.00,160.00,160.00,0.00,'NX-1758F813',NULL,NULL,NULL,1,'paystack','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-04-01 06:45:21','2026-04-01 06:47:19'),(13,2,0,'NX-94984190','pending',450.00,0.00,0.00,450.00,0.00,450.00,NULL,NULL,NULL,NULL,1,'pod','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-04-01 07:36:09','2026-04-01 07:36:09'),(14,2,1,'NX-5C001114','refunded',1000.00,0.00,0.00,1000.00,50.00,950.00,'NX-5C001114',NULL,NULL,NULL,1,'paystack','pending','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-04-01 20:24:21','2026-04-02 00:19:24'),(15,2,0,'NX-CED81F58','pending',20000.00,0.00,0.00,20000.00,0.00,20000.00,NULL,NULL,NULL,NULL,1,'pod','unpaid','Avazonia Admin','minatoflash82@gmail.com','0235750693','Old john','Takoradi','',NULL,NULL,'2026-04-02 00:38:36','2026-04-02 00:38:36');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(200) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (2,'minatoflash2@gmail.com','fd214601bcc07c80e732c4c8fa07231be0da6cbac568181cbd61ddb83095e363','2026-03-31 09:15:33',1,'2026-03-31 06:15:33'),(7,'minatoflash82@gmail.com','2db54993a5de7dcc103116329d317148bcf0b3f0d59ffbc5cee81b9d38a740fc','2026-04-02 04:42:42',1,'2026-04-02 01:42:42');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `url` varchar(500) NOT NULL,
  `alt_text` varchar(200) DEFAULT NULL,
  `sort_order` tinyint(4) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,'public/uploads/products/p_1774337531_78ce85c1.png',NULL,0,1),(2,2,'public/uploads/products/p_1774471714_92fb2d93.jpg',NULL,0,1),(3,3,'public/uploads/products/p_1774472030_fbbd24f3.jpg',NULL,0,1),(4,5,'public/uploads/products/p_1774473373_fe5b501f.jpg',NULL,0,1),(5,6,'public/uploads/products/p_1774474132_ac045d45.jpg',NULL,0,1),(6,4,'public/uploads/products/p_1774474444_b82b2c87.jpg',NULL,0,1),(7,8,'public/uploads/products/p_1774474989_23683762.jpg',NULL,0,1),(8,9,'public/uploads/products/p_1774481445_9829a83b.jpg',NULL,0,1),(9,10,'public/uploads/products/p_1774481574_8c88d9de.jpg',NULL,0,1),(10,12,'public/uploads/products/p_1774491520_c9dcf4bb.png',NULL,0,1),(11,12,'public/uploads/products/p_1774673557_bf4795b7_0.jpg',NULL,0,0);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned DEFAULT NULL,
  `brand_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `brand_name` varchar(100) DEFAULT NULL,
  `slug` varchar(220) NOT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `specs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specs`)),
  `price_ghs` decimal(10,2) NOT NULL,
  `compare_at_price_ghs` decimal(10,2) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_new_arrival` tinyint(1) DEFAULT 0,
  `is_bestseller` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `video_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_preorder` tinyint(1) DEFAULT 0,
  `is_dropshipping` tinyint(1) DEFAULT 0,
  `lead_time_days` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `sku` (`sku`),
  FULLTEXT KEY `ft_product_search` (`name`,`description`,`brand_name`),
  CONSTRAINT `chk_compare_price` CHECK (`compare_at_price_ghs` is null or `compare_at_price_ghs` > `price_ghs`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,2,'Samsung s25 ultra',NULL,'samsung-s25-ultra',NULL,'','Premium, New Arrival, Best Seller',NULL,NULL,NULL,NULL,NULL,8000.00,NULL,10,0,0,0,1,NULL,'2026-03-24 07:32:11','2026-04-01 06:16:59',0,0,NULL),(2,1,3,'IPhone 16 Pro Max',NULL,'iphone-16-pro-max',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,20000.00,NULL,8,0,0,0,1,NULL,'2026-03-25 20:48:34','2026-03-25 20:48:34',0,0,NULL),(3,6,3,'Apple Watch Series 10 GPS 42mm Jet Black Aluminium Sport Loo',NULL,'apple-watch-series-10-gps-42mm-jet-black-aluminium-sport-loo',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,5000.00,NULL,8,0,0,0,1,NULL,'2026-03-25 20:53:50','2026-03-26 05:53:06',0,0,NULL),(4,3,2,'Samsung galaxy buds 3',NULL,'samsung-galaxy-buds-3',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,3000.00,NULL,8,0,0,0,1,NULL,'2026-03-25 20:56:43','2026-03-25 20:56:43',0,0,NULL),(5,6,5,'Oraimo Power Box 500 50000mah 22.5w Power Bank',NULL,'oraimo-power-box-500-50000mah-22-5w-power-bank',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,450.00,NULL,8,0,0,0,1,NULL,'2026-03-25 21:16:13','2026-03-26 05:53:06',0,0,NULL),(6,3,5,' Oraimo BoomPop Pro Wireless Headphones Over-Ear Hybrid Noise Cancellation - Grey',NULL,'-oraimo-boompop-pro-wireless-headphones-over-ear-hybrid-noise-cancellation---grey',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,350.00,NULL,50,1,0,1,1,NULL,'2026-03-25 21:28:52','2026-04-01 19:13:36',0,0,NULL),(8,3,5,'ORAIMO WIRELESS HEADSET STRONG BASS BLACK OEB-E33',NULL,'oraimo-wireless-headset-strong-bass-black-oeb-e33',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,150.00,NULL,85,1,0,1,1,NULL,'2026-03-25 21:43:09','2026-04-01 19:13:36',0,0,NULL),(9,2,15,'Dell Gaming Laptop',NULL,'dell-gaming-laptop',NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,10000.00,NULL,5,1,0,1,1,NULL,'2026-03-25 23:29:56','2026-04-01 19:13:36',0,0,NULL),(10,2,17,'ASUS ROG gaming laptop',NULL,'asus-rog-gaming-laptop',NULL,'','','','','',NULL,NULL,11000.00,20000.00,15,1,0,1,1,NULL,'2026-03-25 23:32:54','2026-04-02 00:36:55',1,0,NULL),(12,1,2,'Samsung s25 ultra new',NULL,'samsung-s25-ultra-new',NULL,'','Samsung, Galaxy, Android, Smartphone','','','Samsung, Galaxy, Android, Smartphone',NULL,NULL,1000.00,NULL,5,1,0,0,1,NULL,'2026-03-26 02:18:40','2026-04-01 19:13:36',1,1,10);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_codes`
--

DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` decimal(8,2) NOT NULL,
  `min_order_ghs` decimal(10,2) DEFAULT 0.00,
  `max_uses` int(11) DEFAULT NULL,
  `current_uses` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_codes`
--

LOCK TABLES `promo_codes` WRITE;
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `reviewer_name` varchar(100) NOT NULL,
  `reviewer_location` varchar(100) DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL CHECK (`rating` between 1 and 5),
  `body` text DEFAULT NULL,
  `verified_purchase` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,1,'Emmanuel Ajigiwe Atio','Accra',5,'not bad',0,1,'2026-03-25 03:25:52');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('announcement_text','Free Delivery on all orders over Γé╡500 ΓÇö Limited Time Offer','2026-03-26 00:04:12'),('default_shipping_fee','5','2026-03-26 00:04:12'),('footer_notice','┬⌐ 2026 AVAZONIA GH ΓÇö CRAFTED IN ACCRA','2026-03-26 00:04:12'),('grid_density','4','2026-04-01 00:32:29'),('mail_encryption','tls','2026-04-02 01:06:48'),('mail_from_email','avazonia@gmail.com.','2026-04-02 01:06:48'),('mail_from_name','Avazonia','2026-04-02 01:06:48'),('mail_host','smtp.gmail.com','2026-04-02 01:06:47'),('mail_password','pdze mtaa rstr wbls','2026-04-02 01:06:48'),('mail_port','587','2026-04-02 01:06:48'),('mail_username','avazonia@gmail.com.','2026-04-02 01:06:48'),('min_stock_threshold','1','2026-04-01 00:32:29'),('preorder_deposit_pct','5','2026-03-30 18:03:18'),('primary_brand_color','#E5001A','2026-04-01 06:30:59'),('returns_policy','Returns, Refunds & Exchange Policy ΓÇô Avazonia\nAt Avazonia, we are committed to customer satisfaction. Subject to our Terms and Conditions, we offer returns, exchanges, or refunds for eligible items within 7 days of purchase. Requests made after this period will not be accepted.\n\nEligibility for Return, Refund, or Exchange\n≡ƒö╣ Wrong Item Delivered\nThe product must remain sealed and unopened\nItem must have no dents, damage, or liquid exposure\nProof of purchase (receipt) is required\n\n≡ƒö╣ Manufacturing Defects\nDefective items reported within 7 days will be replaced with the same product (subject to availability)\nAll returned items will undergo inspection and verification\nDefects reported after 7 days will be referred to the manufacturerΓÇÖs service center under warranty\n\n≡ƒö╣ Incomplete Package\nMissing items or accessories must be reported within 7 days for quick resolution\n\nRefund / Chargeback Policy\n≡ƒö╣ Undelivered Orders\nRefund requests for undelivered orders will be reviewed and approved after verification\nApproved refunds will be processed within 30 days\n\n≡ƒö╣ Payment Reversals\nChargebacks for card or bank payments must be initiated through your bank\nRefunds will be processed using an appropriate payment method as determined by Avazonia\n\nNeed Help?\nOur support team is always available to assist you with any questions regarding our policies.\nContact Avazonia Support for assistance.\nAt Avazonia, we aim to ensure a smooth and trustworthy shopping experience every time you shop with us.','2026-03-30 22:45:41'),('shipping_accra','10','2026-03-26 00:19:50'),('shipping_free_threshold','200','2026-04-01 00:32:29'),('shipping_kumasi','5','2026-03-26 00:20:09'),('shipping_others','50.00','2026-03-26 00:19:50'),('shipping_pickup','FREE','2026-03-26 00:19:50'),('shipping_policy','Delivery & Shipping Information ΓÇô Avazonia\nAt Avazonia, we are committed to ensuring fast, reliable, and convenient delivery to your doorstep.\n\nDelivery Time\nAccra: 1ΓÇô3 working days\nOutside Accra: 3ΓÇô7 working days\n\nOrder Processing\nOrders are processed and fulfilled from Monday to Friday.\n\nShipping Cost\nFREE delivery on all orders above GHS 200\nFor orders below GHS 200, delivery fees apply:\nGreater Accra: GHS 30\nAshanti Region: GHS 35\nBrong Ahafo Region: GHS 35\nOther Regions: GHS 50\n\nImportant Information\n- You will receive your tracking number via email once your order is shipped.\n- All orders are delivered through trusted courier services.\n- Delivery is strictly door-to-door (no P.O. Box addresses allowed).','2026-03-30 22:45:41'),('store_name','AVAZONIA SHOP','2026-03-30 18:13:52'),('support_email','avazonia@gmail.com','2026-04-01 00:38:27'),('whatsapp_number','233240000000','2026-03-26 00:04:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `metadata` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,2,'STATUS_CHANGE','order',14,'Order REF: NX-5C001114 status updated to REFUNDED','{\"ref\":\"NX-5C001114\",\"old_status\":\"paid\",\"new_status\":\"refunded\"}','::1','2026-04-02 00:19:24'),(2,2,'PURCHASE','order',15,'New order placed: NX-CED81F58 by Avazonia Admin','{\"ref\":\"NX-CED81F58\",\"total\":20000,\"items_count\":1}','::1','2026-04-02 00:38:37'),(3,2,'SETTING_UPDATE',NULL,NULL,'Administrative update to 24 system configuration keys.','{\"keys\":[\"store_name\",\"primary_brand_color\",\"support_email\",\"whatsapp_number\",\"announcement_text\",\"grid_density\",\"footer_notice\",\"min_stock_threshold\",\"preorder_deposit_pct\",\"shipping_accra\",\"shipping_kumasi\",\"shipping_others\",\"shipping_free_threshold\",\"shipping_pickup\",\"default_shipping_fee\",\"returns_policy\",\"shipping_policy\",\"mail_host\",\"mail_port\",\"mail_username\",\"mail_password\",\"mail_encryption\",\"mail_from_email\",\"mail_from_name\"]}','::1','2026-04-02 01:06:48');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'minatoflash2@gmail.com','$2y$10$ZGHhEXTIqNF7qxMSjqz0FO9nNZZXj2Vd9IDGd9BasmtEu6dGXbiYK','Emmanuel Ajigiwe Atio','0550599755','customer',0,0,NULL,'2026-03-24 05:01:17','2026-03-31 06:42:38'),(2,'admin@avazonia.com','$2y$10$qqOZ/D0kkp0M1gY9OA.jte/qwoiJCR.RljcV5H9BSvyJwE.IpyHpG','Avazonia Admin',NULL,'admin',1,0,NULL,'2026-03-24 06:13:48','2026-04-02 18:44:46'),(3,'minatoflash82@gmail.com','$2y$10$ZYGyec5oUYfCxFHXMYZiCO9RBDN8dh8VEgQXHTDr9eR46iUjsZwU2','Emmanuel Ajigiwe Atio',NULL,'customer',1,0,'150e9020630b88d3ae9017599779ae77d6665d46f6ac64152a81e5bc55383861','2026-03-31 22:44:16','2026-04-02 01:44:41'),(4,'vaderdarth443@gmail.com','$2y$10$oHzsaDjEaWdng5XyJc1n3Orl9EZznp9t6vFj5/eg1E7QNtdP0M.zu','Emmanuel Ajigiwe Atio','','customer',1,0,'9eb0bfb557bb95846ac7c0972093ed29f99398ff1b61bf0a1c8536f118f279cf','2026-04-02 02:45:06',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variants`
--

DROP TABLE IF EXISTS `variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `color` varchar(80) DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT 0,
  `price_override_ghs` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variants`
--

LOCK TABLES `variants` WRITE;
/*!40000 ALTER TABLE `variants` DISABLE KEYS */;
INSERT INTO `variants` VALUES (1,12,'Silver','#ecdfdf','128GB',NULL,5,NULL,''),(2,12,'Gold','#e0c01f','512GB',NULL,8,NULL,''),(3,12,'Sea blue','#7092d7','128GB',NULL,3,NULL,'');
/*!40000 ALTER TABLE `variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_prod_idx` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-02 12:14:03
