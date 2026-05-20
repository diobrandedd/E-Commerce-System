-- RTS + Inventory — full schema for fresh install (rts_system).
-- Import this single file in phpMyAdmin or: mysql -u root < sql/full_schema.sql
-- Default logins (change after first login): executive / 000001 , delivery / 000002
-- After import, run auth/phase1_5_setup.php once for inventory bootstrap.

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `rts_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rts_system`;

-- ---------------------------------------------------------------------------
-- RTS core
-- ---------------------------------------------------------------------------

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

-- ---------------------------------------------------------------------------
-- Auth + pre-delivery bulk photos
-- ---------------------------------------------------------------------------

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
  `delivery_date` DATE NOT NULL COMMENT 'business date for this delivery run',
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_date` (`delivery_date`),
  KEY `idx_submitted` (`submitted_at`),
  CONSTRAINT `fk_batch_user` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `app_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `delivery_batch_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` INT UNSIGNED NOT NULL,
  `photo_filename` VARCHAR(190) NOT NULL COMMENT 'file under deliveries_documentation storage',
  `tracking_number` VARCHAR(96) NULL COMMENT 'normalized tracking when known',
  `raw_qr_text` VARCHAR(512) NULL COMMENT 'raw decode or JSON for ambiguous',
  `item_status` ENUM('decoded_ok','needs_manual','ambiguous','resolved') NOT NULL DEFAULT 'needs_manual',
  `voided_at` DATETIME NULL COMMENT 'admin may void duplicate row without deleting file',
  `resolved_at` DATETIME NULL,
  `resolved_by_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_status` (`item_status`),
  KEY `idx_tracking` (`tracking_number`),
  CONSTRAINT `fk_item_batch` FOREIGN KEY (`batch_id`) REFERENCES `delivery_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_resolver` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `app_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- SP Sort
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_sort_batches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `submitted_by_user_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(120) NULL COMMENT 'optional batch name',
  `row_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `source_files` JSON NULL COMMENT 'original upload filenames',
  `print_meta` JSON NULL COMMENT 'order/pickup date lines, notes',
  `prepared_by` VARCHAR(120) NULL,
  `merged_filename` VARCHAR(190) NULL COMMENT 'sorted xlsx in sp_sort storage',
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

-- ---------------------------------------------------------------------------
-- Inventory Phase 1: auth foundation
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(160) NULL,
  `employee_id` VARCHAR(24) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `dept_id` INT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_employee_id` (`employee_id`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_dept` (`dept_id`),
  KEY `idx_users_active` (`is_active`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_users_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_pins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pin_hash` VARCHAR(255) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `roles` (`name`) VALUES
  ('executive'),
  ('prod_manager'),
  ('tl'),
  ('staff'),
  ('pos');

INSERT IGNORE INTO `departments` (`name`) VALUES
  ('honey'),
  ('paragis'),
  ('cacao_brc'),
  ('csr');

INSERT INTO `users` (`username`, `name`, `email`, `employee_id`, `password_hash`, `role_id`, `dept_id`, `is_active`)
SELECT
  a.username AS username,
  COALESCE(NULLIF(a.full_name, ''), a.username) AS name,
  CONCAT(LOWER(a.username), '@pyx.local') AS email,
  COALESCE(NULLIF(TRIM(a.employee_id), ''), LPAD(a.id, 6, '0')) AS employee_id,
  a.password_hash,
  r.id AS role_id,
  d.id AS dept_id,
  CASE WHEN a.status = 'active' THEN 1 ELSE 0 END AS is_active
FROM app_users a
INNER JOIN roles r ON r.name = CASE
  WHEN a.role IN ('executive', 'admin') THEN 'executive'
  WHEN a.role = 'production_manager' THEN 'prod_manager'
  WHEN a.role = 'team_leader' THEN 'tl'
  WHEN a.role = 'staff' THEN 'staff'
  WHEN a.role = 'delivery' THEN 'pos'
  ELSE 'staff'
END
LEFT JOIN departments d ON d.name = CASE
  WHEN a.department = 'honey' THEN 'honey'
  WHEN a.department = 'paragis' THEN 'paragis'
  WHEN a.department = 'csr' THEN 'csr'
  WHEN a.department = 'cacao_black_rice_coffee' THEN 'cacao_brc'
  ELSE NULL
END
WHERE NOT EXISTS (
  SELECT 1 FROM users u WHERE u.username = a.username
);

-- ---------------------------------------------------------------------------
-- Inventory Phase 2: stocks
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stocks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'pcs',
  `current_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `supplier_name` VARCHAR(160) NULL,
  `supplier_code` VARCHAR(64) NULL,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stocks_name_dept` (`name`, `dept_id`),
  KEY `idx_stocks_dept` (`dept_id`),
  KEY `idx_stocks_created_by` (`created_by`),
  CONSTRAINT `fk_stocks_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stocks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `batching_date` DATETIME NOT NULL,
  `supplier_batch_no` VARCHAR(120) NULL,
  `entered_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_entries_stock` (`stock_id`),
  KEY `idx_stock_entries_dept` (`dept_id`),
  KEY `idx_stock_entries_entered_by` (`entered_by`),
  KEY `idx_stock_entries_created_at` (`created_at`),
  CONSTRAINT `fk_stock_entries_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_entries_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_entries_entered_by` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `edit_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(64) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `field_name` VARCHAR(64) NOT NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `reason` TEXT NOT NULL,
  `requested_by` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_note` TEXT NULL,
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_edit_requests_status` (`status`),
  KEY `idx_edit_requests_requester` (`requested_by`),
  KEY `idx_edit_requests_reviewed_by` (`reviewed_by`),
  KEY `idx_edit_requests_table_record` (`table_name`, `record_id`),
  CONSTRAINT `fk_edit_requests_requester` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_edit_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Inventory Phase 3: products
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `size` ENUM('liter','350ml','250ml','gallon') NULL,
  `variant` ENUM('light','amber','dark') NULL,
  `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `baseline_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_dept` (`dept_id`),
  KEY `idx_products_created_by` (`created_by`),
  KEY `idx_products_name` (`name`),
  CONSTRAINT `fk_products_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_formula` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty_per_unit` DECIMAL(12,4) NOT NULL,
  `price_at_creation` DECIMAL(12,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_formula_product` (`product_id`),
  KEY `idx_product_formula_stock` (`stock_id`),
  CONSTRAINT `fk_product_formula_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_formula_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_deductions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `dept_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `reason` TEXT NOT NULL,
  `deducted_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_deductions_stock` (`stock_id`),
  KEY `idx_stock_deductions_dept` (`dept_id`),
  KEY `idx_stock_deductions_by` (`deducted_by`),
  KEY `idx_stock_deductions_created_at` (`created_at`),
  CONSTRAINT `fk_stock_deductions_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_deductions_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_deductions_by` FOREIGN KEY (`deducted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `beginning_inventory` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dept_id` INT UNSIGNED NOT NULL,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL,
  `set_by` INT UNSIGNED NOT NULL,
  `month_year` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_beginning_inventory_scope` (`dept_id`, `stock_id`, `month_year`),
  KEY `idx_beginning_inventory_stock` (`stock_id`),
  KEY `idx_beginning_inventory_set_by` (`set_by`),
  KEY `idx_beginning_inventory_month` (`month_year`),
  CONSTRAINT `fk_beginning_inventory_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_beginning_inventory_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_beginning_inventory_set_by` FOREIGN KEY (`set_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Inventory Phase 4: costing history
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stock_price_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `old_price` DECIMAL(12,2) NOT NULL,
  `new_price` DECIMAL(12,2) NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL,
  `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_price_history_stock` (`stock_id`),
  KEY `idx_stock_price_history_changed_by` (`changed_by`),
  KEY `idx_stock_price_history_changed_at` (`changed_at`),
  CONSTRAINT `fk_stock_price_history_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_price_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_cost_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `old_cost` DECIMAL(12,2) NOT NULL,
  `new_cost` DECIMAL(12,2) NOT NULL,
  `triggered_by_stock_id` INT UNSIGNED NOT NULL,
  `recalculated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_cost_history_product` (`product_id`),
  KEY `idx_product_cost_history_trigger_stock` (`triggered_by_stock_id`),
  KEY `idx_product_cost_history_recalculated_at` (`recalculated_at`),
  CONSTRAINT `fk_product_cost_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_product_cost_history_trigger_stock` FOREIGN KEY (`triggered_by_stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
