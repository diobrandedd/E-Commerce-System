-- Import this when `rts_system` already exists (phpMyAdmin: select DB → Import → this file).
USE `rts_system`;

CREATE TABLE IF NOT EXISTS `rts_cases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tracking_number` VARCHAR(96) NOT NULL,
  `courier_detected` VARCHAR(16) NULL COMMENT 'spx|jnt|flash|unknown',
  `courier_final` VARCHAR(16) NOT NULL,
  `shop` VARCHAR(24) NOT NULL COMMENT 'shopee|lazada|tiktok|vip',
  `vip_subsource` VARCHAR(16) NULL COMMENT 'website|facebook when shop=vip',
  `date_delivered` DATE NULL,
  `date_opened` DATE NOT NULL COMMENT 'parcel opened / recorded',
  `documentation_photo` VARCHAR(190) NULL COMMENT 'first image filename; legacy single-doc',
  `documentation_attachments` JSON NULL COMMENT '[{kind:image|video,file:filename}]',
  `damaged_reason` ENUM('leak','item_replaced','packaging_replaced') NULL DEFAULT NULL COMMENT 'NULL = not damaged',
  `damaged_tag` VARCHAR(128) NOT NULL DEFAULT 'no' COMMENT 'no | yes - Leak etc.',
  `claim_status` ENUM('for_submission','denied_claims','approved_claims','submitted_claims','on_process') NOT NULL DEFAULT 'for_submission',
  `claim_status_note` VARCHAR(512) NOT NULL DEFAULT '',
  `delivery_match_item_id` INT UNSIGNED NULL COMMENT 'delivery_batch_items.id for pre-delivery photo match',
  `parcel_info_snapshot` TEXT NULL COMMENT 'optional raw courier item text later',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `voided_at` DATETIME NULL COMMENT 'admin voided; row kept for audit',
  `voided_by_user_id` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tracking` (`tracking_number`),
  KEY `idx_shop` (`shop`),
  KEY `idx_vip` (`shop`, `vip_subsource`),
  KEY `idx_dates` (`date_delivered`, `date_opened`),
  KEY `idx_damaged_reason` (`damaged_reason`),
  KEY `idx_claim_status` (`claim_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rts_line_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` INT UNSIGNED NOT NULL,
  `product_type` VARCHAR(32) NOT NULL COMMENT 'honey|honey_with_other|pouch_pt|pouch_pt_with_honey|pouch_brc|jar_cacao|jar_cacao_with_honey|jar_honey_comb',
  `honey_size` VARCHAR(16) NULL COMMENT '250ml|350ml|liter|gallon',
  `honey_variant` VARCHAR(16) NULL COMMENT 'light|amber|dark',
  `combo_product` VARCHAR(32) NULL COMMENT 'slug of paired product in w/other combos',
  `combo_quantity` DECIMAL(10,2) NULL COMMENT 'quantity of the combo product',
  `combo_honey_size` VARCHAR(16) NULL COMMENT 'honey size when combo item IS honey',
  `combo_honey_variant` VARCHAR(16) NULL COMMENT 'honey variant when combo item IS honey',
  `quantity` DECIMAL(10,2) NOT NULL,
  `is_bonus` TINYINT(1) NOT NULL DEFAULT 0,
  `bonus_parent_id` INT UNSIGNED NULL COMMENT 'id of parent line if this is a bonus',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_case` (`case_id`),
  KEY `idx_bonus_parent` (`bonus_parent_id`),
  CONSTRAINT `fk_line_case` FOREIGN KEY (`case_id`) REFERENCES `rts_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL DEFAULT '',
  `employee_id` VARCHAR(24) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('executive','production_manager','team_leader','staff','delivery','admin') NOT NULL,
  `department` ENUM('csr','honey','cacao_black_rice_coffee','paragis') NULL,
  `status` ENUM('active','inactive','awol','on_leave','resigned') NOT NULL DEFAULT 'active',
  `created_by_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_app_users_username` (`username`),
  UNIQUE KEY `uq_app_users_employee_id` (`employee_id`),
  KEY `idx_app_users_role` (`role`),
  KEY `idx_app_users_department` (`department`),
  KEY `idx_app_users_status` (`status`),
  CONSTRAINT `fk_app_users_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `app_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_users` (`username`, `full_name`, `employee_id`, `password_hash`, `role`, `status`) VALUES
  ('executive', 'Executive', '000001', '$2y$10$DmICRvrRPGD0S4dFVSgnxO.zKjBMxEq/E7qkEsY34IaAqYZ.7qITu', 'executive', 'active'),
  ('delivery', 'Delivery User', '000002', '$2y$10$SjrJICoQyQ5b/A4Dk7ma5.ygADAjqLHIv3wDfgG55kE9eAbYmzwIu', 'delivery', 'active')
ON DUPLICATE KEY UPDATE `username` = `username`;

CREATE TABLE IF NOT EXISTS `delivery_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submitted_by_user_id` INT UNSIGNED NOT NULL,
  `delivery_date` DATE NOT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_date` (`delivery_date`),
  CONSTRAINT `fk_batch_user` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `app_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `delivery_batch_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `photo_filename` VARCHAR(190) NOT NULL,
  `tracking_number` VARCHAR(96) NULL,
  `raw_qr_text` VARCHAR(512) NULL,
  `item_status` ENUM('decoded_ok','needs_manual','ambiguous','resolved') NOT NULL DEFAULT 'needs_manual',
  `voided_at` DATETIME NULL,
  `resolved_at` DATETIME NULL,
  `resolved_by_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_item_status` (`item_status`),
  CONSTRAINT `fk_item_batch` FOREIGN KEY (`batch_id`) REFERENCES `delivery_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_resolver` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `app_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_sort_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submitted_by_user_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(120) NULL,
  `row_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `source_files` JSON NULL,
  `print_meta` JSON NULL,
  `prepared_by` VARCHAR(120) NULL,
  `merged_filename` VARCHAR(190) NULL,
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
  `row_num` INT UNSIGNED NOT NULL,
  `sort_key_d` VARCHAR(96) NULL,
  `courier` VARCHAR(24) NULL,
  `shop` VARCHAR(24) NULL,
  `channel_text` VARCHAR(255) NULL,
  `row_data` JSON NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sp_sort_rows_batch` (`batch_id`),
  KEY `idx_sp_sort_rows_courier` (`courier`),
  KEY `idx_sp_sort_rows_shop` (`shop`),
  CONSTRAINT `fk_sp_sort_rows_batch` FOREIGN KEY (`batch_id`) REFERENCES `sp_sort_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
