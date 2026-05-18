-- SP Sort: merged delivery Excel batches for printing / waybill workflow
USE `rts_system`;

CREATE TABLE IF NOT EXISTS `sp_sort_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submitted_by_user_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(120) NULL COMMENT 'optional batch name',
  `row_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `source_files` JSON NULL COMMENT 'original upload filenames',
  `print_meta` JSON NULL COMMENT 'order/pickup date lines, notes',
  `prepared_by` VARCHAR(120) NULL,
  `merged_filename` VARCHAR(190) NULL COMMENT 'sorted xlsx in assets/sp_sort',
  `status` ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
  `completed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sp_sort_status` (`status`),
  KEY `idx_sp_sort_created` (`created_at`),
  CONSTRAINT `fk_sp_sort_user` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `app_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_sort_rows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `row_num` INT UNSIGNED NOT NULL COMMENT '1-based order in sorted output',
  `sort_key_d` VARCHAR(96) NULL COMMENT 'column D value for grouping',
  `courier` VARCHAR(24) NULL COMMENT 'spx|jnt|flash|unknown',
  `shop` VARCHAR(24) NULL COMMENT 'shopee|lazada|tiktok|unknown',
  `channel_text` VARCHAR(255) NULL,
  `row_data` JSON NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sp_sort_rows_batch` (`batch_id`),
  KEY `idx_sp_sort_rows_courier` (`courier`),
  KEY `idx_sp_sort_rows_shop` (`shop`),
  CONSTRAINT `fk_sp_sort_rows_batch` FOREIGN KEY (`batch_id`) REFERENCES `sp_sort_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
