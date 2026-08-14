-- Never-reuse ledger for honey bottle sticker codes.
-- Run once: mysql -u root rts_system < sql/migration_phase55_honey_bottle_codes_issued.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `honey_bottle_codes_issued` (
  `code` VARCHAR(64) NOT NULL,
  `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `honey_bottle_codes_issued` (`code`)
SELECT `code` FROM `honey_bottle_barcodes`;
