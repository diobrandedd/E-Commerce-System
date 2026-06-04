-- CSR Online Orders Generator — batch sessions and parcel lines for print merge.
-- Run once: mysql -u root rts_system < sql/migration_phase17_csr_online_orders.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `csr_online_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `status` ENUM('active','processed','cancelled') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME NULL DEFAULT NULL,
  `master_file_token` VARCHAR(64) NULL DEFAULT NULL,
  `master_filename` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_csr_online_batches_user_status` (`user_id`, `status`),
  KEY `idx_csr_online_batches_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `csr_online_batch_runs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `pipeline` ENUM('partner','vip','shopee_spx') NOT NULL,
  `status` ENUM('started','completed','failed') NOT NULL DEFAULT 'started',
  `meta_json` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_csr_online_batch_runs_batch` (`batch_id`),
  CONSTRAINT `fk_csr_online_batch_runs_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `csr_online_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `csr_online_order_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `run_id` INT UNSIGNED NULL DEFAULT NULL,
  `order_no` VARCHAR(64) NULL DEFAULT NULL,
  `recipient_name` VARCHAR(255) NOT NULL DEFAULT '',
  `items` TEXT NOT NULL,
  `courier` VARCHAR(64) NOT NULL DEFAULT '',
  `tracking_number` VARCHAR(96) NOT NULL DEFAULT '',
  `source_pipeline` ENUM('partner','vip','shopee_spx') NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_csr_online_order_lines_batch` (`batch_id`),
  KEY `idx_csr_online_order_lines_tracking` (`tracking_number`),
  CONSTRAINT `fk_csr_online_order_lines_batch`
    FOREIGN KEY (`batch_id`) REFERENCES `csr_online_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_csr_online_order_lines_run`
    FOREIGN KEY (`run_id`) REFERENCES `csr_online_batch_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
