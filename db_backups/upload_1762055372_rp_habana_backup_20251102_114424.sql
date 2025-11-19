-- Simple dump for rp_habana @ 20251102_114424
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

INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('1','Bucal Branch','Bucal Bypass road','bypass@email.com','Riza',NULL,'2025-09-20 21:58:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('2','Batino Branch','Brgy. Batino near MCDO','batino@email.com','RJ',NULL,'2025-09-20 21:58:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('3','Prinza Branch','Brgy. Prinza','prinza@email.com','Mark',NULL,'2025-09-20 21:58:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('4','Halang Branch','Brgy. Halang','halang@email.com','AJ',NULL,'2025-09-20 21:58:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('5','00Bucal','Bucal','1@gmail.com','21','09282876871','2025-09-20 21:58:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('6','Barandal Branch','Barandal','example@email.com','','0','2025-09-20 21:58:20','0',NULL);
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`created_at`,`archived`,`archived_at`) VALUES ('7','San Juan','San Juan','aerene@gmail.com','Aerene','2147483647','2025-09-24 16:56:15','0',NULL);

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(100) NOT NULL,
  PRIMARY KEY (`brand_id`),
  UNIQUE KEY `brand_name` (`brand_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('2','Bridgestone');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('6','Castrol');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('5','Flamingo');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('3','Goodyear');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('7','Iphone');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('1','Michelin');
INSERT INTO `brands` (`brand_id`,`brand_name`) VALUES ('4','Petron');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('3','Electronics');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('2','Liquid');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('1','Solid');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('6','Tire');

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
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('9','9','2','10','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('11','11','3','8','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('13','13','3','7','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('14','14','3','9','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('17','8','4','8','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('18','6','4','9','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('20','16','1','200','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('21','6','1','21','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('46','43','1','18','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('47','44','1','18','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('48','45','1','19','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('49','46','1','12','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('50','47','1','10','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('51','48','5','20','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('84','42','1','9','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('85','49','2','0','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('86','49','1','1','1','2025-09-21 18:54:36','2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('91','50','6','3','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('99','44','2','3','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('100','56','6','10','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('102','58','6','10','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('103','59','4','3','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('106','42','6','2','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('107','44','6','-3','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('108','6',NULL,'5','0',NULL,'2025-09-20 21:49:05','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('121','67','5','10','0',NULL,'2025-09-20 22:17:38','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('124','70','1','19','0',NULL,'2025-09-21 19:24:27','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('127','44','5','8','0',NULL,'2025-09-23 18:16:00','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('129','67','6','10','0',NULL,'2025-09-24 18:36:21','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('130','70','6','5','0',NULL,'2025-09-24 18:49:57','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('131','70','2','5','0',NULL,'2025-09-24 18:50:09','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('134','74','1','31','1','2025-09-28 15:32:30','2025-09-28 15:22:39','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`,`archived_at`,`created_at`,`reserved_outgoing`) VALUES ('135','75','1','104','0',NULL,'2025-09-28 15:34:00','0');

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('2','74','1','2025-12-01','10');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('3','75','1','2025-12-01','10');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('4','75','1','2060-08-22','2');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('5','75','1','2025-12-25','5');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('6','75','1','2027-12-23','5');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('7','75','1','2029-12-23','5');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('8','75','1','2028-02-25','5');
INSERT INTO `inventory_lots` (`lot_id`,`product_id`,`branch_id`,`expiry_date`,`qty`) VALUES ('9','75','1','2025-10-22','5');

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
) ENGINE=InnoDB AUTO_INCREMENT=991 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('6','1','Add Product','Added product:  (ID: )','2025-08-12 17:33:46',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('7','1','Archive Product','Archived product:  (ID: 50)','2025-08-12 17:35:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('8','1','Archive Product','Archived product:  (ID: 52)','2025-08-12 17:40:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('9','1','Archive Product','Archived product:  (ID: 51)','2025-08-12 17:40:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('10','1','Archive Product','Archived product:  (ID: 53)','2025-08-12 17:40:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('11','1','Add Product','Added product \'java chip\' (ID: 55) with stock 2 to Barandal Branch','2025-08-12 17:41:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('12','1','Edit Product','Edited product ID : ','2025-08-12 17:45:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('13','1','Edit Product','Edited product ID 55: vat changed from \'12\' to \'121\'','2025-08-12 17:46:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('14','1','Archive Service','Archived service:  (ID: 6)','2025-08-12 17:51:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('15','1','Archive Product','Archived product:  (ID: 6)','2025-08-24 21:39:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('16','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:48:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('17','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:48:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('18','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:53:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('19','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:53:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('20','1','Archive Product','Archived product: Generator (ID: 9)','2025-08-24 21:58:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('21','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 22:00:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('22','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 22:00:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('23','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 22:02:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('24','1','Archive Product','Archived product: H931 (ID: 13)','2025-08-24 22:03:04','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('25','1','Archive Service','Archived service: Oil Change (ID: 6)','2025-08-24 22:04:14','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('26','3','Add Product to Cart','Added 1 of Sealant INF to cart','2025-08-24 22:13:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('27','3','Add Service to Cart','Added 1 service: Tire Rotation (ID: 2) to cart','2025-08-24 22:17:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('28','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-26 13:53:26','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('29','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:08:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('30','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:08:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('31','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:08:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('32','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:08:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('33','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:09:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('34','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:11:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('35','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:11:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('36','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:12:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('37','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:12:59',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('38','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:13:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('39','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:15:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('40','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:17:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('41','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:21:17',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('42','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:21:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('43','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:22:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('44','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:25:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('45','1','Archive Product','Archived product:  (Inventory ID: 0)','2025-08-26 15:26:13',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('46','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 52)','2025-08-26 15:30:20','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('47','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 52)','2025-08-26 15:30:23','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('48','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 52)','2025-08-26 15:32:11','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('49','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 52)','2025-08-26 15:32:33','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('50','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 52)','2025-08-26 15:36:03','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('51','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 86)','2025-08-26 15:47:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('52','3','Add Product to Cart','Added 1 of ATF Premium to cart','2025-08-27 14:03:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('53','3','Add Product to Cart','Added 1 of ATF Premium to cart','2025-08-27 14:05:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('54','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-27 14:09:21','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('55','3','Add Product to Cart','Added 1 of ATF Premium to cart','2025-08-27 14:11:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('56','3','Add Product to Cart','Added 1 of ATF Premium to cart','2025-08-27 14:12:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('57','3','Add Product to Cart','Added 1 of M040 to cart','2025-08-27 14:21:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('58','3','Add Product to Cart','Added 1 of M040 to cart','2025-08-27 14:22:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('59','3','Add Product to Cart','Added 1 of M040 to cart','2025-08-27 14:25:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('60','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-27 14:33:25','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('61','1','Archive Product','Archived product: java chip (Inventory ID: 95)','2025-08-27 19:46:43','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('62','1','Create Account','Created user: lablab ko, role: staff, branch: Prinza Branch','2025-08-27 20:38:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('63','1','Add Product','Added product \'Ding dong\' (ID: 56) with stock 5 to Barandal Branch','2025-08-28 02:06:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('64','1','Add Service','Added service \'Oil Change\' (ID: 15) to Bucal Branch','2025-08-28 02:10:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('65','1','Edit Product','Edited product ID 56: retail_price changed from \'\' to \'22\'; ceiling_point changed from \'5\' to \'10\'; critical_point changed from \'5\' to \'3\'; vat changed from \'\' to \'123\'','2025-08-28 02:12:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('66','1','Archive Product','Archived product: Ding dong (Inventory ID: 100)','2025-08-28 02:14:33','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('67','1','Archive Service','Archived service: Oil Change (ID: 14)','2025-08-28 02:14:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('68','1','Archive Service','Archived service: Oil Change (ID: 15)','2025-08-28 02:14:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('69','3','Add Product to Cart','Added 1 of M040 to cart','2025-08-28 02:30:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('70','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:30:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('71','3','Add Product to Cart','Added 1 of M040 to cart','2025-08-28 02:31:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('72','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:33:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('73','3','Add Product to Cart','Added 1 of M040 to cart','2025-08-28 02:33:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('74','3','Add Product to Cart','Added 1 of Sealant INF to cart','2025-08-28 02:35:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('75','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:37:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('76','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:37:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('77','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:38:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('78','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:38:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('79','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:38:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('80','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:38:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('81','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:38:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('82','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-08-28 02:39:25','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('83','3','Add Product to Cart','Added 1 of Tire 70 X 80 X 14 to cart','2025-08-28 02:43:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('84','3','Add Product to Cart','Added 1 of Tire 70 X 80 X 14 to cart','2025-08-28 02:54:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('85','3','Add Product to Cart','Added 1 of M040 to cart','2025-08-28 02:54:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('86','3','Add Service to Cart','Added 1 service: Oil Change (ID: 14) to cart','2025-08-28 03:06:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('87','1','Archive Product','Archived product: java chip (Inventory ID: 96)','2025-08-28 04:21:26','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('88','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-09-03 15:37:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('89','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-09-03 15:37:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('90','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-09-03 15:37:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('91','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-09-03 15:37:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('92','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-09-03 15:37:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('93','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-09-03 15:37:42','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('94','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-03 15:37:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('95','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-03 15:37:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('96','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-03 15:37:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('97','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-03 15:37:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('98','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-03 15:37:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('99','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-03 15:37:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('100','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-03 15:37:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('101','1','Create Account','Created user: sertaposnapo, role: staff, branch: Halang Branch','2025-09-03 15:46:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('102','1','Create Account','Created user: Staff2, role: staff, branch: Halang Branch','2025-09-03 15:52:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('103','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:10:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('104','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:10:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('105','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:11:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('106','1','Add Product','Added product \'Iphone 17 Viva Max\' (ID: 58) with stock 10 to Barandal Branch','2025-09-04 19:35:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('107','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:43:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('108','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:57:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('109','15','Login','User Stockman1 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:00:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('110','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:00:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('111','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:06:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('112','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:08:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('113','15','Login','User Stockman1 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:12:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('114','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:12:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('115','1','Create Account','Created user: barandal.stockman, role: stockman, branch: Barandal Branch','2025-09-04 20:19:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('116','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:19:49',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('117','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:23:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('118','3','Add Service to Cart','Added 1 service: Oil Change (ID: 15) to cart','2025-09-04 20:23:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('119','3','Add Service to Cart','Added 1 service: Oil Change (ID: 14) to cart','2025-09-04 20:29:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('120','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-04 20:30:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('121','3','Add Product to Cart','Added 1 of A/C Pro to cart','2025-09-04 20:49:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('122','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-04 20:51:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('123','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-04 20:52:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('124','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-04 20:55:07','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('125','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-04 20:56:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('126','3','Add Product to Cart','Added 1 of M040 to cart','2025-09-04 20:58:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('127','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 21:17:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('128','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 21:22:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('129','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 52)','2025-09-04 21:35:09','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('130','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 85)','2025-09-04 21:35:20','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('131','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 21:43:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('132','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 21:45:18','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('133','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 21:45:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('134','15','Login','User Stockman1 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 21:48:18','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('135','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 21:48:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('136','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:21:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('137','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:44:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('138','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:44:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('139',NULL,'Login Failed','Failed login for username: bucal.stockman. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:46:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('140','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:47:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('141','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:47:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('142','1','Create Account','Created user: bucal.stockman, role: stockman, branch: Bucal Branch','2025-09-05 22:47:51',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('143','28','Login','User bucal.stockman logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:47:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('144','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:48:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('145',NULL,'Login Failed','Failed login for username: bucal.stockman. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:48:44',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('146','28','Login','User bucal.stockman logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:48:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('147','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 22:51:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('148','1','Add Stock','Added 2 stock to A/C Pro (ID: 44)','2025-09-05 22:53:16','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('149','1','Add Stock','Added 1 stock to A/C Pro (ID: 44)','2025-09-05 22:57:36','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('150','1','Add Stock','Added 1 stock to A/C Pro (ID: 44)','2025-09-05 23:02:56','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('151','1','Add Stock','Added 1 stock to A/C Pro (ID: 44)','2025-09-05 23:05:04','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('152','1','Add Stock','Added 1 stock to A/C Pro (ID: 44)','2025-09-05 23:05:23','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('153','1','Add Stock','Added 1 stock to A/C Pro (ID: 44)','2025-09-05 23:07:11','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('154','1','Add Stock','Added 1 stock to Generator (ID: 9)','2025-09-05 23:07:38','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('155','1','Add Stock','Added 2 stock to ATF Premium (ID: 46)','2025-09-05 23:07:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('156','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:11:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('157','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:12:21','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('158','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:12:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('159','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:13:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('160','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:15:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('161','1','Add Service','Added service \'Oil Change\' (ID: 16) to Batino Branch','2025-09-05 23:15:39',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('162','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:16:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('163','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:16:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('164','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:17:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('165','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:17:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('166','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:20:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('167','1','Add Stock','Added 2 stock to Laptop (ID: 6)','2025-09-05 23:28:25','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('168','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:33:09','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('169','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:35:21','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('170','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-05 23:36:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('171','1','Add Stock','Added 1 stock to Pulsar Mat (ID: 8)','2025-09-05 23:40:16','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('172','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:42:13','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('173','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:43:19','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('174','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:47:28','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('175','1','Add Stock','Added 1 stock to Laptop (ID: 6)','2025-09-05 23:57:00','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('176','1','Add Service','Added service \'Oil Change\' (ID: 17) to Halang Branch','2025-09-06 00:10:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('177','1','Archive Service','Archived service: Oil Change (ID: 17)','2025-09-06 00:15:08','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('178','1','Add Service','Added service \'Computer Wheel Alignment\' (ID: 18) to Halang Branch','2025-09-06 00:16:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('179','1','Add Service','Added service \'223w\' (ID: 19) to Halang Branch','2025-09-06 00:16:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('180','1','Add Stock','Added 1 stock to Pulsar Mat (ID: 8)','2025-09-06 00:16:49','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('181','1','Add Stock','Added 1 stock to Pulsar Mat (ID: 8)','2025-09-06 00:17:11','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('182','1','Add Product','Added product \'80x80x80\' (ID: 59) with stock 2 to Halang Branch','2025-09-06 00:28:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('183','1','Add Service','Added service \'Oil Change\' (ID: 20) to Halang Branch','2025-09-06 00:30:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('184','1','Add Stock','Added 1 stock to 80x80x80 (ID: 59)','2025-09-06 00:33:16','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('185','1','Add Service','Added service \'asd\' (ID: 21) to Halang Branch','2025-09-06 00:34:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('186','1','Archive Service','Archived service: 223w (ID: 19)','2025-09-06 00:34:37','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('187','1','Archive Service','Archived service: Oil Change (ID: 20)','2025-09-06 00:34:41','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('188','1','Archive Service','Archived service: asd (ID: 21)','2025-09-06 00:34:44','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('189','1','Edit Product','Edited product ID 59: category changed from \'\' to \'Solid\'; markup_price changed from \'10\' to \'20\'; retail_price changed from \'\' to \'220\'; vat changed from \'\' to \'12\'','2025-09-06 01:00:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('190','1','Edit Product','Edited product ID 44: retail_price changed from \'\' to \'385\'; vat changed from \'\' to \'23\'','2025-09-06 01:08:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('191','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-06 01:50:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('192','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-06 02:25:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('193','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-06 02:25:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('194','1','Database Restored','File: rp_habana_backup_20250907_115114.sql','2025-09-14 17:48:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('195','1','Login successful','','2025-09-14 17:51:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('196','1','Reject Password Reset','Reset rejected for reset_id=6','2025-09-14 17:51:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('197','1','Login successful','','2025-09-14 17:51:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('198','1','Login successful','','2025-09-14 17:51:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('199','1','Approve Password Reset','Reset approved for user_id=3','2025-09-14 17:51:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('200','3','Login successful','','2025-09-14 17:51:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('201','1','Login successful','','2025-09-14 17:51:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('202','1','Login successful','','2025-09-14 18:00:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('203','1','Reject Password Reset','Reset rejected for reset_id=8','2025-09-14 18:25:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('204','1','Login successful','','2025-09-14 18:32:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('205','1','Login successful','','2025-09-14 18:50:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('206','1','Login successful','','2025-09-14 18:51:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('207','1','Login successful','','2025-09-14 19:04:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('208','1','Login successful','','2025-09-14 19:11:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('209','1','Login successful','','2025-09-14 19:12:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('210','1','Login successful','','2025-09-14 19:14:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('211',NULL,'Login failed for username: admin123','','2025-09-14 19:15:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('212','1','Login successful','','2025-09-14 19:15:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('213',NULL,'Login failed for username: admin123','','2025-09-15 12:07:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('214','1','Login successful','','2025-09-15 12:07:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('215','1','Login successful','','2025-09-15 13:25:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('216','15','Login successful','','2025-09-15 13:43:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('217','1','Login successful','','2025-09-15 18:55:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('218','15','Login successful','','2025-09-15 19:30:49','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('219','1','Login successful','','2025-09-15 19:31:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('220','15','Login successful','','2025-09-15 19:51:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('221','1','Login successful','','2025-09-15 19:54:46',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('222','15','Login successful','','2025-09-15 20:00:14','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('223','1','Login successful','','2025-09-15 20:00:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('224','1','Add Stock','Added 5 stocks to Laptop (Branch )','2025-09-15 20:07:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('225','15','Login successful','','2025-09-15 20:07:38','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('226','15','Stock-In Request','Requested stock-in of 5 Laptop to Bucal Branch','2025-09-15 20:12:38','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('227','1','Login successful','','2025-09-15 20:12:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('228','1','Add Stock','Added 5 stocks to Laptop (Branch Bucal Branch)','2025-09-15 20:13:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('229','1','Add Stock','Added 5 stocks to ATF Premium (Branch Bucal Branch)','2025-09-15 20:17:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('230','15','Login successful','','2025-09-15 20:19:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('231','15','Stock-In Request','Requested stock-in of 2 ATF Premium to Bucal Branch','2025-09-15 20:19:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('232','1','Login successful','','2025-09-15 20:19:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('233','1','Login successful','','2025-09-15 20:22:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('234','1','Add Stock','Added 2 stocks to A/C Pro (Branch Bucal Branch)','2025-09-15 20:23:35','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('235','1','Add Stock','Added 3 stocks to A/C Pro (Branch Bucal Branch)','2025-09-15 20:24:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('236','15','Login successful','','2025-09-15 20:24:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('237','15','Stock-In Request','Requested stock-in of 3 ATF Premium to Bucal Branch','2025-09-15 20:25:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('238','1','Login successful','','2025-09-15 20:25:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('239','1','Add Stock','Added 3 stocks to M040 (Branch Bucal Branch)','2025-09-15 20:27:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('240','15','Login successful','','2025-09-15 20:27:21','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('241','15','Stock-In Request','Requested stock-in of 5 A/C Pro to Bucal Branch','2025-09-15 20:27:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('242','1','Login successful','','2025-09-15 20:28:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('243','15','Login successful','','2025-09-15 20:28:26','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('244','15','Stock-In Request','Requested stock-in of 5 M040 to Bucal Branch','2025-09-15 20:28:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('245','1','Login successful','','2025-09-15 20:29:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('246','1','Login successful','','2025-09-15 20:31:44',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('247','15','Login successful','','2025-09-15 20:31:49','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('248','15','Login successful','','2025-09-15 20:31:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('249','15','Stock-In Request','Requested stock-in of 5 A/C Pro to Bucal Branch','2025-09-15 20:32:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('250','1','Login successful','','2025-09-15 20:32:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('251','15','Login successful','','2025-09-15 20:43:35','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('252','15','Stock-In Request','Requested stock-in of 5 A/C Pro to Bucal Branch','2025-09-15 20:44:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('253',NULL,'Login failed for username: admin123','','2025-09-15 20:44:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('254','1','Login successful','','2025-09-15 20:44:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('255','15','Login successful','','2025-09-15 20:44:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('256','1','Login successful','','2025-09-15 20:46:46',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('257','1','Stock Transfer','Transferred 5 A/C Pro from Bucal Branch to Barandal Branch','2025-09-15 21:05:46','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('258','1','Stock Transfer','Transferred 5 A/C Pro from Bucal Branch to Barandal Branch','2025-09-15 21:06:19','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('259','15','Login successful','','2025-09-15 21:06:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('260','15','Stock Transfer Request','Requested transfer of 5 A/C Pro from Barandal Branch to Bucal Branch','2025-09-15 21:07:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('261','1','Login successful','','2025-09-15 21:07:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('262','15','Login successful','','2025-09-15 21:11:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('263','1','Login successful','','2025-09-15 21:12:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('264','15','Login successful','','2025-09-15 21:12:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('265','15','Login successful','','2025-09-15 21:25:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('266','1','Login successful','','2025-09-15 21:44:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('267','15','Login successful','','2025-09-15 22:04:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('268','1','Login successful','','2025-09-15 22:04:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('269',NULL,'Login failed for username: staff001','','2025-09-15 22:08:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('270',NULL,'Login failed for username: staff001','','2025-09-15 22:08:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('271','1','Login successful','','2025-09-15 22:08:39',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('272','1','Approve Password Reset','Reset approved for user_id=3','2025-09-15 22:08:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('273','3','Login successful','','2025-09-15 22:08:49','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('274',NULL,'Login failed for username: admin123','','2025-09-15 22:09:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('275','1','Login successful','','2025-09-15 22:09:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('276','3','Login successful','','2025-09-15 22:11:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('277','1','Login successful','','2025-09-15 22:11:20',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('278','15','Login successful','','2025-09-15 22:11:54','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('279','1','Login successful','','2025-09-15 22:11:59',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('280','15','Login successful','','2025-09-15 22:34:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('281','1','Login successful','','2025-09-15 22:34:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('282','1','Archive Product','Archived product: Jeep Customize Limited Edition (Inventory ID: 86)','2025-09-15 22:36:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('283',NULL,'Login','Successful','2025-09-15 22:39:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('284','1','Login successful','','2025-09-15 22:39:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('285',NULL,'Login','Successful','2025-09-15 22:39:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('286','1','Login successful','','2025-09-15 22:39:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('287','15','Login successful','','2025-09-15 22:50:19','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('288','1','Login successful','','2025-09-15 22:50:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('289','1','Add Stock','Added 5 stocks to A/C Pro (Branch Bucal Branch)','2025-09-15 22:51:26','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('290','1','Stock Transfer','Transferred 5 A/C Pro from Bucal Branch to Barandal Branch','2025-09-15 22:51:38','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('291','15','Login successful','','2025-09-15 22:54:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('292','15','Stock Transfer Request','Requested transfer of 5 A/C Pro from Barandal Branch to Bucal Branch','2025-09-15 22:54:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('293','1','Login successful','','2025-09-15 22:54:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('294','1','Login successful','','2025-09-16 13:44:48',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('295','1','Physical Inventory Saved','Saved product_id=47, physical_count=10, status=Complete','2025-09-16 13:59:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('296','15','Login successful','','2025-09-16 14:24:42','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('297','15','Stock Transfer Request','Requested transfer of 5 A/C Pro from Barandal Branch to Bucal Branch','2025-09-16 14:24:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('298','1','Login successful','','2025-09-16 14:24:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('299','1','Login successful','','2025-09-16 14:25:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('300','15','Login successful','','2025-09-16 14:25:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('301','15','Stock-In Request','Requested stock-in of 10 M040 to Bucal Branch','2025-09-16 14:25:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('302','1','Login successful','','2025-09-16 14:26:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('303','1','Add Stock','Added 5 stocks to M040 (Branch Bucal Branch)','2025-09-16 14:33:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('304','1','Add Stock','Added 5 stocks to M040 (Branch Bucal Branch)','2025-09-16 14:35:54','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('305','1','Add Stock','Added 5 stocks to Engine Oil (Branch Prinza Branch)','2025-09-16 14:39:32','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('306','1','Login successful','','2025-09-16 14:39:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('307','1','Login successful','','2025-09-17 00:20:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('308','1','Restore Product','Restored product: Jeep Customize Limited Edition (ID: 86)','2025-09-17 00:33:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('309','1','Restore Product','Restored product: Jeep Customize Limited Edition (ID: 85)','2025-09-17 00:34:05','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('310','1','Restore Product','Restored product: java chip (ID: 95)','2025-09-17 00:46:01','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('311','1','Archive Product','Archived product: java chip (Inventory ID: 95)','2025-09-17 00:46:32','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('312','1','Delete Product','Deleted product:  (ID: 95)','2025-09-17 00:46:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('313','1','Delete Product','Deleted product:  (ID: 95)','2025-09-17 00:50:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('314','1','Delete Product','Deleted product: java chip (ID: 96)','2025-09-17 00:50:21','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('315','1','Delete Product','Deleted product:  (ID: 96)','2025-09-17 00:50:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('316','1','Delete Service','Deleted service: Oil Change (ID: 15)','2025-09-17 00:50:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('317','1','Delete Service','Deleted service:  (ID: 15)','2025-09-17 00:51:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('318','1','Delete Service','Deleted service: Oil Change (ID: 17)','2025-09-17 00:53:24','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('319','1','Delete Service','Deleted service: 223w (ID: 19)','2025-09-17 00:53:27','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('320','1','Delete Product','Deleted product: Jeep Customize Limited Edition (ID: 52)','2025-09-17 01:00:57','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('321','1','Delete User','Deleted user: lablab ko (ID: 24)','2025-09-17 01:01:07','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('322','1','Delete Service','Deleted service: Oil Change (ID: 14)','2025-09-17 01:07:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('323','15','Login successful','','2025-09-17 01:21:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('324','1','Login successful','','2025-09-17 01:28:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('325','1','Restore Service','Restored service: Oil Change (ID: 20)','2025-09-17 01:28:22','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('326','1','Login successful','','2025-09-17 17:56:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('327','1','Login successful','','2025-09-17 18:03:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('328','1','Login successful','','2025-09-17 18:11:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('329','1','Approve Password Reset','Reset approved for user_id=3','2025-09-17 18:14:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('330','3','Login successful','','2025-09-17 18:14:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('331',NULL,'Login failed for username: staff001','','2025-09-17 18:17:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('332','1','Login successful','','2025-09-17 18:17:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('333','1','Approve Password Reset','Reset approved for user_id=3','2025-09-17 18:20:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('334','3','Login successful','','2025-09-17 18:20:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('335','1','Login successful','','2025-09-17 18:22:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('336','1','Approve Password Reset','Reset approved for user_id=3','2025-09-17 18:22:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('337','3','Login successful','','2025-09-17 18:22:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('338','1','Login successful','','2025-09-17 18:22:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('339','1','Restore User','Restored user: barandal.stockman (ID: 27)','2025-09-17 18:37:25','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('340','1','Add Product','Added product \'Slap Soil\' (ID: 60) with stock 20 to Bucal Branch','2025-09-17 20:00:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('341','1','Add Service','Added service \'Vulcanize\' (ID: 22) to Bucal Branch','2025-09-17 20:07:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('342','1','Archive Product','Archived product: Slap Soil (Inventory ID: 109)','2025-09-17 20:11:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('343','1','Archive Service','Archived service: Vulcanize (ID: 22)','2025-09-17 20:11:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('344','1','Add Product','Added product \'Product 80x20\' (ID: 61) with stock 20 to Bucal Branch','2025-09-17 20:21:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('345','1','Archive Product','Archived product: Product 80x20 (Inventory ID: 110)','2025-09-17 20:26:17','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('346','1','Delete Product','Deleted product: Slap Soil (ID: 109)','2025-09-17 20:26:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('347','1','Delete Product','Deleted product: Product 80x20 (ID: 110)','2025-09-17 20:26:26','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('348','1','Add Product','Added product \'Slap Soil\' (ID: 62) with stock 20 to Barandal Branch','2025-09-17 20:27:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('349','1','Add Service','Added service \'Vulcanize\' (ID: 23) to Barandal Branch','2025-09-17 20:27:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('350','3','Login successful','','2025-09-17 21:02:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('351','1','Login successful','','2025-09-17 21:02:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('352','1','Login successful','','2025-09-18 11:53:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('353','1','Login successful','','2025-09-18 12:01:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('354','3','Login successful','','2025-09-18 12:12:25','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('355','15','Login successful','','2025-09-18 12:13:34','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('356','15','Login successful','','2025-09-18 12:13:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('357','15','Login successful','','2025-09-18 12:38:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('358','15','Stock-In Request','Requested stock-in of 5 ATF Premium to Bucal Branch','2025-09-18 12:38:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('359','15','Stock Transfer Request','Requested transfer of 2 A/C Pro from Barandal Branch to Bucal Branch','2025-09-18 12:39:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('360','1','Login successful','','2025-09-18 12:39:10',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('361','1','Login successful','','2025-09-18 12:45:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('362','1','Reject Password Reset','Reset rejected for reset_id=13','2025-09-18 12:45:14',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('363','1','Login successful','','2025-09-18 16:42:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('364','1','Add Product','Added product \'Nigger\' (ID: 63) with stock 20 to branch ID 1','2025-09-18 16:43:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('365','1','Archive Product','Archived product: Nigger (Inventory ID: 112) | Branch: Bucal Branch','2025-09-18 16:45:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('366','1','Delete Product','Deleted product: Nigger (ID: 112)','2025-09-18 16:46:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('367','1','Login successful','','2025-09-18 17:10:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('368','1','Add Product','Added product \'Slave Nigger\' (ID: 64) with stock 20 to branch ID 1','2025-09-18 17:13:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('369','15','Login successful','','2025-09-18 18:20:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('370','15','Login successful','','2025-09-18 18:21:07','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('371','1','Login successful','','2025-09-18 18:21:56',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('372','1','Archive Product','Archived product: Slap Soil (Inventory ID: 111) | Branch: Barandal Branch','2025-09-18 18:34:03','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('373','1','Archive Product','Archived product: Slave Nigger (Inventory ID: 113) | Branch: Bucal Branch','2025-09-18 19:12:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('374','1','Delete Product','Deleted product: Slap Soil (ID: 111)','2025-09-18 19:12:25','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('375','1','Delete Product','Deleted product: Slave Nigger (ID: 113)','2025-09-18 19:12:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('376','1','Login successful','','2025-09-18 19:24:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('377','15','Login successful','','2025-09-20 18:00:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('378','1','Login successful','','2025-09-20 18:01:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('379','1','Login successful','','2025-09-20 18:01:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('380','15','Login successful','','2025-09-20 18:02:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('381','1','Login successful','','2025-09-20 18:07:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('382','15','Login successful','','2025-09-20 18:07:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('383','1','Login successful','','2025-09-20 18:10:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('384','15','Login successful','','2025-09-20 18:14:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('385','1','Login successful','','2025-09-20 18:17:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('386','15','Login successful','','2025-09-20 18:18:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('387','1','Login successful','','2025-09-20 18:21:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('388','15','Login successful','','2025-09-20 18:24:14','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('389','1','Login successful','','2025-09-20 18:27:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('390','15','Login successful','','2025-09-20 18:27:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('391','1','Login successful','','2025-09-20 18:28:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('392',NULL,'Login failed for username: barandal.stockman','','2025-09-20 18:29:56',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('393','1','Login successful','','2025-09-20 18:30:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('394','1','Approve Password Reset','Reset approved for user_id=27','2025-09-20 18:30:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('395','27','Login successful','','2025-09-20 18:30:24','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('396','27','Physical Inventory Saved','Saved product_id=58, physical_count=10, status=Complete','2025-09-20 18:30:45','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('397','15','Login successful','','2025-09-20 18:31:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('398','1','Login successful','','2025-09-20 18:31:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('399','1','Update Account','Updated user: Staff2 (Dudong), role: staff, branch_id: 4','2025-09-20 19:15:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('400','1','Update Account','Updated user: Stockman1 (Kenny), role: stockman, branch_id: 1','2025-09-20 19:15:48',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('401','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-20 19:23:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('402','1','Archive Branch','Archived branch ID: 3','2025-09-20 19:24:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('403','1','Restore Branch','Restored branch: Prinza Branch (ID: 3)','2025-09-20 19:24:25','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('404','1','Restore User','Restored user: barandal.stockman (ID: 27)','2025-09-20 19:26:34','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('405','1','Reject Password Reset','Reset rejected for reset_id=14','2025-09-20 20:08:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('406','1','Login successful','','2025-09-20 20:13:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('407','1','Reject Password Reset','Reset rejected for reset_id=16','2025-09-20 20:14:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('408','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-09-20 20:14:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('409','3','Login successful','','2025-09-20 20:28:34','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('410','1','Login successful','','2025-09-20 20:29:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('411','3','Login successful','','2025-09-20 20:37:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('412','1','Login successful','','2025-09-20 20:38:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('413','3','Login successful','','2025-09-20 20:45:19','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('414','3','Add Product to Cart','Added 1 of A/C Pro to cart | Branch: Bucal Branch','2025-09-20 20:45:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('415','3','Add Service to Cart','Added 1 service: Computer Wheel Alignment (ID: 9) to cart | Branch: Bucal Branch','2025-09-20 20:46:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('416','3','Add Product to Cart','Added 1 of A/C Pro to cart | Branch: Bucal Branch','2025-09-20 20:46:17','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('417','1','Login successful','','2025-09-20 20:54:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('418','3','Login successful','','2025-09-20 21:04:06','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('419','1','Login successful','','2025-09-20 21:04:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('420','1','Edit Product','Edited product ID 49: ceiling_point changed from \'20000\' to \'20\'; critical_point changed from \'500\' to \'5\'','2025-09-20 21:14:48',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('421','1','Edit Service','Edited service: Oil Change (ID: 16) | Branch: Batino Branch','2025-09-20 21:15:01','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('422','1','Edit Product','Edited product ID 42: retail_price changed from \'\' to \'385\'; ceiling_point changed from \'10\' to \'21\'; vat changed from \'\' to \'12\'','2025-09-20 21:20:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('423','1','Edit Product','Edited product ID 42: No changes detected','2025-09-20 21:27:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('424','1','Edit Service','Edited service: Computer Wheel Alignment (ID: 9) | Branch: Bucal Branch','2025-09-20 21:27:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('425','1','Edit Product','Edited product ID 16: retail_price changed from \'\' to \'1320\'; vat changed from \'\' to \'12\'','2025-09-20 21:27:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('426','1','Edit Product','Edited product ID 16: No changes detected','2025-09-20 21:28:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('427','1','Add Product','Added product \'Nigger\' (ID: 65) with stock 20 to branch ID 5','2025-09-20 21:40:17',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('428','1','Archive Product','Archived product: Oil 3 in 1 (Inventory ID: 45) | Branch: 00Bucal','2025-09-20 22:04:52','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('429','1','Delete Product','Deleted product: Oil 3 in 1 (ID: 45)','2025-09-20 22:05:46','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('430','1','Add Product','Added product \'Laptop\' (ID: 66) with stock 5 to branch ID 5','2025-09-20 22:06:51',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('431','1','Archive Product','Archived product: Laptop (Inventory ID: 120) | Branch: 00Bucal','2025-09-20 22:12:40','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('432','1','Delete Product','Deleted product: Laptop (ID: 120)','2025-09-20 22:13:28','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('433','1','Add Product','Added product \'Kopika 1n3\' (ID: 67) with stock 20 to branch ID 5','2025-09-20 22:17:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('434','1','Login successful','','2025-09-21 11:12:14',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('435','15','Login successful','','2025-09-21 12:17:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('436','1','Login successful','','2025-09-21 12:18:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('437','1','Login successful','','2025-09-21 16:38:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('438','1','Edit Service','Edited service: Vulcanize (ID: 23) | Branch: Barandal Branch','2025-09-21 16:47:28','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('439','1','Edit Service','Edited service: Computer Wheel Alignment (ID: 9) | Branch: Bucal Branch','2025-09-21 16:56:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('440','1','Add Service','Added service \'Change Tire\' (ID: 24) to branch ID 5','2025-09-21 16:57:56',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('441','1','Login successful','','2025-09-21 17:07:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('442','1','Login successful','','2025-09-21 17:13:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('443','1','Login successful','','2025-09-21 17:13:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('444','1','Login successful','','2025-09-21 17:13:51',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('445','1','Login successful','','2025-09-21 17:14:10',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('446','1','Login successful','','2025-09-21 17:14:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('447','1','Reject Password Reset','Reset rejected for reset_id=17','2025-09-21 17:14:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('448','1','Login successful','','2025-09-21 17:15:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('449','1','Login successful','','2025-09-21 17:20:48',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('450','1','Login successful','','2025-09-21 17:21:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('451','1','Login successful','','2025-09-21 17:23:51',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('452','1','Login successful','','2025-09-21 17:24:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('453','1','Logout','User logged out.','2025-09-21 17:24:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('454',NULL,'Login failed for username: admin123','','2025-09-21 17:24:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('455','1','Login successful','','2025-09-21 17:24:46',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('456','1','Logout','User logged out.','2025-09-21 17:24:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('457','1','Login successful','','2025-09-21 17:24:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('458','1','Logout','User logged out.','2025-09-21 17:24:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('459','1','Login successful','','2025-09-21 17:28:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('460','1','Logout','User logged out.','2025-09-21 17:28:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('461','1','Login successful','','2025-09-21 17:30:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('462','1','Logout','User logged out.','2025-09-21 17:30:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('463','1','Login successful','','2025-09-21 17:30:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('464','1','Logout','User logged out.','2025-09-21 17:30:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('465',NULL,'Login failed for username: admin123','','2025-09-21 17:35:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('466','1','Login successful','','2025-09-21 17:35:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('467','1','Reject Password Reset','Reset rejected for reset_id=18','2025-09-21 17:35:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('468','1','Logout','User logged out.','2025-09-21 17:35:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('469','1','Login successful','','2025-09-21 17:35:56',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('470','1','Logout','User logged out.','2025-09-21 17:35:59',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('471','1','Login successful','','2025-09-21 17:36:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('472','1','Logout','User logged out.','2025-09-21 17:36:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('473','1','Login successful','','2025-09-21 17:38:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('474','1','Physical Inventory Saved','Saved product_id=49, physical_count=23, status=Overstock','2025-09-21 17:42:57','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('475','1','Physical Inventory Saved','Saved product_id=49, physical_count=0, status=Complete','2025-09-21 17:43:04','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('476','1','Login successful','','2025-09-21 18:20:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('477','1','Logout','User logged out.','2025-09-21 18:21:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('478','1','Login successful','','2025-09-21 18:26:01',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('479','1','Logout','User logged out.','2025-09-21 18:26:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('480','3','Login successful','','2025-09-21 18:26:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('481','15','Login successful','','2025-09-21 18:26:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('482','15','Logout','User logged out.','2025-09-21 18:26:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('483','3','Login successful','','2025-09-21 18:27:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('484','1','Login successful','','2025-09-21 18:28:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('485','1','Add Product','Added product \'Gulong ni Marlou\' with stock 20 to branch 1','2025-09-21 18:30:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('486','1','Logout','User logged out.','2025-09-21 18:31:44',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('487','3','Login successful','','2025-09-21 18:33:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('488','1','Login successful','','2025-09-21 18:39:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('489','1','Logout','User logged out.','2025-09-21 18:40:13',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('490','3','Login successful','','2025-09-21 18:41:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('491','3','Logout','User logged out.','2025-09-21 18:46:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('492','1','Login successful','','2025-09-21 18:46:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('493','1','Inventory Movement','Product 68, Branch Bucal Branch, Qty , Reason ','2025-09-21 18:50:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('494','1','Add Stock','Added 5 stocks to ATF Premium (Branch Bucal Branch)','2025-09-21 18:53:42','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('495','1','Archive Service','Archived service: Change Tire (ID: 24) | Branch: 00Bucal','2025-09-21 18:59:18','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('496','1','Delete Product','Deleted product: Gulong ni Marlou (ID: 122)','2025-09-21 19:00:14','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('497','1','Logout','User logged out.','2025-09-21 19:05:39',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('498','1','Login successful','','2025-09-21 19:05:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('499','1','Restore Product','Restored product: Nigger (ID: 119)','2025-09-21 19:07:51','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('500','1','Restore Product','Restored product: Nigger (ID: 119)','2025-09-21 19:10:26','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('501','1','Archive Product','Archived product: Nigger (Inventory ID: 119) | Branch: 00Bucal','2025-09-21 19:10:32','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('502','1','Add Product','Added product \'Gulong ni Dudoy\' with stock 20 to branch 1','2025-09-21 19:20:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('503','1','Archive Product','Archived product: Gulong ni Dudoy (Inventory ID: 123) | Branch: Bucal Branch','2025-09-21 19:20:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('504','1','Delete Product','Deleted product: Gulong ni Dudoy (ID: 123)','2025-09-21 19:21:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('505','1','Add Product','Added product \'Gogolonggong\' with stock 20 to branch 1','2025-09-21 19:24:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('506','1','Logout','User logged out.','2025-09-21 19:26:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('507','3','Login successful','','2025-09-21 19:26:38','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('508','1','Login successful','','2025-09-21 20:46:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('509','1','Logout','User logged out.','2025-09-21 20:46:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('510','3','Login successful','','2025-09-21 20:46:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('511',NULL,'Login failed for username: staff001','','2025-09-21 21:05:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('512',NULL,'Login failed for username: staff001','','2025-09-21 21:05:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('513','3','Login successful','','2025-09-21 21:05:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('514','3','Login successful','','2025-09-22 17:16:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('515','3','Logout','User logged out.','2025-09-22 17:33:30','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('516','1','Login successful','','2025-09-22 17:33:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('517','1','Logout','User logged out.','2025-09-22 17:34:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('518','3','Login successful','','2025-09-22 17:34:22','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('519','3','Logout','User logged out.','2025-09-22 18:24:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('520','3','Login successful','','2025-09-22 18:24:54','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('521','3','Logout','User logged out.','2025-09-22 18:24:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('522','3','Login successful','','2025-09-22 18:25:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('523','1','Login successful','','2025-09-22 18:25:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('524','1','Login successful','','2025-09-23 17:51:10',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('525','1','Add Stock','Added 200 stocks to Tire 70 X 80 X 14 (Branch Bucal Branch)','2025-09-23 17:52:22','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('526','1','Logout','User logged out.','2025-09-23 17:54:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('527','1','Login successful','','2025-09-23 17:54:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('528','1','Login successful','','2025-09-23 17:59:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('529','1','Logout','User logged out.','2025-09-23 17:59:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('530','1','Login successful','','2025-09-23 17:59:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('531','1','Create Account','Created user: 123 (123), role: staff, branch_id=2','2025-09-23 18:02:10','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('532','1','Archive Account','Archived user: 123 (username: 123, role: staff)','2025-09-23 18:02:18','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('533','1','Delete User','Deleted user: 123 (ID: 29)','2025-09-23 18:02:29','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('534','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-23 18:05:26','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('535','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-23 18:05:46','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('536','1','Update Branch','Updated branch_id=5 to \'12\'','2025-09-23 18:06:58','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('537','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-23 18:07:10','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('538','1','Update Branch','Updated branch_id=6 to \'Barandal Branch\'','2025-09-23 18:09:04','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('539','1','Update Branch','Updated branch_id=6 to \'Barandal Branch\'','2025-09-23 18:09:10','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('540','1','Edit Service','Edited service: Vulcanize (ID: 23) | Branch: Barandal Branch','2025-09-23 18:11:17','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('541','1','Edit Service','Edited service: Vulcanize (ID: 23) | Branch: Barandal Branch','2025-09-23 18:11:35','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('542','1','Edit Service','Edited service: Computer Wheel Alignment (ID: 9) | Branch: Bucal Branch','2025-09-23 18:11:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('543','1','Logout','User logged out.','2025-09-23 18:11:49',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('544',NULL,'Login failed for username: staf001','','2025-09-23 18:11:59',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('545','3','Login successful','','2025-09-23 18:12:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('546','3','Logout','User logged out.','2025-09-23 18:12:30','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('547','1','Login successful','','2025-09-23 18:12:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('548','1','Edit Product','Edited product ID 9: price changed from \'27000\' to \'-27000\'; retail_price changed from \'\' to \'29700\'; vat changed from \'\' to \'12\'','2025-09-23 18:13:10',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('549','1','Edit Product','Edited product ID 9: price changed from \'-27000\' to \'27000\'; retail_price changed from \'29700\' to \'-29700\'','2025-09-23 18:13:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('550','1','Stock Transfer','Transferred 8 A/C Pro from Batino Branch to Branch 00Bucal','2025-09-23 18:16:00','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('551','1','Logout','User logged out.','2025-09-23 18:18:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('552','3','Login successful','','2025-09-23 18:18:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('553','3','Logout','User logged out.','2025-09-23 18:18:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('554',NULL,'Login failed for username: Stockman1','','2025-09-23 18:18:49',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('555',NULL,'Login failed for username: admin123','','2025-09-23 18:18:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('556',NULL,'Login failed for username: admin123','','2025-09-23 18:19:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('557','1','Login successful','','2025-09-23 18:19:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('558','1','Reject Password Reset','Reset rejected for reset_id=19','2025-09-23 18:19:17',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('559','1','Logout','User logged out.','2025-09-23 18:19:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('560','15','Login successful','','2025-09-23 18:19:22','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('561','15','Stock Transfer Request','Requested transfer of 3 A/C Pro from Barandal Branch to Bucal Branch','2025-09-23 18:21:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('562','15','Logout','User logged out.','2025-09-23 18:21:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('563','1','Login successful','','2025-09-23 18:22:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('564','1','Stock Transfer','Transferred 3 A/C Pro from Barandal Branch to Batino Branch','2025-09-23 18:22:32','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('565','1','Login successful','','2025-09-23 20:28:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('566','1','Create Account','Created user: 1231231 (Dudong), role: staff, branch_id=2','2025-09-23 20:41:21','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('567','1','Archive Account','Archived user: Dudong (username: 1231231, role: staff)','2025-09-23 20:41:31','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('568','1','Delete User','Deleted user: 1231231 (ID: 30)','2025-09-23 20:41:42','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('569','1','Create Account','Created user: Staff123 (Dudong), role: staff, branch_id=4','2025-09-23 20:44:01','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('570','1','Logout','User logged out.','2025-09-23 21:04:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('571','1','Login successful','','2025-09-23 21:04:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('572','1','Approve Password Reset','Reset approved for user_id=3','2025-09-23 21:04:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('573','1','Logout','User logged out.','2025-09-23 21:04:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('574','3','Login successful','','2025-09-23 21:04:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('575','3','Logout','User logged out.','2025-09-23 21:06:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('576','1','Login successful','','2025-09-23 21:06:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('577','1','Login successful','','2025-09-24 12:28:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('578','1','Logout','User logged out.','2025-09-24 12:29:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('579','3','Login successful','','2025-09-24 12:29:35','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('580','1','Login successful','','2025-09-24 14:49:49',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('581','1','Create Account','Created user: Stockman123 (ksperez), role: staff, branch_id=5','2025-09-24 15:31:25','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('582','1','Archive Account','Archived user: ksperez (username: Stockman123, role: staff)','2025-09-24 15:31:33','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('583','1','Delete User','Deleted user: Stockman123 (ID: 32)','2025-09-24 15:31:48','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('584','1','Create Account','Created user: Staff005 (Kent), role: staff, branch_id=3','2025-09-24 15:57:18','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('585','1','Logout','User logged out.','2025-09-24 15:57:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('586','1','Login successful','','2025-09-24 15:57:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('587','1','Reject Password Reset','Reset rejected for reset_id=21','2025-09-24 15:57:46',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('588','1','Update Account','Updated user: Staff005 (12), role: staff, branch_id=3','2025-09-24 15:59:21','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('589','1','Update Account','Updated user: Staff005 (Kent), role: staff, branch_id=3','2025-09-24 15:59:32','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('590','1','Create Branch','Created branch: San Juan (#123) (aerene@gmail.com)','2025-09-24 16:56:15','7');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('591','1','Update Account','Updated user: Staff005 (Kent), role: staff, branch_id=3','2025-09-24 17:16:43','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('592','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-24 17:22:05','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('593','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-24 17:22:30','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('594','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-24 17:23:09','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('595','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-24 17:24:02','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('596','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-09-24 17:28:48','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('597','1','Add Product','Added product \'Tire Betch\' with stock 20 to branch 1','2025-09-24 17:42:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('598','1','Edit Service','Edited service: Computer Wheel Alignment (ID: 9) | Branch: Bucal Branch','2025-09-24 18:13:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('599','1','Logout','User logged out.','2025-09-24 18:35:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('600','15','Login successful','','2025-09-24 18:35:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('601','15','Stock Transfer Request','Requested transfer of 10 Kopika 1n3 from Branch 00Bucal to Barandal Branch','2025-09-24 18:36:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('602','15','Logout','User logged out.','2025-09-24 18:36:06','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('603','1','Login successful','','2025-09-24 18:36:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('604','1','Logout','User logged out.','2025-09-24 18:47:44',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('605','15','Login successful','','2025-09-24 18:47:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('608','15','Stock Transfer Request','Requested transfer of 5 Gogolonggong from Bucal Branch to Batino Branch','2025-09-24 18:49:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('609','15','Logout','User logged out.','2025-09-24 18:49:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('610','1','Login successful','','2025-09-24 18:49:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('611','1','Stock Transfer','Transferred 10 Gogolonggong from Bucal Branch to Barandal Branch','2025-09-24 18:49:57','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('612','1','Stock Transfer','Transferred 5 Gogolonggong from Barandal Branch to Bucal Branch','2025-09-24 18:51:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('613','3','Login successful','','2025-09-24 22:14:29','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('614','3','Logout','User logged out.','2025-09-24 22:15:58','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('615','3','Login successful','','2025-09-24 22:16:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('616','3','Logout','User logged out.','2025-09-24 22:43:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('617','3','Login successful','','2025-09-24 22:43:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('618','3','Logout','User logged out.','2025-09-24 22:48:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('619','3','Login successful','','2025-09-24 22:48:49','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('620','3','Logout','User logged out.','2025-09-24 22:54:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('621','3','Login successful','','2025-09-24 22:54:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('622','3','Logout','User logged out.','2025-09-24 23:21:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('623','3','Login successful','','2025-09-24 23:21:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('624','3','Logout','User logged out.','2025-09-24 23:22:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('625','3','Login successful','','2025-09-24 23:22:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('626','3','Logout','User logged out.','2025-09-24 23:25:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('627','3','Login successful','','2025-09-24 23:25:34','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('628','3','Logout','User logged out.','2025-09-24 23:28:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('629','3','Login successful','','2025-09-24 23:28:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('630','3','Logout','User logged out.','2025-09-24 23:29:10','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('631','3','Login successful','','2025-09-24 23:29:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('632','3','Logout','User logged out.','2025-09-24 23:33:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('633','3','Login successful','','2025-09-24 23:33:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('634','3','Logout','User logged out.','2025-09-24 23:46:42','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('635','3','Login successful','','2025-09-24 23:46:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('636','3','Logout','User logged out.','2025-09-24 23:47:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('637','3','Login successful','','2025-09-24 23:47:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('638','3','Logout','User logged out.','2025-09-24 23:53:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('639','3','Login successful','','2025-09-24 23:53:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('640','3','Logout','User logged out.','2025-09-24 23:54:17','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('641','3','Login successful','','2025-09-24 23:54:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('642','3','Logout','User logged out.','2025-09-24 23:54:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('643','3','Login successful','','2025-09-24 23:55:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('644','3','Logout','User logged out.','2025-09-24 23:55:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('645','3','Login successful','','2025-09-24 23:56:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('646','3','Logout','User logged out.','2025-09-24 23:56:14','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('647','3','Login successful','','2025-09-24 23:56:17','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('648','3','Logout','User logged out.','2025-09-25 00:11:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('649',NULL,'Login failed for username: staff001','','2025-09-25 00:11:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('650',NULL,'Login failed for username: staff001','','2025-09-25 00:11:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('651','3','Login successful','','2025-09-25 00:11:29','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('652','3','Logout','User logged out.','2025-09-25 00:12:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('653','3','Login successful','','2025-09-25 00:12:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('654','1','Login successful','','2025-09-27 09:31:44',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('655','1','Logout','User logged out.','2025-09-27 09:32:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('656','3','Login successful','','2025-09-27 09:32:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('657','3','Logout','User logged out.','2025-09-27 09:33:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('658','1','Login successful','','2025-09-27 09:33:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('659','3','Login successful','','2025-09-27 22:36:22','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('660','3','Login successful','','2025-09-27 22:46:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('661','3','Logout','User logged out.','2025-09-27 22:47:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('662','1','Login successful','','2025-09-27 22:47:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('663','1','Logout','User logged out.','2025-09-27 22:48:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('664','3','Login successful','','2025-09-28 14:07:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('665','3','Logout','User logged out.','2025-09-28 14:08:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('666','1','Login successful','','2025-09-28 14:08:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('667','1','Add Stock','Added 10 stocks to Gogolonggong (Branch Bucal Branch)','2025-09-28 14:26:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('668','1','Login successful','','2025-09-28 14:31:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('669','1','Login successful','','2025-09-28 14:36:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('670','1','Archive Product','Archived product: Tire Betch (Inventory ID: 128) | Branch: Bucal Branch','2025-09-28 14:36:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('671','1','Delete Product','Deleted product: Tire Betch (ID: 128)','2025-09-28 14:37:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('672','1','Delete Product','Deleted product: Nigger (ID: 119)','2025-09-28 14:37:06','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('673','1','Add Product','Added product \'Sample Exp1\' with stock 10 to branch 1','2025-09-28 14:38:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('674','1','Add Product','Added product \'Sample Exp2\' (ID: 73) with stock 10 to branch 1','2025-09-28 14:50:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('675','1','Add Stock','Added 10 stocks to Sample Exp1 (Branch Bucal Branch)','2025-09-28 14:50:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('676','1','Add Stock','Added 10 stocks to Sample Exp2 (Branch Bucal Branch)','2025-09-28 14:54:19','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('677','1','Add Stock','Added 5 stocks to Sample Exp2 (Branch Bucal Branch)','2025-09-28 15:10:34','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('678','1','Add Stock','Added 5 stocks to Sample Exp2 (Branch Bucal Branch)','2025-09-28 15:15:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('679','1','Archive Product','Archived product: Sample Exp2 (Inventory ID: 133) | Branch: Bucal Branch','2025-09-28 15:21:18','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('680','1','Archive Product','Archived product: Sample Exp1 (Inventory ID: 132) | Branch: Bucal Branch','2025-09-28 15:21:21','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('681','1','Delete Product','Deleted product: Sample Exp2 (ID: 133)','2025-09-28 15:21:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('682','1','Delete Product','Deleted product: Sample Exp1 (ID: 132)','2025-09-28 15:21:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('683','1','Add Product','Added product \'Sample Exp1\' (ID: 74) with stock 10 to branch 1','2025-09-28 15:22:39',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('684','1','Add Stock','Added 5 stocks to Sample Exp1 (Branch Bucal Branch)','2025-09-28 15:23:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('685','1','Add Stock','Added 12 stocks to Sample Exp1 (Branch Bucal Branch)','2025-09-28 15:26:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('686','1','Add Stock','Added 1 stocks to Sample Exp1 (Branch Bucal Branch)','2025-09-28 15:28:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('687','1','Add Stock','Added 1 stocks to Sample Exp1 (Branch Bucal Branch)','2025-09-28 15:31:17','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('688','1','Add Stock','Added 1 stocks to Sample Exp1 (Branch Bucal Branch)','2025-09-28 15:31:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('689','1','Archive Product','Archived product: Sample Exp1 (Inventory ID: 134) | Branch: Bucal Branch','2025-09-28 15:32:30','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('690','1','Add Product','Added product \'Sample Exp3\' (ID: 75) with stock 10 to branch 1','2025-09-28 15:34:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('691','1','Add Stock','Added 5 stocks to Sample Exp3 (Branch Bucal Branch)','2025-09-28 15:34:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('692','1','Add Stock','Added 3 stocks to Sample Exp3 (Branch Bucal Branch)','2025-09-28 15:46:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('693','1','Add Stock','Added 2 stock to Sample Exp3 (ID: 75) (Expiry: 2060-08-22) | Branch: Bucal Branch','2025-09-28 15:46:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('694','1','Add Stock','Added 5 stock to Sample Exp3 (ID: 75) (Expiry: 2025-12-25) | Branch: Bucal Branch','2025-09-28 15:47:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('695','1','Add Stock','Added 5 stock to Sample Exp3 (ID: 75) (Expiry: 2027-12-23) | Branch: Bucal Branch','2025-09-28 15:49:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('696','1','Add Stock','Added 5 stock to Sample Exp3 (ID: 75) (Expiry: 2029-12-23) | Branch: Bucal Branch','2025-09-28 16:02:35','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('697','1','Add Stock','Added 5 stock to Sample Exp3 (ID: 75) (Expiry: 2028-02-25) | Branch: Bucal Branch','2025-09-28 16:12:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('698','15','Login successful','','2025-09-29 00:22:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('699','15','Add Stock','Added 5 stock to Sample Exp3 (ID: 75) (Expiry: 2025-10-22) | Branch: Bucal Branch','2025-09-29 00:23:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('700','15','Logout','User logged out.','2025-09-29 00:23:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('701','1','Login successful','','2025-09-29 00:23:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('702','1','Logout','User logged out.','2025-09-29 00:33:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('703','15','Login successful','','2025-09-29 00:33:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('704','15','Stock-In Request','Requested +5 for Sample Exp3 (ID:75) (Expiry: 2025-10-22) | Branch: Bucal Branch','2025-09-29 00:37:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('705','15','Stock-In Request','Requested +5 for Sample Exp3 (ID:75) (Expiry: 2025-10-22) | Branch: Bucal Branch','2025-09-29 00:38:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('706','15','Logout','User logged out.','2025-09-29 00:38:17','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('707','1','Login successful','','2025-09-29 00:38:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('708','1','Logout','User logged out.','2025-09-29 00:41:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('709','1','Login successful','','2025-09-29 00:41:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('710','1','Logout','User logged out.','2025-09-29 00:41:47',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('711','15','Login successful','','2025-09-29 00:41:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('712','15','Stock-In Request','Requested +5 for Sample Exp3 (ID:75) (Expiry: 2025-12-01) | Branch: Bucal Branch','2025-09-29 00:43:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('713','15','Logout','User logged out.','2025-09-29 00:43:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('714','1','Login successful','','2025-09-29 00:43:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('715','1','Logout','User logged out.','2025-09-29 00:43:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('716','15','Login successful','','2025-09-29 00:48:34','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('717','15','Stock-In Request','Requested +2 for A/C Pro (ID:44) | Branch: Bucal Branch','2025-09-29 00:48:55','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('718','15','Logout','User logged out.','2025-09-29 00:49:06','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('719',NULL,'Login failed for username: Admin123','','2025-09-29 00:49:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('720','1','Login successful','','2025-09-29 00:49:20',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('721','1','Logout','User logged out.','2025-09-29 00:50:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('722','15','Login successful','','2025-09-29 00:50:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('723','15','Logout','User logged out.','2025-09-29 00:50:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('724','15','Login successful','','2025-09-29 00:51:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('725','15','Stock-In Request','Requested +5 for ATF Premium (ID:46) | Branch: Bucal Branch','2025-09-29 00:51:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('726','15','Logout','User logged out.','2025-09-29 00:51:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('727','1','Login successful','','2025-09-29 00:51:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('728','3','Login successful','','2025-09-29 20:20:34','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('729','1','Login successful','','2025-09-29 20:21:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('730','1','Logout','User logged out.','2025-09-29 20:21:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('731','1','Login successful','','2025-09-29 20:21:28',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('732','1','Logout','User logged out.','2025-09-29 20:21:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('733','3','Login successful','','2025-09-29 20:21:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('734','1','Login successful','','2025-09-29 21:31:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('735','1','Login successful','','2025-10-20 17:09:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('736','1','Logout','User logged out.','2025-10-20 17:09:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('737','3','Login successful','','2025-10-20 17:09:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('738','3','Logout','User logged out.','2025-10-20 17:18:26','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('739','1','Login successful','','2025-10-20 17:18:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('740','1','Logout','User logged out.','2025-10-20 18:12:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('741','1','Login successful','','2025-10-20 18:12:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('742','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:32:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('743','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:32:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('744','1','Update Account','Updated user: Stockman123 (Kenken), role: stockman, branch_id=1','2025-10-20 18:35:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('745','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:35:14','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('746','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:37:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('747','1','Update Account','Updated user: admin123 (Riza), role: admin','2025-10-20 18:39:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('748','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:39:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('749','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:42:46','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('750',NULL,'Login failed for username: admin123','','2025-10-20 18:43:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('751','1','Login successful','','2025-10-20 18:43:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('752','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:43:35','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('753','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:44:35','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('754','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, branch_id=1','2025-10-20 18:44:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('755','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, phone: 09935844995, branch_id=1','2025-10-20 18:46:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('756','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, phone: 09935844994, branch_id=1','2025-10-20 18:47:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('757','1','Create Account','Created new user: Gel123 (Reign), role: staff, phone: 09944240934, branch_id=5','2025-10-20 18:51:01','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('758','1','Archive Account','Archived user: Reign (username: Gel123, role: staff)','2025-10-20 18:51:07','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('759','1','Logout','User logged out.','2025-10-20 21:44:14',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('760',NULL,'Login failed for username: admin123','','2025-10-20 21:44:18',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('761','1','Login successful','','2025-10-20 21:44:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('762','1','Logout','User logged out.','2025-10-20 21:44:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('763','1','Login successful','','2025-10-20 21:45:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('764','1','Logout','User logged out.','2025-10-20 21:45:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('765','1','Login successful','','2025-10-20 22:16:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('766','1','Logout','User logged out.','2025-10-20 22:16:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('767',NULL,'Login failed for username: Stockman1','','2025-10-20 22:17:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('768','15','Login successful','','2025-10-20 22:18:00','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('769','15','Logout','User logged out.','2025-10-20 22:18:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('770','15','Login successful','','2025-10-20 22:23:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('771','15','Logout','User logged out.','2025-10-20 22:31:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('772',NULL,'Login failed for username: Stockman1','','2025-10-20 22:34:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('773','15','Login successful','','2025-10-20 22:34:39','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('774','15','Logout','User logged out.','2025-10-20 22:34:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('775','1','Login successful','','2025-10-21 08:36:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('776','1','Logout','User logged out.','2025-10-21 10:01:49',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('777','1','Login successful','','2025-10-21 10:01:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('778','1','Logout','User logged out.','2025-10-21 10:22:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('779','1','Login successful','','2025-10-21 10:23:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('780','1','Logout','User logged out.','2025-10-21 11:17:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('781','3','Login successful','','2025-10-21 11:17:18','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('782','3','Logout','User logged out.','2025-10-21 11:27:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('783','15','Login successful','','2025-10-21 11:27:30','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('784','1','Login successful','','2025-10-21 19:05:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('785','1','Logout','User logged out.','2025-10-21 19:10:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('786','1','Login successful','','2025-10-21 19:10:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('787','1','Logout','User logged out.','2025-10-21 19:11:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('788','15','Login successful','','2025-10-21 19:13:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('789','15','Logout','User logged out.','2025-10-21 19:14:07','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('790','3','Login successful','','2025-10-21 19:14:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('791','3','Login successful','','2025-10-21 19:26:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('792','3','Login successful','','2025-10-21 19:29:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('793','3','Logout','User logged out.','2025-10-21 19:30:22','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('794','1','Login successful','','2025-10-25 12:23:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('795','1','Logout','User logged out.','2025-10-25 12:25:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('796','15','Login successful','','2025-10-25 12:26:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('797','15','Logout','User logged out.','2025-10-25 12:26:53','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('798','1','Login successful','','2025-10-25 12:26:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('799','1','Edit Service','Edited service: Oil Change (ID: 20) | Branch: Halang Branch','2025-10-25 12:32:56','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('800','1','Edit Service','Edited service: Computer Wheel Alignment (ID: 18) | Branch: Halang Branch','2025-10-25 12:33:08','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('801','1','Logout','User logged out.','2025-10-25 12:39:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('802','15','Login successful','','2025-10-25 12:39:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('803','15','Logout','User logged out.','2025-10-25 12:42:32','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('804','3','Login successful','','2025-10-25 12:42:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('805','3','Logout','User logged out.','2025-10-25 12:45:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('806','1','Login successful','','2025-10-25 12:45:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('807','1','Logout','User logged out.','2025-10-25 12:50:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('808',NULL,'Login failed for username: Stockman1','','2025-10-25 14:31:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('809','15','Login successful','','2025-10-25 14:31:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('810','15','Login successful','','2025-10-25 14:34:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('811','15','Logout','User logged out.','2025-10-25 14:38:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('812','3','Login successful','','2025-10-25 14:38:21','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('813','3','Login successful','','2025-10-27 17:36:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('814','3','Logout','User logged out.','2025-10-27 17:36:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('815','1','Login successful','','2025-10-27 17:42:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('816','1','Logout','User logged out.','2025-10-27 17:50:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('817','3','Login successful','','2025-10-27 17:50:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('818','3','Logout','User logged out.','2025-10-27 17:50:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('819',NULL,'Login failed for username: admin123','','2025-10-27 18:24:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('820','1','Login successful','','2025-10-27 18:24:15',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('821','1','Logout','User logged out.','2025-10-27 18:24:20',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('822','3','Login successful','','2025-10-27 18:25:07','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('823','3','Logout','User logged out.','2025-10-27 18:25:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('824','1','Login successful','','2025-10-27 18:25:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('825','1','Logout','User logged out.','2025-10-27 18:25:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('826','15','Login successful','','2025-10-27 18:25:54','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('827','15','Logout','User logged out.','2025-10-27 18:26:10','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('828','1','Login successful','','2025-10-28 19:43:44',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('829','1','Logout','User logged out.','2025-10-28 19:44:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('830','1','Login successful','','2025-10-28 19:45:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('831','1','Logout','User logged out.','2025-10-28 19:55:49',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('832','3','Login successful','','2025-10-28 19:55:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('833','3','Logout','User logged out.','2025-10-28 19:56:06','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('834','15','Login successful','','2025-10-28 19:56:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('835','1','Login successful','','2025-10-29 09:23:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('836','1','Physical Inventory Saved','Saved product_id=44, physical_count=6, status=Understock','2025-10-29 09:24:15','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('839','1','Add Stock','Added 2 to H927 (ID:14) | Branch: Prinza Branch','2025-10-29 09:32:50','3');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('840','1','Add Stock','Added 5 to Generator (ID:9) | Branch: Batino Branch','2025-10-29 09:33:07','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('841','1','Logout','User logged out.','2025-10-29 09:33:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('842','15','Login successful','','2025-10-29 09:33:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('843','15','Logout','User logged out.','2025-10-29 09:34:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('844','1','Login successful','','2025-10-29 09:34:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('845','1','Update Account','Updated user: Stockman1 (Kenken), role: stockman, phone: 09935844994, branch_id=1','2025-10-29 09:35:29','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('846','1','Logout','User logged out.','2025-10-29 09:36:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('847','1','Login successful','','2025-10-29 09:36:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('848','15','Login successful','','2025-10-29 09:36:21','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('849','15','Logout','User logged out.','2025-10-29 09:36:38','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('850','3','Login successful','','2025-10-29 09:36:42','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('851','3','Logout','User logged out.','2025-10-29 09:36:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('852','15','Login successful','','2025-10-29 09:37:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('853','15','Stock-In Request','Requested +10 for Oil 3 in 1 (ID:42) | Branch: Bucal Branch','2025-10-29 09:37:22','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('854','15','Logout','User logged out.','2025-10-29 09:37:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('855','1','Login successful','','2025-10-29 09:37:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('856','1','Update Account','Updated user: Staff123 (Dudong), role: staff, phone: 09215672315, branch_id=6','2025-10-29 09:39:25','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('857','1','Logout','User logged out.','2025-10-29 09:39:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('858',NULL,'Login failed for username: Staff123','','2025-10-29 09:39:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('859',NULL,'Login failed for username: Staff123','','2025-10-29 09:43:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('860',NULL,'Login failed for username: Staff123','','2025-10-29 09:44:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('861','1','Login successful','','2025-10-29 09:45:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('862','1','Update Account','Updated user: Staff123 (Dudong), role: staff, phone: 09215672315, branch_id=6','2025-10-29 09:48:19','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('863','1','Logout','User logged out.','2025-10-29 09:48:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('864','31','Login successful','','2025-10-29 09:48:34','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('865','31','Logout','User logged out.','2025-10-29 09:49:31','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('866','1','Login successful','','2025-10-29 10:11:48',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('867','1','Logout','User logged out.','2025-10-29 10:11:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('868','1','Login successful','','2025-10-29 10:20:59',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('869','1','Login successful','','2025-10-29 13:28:22',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('870','1','Logout','User logged out.','2025-10-29 13:28:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('871','1','Login successful','','2025-10-29 13:28:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('872','1','Edit Service','Edited service: Yeyor (ID: 23) | Branch: Barandal Branch','2025-10-29 13:28:46','6');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('873','1','Logout','User logged out.','2025-10-29 13:31:06',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('874','3','Login successful','','2025-10-29 18:12:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('875','3','Logout','User logged out.','2025-10-29 18:15:02','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('876','1','Login successful','','2025-10-29 18:15:07',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('877','1','Logout','User logged out.','2025-10-29 18:15:24',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('878','3','Login successful','','2025-10-29 18:17:17','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('879','3','Logout','User logged out.','2025-10-29 19:51:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('880','1','Login successful','','2025-10-29 19:51:14',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('881','1','Logout','User logged out.','2025-10-29 19:53:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('882','3','Login successful','','2025-10-29 19:53:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('883','3','Logout','User logged out.','2025-10-29 19:53:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('884','1','Login successful','','2025-10-29 19:53:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('885','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 19:55:07','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('886','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:01:30','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('887','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:01:39','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('888','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:01:56','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('889','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:02:54','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('890','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:03:24','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('891','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:03:29','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('892','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:06:27','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('893','1','Update Branch','Updated branch_id=5 to \'00Bucal\'','2025-10-29 20:06:33','5');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('894','1','Logout','User logged out.','2025-10-29 20:22:17',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('895','3','Login successful','','2025-10-29 20:22:30','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('896','3','Login successful','','2025-10-29 21:41:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('897','3','Logout','User logged out.','2025-10-29 22:30:48','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('898','1','Login successful','','2025-10-29 22:31:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('899','1','Logout','User logged out.','2025-10-29 22:32:58',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('900','3','Login successful','','2025-10-29 22:33:03','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('901','3','Logout','User logged out.','2025-10-29 22:45:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('902','1','Login successful','','2025-10-29 22:45:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('903','1','Logout','User logged out.','2025-10-29 22:46:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('904','3','Login successful','','2025-10-29 22:46:34','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('905','3','Logout','User logged out.','2025-10-29 22:55:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('906','1','Login successful','','2025-10-29 22:56:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('907','1','Logout','User logged out.','2025-10-29 22:57:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('908','15','Login successful','','2025-10-29 22:57:37','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('909','15','Physical Inventory Saved','Saved product_id=44, physical_count=20, status=Overstock','2025-10-29 22:58:28','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('910','15','Logout','User logged out.','2025-10-29 22:58:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('911','3','Login successful','','2025-10-29 22:58:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('912','3','Login successful','','2025-10-30 09:18:51','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('913','3','Logout','User logged out.','2025-10-30 09:38:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('914',NULL,'Login failed for username: admin123','','2025-10-30 09:38:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('915','1','Login successful','','2025-10-30 09:38:47',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('916','1','Logout','User logged out.','2025-10-30 09:40:11',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('917','3','Login successful','','2025-10-30 09:40:15','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('918','3','Logout','User logged out.','2025-10-30 09:40:49','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('919','1','Login successful','','2025-10-30 09:40:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('920','1','Logout','User logged out.','2025-10-30 09:41:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('921',NULL,'Login failed for username: admin123','','2025-10-30 09:41:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('922','3','Login successful','','2025-10-30 09:41:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('923','3','Login successful','','2025-10-30 10:03:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('924','3','Logout','User logged out.','2025-10-30 10:43:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('925','1','Login successful','','2025-10-30 10:44:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('926','1','Logout','User logged out.','2025-10-30 10:51:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('927','3','Login successful','','2025-10-30 10:51:30','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('928','3','Logout','User logged out.','2025-10-30 11:17:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('929','3','Login successful','','2025-10-30 11:17:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('930','3','Logout','User logged out.','2025-10-30 11:17:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('931','3','Login successful','','2025-10-30 21:17:52','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('932','3','Logout','User logged out.','2025-10-30 22:55:01','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('933','1','Login successful','','2025-10-30 22:55:04',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('934','1','Logout','User logged out.','2025-10-30 22:55:27',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('935','3','Login successful','','2025-10-30 22:55:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('936','3','Login successful','','2025-10-31 09:37:43','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('937','3','Logout','User logged out.','2025-10-31 13:15:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('938','1','Login successful','','2025-10-31 13:15:17',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('939','1','Logout','User logged out.','2025-10-31 13:15:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('940','3','Login successful','','2025-10-31 13:15:36','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('941','3','Logout','User logged out.','2025-10-31 13:15:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('942','1','Login successful','','2025-10-31 13:16:00',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('943','1','Logout','User logged out.','2025-10-31 13:16:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('944','3','Login successful','','2025-10-31 13:16:45','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('945','3','Logout','User logged out.','2025-10-31 13:16:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('946','3','Login successful','','2025-10-31 13:17:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('947','3','Logout','User logged out.','2025-10-31 13:17:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('948','1','Login successful','','2025-10-31 13:17:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('949','1','Logout','User logged out.','2025-10-31 13:30:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('950','3','Login successful','','2025-10-31 13:30:56','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('951','3','Logout','User logged out.','2025-10-31 13:31:09','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('952','1','Login successful','','2025-10-31 13:31:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('953','1','Logout','User logged out.','2025-10-31 13:31:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('954','3','Login successful','','2025-10-31 13:31:25','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('955','3','Logout','User logged out.','2025-10-31 13:31:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('956','1','Login successful','','2025-10-31 13:31:38',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('957','1','Logout','User logged out.','2025-10-31 13:31:46',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('958','3','Login successful','','2025-10-31 14:07:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('959','3','Logout','User logged out.','2025-10-31 14:07:31','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('960','1','Login successful','','2025-10-31 14:07:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('961','1','Logout','User logged out.','2025-10-31 14:07:51',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('962','3','Login successful','','2025-10-31 14:24:20','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('963','3','Logout','User logged out.','2025-10-31 14:37:16','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('964','1','Login successful','','2025-10-31 14:37:21',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('965','1','Logout','User logged out.','2025-10-31 14:37:41',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('966','3','Login successful','','2025-10-31 14:37:47','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('967','3','Logout','User logged out.','2025-10-31 14:40:12','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('968','1','Login successful','','2025-10-31 14:43:56',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('969','1','Create Account','Created new user: Stockman02 (Jerry), role: admin, phone: 09854910625','2025-10-31 14:45:17',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('970','1','Logout','User logged out.','2025-10-31 14:45:26',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('971','1','Login successful','','2025-10-31 14:45:37',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('972','1','Logout','User logged out.','2025-10-31 14:45:42',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('973',NULL,'Login failed for username: Stockman02','','2025-10-31 14:45:50',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('974','35','Login successful','','2025-10-31 14:46:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('975','35','Logout','User logged out.','2025-10-31 14:46:09',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('976','3','Login successful','','2025-10-31 14:48:13','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('977','1','Login successful','','2025-10-31 18:01:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('978','1','Logout','User logged out.','2025-10-31 18:28:20',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('979','3','Login successful','','2025-10-31 18:28:23','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('980','3','Login successful','','2025-10-31 18:38:11','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('981','3','Logout','User logged out.','2025-10-31 18:38:50','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('982','1','Login successful','','2025-11-02 11:39:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('983','1','Add Stock','Added 5 to Generator (ID:9) | Branch: Batino Branch','2025-11-02 11:39:59','2');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('984','1','Logout','User logged out.','2025-11-02 11:40:02',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('985','15','Login successful','','2025-11-02 11:40:05','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('986','15','Stock-In Request','Requested +50 for Sample Exp3 (ID:75) (Expiry: 2026-01-22) | Branch: Bucal Branch','2025-11-02 11:40:57','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('987','15','Logout','User logged out.','2025-11-02 11:40:59','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('988','1','Login successful','','2025-11-02 11:41:03',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('989','1','Logout','User logged out.','2025-11-02 11:41:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('990','1','Login successful','','2025-11-02 11:44:18',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('99','54','2','1','-1','Mismatch','1','6','2025-09-07 21:11:47');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('100','54','2','2','0','Match','1','6','2025-09-07 21:12:37');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('101','50','3','0','-3','Mismatch','1','6','2025-09-07 21:12:45');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('102','44','5','5','0','Match','15','1','2025-09-08 16:04:14');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('103','46','2','2','0','Match','15','1','2025-09-08 16:07:07');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('104','49','1','1','0','Match','15','1','2025-09-08 16:07:15');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('105','6','10','1','-9','Mismatch','1','1','2025-09-08 16:35:42');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('106','45','7','7','0','Match','1','1','2025-09-08 17:07:05');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('107','42','0','2','2','Mismatch','1','1','2025-09-09 21:45:59');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('108','43','11','11','0','Match','1','1','2025-09-09 21:46:09');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('109','45','5','5','0','Match','1','1','2025-09-09 21:50:16');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('110','47','3','2','-1','Mismatch','1','1','2025-09-09 21:53:00');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('111','43','11','1','10','','1','1','2025-09-09 21:56:12');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('112','47','3','3','0','','1','1','2025-09-09 21:56:35');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('113','44','0','0','0','','1','1','2025-09-09 21:57:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('114','46','1','1','0','','1','1','2025-09-09 21:57:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('115','6','20','20','0','','1','1','2025-09-09 21:57:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('116','42','0','0','0','','1','1','2025-09-09 21:57:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('117','43','11','11','0','','1','1','2025-09-09 21:57:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('118','16','19','19','0','','1','1','2025-09-09 21:57:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('119','44','8','4','4','','1','2','2025-09-09 21:57:45');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('120','9','9','3','6','','1','2','2025-09-09 21:57:45');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('121','49','20','20','0','','1','2','2025-09-09 21:57:45');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('122','6','20','19','1','','15','1','2025-09-10 01:25:06');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('123','6','20','18','2','','15','1','2025-09-13 20:49:56');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('124','47','10','10','0','','1','1','2025-09-16 13:59:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('125','58','10','10','0','','27','6','2025-09-20 18:30:45');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('126','49','0','23','23','','1','2','2025-09-21 17:42:57');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('127','49','0','0','0','','1','2','2025-09-21 17:43:04');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('128','44','8','6','2','','1','5','2025-10-29 09:24:15');
INSERT INTO `physical_inventory` (`id`,`product_id`,`system_stock`,`physical_count`,`discrepancy`,`status`,`counted_by`,`branch_id`,`count_date`) VALUES ('129','44','19','20','1','','15','1','2025-10-29 22:58:28');

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
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('6','Laptop','001','Solid','22000','10',NULL,'20','10',NULL,'2025-01-02 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('8','Pulsar Mat','002','Solid','950','10',NULL,'10','5',NULL,'2025-01-03 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('9','Generator','003','Solid','27000','10','-29700','10','5','12','2025-01-04 08:00:00','2025-09-23 18:13:22',NULL,'0',NULL,'','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('11','Engine Oil','004','Liquid','700','10',NULL,'20','5',NULL,'2025-01-05 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('13','H931','005','Solid','850','12','2295','20','5','12','2025-01-06 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('14','H927','006','Solid','1200','12','3000','20','5','12','2025-01-07 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('16','Tire 70 X 80 X 14','007','Solid','1200','10','1320','20','5','12','2025-01-08 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('42','Oil 3 in 1','008','Liquid','350','10','385','21','5','12','2025-01-09 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Goodyear','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('43','Sealant INF','009','Liquid','350','10',NULL,'20','5',NULL,'2025-01-10 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Flamingo','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('44','A/C Pro','010','Liquid','350','10','385','10','5','23','2025-01-11 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Flamingo','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('45','M040','011','Liquid','220','10',NULL,'10','5',NULL,'2025-01-12 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('46','ATF Premium','012','Liquid','220','10',NULL,'10','5',NULL,'2025-01-13 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('47','Sprint 4T 1 liter','013','Liquid','200','10',NULL,'15','5',NULL,'2025-01-14 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('48','oil 4 in 1','014','Liquid','5000','10',NULL,'20','5',NULL,'2025-01-15 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Michelin','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('49','Jeep Customize Limited Edition','015','Solid','300','12','336','20','5','12','2025-01-16 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('50','Oil 5 in 1','016','Liquid','1000000','10',NULL,'5','2',NULL,'2025-01-17 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('54','java chip','017','Solid','150','10',NULL,'10','5',NULL,'2025-01-18 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Bridgestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('55','java chip','018','Solid','150','10','165','10','5','121','2025-01-19 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Bridgestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('56','Ding dong','019','Solid','20','10','22','10','3','123','2025-01-20 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Petron','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('58','Iphone 17 Viva Max','021','Electronics','65000','10',NULL,'20','5',NULL,'2025-01-21 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Iphone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('59','80x80x80','022','Solid','200','20','220','10','5','12','2025-01-22 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Goodyear','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('60','Slap Soil','128','','200','15','230','20','5','12','2025-01-23 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Bridgestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('61','Product 80x20','123','','858','12','960.96','20','12','12','2025-01-24 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Castrol','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('62','Slap Soil','1192','','235','12','263.2','20','5','12','2025-01-25 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Bridgestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('63','Nigger','132','','300','12','336','20','12','12','2025-01-26 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Bridgestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('64','Slave Nigger','2000000000640','Electronics','300','12','336','20','5','12','2025-01-27 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Bridgestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('65','Nigger','2000000000657','Electronics','900','12','1008','20','5','12','2025-01-28 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Bridgestone','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('66','Laptop','2000000000664','Electronics','24000','12','26880','10','5','12','2025-01-29 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Flamingo','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('67','Kopika 1n3','2000000000671','Liquid','20','12','22.4','50','10','12','2025-01-30 08:00:00','2025-09-20 22:20:54',NULL,'0',NULL,'Goodyear','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('68','Gulong ni Marlou','2000000000688',NULL,'250','10','275','20','5','12','2025-09-21 18:30:36','2025-09-21 18:30:36',NULL,'0',NULL,'Michelin','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('69','Gulong ni Dudoy','2000000000695',NULL,'200','12','224','20','5','12','2025-09-21 19:20:33','2025-09-21 19:20:33',NULL,'0',NULL,'Michelin','0');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('70','Gogolonggong','0101','Tire','2020','12','2262.4','20','5','12','2025-09-21 19:24:27','2025-10-31 14:58:48','2025-10-30','0',NULL,'Michelin','1');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('71','Tire Betch','2000000000718','Tire','200','12','224','20','5','12','2025-09-24 17:42:30','2025-09-28 14:20:38','2025-12-25','0',NULL,'Castrol','1');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('74','Sample Exp1','2000000000749','Solid','2309','12','2586.08','30','10','12','2025-09-28 15:22:39','2025-09-28 15:22:39','2025-12-01','0',NULL,'Bridgestone','1');
INSERT INTO `products` (`product_id`,`product_name`,`barcode`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`created_at`,`updated_at`,`expiration_date`,`archived`,`archived_at`,`brand_name`,`expiry_required`) VALUES ('75','Sample Exp3','2000000000756','Solid','1234','12','1382.08','200','5','12','2025-09-28 15:34:00','2025-09-28 15:34:00','2025-12-01','0',NULL,'Flamingo','1');

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
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('1','1',NULL,'2025-04-30 23:36:57','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('2','1',NULL,'2025-04-30 23:40:07','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('3','1',NULL,'2025-05-01 00:14:42','44000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('4','2',NULL,'2025-05-01 01:05:40','27000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('5','1',NULL,'2025-05-02 13:39:21','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('6','1',NULL,'2025-05-02 14:32:57','220000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('7','1',NULL,'2025-05-06 00:28:12','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('8','1',NULL,'2025-05-06 00:28:27','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('9','1',NULL,'2025-05-06 00:36:45','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('10','1',NULL,'2025-05-26 23:27:03','102000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('11','1',NULL,'2025-06-25 17:51:23','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('12','1',NULL,'2025-06-25 17:52:19','950.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('13','1',NULL,'2025-06-25 18:01:29','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('14','1',NULL,'2025-06-25 19:37:52','85000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('15','1',NULL,'2025-06-25 19:39:14','1000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('16','1',NULL,'2025-06-25 19:41:13','14000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('17','1',NULL,'2025-07-05 18:57:51','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('18','1',NULL,'2025-07-05 19:06:52','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('19','1',NULL,'2025-07-05 19:09:05','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('20','1',NULL,'2025-07-05 19:12:07','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('21','1',NULL,'2025-07-05 19:12:58','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('22','1',NULL,'2025-07-05 19:14:23','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('23','1',NULL,'2025-07-05 19:14:36','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('24','1',NULL,'2025-07-05 19:17:05','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('25','1',NULL,'2025-07-05 19:17:11','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('26','1',NULL,'2025-07-05 19:17:24','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('27','1',NULL,'2025-07-05 19:20:35','22000.00','0.00','0.00',NULL,'Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('28','1',NULL,'2025-07-05 19:38:12','23250.00','23250.00','0.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('29','1',NULL,'2025-07-05 19:38:36','23250.00','23250.00','0.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('30','1',NULL,'2025-07-05 19:44:30','37250.00','37250.00','0.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('31','3',NULL,'2025-07-05 19:46:35','870.00','900.00','30.00','9','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('32','1',NULL,'2025-07-30 17:39:10','720.00','1000.00','280.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('33','1',NULL,'2025-07-30 17:39:54','460.00','500.00','40.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('34','1',NULL,'2025-07-30 17:56:55','22010.00','23000.00','990.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('35','1',NULL,'2025-07-31 17:05:44','44020.00','55000.00','10980.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('36','1',NULL,'2025-08-04 20:17:25','1150.00','2000.00','850.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('37','1',NULL,'2025-08-05 15:55:38','400.00','400.00','0.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('38','5',NULL,'2025-08-05 19:16:56','600400.00','1000000.00','399600.00','20','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('39','6',NULL,'2025-08-12 00:49:22','500.00','600.00','100.00','21','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('40','1',NULL,'2025-08-13 10:41:19','300.00','300.00','0.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('41','1',NULL,'2025-08-13 10:46:57','1200.00','2000.00','800.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('42','1',NULL,'2025-08-13 10:47:56','300.00','300.00','0.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('43','1',NULL,'2025-08-22 16:50:55','1120.00','2000.00','880.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('44','1',NULL,'2025-08-27 14:09:29','230.00','250.00','20.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('45','1',NULL,'2025-08-27 14:11:36','230.00','250.00','20.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('46','1',NULL,'2025-08-27 14:14:52','242.00','250.00','8.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('47','1',NULL,'2025-08-27 14:22:06','242.00','252.00','10.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('48','1',NULL,'2025-08-27 14:22:23','0.00','252.00','252.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('49','1',NULL,'2025-08-27 14:22:35','0.00','252.00','252.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('50','1',NULL,'2025-08-27 14:22:43','242.00','250.00','8.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('51','1',NULL,'2025-08-27 14:24:02','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('52','1',NULL,'2025-08-27 14:24:18','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('53','1',NULL,'2025-08-27 14:24:32','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('54','1',NULL,'2025-08-27 14:25:24','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('55','1',NULL,'2025-08-27 14:25:25','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('56','1',NULL,'2025-08-27 14:25:37','242.00','250.00','8.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('57','1',NULL,'2025-08-27 14:25:47','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('58','1',NULL,'2025-08-27 14:26:33','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('59','1',NULL,'2025-08-27 14:26:50','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('60','1',NULL,'2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('61','1',NULL,'2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('62','1',NULL,'2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('63','1',NULL,'2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('64','1',NULL,'2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('65','1',NULL,'2025-08-27 14:26:57','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('66','1',NULL,'2025-08-27 14:26:57','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('67','1',NULL,'2025-08-27 14:27:46','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('68','1',NULL,'2025-08-27 14:28:48','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('69','1',NULL,'2025-08-27 14:28:49','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('70','1',NULL,'2025-08-27 14:29:17','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('71','1',NULL,'2025-08-27 14:33:31','385.00','390.00','5.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('72','1',NULL,'2025-08-28 03:06:47','450.00','500.00','50.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('73','1',NULL,'2025-09-04 20:24:03','25.00','50.00','25.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('74','1',NULL,'2025-09-04 20:30:54','450.00','500.00','50.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('75','1',NULL,'2025-09-04 20:31:11','242.00','250.00','8.00','3','Refunded','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('76','1',NULL,'2025-09-04 20:37:26','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('77','1',NULL,'2025-09-04 20:38:02','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('78','1',NULL,'2025-09-04 20:38:07','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('79','1',NULL,'2025-09-04 20:38:07','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('80','1',NULL,'2025-09-04 20:38:07','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('81','1',NULL,'2025-09-04 20:38:08','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('82','1',NULL,'2025-09-04 20:38:08','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('83','1',NULL,'2025-09-04 20:38:08','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('84','1',NULL,'2025-09-04 20:38:28','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('85','1',NULL,'2025-09-04 20:39:36','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('86','1',NULL,'2025-09-04 20:39:57','0.00','250.00','250.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('87','1',NULL,'2025-09-04 20:49:37','385.00','400.00','15.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('88','1',NULL,'2025-09-04 20:49:43','0.00','400.00','400.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('89','1',NULL,'2025-09-04 20:49:46','0.00','400.00','400.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('90','1',NULL,'2025-09-04 20:49:46','0.00','400.00','400.00','3','Completed','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('91','1',NULL,'2025-09-04 20:52:02','242.00','250.00','8.00','3','Refunded','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('93','1',NULL,'2025-09-04 20:52:47','242.00','250.00','8.00','3','Refunded','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('95','1',NULL,'2025-09-20 20:46:05','4885.00','5000.00','115.00','3','Partial Refund','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('96','1',NULL,'2025-09-20 20:46:25','385.00','400.00','15.00','3','Refunded','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('98','1',NULL,'2025-10-29 18:13:42','4524.80','6000.00','932.00','3','Refunded','0.00','amount','542.98');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('99','1',NULL,'2025-10-29 19:06:43','30766.56','40000.00','8445.00','3','Refunded','0.00','amount','787.99');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('100','1',NULL,'2025-10-29 19:08:20','51613.08','60000.00','7988.00','3','Refunded','0.00','amount','398.88');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('101','1',NULL,'2025-10-29 20:23:00','39170.40','40000.00','469.00','3','Partial Refund','0.00','amount','360.04');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('102','1',NULL,'2025-10-29 20:40:35','3213.08','4000.00','430.00','3','Partial Refund','0.00','amount','356.53');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('103','1',NULL,'2025-10-29 21:58:36','869.00','1000.00','42.00','3','Partial Refund','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('104','1',NULL,'2025-10-29 22:37:22','721.00','1000.00','150.00','3','Refunded','0.00','amount','128.87');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('105','1',NULL,'2025-10-29 22:40:47','869.00','1000.00','42.00','3','Partial Refund','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('106','1',NULL,'2025-10-29 22:50:16','2647.40','4000.00','992.00','3','Refunded','0.00','amount','360.04');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('107','1',NULL,'2025-10-30 09:33:59','869.00','1000.00','42.00','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('108','1',NULL,'2025-10-30 09:40:36','869.00','1000.00','42.00','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('109','1','2','2025-10-30 21:56:36','2889.40','4000.00','750.56','3','Refunded','0.00','amount','360.04');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('110','1','3','2025-10-30 22:13:14','484.00','500.00','16.00','3','Refunded','0.00','amount','0.00');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('111','1','3','2025-10-30 22:13:49','869.00','1000.00','42.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('112','1','3','2025-10-30 22:27:46','385.00','500.00','26.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('113','1','4','2025-10-30 22:45:22','627.00','1000.00','284.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('114','1','4','2025-10-30 22:46:46','869.00','1000.00','42.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('115','1','4','2025-10-30 22:47:09','627.00','1000.00','284.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('116','1','4','2025-10-30 22:48:59','627.00','1000.00','284.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('117','1','4','2025-10-30 22:49:24','627.00','1000.00','284.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('118','1','4','2025-10-30 22:49:45','385.00','500.00','26.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('119','1','4','2025-10-30 22:50:00','385.00','500.00','26.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('120','1','4','2025-10-30 22:50:24','385.00','500.00','26.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('121','1','4','2025-10-31 09:43:46','869.00','1000.00','42.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('122','1','4','2025-10-31 09:44:40','385.00','500.00','26.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('123','1','5','2025-10-31 09:52:16','869.00','1000.00','42.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('124','1','5','2025-10-31 11:06:58','627.00','1000.00','284.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('125','1','5','2025-10-31 13:16:55','627.00','1000.00','284.45','3','Refunded','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('126','1','5','2025-10-31 14:24:54','1012.00','1200.00','10.90','3','Refunded','0.00','amount','177.10');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('127','1','5','2025-10-31 14:31:22','869.00','1000.00','42.45','3','completed','0.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('128','1','5','2025-10-31 14:48:58','869.00','1000.00','542.45','3','completed','500.00','amount','88.55');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('129','1','5','2025-10-31 14:56:00','2889.40','3500.00','250.56','3','completed','0.00','amount','360.04');
INSERT INTO `sales` (`sale_id`,`branch_id`,`shift_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`,`discount`,`discount_type`,`vat`) VALUES ('130','1','5','2025-10-31 14:56:48','385.00','500.00','26.45','3','completed','0.00','amount','88.55');

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
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('1','1','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('2','2','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('3','3','6','2','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('4','4','9','1','27000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('5','5','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('6','6','6','10','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('7','7','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('8','8','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('9','9','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('10','10','6','3','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('11','10','7','1','36000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('12','11','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('13','12','8','1','950.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('14','13','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('15','14','15','85','1000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('16','15','15','1','1000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('17','16','15','14','1000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('18','17','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('19','18','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('20','19','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('21','20','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('22','21','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('23','22','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('24','23','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('25','24','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('26','25','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('27','26','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('28','27','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('29','28','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('30','29','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('31','30','7','1','36000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('32','31','11','1','700.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('33','32','43','2','350.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('34','33','46','2','220.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('35','34','6','1','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('36','35','6','2','22000.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('37','36','46','5','220.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('38','38','49','2000','250.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('39','43','44','1','350.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('40','43','42','1','350.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('41','44','46','1','220.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('42','45','46','1','220.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('43','46','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('44','47','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('45','50','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('46','56','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('47','71','44','1','385.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('48','75','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('49','87','44','1','385.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('50','91','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('51','93','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('52','95','44','1','385.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('53','96','44','1','385.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('55','98','70','2','2262.40','542.98');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('56','99','70','1','2262.40','271.49');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('57','99','6','1','24200.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('58','99','49','1','336.00','40.32');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('59','99','74','1','2586.08','310.33');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('60','99','75','1','1382.08','165.85');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('61','100','6','2','24200.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('62','100','74','1','2586.08','310.33');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('63','100','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('64','100','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('65','101','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('66','101','6','1','24200.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('67','101','70','1','2262.40','271.49');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('68','102','74','1','2586.08','310.33');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('69','102','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('70','102','42','1','385.00','46.20');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('71','103','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('72','103','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('73','103','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('74','104','49','1','336.00','40.32');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('75','104','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('76','105','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('77','105','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('78','105','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('79','106','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('80','106','70','1','2262.40','271.49');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('81','107','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('82','107','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('83','107','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('84','108','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('85','108','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('86','108','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('87','109','70','1','2262.40','271.49');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('88','109','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('89','109','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('90','110','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('91','110','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('92','111','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('93','111','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('94','111','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('95','112','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('96','113','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('97','113','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('98','114','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('99','114','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('100','114','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('101','115','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('102','115','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('103','116','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('104','116','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('105','117','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('106','117','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('107','118','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('108','119','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('109','120','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('110','121','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('111','121','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('112','121','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('113','122','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('114','123','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('115','123','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('116','123','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('117','124','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('118','124','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('119','125','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('120','125','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('121','126','44','2','385.00','177.10');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('122','126','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('123','127','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('124','127','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('125','127','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('126','128','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('127','128','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('128','128','45','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('129','129','44','1','385.00','88.55');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('130','129','46','1','242.00','0.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('131','129','70','1','2262.40','271.49');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`,`vat`) VALUES ('132','130','44','1','385.00','88.55');

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
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('1','14','102','45','1','242.00','2025-10-29 21:58:27');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('2','14','102','74','1','2586.08','2025-10-29 21:58:27');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('3','15','103','44','1','385.00','2025-10-29 21:58:50');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('4','16','103','45','1','242.00','2025-10-29 21:59:05');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('5','17','103','46','1','242.00','2025-10-29 21:59:17');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('6','18','105','44','1','385.00','2025-10-29 22:41:08');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('7','18','105','46','1','242.00','2025-10-29 22:41:08');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('8','21','106','44','1','385.00','2025-10-30 09:19:07');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('9','23','106','70','1','2262.40','2025-10-30 09:27:06');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('14','27','104','44','1','385.00','2025-10-30 09:31:19');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('15','28','104','49','1','336.00','2025-10-30 09:31:27');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('16','29','107','44','1','385.00','2025-10-30 09:34:08');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('17','29','107','46','1','242.00','2025-10-30 09:34:08');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('18','30','107','45','1','242.00','2025-10-30 09:34:15');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('19','31','108','44','1','385.00','2025-10-30 09:40:47');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('20','31','108','46','1','242.00','2025-10-30 09:40:47');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('21','31','108','45','1','242.00','2025-10-30 09:40:47');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('22','32','95','44','1','385.00','2025-10-30 11:16:20');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('23','33','109','44','1','385.00','2025-10-30 22:12:19');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('24','33','109','46','1','242.00','2025-10-30 22:12:19');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('25','34','109','70','1','2262.40','2025-10-30 22:12:35');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('26','35','110','46','1','242.00','2025-10-30 22:13:25');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('27','36','110','45','1','242.00','2025-10-30 22:13:29');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('28','37','111','44','1','385.00','2025-10-30 22:13:56');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('29','37','111','46','1','242.00','2025-10-30 22:13:56');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('30','38','111','45','1','242.00','2025-10-30 22:14:01');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('31','39','112','44','1','385.00','2025-10-30 22:44:17');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('32','40','113','44','1','385.00','2025-10-30 22:46:16');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('33','41','113','46','1','242.00','2025-10-30 22:46:35');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('34','42','114','44','1','385.00','2025-10-30 22:46:55');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('35','42','114','46','1','242.00','2025-10-30 22:46:55');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('36','43','114','45','1','242.00','2025-10-30 22:47:00');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('37','44','115','44','1','385.00','2025-10-30 22:47:15');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('38','45','115','46','1','242.00','2025-10-30 22:47:18');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('39','46','116','44','1','385.00','2025-10-30 22:49:11');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('40','47','116','46','1','242.00','2025-10-30 22:49:16');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('41','48','117','44','1','385.00','2025-10-30 22:49:30');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('42','48','117','46','1','242.00','2025-10-30 22:49:30');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('43','49','118','44','1','385.00','2025-10-30 22:49:52');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('44','50','119','44','1','385.00','2025-10-30 22:50:11');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('45','51','120','44','1','385.00','2025-10-30 22:50:36');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('46','52','121','44','1','385.00','2025-10-31 09:43:57');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('47','53','121','45','1','242.00','2025-10-31 09:44:05');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('48','54','121','46','1','242.00','2025-10-31 09:44:12');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('49','55','122','44','1','385.00','2025-10-31 09:45:06');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('50','56','123','44','1','385.00','2025-10-31 09:53:15');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('51','56','123','46','1','242.00','2025-10-31 09:53:15');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('52','56','123','45','1','242.00','2025-10-31 09:53:15');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('53','57','124','44','1','385.00','2025-10-31 11:07:16');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('54','58','124','46','1','242.00','2025-10-31 13:15:55');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('55','59','125','44','1','385.00','2025-10-31 13:31:08');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('56','60','125','46','1','242.00','2025-10-31 13:31:31');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('57','61','126','46','1','242.00','2025-10-31 14:25:24');
INSERT INTO `sales_refund_items` (`id`,`refund_id`,`sale_id`,`product_id`,`qty`,`price`,`created_at`) VALUES ('58','62','126','44','2','385.00','2025-10-31 14:25:44');

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
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('1','93','3','252.00','0.00','Damaged product','2025-09-06 21:06:19','0.00',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('2','93','3','252.00','0.00','Damaged product','2025-09-06 21:07:29','0.00',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('3','93','3','242.00','0.00','Wrong item delivered','2025-09-06 21:13:42','0.00',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('4','96','3','385.00','46.20','Customer changed mind','2025-09-29 20:21:01','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('5','91','3','242.00','29.04','Wrong item delivered','2025-09-29 21:31:39','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('6','98','3','4524.80','542.98','Expired product','2025-10-29 18:14:55','5067.78',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('7','75','3','242.00','29.04','Other','2025-10-29 20:24:36','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('8','101','3','385.00','46.20','Damaged product','2025-10-29 20:25:11','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('9','100','3','51613.08','6193.57','Wrong item delivered','2025-10-29 20:33:18','57806.65',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('10','101','3','26847.40','3221.69','Damaged product','2025-10-29 20:39:33','30069.09',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('11','101','3','24585.00','2950.20','Damaged product','2025-10-29 20:40:00','27535.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('12','99','3','29384.48','3526.14','Customer changed mind','2025-10-29 20:40:13','32910.62',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('13','102','3','2586.08','310.33','Damaged product','2025-10-29 20:40:53','2896.41',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('14','102','3','2828.08','339.37','Damaged product','2025-10-29 21:58:27','3167.45',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('15','103','3','385.00','46.20','Damaged product','2025-10-29 21:58:50','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('16','103','3','242.00','29.04','Damaged product','2025-10-29 21:59:05','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('17','103','3','242.00','29.04','Customer changed mind','2025-10-29 21:59:17','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('18','105','3','627.00','75.24','Damaged product','2025-10-29 22:41:08','702.24',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('19','105','3','0.00','0.00','Wrong item delivered','2025-10-29 22:41:34','0.00',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('20','105','3','0.00','0.00','Expired product','2025-10-29 22:41:47','0.00',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('21','106','3','385.00','46.20','Damaged product','2025-10-30 09:19:07','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('22','106','3','0.00','0.00','Damaged product','2025-10-30 09:19:14','0.00',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('23','106','3','2262.40','271.49','Expired product','2025-10-30 09:27:06','2533.89',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('27','104','3','385.00','46.20','Damaged product','2025-10-30 09:31:19','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('28','104','3','336.00','40.32','Wrong item delivered','2025-10-30 09:31:27','376.32',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('29','107','3','627.00','75.24','Damaged product','2025-10-30 09:34:08','702.24',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('30','107','3','242.00','29.04','Customer changed mind','2025-10-30 09:34:15','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('31','108','3','869.00','104.28','Customer changed mind','2025-10-30 09:40:47','973.28',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('32','95','3','385.00','46.20','Damaged product','2025-10-30 11:16:20','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('33','109','3','627.00','75.24','Wrong item delivered','2025-10-30 22:12:19','702.24',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('34','109','3','2262.40','271.49','Wrong item delivered','2025-10-30 22:12:35','2533.89',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('35','110','3','242.00','29.04','Damaged product','2025-10-30 22:13:25','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('36','110','3','242.00','29.04','Customer changed mind','2025-10-30 22:13:29','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('37','111','3','627.00','75.24','Wrong item delivered','2025-10-30 22:13:56','702.24',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('38','111','3','242.00','29.04','Wrong item delivered','2025-10-30 22:14:01','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('39','112','3','385.00','46.20','Wrong item delivered','2025-10-30 22:44:17','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('40','113','3','385.00','46.20','Expired product','2025-10-30 22:46:16','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('41','113','3','242.00','29.04','Damaged product','2025-10-30 22:46:35','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('42','114','3','627.00','75.24','Customer changed mind','2025-10-30 22:46:55','702.24',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('43','114','3','242.00','29.04','Wrong item delivered','2025-10-30 22:47:00','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('44','115','3','385.00','46.20','Customer changed mind','2025-10-30 22:47:15','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('45','115','3','242.00','29.04','Expired product','2025-10-30 22:47:18','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('46','116','3','385.00','46.20','Customer changed mind','2025-10-30 22:49:11','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('47','116','3','242.00','29.04','Damaged product','2025-10-30 22:49:16','271.04',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('48','117','3','627.00','75.24','Wrong item delivered','2025-10-30 22:49:30','702.24',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('49','118','3','385.00','46.20','Customer changed mind','2025-10-30 22:49:52','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('50','119','3','385.00','46.20','Wrong item delivered','2025-10-30 22:50:11','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('51','120','3','385.00','46.20','Damaged product','2025-10-30 22:50:36','431.20',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('52','121','3','385.00','39.23','Customer changed mind','2025-10-31 09:43:57','424.23',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('53','121','3','242.00','24.66','Wrong item delivered','2025-10-31 09:44:05','266.66',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('54','121','3','242.00','24.66','Expired product','2025-10-31 09:44:12','266.66',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('55','122','3','385.00','88.55','Wrong item delivered','2025-10-31 09:45:06','473.55',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('56','123','3','869.00','88.55','Wrong item delivered','2025-10-31 09:53:15','957.55',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('57','124','3','385.00','54.37','Wrong item delivered','2025-10-31 11:07:16','439.37',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('58','124','3','242.00','34.18','Wrong item delivered','2025-10-31 13:15:55','276.18',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('59','125','3','385.00','54.37','Damaged product','2025-10-31 13:31:08','439.37',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('60','125','3','242.00','34.18','Damaged product','2025-10-31 13:31:31','276.18',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('61','126','3','242.00','42.35','Wrong item delivered','2025-10-31 14:25:24','284.35',NULL);
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_vat`,`refund_reason`,`refund_date`,`refund_total`,`shift_id`) VALUES ('62','126','3','770.00','134.75','Expired product','2025-10-31 14:25:44','904.75',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('1','37','1','400.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('2','38','1','400.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('4','40','2','300.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('5','41','3','1200.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('6','42','2','300.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('7','43','1','400.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('8','72','14','450.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('9','73','15','25.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('10','74','14','450.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('11','95','9','4500.00','0.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`,`vat`) VALUES ('12','101','9','12323.00','0.00');

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('9','1','Computer Wheel Alignment','12323.00','Sample 1','2025-09-20 21:58:06','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('16','2','Oil Change','233.00','asdasd','2025-09-20 21:58:06','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('18','4','Computer Wheel Alignment','23.00','Lorem Ipsum','2025-09-20 21:58:06','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('20','4','Oil Change','256.00','Lorem Ipsum','2025-09-20 21:58:06','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('21','4','asd','232.00','asd','2025-09-20 21:58:06','1',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('22','1','Vulcanize','250.00','','2025-09-20 21:58:06','1',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('23','6','Yeyor','200.00','Samples','2025-09-20 21:58:06','0',NULL);
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`created_at`,`archived`,`archived_at`) VALUES ('24','5','Change Tire','250.00','Change Tire for Sedan Cars','2025-09-21 16:57:56','1',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('1','1','pay_out','200.00','Food','2025-10-30 11:14:51');
INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('2','1','pay_in','200.00','','2025-10-30 11:15:07');
INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('3','4','pay_out','500.00','Shopee','2025-10-30 22:45:56');
INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('4','4','pay_in','500.00','Papalit','2025-10-30 23:00:23');
INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('5','4','pay_out','240.00','240','2025-10-30 23:00:41');
INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('6','5','pay_out','100.00','shoppee','2025-10-31 09:52:49');
INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('7','5','pay_in','100.00','Food','2025-10-31 11:06:36');
INSERT INTO `shift_cash_moves` (`id`,`shift_id`,`move_type`,`amount`,`reason`,`created_at`) VALUES ('8','5','pay_out','500.00','Lazada','2025-10-31 12:41:56');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `shifts` (`shift_id`,`user_id`,`branch_id`,`start_time`,`opening_cash`,`opening_note`,`end_time`,`closing_cash`,`expected_cash`,`cash_difference`,`closing_note`,`status`) VALUES ('1','3','1','2025-10-30 11:13:55','3000.00','','2025-10-30 21:18:20','5000.00','3000.00','2000.00','','closed');
INSERT INTO `shifts` (`shift_id`,`user_id`,`branch_id`,`start_time`,`opening_cash`,`opening_note`,`end_time`,`closing_cash`,`expected_cash`,`cash_difference`,`closing_note`,`status`) VALUES ('2','3','1','2025-10-30 21:55:29','5000.00','','2025-10-30 22:10:34','5000.00','8249.44','-3249.44','','closed');
INSERT INTO `shifts` (`shift_id`,`user_id`,`branch_id`,`start_time`,`opening_cash`,`opening_note`,`end_time`,`closing_cash`,`expected_cash`,`cash_difference`,`closing_note`,`status`) VALUES ('3','3','1','2025-10-30 22:10:40','2000.00','','2025-10-30 22:45:05','3915.10','3915.10','0.00','','closed');
INSERT INTO `shifts` (`shift_id`,`user_id`,`branch_id`,`start_time`,`opening_cash`,`opening_note`,`end_time`,`closing_cash`,`expected_cash`,`cash_difference`,`closing_note`,`status`) VALUES ('4','3','1','2025-10-30 22:45:09','5000.00','','2025-10-31 09:52:07','4924.56','11431.50','-6506.94','','closed');
INSERT INTO `shifts` (`shift_id`,`user_id`,`branch_id`,`start_time`,`opening_cash`,`opening_note`,`end_time`,`closing_cash`,`expected_cash`,`cash_difference`,`closing_note`,`status`) VALUES ('5','3','1','2025-10-31 09:52:10','2000.00','',NULL,NULL,NULL,NULL,NULL,'open');

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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('1','6','1','5',NULL,'','approved','15','2025-09-15 20:12:38','1','2025-09-15 20:12:54','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('2','46','1','2',NULL,'','rejected','15','2025-09-15 20:19:53','1','2025-09-15 20:20:07','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('3','46','1','3',NULL,'','approved','15','2025-09-15 20:25:00','1','2025-09-15 20:25:14','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('4','44','1','5',NULL,'','approved','15','2025-09-15 20:27:47','1','2025-09-15 20:29:39','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('5','45','1','5',NULL,'','rejected','15','2025-09-15 20:28:43','1','2025-09-15 20:29:56','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('6','44','1','5',NULL,'','rejected','15','2025-09-15 20:32:08','1','2025-09-15 20:32:44','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('7','44','1','5',NULL,'','rejected','15','2025-09-15 20:44:20','1','2025-09-18 11:53:58','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('8','45','1','10',NULL,'','rejected','15','2025-09-16 14:25:55','1','2025-09-16 14:35:44','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('9','46','1','5',NULL,'','rejected','15','2025-09-18 12:38:55','1','2025-10-21 08:38:05','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('10','75','1','5','2025-10-22','','rejected','15','2025-09-29 00:37:56','1','2025-09-29 00:38:30','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('11','75','1','5','2025-10-22','','approved','15','2025-09-29 00:38:13','1','2025-09-29 00:38:33','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('12','75','1','5','2025-12-01','','approved','15','2025-09-29 00:43:09','1','2025-09-29 00:43:22','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('13','44','1','2',NULL,'','rejected','15','2025-09-29 00:48:55','1','2025-10-21 08:38:08','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('14','46','1','5',NULL,'','rejected','15','2025-09-29 00:51:16','1','2025-10-21 08:38:09','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('15','14','3','2',NULL,'','approved','1','2025-10-29 09:32:50','1','2025-10-29 09:32:50','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('16','9','2','5',NULL,'','approved','1','2025-10-29 09:33:07','1','2025-10-29 09:33:07','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('17','42','1','10',NULL,'','approved','15','2025-10-29 09:37:22','1','2025-10-29 09:37:44','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('18','9','2','5',NULL,'','approved','1','2025-11-02 11:39:59','1','2025-11-02 11:39:59','0');
INSERT INTO `stock_in_requests` (`id`,`product_id`,`branch_id`,`quantity`,`expiry_date`,`remarks`,`status`,`requested_by`,`request_date`,`decided_by`,`decision_date`,`archived`) VALUES ('19','75','1','50','2026-01-22','','approved','15','2025-11-02 11:40:57','1','2025-11-02 11:41:09','0');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transfer_logs` (`transfer_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`transfer_date`,`transferred_by`) VALUES ('1','8','1','4','5','2025-06-25 18:16:05',NULL);
INSERT INTO `transfer_logs` (`transfer_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`transfer_date`,`transferred_by`) VALUES ('2','6','1','4','1','2025-06-25 18:55:13',NULL);
INSERT INTO `transfer_logs` (`transfer_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`transfer_date`,`transferred_by`) VALUES ('3','6','1','4','1','2025-06-25 18:57:01',NULL);
INSERT INTO `transfer_logs` (`transfer_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`transfer_date`,`transferred_by`) VALUES ('4','6','1','4','1','2025-06-25 19:08:11',NULL);
INSERT INTO `transfer_logs` (`transfer_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`transfer_date`,`transferred_by`) VALUES ('5','6','1','4','1','2025-06-25 19:10:45',NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('1','6','4','1','2','15','approved','2025-07-27 13:48:01','2025-07-27 13:56:49','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('2','6','4','1','1','15','approved','2025-07-27 14:24:26','2025-07-27 14:24:38','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('3','42','5','1','1','15','approved','2025-07-31 01:15:23','2025-08-06 21:24:00','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('4','49','5','2','1','15','approved','2025-08-06 21:23:16','2025-08-06 21:27:06','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('5','49','2','5','1','15','approved','2025-08-06 21:40:55','2025-08-06 21:41:13','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('6','49','2','1','1','15','approved','2025-08-06 21:42:04','2025-08-06 21:42:21','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('7','44','1','2','1','15','approved','2025-08-22 16:46:17','2025-08-22 16:47:09','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('8','56','6','5','5','15','rejected','2025-09-03 16:06:48','2025-09-07 00:10:48','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('9','42','5','6','2','1','approved','2025-09-15 12:53:48','2025-09-15 12:54:00','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('10','44','1','6','2','1','approved','2025-09-15 12:54:24','2025-09-15 12:54:30','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('11','44','1','6','3','1','approved','2025-09-15 12:54:56','2025-09-15 12:55:02','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('12','44','6','1','5','1','rejected','2025-09-15 12:58:07','2025-09-15 12:58:50','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('13','44','6','1','5','1','rejected','2025-09-15 12:58:10','2025-09-15 12:58:45','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('14','44','6','1','5','1','rejected','2025-09-15 12:58:11','2025-09-15 12:58:43','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('15','44','6','1','5','1','rejected','2025-09-15 12:58:11','2025-09-15 12:58:42','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('16','44','6','1','5','1','rejected','2025-09-15 12:58:11','2025-09-15 12:58:38','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('17','44','6','1','5','1','rejected','2025-09-15 12:58:11','2025-09-15 12:58:37','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('18','44','6','1','5','1','rejected','2025-09-15 12:58:11','2025-09-15 12:58:35','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('19','44','6','1','5','1','rejected','2025-09-15 12:58:12','2025-09-15 12:58:33','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('20','44','6','1','5','1','rejected','2025-09-15 12:58:12','2025-09-15 12:58:32','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('21','44','6','1','5','1','rejected','2025-09-15 12:58:12','2025-09-15 12:58:30','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('22','44','6','1','5','1','rejected','2025-09-15 12:58:12','2025-09-15 12:58:29','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('23','44','6','1','5','1','rejected','2025-09-15 12:58:12','2025-09-15 12:58:27','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('24','44','6','1','5','1','rejected','2025-09-15 12:58:13','2025-09-15 12:58:25','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('25','44','6','1','5','1','rejected','2025-09-15 12:58:13','2025-09-15 12:58:23','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('26','44','6','1','5','1','rejected','2025-09-15 12:58:13','2025-09-15 12:58:19','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('27','44','6','1','5','1','approved','2025-09-15 12:59:00','2025-09-15 12:59:05','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('28','56','6','1','5','1','rejected','2025-09-15 12:59:49','2025-09-15 12:59:57','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('29','44','1','6','5','1','approved','2025-09-15 13:00:13','2025-09-15 13:00:25','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('30','44','6','1','5','1','approved','2025-09-15 13:03:59','2025-09-15 13:04:04','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('31','44','1','6','5','1','approved','2025-09-15 13:07:42','2025-09-15 13:08:03','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('32','44','6','1','5','1','approved','2025-09-15 13:08:49','2025-09-15 13:08:53','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('33','44','1','6','5','1','rejected','2025-09-15 13:09:10','2025-09-15 13:33:44','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('34','44','1','6','5','1','rejected','2025-09-15 13:35:33','2025-09-15 19:00:06','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('35','49','5','6','32','1','rejected','2025-09-15 13:42:44','2025-09-15 19:00:00','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('36','56','6','1','5','15','rejected','2025-09-15 19:31:21','2025-09-15 19:31:39','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('37','6','1','6','10','1','rejected','2025-09-15 20:23:11','2025-09-15 20:23:18','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('38','44','1','6','5','15','rejected','2025-09-15 20:32:25','2025-09-15 20:32:46','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('39','44','1','6','5','1','rejected','2025-09-15 20:43:25','2025-09-15 21:05:30','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('40','44','1','6','5','1','approved','2025-09-15 21:05:46','2025-09-15 21:05:46','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('41','44','1','6','5','1','approved','2025-09-15 21:06:19','2025-09-15 21:06:19','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('42','44','6','1','5','15','rejected','2025-09-15 21:07:15','2025-09-15 21:07:34','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('43','44','1','6','5','1','approved','2025-09-15 22:51:38','2025-09-15 22:51:38','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('44','44','6','1','5','15','approved','2025-09-15 22:54:13','2025-09-15 22:54:35','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('45','44','6','1','5','15','approved','2025-09-16 14:24:52','2025-09-18 11:54:02','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('46','44','6','1','2','15','approved','2025-09-18 12:39:05','2025-09-23 18:17:59','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('47','44','2','5','8','1','approved','2025-09-23 18:16:00','2025-09-23 18:16:00','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('48','44','6','1','3','15','approved','2025-09-23 18:21:56','2025-09-23 18:23:04','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('49','44','6','2','3','1','approved','2025-09-23 18:22:32','2025-09-23 18:22:32','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('50','67','5','6','10','15','approved','2025-09-24 18:36:04','2025-09-24 18:36:21','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('53','70','1','2','5','15','approved','2025-09-24 18:49:23','2025-09-24 18:50:09','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('54','70','1','6','10','1','approved','2025-09-24 18:49:57','2025-09-24 18:49:57','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('55','70','6','1','5','1','approved','2025-09-24 18:51:28','2025-09-24 18:51:28','1','0');

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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('1','admin123','Riza','09282876871','$2y$10$5NvLDzZGDyXCIYJUBQTXo.UyQVMOUs0BmXHiQ0tqjWa1aCaXtiITq','0','admin','2025-09-20 21:57:50',NULL,'0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('3','staff001','Budoy','','$2y$10$WSf318UBRgxqo1IToZu1xeJOu1LFLId.3U7WMHCLUhcKGjAY1.722','0','staff','2025-09-20 21:57:50','1','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('9','staff003','staff003','','$2y$10$4er4bKqGg2HNd9IAKuFYyOO2jPYM.tPT4IhwviLLJcZfcHJbNaNf2','0','staff','2025-09-20 21:57:50','3','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('11','Staff004','Staff004','','$2y$10$fVt.3Km2TI1.8/r0t67J4uwVfZSjDHqPjpc6fqFhFF9kz/v8/VQhK','0','staff','2025-09-20 21:57:50','4','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('15','Stockman1','Kenken','09935844994','$2y$10$Ojydo56b8qhUmHscrq.l1ezA/38GAsZDHkC00XnPZrlP16scb072S','0','stockman','2025-09-20 21:57:50','1','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('19','staff002','staff002','','$2y$10$FIjQ51vAFbIgtBlgnzaxWOjjjde9539jhGK1oBFUtKN66p00z50UW','0','staff','2025-09-20 21:57:50','2','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('20','yrrabz112426','yrrabz112426','','$2y$10$yntX5vvM8uVPE4WmiDVD9eX25c.dzdv9usXsI8DOQlqDBmO6jeSE2','0','staff','2025-09-20 21:57:50','5','1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('21','bars123','bars123','','$2y$10$CDBlvkq4DkrwrrL2QZLm6OfBzFsr..wg6SFfrRIxKTyXNG/El442.','0','staff','2025-09-20 21:57:50','6','1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('22','cooljohnric24@gmail.com','cooljohnric24@gmail.com','','$2y$10$kjdNpET/DWIzSvx4c9EpxOLJ.NP3dT1rulF0KtYnqgVjUGJlxilrq','0','admin','2025-09-20 21:57:50',NULL,'1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('25','sertaposnapo','sertaposnapo','','$2y$10$5aMr9lnohaAWPsoM90ueeOy0O8fcMK13NFD5vC9iDjh5xosDyVrDi','0','staff','2025-09-20 21:57:50','4','1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('26','Staff2','Dudong','','$2y$10$T/.ZRA5yUPNkpQvmPjZ9JOf9Td.nCEvvsV0BbuOmbqugT6qn1jZoe','0','staff','2025-09-20 21:57:50','4','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('27','barandal.stockman','barandal.stockman','','$2y$10$a.IanD2C.ESAjYhA6SY4j.bW.xiU.4l6wjpw5o.71zPAbyZGSJyWG','0','stockman','2025-09-20 21:57:50','6','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('28','bucal.stockman','bucal.stockman','','$2y$10$zhG53lSYMVVTfHU/10MfZO1Ct8QvZZHFnyg/MORiCB20jynIruY9C','0','stockman','2025-09-20 21:57:50','1','1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('31','Staff123','Dudong','09215672315','$2y$10$icRCTwxsqTTcbhPG0LxYUeChCvErOtyA5hqcobB1QjdFztpyjTsT.','0','staff','2025-09-23 20:44:01','6','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('33','Staff005','Kent','','$2y$10$7BStMYTn7e/rzmVkPYU/IeHWLr7MDqS0DZAqZXwPdmMsjyjdF88qO','0','staff','2025-09-24 15:57:18','3','0',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('34','Gel123','Reign','09944240934','$2y$10$atZp3CksAZQJlNo0KX0aDOFk3kwSFNKBrugeQModNKa19jjio2OU2','0','staff','2025-10-20 18:51:01','5','1',NULL);
INSERT INTO `users` (`id`,`username`,`name`,`phone_number`,`password`,`must_change_password`,`role`,`created_at`,`branch_id`,`archived`,`archived_at`) VALUES ('35','Stockman02','Jerry','09854910625','$2y$10$OAKDtoRLxJg0CqhrGNMNf.MAh.5bFUSmqVZJm56pgS8BlDJvCJn5.','0','admin','2025-10-31 14:45:17',NULL,'0',NULL);

SET FOREIGN_KEY_CHECKS=1;
