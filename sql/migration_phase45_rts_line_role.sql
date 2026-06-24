-- Phase 45: RTS line role (reusable vs damaged) for leak split recording
SET NAMES utf8mb4;

ALTER TABLE `rts_line_items`
  ADD COLUMN `line_role` ENUM('reusable','damaged') NOT NULL DEFAULT 'reusable'
    COMMENT 'reusable=sellable RTS pool; damaged=damaged RTS pool (leak cases)'
    AFTER `bonus_parent_id`,
  ADD KEY `idx_rts_line_role` (`line_role`);
