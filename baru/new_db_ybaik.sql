-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for db_ybaik_new
CREATE DATABASE IF NOT EXISTS `db_ybaik_new` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `db_ybaik_new`;

-- Dumping structure for table db_ybaik_new.admin_students
CREATE TABLE IF NOT EXISTS `admin_students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `admin_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_students_student_id_admin_id_unique` (`student_id`,`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1065 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.agents
CREATE TABLE IF NOT EXISTS `agents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `users_id` bigint unsigned NOT NULL,
  `parent_agents_id` bigint unsigned DEFAULT NULL,
  `consultant_type` enum('consultant','senior_consultant','referral') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regions_id` bigint unsigned DEFAULT NULL,
  `subregions_id` bigint unsigned DEFAULT NULL,
  `countries_id` bigint unsigned DEFAULT NULL,
  `states_id` bigint unsigned DEFAULT NULL,
  `cities_id` bigint unsigned DEFAULT NULL,
  `alamat` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_agents_users1_idx` (`users_id`),
  KEY `fk_agents_regions1_idx` (`regions_id`),
  KEY `fk_agents_countries1_idx` (`countries_id`),
  KEY `fk_agents_states1_idx` (`states_id`),
  KEY `fk_agents_cities1_idx` (`cities_id`),
  KEY `fk_agents_subregions1_idx` (`subregions_id`),
  KEY `fk_agents_agents1_idx` (`parent_agents_id`),
  CONSTRAINT `fk_agents_agents1` FOREIGN KEY (`parent_agents_id`) REFERENCES `agents` (`id`),
  CONSTRAINT `fk_agents_cities1` FOREIGN KEY (`cities_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `fk_agents_countries1` FOREIGN KEY (`countries_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `fk_agents_regions1` FOREIGN KEY (`regions_id`) REFERENCES `regions` (`id`),
  CONSTRAINT `fk_agents_states1` FOREIGN KEY (`states_id`) REFERENCES `states` (`id`),
  CONSTRAINT `fk_agents_subregions1` FOREIGN KEY (`subregions_id`) REFERENCES `subregions` (`id`),
  CONSTRAINT `fk_agents_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.bank_accounts
CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_bank` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_rekening` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.chats
CREATE TABLE IF NOT EXISTS `chats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.chat_messages
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_chat_id_foreign` (`chat_id`),
  KEY `fk_chat_messages_users1_idx` (`user_id`),
  CONSTRAINT `chat_messages_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_messages_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.chat_users
CREATE TABLE IF NOT EXISTS `chat_users` (
  `user_id` bigint unsigned NOT NULL,
  `chat_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`chat_id`),
  KEY `chat_users_chat_id_foreign` (`chat_id`),
  KEY `fk_chat_users_users1_idx` (`user_id`),
  CONSTRAINT `chat_users_chat_id_foreign` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_users_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.cities
CREATE TABLE IF NOT EXISTS `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_id` bigint unsigned NOT NULL,
  `state_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned NOT NULL,
  `country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_test_ibfk_1` (`state_id`),
  KEY `cities_test_ibfk_2` (`country_id`),
  CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`),
  CONSTRAINT `cities_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157046 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.commissions
CREATE TABLE IF NOT EXISTS `commissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `total_amount` double NOT NULL,
  `status` enum('paid','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `tanggal_keberangkatan` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_commissions_users1_idx` (`user_id`),
  CONSTRAINT `fk_commissions_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.commission_details
CREATE TABLE IF NOT EXISTS `commission_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `commission_id` bigint unsigned NOT NULL,
  `recipient_type` enum('consultant','korwil','koordinator','student','school','referral','senior_consultant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` double NOT NULL,
  `status` enum('pending','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `is_approved` tinyint DEFAULT NULL,
  `level` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_commission_details_commissions1_idx` (`commission_id`),
  KEY `fk_commission_details_users1_idx` (`user_id`),
  CONSTRAINT `fk_commission_details_commissions1` FOREIGN KEY (`commission_id`) REFERENCES `commissions` (`id`),
  CONSTRAINT `fk_commission_details_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1403 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.companions
CREATE TABLE IF NOT EXISTS `companions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned DEFAULT NULL,
  `relation` enum('ayah','ibu','kakek','nenek','saudara','pasangan') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('Kebutuhan Data Visa','Ikut Perjalanan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_ktp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket_hotel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flight_ticket_pp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pas_foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_employed` tinyint DEFAULT NULL,
  `father_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_birth_date` date DEFAULT NULL,
  `father_birth_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kebangsaan_ayah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mother_birth_date` date DEFAULT NULL,
  `mother_birth_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kebangsaan_ibu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `major` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_institution` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_eduction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_menikah` enum('menikah','belum menikah','cerai') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surat_cerai` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_companions_students1_idx` (`student_id`),
  CONSTRAINT `fk_companions_students1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1061 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.companion_parent_company_backgrounds
CREATE TABLE IF NOT EXISTS `companion_parent_company_backgrounds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `companion_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supervisor_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_companion_parent_company_backgrounds_companions1_idx` (`companion_id`),
  CONSTRAINT `fk_companion_parent_company_backgrounds_companions1` FOREIGN KEY (`companion_id`) REFERENCES `companions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=772 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.companion_relations
CREATE TABLE IF NOT EXISTS `companion_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `relation` enum('ayah','ibu','kakek','nenek','saudara','pasangan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `companions_id` bigint unsigned NOT NULL,
  `companions_2_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_companion_relations_companions1_idx` (`companions_id`),
  KEY `fk_companion_relations_companions2_idx` (`companions_2_id`),
  CONSTRAINT `fk_companion_relations_companions1` FOREIGN KEY (`companions_id`) REFERENCES `companions` (`id`),
  CONSTRAINT `fk_companion_relations_companions2` FOREIGN KEY (`companions_2_id`) REFERENCES `companions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=521 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.companion_travel_historys
CREATE TABLE IF NOT EXISTS `companion_travel_historys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `date_of_entry` timestamp NULL DEFAULT NULL,
  `date_of_exit` timestamp NULL DEFAULT NULL,
  `visa_china` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `companion_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_companion_travel_historys_companions1_idx` (`companion_id`),
  CONSTRAINT `fk_companion_travel_historys_companions1` FOREIGN KEY (`companion_id`) REFERENCES `companions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.consultations
CREATE TABLE IF NOT EXISTS `consultations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `guest_id` bigint unsigned DEFAULT NULL,
  `referrer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admission_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` bigint DEFAULT NULL,
  `assigned_to` bigint DEFAULT NULL COMMENT 'Merujuk ke consultants',
  `preferred_datetime` timestamp NULL DEFAULT NULL,
  `status` enum('pending','scheduled','re-scheduled','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `consultation_type` enum('onsite','online') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meeting_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultations_guest_id_foreign` (`guest_id`),
  CONSTRAINT `consultations_guest_id_foreign` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.countries
CREATE TABLE IF NOT EXISTS `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso3` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numeric_code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso2` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phonecode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capital` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_symbol` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tld` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `native` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `subregion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subregion_id` bigint unsigned DEFAULT NULL,
  `nationality` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `translations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `emoji` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emojiU` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `country_continent` (`region_id`),
  KEY `country_subregion` (`subregion_id`),
  CONSTRAINT `country_continent_final` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`),
  CONSTRAINT `country_subregion_final` FOREIGN KEY (`subregion_id`) REFERENCES `subregions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=251 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.departure
CREATE TABLE IF NOT EXISTS `departure` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `student_program_id` bigint unsigned NOT NULL,
  `student_program_detail_id` bigint unsigned NOT NULL,
  `univ_program_id` bigint unsigned NOT NULL,
  `enrollment_scholarship_id` bigint unsigned DEFAULT NULL,
  `package_category` enum('1','2','3','4') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '1' COMMENT '1.Normal, 2.No paket, 3.Subsidi, 4.Beasiswa',
  `depart` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_departure_students1_idx` (`student_id`),
  KEY `fk_departure_enrollments1_idx` (`student_program_id`),
  KEY `fk_departure_enrollment_programs1_idx` (`student_program_detail_id`),
  KEY `fk_departure_univ_programs1_idx` (`univ_program_id`),
  KEY `fk_departure_enrollment_scholarships1_idx` (`enrollment_scholarship_id`),
  CONSTRAINT `fk_departure_enrollment_programs1` FOREIGN KEY (`student_program_detail_id`) REFERENCES `enrollment_programs` (`id`),
  CONSTRAINT `fk_departure_enrollment_scholarships1` FOREIGN KEY (`enrollment_scholarship_id`) REFERENCES `enrollment_scholarships` (`id`),
  CONSTRAINT `fk_departure_enrollments1` FOREIGN KEY (`student_program_id`) REFERENCES `enrollments` (`id`),
  CONSTRAINT `fk_departure_students1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fk_departure_univ_programs1` FOREIGN KEY (`univ_program_id`) REFERENCES `univ_programs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.employees
CREATE TABLE IF NOT EXISTS `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_number` bigint unsigned NOT NULL,
  `employee_id_number` char(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `place_of_birth` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date NOT NULL,
  `main_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alternate_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corporate_email` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` char(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `corporate_phone_number` char(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marriage_status` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_child` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_work_date` date NOT NULL,
  `position` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `work_status` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_card_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resign_at` date DEFAULT NULL,
  `resign_reason` mediumtext COLLATE utf8mb4_unicode_ci,
  `division_id` int NOT NULL,
  `emergency_contact_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_phone` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emergency_contact_relation` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `update_at` timestamp NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.employee_kinerjas
CREATE TABLE IF NOT EXISTS `employee_kinerjas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employees_id` bigint unsigned NOT NULL,
  `periode` date DEFAULT NULL,
  `nominal_tabungan` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_employee_kinerjas_employees1_idx` (`employees_id`),
  CONSTRAINT `fk_employee_kinerjas_employees1` FOREIGN KEY (`employees_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.employee_warnings
CREATE TABLE IF NOT EXISTS `employee_warnings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employees_id` bigint unsigned NOT NULL,
  `level` tinyint DEFAULT NULL,
  `year` year DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_employee_warnings_employees1_idx` (`employees_id`),
  CONSTRAINT `fk_employee_warnings_employees1` FOREIGN KEY (`employees_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.enrollments
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `students_id` bigint unsigned NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `university_id` bigint unsigned NOT NULL,
  `priorities_order` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'selecting',
  `uni_status` enum('rejected','not_selected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_programs_students1_idx` (`students_id`),
  KEY `fk_student_programs_universities1_idx` (`university_id`),
  CONSTRAINT `fk_student_programs_students1` FOREIGN KEY (`students_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fk_student_programs_universities1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1031 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.enrollment_examinations
CREATE TABLE IF NOT EXISTS `enrollment_examinations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_program_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `exam_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_examinations_student_program_id_foreign` (`student_program_id`),
  CONSTRAINT `enrollment_examinations_student_program_id_foreign` FOREIGN KEY (`student_program_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.enrollment_programs
CREATE TABLE IF NOT EXISTS `enrollment_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_program_id` bigint unsigned DEFAULT NULL,
  `program_id` bigint unsigned NOT NULL,
  `interest` smallint DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_enrollment_programs_enrollments1_idx` (`student_program_id`),
  KEY `fk_enrollment_programs_univ_programs1_idx` (`program_id`),
  CONSTRAINT `fk_enrollment_programs_enrollments1` FOREIGN KEY (`student_program_id`) REFERENCES `enrollments` (`id`),
  CONSTRAINT `fk_enrollment_programs_univ_programs1` FOREIGN KEY (`program_id`) REFERENCES `univ_programs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1592 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.enrollment_scholarships
CREATE TABLE IF NOT EXISTS `enrollment_scholarships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_program_id` bigint unsigned DEFAULT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal_tuition_fee` decimal(12,2) DEFAULT NULL,
  `nominal_accomodation` decimal(12,2) DEFAULT NULL,
  `nominal_stipend` decimal(12,2) DEFAULT NULL,
  `nominal_tuition_fee_percentage` decimal(5,2) DEFAULT NULL,
  `nominal_accomodation_percentage` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_scholarships_student_program_id_foreign` (`student_program_id`),
  CONSTRAINT `enrollment_scholarships_student_program_id_foreign` FOREIGN KEY (`student_program_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.enrollment_timelines
CREATE TABLE IF NOT EXISTS `enrollment_timelines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_program_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_timelines_student_program_id_foreign` (`student_program_id`),
  CONSTRAINT `enrollment_timelines_student_program_id_foreign` FOREIGN KEY (`student_program_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1371 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.enrollment_timeline_media
CREATE TABLE IF NOT EXISTS `enrollment_timeline_media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `enrollment_timeline_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollment_timeline_media_enrollment_timeline_id_foreign` (`enrollment_timeline_id`),
  CONSTRAINT `enrollment_timeline_media_enrollment_timeline_id_foreign` FOREIGN KEY (`enrollment_timeline_id`) REFERENCES `enrollment_timelines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.file_type_tutorial
CREATE TABLE IF NOT EXISTS `file_type_tutorial` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_type_id` bigint unsigned NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_file_type_tutorial_student_file_types1_idx` (`file_type_id`),
  CONSTRAINT `fk_file_type_tutorial_student_file_types1` FOREIGN KEY (`file_type_id`) REFERENCES `student_file_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.guests
CREATE TABLE IF NOT EXISTS `guests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `converted_student_id` bigint unsigned DEFAULT NULL,
  `is_converted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.kurs
CREATE TABLE IF NOT EXISTS `kurs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_kurs_awal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_kurs_akhir` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.locations
CREATE TABLE IF NOT EXISTS `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.log_admin_students
CREATE TABLE IF NOT EXISTS `log_admin_students` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (uuid()),
  `student_id` bigint unsigned NOT NULL,
  `admin_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `action` enum('insert','update','delete','select') COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `row_id` bigint DEFAULT NULL,
  `agent` text COLLATE utf8mb4_unicode_ci,
  `value_before` json DEFAULT NULL,
  `value_after` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_student_id` bigint unsigned NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_notifications_admin_students1_idx` (`admin_student_id`),
  CONSTRAINT `fk_notifications_admin_students1` FOREIGN KEY (`admin_student_id`) REFERENCES `admin_students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_payment_type_id` bigint unsigned DEFAULT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah_nominal` int DEFAULT NULL,
  `jatuh_tempo` date DEFAULT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package` enum('1','2','3','4') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '1.Normal, 2.No paket, 3.Subsidi, 4.Beasiswa',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_paid',
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payments_student_payment_types1_idx` (`student_payment_type_id`),
  CONSTRAINT `fk_payments_student_payment_types1` FOREIGN KEY (`student_payment_type_id`) REFERENCES `student_payment_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=432 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.payment_details
CREATE TABLE IF NOT EXISTS `payment_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `jatuh_tempo` date DEFAULT NULL,
  `nominal` int DEFAULT NULL,
  `tanggal_pembayaran` timestamp NULL DEFAULT NULL,
  `status_pembayaran` enum('not_paid','partially_paid','paid','paid_late','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_paid',
  `status_verifikasi` enum('verified','rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_by` bigint DEFAULT NULL,
  `filename` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` varchar(225) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payment_details_payments1_idx` (`payment_id`),
  CONSTRAINT `fk_payment_details_payments1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=458 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.payment_receipts
CREATE TABLE IF NOT EXISTS `payment_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_detail_id` bigint unsigned NOT NULL,
  `nominal` int DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_pembayaran` timestamp NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payment_receipts_payment_details1_idx` (`payment_detail_id`),
  CONSTRAINT `fk_payment_receipts_payment_details1` FOREIGN KEY (`payment_detail_id`) REFERENCES `payment_details` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.payment_student_programs
CREATE TABLE IF NOT EXISTS `payment_student_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `student_program_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_student_programs_payment_id_student_program_id_unique` (`payment_id`,`student_program_id`)
) ENGINE=InnoDB AUTO_INCREMENT=375 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.payrolls
CREATE TABLE IF NOT EXISTS `payrolls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `npwp` char(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `basic_salary` int DEFAULT NULL,
  `salary_date` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_increase` int DEFAULT NULL,
  `salary_increase_date` char(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overtime_pay` int DEFAULT NULL,
  `meal_allowance` int DEFAULT NULL,
  `allowance` int DEFAULT NULL,
  `employee_id` int NOT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.privileges
CREATE TABLE IF NOT EXISTS `privileges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.privileges_has_roles
CREATE TABLE IF NOT EXISTS `privileges_has_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `privilege_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`,`privilege_id`,`role_id`),
  KEY `fk_privileges_has_roles_roles1_idx` (`role_id`),
  KEY `fk_privileges_has_roles_privileges1_idx` (`privilege_id`),
  CONSTRAINT `fk_privileges_has_roles_privileges1` FOREIGN KEY (`privilege_id`) REFERENCES `privileges` (`id`),
  CONSTRAINT `fk_privileges_has_roles_roles1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.regions
CREATE TABLE IF NOT EXISTS `regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `translations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.sekolah
CREATE TABLE IF NOT EXISTS `sekolah` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `kode_prop` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `propinsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_kab_kota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kabupaten_kota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kode_kec` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kecamatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `npsn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sekolah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama Sekolah',
  `bentuk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_jalan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lintang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bujur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `npsn` (`npsn`),
  KEY `fk_sekolah_countries1_idx` (`country_id`),
  KEY `fk_sekolah_agents1_idx` (`agent_id`),
  CONSTRAINT `fk_sekolah_agents1` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`),
  CONSTRAINT `fk_sekolah_countries1` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=250489 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.states
CREATE TABLE IF NOT EXISTS `states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_id` bigint unsigned NOT NULL,
  `country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fips_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `native` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `country_region` (`country_id`),
  CONSTRAINT `country_region_final` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5464 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.students
CREATE TABLE IF NOT EXISTS `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `korwil_id` bigint unsigned DEFAULT NULL,
  `koordinator_id` bigint unsigned DEFAULT NULL,
  `consultant_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `chinese_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ayah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ayah_phone_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ayah_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_ayah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_ayah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kantor_ayah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_ibu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ibu_phone_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ibu_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_ibu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pekerjaan_ibu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kantor_ibu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenjang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_major` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_id_origin` mediumint DEFAULT NULL,
  `address_origin` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code_origin` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_id_current` mediumint DEFAULT NULL,
  `address_current` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code_current` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `gender` smallint DEFAULT NULL COMMENT '0=Laki; 1=Perempuan;',
  `religion` smallint DEFAULT NULL COMMENT '0=Islam; 1=Kristen Protestan; 2=Kristen Katolik; 3=Hindu; 4=Buddha; 5=Konghucu;',
  `tanggal_berangkat` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_keberangkatan` date DEFAULT NULL,
  `pass_id_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_identitas` smallint DEFAULT NULL COMMENT '0=Passport; 1=KTP;',
  `expired_passport` date DEFAULT NULL,
  `tempat_lahir` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `graduation_time` date DEFAULT NULL,
  `average_score` decimal(4,2) DEFAULT NULL,
  `test_selesai` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `test_detail` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sponsor_status` smallint DEFAULT NULL,
  `nama_sponsor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perusahaan_sponsor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan_sponsor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bidang_usaha_sponsor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat_usaha_sponsor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_sponsor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hubungan_sponsor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_siswa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_completion_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_new_student` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_students_users1_idx` (`user_id`),
  KEY `fk_students_korwil_id` (`korwil_id`),
  KEY `fk_students_koordinator_id` (`koordinator_id`),
  KEY `fk_students_consultant_id` (`consultant_id`),
  CONSTRAINT `fk_students_consultant_id` FOREIGN KEY (`consultant_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_koordinator_id` FOREIGN KEY (`koordinator_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_korwil_id` FOREIGN KEY (`korwil_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_students_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=724 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.students_has_payments
CREATE TABLE IF NOT EXISTS `students_has_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `payment_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_students_has_payments_payments1_idx` (`payment_id`),
  KEY `fk_students_has_payments_students1_idx` (`student_id`),
  CONSTRAINT `fk_students_has_payments_payments1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  CONSTRAINT `fk_students_has_payments_students1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=434 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_education_backgrounds
CREATE TABLE IF NOT EXISTS `student_education_backgrounds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `jenjang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_sekolah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `masuk` year DEFAULT NULL,
  `keluar` year DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_education_backgrounds_students1_idx` (`student_id`),
  CONSTRAINT `fk_student_education_backgrounds_students1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=788 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_enrollment_documents
CREATE TABLE IF NOT EXISTS `student_enrollment_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `university_id` bigint unsigned NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `category` enum('personal_documents','university_documents','program_documents') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_enrollment_documents_universities1_idx` (`university_id`),
  CONSTRAINT `fk_student_enrollment_documents_universities1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_enrollment_document_programs
CREATE TABLE IF NOT EXISTS `student_enrollment_document_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_enrollment_document_id` bigint unsigned NOT NULL,
  `student_program_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doc_program` (`student_enrollment_document_id`,`student_program_id`),
  KEY `fk_doc_program_prog` (`student_program_id`),
  CONSTRAINT `fk_doc_program_doc` FOREIGN KEY (`student_enrollment_document_id`) REFERENCES `student_enrollment_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_program_prog` FOREIGN KEY (`student_program_id`) REFERENCES `enrollment_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_favorites
CREATE TABLE IF NOT EXISTS `student_favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `program_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_favorites_students1_idx` (`student_id`),
  KEY `fk_student_favorites_univ_programs1_idx` (`program_id`),
  CONSTRAINT `fk_student_favorites_students1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `fk_student_favorites_univ_programs1` FOREIGN KEY (`program_id`) REFERENCES `univ_programs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=452 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_files
CREATE TABLE IF NOT EXISTS `student_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_note` text COLLATE utf8mb4_unicode_ci,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_files_type_foreign` (`type`),
  KEY `fk_student_files_students1_idx` (`student_id`),
  CONSTRAINT `fk_student_files_students1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `student_files_type_foreign` FOREIGN KEY (`type`) REFERENCES `student_file_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_file_types
CREATE TABLE IF NOT EXISTS `student_file_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_additional` tinyint(1) NOT NULL DEFAULT '0',
  `order` int NOT NULL,
  `mime_type` enum('text-field','files') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_file_count` int NOT NULL DEFAULT '1',
  `max_file_count` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_file_type_univ_program
CREATE TABLE IF NOT EXISTS `student_file_type_univ_program` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_file_type_id` bigint unsigned NOT NULL,
  `univ_id` bigint unsigned NOT NULL,
  `program_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_file_type_univ_program_universities1_idx` (`univ_id`),
  KEY `fk_student_file_type_univ_program_univ_programs1_idx` (`program_id`),
  KEY `idx_sft_id` (`student_file_type_id`),
  CONSTRAINT `fk_student_file_type_univ_program_univ_programs1` FOREIGN KEY (`program_id`) REFERENCES `univ_programs` (`id`),
  CONSTRAINT `fk_student_file_type_univ_program_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`),
  CONSTRAINT `student_file_type_univ_program_student_file_type_id_foreign` FOREIGN KEY (`student_file_type_id`) REFERENCES `student_file_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=613 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_payment_discounts
CREATE TABLE IF NOT EXISTS `student_payment_discounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `nominal` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_payment_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_student_payment_discounts_student_payments_idx` (`student_payment_id`),
  CONSTRAINT `fk_student_payment_discounts_student_payments` FOREIGN KEY (`student_payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.student_payment_types
CREATE TABLE IF NOT EXISTS `student_payment_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `need_enrollment` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.subregions
CREATE TABLE IF NOT EXISTS `subregions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `translations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `region_id` bigint unsigned NOT NULL,
  `flag` tinyint(1) NOT NULL DEFAULT '1',
  `wikiDataId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapid API GeoDB Cities',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subregion_continent` (`region_id`),
  CONSTRAINT `subregion_continent_final` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.universities
CREATE TABLE IF NOT EXISTS `universities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_univ_china` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `register_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_univ_international` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `judul1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi1` longtext COLLATE utf8mb4_unicode_ci,
  `judul2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi2` longtext COLLATE utf8mb4_unicode_ci,
  `judul3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi3` longtext COLLATE utf8mb4_unicode_ci,
  `judul4` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi4` longtext COLLATE utf8mb4_unicode_ci,
  `kategori` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `participation` int DEFAULT NULL,
  `kuota` int DEFAULT NULL,
  `kota` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provinsi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edurank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_edurank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usnews` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_usnews` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `times` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_times` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qs` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_qs` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shanghai_rank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url_shanghai_rank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah_murid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atribut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `living_expense` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `more_info` longtext COLLATE utf8mb4_unicode_ci,
  `accomodation_description` longtext COLLATE utf8mb4_unicode_ci,
  `status` enum('P','M','S','T') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'P=Premium; M=Medioker; S=Special; T=Trash;',
  `t_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=302 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_accomodations
CREATE TABLE IF NOT EXISTS `univ_accomodations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_accomodations_universities1_idx` (`univ_id`),
  CONSTRAINT `fk_univ_accomodations_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=332 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_accomodation_details
CREATE TABLE IF NOT EXISTS `univ_accomodation_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_accomodations_id` bigint unsigned NOT NULL,
  `room_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_price` int DEFAULT NULL,
  `price_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_accomodation_details_univ_accomodations1_idx` (`univ_accomodations_id`),
  CONSTRAINT `fk_univ_accomodation_details_univ_accomodations1` FOREIGN KEY (`univ_accomodations_id`) REFERENCES `univ_accomodations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=658 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_accomodation_photos
CREATE TABLE IF NOT EXISTS `univ_accomodation_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `univ_accomodations_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_accomodation_photos_universities1_idx` (`univ_id`),
  KEY `fk_univ_accomodation_photos_univ_accomodations1_idx` (`univ_accomodations_id`),
  CONSTRAINT `fk_univ_accomodation_photos_univ_accomodations1` FOREIGN KEY (`univ_accomodations_id`) REFERENCES `univ_accomodations` (`id`),
  CONSTRAINT `fk_univ_accomodation_photos_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_categories
CREATE TABLE IF NOT EXISTS `univ_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `univ_categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_entry_requirements
CREATE TABLE IF NOT EXISTS `univ_entry_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `admission_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_entry_requirements_universities1_idx` (`univ_id`),
  CONSTRAINT `fk_univ_entry_requirements_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=416 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_facilities
CREATE TABLE IF NOT EXISTS `univ_facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_fee_structures
CREATE TABLE IF NOT EXISTS `univ_fee_structures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `fee_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal` int DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_fee_structures_universities1_idx` (`univ_id`),
  CONSTRAINT `fk_univ_fee_structures_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=632 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_has_categories
CREATE TABLE IF NOT EXISTS `univ_has_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_has_categories_universities1_idx` (`univ_id`),
  KEY `fk_univ_has_categories_univ_categories1_idx` (`category_id`),
  CONSTRAINT `fk_univ_has_categories_univ_categories1` FOREIGN KEY (`category_id`) REFERENCES `univ_categories` (`id`),
  CONSTRAINT `fk_univ_has_categories_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=300 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_has_facilities
CREATE TABLE IF NOT EXISTS `univ_has_facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `univ_facilities_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_has_facilities_univ_id` (`univ_id`),
  KEY `fk_univ_has_facilities_facility_id` (`univ_facilities_id`),
  CONSTRAINT `fk_univ_has_facilities_facility_id` FOREIGN KEY (`univ_facilities_id`) REFERENCES `univ_facilities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_univ_has_facilities_univ_id` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=898 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_programs
CREATE TABLE IF NOT EXISTS `univ_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admission_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `c_duration` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starting_date` date DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `starting_date2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application_deadline2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teaching_language` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `c_tuition_fee` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'RMB',
  `tuition_fee` int DEFAULT NULL,
  `c_application_fee` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'RMB',
  `application_fee` int DEFAULT NULL,
  `c_service_fee` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'RMB',
  `service_fee` int DEFAULT NULL,
  `program_description` longtext COLLATE utf8mb4_unicode_ci,
  `entry_requirement` longtext COLLATE utf8mb4_unicode_ci,
  `fee_structure` longtext COLLATE utf8mb4_unicode_ci,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `is_featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_programs_universities1_idx` (`univ_id`),
  CONSTRAINT `fk_univ_programs_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47346 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.univ_scholarships
CREATE TABLE IF NOT EXISTS `univ_scholarships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `univ_id` bigint unsigned NOT NULL,
  `admission_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tuition_fee` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accomodation_fee` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_fee` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stipend_monthly` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stipend_yearly` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_univ_scholarships_universities1_idx` (`univ_id`),
  CONSTRAINT `fk_univ_scholarships_universities1` FOREIGN KEY (`univ_id`) REFERENCES `universities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=900 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table db_ybaik_new.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `bank_accounts_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approval` tinyint(1) DEFAULT NULL,
  `plain_password` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referensi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_code_UNIQUE` (`referral_code`),
  KEY `fk_users_roles1_idx` (`role_id`),
  KEY `fk_users_bank_accounts1_idx` (`bank_accounts_id`),
  CONSTRAINT `fk_users_bank_accounts1` FOREIGN KEY (`bank_accounts_id`) REFERENCES `bank_accounts` (`id`),
  CONSTRAINT `fk_users_roles1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1082 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
