-- Simple dump for rp_habana @ 20250907_103617
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `branches`;
CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(255) NOT NULL,
  `branch_location` varchar(255) DEFAULT NULL,
  `branch_email` varchar(255) DEFAULT NULL,
  `branch_contact` varchar(255) DEFAULT NULL,
  `branch_contact_number` int(250) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`archived`) VALUES ('1','Bucal Branch','Bucal Bypass road','bypass@email.com','Riza',NULL,'0');
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`archived`) VALUES ('2','Batino Branch','Brgy. Batino near MCDO','batino@email.com','RJ',NULL,'0');
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`archived`) VALUES ('3','Prinza Branch','Brgy. Prinza','prinza@email.com','Mark',NULL,'0');
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`archived`) VALUES ('4','Halang Branch','Brgy. Halang','halang@email.com','AJ',NULL,'0');
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`archived`) VALUES ('5','00Bucal','Bucal','example@email.com','jr',NULL,'0');
INSERT INTO `branches` (`branch_id`,`branch_name`,`branch_location`,`branch_email`,`branch_contact`,`branch_contact_number`,`archived`) VALUES ('6','Barandal Branch','Barandal','example@email.com','Neil','9090909','0');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('3','Electronics');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('2','Liquid');
INSERT INTO `categories` (`category_id`,`category_name`) VALUES ('1','Solid');

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`inventory_id`),
  UNIQUE KEY `unique_inventory` (`product_id`,`branch_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('9','9','2','9','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('11','11','3','3','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('13','13','3','7','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('14','14','3','7','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('17','8','4','8','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('18','6','4','9','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('20','16','1','20','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('21','6','1','10','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('45','42','5','9','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('46','43','1','18','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('47','44','1','5','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('48','45','1','7','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('49','46','1','2','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('50','47','1','10','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('51','48','5','20','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('52','49','5','200','1');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('84','42','1','0','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('85','49','2','20','1');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('86','49','1','1','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('91','50','6','3','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('95','54','6','2','1');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('96','55','6','8','1');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('99','44','2','8','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('100','56','6','10','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('102','58','6','10','0');
INSERT INTO `inventory` (`inventory_id`,`product_id`,`branch_id`,`stock`,`archived`) VALUES ('103','59','4','3','0');

DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=194 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('6','1','Add Product','Added product:  (ID: )','2025-08-12 17:33:46',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('7','1','Archive Product','Archived product:  (ID: 50)','2025-08-12 17:35:25',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('8','1','Archive Product','Archived product:  (ID: 52)','2025-08-12 17:40:45',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('9','1','Archive Product','Archived product:  (ID: 51)','2025-08-12 17:40:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('10','1','Archive Product','Archived product:  (ID: 53)','2025-08-12 17:40:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('11','1','Add Product','Added product \'java chip\' (ID: 55) with stock 2 to branch ID 6','2025-08-12 17:41:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('12','1','Edit Product','Edited product ID : ','2025-08-12 17:45:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('13','1','Edit Product','Edited product ID 55: vat changed from \'12\' to \'121\'','2025-08-12 17:46:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('14','1','Archive Service','Archived service:  (ID: 6)','2025-08-12 17:51:08',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('15','1','Archive Product','Archived product:  (ID: 6)','2025-08-24 21:39:52',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('16','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:48:12',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('17','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:48:23',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('18','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:53:36',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('19','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 21:53:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('20','1','Archive Product','Archived product: Generator (ID: 9)','2025-08-24 21:58:05',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('21','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 22:00:06','0');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('22','1','Archive Product','Archived product: Jeep Customize Limited Edition (ID: 49)','2025-08-24 22:00:42','0');
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
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('62','1','Create Account','Created user: lablab ko, role: staff, branch: 3','2025-08-27 20:38:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('63','1','Add Product','Added product \'Ding dong\' (ID: 56) with stock 5 to branch ID 6','2025-08-28 02:06:19',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('64','1','Add Service','Added service \'Oil Change\' (ID: 15) to branch ID 1','2025-08-28 02:10:58',NULL);
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
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('101','1','Create Account','Created user: sertaposnapo, role: staff, branch: 4','2025-09-03 15:46:29',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('102','1','Create Account','Created user: Staff2, role: staff, branch: 4','2025-09-03 15:52:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('103','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:10:33','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('104','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:10:53',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('105','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:11:31',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('106','1','Add Product','Added product \'Iphone 17 Viva Max\' (ID: 58) with stock 10 to branch ID 6','2025-09-04 19:35:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('107','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:43:41','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('108','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 19:57:04','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('109','15','Login','User Stockman1 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:00:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('110','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:00:40','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('111','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:06:57',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('112','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:08:08','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('113','15','Login','User Stockman1 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:12:27','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('114','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0','2025-09-04 20:12:54',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('115','1','Create Account','Created user: barandal.stockman, role: stockman, branch: 6','2025-09-04 20:19:18',NULL);
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
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('142','1','Create Account','Created user: bucal.stockman, role: stockman, branch: 1','2025-09-05 22:47:51',NULL);
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
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('161','1','Add Service','Added service \'Oil Change\' (ID: 16) to branch ID 2','2025-09-05 23:15:39',NULL);
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
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('176','1','Add Service','Added service \'Oil Change\' (ID: 17) to branch ID 4','2025-09-06 00:10:16',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('177','1','Archive Service','Archived service: Oil Change (ID: 17)','2025-09-06 00:15:08','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('178','1','Add Service','Added service \'Computer Wheel Alignment\' (ID: 18) to branch ID 4','2025-09-06 00:16:32',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('179','1','Add Service','Added service \'223w\' (ID: 19) to branch ID 4','2025-09-06 00:16:43',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('180','1','Add Stock','Added 1 stock to Pulsar Mat (ID: 8)','2025-09-06 00:16:49','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('181','1','Add Stock','Added 1 stock to Pulsar Mat (ID: 8)','2025-09-06 00:17:11','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('182','1','Add Product','Added product \'80x80x80\' (ID: 59) with stock 2 to branch ID 4','2025-09-06 00:28:40',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('183','1','Add Service','Added service \'Oil Change\' (ID: 20) to branch ID 4','2025-09-06 00:30:33',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('184','1','Add Stock','Added 1 stock to 80x80x80 (ID: 59)','2025-09-06 00:33:16','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('185','1','Add Service','Added service \'asd\' (ID: 21) to branch ID 4','2025-09-06 00:34:30',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('186','1','Archive Service','Archived service: 223w (ID: 19)','2025-09-06 00:34:37','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('187','1','Archive Service','Archived service: Oil Change (ID: 20)','2025-09-06 00:34:41','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('188','1','Archive Service','Archived service: asd (ID: 21)','2025-09-06 00:34:44','4');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('189','1','Edit Product','Edited product ID 59: category changed from \'\' to \'Solid\'; markup_price changed from \'10\' to \'20\'; retail_price changed from \'\' to \'220\'; vat changed from \'\' to \'12\'','2025-09-06 01:00:34',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('190','1','Edit Product','Edited product ID 44: retail_price changed from \'\' to \'385\'; vat changed from \'\' to \'23\'','2025-09-06 01:08:55',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('191','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-06 01:50:24','1');
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('192','1','Login','User admin123 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-06 02:25:35',NULL);
INSERT INTO `logs` (`log_id`,`user_id`,`action`,`details`,`timestamp`,`branch_id`) VALUES ('193','3','Login','User staff001 logged in. IP: ::1, Browser: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 OPR/121.0.0.0','2025-09-06 02:25:47','1');

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pr_user` (`user_id`),
  KEY `fk_pr_reqby` (`requested_by`),
  CONSTRAINT `fk_pr_reqby` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `password_resets` (`id`,`user_id`,`requested_by`,`status`,`requested_at`,`decided_by`,`decided_at`) VALUES ('1','3','3','approved','2025-09-06 02:30:46','1','2025-09-06 02:31:06');
INSERT INTO `password_resets` (`id`,`user_id`,`requested_by`,`status`,`requested_at`,`decided_by`,`decided_at`) VALUES ('2','28','28','approved','2025-09-06 19:57:06','1','2025-09-06 19:57:15');
INSERT INTO `password_resets` (`id`,`user_id`,`requested_by`,`status`,`requested_at`,`decided_by`,`decided_at`) VALUES ('3','28','28','approved','2025-09-06 20:02:17','1','2025-09-06 20:02:24');
INSERT INTO `password_resets` (`id`,`user_id`,`requested_by`,`status`,`requested_at`,`decided_by`,`decided_at`) VALUES ('4','28','28','approved','2025-09-06 20:03:00','1','2025-09-06 20:03:09');
INSERT INTO `password_resets` (`id`,`user_id`,`requested_by`,`status`,`requested_at`,`decided_by`,`decided_at`) VALUES ('5','3','3','rejected','2025-09-06 21:24:24','1','2025-09-06 21:24:36');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(20,0) DEFAULT NULL,
  `markup_price` int(10) DEFAULT NULL,
  `retail_price` float DEFAULT NULL,
  `ceiling_point` int(11) DEFAULT NULL,
  `critical_point` int(11) DEFAULT NULL,
  `vat` float DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `archived` tinyint(10) NOT NULL DEFAULT 0,
  `brand_name` text DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('6','Laptop','Solid','22000','10',NULL,'20','10',NULL,NULL,'0','');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('8','Pulsar Mat','Solid','950','10',NULL,'10','5',NULL,NULL,'0','');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('9','Generator','Solid','27000','10',NULL,'10','5',NULL,NULL,'0','');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('11','Engine Oil','Liquid','700','10',NULL,'20','5',NULL,NULL,'0','');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('13','H931','Solid','850','12','2295','20','5','12',NULL,'0','');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('14','H927','Solid','1200','12','3000','20','5','12',NULL,'0','');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('16','Tire 70 X 80 X 14','Solid','1200','10',NULL,'20','5',NULL,NULL,'0','');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('42','Oil 3 in 1','Liquid','350','10',NULL,'10','5',NULL,NULL,'0','Goodyear');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('43','Sealant INF','Liquid','350','10',NULL,'20','5',NULL,NULL,'0','Flamingo');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('44','A/C Pro','Liquid','350','10','385','10','5','23',NULL,'0','Flamingo');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('45','M040','Liquid','220','10',NULL,'10','5',NULL,NULL,'0','Petron');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('46','ATF Premium','Liquid','220','10',NULL,'10','5',NULL,NULL,'0','Petron');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('47','Sprint 4T 1 liter','Liquid','200','10',NULL,'15','5',NULL,NULL,'0','Petron');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('48','oil 4 in 1','Liquid','5000','10',NULL,'20','5',NULL,NULL,'0','Michelin');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('49','Jeep Customize Limited Edition','Solid','300','12','336','20000','500','12',NULL,'0','Petron');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('50','Oil 5 in 1','Liquid','1000000','10',NULL,'5','2',NULL,NULL,'0','Petron');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('54','java chip','Solid','150','10',NULL,'10','5',NULL,NULL,'0','Bridgestone');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('55','java chip','Solid','150','10','165','10','5','121',NULL,'0','Bridgestone');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('56','Ding dong','Solid','20','10','22','10','3','123',NULL,'0','Petron');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('57',NULL,'Electronics',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL);
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('58','Iphone 17 Viva Max','Electronics','65000','10',NULL,'20','5',NULL,NULL,'0','Iphone');
INSERT INTO `products` (`product_id`,`product_name`,`category`,`price`,`markup_price`,`retail_price`,`ceiling_point`,`critical_point`,`vat`,`expiration_date`,`archived`,`brand_name`) VALUES ('59','80x80x80','Solid','200','20','220','10','5','12',NULL,'0','Goodyear');

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `sale_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `payment` decimal(10,2) NOT NULL,
  `change_given` decimal(10,2) NOT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Completed',
  PRIMARY KEY (`sale_id`),
  KEY `fk_sales_user` (`processed_by`),
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('1','1','2025-04-30 23:36:57','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('2','1','2025-04-30 23:40:07','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('3','1','2025-05-01 00:14:42','44000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('4','2','2025-05-01 01:05:40','27000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('5','1','2025-05-02 13:39:21','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('6','1','2025-05-02 14:32:57','220000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('7','1','2025-05-06 00:28:12','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('8','1','2025-05-06 00:28:27','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('9','1','2025-05-06 00:36:45','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('10','1','2025-05-26 23:27:03','102000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('11','1','2025-06-25 17:51:23','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('12','1','2025-06-25 17:52:19','950.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('13','1','2025-06-25 18:01:29','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('14','1','2025-06-25 19:37:52','85000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('15','1','2025-06-25 19:39:14','1000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('16','1','2025-06-25 19:41:13','14000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('17','1','2025-07-05 18:57:51','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('18','1','2025-07-05 19:06:52','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('19','1','2025-07-05 19:09:05','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('20','1','2025-07-05 19:12:07','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('21','1','2025-07-05 19:12:58','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('22','1','2025-07-05 19:14:23','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('23','1','2025-07-05 19:14:36','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('24','1','2025-07-05 19:17:05','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('25','1','2025-07-05 19:17:11','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('26','1','2025-07-05 19:17:24','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('27','1','2025-07-05 19:20:35','22000.00','0.00','0.00',NULL,'Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('28','1','2025-07-05 19:38:12','23250.00','23250.00','0.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('29','1','2025-07-05 19:38:36','23250.00','23250.00','0.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('30','1','2025-07-05 19:44:30','37250.00','37250.00','0.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('31','3','2025-07-05 19:46:35','870.00','900.00','30.00','9','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('32','1','2025-07-30 17:39:10','720.00','1000.00','280.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('33','1','2025-07-30 17:39:54','460.00','500.00','40.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('34','1','2025-07-30 17:56:55','22010.00','23000.00','990.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('35','1','2025-07-31 17:05:44','44020.00','55000.00','10980.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('36','1','2025-08-04 20:17:25','1150.00','2000.00','850.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('37','1','2025-08-05 15:55:38','400.00','400.00','0.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('38','5','2025-08-05 19:16:56','600400.00','1000000.00','399600.00','20','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('39','6','2025-08-12 00:49:22','500.00','600.00','100.00','21','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('40','1','2025-08-13 10:41:19','300.00','300.00','0.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('41','1','2025-08-13 10:46:57','1200.00','2000.00','800.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('42','1','2025-08-13 10:47:56','300.00','300.00','0.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('43','1','2025-08-22 16:50:55','1120.00','2000.00','880.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('44','1','2025-08-27 14:09:29','230.00','250.00','20.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('45','1','2025-08-27 14:11:36','230.00','250.00','20.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('46','1','2025-08-27 14:14:52','242.00','250.00','8.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('47','1','2025-08-27 14:22:06','242.00','252.00','10.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('48','1','2025-08-27 14:22:23','0.00','252.00','252.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('49','1','2025-08-27 14:22:35','0.00','252.00','252.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('50','1','2025-08-27 14:22:43','242.00','250.00','8.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('51','1','2025-08-27 14:24:02','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('52','1','2025-08-27 14:24:18','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('53','1','2025-08-27 14:24:32','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('54','1','2025-08-27 14:25:24','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('55','1','2025-08-27 14:25:25','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('56','1','2025-08-27 14:25:37','242.00','250.00','8.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('57','1','2025-08-27 14:25:47','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('58','1','2025-08-27 14:26:33','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('59','1','2025-08-27 14:26:50','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('60','1','2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('61','1','2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('62','1','2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('63','1','2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('64','1','2025-08-27 14:26:51','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('65','1','2025-08-27 14:26:57','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('66','1','2025-08-27 14:26:57','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('67','1','2025-08-27 14:27:46','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('68','1','2025-08-27 14:28:48','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('69','1','2025-08-27 14:28:49','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('70','1','2025-08-27 14:29:17','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('71','1','2025-08-27 14:33:31','385.00','390.00','5.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('72','1','2025-08-28 03:06:47','450.00','500.00','50.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('73','1','2025-09-04 20:24:03','25.00','50.00','25.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('74','1','2025-09-04 20:30:54','450.00','500.00','50.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('75','1','2025-09-04 20:31:11','242.00','250.00','8.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('76','1','2025-09-04 20:37:26','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('77','1','2025-09-04 20:38:02','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('78','1','2025-09-04 20:38:07','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('79','1','2025-09-04 20:38:07','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('80','1','2025-09-04 20:38:07','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('81','1','2025-09-04 20:38:08','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('82','1','2025-09-04 20:38:08','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('83','1','2025-09-04 20:38:08','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('84','1','2025-09-04 20:38:28','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('85','1','2025-09-04 20:39:36','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('86','1','2025-09-04 20:39:57','0.00','250.00','250.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('87','1','2025-09-04 20:49:37','385.00','400.00','15.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('88','1','2025-09-04 20:49:43','0.00','400.00','400.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('89','1','2025-09-04 20:49:46','0.00','400.00','400.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('90','1','2025-09-04 20:49:46','0.00','400.00','400.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('91','1','2025-09-04 20:52:02','242.00','250.00','8.00','3','Completed');
INSERT INTO `sales` (`sale_id`,`branch_id`,`sale_date`,`total`,`payment`,`change_given`,`processed_by`,`status`) VALUES ('93','1','2025-09-04 20:52:47','242.00','250.00','8.00','3','Refunded');

DROP TABLE IF EXISTS `sales_items`;
CREATE TABLE `sales_items` (
  `sales_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sales_item_id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('1','1','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('2','2','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('3','3','6','2','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('4','4','9','1','27000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('5','5','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('6','6','6','10','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('7','7','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('8','8','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('9','9','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('10','10','6','3','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('11','10','7','1','36000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('12','11','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('13','12','8','1','950.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('14','13','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('15','14','15','85','1000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('16','15','15','1','1000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('17','16','15','14','1000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('18','17','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('19','18','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('20','19','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('21','20','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('22','21','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('23','22','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('24','23','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('25','24','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('26','25','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('27','26','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('28','27','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('29','28','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('30','29','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('31','30','7','1','36000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('32','31','11','1','700.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('33','32','43','2','350.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('34','33','46','2','220.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('35','34','6','1','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('36','35','6','2','22000.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('37','36','46','5','220.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('38','38','49','2000','250.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('39','43','44','1','350.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('40','43','42','1','350.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('41','44','46','1','220.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('42','45','46','1','220.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('43','46','46','1','242.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('44','47','45','1','242.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('45','50','45','1','242.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('46','56','45','1','242.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('47','71','44','1','385.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('48','75','45','1','242.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('49','87','44','1','385.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('50','91','45','1','242.00');
INSERT INTO `sales_items` (`sales_item_id`,`sale_id`,`product_id`,`quantity`,`price`) VALUES ('51','93','45','1','242.00');

DROP TABLE IF EXISTS `sales_refunds`;
CREATE TABLE `sales_refunds` (
  `refund_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `refunded_by` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_reason` varchar(255) NOT NULL,
  `refund_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`refund_id`),
  KEY `fk_refund_sale` (`sale_id`),
  KEY `fk_refund_user` (`refunded_by`),
  CONSTRAINT `fk_refund_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_refund_user` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_reason`,`refund_date`) VALUES ('1','93','3','252.00','Damaged product','2025-09-06 21:06:19');
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_reason`,`refund_date`) VALUES ('2','93','3','252.00','Damaged product','2025-09-06 21:07:29');
INSERT INTO `sales_refunds` (`refund_id`,`sale_id`,`refunded_by`,`refund_amount`,`refund_reason`,`refund_date`) VALUES ('3','93','3','242.00','Wrong item delivered','2025-09-06 21:13:42');

DROP TABLE IF EXISTS `sales_services`;
CREATE TABLE `sales_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sales_services_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('1','37','1','400.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('2','38','1','400.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('4','40','2','300.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('5','41','3','1200.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('6','42','2','300.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('7','43','1','400.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('8','72','14','450.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('9','73','15','25.00');
INSERT INTO `sales_services` (`id`,`sale_id`,`service_id`,`price`) VALUES ('10','74','14','450.00');

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) DEFAULT NULL,
  `service_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `archived` tinyint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('9','1','Computer Wheel Alignment','4500.00','sample','0');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('14','1','Oil Change','450.00','sampple','1');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('15','1','Oil Change','25.00','sada','1');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('16','2','Oil Change','233.00','ayes','0');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('17','4','Oil Change','350.00','sample','1');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('18','4','Computer Wheel Alignment','23.00','23','0');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('19','4','223w','23.00','23','1');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('20','4','Oil Change','256.00','232323232asd','1');
INSERT INTO `services` (`service_id`,`branch_id`,`service_name`,`price`,`description`,`archived`) VALUES ('21','4','asd','232.00','asd','1');

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
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('1','6','4','1','2','15','approved','2025-07-27 13:48:01','2025-07-27 13:56:49','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('2','6','4','1','1','15','approved','2025-07-27 14:24:26','2025-07-27 14:24:38','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('3','42','5','1','1','15','approved','2025-07-31 01:15:23','2025-08-06 21:24:00','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('4','49','5','2','1','15','approved','2025-08-06 21:23:16','2025-08-06 21:27:06','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('5','49','2','5','1','15','approved','2025-08-06 21:40:55','2025-08-06 21:41:13','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('6','49','2','1','1','15','approved','2025-08-06 21:42:04','2025-08-06 21:42:21','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('7','44','1','2','1','15','approved','2025-08-22 16:46:17','2025-08-22 16:47:09','1','0');
INSERT INTO `transfer_requests` (`request_id`,`product_id`,`source_branch`,`destination_branch`,`quantity`,`requested_by`,`status`,`request_date`,`decision_date`,`decided_by`,`archived`) VALUES ('8','56','6','5','5','15','rejected','2025-09-03 16:06:48','2025-09-07 00:10:48','1','0');

DROP TABLE IF EXISTS `transfers`;
CREATE TABLE `transfers` (
  `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `from_branch` int(11) NOT NULL,
  `to_branch` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `transferred_by` int(11) NOT NULL,
  `transfer_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `role` enum('admin','staff','stockman') NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('1','admin123','$2y$10$lH8K.50M1DAo77DzfOWTGubEXWvD9uejj.TwHXQcFfbvDATV3DtOu','0','admin',NULL,'0');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('3','staff001','$2y$10$xxZ7gNInPuMAz.B9AcYq2Ot31HoizoWBikkFKSGo.q8SNlkyw5jk.','0','staff','1','0');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('9','staff003','$2y$10$4er4bKqGg2HNd9IAKuFYyOO2jPYM.tPT4IhwviLLJcZfcHJbNaNf2','0','staff','3','0');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('11','Staff004','$2y$10$fVt.3Km2TI1.8/r0t67J4uwVfZSjDHqPjpc6fqFhFF9kz/v8/VQhK','0','staff','4','0');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('15','Stockman1','$2y$10$Nn.ERKp7qUbyXT6x3iC4n.RKaMKi65oswsZDdtuH0IBFSj8wfXL32','0','stockman','1','0');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('19','staff002','$2y$10$FIjQ51vAFbIgtBlgnzaxWOjjjde9539jhGK1oBFUtKN66p00z50UW','0','staff','2','0');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('20','yrrabz112426','$2y$10$yntX5vvM8uVPE4WmiDVD9eX25c.dzdv9usXsI8DOQlqDBmO6jeSE2','0','staff','5','1');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('21','bars123','$2y$10$CDBlvkq4DkrwrrL2QZLm6OfBzFsr..wg6SFfrRIxKTyXNG/El442.','0','staff','6','1');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('22','cooljohnric24@gmail.com','$2y$10$kjdNpET/DWIzSvx4c9EpxOLJ.NP3dT1rulF0KtYnqgVjUGJlxilrq','0','admin',NULL,'1');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('24','lablab ko','$2y$10$I.uaoAXO.kNADfYsHchNm.gVSsfOHLmcRQne/L/31iZHclebkfz1i','0','staff','3','1');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('25','sertaposnapo','$2y$10$5aMr9lnohaAWPsoM90ueeOy0O8fcMK13NFD5vC9iDjh5xosDyVrDi','0','staff','4','1');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('26','Staff2','$2y$10$T/.ZRA5yUPNkpQvmPjZ9JOf9Td.nCEvvsV0BbuOmbqugT6qn1jZoe','0','staff','4','0');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('27','barandal.stockman','$2y$10$6cLqG10Uozz5eY2moa4uSurtdWgZOMH6AiAS1zTai.pAduo8rDsWS','0','stockman','6','1');
INSERT INTO `users` (`id`,`username`,`password`,`must_change_password`,`role`,`branch_id`,`archived`) VALUES ('28','bucal.stockman','$2y$10$zhG53lSYMVVTfHU/10MfZO1Ct8QvZZHFnyg/MORiCB20jynIruY9C','0','stockman','1','0');

SET FOREIGN_KEY_CHECKS=1;
