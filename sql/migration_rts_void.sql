-- Void RTS cases (row stays in list; same pattern as delivery_batch_items).
ALTER TABLE `rts_cases`
  ADD COLUMN `voided_at` DATETIME NULL DEFAULT NULL COMMENT 'admin voided; row kept for audit' AFTER `updated_at`,
  ADD COLUMN `voided_by_user_id` INT UNSIGNED NULL DEFAULT NULL AFTER `voided_at`,
  ADD KEY `idx_voided_at` (`voided_at`);

-- Optional FK (skip if your DB user cannot add constraints):
-- ALTER TABLE `rts_cases`
--   ADD CONSTRAINT `fk_rts_voided_by` FOREIGN KEY (`voided_by_user_id`) REFERENCES `app_users` (`id`) ON DELETE SET NULL;
