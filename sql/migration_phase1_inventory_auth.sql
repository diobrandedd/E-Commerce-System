-- Phase 1 auth foundation for Pyx Tracker Inventory modules.
-- Safe for existing RTS deployment: this adds new tables only.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(160) NULL,
  `employee_id` VARCHAR(24) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `dept_id` INT UNSIGNED NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_employee_id` (`employee_id`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_dept` (`dept_id`),
  KEY `idx_users_active` (`is_active`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_users_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pos_pins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pin_hash` VARCHAR(255) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `roles` (`name`) VALUES
  ('executive'),
  ('prod_manager'),
  ('tl'),
  ('staff'),
  ('pos');

INSERT IGNORE INTO `departments` (`name`) VALUES
  ('honey'),
  ('paragis'),
  ('cacao_brc'),
  ('csr');

-- Optional bootstrap from existing RTS app_users accounts.
-- Creates inventory users with synthetic local emails if they do not exist yet.
INSERT INTO `users` (`username`, `name`, `email`, `employee_id`, `password_hash`, `role_id`, `dept_id`, `is_active`)
SELECT
  a.username AS username,
  COALESCE(NULLIF(a.full_name, ''), a.username) AS name,
  CONCAT(LOWER(a.username), '@pyx.local') AS email,
  COALESCE(NULLIF(TRIM(a.employee_id), ''), LPAD(a.id, 6, '0')) AS employee_id,
  a.password_hash,
  r.id AS role_id,
  d.id AS dept_id,
  CASE WHEN a.status = 'active' THEN 1 ELSE 0 END AS is_active
FROM app_users a
INNER JOIN roles r ON r.name = CASE
  WHEN a.role IN ('executive', 'admin') THEN 'executive'
  WHEN a.role = 'production_manager' THEN 'prod_manager'
  WHEN a.role = 'team_leader' THEN 'tl'
  WHEN a.role = 'staff' THEN 'staff'
  WHEN a.role = 'delivery' THEN 'pos'
  ELSE 'staff'
END
LEFT JOIN departments d ON d.name = CASE
  WHEN a.department = 'honey' THEN 'honey'
  WHEN a.department = 'paragis' THEN 'paragis'
  WHEN a.department = 'csr' THEN 'csr'
  WHEN a.department = 'cacao_black_rice_coffee' THEN 'cacao_brc'
  ELSE NULL
END
WHERE NOT EXISTS (
  SELECT 1 FROM users u WHERE u.username = a.username
);
