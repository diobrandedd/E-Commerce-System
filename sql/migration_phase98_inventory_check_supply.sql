-- Phase 98: Inventory check supplies (packaging materials) kind
-- Run once: mysql -u root rts_system < sql/migration_phase98_inventory_check_supply.sql

SET NAMES utf8mb4;

ALTER TABLE `inventory_check_scans`
  MODIFY COLUMN `kind` ENUM('honey','dept','freebie','supply') NOT NULL;

ALTER TABLE `inventory_check_variance_lines`
  MODIFY COLUMN `kind` ENUM('honey','dept','freebie','supply') NOT NULL;
