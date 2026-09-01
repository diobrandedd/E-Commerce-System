-- Phase 74: Manual seller name on POS sales (not inferred from login session).
SET NAMES utf8mb4;

SET @c := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pos_sales'
    AND COLUMN_NAME = 'seller_name'
);
SET @sql := IF(@c = 0,
  'ALTER TABLE `pos_sales` ADD COLUMN `seller_name` VARCHAR(160) NULL AFTER `buyer_credentials`',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pos_sales'
    AND COLUMN_NAME = 'seller_name'
    AND IS_NULLABLE = 'YES'
);
SET @sql := IF(@c > 0,
  'UPDATE `pos_sales` SET `seller_name` = COALESCE(NULLIF(TRIM(`seller_name`), \'\'), \'POS\') WHERE `seller_name` IS NULL OR TRIM(`seller_name`) = \'\'',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
