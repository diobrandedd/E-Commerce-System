-- Phase 78: stock_entries.qty needs >2 decimal places for Paragis bag-pool pouch deducts.
-- 48 g / 1000 g bag = 0.048 bags; DECIMAL(12,2) rounded that to 0.05 (= 50 g).

SET NAMES utf8mb4;

ALTER TABLE `stock_entries`
  MODIFY COLUMN `qty` DECIMAL(14,6) NOT NULL;
