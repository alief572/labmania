-- ============================================================
-- Migration: Visit Report Module Tables
-- Database: db_consultant_new_dev
-- Host: 206.84.97.107
-- 
-- Creates tables for the Laporan Kunjungan (Visit Report) module.
-- These tables are isolated from existing SPK tables.
-- No foreign key constraints to existing tables (soft references only).
-- Foreign key constraints ARE added between visit_report_* tables.
-- ============================================================

-- -----------------------------------------------------------
-- Table: visit_report_headers
-- Main visit report record with project reference and metadata
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visit_report_headers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` VARCHAR(20) NOT NULL COMMENT 'Generated ID format: VR{YYMM}{seq}',
  `id_spk_penawaran` VARCHAR(50) NOT NULL COMMENT 'Soft FK to kons_tr_spk_penawaran',
  `company_name` VARCHAR(255) NOT NULL COMMENT 'Denormalized company name',
  `project_name` VARCHAR(255) NOT NULL COMMENT 'Denormalized project name',
  `visit_date` DATE NOT NULL COMMENT 'Date of visit',
  `start_time` TIME NULL DEFAULT NULL COMMENT 'Visit start time',
  `finish_time` TIME NULL DEFAULT NULL COMMENT 'Visit finish time',
  `consultant_id` INT(11) NOT NULL COMMENT 'User ID of consultant',
  `consultant_name` VARCHAR(100) NOT NULL COMMENT 'Denormalized consultant name',
  `status` ENUM('draft','final') NOT NULL DEFAULT 'draft' COMMENT 'Report status',
  `created_at` DATETIME NOT NULL COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Last update timestamp',
  `created_by` INT(11) NOT NULL COMMENT 'User who created',
  `updated_by` INT(11) NULL DEFAULT NULL COMMENT 'User who last updated',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_report_id` (`report_id`),
  INDEX `idx_id_spk_penawaran` (`id_spk_penawaran`),
  INDEX `idx_consultant_id` (`consultant_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Visit report header records';

-- -----------------------------------------------------------
-- Table: visit_report_activities
-- Activities performed during a visit (from SPK list or custom)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visit_report_activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) NOT NULL COMMENT 'FK to visit_report_headers.id',
  `activity_source` ENUM('spk','custom') NOT NULL COMMENT 'Whether from SPK list or custom',
  `spk_activity_id` INT(11) NULL DEFAULT NULL COMMENT 'Soft reference to kons_tr_spk_aktifitas.id',
  `activity_name` VARCHAR(500) NOT NULL COMMENT 'Activity name (from SPK or custom text)',
  `sort_order` INT(3) NOT NULL DEFAULT 0 COMMENT 'Display order',
  PRIMARY KEY (`id`),
  INDEX `idx_report_id` (`report_id`),
  CONSTRAINT `fk_activities_report` FOREIGN KEY (`report_id`) REFERENCES `visit_report_headers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Visit report activities';

-- -----------------------------------------------------------
-- Table: visit_report_action_plans
-- Action plans associated with activities
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visit_report_action_plans` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `activity_id` INT(11) NOT NULL COMMENT 'FK to visit_report_activities.id',
  `report_id` INT(11) NOT NULL COMMENT 'FK to visit_report_headers.id (for easy querying)',
  `description` VARCHAR(500) NOT NULL COMMENT 'Action plan description',
  `pic` VARCHAR(100) NOT NULL COMMENT 'Person in charge',
  `due_date` DATE NOT NULL COMMENT 'Due date',
  `status` ENUM('progress','done') NOT NULL DEFAULT 'progress' COMMENT 'Action plan status',
  `created_at` DATETIME NOT NULL COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Last status update',
  PRIMARY KEY (`id`),
  INDEX `idx_activity_id` (`activity_id`),
  INDEX `idx_report_id` (`report_id`),
  CONSTRAINT `fk_action_plans_activity` FOREIGN KEY (`activity_id`) REFERENCES `visit_report_activities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_action_plans_report` FOREIGN KEY (`report_id`) REFERENCES `visit_report_headers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Action plans for visit report activities';

-- -----------------------------------------------------------
-- Table: visit_report_improvements
-- Potential improvements recorded during visits
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visit_report_improvements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_id` INT(11) NOT NULL COMMENT 'FK to visit_report_headers.id',
  `sort_order` INT(3) NOT NULL COMMENT 'Sequence number',
  `potensi_improvement` TEXT NOT NULL COMMENT 'Potential improvement description',
  `hasil_improvement` TEXT NOT NULL COMMENT 'Improvement result',
  `status` ENUM('progress','done') NOT NULL DEFAULT 'progress' COMMENT 'Improvement status',
  `created_at` DATETIME NOT NULL COMMENT 'Record creation timestamp',
  `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Last status update',
  PRIMARY KEY (`id`),
  INDEX `idx_report_id` (`report_id`),
  CONSTRAINT `fk_improvements_report` FOREIGN KEY (`report_id`) REFERENCES `visit_report_headers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Potential improvements from visit reports';

