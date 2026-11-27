-- Phase 100: HR manager override approval requests (replaces administrator password for
-- Check Inventory finalize, leaf-pool edit, Packaging scan-line undo).
-- Run once: mysql -u root rts_system < sql/migration_phase100_override_approvals.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `override_approval_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action_type` ENUM(
    'inventory_check_finalize',
    'dept_raw_pool_set',
    'packaging_scan_undo'
  ) NOT NULL,
  `payload_json` JSON NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `requested_by` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','approved','rejected','failed') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` DATETIME NULL DEFAULT NULL,
  `rejection_note` VARCHAR(500) NULL DEFAULT NULL,
  `result_json` JSON NULL DEFAULT NULL,
  `error_message` VARCHAR(500) NULL DEFAULT NULL,
  `hr_viewed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_override_appr_status` (`status`, `created_at`),
  KEY `idx_override_appr_requested` (`requested_by`, `created_at`),
  KEY `idx_override_appr_type` (`action_type`, `status`),
  CONSTRAINT `fk_override_appr_requester` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_override_appr_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
