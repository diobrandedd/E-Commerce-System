-- Phase 43: Allow Honey Comb supplier category (phase 42 dept product)
-- Run after phase 42.

SET NAMES utf8mb4;

ALTER TABLE `suppliers`
  MODIFY COLUMN `category` ENUM('honey', 'paragis', 'black_rice', 'cacao', 'honey_comb') NOT NULL;
