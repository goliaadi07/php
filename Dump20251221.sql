-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: homebuilder_app
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `agentregister`
--

DROP TABLE IF EXISTS `agentregister`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agentregister` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `phone_primary` varchar(30) DEFAULT NULL,
  `phone_alt` varchar(30) DEFAULT NULL,
  `preferred_contact_method` varchar(60) DEFAULT NULL,
  `license_number` varchar(80) DEFAULT NULL,
  `brokerage_name` varchar(180) DEFAULT NULL,
  `experience_level` varchar(60) DEFAULT NULL,
  `availability` varchar(60) DEFAULT NULL,
  `certifications` text,
  `primary_role` varchar(60) DEFAULT NULL,
  `service_areas` text,
  `property_types` text,
  `price_ranges` text,
  `biography` text,
  `brand_statement` text,
  `homes_closed_last_year` int DEFAULT NULL,
  `avg_days_to_close` int DEFAULT NULL,
  `languages` text,
  `website_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `agree_terms` tinyint(1) DEFAULT '0',
  `last_completed_step` tinyint unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `digital_signature` varchar(100) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `additional_photos` text,
  `status` varchar(50) DEFAULT 'Pending',
  `admin_comments` text,
  `tracking_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agentregister`
--

LOCK TABLES `agentregister` WRITE;
/*!40000 ALTER TABLE `agentregister` DISABLE KEYS */;
INSERT INTO `agentregister` VALUES (1,'Project','Jupiter','jupiterservices@gmail.com','07447448756','','','jnk','jn','','','','buyers_agent','','','','dsa','',NULL,NULL,'','','',1,5,'2025-12-09 20:17:09','2025-12-09 20:17:25',NULL,NULL,NULL,'Pending',NULL,NULL),(2,'Project','Jupiter','jupiterservices@gmail.com','07447448756','','','jnk','jn','','','','sellers_agent','','','','cas','',NULL,NULL,'','','',1,5,'2025-12-09 20:17:41','2025-12-09 20:17:59',NULL,NULL,NULL,'Pending',NULL,NULL),(3,'Ganesh','Iraganti','dreamhomebuilders00@gmail.com','07447448756','','','saddsa','asd','','','','sellers_agent','','','','cads','',NULL,NULL,'','','',1,5,'2025-12-10 18:59:08','2025-12-11 10:06:32','cda',NULL,NULL,'Pending',NULL,NULL),(4,'Ganesh','Iraganti','dreamhomebuilders00@gmail.com','07447448756','','','saddsa','asd','6-10 years','','','buyers_agent','','','','fjadsjkfnjkasnkf','',NULL,NULL,'','','',1,5,'2025-12-11 10:11:20','2025-12-11 10:11:43','ganehsh',NULL,NULL,'Pending',NULL,NULL),(5,'sumit','ande','sumit.ande.5@gmail.com','07447448756','','Phone','ca','cad','6-10 years','Limited availability','','full_service','Condos, $400k - $600k','Condos, $400k - $600k','Condos, $400k - $600k','sumit','',NULL,NULL,'Condos, $400k - $600k','','',1,5,'2025-12-11 10:12:13','2025-12-11 10:12:41','sumit',NULL,NULL,'Pending',NULL,NULL),(6,'aditya','goli','golia00@gmail.com','9881556859','6','Phone','kfjsajof','jfask','11-15 years','Available in 1-2 weeks','','buyers_agent','$200k - $400k','$200k - $400k','$200k - $400k','hi it agent register','',NULL,NULL,'$200k - $400k','','',1,5,'2025-12-14 10:31:14','2025-12-14 10:31:53','aditya',NULL,NULL,'Pending',NULL,NULL),(7,'aditya','goli','golia00@gmail.com','9881556859','','Email','kfjsajof','jfask','6-10 years','','','buyers_agent','','','','vdsa','',NULL,NULL,'','','',0,4,'2025-12-14 17:41:12','2025-12-14 17:41:32',NULL,NULL,NULL,'Pending',NULL,NULL),(8,'Project','Jupiter','jupiterservices@gmail.com','07447448756','07447448756','Any Method','123456','ganesh','3-5 years','Available within 1 month','builder co','buyers_agent','North Side, Condos, $400k - $600k','North Side, Condos, $400k - $600k','North Side, Condos, $400k - $600k','yes builders','builders',5,2,'North Side, Condos, $400k - $600k, English','www.nexux','www.oi.com',1,5,'2025-12-17 07:09:51','2025-12-17 07:18:55','nexus',NULL,NULL,'Pending',NULL,NULL),(9,'Project','Jupiter','jupiterservices@gmail.com','07447448756','','','123456','ganesh','','','','full_service','','','','fda','',NULL,NULL,'','','',1,5,'2025-12-17 07:19:59','2025-12-17 07:42:07','adf',NULL,NULL,'Pending',NULL,NULL),(10,'Project','Jupiter','jupiterservices@gmail.com','07447448756','07447448756','Email','123456','ganesh','','','','full_service','','','','nexus co','',5,5,'Spanish','','',1,5,'2025-12-17 07:50:08','2025-12-17 07:50:54','ganehsh','profile_1765957847_694260d7c4115.png','gallery_1765957847_0_694260d7c71d9.png','Pending',NULL,NULL),(11,'Project','Jupiter','jupiterservices@gmail.com','07447448756','07447448756','Email','123456','ganesh','0-2 years','Available in 1-2 weeks','saddasfsafsadf','custom_build','North Side, South Side, Condos, $400k - $600k','North Side, South Side, Condos, $400k - $600k','North Side, South Side, Condos, $400k - $600k','nexus home build','cas',4,1,'North Side, South Side, Condos, $400k - $600k, French','www.nexux','www.oi.com',1,5,'2025-12-17 07:54:15','2025-12-17 08:06:13','ganesh','profile_1765958763_6942646bc7874.png','gallery_1765958763_0_6942646bc8364.png','Pending',NULL,NULL),(12,'Project','Jupiter','jupiterservices@gmail.com','0744744875','0744744875','Phone','123456','ganesh','6-10 years','Available within 1 month','yes','custom_build','Downtown, Single Family Homes, $200k - $400k, $400k - $600k','Downtown, Single Family Homes, $200k - $400k, $400k - $600k','Downtown, Single Family Homes, $200k - $400k, $400k - $600k','Personal Brand Statement','',2025,55,'Downtown, Single Family Homes, $200k - $400k, $400k - $600k, English','www.nexux.com','www.oi.com',1,5,'2025-12-17 15:50:36','2025-12-17 15:53:12','ganesh','profile_1765986781_6942d1ddef30c.webp','gallery_1765986781_0_6942d1ddf13ab.webp','Pending',NULL,NULL),(13,'Aditya','Goli','aadgo07@gmail.com','9933283738','','Any Method','54114564','nexus','3-5 years','Taking new clients now','jnwr','custom_build','Downtown, Condos, $400k - $600k','Downtown, Condos, $400k - $600k','Downtown, Condos, $400k - $600k','uiwferjogiowrjgoiwrjogijwrog','uihhgwrjoigjrw',2025,52,'Downtown, Condos, $400k - $600k, English','www.nexus.co','',1,5,'2025-12-18 10:59:44','2025-12-18 15:44:34','aditya','profile_1766072656_694421509ec38.png','gallery_1766072656_0_69442150a1527.png','Pending',NULL,NULL),(14,'Project','Jupiter','jupiterservices@gmail.com','0744744875','','Email','123456','ganesh','','Available in 1-2 weeks','','custom_build','Downtown, North Side, New Construction, Over $2M','Downtown, North Side, New Construction, Over $2M','Downtown, North Side, New Construction, Over $2M','Your contact information for potential clients','',NULL,NULL,'Downtown, North Side, New Construction, Over $2M','','',1,5,'2025-12-20 12:10:51','2025-12-20 12:15:23','ganehsh',NULL,NULL,'Pending',NULL,NULL),(15,'Project','Jupiter','jupiterservices@gmail.com','0744744875','07447448756','Phone','123456','ganesh','11-15 years','Taking new clients now','adsasdc','custom_build','North Side','North Side','North Side','vsd','',2025,25,'North Side, English','','',1,5,'2025-12-20 12:21:28','2025-12-20 12:22:33','ganesh','profile_1766233340_694694fcefeeb.webp','gallery_1766233340_0_694694fcf2ffc.webp','Pending',NULL,'AGT-71EF3537'),(16,'aditya','goli','aditya2907@gmail.com','9881667748','9881665579','Text','13456','nexus','3-5 years','Available in 1-2 weeks','oracle, supervisior','full_service','North Side, Condos, $200k - $400k','North Side, Condos, $200k - $400k','North Side, Condos, $200k - $400k','Tell clients about your background and achievements','Tell clients about your background and achievements',20,20,'North Side, Condos, $200k - $400k, English','www.nexus.in','www.likndin.com',1,5,'2025-12-21 08:29:48','2025-12-21 08:31:18','aditya','profile_1766305868_6947b04c2d8d8.jpg','gallery_1766305868_0_6947b04c2e27b.jpg','Pending',NULL,'AGT-CE949E73');
/*!40000 ALTER TABLE `agentregister` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `professional_registrations`
--

DROP TABLE IF EXISTS `professional_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `professional_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_completed_step` int DEFAULT '0',
  `professional_type` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `experience_years` varchar(50) DEFAULT NULL,
  `service_areas` text,
  `specialties` text,
  `certifications` text,
  `languages` text,
  `bio` text,
  `preferred_contact_method` varchar(50) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `electronic_signature` varchar(150) DEFAULT NULL,
  `agreed_terms` tinyint(1) DEFAULT '0',
  `status` varchar(50) DEFAULT 'Pending',
  `admin_comments` text,
  `work_experience` text,
  `education` text,
  `awards` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `professional_registrations`
--

LOCK TABLES `professional_registrations` WRITE;
/*!40000 ALTER TABLE `professional_registrations` DISABLE KEYS */;
INSERT INTO `professional_registrations` VALUES (1,'PRO-544613','2025-12-10 10:00:37',5,'Property Manager','Project','Jupiter','jupiterservices@gmail.com','07447448756','bfd','gfs','3-5 years','vfs','vsfv','','','vfsbgivsfjigosgjoifsjgokjsfklgmlksfglksfmnlkgbvfslk','Email','','','fads',1,'Pending',NULL,NULL,NULL,NULL),(2,'PRO-34A9FA','2025-12-10 10:02:59',5,'Broker','Project','Jupiter','jupiterservices@gmail.com','07447448756','fds','fda','5-10 years','fda','fad','','','fjaslkkaslknaldsnflsdkngskdngldsnglksdnlgnlskdnglksdngklndslkgnkldsgf','Phone','','','fd',1,'Pending',NULL,NULL,NULL,NULL),(3,'PRO-4D3495','2025-12-11 10:59:16',5,'Broker','Project','Jupiter','jupiterservices@gmail.com','07447448756','fds','fda','0-2 years','fda','vsfv','','','feadmllllllllllllllllllllllllllllllncsajknjsalkncknlkafckwqlncfklqwn','Email','','','dcvs',1,'Pending',NULL,NULL,NULL,NULL),(4,'PRO-DB9E81','2025-12-11 11:00:45',5,'Appraiser','Project','Jupiter','jupiterservices@gmail.com','07447448756','fds','fda','3-5 years','fda','vsfv','','','kf;camd vlkdasfl;,alf;dwfmka;dlfwmkwas;l,fkamslfcasmkfl;mvaed','Text','','','sca',1,'Pending',NULL,NULL,NULL,NULL),(5,'PRO-449616','2025-12-11 11:01:56',5,'Appraiser','Project','Jupiter','jupiterservices@gmail.com','07447448756','fds','fda','5-10 years','fda','vsfv','','','maslcdkmdlsvcmal amvkadmvlasmcsklMAVLKASMLMSAKFCN SAKSVJKAV','Text','','','sca',1,'Pending',NULL,NULL,NULL,NULL),(6,'PRO-A824AA','2025-12-11 11:08:58',5,'Real Estate Agent','Ganesh','Iraganti','dreamhomebuilders00@gmail.com','07447448756','fds','fda','5-10 years','fda','vsfv','','','Minimum 50 characters. This will be displayed on your profile.','Email','','','fd',1,'Pending',NULL,NULL,NULL,NULL),(7,'PRO-29A687','2025-12-11 12:47:14',5,'Real Estate Agent','ganu','Iraganti','dreamhomebuilders00@gmail.com','07447448756','fds','fda','3-5 years','fda','vsfv','','','Minimum 50 characters. This will be displayed on your profile.','Text','','','fd',1,'Pending',NULL,NULL,NULL,NULL),(8,'PRO-ADE64F','2025-12-11 13:02:50',5,'Banker','ganu','Iraganti','dreamhomebuilders00@gmail.com','07447448756','fds','fda','6-10 years','fda','vsfv','','','Minimum 50 characters. This will be displayed on your profile.','Phone','','','fd',1,'Pending',NULL,NULL,NULL,NULL),(9,'PRO-40D247','2025-12-11 13:04:20',5,'loan officer','Project','Jupiter','jupiterservices@gmail.com','07447448756','fds','fda','3-5 years','fda','vsfv','','','Minimum 50 characters. This will be displayed on your profile.','Text','','','fd',1,'Pending',NULL,NULL,NULL,NULL),(10,'PRO-588ED0','2025-12-14 17:45:25',5,'loan officer','aditya','goli','golia00@gmail.com','9885456555','cad','156','3-5 years','csad','cad','','','cadMinimum 50 characters. This will be displayed on your profile.','Both','','','ganesh',1,'Pending',NULL,NULL,NULL,NULL),(11,'PRO-D32EBE','2025-12-17 11:30:53',5,'Real Estate Agent','Ganesh','Iraganti','dreamhomebuilders00@gmail.com','07447448756','vdsvds','vsd','3-5 years','vds','vds','','','vdsMinimum 50 characters. This will be displayed on your profile.','Phone','','','fd',1,'Pending',NULL,NULL,NULL,NULL),(12,'PRO-31B394','2025-12-17 13:57:39',4,'loan officer','Ganesh','Iraganti','dreamhomebuilders00@gmail.com','07447448756','vdsvds','vsd','6-10 years','vds','vds','','','Minimum 50 characters. This will be displayed on your profile.','Phone','','',NULL,0,'Pending',NULL,NULL,NULL,NULL),(13,'PRO-3E22EA','2025-12-20 16:55:31',6,'loan officer','sumit','Jupiter','jupiterservices@gmail.com','0744744875','bfd','123456','6-10 years','cadcsa','ca','','','Minimum 50 characters. This will be displayed on your profile.','Phone','','','dcvs',1,'Pending',NULL,'Minimum 50 characters. This will be displayed on your profile.','Minimum 50 characters. This will be displayed on your profile.','');
/*!40000 ALTER TABLE `professional_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_requests`
--

DROP TABLE IF EXISTS `property_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tracking_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_method` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `property_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `min_price` decimal(15,2) DEFAULT NULL,
  `max_price` decimal(15,2) DEFAULT NULL,
  `min_bedrooms` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `min_bathrooms` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `min_sq_feet` int DEFAULT NULL,
  `features` text COLLATE utf8mb4_general_ci,
  `preferred_locations` text COLLATE utf8mb4_general_ci,
  `additional_reqs` text COLLATE utf8mb4_general_ci,
  `pre_approved` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `first_time_buyer` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `buy_timeline` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `digital_signature` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agreed_to_terms` tinyint(1) DEFAULT '1',
  `submission_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `admin_comments` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_requests`
--

LOCK TABLES `property_requests` WRITE;
/*!40000 ALTER TABLE `property_requests` DISABLE KEYS */;
INSERT INTO `property_requests` VALUES (1,'Project','Jupiter','jupiterservices@gmail.com','07447448756',NULL,'Email','Single Family',0.00,0.00,'2+','1+',0,'','fsa','','Yes','Yes','Select timeframe','Agreed via Checkbox',1,'2025-12-10 10:34:25','Pending',NULL),(2,'Project','Jupiter','jupiterservices@gmail.com','07447448756',NULL,'Email','Single Family',0.00,0.00,'2+','1+',0,'','fsa','','Select','Select','Select timeframe','Agreed via Checkbox',1,'2025-12-10 12:49:03','Pending',NULL),(3,'Project','Jupiter','jupiterservices@gmail.com','07447448756',NULL,'Email','Single Family Home',0.00,0.00,'2+','1+',0,'','fda','','Select','Select','Select timeframe','cda',1,'2025-12-10 18:35:39','Pending',NULL),(4,'Project','Jupiter','jupiterservices@gmail.com','07447448756',NULL,'Email','Single Family Home',0.00,0.00,'2+','1+',0,'','m','','Select','Select','Select timeframe','cda',1,'2025-12-10 18:36:32','Pending',NULL),(5,'aditya','aa','golia00@gmail.com','9885456555',NULL,'Phone','Single Family Home',300000.00,1000000.00,'2+','1+',0,'Garage','fda','','In progress','No','3-6 Months','aditya',1,'2025-12-14 14:01:54','Pending',NULL),(6,'Aditya','Goli','aadgo07@gmail.com','9881667749',NULL,'Email','Condominium',300000.00,1000000.00,'2+','1+',1511,'','solapur ','','In progress','Yes','3-6 Months','Aditya',1,'2025-12-18 10:46:18','Pending',NULL),(7,'Aditya','Goli','aadgo07@gmail.com','9881667749',NULL,'Phone Call','Townhouse',200000.00,500000.00,'2+','2+',0,'Garage (5 cars), Basement (2), Updated Kitchen (1), Backyard (5)','fad','','Select','Select','Select timeframe','Aditya',1,'2025-12-18 11:05:53','Pending',NULL),(8,'Aditya','Goli','aadgo07@gmail.com','9881667749',NULL,'Phone Call','Single Family Home',0.00,0.00,'2+','1+',0,'Garage (5 cars), Basement, Updated Kitchen, Backyard','jhiifvsd','','Yes, pre-approved','Yes','3-6 Months','Aditya',1,'2025-12-18 16:08:14','Pending',NULL),(9,'Project','Jupiter','jupiterservices@gmail.com','0744744875',NULL,'Text Message','Townhouse',300000.00,0.00,'2+','1+',0,'Garage, Basement, Updated Kitchen, Backyard','cvd','','Select','Yes','Immediately/ASAP','ganesh',1,'2025-12-20 12:55:43','Pending',NULL),(10,'Project','Jupiter','jupiterservices@gmail.com','0744744875','REQ-B9949989','Phone Call','Townhouse',300000.00,500000.00,'2+','1+',0,'Garage (5 cars)','665','','Not yet','Yes','Select timeframe','ganehsh',1,'2025-12-20 15:58:50','Pending',NULL),(11,'Ganesh','Iraganti','dreamhomebuilders00@gmail.com','07447448756','REQ-703A9A4F','Text Message','Townhouse',200000.00,500000.00,'2+','1+',0,'Garage (2 cars)','fadca','','In progress','Yes','Immediately/ASAP','aditya',1,'2025-12-20 15:59:41','Pending',NULL),(12,'Ganesh','Iraganti','dreamhomebuilders00@gmail.com','07447448756','REQ-936556F8','Email','Townhouse',200000.00,500000.00,'2+','3+',0,'Garage, Basement, Updated Kitchen, Backyard','saxdsac','','In progress','Yes','3-6 Months','ganesh',1,'2025-12-20 16:18:00','Pending',NULL);
/*!40000 ALTER TABLE `property_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sell_requests`
--

DROP TABLE IF EXISTS `sell_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sell_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_completed_step` int DEFAULT '0',
  `street_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `property_type` varchar(50) DEFAULT NULL,
  `bedrooms` int DEFAULT NULL,
  `bathrooms` decimal(3,1) DEFAULT NULL,
  `square_feet` int DEFAULT NULL,
  `year_built` int DEFAULT NULL,
  `lot_size` varchar(100) DEFAULT NULL,
  `has_garage` tinyint(1) DEFAULT '0',
  `has_pool` tinyint(1) DEFAULT '0',
  `has_basement` tinyint(1) DEFAULT '0',
  `is_recently_updated` tinyint(1) DEFAULT '0',
  `overall_condition` varchar(50) DEFAULT NULL,
  `needs_repairs` varchar(50) DEFAULT NULL,
  `is_listed` varchar(20) DEFAULT NULL,
  `current_price` varchar(50) DEFAULT NULL,
  `desired_price` varchar(50) DEFAULT NULL,
  `sell_timeframe` varchar(50) DEFAULT NULL,
  `sell_reason` varchar(255) DEFAULT NULL,
  `confirm_owner` tinyint(1) DEFAULT '0',
  `confirm_accuracy_step2` tinyint(1) DEFAULT '0',
  `listing_link` text,
  `additional_info` text,
  `has_uploaded_photos` tinyint(1) DEFAULT '0',
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `photo_path` text,
  `status` varchar(50) DEFAULT 'Pending',
  `admin_comments` text,
  `garage_count` int DEFAULT '0',
  `basement_area` int DEFAULT '0',
  `pool_count` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sell_requests`
--

LOCK TABLES `sell_requests` WRITE;
/*!40000 ALTER TABLE `sell_requests` DISABLE KEYS */;
INSERT INTO `sell_requests` VALUES (1,'PROP-AD1BA3','2025-12-10 07:54:18',4,'fesdf','vsd','vsd','vsd','Condo',54,2.0,NULL,NULL,'',0,0,0,0,'Excellent','None','No','','','1-3 Months','',0,0,'','',0,'Project Jupiter','07447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(2,'PROP-490844','2025-12-10 08:14:12',4,'fesdf','vsd','vsd','vsd','Condo',54,2.0,NULL,NULL,'',0,0,0,0,'Excellent','None','No','','','3-6 Months','',0,0,'','',0,'Project Jupiter','07447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(3,'PROP-F2E0C2','2025-12-10 09:03:59',3,'fesdf','vsd','vsd','vsd','Condo',5,NULL,NULL,NULL,'',1,0,0,0,NULL,NULL,NULL,NULL,'','3-6 Months',NULL,0,0,'','',0,'Project Jupiter','07447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(4,'PROP-969127','2025-12-10 09:06:01',2,'fesdf','','vsd','vsd','Single Family',5,3.0,NULL,NULL,'',0,0,0,0,'','','No','','','1-3 Months','',1,1,NULL,NULL,0,'Project Jupiter','07447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(5,'PROP-0BA567','2025-12-10 09:18:40',3,'fesdf','vsd','vsd','vsd','Townhouse',4,2.0,NULL,NULL,'',0,0,0,0,'','','Na','','','1-3 Months','',0,0,'','',0,'Project Jupiter','+917447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(6,'PROP-B75324','2025-12-10 18:40:27',3,'fesdf','vsd','vsd','vsd','Multi-Family',4,2.0,NULL,NULL,'',0,0,0,0,'','','No','','','6+ Months','',0,0,'','',0,'Project Jupiter','+917447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(7,'PROP-946E9D','2025-12-10 18:52:57',3,'fesdf','vsd','vsd','vsd','Condo',4,2.0,NULL,NULL,'',0,0,0,0,'','','No','','','ASAP','',0,0,'','',0,'Project Jupiter','+917447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(8,'PROP-04BC2D','2025-12-10 18:55:28',2,'fesdf','vsd','vsd','vsd','Condo',4,2.0,NULL,NULL,'',0,0,0,0,'','','Yes','','','1-3 Months','',0,0,NULL,NULL,0,'Ganesh Iraganti','+917447448756','dreamhomebuilders00@gmail.com',NULL,'Pending',NULL,0,0,0),(9,'PROP-939AC0','2025-12-14 11:44:25',3,'fd','fda','fda','26616','Condo',5,1.0,NULL,NULL,'',1,0,1,0,'Good','','Yes','','','3-6 Months','',0,0,'','',1,'aditya','9881224246','adityag@gmail.com',NULL,'Pending',NULL,0,0,0),(10,'PROP-19804D','2025-12-14 11:47:29',3,'aa','fda','sgd','65','Single Family',3,1.0,NULL,NULL,'',0,0,0,0,'','','Yes','','','1-3 Months','',0,0,'','',0,'aditya','GOLI','goliadi@gmail.com',NULL,'Pending',NULL,0,0,0),(11,'PROP-F356A4','2025-12-14 13:59:27',3,'solapur','solapur','maha','4153522','Condo',5,2.0,NULL,NULL,'',0,0,0,0,'','','previous_expired','','','6+ Months','',0,0,'','',0,'aditya','9881667798','aditya@gmail.com',NULL,'Pending',NULL,0,0,0),(12,'PROP-7257CD','2025-12-14 17:30:47',3,'ds','dds','ds','ds','Townhouse',3,NULL,NULL,NULL,'',0,0,0,0,'','','No','','','3-6 Months','',0,0,'','',0,'fdas','5895646','aditya@gmail.com',NULL,'Pending',NULL,0,0,0),(13,'PROP-EEFE51','2025-12-17 16:03:42',3,'fesdf','vsd','vsd','413006','Townhouse',5,2.0,NULL,NULL,'',0,0,0,0,'','','Yes','','','1-3 Months','',0,0,'','',0,'Project Jupiter','+917447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(14,'PROP-368F4D','2025-12-18 10:16:03',3,'710-A Ashok Chowk Solapur','Solapur','Maharashtra','413006','Townhouse',4,4.0,NULL,NULL,'',1,0,1,0,'','','Yes','','','1-3 Months','',0,0,'','',1,'Aditya Goli','9881667749','aditya@gmail.com',NULL,'Pending',NULL,0,0,0),(15,'PROP-A66C89','2025-12-18 10:26:02',3,'710-A Ashok Chowk Solapur','Solapur','Maharashtra','413006','Townhouse',5,2.0,NULL,NULL,'',0,0,0,0,'','','previous_expired','','','1-3 Months','',0,0,'','',1,'Aditya Goli','5846','adita@gmail.com','PROP_1766053987_6943d8635030b.png','Pending',NULL,0,0,0),(16,'PROP-24CD8B','2025-12-18 15:55:46',3,'india','Solapur','Maharashtra','413006','Condo',5,2.0,NULL,NULL,'',1,1,1,1,'','','No','','','1-3 Months','',0,0,'','',1,'Aditya Goli','9881667749','aditya@gmail.com','PROP_1766073677_6944254d2aae0.png','Rejected','',0,0,0),(17,'PROP-C051DF','2025-12-18 16:03:24',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,0,'Aditya Goli','9881667749','adityagoli@gmail.com',NULL,'Pending',NULL,0,0,0),(18,'PROP-4C7AC6','2025-12-20 17:25:40',3,'fesdf','vsd','vsd','413355','Single Family',5,2.0,2025,2025,'',1,0,0,0,'','','No','','','ASAP','',0,0,'','',0,'Project Jupiter','+917447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,0,0,0),(19,'PROP-73E8C9','2025-12-20 17:32:07',3,'fesdf','vsd','vsd','563','Condo',5,2.0,NULL,NULL,'',1,1,1,0,'Excellent','minor_repair','No','','','3-6 Months','',0,0,'','',0,'Ganesh Iraganti','+917447448756','dreamhomebuilders00@gmail.com',NULL,'Pending',NULL,2,2,2),(20,'PROP-F13399','2025-12-20 17:51:59',3,'fesdf','vsd','vsd','563','Townhouse',5,2.0,NULL,NULL,'',0,0,0,0,'','','Yes','','','ASAP','Relocation',0,0,'','',0,'Ganesh Iraganti','+917447448756','dreamhomebuilders00@gmail.com',NULL,'Pending',NULL,0,0,0),(21,'PROP-180E81','2025-12-20 17:58:25',3,'fesdf','vsd','vsd','vsd','Townhouse',5,5.0,NULL,NULL,'',1,0,1,1,'Good','minor_repair','Yes','','','1-3 Months','Relocation',0,0,'','',1,'Ganesh Iraganti','+917447448756','dreamhomebuilders00@gmail.com','PROP_1766253588_6946e41409136.webp','Pending',NULL,2,2222,0),(22,'PROP-CAB3B2','2025-12-20 18:04:12',3,'fesdf','vsd','vsd','vsd','Condo',5,5.0,NULL,NULL,'',1,0,1,0,'','','Yes','','','ASAP','',0,0,'','',0,'sumit ande','+917447448756','sumit.ande.5@gmail.com',NULL,'Pending',NULL,2,2222,0),(23,'PROP-5DAAF7','2025-12-20 18:08:21',3,'fesdf','vsd','vsd','vsd','Townhouse',5,5.0,NULL,NULL,'',1,0,1,0,'','','No','','','3-6 Months','',0,0,'','',0,'sumit ande','+917447448756','sumit.ande.5@gmail.com',NULL,'Pending',NULL,2,2222,0),(24,'PROP-4EF785','2025-12-20 19:02:44',3,'fesdf','vsd','vsd','vsd','Villa',5,5.0,NULL,NULL,'',1,0,0,0,'','','No','','','1-3 Months','',0,0,'','',0,'Project Jupiter','+917447448756','jupiterservices@gmail.com',NULL,'Pending',NULL,2,0,0),(25,'PROP-28C45D','2025-12-21 08:53:06',3,'india','india','maha','413550','Villa',6,2.0,2000,2022,'2.3',1,0,1,0,'Fair','minor_repair','Yes','500000','600000','ASAP','Downsizing',0,0,'www.nexus','Provide a link to your property on Zillow, Realtor.com, or other listing sites',1,'aditya','9881668856','ganesh@gmail.com','PROP_1766307250_6947b5b2dc0ea.jpg','Approved','add proper details',2,500,0),(26,'PROP-B7AEAF','2025-12-21 11:27:39',3,'70 foot road','solapur','maha','413660','Land',5,2.0,2002,2022,'2.3',1,0,0,0,'Good','No','No','5000000','6000000','ASAP','Upsizing',0,0,'www.ganesh.in','Provide a link to your property on Zillow, Realtor.com, or other listing sites',1,'ganesh','7447448726','ganeshira@gmail.com','PROP_1766316545_6947da012bbc7.webp','Approved','',2,0,0);
/*!40000 ALTER TABLE `sell_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(120) DEFAULT NULL,
  `last_name` varchar(120) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `preferred_contact_method` varchar(60) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `own_land` enum('yes','no') DEFAULT NULL,
  `preferred_location` varchar(300) DEFAULT NULL,
  `plot_size` varchar(100) DEFAULT NULL,
  `zoning` varchar(150) DEFAULT NULL,
  `utilities` enum('yes','no') DEFAULT NULL,
  `home_type` varchar(120) DEFAULT NULL,
  `floors` varchar(50) DEFAULT NULL,
  `bedrooms` smallint unsigned DEFAULT NULL,
  `bathrooms` smallint unsigned DEFAULT NULL,
  `garage` varchar(80) DEFAULT NULL,
  `estimated_budget` varchar(120) DEFAULT NULL,
  `preferred_start_date` date DEFAULT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `design_style` varchar(120) DEFAULT NULL,
  `materials_preference` varchar(120) DEFAULT NULL,
  `service_architectural` tinyint(1) DEFAULT '0',
  `service_interior` tinyint(1) DEFAULT '0',
  `service_landscape` tinyint(1) DEFAULT '0',
  `service_permit` tinyint(1) DEFAULT '0',
  `service_loan` tinyint(1) DEFAULT '0',
  `file_land_ownership` varchar(255) DEFAULT NULL,
  `file_site_photos` varchar(255) DEFAULT NULL,
  `file_reference_design` varchar(255) DEFAULT NULL,
  `additional_notes` text,
  `confirm_accuracy` tinyint(1) DEFAULT '0',
  `agree_terms` tinyint(1) DEFAULT '0',
  `owner_token` varchar(64) DEFAULT NULL,
  `tracking_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `last_completed_step` tinyint unsigned DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `digital_signature` varchar(100) DEFAULT NULL,
  `land_address` varchar(255) DEFAULT NULL,
  `garage_spaces` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `admin_comments` text,
  PRIMARY KEY (`id`),
  KEY `idx_owner_token` (`owner_token`),
  KEY `idx_submission_code` (`tracking_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
INSERT INTO `submissions` VALUES (1,'Ganesh','Iraganti','7447448756','Email','dreamhomebuilders00@gmail.com','yes','','','','yes','','',5,2,'','',NULL,NULL,'','Standard',0,1,0,0,0,NULL,NULL,NULL,'',1,1,'abd5705e0ebaffc6350ddcf104379de45e1d25613ddf9469ccee6fb532a99bab','HB-03B11F',4,'2025-12-08 09:32:34','2025-12-20 19:09:21',NULL,NULL,NULL,'Approved',NULL),(2,'Project','Jupiter','7447448752','Email','jupiterervices@gmail.com','no','','','','yes','Townhouse','',6,2,'','',NULL,NULL,'','Premium',0,0,1,0,0,NULL,NULL,NULL,'',0,0,'abd5705e0ebaffc6350ddcf104379de45e1d25613ddf9469ccee6fb532a99bab','HB-166264',4,'2025-12-08 09:34:52','2025-12-08 09:35:39',NULL,NULL,NULL,'Pending',NULL),(3,'Ganesh','Iraganti','7447448756','WhatsApp','dreamhomebuilders00@gmail.com','yes','','','','yes',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,'abd5705e0ebaffc6350ddcf104379de45e1d25613ddf9469ccee6fb532a99bab','HB-05AC72',2,'2025-12-08 09:38:47','2025-12-08 09:41:46',NULL,NULL,NULL,'Pending',NULL),(4,'Ganesh','Iraganti','7447448756','Phone','dreamhomebuilders00@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,'abd5705e0ebaffc6350ddcf104379de45e1d25613ddf9469ccee6fb532a99bab','HB-EAE4B8',1,'2025-12-08 09:46:08','2025-12-08 09:46:08',NULL,NULL,NULL,'Pending',NULL),(5,'Ganesh','Iraganti','7447448756','','dreamhomebuilders00@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,'abd5705e0ebaffc6350ddcf104379de45e1d25613ddf9469ccee6fb532a99bab','HB-5973D6',1,'2025-12-08 16:59:30','2025-12-08 16:59:30',NULL,NULL,NULL,'Pending',NULL),(6,'Gane','Iraganti','7447448756','','dreamhomebuilders00@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,'abd5705e0ebaffc6350ddcf104379de45e1d25613ddf9469ccee6fb532a99bab','HB-3290E7',1,'2025-12-09 10:09:36','2025-12-09 10:09:36',NULL,NULL,NULL,'Pending',NULL),(7,'Gane','Iraganti','7447448756','','dreamhomebuilders00@gmail.com','yes','','','','yes','','',4,2,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',0,0,'abd5705e0ebaffc6350ddcf104379de45e1d25613ddf9469ccee6fb532a99bab','HB-271564',4,'2025-12-09 10:35:43','2025-12-09 10:36:04',NULL,NULL,NULL,'Pending',NULL),(8,'Gane','Iraganti','7447448756','','dreamhomebuilders00@gmail.com','yes','','','','yes','Townhouse','',4,1,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-3BF4E6949F',4,'2025-12-09 11:13:17','2025-12-09 11:13:41',NULL,NULL,NULL,'Pending',NULL),(9,'Gane','Iraganti','7447448756','','dreamhomebuilders00@gmail.com',NULL,'','','',NULL,'','',54,11,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',0,0,NULL,'DH-CB0B9D77A0',4,'2025-12-09 11:13:58','2025-12-09 11:14:12',NULL,NULL,NULL,'Pending',NULL),(10,'Gane','Iraganti','7447448756','','dreamhomebuilders00@gmail.com',NULL,'','','',NULL,'','',54,11,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-D75B673CDE',3,'2025-12-09 11:15:20','2025-12-09 11:22:33',NULL,NULL,NULL,'Pending',NULL),(11,'Project','Jupiter','0744744875','','jupiterservices@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-90FC622F15',1,'2025-12-09 11:31:07','2025-12-09 11:31:07',NULL,NULL,NULL,'Pending',NULL),(12,'Project','Jupiter','0744744875','','jupiterservices@gmail.com',NULL,'','','',NULL,'','',55,10,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-4DA5A1F0CE',3,'2025-12-09 11:31:37','2025-12-09 11:31:49',NULL,NULL,NULL,'Pending',NULL),(13,'Project','Jupiter','0744744875','','jupiterservices@gmail.com','yes','','','','yes','','',5,1,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-AE2B723CB7',4,'2025-12-09 11:46:30','2025-12-09 11:46:53',NULL,NULL,NULL,'Pending',NULL),(14,'Aditya','Goli','0744744875','Phone','jupiterservices@gmail.com','yes','da','fad','gsd','yes','','',5,1,'','',NULL,NULL,'','',1,0,0,0,1,NULL,NULL,NULL,'',1,1,NULL,'DH-987B84452F',4,'2025-12-09 17:13:32','2025-12-09 17:14:01',NULL,NULL,NULL,'Pending',NULL),(15,'Aditya','Goli','0744744875','','jupiterservices@gmail.com','yes','','','','yes','','',4,6,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',0,0,NULL,'DH-05E422C6A8',4,'2025-12-09 17:14:16','2025-12-09 17:16:12',NULL,NULL,NULL,'Pending',NULL),(16,'Aditya','Goli','0744744875','Email','jupiterservices@gmail.com',NULL,'','','',NULL,'','',5,6,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',0,0,NULL,'DH-37FA398D87',4,'2025-12-09 17:29:29','2025-12-09 17:53:17',NULL,NULL,NULL,'Pending',NULL),(17,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','yes','','','','yes','','',5,1,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',0,0,NULL,'DH-36E61DD949',4,'2025-12-09 17:53:35','2025-12-09 17:53:49',NULL,NULL,NULL,'Pending',NULL),(18,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','yes','','','',NULL,'','',4,2,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-3DC9D7421A',4,'2025-12-09 18:00:43','2025-12-09 18:01:26',NULL,NULL,NULL,'Pending',NULL),(19,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','no','','','',NULL,'','',5,4,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-EC70F9C02C',4,'2025-12-09 18:02:41','2025-12-09 18:02:57',NULL,NULL,NULL,'Pending',NULL),(20,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','no','','','',NULL,'','',5,1,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-F248CF8A73',3,'2025-12-09 18:03:11','2025-12-09 18:03:19',NULL,NULL,NULL,'Pending',NULL),(21,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','no','','','',NULL,'','',6,1,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-DFAA0B9265',3,'2025-12-09 18:16:53','2025-12-09 18:17:03',NULL,NULL,NULL,'Pending',NULL),(22,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','no','','','',NULL,'','',8,5,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-39D489B87A',3,'2025-12-09 18:17:14','2025-12-09 18:17:23',NULL,NULL,NULL,'Pending',NULL),(23,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','no','','','',NULL,'','',5,5,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-BF77EE9FE1',3,'2025-12-09 18:17:36','2025-12-09 18:17:47',NULL,NULL,NULL,'Pending',NULL),(24,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','yes','','','',NULL,'','',4,2,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-8263B6E4BF',4,'2025-12-09 18:24:47','2025-12-09 18:25:14',NULL,NULL,NULL,'Pending',NULL),(25,'sumit','ande','0744744875','','sumit.ande.5@gmail.com','yes','','','',NULL,'','',5,2,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-B76540284C',4,'2025-12-10 07:26:31','2025-12-10 07:33:57',NULL,NULL,NULL,'Pending',NULL),(26,'sumit','ande','0744744875','Email','sumit.ande.5@gmail.com',NULL,'','','',NULL,'','',5,5,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-025D439A2B',4,'2025-12-10 17:14:30','2025-12-10 17:15:59',NULL,NULL,NULL,'Pending',NULL),(27,'Project','Jupiter','0744744875','','jupiterservices@gmail.com','yes','','','','yes','','',5,5,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-E24D2B8012',4,'2025-12-10 17:49:36','2025-12-10 17:49:56',NULL,NULL,NULL,'Pending',NULL),(28,'Project','Jupiter','0744744875','','jupiterservices@gmail.com','yes','','','',NULL,'','',8,1,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-FBD45A9E9A',4,'2025-12-11 13:35:23','2025-12-11 13:39:07',NULL,NULL,NULL,'Pending',NULL),(29,'aditya','aa','9885456555','Email','golia00@gmail.com','yes','','','','yes','','',5,1,'','',NULL,NULL,'','',1,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-12240AB4D0',4,'2025-12-14 11:46:37','2025-12-14 11:46:57',NULL,NULL,NULL,'Pending',NULL),(30,'aditya','aa','9885456555','Email','golia00@gmail.com','yes','','','',NULL,'','',4,3,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-652AF9C72B',4,'2025-12-14 17:24:30','2025-12-14 17:27:21',NULL,NULL,NULL,'Pending',NULL),(31,'Project','Jupiter','0744744875','','jupiterservices@gmail.com','yes','','','','yes','','',5,1,'','',NULL,NULL,'','',1,0,0,0,0,'land_1765960291_69426a636af66.jpg','site_1765960291_0_69426a636bf77.webp','ref_1765960291_0_69426a636c883.webp',NULL,1,1,NULL,'DH-8C9298FECF',4,'2025-12-17 08:09:44','2025-12-20 19:21:40','ganesh','afd',NULL,'Approved',NULL),(32,'Ganesh','Iraganti','0744744875','','dreamhomebuilders00@gmail.com','no','da','','','yes','','',7,4,'','',NULL,NULL,'','',1,0,0,0,0,NULL,NULL,NULL,NULL,1,1,NULL,'DH-0638932C49',4,'2025-12-17 08:33:32','2025-12-17 08:33:58','ganesh','',NULL,'Pending',NULL),(33,'Ganesh','Iraganti','0744744875','','dreamhomebuilders00@gmail.com','no','ca','','','yes','','',4,1,'','',NULL,NULL,'','',1,0,0,0,0,'land_1765961022_69426d3e639fb.jpg',NULL,NULL,NULL,1,1,NULL,'DH-9B99E4D28C',4,'2025-12-17 08:39:30','2025-12-17 08:43:42','ganesh','',NULL,'Pending',NULL),(34,'Ganesh','Iraganti','0744744875','Phone','dreamhomebuilders00@gmail.com','no','','','','no','','',5,1,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-544D4CD50D',4,'2025-12-17 08:45:55','2025-12-17 09:27:12',NULL,'das',NULL,'Pending',NULL),(35,'Ganesh','Iraganti','0744744875','WhatsApp','dreamhomebuilders00@gmail.com','yes','','','',NULL,'','',7,5,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-2811F7C484',4,'2025-12-17 09:28:43','2025-12-17 09:29:00',NULL,NULL,NULL,'Pending',NULL),(36,'Ganesh','Iraganti','0744744875','WhatsApp','dreamhomebuilders00@gmail.com','yes','','','','yes','Duplex','',54,55,'','',NULL,NULL,'','',1,0,0,0,0,'buildfolder/1765964159_OIP.webp','buildfolder/1765964159_OIP (1).webp','buildfolder/1765964159_bird-1850188_1280.jpg','',1,1,NULL,'DH-2C4D68AB87',4,'2025-12-17 09:35:13','2025-12-17 09:35:59','Project Jupiter','',NULL,'Pending',NULL),(37,'Ganesh','Iraganti','0744744875','','dreamhomebuilders00@gmail.com','no','','','',NULL,'','',5,65,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,NULL,0,0,NULL,'DH-15F7797512',3,'2025-12-17 09:36:53','2025-12-17 10:36:21',NULL,'',NULL,'Pending',NULL),(38,'Ganesh','Iraganti','0744744875','Email','dreamhomebuilders00@gmail.com','yes','','','','yes','','',5,1,'','',NULL,NULL,'','',1,0,0,1,0,NULL,NULL,NULL,NULL,1,1,NULL,'DH-F31B61BD70',4,'2025-12-17 10:37:44','2025-12-17 10:38:16','dca','India',NULL,'Pending',NULL),(39,'Ganesh','Iraganti','0744744875','Email','dreamhomebuilders00@gmail.com','yes','','fad','gsd','yes','Duplex','2 Floors',5,5,'Yes','',NULL,NULL,'','',0,0,0,0,0,'land_1765968338_694289d2ec5fb.jpg','site_1765968338_0_694289d2ed6f8.webp','ref_1765968338_0_694289d2edfc8.webp',NULL,1,1,NULL,'DH-7681D727DA',4,'2025-12-17 10:43:29','2025-12-17 10:45:38','ganesh','India',NULL,'Pending',NULL),(42,'Aditya','G','0744744875','Email','dreamhomebuilders00@gmail.com','yes','','200','yes','yes','Duplex','2 Floors',5,2,'Yes','Above ₹50L',NULL,NULL,'Contemporary','Wood',1,1,0,0,0,'land_1765970683_694292fb55a90.jpg',NULL,NULL,'builders co',1,1,NULL,'DH-6818EA8DFD',4,'2025-12-17 11:12:08','2025-12-20 19:09:15','nexus','India',25,'Approved',NULL),(44,'Aditya','Goli','9881667749','Phone','aadgo07@gmail.com','yes','','','','yes','Single-Family','1 Floor',54,2,'Yes','',NULL,NULL,'','Wood',1,0,0,0,0,'land_1766073064_694422e867ca3.png','site_1766073064_0_694422e869624.png',NULL,'iufdusf',1,1,NULL,'DH-7E227192C6',4,'2025-12-18 15:49:44','2025-12-20 19:02:30','Aditya','India',2,'Approved',NULL),(45,'Aditya','Goli','9881667749','Phone','goliaditya08@gmail.com','yes','','','',NULL,'','',4,4,'','',NULL,NULL,'','',0,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-B85DEE9DE8',4,'2025-12-18 15:51:17','2025-12-21 06:39:34','Aditya','India',NULL,'Rejected',NULL),(46,'Ganesh','Iraganti','0744744875','','dreamhomebuilders00@gmail.com','yes','','100','yes','yes','Villa','2 Floors',15,5,'Yes','',NULL,NULL,'','',1,0,0,0,0,NULL,NULL,NULL,'',1,1,NULL,'DH-3FEFE32C76',4,'2025-12-20 12:26:43','2025-12-21 06:39:09','ganehsh','India',5,'Rejected',NULL),(47,'Ganesh','Iraganti','0744744875','Email','dreamhomebuilders00@gmail.com','yes','','50000','YES','no','Single-Family','',5,1,'Yes','$300,000-$400,000','2025-12-26','2025-12-31','Contemporary','Stone',1,1,0,0,0,'land_1766234881_69469b012a25b.jpg','site_1766234881_0_69469b012b416.webp',NULL,'vm,dsj',1,1,NULL,'DH-F69F574F49',4,'2025-12-20 12:37:59','2025-12-21 06:35:24','kml','dca',2,'Rejected','all good'),(48,'aditya','goli','9885456555','Email','nexushomepro@gmail.com','yes','','2000','413006','yes','Single-Family','1 Floor',5,2,'Yes','Under $300,000','2025-12-16','2025-12-31','Contemporary','Mixed Materials',1,1,0,0,0,'land_1766299591_694797c7aa429.jpg','site_1766299591_0_694797c7ab99c.webp','ref_1766299591_0_694797c7ac0ed.webp','I confirm that the details provided are accurate to the best of my knowledge.',1,1,NULL,'DH-490C31F262',4,'2025-12-21 06:44:56','2025-12-21 06:55:46','Ganesh','india',2,'Approved','');
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-21 17:43:31
