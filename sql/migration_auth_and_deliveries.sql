-- Auth + pre-delivery bulk photo batches (import into your RTS database, e.g. rts_system).
-- Default passwords (change after first login): admin = admin123 , delivery = delivery123

SET NAMES utf8mb4;

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
  `photo_filename` VARCHAR(190) NOT NULL COMMENT 'file under assets/deliveries_documentation',
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
