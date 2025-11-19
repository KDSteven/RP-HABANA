-- Simple dump for u542150242_rp_habana @ 20251116_232100
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `branches`;
CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(255) NOT NULL,
  `branch_location` varchar(255) DEFAULT NULL,
  `branch_email` varchar(255) DEFAULT NULL,
  `branch_contact` varchar(255) DEFAULT NULL,
  `branch_contact_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('1','Bucal - Main Branch','Bucal Bypass Road','rphabana_bucal@gmail.com','Kenneth John Villa','09212125215','2025-11-02 12:17:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('2','Halang Branch 1','Brgy. Halang','rphabnana_halang1@gmail.com','Daryll A. Dimuyog','09255258125','2025-11-02 12:19:32','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('3','Halang Branch 2','Brgy. Halang (City Hall)','rphabana_halang2@gmail.com','Kristine A. Santillan','09124211251','2025-11-02 12:22:32','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('4','Batino Branch','Brgy. Batino','rphabana_batino@gmail.com','John Michael Dela Cruz','09285822182','2025-11-02 12:23:58','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('5','Barandal Branch','Brgy. Barandal','rphabana_barandal@gmail.com','Viktor Mercado','09278275127','2025-11-02 12:28:07','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('6','Lawa Branch 1','Brgy. Lawa','rphabana_lawa1@gmail.com','Noel Jazareno','09256255125','2025-11-02 12:29:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('7','Lawa Branch 2','Brgy. Lawa','rphabana_lawa2@gmail.com','Marvin Mercado','09375178218','2025-11-02 12:30:30','0',NULL);

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('1','Shell','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('2','Motolite','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('3','Bosny','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('4','Best Drive','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('5','Repsol','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('6','Blade','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('7','WD-40','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('8','Slime','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('9','Magic Gatas','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('10','Pledge','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('11','Whiz','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('12','Castrol','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('13','Flamingo','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('14','Petron','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('15','Generic','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('16','Prestone','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('17','Yamaha','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('18','Kixx','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('22','goodshit','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('23','SouthLake','1');
INSERT INTO `brands` (`brand_id`,`brand_name`,`active`) VALUES ('24','NorthLake','1');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('1','Engine Oils','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('13','Battery Water & Solutions','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('14','Spray Paints & Coatings','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('15','Spray Lubricants & Maintenance','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('16','Motorcycle Accessories','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('17','Car Care / Cleaning','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('18','Tire Sealants & Repair Fluids','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('19','Cleaning & Polishing','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('20','Gear & Transmission Oils','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('21','Brake Fluids & Hydraulics','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('22','Fuel & Gas Refills','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('23','Coolants & Radiator Fluids','1');
INSERT INTO `categories` (`category_id`,`category_name`,`active`) VALUES ('24','Di sulat','1');

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reserved_outgoing` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`inventory_id`),
  UNIQUE KEY `unique_inventory` (`product_id`,`branch_id`),
  KEY `branch_id` (`branch_id`),
  KEY `idx_inventory_branch_prod` (`branch_id`,`product_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('1','1','1','20','0','2025-11-16 22:58:26','2025-11-02 12:49:29','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('2','2','1','0','0',NULL,'2025-11-02 12:51:54','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('3','3','1','40','0',NULL,'2025-11-02 12:56:16','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('4','4','1','30','0',NULL,'2025-11-02 13:11:28','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('5','5','1','10','0',NULL,'2025-11-02 13:12:51','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('6','6','1','15','0',NULL,'2025-11-02 13:18:02','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('7','7','1','15','0',NULL,'2025-11-02 13:21:24','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('8','8','1','20','0',NULL,'2025-11-02 13:28:18','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('9','9','1','20','0',NULL,'2025-11-02 13:31:17','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('10','10','1','6','0',NULL,'2025-11-02 13:32:22','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('11','11','1','10','0',NULL,'2025-11-02 13:35:25','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('12','12','1','10','0',NULL,'2025-11-02 13:39:22','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('13','13','1','15','0',NULL,'2025-11-02 13:45:19','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('14','14','1','5','0',NULL,'2025-11-02 13:46:03','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('15','15','1','20','0',NULL,'2025-11-02 13:47:32','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('16','16','1','30','0',NULL,'2025-11-02 13:49:50','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('17','17','1','15','0',NULL,'2025-11-02 13:51:25','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('18','1','2','2','0',NULL,'2025-11-02 13:51:52','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('19','18','1','15','0',NULL,'2025-11-02 13:53:10','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('20','19','1','10','0',NULL,'2025-11-02 13:54:55','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('21','20','1','20','0',NULL,'2025-11-02 13:58:36','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('22','21','1','10','0',NULL,'2025-11-03 09:11:08','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('23','22','1','20','0',NULL,'2025-11-03 09:12:34','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('24','23','1','35','0',NULL,'2025-11-03 09:13:54','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('25','24','1','20','0',NULL,'2025-11-03 09:39:44','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('26','25','1','30','0',NULL,'2025-11-03 09:42:37','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('27','26','1','20','0',NULL,'2025-11-03 09:45:13','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('28','27','1','20','0',NULL,'2025-11-03 09:46:08','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('29','28','1','20','0',NULL,'2025-11-03 09:46:59','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('30','29','1','20','0',NULL,'2025-11-03 09:48:08','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('31','30','1','15','0',NULL,'2025-11-03 09:48:39','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('32','31','1','20','0',NULL,'2025-11-03 09:49:33','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('33','32','1','20','0',NULL,'2025-11-03 09:53:36','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('34','33','1','0','0',NULL,'2025-11-03 09:54:30','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('35','34','1','19','0',NULL,'2025-11-03 09:57:01','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('36','35','1','20','0',NULL,'2025-11-03 09:57:51','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('37','36','1','20','0',NULL,'2025-11-03 10:18:03','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('38','37','1','20','0',NULL,'2025-11-03 10:19:40','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('39','38','1','20','0',NULL,'2025-11-03 10:20:53','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('40','39','1','20','0',NULL,'2025-11-03 10:21:52','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('41','40','1','20','0',NULL,'2025-11-03 10:22:36','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('42','41','1','20','0',NULL,'2025-11-03 10:23:51','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('43','42','1','20','0',NULL,'2025-11-03 10:24:27','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('44','43','1','20','0',NULL,'2025-11-03 10:29:02','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('45','44','1','0','0',NULL,'2025-11-03 10:41:31','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('46','45','1','0','0',NULL,'2025-11-03 10:42:23','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('47','46','1','20','0',NULL,'2025-11-03 10:43:10','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('48','47','1','20','0',NULL,'2025-11-03 10:44:58','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('49','48','1','20','0',NULL,'2025-11-03 10:48:53','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('50','49','1','20','0',NULL,'2025-11-03 10:51:08','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('51','17','4','2','0',NULL,'2025-11-15 16:53:31','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('52','18','5','5','0',NULL,'2025-11-15 16:54:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('53','7','3','5','0',NULL,'2025-11-15 16:56:37','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('54','30','5','5','0',NULL,'2025-11-15 16:58:57','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('55','17','2','3','0',NULL,'2025-11-15 17:02:03','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('56','2','2','2','0',NULL,'2025-11-15 17:03:43','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('57','4','2','5','0',NULL,'2025-11-15 17:10:04','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('58','50','1','1','0',NULL,'2025-11-16 20:05:09','0');

DROP TABLE IF EXISTS `inventory_lots`;
CREATE TABLE `inventory_lots` (
  `lot_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `expiry_date` date NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`lot_id`),
  UNIQUE KEY `uq_prod_branch_expiry` (`product_id`,`branch_id`,`expiry_date`),
  KEY `fk_lots_branch` (`branch_id`),
  CONSTRAINT `fk_lots_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_lots_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('1','11','1','2030-11-02','10');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('2','12','1','2030-11-02','10');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('3','34','1','2030-11-03','20');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('4','10','1','2025-11-17','6');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('5','50','1','2025-11-16','1');

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_branch` (`branch_id`),
  KEY `idx_logs_timestamp` (`timestamp`),
  CONSTRAINT `fk_logs_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('1','1','Login successful','','2025-11-15 16:50:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('2','1','Logout','User logged out.','2025-11-15 16:52:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('3','1','Login successful','','2025-11-15 16:53:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('4','1','Stock Transfer','Transferred 2 Anti-Rust from Bucal - Main Branch to Batino Branch','2025-11-15 16:53:31','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('5','1','Stock Transfer','Transferred 5 BS-40 from Bucal - Main Branch to Barandal Branch','2025-11-15 16:54:05','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('6','1','Logout','User logged out.','2025-11-15 16:54:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('7','42','Login successful','','2025-11-15 16:54:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('8','42','Stock Transfer Request','Requested transfer of 5 Top Sports Oil from Bucal - Main Branch to Halang Branch 2','2025-11-15 16:55:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('9','42','Logout','User logged out.','2025-11-15 16:56:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('10','1','Login successful','','2025-11-15 16:56:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('11','1','Stock Transfer','Transferred 5 CASTROL GTX from Bucal - Main Branch to Barandal Branch','2025-11-15 16:58:57','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('12','1','Stock Transfer','Transferred 3 Anti-Rust from Bucal - Main Branch to Halang Branch 1','2025-11-15 17:02:03','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('13','1','Stock Transfer','Transferred 2 Battery Solution from Bucal - Main Branch to Halang Branch 1','2025-11-15 17:03:43','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('14','1','Stock Transfer','Transferred 5 Bosny Gold from Bucal - Main Branch to Halang Branch 1','2025-11-15 17:10:04','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('15','1','Add Stock','Added 2 to 2T Advance (ID:1) | Branch: Bucal - Main Branch','2025-11-15 17:10:54','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('16','1','Logout','User logged out.','2025-11-15 17:22:39',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('17','1','Login successful','','2025-11-15 17:22:59',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('18','1','Login successful','','2025-11-15 14:24:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('19','1','Logout','User logged out.','2025-11-15 14:24:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('20',NULL,'Login failed for username: admin123','','2025-11-15 14:25:20',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('21','1','Login successful','','2025-11-15 14:25:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('22',NULL,'Login failed for username: staff001','','2025-11-15 14:25:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('23',NULL,'Login failed for username: staff001','','2025-11-15 14:25:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('24',NULL,'Login failed for username: staff001','','2025-11-15 14:26:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('25',NULL,'Login failed for username: StaffKJ','','2025-11-15 14:26:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('26',NULL,'Login failed for username: staffVik','','2025-11-15 14:27:14',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('27',NULL,'Login failed for username: StaffKJ','','2025-11-15 14:28:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('28','41','Login successful','','2025-11-15 14:28:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('29','41','Logout','User logged out.','2025-11-15 14:29:07','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('30','1','Login successful','','2025-11-15 14:30:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('31','1','Login successful','','2025-11-15 14:39:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('32','40','Login successful','','2025-11-15 14:44:10','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('33','40','Logout','User logged out.','2025-11-15 14:45:54','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('34','41','Login successful','','2025-11-15 14:46:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('35','41','Logout','User logged out.','2025-11-15 14:51:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('36','1','Login successful','','2025-11-15 14:51:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('37','1','Login successful','','2025-11-15 14:57:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('38','1','Logout','User logged out.','2025-11-15 15:05:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('39','41','Login successful','','2025-11-15 15:05:29','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('40','41','Logout','User logged out.','2025-11-15 15:11:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('41','42','Login successful','','2025-11-15 15:12:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('42','42','Stock Transfer Request','Requested transfer of 3 CASTROL GTX from Bucal - Main Branch to Halang Branch 1','2025-11-15 15:13:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('43','42','Logout','User logged out.','2025-11-15 15:14:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('44','41','Login successful','','2025-11-15 15:14:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('45','41','Login successful','','2025-11-15 15:14:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('46','1','Logout','User logged out.','2025-11-15 15:19:44',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('47','1','Login successful','','2025-11-15 15:19:48',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('48','41','Logout','User logged out.','2025-11-15 15:24:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('49','41','Login successful','','2025-11-16 04:09:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('50','41','Logout','User logged out.','2025-11-16 04:09:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('51',NULL,'Login failed for username: pbergina@ccc.edu.ph','','2025-11-16 07:21:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('52',NULL,'Login failed for username: admin123','','2025-11-16 10:50:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('53',NULL,'Login failed for username: ad','','2025-11-16 10:52:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('54','1','Login successful','','2025-11-16 10:52:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('55','41','Login successful','','2025-11-16 10:52:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('56','1','Logout','User logged out.','2025-11-16 10:57:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('57','1','Login successful','','2025-11-16 10:57:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('58','1','Logout','User logged out.','2025-11-16 11:01:56',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('59','41','Login successful','','2025-11-16 11:02:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('60','41','Logout','User logged out.','2025-11-16 11:02:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('61','41','Login successful','','2025-11-16 11:02:06','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('62','41','Logout','User logged out.','2025-11-16 11:02:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('63','1','Login successful','','2025-11-16 11:02:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('64','1','Logout','User logged out.','2025-11-16 11:33:10',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('65','1','Login successful','','2025-11-16 11:33:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('66','1','Logout','User logged out.','2025-11-16 11:42:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('67','1','Login successful','','2025-11-16 11:42:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('68','1','Login successful','','2025-11-16 11:43:49',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('69','1','Login successful','','2025-11-16 11:44:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('70','1','Logout','User logged out.','2025-11-16 11:46:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('71','1','Login successful','','2025-11-16 11:47:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('72','1','Logout','User logged out.','2025-11-16 11:50:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('73','41','Login successful','','2025-11-16 11:50:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('74','41','Logout','User logged out.','2025-11-16 11:51:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('75','42','Login successful','','2025-11-16 11:51:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('76','42','Logout','User logged out.','2025-11-16 11:53:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('77','1','Login successful','','2025-11-16 11:53:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('78','1','Logout','User logged out.','2025-11-16 19:55:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('79','1','Login successful','','2025-11-16 19:55:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('80','1','Logout','User logged out.','2025-11-16 19:56:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('81','1','Logout','User logged out.','2025-11-16 19:56:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('82',NULL,'Login failed for username: staff001','','2025-11-16 19:56:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('83','41','Login successful','','2025-11-16 19:56:29','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('84','41','Login successful','','2025-11-16 19:57:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('85','41','Logout','User logged out.','2025-11-16 19:57:06','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('86','1','Login successful','','2025-11-16 19:57:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('87','1','Logout','User logged out.','2025-11-16 19:57:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('88','41','Logout','User logged out.','2025-11-16 19:57:29','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('89','1','Login successful','','2025-11-16 19:57:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('90','42','Login successful','','2025-11-16 19:57:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('91','42','Stock Transfer Request','Requested transfer of 1 2T Advance from Bucal - Main Branch to Barandal Branch','2025-11-16 19:57:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('92','42','Logout','User logged out.','2025-11-16 19:57:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('93','1','Login successful','','2025-11-16 19:57:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('94','1','Logout','User logged out.','2025-11-16 19:58:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('95','1','Logout','User logged out.','2025-11-16 19:59:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('96','42','Login successful','','2025-11-16 19:59:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('97','1','Login successful','','2025-11-16 19:59:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('98','41','Login successful','','2025-11-16 20:01:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('99','41','Logout','User logged out.','2025-11-16 20:01:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('100','1','Login successful','','2025-11-16 20:01:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('101','42','Add Product','Added product \'battery\' (ID: 50) with stock 10 to branch 1','2025-11-16 20:05:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('102','42','Stock-In Request','Requested +5 for 2T Advance (ID:1) | Branch: Bucal - Main Branch','2025-11-16 20:06:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('103','42','Stock Transfer Request','Requested transfer of 5 2T Advance from Bucal - Main Branch to Halang Branch 1','2025-11-16 20:07:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('104','42','Logout','User logged out.','2025-11-16 20:08:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('105','41','Login successful','','2025-11-16 20:08:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('106','41','Logout','User logged out.','2025-11-16 20:10:30','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('107','41','Login successful','','2025-11-16 20:10:44','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('108','1','Login successful','','2025-11-16 20:14:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('109','1','Logout','User logged out.','2025-11-16 20:16:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('110','41','Login successful','','2025-11-16 20:16:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('111','41','Logout','User logged out.','2025-11-16 20:17:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('112','1','Login successful','','2025-11-16 20:17:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('113','1','Logout','User logged out.','2025-11-16 20:24:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('114','1','Login successful','','2025-11-16 20:24:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('115','1','Logout','User logged out.','2025-11-16 20:26:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('116','1','Login successful','','2025-11-16 20:26:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('117','1','Logout','User logged out.','2025-11-16 20:26:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('118','41','Login successful','','2025-11-16 20:27:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('119','41','Login successful','','2025-11-16 20:42:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('120','41','Login successful','','2025-11-16 20:43:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('121','41','Login successful','','2025-11-16 20:46:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('122','41','Login successful','','2025-11-16 20:50:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('123','41','Logout','User logged out.','2025-11-16 20:52:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('124','41','Login successful','','2025-11-16 20:52:10','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('125','41','Login successful','','2025-11-16 20:52:38','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('126','41','Login successful','','2025-11-16 20:54:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('127','41','Login successful','','2025-11-16 20:58:44','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('128','41','Login successful','','2025-11-16 21:01:38','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('129','41','Login successful','','2025-11-16 21:04:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('130','41','Login successful','','2025-11-16 21:07:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('131','41','Logout','User logged out.','2025-11-16 21:08:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('132','1','Login successful','','2025-11-16 21:08:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('133','1','Logout','User logged out.','2025-11-16 21:09:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('134','41','Login successful','','2025-11-16 21:10:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('135','41','Login successful','','2025-11-16 21:16:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('136','41','Login successful','','2025-11-16 21:17:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('137','41','Login successful','','2025-11-16 21:18:07','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('138','1','Login successful','','2025-11-16 21:19:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('139','41','Login successful','','2025-11-16 21:20:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('140','41','Login successful','','2025-11-16 21:26:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('141','41','Login successful','','2025-11-16 21:28:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('142','1','Logout','User logged out.','2025-11-16 21:29:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('143','41','Login successful','','2025-11-16 21:29:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('144','41','Login successful','','2025-11-16 21:35:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('145','1','Logout','User logged out.','2025-11-16 21:36:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('146','41','Login successful','','2025-11-16 21:36:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('147','41','Logout','User logged out.','2025-11-16 21:37:10','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('148','1','Login successful','','2025-11-16 21:37:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('149',NULL,'Login failed for username: StaffKJ','','2025-11-16 21:37:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('150','41','Login successful','','2025-11-16 21:37:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('151','41','Login successful','','2025-11-16 21:39:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('152','41','Login successful','','2025-11-16 21:42:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('153','41','Login successful','','2025-11-16 21:44:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('154','41','Login successful','','2025-11-16 21:46:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('155','41','Login successful','','2025-11-16 21:50:06','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('156','41','Login successful','','2025-11-16 21:51:10','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('157','41','Logout','User logged out.','2025-11-16 21:53:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('158','1','Login successful','','2025-11-16 21:53:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('162','41','Logout','User logged out.','2025-11-16 22:08:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('163','1','Login successful','','2025-11-16 22:08:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('164','41','Login successful','','2025-11-16 22:09:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('166','1','Logout','User logged out.','2025-11-16 22:17:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('167','41','Logout','User logged out.','2025-11-16 22:17:18','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('168','41','Login successful','','2025-11-16 22:17:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('169',NULL,'Login failed for username: StaffKJ','','2025-11-16 22:17:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('170','41','Login successful','','2025-11-16 22:17:29','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('171','41','Logout','User logged out.','2025-11-16 22:17:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('172','41','Login successful','','2025-11-16 22:18:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('173','41','Login successful','','2025-11-16 22:20:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('174','41','Login successful','','2025-11-16 22:21:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('175','41','Login successful','','2025-11-16 22:23:21','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('176','41','Logout','User logged out.','2025-11-16 22:24:54','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('177','1','Login successful','','2025-11-16 22:25:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('178','1','Logout','User logged out.','2025-11-16 22:27:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('179','41','Login successful','','2025-11-16 22:27:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('180','41','Logout','User logged out.','2025-11-16 22:28:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('181','1','Login successful','','2025-11-16 22:28:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('182',NULL,'Login failed for username: StaffKJ','','2025-11-16 22:29:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('183','41','Login successful','','2025-11-16 22:30:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('184','1','Add Stock','Added 6 to Water Solution (ID:10) (Expiry: 2025-11-17) | Branch: Bucal - Main Branch','2025-11-16 22:32:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('185','1','Login successful','','2025-11-16 22:44:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('186','1','Logout','User logged out.','2025-11-16 22:45:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('187',NULL,'Login failed for username: staffKJ','','2025-11-16 22:45:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('188','41','Logout','User logged out.','2025-11-16 22:45:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('189','41','Login successful','','2025-11-16 22:45:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('190','1','Add Stock','Added 1 to battery (ID:50) (Expiry: 2025-11-16) | Branch: Bucal - Main Branch','2025-11-16 22:47:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('191','41','Logout','User logged out.','2025-11-16 22:52:18','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('192','1','Login successful','','2025-11-16 22:52:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('193',NULL,'Create Brand','Created brand: SouthLake','2025-11-16 22:52:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('194',NULL,'Create Brand','Created brand: NorthLake','2025-11-16 22:56:13',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('195',NULL,'Create Category','Created category: Di sulat','2025-11-16 22:56:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('196',NULL,'Archive Category','Archived category: Di sulat','2025-11-16 22:56:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('197','1','Restore Category','Restored category_id=24','2025-11-16 22:57:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('198','1','Archive Product','Archived product: 2T Advance (Inventory ID: 1) | Branch: Bucal - Main Branch','2025-11-16 22:58:26','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('199','1','Login successful','','2025-11-16 23:08:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('200','41','Logout','User logged out.','2025-11-16 23:09:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('201','1','Login successful','','2025-11-16 23:09:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('202','1','Logout','User logged out.','2025-11-16 23:13:10',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('203',NULL,'Login failed for username: staff001','','2025-11-16 23:13:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('204','1','Login successful','','2025-11-16 23:13:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('205','1','Update Account','Updated user: admin0205 (Rich), role: admin, phone: 09935844994','2025-11-16 23:15:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('206','1','Logout','User logged out.','2025-11-16 23:15:51',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('207','38','Login successful','','2025-11-16 23:18:14',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('208','1','Restore Product','Restored product: 2T Advance (ID: 1)','2025-11-16 23:20:35','1');

DROP TABLE IF EXISTS `otp_codes`;
CREATE TABLE `otp_codes` (
  `phone_number` varchar(20) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `physical_inventory`;
CREATE TABLE `physical_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `system_stock` int(11) NOT NULL,
  `physical_count` int(11) NOT NULL,
  `discrepancy` int(11) NOT NULL,
  `status` enum('Match','Mismatch') NOT NULL,
  `counted_by` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `count_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(20,0) DEFAULT NULL,
  `markup_price` int(10) DEFAULT NULL,
  `retail_price` float DEFAULT NULL,
  `ceiling_point` int(11) DEFAULT NULL,
  `critical_point` int(11) DEFAULT NULL,
  `vat` float DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `expiration_date` date DEFAULT NULL,
  `archived` tinyint(10) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `brand_name` text DEFAULT NULL,
  `expiry_required` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `barcode` (`barcode`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('1','2T Advance','2000000000015','Engine Oils','45','10','49.5','20','5','12','2025-11-02 12:49:29','2025-11-02 12:49:29',NULL,'0',NULL,'Shell','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('2','Battery Solution','2000000000022','Battery Water & Solutions','50','10','55','20','5','12','2025-11-02 12:51:54','2025-11-02 12:51:54',NULL,'0',NULL,'Motolite','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('3','Bosny Paint','2000000000039','Spray Paints & Coatings','150','5','157.5','50','10','12','2025-11-02 12:56:16','2025-11-02 12:56:16',NULL,'0',NULL,'Bosny','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('4','Bosny Gold','2000000000046','Spray Paints & Coatings','300','5','315','50','10','12','2025-11-02 13:11:28','2025-11-02 13:11:28',NULL,'0',NULL,'Bosny','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('5','Extreme Paint Best Drive','2000000000053','Spray Paints & Coatings','180','5','189','50','10','12','2025-11-02 13:12:51','2025-11-02 13:12:51',NULL,'0',NULL,'Best Drive','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('6','Thunder','2000000000060','Spray Lubricants & Maintenance','200','5','210','20','5','12','2025-11-02 13:18:02','2025-11-02 13:18:02',NULL,'0',NULL,'Repsol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('7','Top Sports Oil','2000000000077','Spray Lubricants & Maintenance','40','5','42','30','10','12','2025-11-02 13:21:24','2025-11-02 13:21:24',NULL,'0',NULL,'Shell','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('8','VS1','2000000000084','Car Care / Cleaning','200','10','220','30','10','12','2025-11-02 13:28:18','2025-11-02 13:28:18',NULL,'0',NULL,'Blade','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('9','WD40 Lubricant Spray','2000000000091','Spray Lubricants & Maintenance','200','10','220','30','10','12','2025-11-02 13:31:17','2025-11-02 13:31:17',NULL,'0',NULL,'WD-40','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('10','Water Solution','2000000000107','Battery Water & Solutions','40','10','44','20','5','12','2025-11-02 13:32:22','2025-11-02 13:32:22',NULL,'0',NULL,'Repsol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('11','ZEALANT TIRE big','2000000000114','Tire Sealants & Repair Fluids','170','5','178.5','20','5','12','2025-11-02 13:35:25','2025-11-02 13:35:25','2030-11-02','0',NULL,'Slime','1');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('12','ZEALANT TIRE small','2000000000121','Tire Sealants & Repair Fluids','135','5','141.75','20','5','12','2025-11-02 13:39:22','2025-11-02 13:39:22','2030-11-02','0',NULL,'Slime','1');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('13','Magic Gatas 500ml','2000000000138','Cleaning & Polishing','165','5','173.25','20','5','12','2025-11-02 13:45:19','2025-11-02 13:45:19',NULL,'0',NULL,'Magic Gatas','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('14','Magic Gatas 200ml','2000000000145','Cleaning & Polishing','135','5','141.75','20','5','12','2025-11-02 13:46:03','2025-11-02 13:46:03',NULL,'0',NULL,'Magic Gatas','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('15','Tire Black','2000000000152','Cleaning & Polishing','215','10','236.5','30','10','12','2025-11-02 13:47:32','2025-11-02 13:47:32',NULL,'0',NULL,'Slime','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('16','Metal Polish','2000000000169','Cleaning & Polishing','210','10','231','30','5','12','2025-11-02 13:49:50','2025-11-02 13:49:50',NULL,'0',NULL,'Pledge','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('17','Anti-Rust','2000000000176','Spray Lubricants & Maintenance','120','10','132','20','5','12','2025-11-02 13:51:25','2025-11-02 13:51:25',NULL,'0',NULL,'Pledge','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('18','BS-40','2000000000183','Spray Lubricants & Maintenance','55','10','60.5','20','5','12','2025-11-02 13:53:10','2025-11-02 13:53:10',NULL,'0',NULL,'WD-40','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('19','Moto Gear Oil','2000000000190','Gear & Transmission Oils','65','10','71.5','20','5','12','2025-11-02 13:54:55','2025-11-02 13:54:55',NULL,'0',NULL,'Repsol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('20','WHIZ Brake Fluid','2000000000206','Brake Fluids & Hydraulics','60','10','66','20','5','12','2025-11-02 13:58:36','2025-11-02 13:58:36',NULL,'0',NULL,'Whiz','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('21','WHIZ Brake Fluid 170ml','2000000000213','Brake Fluids & Hydraulics','50','10','55','30','10','122','2025-11-03 09:11:08','2025-11-03 09:11:08',NULL,'0',NULL,'Whiz','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('22','WHIZ Brake Fluid 4T 300ml','2000000000220','Brake Fluids & Hydraulics','80','7','85.6','35','5','12','2025-11-03 09:12:34','2025-11-03 09:12:34',NULL,'0',NULL,'Whiz','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('23','WHIZ Brake Fluid 4T 900ml','2000000000237','Brake Fluids & Hydraulics','185','5','194.25','35','5','12','2025-11-03 09:13:54','2025-11-03 09:13:54',NULL,'0',NULL,'Whiz','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('24','CASTROL ACTIVE 1L','2000000000244','Engine Oils','260','5','273','30','5','12','2025-11-03 09:39:44','2025-11-03 09:39:44',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('25','CASTROL ACTIVE 800ml','2000000000251','Engine Oils','220','10','242','30','5','12','2025-11-03 09:42:37','2025-11-03 09:42:37',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('26','CASTROL GO 1L','2000000000268','Engine Oils','190','5','199.5','30','10','12','2025-11-03 09:45:13','2025-11-03 09:45:13',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('27','CASTROL GO 800ml','2000000000275','Engine Oils','180','5','189','30','10','12','2025-11-03 09:46:08','2025-11-03 09:46:08',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('28','CASTROL POWER 1L','2000000000282','Engine Oils','290','5','304.5','30','10','12','2025-11-03 09:46:59','2025-11-03 09:46:59',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('29','CASTROL POWER 800ml','2000000000299','Engine Oils','255','5','267.75','30','10','12','2025-11-03 09:48:08','2025-11-03 09:48:08',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('30','CASTROL GTX','2000000000305','Engine Oils','300','5','315','30','10','12','2025-11-03 09:48:39','2025-11-03 09:48:39',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('31','CASTROL GTX PROTECTION','2000000000312','Engine Oils','280','5','294','30','5','12','2025-11-03 09:49:33','2025-11-03 09:49:33',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('32','Power Coolant','2000000000329','Coolants & Radiator Fluids','130','5','136.5','30','10','12','2025-11-03 09:53:36','2025-11-03 09:53:36',NULL,'0',NULL,'Shell','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('33','Coolant Brake Fluid','2000000000336','Brake Fluids & Hydraulics','320','5','336','30','5','12','2025-11-03 09:54:30','2025-11-03 09:54:30',NULL,'0',NULL,'Whiz','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('34','FLM Sealant INF','2000000000343','Tire Sealants & Repair Fluids','320','5','336','30','10','12','2025-11-03 09:57:01','2025-11-03 09:57:01','2030-11-03','0',NULL,'Flamingo','1');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('35','FLM A/C PRO','2000000000350','Cleaning & Polishing','300','5','315','30','5','12','2025-11-03 09:57:51','2025-11-03 09:57:51',NULL,'0',NULL,'Flamingo','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('36','MO40','2000000000367','Spray Lubricants & Maintenance','200','5','210','30','10','12','2025-11-03 10:18:03','2025-11-03 10:18:03',NULL,'0',NULL,'WD-40','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('37','PETRON ATF','2000000000374','Gear & Transmission Oils','200','5','210','30','10','12','2025-11-03 10:19:40','2025-11-03 10:19:40',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('38','SPRINT 4T 1L','2000000000381','Engine Oils','180','5','189','30','10','12','2025-11-03 10:20:53','2025-11-03 10:20:53',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('39','SPRINT 4T 800ml','2000000000398','Engine Oils','170','5','178.5','30','10','12','2025-11-03 10:21:52','2025-11-03 10:21:52',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('40','REV X TRECKER BLUE','2000000000404','Engine Oils','180','5','189','30','5','12','2025-11-03 10:22:36','2025-11-03 10:22:36',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('41','SPRINT ENDURO 1L','2000000000411','Engine Oils','200','5','210','30','5','12','2025-11-03 10:23:51','2025-11-03 10:23:51',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('42','SPRINT RIDER 1L','2000000000428','Engine Oils','110','5','115.5','30','5','12','2025-11-03 10:24:27','2025-11-03 10:24:27',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('43','REV X PULA','2000000000435','Engine Oils','135','5','141.75','30','5','12','2025-11-03 10:29:02','2025-11-03 10:29:02',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('44','PRT BRAKE FLUID 270ML','2000000000442','Brake Fluids & Hydraulics','125','5','131.25','30','5','12','2025-11-03 10:41:31','2025-11-03 10:41:31',NULL,'0',NULL,'Prestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('45','PRT BRAKE FLUID 900ML','2000000000459','Brake Fluids & Hydraulics','320','5','336','30','5','12','2025-11-03 10:42:23','2025-11-03 10:42:23',NULL,'0',NULL,'Prestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('46','PRT COOLANT','2000000000466','Coolants & Radiator Fluids','320','5','336','30','5','12','2025-11-03 10:43:10','2025-11-03 10:43:10',NULL,'0',NULL,'Prestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('47','PRT COOLANT 500ML','2000000000473','Coolants & Radiator Fluids','180','5','189','30','5','12','2025-11-03 10:44:58','2025-11-03 10:44:58',NULL,'0',NULL,'Prestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('48','YMH LUBE GEAR OIL','2000000000480','Engine Oils','65','5','68.25','30','5','12','2025-11-03 10:48:53','2025-11-03 10:48:53',NULL,'0',NULL,'Yamaha','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('49','YMH AT AUTOMATIC','2000000000497','Engine Oils','270','5','283.5','30','5','12','2025-11-03 10:51:08','2025-11-03 10:51:08',NULL,'0',NULL,'Yamaha','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('50','battery','2000000000503','Battery Water & Solutions','200','10','220','20','5','12','2025-11-16 20:05:09','2025-11-16 20:05:09',NULL,'0',NULL,'','0');

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `sale_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `payment` decimal(10,2) NOT NULL,
  `change_given` decimal(10,2) NOT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Completed',
  `discount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('amount','percent') DEFAULT 'amount',
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`sale_id`),
  KEY `fk_sales_user` (`processed_by`),
  KEY `idx_sales_shift` (`shift_id`),
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('1','1','2','2025-11-15 15:15:26','391.00','500.00','62.08','41','completed','0.00','amount','46.92');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('2','1','2','2025-11-15 15:18:00','220.00','500.00','253.60','41','completed','0.00','amount','26.40');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('3','1','2','2025-11-15 15:19:57','385.00','500.00','68.80','41','completed','0.00','amount','46.20');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('4','1','2','2025-11-15 15:20:36','6384.00','8200.00','1049.92','41','completed','0.00','amount','766.08');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('5','1','2','2025-11-16 20:09:21','2200.00','30000.00','27536.00','41','completed','0.00','amount','264.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('6','1','2','2025-11-16 20:15:08','2625.00','3000.00','60.00','41','completed','0.00','amount','315.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('7','1','2','2025-11-16 21:33:50','336.00','400.00','23.68','41','completed','0.00','amount','40.32');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('8','1','2','2025-11-16 22:29:52','6720.00','10000.00','2473.60','41','completed','0.00','amount','806.40');

DROP TABLE IF EXISTS `sales_items`;
CREATE TABLE `sales_items` (
  `sales_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `vat` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`sales_item_id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('1','1','2','1','55.00','6.60');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('2','1','33','1','336.00','40.32');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('3','2','10','5','44.00','26.40');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('4','3','2','7','55.00','46.20');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('5','4','33','19','336.00','766.08');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('6','5','50','10','220.00','264.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('7','6','44','20','131.25','315.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('8','7','34','1','336.00','40.32');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('9','8','45','20','336.00','806.40');

DROP TABLE IF EXISTS `sales_refund_items`;
CREATE TABLE `sales_refund_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `sales_refunds`;
CREATE TABLE `sales_refunds` (
  `refund_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `refunded_by` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_reason` varchar(255) NOT NULL,
  `refund_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `refund_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shift_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`refund_id`),
  KEY `fk_refund_sale` (`sale_id`),
  KEY `fk_refund_user` (`refunded_by`),
  KEY `idx_refund_shift` (`shift_id`),
  CONSTRAINT `fk_refund_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_refund_user` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `sales_services`;
CREATE TABLE `sales_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `vat` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sales_services_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) DEFAULT NULL,
  `service_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `archived` tinyint(10) NOT NULL DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('1','5','Vulcanize','150.00','Tubeless puncture repair per tire; includes leak test and reinflation.','2025-11-11 13:58:28','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('2','5','Tire Replacement & Balancing','550.00','Mount/dismount tire and computer balancing per wheel; weights included.','2025-11-11 13:58:55','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('3','5','Oil Change (Motorcycle)','400.00','Labor only for drain/refill and filter swap; oil and filter charged separately.','2025-11-11 14:00:09','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('4','1','Oil Change (Sedan)','1500.00','Labor only for drain/refill and filter swap; oil and filter charged separately.','2025-11-11 14:00:51','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('5','1','Oil Change (Motorcycle)','400.00','Labor only for drain/refill and filter swap; oil and filter charged separately.','2025-11-11 14:01:43','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('6','1','Tire Replacement & Balancing','550.00','Mount/dismount tire and computer balancing per wheel; weights included.','2025-11-11 14:02:18','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('7','1','Brake Check & Repair','500.00','Brake inspection, cleaning, and adjustment; pads/rotors/fluids billed if needed.','2025-11-11 14:09:33','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('8','5','Brake Check & Repair','500.00','Brake inspection, cleaning, and adjustment; pads/rotors/fluids billed if needed.','2025-11-11 14:09:48','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('9','5','Battery Services','350.00','Battery test, terminal cleaning, and charging check','2025-11-11 14:10:50','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('10','1','Battery Services','350.00','Battery test, terminal cleaning, and charging check','2025-11-11 14:11:02','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('11','1','Vulcanize','150.00','Tubeless puncture repair per tire; includes leak test and reinflation.','2025-11-11 14:11:46','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('12','1','Wheel Alignment','1200.00','4-wheel computerized alignment with camber/toe adjustment','2025-11-11 14:13:18','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('13','4','Vulcanize','150.00','Tubeless puncture repair per tire; includes leak test and reinflation.','2025-11-11 14:15:22','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('14','4','Tire Replacement & Balancing','550.00','Mount/dismount tire and computer balancing per wheel; weights included','2025-11-11 14:19:11','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('15','4','Oil Change (Motorcycle)','400.00','Labor only for drain/refill and filter swap; oil and filter charged separately.','2025-11-11 14:19:34','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('16','4','Oil Change (Sedan)','1500.00','Labor only for drain/refill and filter swap; oil and filter charged separately.','2025-11-11 14:19:51','0',NULL);

DROP TABLE IF EXISTS `shift_cash_moves`;
CREATE TABLE `shift_cash_moves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_id` int(11) NOT NULL,
  `move_type` enum('pay_in','pay_out') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shift` (`shift_id`),
  CONSTRAINT `fk_shift_moves` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`shift_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('1','1','pay_out','2000.00','parcel!!','2025-11-15 15:09:37');

DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
  `shift_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `opening_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_note` varchar(255) DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `closing_cash` decimal(12,2) DEFAULT NULL,
  `expected_cash` decimal(12,2) DEFAULT NULL,
  `cash_difference` decimal(12,2) DEFAULT NULL,
  `closing_note` varchar(255) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  PRIMARY KEY (`shift_id`),
  KEY `idx_user_branch_status` (`user_id`,`branch_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `shifts` (`shift_id`,`user_id`,`branch_id`,`start_time`,`opening_cash`,`opening_note`,`end_time`,`closing_cash`,`expected_cash`,`cash_difference`,`closing_note`,`status`) VALUES ('1','41','1','2025-11-15 14:50:26','10000.00','','2025-11-15 15:10:42','8000.00','8000.00','0.00','sarap dto','closed');
INSERT INTO `shifts` (`shift_id`,`user_id`,`branch_id`,`start_time`,`opening_cash`,`opening_note`,`end_time`,`closing_cash`,`expected_cash`,`cash_difference`,`closing_note`,`status`) VALUES ('2','41','1','2025-11-15 15:14:56','1000.00','',NULL,NULL,NULL,NULL,NULL,'open');

DROP TABLE IF EXISTS `stock_in_requests`;
CREATE TABLE `stock_in_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_by` int(11) NOT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `decided_by` int(11) DEFAULT NULL,
  `decision_date` datetime DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_sir_product_id` (`product_id`),
  KEY `idx_sir_branch_id` (`branch_id`),
  KEY `idx_sir_requested_by` (`requested_by`),
  KEY `idx_sir_decided_by` (`decided_by`),
  CONSTRAINT `fk_sir_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sir_decided_by` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sir_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sir_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('1','1','1','2',NULL,'','approved','1','2025-11-15 17:10:54','1','2025-11-15 17:10:54','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('2','1','1','5',NULL,'','pending','42','2025-11-16 20:06:36',NULL,NULL,'0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('3','10','1','6','2025-11-17','','approved','1','2025-11-16 22:32:00','1','2025-11-16 22:32:00','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('4','50','1','1','2025-11-16','','approved','1','2025-11-16 22:47:09','1','2025-11-16 22:47:09','0');

DROP TABLE IF EXISTS `transfer_logs`;
CREATE TABLE `transfer_logs` (
  `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `source_branch` int(11) NOT NULL,
  `destination_branch` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `transfer_date` datetime NOT NULL DEFAULT current_timestamp(),
  `transferred_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`transfer_id`),
  KEY `product_id` (`product_id`),
  KEY `source_branch` (`source_branch`),
  KEY `destination_branch` (`destination_branch`),
  CONSTRAINT `transfer_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `transfer_logs_ibfk_2` FOREIGN KEY (`source_branch`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `transfer_logs_ibfk_3` FOREIGN KEY (`destination_branch`) REFERENCES `branches` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `transfer_requests`;
CREATE TABLE `transfer_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `source_branch` int(11) NOT NULL,
  `destination_branch` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `request_date` datetime DEFAULT current_timestamp(),
  `decision_date` datetime DEFAULT NULL,
  `decided_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`request_id`),
  KEY `idx_tr_product_id` (`product_id`),
  KEY `idx_tr_source_branch` (`source_branch`),
  KEY `idx_tr_destination_branch` (`destination_branch`),
  KEY `idx_tr_requested_by` (`requested_by`),
  KEY `idx_tr_decided_by` (`decided_by`),
  KEY `idx_tr_src_prod_status` (`source_branch`,`product_id`,`status`),
  CONSTRAINT `fk_tr_decided_by` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_destination_branch` FOREIGN KEY (`destination_branch`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_source_branch` FOREIGN KEY (`source_branch`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('1','1','1','2','2','1','approved','2025-11-02 13:51:52','2025-11-02 13:51:52','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('2','17','1','4','2','1','approved','2025-11-15 16:53:31','2025-11-15 16:53:31','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('3','18','1','5','5','1','approved','2025-11-15 16:54:05','2025-11-15 16:54:05','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('4','7','1','3','5','42','approved','2025-11-15 16:55:55','2025-11-15 16:56:37','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('5','30','1','5','5','1','approved','2025-11-15 16:58:57','2025-11-15 16:58:57','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('6','17','1','2','3','1','approved','2025-11-15 17:02:03','2025-11-15 17:02:03','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('7','2','1','2','2','1','approved','2025-11-15 17:03:43','2025-11-15 17:03:43','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('8','4','1','2','5','1','approved','2025-11-15 17:10:04','2025-11-15 17:10:04','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('9','30','1','2','3','42','rejected','2025-11-15 15:13:50','2025-11-15 15:14:04','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('10','1','1','5','1','42','pending','2025-11-16 19:57:51',NULL,NULL,'0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('11','1','1','2','5','42','pending','2025-11-16 20:07:32',NULL,NULL,'0');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `role` enum('admin','staff','stockman') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('1','admin123','Riza','09282876871','$2y$10$5NvLDzZGDyXCIYJUBQTXo.UyQVMOUs0BmXHiQ0tqjWa1aCaXtiITq','0','admin','2025-09-20 21:57:50',NULL,'0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('38','admin0205','Rich','09935844994','$2y$10$vfSq1Rl3JGaNek1b.yACsu44Mc5mTSQ9h/ex0TRIGrDgeNN7bsS9O','0','admin','2025-11-11 13:29:29',NULL,'0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('39','staffVik','Viktor','09352875218','$2y$10$lfKroXkGuNu28kDbAc2vQOfgea98ds2omGTjMTF7LOMGkIMwx5f1O','0','staff','2025-11-11 13:38:08','5','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('40','staffJM','JM','09215672315','$2y$10$QJFjBlgEFPVcWBnRFqdziOrRT2PwdBhzRFYMjBSwOY2LhohbNDWoy','0','staff','2025-11-11 13:39:12','4','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('41','StaffKJ','KJ','09944240934','$2y$10$ekN/9r86JNRo8MrimInVweS7dKCvrvYvAd3JSzJJdEm3tPlSzk8Li','0','staff','2025-11-11 13:42:12','1','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('42','StockmanJV','JV','09939903687','$2y$10$adrgLpM7b4t2pSygywvehOUK33kdK1bn7pOunSDU3k21.GT6.X/QK','0','stockman','2025-11-11 13:45:41','1','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('43','StockmanChael','Chael','09672576217','$2y$10$i15q.aQ.mX2Xf.Hee7gI/uMI5Hq/B5isOr80vs3o8BcXfl067VG7S','0','stockman','2025-11-11 13:47:13','5','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('44','StockmanTin','Tine','09215256528','$2y$10$BCqnQp.YDtbcSxWAx6hpkeadBK3r2sBodG5QfiDRJ0ImzMPVu34Ie','0','stockman','2025-11-11 13:49:35','4','0',NULL);

SET FOREIGN_KEY_CHECKS=1;
