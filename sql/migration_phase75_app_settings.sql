-- Phase 75: Key-value app settings (honey.ml_per_gallon, etc.)
-- Run once: mysql -u root rts_system < sql/migration_phase75_app_settings.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` VARCHAR(64) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`setting_key`),
  CONSTRAINT `fk_app_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
