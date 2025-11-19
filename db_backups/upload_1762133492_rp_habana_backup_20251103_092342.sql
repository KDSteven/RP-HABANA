-- Simple dump for rp_habana @ 20251103_092342
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
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('4','Best Drive');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('6','Blade');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('3','Bosny');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('9','Magic Gatas');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('2','Motolite');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('10','Pledge');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('5','Repsol');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('1','Shell');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('8','Slime');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('7','WD-40');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('11','Whiz');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('13','Battery Water & Solutions');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('21','Brake Fluids & Hydraulics');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('17','Car Care / Cleaning');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('19','Cleaning & Polishing');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('1','Engine Oils');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('22','Fuel & Gas Refills');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('20','Gear & Transmission Oils');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('16','Motorcycle Accessories');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('15','Spray Lubricants & Maintenance');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('14','Spray Paints & Coatings');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('18','Tire Sealants & Repair Fluids');

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('1','1','1','18','0',NULL,'2025-11-02 12:49:29','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('2','2','1','10','0',NULL,'2025-11-02 12:51:54','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('3','3','1','40','0',NULL,'2025-11-02 12:56:16','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('4','4','1','35','0',NULL,'2025-11-02 13:11:28','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('5','5','1','10','0',NULL,'2025-11-02 13:12:51','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('6','6','1','15','0',NULL,'2025-11-02 13:18:02','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('7','7','1','20','0',NULL,'2025-11-02 13:21:24','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('8','8','1','20','0',NULL,'2025-11-02 13:28:18','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('9','9','1','20','0',NULL,'2025-11-02 13:31:17','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('10','10','1','5','0',NULL,'2025-11-02 13:32:22','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('11','11','1','10','0',NULL,'2025-11-02 13:35:25','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('12','12','1','10','0',NULL,'2025-11-02 13:39:22','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('13','13','1','15','0',NULL,'2025-11-02 13:45:19','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('14','14','1','5','0',NULL,'2025-11-02 13:46:03','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('15','15','1','20','0',NULL,'2025-11-02 13:47:32','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('16','16','1','30','0',NULL,'2025-11-02 13:49:50','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('17','17','1','20','0',NULL,'2025-11-02 13:51:25','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('18','1','2','2','0',NULL,'2025-11-02 13:51:52','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('19','18','1','20','0',NULL,'2025-11-02 13:53:10','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('20','19','1','10','0',NULL,'2025-11-02 13:54:55','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('21','20','1','20','0',NULL,'2025-11-02 13:58:36','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('22','21','1','10','0',NULL,'2025-11-03 09:11:08','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('23','22','1','20','0',NULL,'2025-11-03 09:12:34','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('24','23','1','35','0',NULL,'2025-11-03 09:13:54','0');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('1','11','1','2030-11-02','10');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('2','12','1','2030-11-02','10');

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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('1','1','Create Branch','Created branch: Bucal - Main Branch (#101) (rphabana_bucal@gmail.com)','2025-11-02 12:17:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('2','1','Create Branch','Created branch: Halang Branch 1 (#102) (rphabnana_halang1@gmail.com)','2025-11-02 12:19:32','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('3','1','Create Branch','Created branch: Halang Branch 2 (#103) (rphabana_halang2@gmail.com)','2025-11-02 12:22:32','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('4','1','Create Branch','Created branch: Batino Branch (#104) (rphabana_batino@gmail.com)','2025-11-02 12:23:58','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('5','1','Create Branch','Created branch: Barandal Branch (#105) (rphabana_barandal@gmail.com)','2025-11-02 12:28:07','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('6','1','Create Branch','Created branch: Lawa Branch 1 (#106) (rphabana_lawa1@gmail.com)','2025-11-02 12:29:20','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('7','1','Create Branch','Created branch: Lawa Branch 2 (#107) (rphabana_lawa2@gmail.com)','2025-11-02 12:30:30','7');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('8','1','Add Product','Added product \'2T Advance\' (ID: 1) with stock 20 to branch 1','2025-11-02 12:49:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('9','1','Add Product','Added product \'Battery Solution\' (ID: 2) with stock 10 to branch 1','2025-11-02 12:51:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('10','1','Add Product','Added product \'Bosny Paint\' (ID: 3) with stock 40 to branch 1','2025-11-02 12:56:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('11','1','Add Product','Added product \'Bosny Gold\' (ID: 4) with stock 35 to branch 1','2025-11-02 13:11:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('12','1','Add Product','Added product \'Extreme Paint Best Drive\' (ID: 5) with stock 10 to branch 1','2025-11-02 13:12:51',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('13','1','Add Product','Added product \'Thunder\' (ID: 6) with stock 15 to branch 1','2025-11-02 13:18:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('14','1','Logout','User logged out.','2025-11-02 13:19:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('15','1','Login successful','','2025-11-02 13:19:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('16','1','Add Product','Added product \'Top Sports Oil\' (ID: 7) with stock 20 to branch 1','2025-11-02 13:21:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('17','1','Logout','User logged out.','2025-11-02 13:21:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('18','3','Login successful','','2025-11-02 13:21:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('19','3','Logout','User logged out.','2025-11-02 13:22:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('20','1','Login successful','','2025-11-02 13:22:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('21','1','Archive Account','Archived user: Budoy (username: staff001, role: staff)','2025-11-02 13:23:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('22','1','Archive Account','Archived user: Kent (username: Staff005, role: staff)','2025-11-02 13:23:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('23','1','Archive Account','Archived user: Dudong (username: Staff123, role: staff)','2025-11-02 13:23:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('24','1','Archive Account','Archived user: barandal.stockman (username: barandal.stockman, role: stockman)','2025-11-02 13:23:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('25','1','Archive Account','Archived user: Dudong (username: Staff2, role: staff)','2025-11-02 13:23:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('26','1','Archive Account','Archived user: staff002 (username: staff002, role: staff)','2025-11-02 13:23:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('27','1','Archive Account','Archived user: Kenken (username: Stockman1, role: stockman)','2025-11-02 13:23:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('28','1','Archive Account','Archived user: Staff004 (username: Staff004, role: staff)','2025-11-02 13:23:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('29','1','Archive Account','Archived user: staff003 (username: staff003, role: staff)','2025-11-02 13:23:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('30','1','Create Account','Created new user: StaffKenneth01 (KJ), role: staff, phone: 09278275127, branch_id=1','2025-11-02 13:26:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('31','1','Logout','User logged out.','2025-11-02 13:26:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('32','36','Login successful','','2025-11-02 13:26:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('33','36','Logout','User logged out.','2025-11-02 13:26:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('34','1','Login successful','','2025-11-02 13:26:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('35','1','Add Product','Added product \'VS1\' (ID: 8) with stock 20 to branch 1','2025-11-02 13:28:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('36','1','Add Product','Added product \'WD40 Lubricant Spray\' (ID: 9) with stock 20 to branch 1','2025-11-02 13:31:17',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('37','1','Add Product','Added product \'Water Solution\' (ID: 10) with stock 5 to branch 1','2025-11-02 13:32:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('38','1','Add Product','Added product \'ZEALANT TIRE big\' (ID: 11) with stock 10 to branch 1','2025-11-02 13:35:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('39','1','Add Product','Added product \'ZEALANT TIRE small\' (ID: 12) with stock 10 to branch 1','2025-11-02 13:39:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('40','1','Add Product','Added product \'Magic Gatas 500ml\' (ID: 13) with stock 15 to branch 1','2025-11-02 13:45:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('41','1','Add Product','Added product \'Magic Gatas 200ml\' (ID: 14) with stock 5 to branch 1','2025-11-02 13:46:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('42','1','Add Product','Added product \'Tire Black\' (ID: 15) with stock 20 to branch 1','2025-11-02 13:47:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('43','1','Add Product','Added product \'Metal Polish\' (ID: 16) with stock 30 to branch 1','2025-11-02 13:49:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('44','1','Add Product','Added product \'Anti-Rust\' (ID: 17) with stock 20 to branch 1','2025-11-02 13:51:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('45','1','Stock Transfer','Transferred 2 2T Advance from Bucal - Main Branch to Halang Branch 1','2025-11-02 13:51:52','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('46','1','Add Product','Added product \'BS-40\' (ID: 18) with stock 20 to branch 1','2025-11-02 13:53:10',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('47','1','Add Product','Added product \'Moto Gear Oil\' (ID: 19) with stock 10 to branch 1','2025-11-02 13:54:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('48','1','Add Product','Added product \'WHIZ Brake Fluid\' (ID: 20) with stock 20 to branch 1','2025-11-02 13:58:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('49','1','Login successful','','2025-11-02 22:08:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('50','1','Logout','User logged out.','2025-11-02 22:10:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('51','1','Login successful','','2025-11-03 09:06:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('52','1','Add Product','Added product \'WHIZ Brake Fluid 170ml\' (ID: 21) with stock 10 to branch 1','2025-11-03 09:11:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('53','1','Add Product','Added product \'WHIZ Brake Fluid 4T 300ml\' (ID: 22) with stock 20 to branch 1','2025-11-03 09:12:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('54','1','Add Product','Added product \'WHIZ Brake Fluid 4T 900ml\' (ID: 23) with stock 35 to branch 1','2025-11-03 09:13:54',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('1','1','1','2','2','1','approved','2025-11-02 13:51:52','2025-11-02 13:51:52','1','0');

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
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('1','admin123','Riza','09282876871','$2y$10$5NvLDzZGDyXCIYJUBQTXo.UyQVMOUs0BmXHiQ0tqjWa1aCaXtiITq','0','admin','2025-09-20 21:57:50',NULL,'0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('3','staff001','Budoy','','$2y$10$WSf318UBRgxqo1IToZu1xeJOu1LFLId.3U7WMHCLUhcKGjAY1.722','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('9','staff003','staff003','','$2y$10$4er4bKqGg2HNd9IAKuFYyOO2jPYM.tPT4IhwviLLJcZfcHJbNaNf2','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('11','Staff004','Staff004','','$2y$10$fVt.3Km2TI1.8/r0t67J4uwVfZSjDHqPjpc6fqFhFF9kz/v8/VQhK','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('15','Stockman1','Kenken','09935844994','$2y$10$Ojydo56b8qhUmHscrq.l1ezA/38GAsZDHkC00XnPZrlP16scb072S','0','stockman','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('19','staff002','staff002','','$2y$10$FIjQ51vAFbIgtBlgnzaxWOjjjde9539jhGK1oBFUtKN66p00z50UW','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('20','yrrabz112426','yrrabz112426','','$2y$10$yntX5vvM8uVPE4WmiDVD9eX25c.dzdv9usXsI8DOQlqDBmO6jeSE2','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('21','bars123','bars123','','$2y$10$CDBlvkq4DkrwrrL2QZLm6OfBzFsr..wg6SFfrRIxKTyXNG/El442.','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('22','cooljohnric24@gmail.com','cooljohnric24@gmail.com','','$2y$10$kjdNpET/DWIzSvx4c9EpxOLJ.NP3dT1rulF0KtYnqgVjUGJlxilrq','0','admin','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('25','sertaposnapo','sertaposnapo','','$2y$10$5aMr9lnohaAWPsoM90ueeOy0O8fcMK13NFD5vC9iDjh5xosDyVrDi','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('26','Staff2','Dudong','','$2y$10$T/.ZRA5yUPNkpQvmPjZ9JOf9Td.nCEvvsV0BbuOmbqugT6qn1jZoe','0','staff','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('27','barandal.stockman','barandal.stockman','','$2y$10$a.IanD2C.ESAjYhA6SY4j.bW.xiU.4l6wjpw5o.71zPAbyZGSJyWG','0','stockman','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('28','bucal.stockman','bucal.stockman','','$2y$10$zhG53lSYMVVTfHU/10MfZO1Ct8QvZZHFnyg/MORiCB20jynIruY9C','0','stockman','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('31','Staff123','Dudong','09215672315','$2y$10$icRCTwxsqTTcbhPG0LxYUeChCvErOtyA5hqcobB1QjdFztpyjTsT.','0','staff','2025-09-23 20:44:01',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('33','Staff005','Kent','','$2y$10$7BStMYTn7e/rzmVkPYU/IeHWLr7MDqS0DZAqZXwPdmMsjyjdF88qO','0','staff','2025-09-24 15:57:18',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('34','Gel123','Reign','09944240934','$2y$10$atZp3CksAZQJlNo0KX0aDOFk3kwSFNKBrugeQModNKa19jjio2OU2','0','staff','2025-10-20 18:51:01',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('35','Stockman02','Jerry','09854910625','$2y$10$OAKDtoRLxJg0CqhrGNMNf.MAh.5bFUSmqVZJm56pgS8BlDJvCJn5.','0','admin','2025-10-31 14:45:17',NULL,'0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('36','StaffKenneth01','KJ','09278275127','$2y$10$LegyTY/WdPBkXceFpZOto.eXMyQIRBMUnAD6SmXjD0H4ZX7oi2KKy','0','staff','2025-11-02 13:26:04','1','0',NULL);

SET FOREIGN_KEY_CHECKS=1;
