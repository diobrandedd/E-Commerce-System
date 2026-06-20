-- Phase 1 inventory auth patch: switch regular login identity to username + employee_id.
-- Run this only if you already executed migration_phase1_inventory_auth.sql before this update.

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `username` VARCHAR(64) NOT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `employee_id` VARCHAR(24) NULL AFTER `email`;

UPDATE `users`
SET `username` = CASE
    WHEN TRIM(COALESCE(`username`, '')) = '' THEN CONCAT('user_', `id`)
    ELSE `username`
END,
    `employee_id` = CASE
        WHEN TRIM(COALESCE(`employee_id`, '')) = '' THEN LPAD(`id`, 6, '0')
        ELSE `employee_id`
    END;

ALTER TABLE `users`
  MODIFY COLUMN `username` VARCHAR(64) NOT NULL,
  MODIFY COLUMN `employee_id` VARCHAR(24) NOT NULL;

CREATE UNIQUE INDEX `uq_users_username` ON `users` (`username`);
CREATE UNIQUE INDEX `uq_users_employee_id` ON `users` (`employee_id`);
