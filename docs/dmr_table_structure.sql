-- -------------------------------------------------------------
-- TablePlus 6.4.4(604)
--
-- https://tableplus.com/
--
-- Database: berken_db
-- Generation Time: 2026-03-23 22:07:32.1230
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


CREATE TABLE `dmr_brands` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_brands_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_models` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `brand_id` bigint NOT NULL,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_models_brand_name` (`brand_id`,`name`),
  KEY `idx_dmr_models_brand_id` (`brand_id`),
  CONSTRAINT `fk_dmr_models_brand` FOREIGN KEY (`brand_id`) REFERENCES `dmr_brands` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=12111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_colours` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_colours_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_etl_loads` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `loaded_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `source_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_variants` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `model_id` bigint NOT NULL,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_variants_model_name` (`model_id`,`name`),
  KEY `idx_dmr_variants_model_id` (`model_id`),
  CONSTRAINT `fk_dmr_variants_model` FOREIGN KEY (`model_id`) REFERENCES `dmr_models` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=82940 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_body_types` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_body_types_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_vehicle_uses` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_vehicle_uses_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_fact_vehicles` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `stel_nummer` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registrering_nummer` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `variant_id` bigint DEFAULT NULL,
  `vehicle_use_id` bigint DEFAULT NULL,
  `body_type_id` bigint DEFAULT NULL,
  `colour_id` bigint DEFAULT NULL,
  `emission_norm_id` bigint DEFAULT NULL,
  `registration_status_id` bigint DEFAULT NULL,
  `foerste_registrering_dato` datetime(6) DEFAULT NULL,
  `registrering_status_dato` datetime(6) DEFAULT NULL,
  `emission_co` decimal(15,6) DEFAULT NULL,
  `emission_nox` decimal(15,6) DEFAULT NULL,
  `partikel_filter` tinyint(1) DEFAULT NULL,
  `motor_stoerste_effekt` decimal(15,6) DEFAULT NULL,
  `motor_slag_volumen` decimal(15,6) DEFAULT NULL,
  `aksel_antal` int DEFAULT NULL,
  `antal_doere` int DEFAULT NULL,
  `antal_gear` int DEFAULT NULL,
  `maksimum_hastighed` int DEFAULT NULL,
  `model_aar` int DEFAULT NULL,
  `ncap_test` tinyint(1) DEFAULT NULL,
  `siddepladser_maksimum` int DEFAULT NULL,
  `siddepladser_minimum` int DEFAULT NULL,
  `teknisk_total_vaegt` bigint DEFAULT NULL,
  `oevrigt_udstyr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `etl_load_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dmr_fact_vehicles_vehicle_use` (`vehicle_use_id`),
  KEY `fk_dmr_fact_vehicles_emission_norm` (`emission_norm_id`),
  KEY `idx_dmr_fact_vehicles_stel` (`stel_nummer`),
  KEY `idx_dmr_fact_vehicles_registrering` (`registrering_nummer`),
  KEY `idx_dmr_fact_vehicles_model_aar` (`model_aar`),
  KEY `idx_dmr_fact_vehicles_body_type_id` (`body_type_id`),
  KEY `idx_dmr_fact_vehicles_colour_id` (`colour_id`),
  KEY `idx_dmr_fact_vehicles_registration_status_id` (`registration_status_id`),
  KEY `idx_dmr_fact_vehicles_variant` (`variant_id`),
  KEY `idx_dmr_fact_vehicles_etl_load` (`etl_load_id`),
  CONSTRAINT `fk_dmr_fact_vehicles_body_type` FOREIGN KEY (`body_type_id`) REFERENCES `dmr_body_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_fact_vehicles_colour` FOREIGN KEY (`colour_id`) REFERENCES `dmr_colours` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_fact_vehicles_emission_norm` FOREIGN KEY (`emission_norm_id`) REFERENCES `dmr_emission_norms` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_fact_vehicles_etl_load` FOREIGN KEY (`etl_load_id`) REFERENCES `dmr_etl_loads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dmr_fact_vehicles_registration_status` FOREIGN KEY (`registration_status_id`) REFERENCES `dmr_registration_statuses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_fact_vehicles_variant` FOREIGN KEY (`variant_id`) REFERENCES `dmr_variants` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_fact_vehicles_vehicle_use` FOREIGN KEY (`vehicle_use_id`) REFERENCES `dmr_vehicle_uses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10759015 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_drive_energies` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type_nummer` bigint DEFAULT NULL,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_drive_energies_nummer` (`type_nummer`),
  KEY `idx_dmr_drive_energies_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_emission_norms` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_emission_norms_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_equipment_types` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type_nummer` bigint DEFAULT NULL,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_equipment_types_nummer` (`type_nummer`),
  KEY `idx_dmr_equipment_types_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_measurement_norms` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type_nummer` bigint DEFAULT NULL,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_measurement_norms_nummer` (`type_nummer`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_registration_statuses` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_registration_statuses_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_bridge_vehicle_equipment` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint NOT NULL,
  `line_order` int NOT NULL DEFAULT '1',
  `equipment_type_id` bigint NOT NULL,
  `antal` int DEFAULT NULL,
  `vises_ved_syn` tinyint(1) DEFAULT NULL,
  `vises_ved_forespoergsel` tinyint(1) DEFAULT NULL,
  `vises_ved_standard_oprettelse` tinyint(1) DEFAULT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_bridge_vehicle_equipment_line` (`vehicle_id`,`line_order`),
  KEY `fk_dmr_bridge_equipment_type` (`equipment_type_id`),
  KEY `idx_dmr_bridge_vehicle_equipment_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_dmr_bridge_equipment_type` FOREIGN KEY (`equipment_type_id`) REFERENCES `dmr_equipment_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_bridge_equipment_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `dmr_fact_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46906751 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `dmr_bridge_vehicle_drivmiddel` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `vehicle_id` bigint NOT NULL,
  `line_order` int NOT NULL DEFAULT '1',
  `drive_energy_id` bigint DEFAULT NULL,
  `measurement_norm_id` bigint DEFAULT NULL,
  `drivmiddel_primaer` tinyint(1) DEFAULT NULL,
  `motor_km_per_liter` decimal(15,6) DEFAULT NULL,
  `miljoe_co2_udslip` decimal(15,6) DEFAULT NULL,
  `motor_elektrisk_forbrug` decimal(15,6) DEFAULT NULL,
  `motor_braendselscelle` tinyint(1) DEFAULT NULL,
  `created_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dmr_bridge_vehicle_drivmiddel_line` (`vehicle_id`,`line_order`),
  KEY `fk_dmr_bridge_drivmiddel_drive_energy` (`drive_energy_id`),
  KEY `fk_dmr_bridge_drivmiddel_measurement_norm` (`measurement_norm_id`),
  KEY `idx_dmr_bridge_vehicle_drivmiddel_vehicle` (`vehicle_id`),
  CONSTRAINT `fk_dmr_bridge_drivmiddel_drive_energy` FOREIGN KEY (`drive_energy_id`) REFERENCES `dmr_drive_energies` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_bridge_drivmiddel_measurement_norm` FOREIGN KEY (`measurement_norm_id`) REFERENCES `dmr_measurement_norms` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_dmr_bridge_drivmiddel_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `dmr_fact_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11035685 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;