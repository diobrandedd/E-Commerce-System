-- RTS line items v2: product-type based with combo and bonus support
-- WARNING: This drops all existing line items data.
USE `rts_system`;

DROP TABLE IF EXISTS `rts_line_items`;

CREATE TABLE `rts_line_items` (
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
