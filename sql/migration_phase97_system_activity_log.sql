-- Phase 97: Unified system activity log (Logs page feed).
-- Going-forward mirror of high-value mutations; no historical backfill.

CREATE TABLE IF NOT EXISTS `system_activity_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(32) NOT NULL,
  `action` VARCHAR(64) NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `entity_type` VARCHAR(64) NULL,
  `entity_id` VARCHAR(64) NULL,
  `old_value` TEXT NULL,
  `new_value` TEXT NULL,
  `meta_json` JSON NULL,
  `actor_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sal_created` (`created_at`),
  KEY `idx_sal_category_created` (`category`, `created_at`),
  KEY `idx_sal_actor` (`actor_user_id`),
  KEY `idx_sal_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
