-- Phase 93: Inventory check freebie sample kind
-- Run once: mysql -u root rts_system < sql/migration_phase93_inventory_check_freebie.sql

SET NAMES utf8mb4;

ALTER TABLE `inventory_check_scans`
  MODIFY COLUMN `kind` ENUM('honey','dept','freebie') NOT NULL;

ALTER TABLE `inventory_check_variance_lines`
  MODIFY COLUMN `kind` ENUM('honey','dept','freebie') NOT NULL;
