-- Phase 79: grant sales.view_amounts to users who already have sales.view
-- (preserve current peso visibility; exec can revoke amounts later).

INSERT INTO user_permissions (user_id, permission_key, granted_by)
SELECT up.user_id, 'sales.view_amounts', up.granted_by
FROM user_permissions up
WHERE up.permission_key = 'sales.view'
  AND NOT EXISTS (
    SELECT 1
    FROM user_permissions x
    WHERE x.user_id = up.user_id
      AND x.permission_key = 'sales.view_amounts'
  );
