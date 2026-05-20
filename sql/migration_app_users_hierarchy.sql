-- Expand app_users for account hierarchy + staff management.
-- Run on the target DB (e.g. rts_system).

ALTER TABLE `app_users`
  ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(120) NOT NULL DEFAULT '' AFTER `username`,
  ADD COLUMN IF NOT EXISTS `employee_id` VARCHAR(24) NULL AFTER `full_name`,
  ADD COLUMN IF NOT EXISTS `department` ENUM('csr','honey','cacao_black_rice_coffee','paragis') NULL AFTER `role`,
  ADD COLUMN IF NOT EXISTS `status` ENUM('active','inactive','awol','on_leave','resigned') NOT NULL DEFAULT 'active' AFTER `department`,
  ADD COLUMN IF NOT EXISTS `created_by_user_id` INT UNSIGNED NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `app_users`
  MODIFY COLUMN `role` ENUM('executive','production_manager','team_leader','staff','delivery','admin') NOT NULL;

UPDATE `app_users`
SET `full_name` = CASE
    WHEN TRIM(COALESCE(`full_name`, '')) = '' THEN `username`
    ELSE `full_name`
END,
    `status` = CASE
        WHEN `status` IS NULL OR TRIM(`status`) = '' THEN 'active'
        ELSE `status`
    END;

-- Promote legacy admin accounts to executive role.
UPDATE `app_users`
SET `role` = 'executive'
WHERE `role` = 'admin';

CREATE UNIQUE INDEX `uq_app_users_employee_id` ON `app_users` (`employee_id`);
CREATE INDEX `idx_app_users_role` ON `app_users` (`role`);
CREATE INDEX `idx_app_users_department` ON `app_users` (`department`);
CREATE INDEX `idx_app_users_status` ON `app_users` (`status`);

ALTER TABLE `app_users`
  ADD CONSTRAINT `fk_app_users_creator`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `app_users`(`id`) ON DELETE SET NULL;
