-- Phase 91: Packaging materials damaged-pool log (Report damage)
-- Run once: mysql -u root rts_system < sql/migration_phase91_packaging_material_damage_log.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `packaging_material_damage_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_id` INT UNSIGNED NOT NULL,
  `qty` DECIMAL(14,4) NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `stock_entry_id` INT UNSIGNED NULL DEFAULT NULL,
  `reported_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pmd_stock_created` (`stock_id`, `created_at`),
  KEY `idx_pmd_created` (`created_at`),
  KEY `idx_pmd_entry` (`stock_entry_id`),
  CONSTRAINT `fk_pmd_stock`
    FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pmd_user`
    FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
