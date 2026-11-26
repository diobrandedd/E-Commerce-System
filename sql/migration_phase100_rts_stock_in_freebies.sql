-- Phase 100: RTS stock-in sessions accept freebie barcodes (PT / BRC / Jar Cacao).
-- Freebie lines credit dept_raw_freebie_balances on confirm — never the RTS sellable pool.
-- Run once: php tools/apply_migration_phase100.php

SET NAMES utf8mb4;

ALTER TABLE `rts_stock_in_session_items`
  MODIFY COLUMN `kind` ENUM('honey','dept','freebie') NOT NULL;

ALTER TABLE `rts_stock_in_session_items`
  MODIFY COLUMN `lookup_kind` ENUM('sku','unique_honey','unique_dept','freebie') NOT NULL;
