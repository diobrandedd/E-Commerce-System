-- RTS multi-batch barcode labels: jobs + rts_label provenance on honey/dept batches.
-- Run once: mysql -u root rts_system < sql/migration_phase66_rts_barcode_labels.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rts_barcode_label_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_by` INT UNSIGNED NOT NULL,
  `note` VARCHAR(255) NULL DEFAULT NULL,
  `printed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rts_bc_label_jobs_created` (`created_at`),
  CONSTRAINT `fk_rts_bc_label_jobs_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rts_barcode_label_job_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` INT UNSIGNED NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL,
  `kind` ENUM('honey','dept') NOT NULL,
  `honey_batch_id` INT UNSIGNED NULL DEFAULT NULL,
  `dept_batch_id` INT UNSIGNED NULL DEFAULT NULL,
  `product_key` VARCHAR(32) NOT NULL,
  `size` VARCHAR(16) NULL DEFAULT NULL,
  `variant` VARCHAR(32) NULL DEFAULT NULL,
  `batch_no` VARCHAR(48) NOT NULL,
  `bottling_date` DATE NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rts_bc_label_lines_job` (`job_id`),
  CONSTRAINT `fk_rts_bc_label_lines_job` FOREIGN KEY (`job_id`) REFERENCES `rts_barcode_label_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Honey: allow rts_label source + job link
ALTER TABLE `honey_barcode_batches`
  MODIFY COLUMN `source` ENUM('request','backfill','rts_label') NOT NULL DEFAULT 'request';

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'honey_barcode_batches' AND COLUMN_NAME = 'rts_label_job_id');
SET @sql := IF(@c = 0,
  'ALTER TABLE `honey_barcode_batches`
     ADD COLUMN `rts_label_job_id` INT UNSIGNED NULL DEFAULT NULL AFTER `request_id`,
     ADD KEY `idx_honey_bc_batch_rts_job` (`rts_label_job_id`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Dept: source + job link
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dept_pouch_barcode_batches' AND COLUMN_NAME = 'source');
SET @sql := IF(@c = 0,
  'ALTER TABLE `dept_pouch_barcode_batches`
     ADD COLUMN `source` ENUM(''request'',''rts_label'') NOT NULL DEFAULT ''request'' AFTER `request_id`,
     ADD KEY `idx_dept_pouch_batch_source` (`source`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dept_pouch_barcode_batches' AND COLUMN_NAME = 'rts_label_job_id');
SET @sql := IF(@c = 0,
  'ALTER TABLE `dept_pouch_barcode_batches`
     ADD COLUMN `rts_label_job_id` INT UNSIGNED NULL DEFAULT NULL AFTER `source`,
     ADD KEY `idx_dept_pouch_batch_rts_job` (`rts_label_job_id`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
