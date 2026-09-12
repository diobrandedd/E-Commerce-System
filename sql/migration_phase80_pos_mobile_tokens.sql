-- Phase 80: Mobile POS API bearer tokens + sale refund columns
-- Safe to re-run: CREATE IF NOT EXISTS / ADD COLUMN guarded in PHP ensure helpers too.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pos_api_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token_hash` CHAR(64) NOT NULL,
  `pin_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `label` VARCHAR(160) NOT NULL DEFAULT '',
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` DATETIME NULL,
  `last_used_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pos_api_tokens_hash` (`token_hash`),
  KEY `idx_pos_api_tokens_pin` (`pin_id`),
  KEY `idx_pos_api_tokens_user` (`user_id`),
  KEY `idx_pos_api_tokens_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Run manually if PHP ensure has not altered yet:
-- ALTER TABLE pos_sales ADD COLUMN refunded_at DATETIME NULL, ADD COLUMN refund_reason VARCHAR(255) NULL;
