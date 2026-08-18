<?php

declare(strict_types=1);

/**
 * Backfill user_permissions from current role + department presets.
 * Idempotent: replaces each user's permission set with the role preset.
 *
 * Usage: php tools/backfill_user_permissions.php [--dry-run]
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/permissions.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);

$pdo = ims_pdo();

$st = $pdo->query(
    "SELECT u.id, u.dept_id, r.name AS role_name, d.name AS dept_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     LEFT JOIN departments d ON d.id = u.dept_id
     WHERE u.is_active = 1
       AND r.name IN ('executive','prod_manager','tl','staff','hr')
     ORDER BY u.id ASC"
);
$rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];

$total = 0;
foreach ($rows as $row) {
    $userId = (int) $row['id'];
    $role = (string) $row['role_name'];
    $deptKey = isset($row['dept_name']) ? strtolower((string) $row['dept_name']) : null;
    $keys = ims_permission_preset_for_role($role, $deptKey);

    if ($dryRun) {
        echo "User {$userId} ({$role}" . ($deptKey ? "/{$deptKey}" : '') . "): " . count($keys) . " permissions\n";
        $total++;
        continue;
    }

    ims_user_permissions_set($userId, $keys, null);
    echo "User {$userId} ({$role}): granted " . count($keys) . " permissions\n";
    $total++;
}

echo ($dryRun ? '[DRY RUN] Would backfill ' : 'Backfilled ') . "{$total} users.\n";
