-- RTS pool use log: where sellable RTS stock was used (POS vs Packaging / Use RTS).
CREATE TABLE IF NOT EXISTS `rts_pool_use_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_kind` VARCHAR(16) NOT NULL COMMENT 'honey|other',
  `variant` VARCHAR(64) NULL DEFAULT NULL,
  `size` VARCHAR(32) NULL DEFAULT NULL,
  `product_label` VARCHAR(255) NULL DEFAULT NULL,
  `qty` DECIMAL(12,4) NOT NULL,
  `used_where` VARCHAR(32) NOT NULL COMMENT 'pos|packaging',
  `pos_sale_id` INT UNSIGNED NULL DEFAULT NULL,
  `actor_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rts_pool_use_created` (`created_at`),
  KEY `idx_rts_pool_use_where` (`used_where`),
  KEY `idx_rts_pool_use_pos_sale` (`pos_sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
