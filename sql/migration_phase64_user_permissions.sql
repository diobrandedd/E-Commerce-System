-- Per-user granular permissions (Phase 64)
-- Safe to run on production before code deploy — no existing tables altered.

CREATE TABLE IF NOT EXISTS user_permissions (
  user_id INT UNSIGNED NOT NULL,
  permission_key VARCHAR(64) NOT NULL,
  granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  granted_by INT UNSIGNED NULL,
  PRIMARY KEY (user_id, permission_key),
  CONSTRAINT fk_uperm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
