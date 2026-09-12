-- Phase 81: Mobile POS assigned staff (login with IMS username/password)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pos_mobile_staff` (
  `user_id` INT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` INT UNSIGNED NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_pos_mobile_staff_assigned` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
