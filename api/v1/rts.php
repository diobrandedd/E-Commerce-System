<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/rts_inventory_sync.php';
require_once dirname(__DIR__, 2) . '/includes/rts_damaged_use.php';
require_once dirname(__DIR__, 2) . '/includes/honey_inventory.php';

/**
 * RTS bridge API.
 *
 * The legacy RTS module is built around `rts_cases` + `rts_line_items`
 * (with case status workflow, claim status, etc.).  Until the SPA
 * achieves full feature parity, this endpoint exposes summarised
 * pool / recent activity data so the SPA `RTSPage` is read-only
 * and links back to the legacy PHP pages for case creation/edits.
 *
 *   GET ?action=pool        -> aggregate balance per product
 *   GET ?action=recent      -> last 50 line items
 *   GET ?action=legacy_urls -> URLs for the legacy PHP screens
 */

api_dispatch([
    'pool' => 'api_rts_pool',
    'recent' => 'api_rts_recent',
    'legacy_urls' => 'api_rts_legacy_urls',
    'inventory_list' => 'api_rts_inventory_list',
    'inventory_deduct_sell' => 'api_rts_inventory_deduct_sell',
    'use_damaged' => 'api_rts_use_damaged',
    'sync_status' => 'api_rts_sync_status_action',
]);

function api_rts_role_allowed(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'team_leader', 'staff', 'hr', 'csr_tl', 'csr_staff'], true);
}

function api_rts_role_can_mutate(string $role): bool
{
    return $role === 'prod_manager';
}

function api_rts_role_can_use_damaged(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'team_leader'], true);
}

function rts_inventory_pool_sql(PDO $pdo, string $pool): string
{
    if (!rts_db_has_column($pdo, 'rts_inventory_rows', 'inventory_pool')) {
        return $pool === 'damaged' ? ' AND 1=0' : '';
    }
    $pool = $pool === 'damaged' ? 'damaged' : 'sellable';

    return " AND inventory_pool = '{$pool}'";
}

function api_rts_pool(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    try {
        $st = $pdo->query(
            'SELECT
                li.product_type,
                li.honey_size,
                li.honey_variant,
                COALESCE(SUM(li.quantity), 0) AS total_qty,
                COUNT(*) AS line_count
             FROM rts_line_items li
             INNER JOIN rts_cases c ON c.id = li.case_id
             WHERE c.voided_at IS NULL
             GROUP BY li.product_type, li.honey_size, li.honey_variant
             ORDER BY li.product_type ASC, li.honey_size ASC, li.honey_variant ASC'
        );
        $rows = $st ? $st->fetchAll() : [];
    } catch (Throwable) {
        // Table missing or older schema — return empty pool gracefully.
        $rows = [];
    }
    api_ok(['pool' => $rows]);
}

function api_rts_recent(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    try {
        $st = $pdo->query(
            "SELECT li.id AS line_id, li.case_id, li.product_type, li.honey_size, li.honey_variant,
                    li.combo_product, li.quantity, li.is_bonus, li.created_at,
                    c.tracking_number, c.shop, c.courier_final, c.claim_status,
                    c.date_opened, c.damaged_tag
             FROM rts_line_items li
             INNER JOIN rts_cases c ON c.id = li.case_id
             WHERE c.voided_at IS NULL
             ORDER BY li.created_at DESC, li.id DESC
             LIMIT 50"
        );
        $rows = $st ? $st->fetchAll() : [];
    } catch (Throwable) {
        $rows = [];
    }
    api_ok(['items' => $rows]);
}

function api_rts_legacy_urls(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    api_ok([
        'records' => '/rts/app/dist/rts/records',
        'create' => '/rts/app/dist/rts/record',
        'dashboard' => '/rts/app/dist/rts/dashboard',
    ]);
}

function api_rts_inventory_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    $pool = isset($_GET['pool']) ? trim((string) $_GET['pool']) : 'sellable';
    if (!in_array($pool, ['sellable', 'damaged'], true)) {
        $pool = 'sellable';
    }
    $poolSql = rts_inventory_pool_sql($pdo, $pool);
    $honey = $pdo->query(
        'SELECT * FROM rts_inventory_rows WHERE category = "honey"' . $poolSql . ' ORDER BY id DESC'
    )->fetchAll();
    $other = $pdo->query(
        'SELECT * FROM rts_inventory_rows WHERE category = "other"' . $poolSql . ' ORDER BY id DESC'
    )->fetchAll();
    $sync = rts_inventory_sync_status($pdo);
    $caseCount = (int) $pdo->query('SELECT COUNT(*) FROM rts_cases')->fetchColumn();
    $lineItemCount = (int) $pdo->query('SELECT COUNT(*) FROM rts_line_items')->fetchColumn();
    $syncableLineCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM rts_line_items li
         INNER JOIN rts_cases c ON c.id = li.case_id
         WHERE c.voided_at IS NULL'
    )->fetchColumn();
    api_ok([
        'honey' => $honey,
        'other' => $other,
        'sync' => $sync,
        'diagnostic' => [
            'case_count' => $caseCount,
            'line_item_count' => $lineItemCount,
            'syncable_line_item_count' => $syncableLineCount,
            'inventory_row_count' => count($honey) + count($other),
        ],
        'readonly' => !api_rts_role_can_mutate((string) $user['role']) && (string) $user['role'] !== 'executive',
        'can_mutate' => api_rts_role_can_mutate((string) $user['role']),
        'can_use_damaged' => api_rts_role_can_use_damaged((string) $user['role']),
        'can_executive_edit' => (string) $user['role'] === 'executive',
        'pool' => $pool,
    ]);
}

function api_rts_use_damaged(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!api_rts_role_can_use_damaged((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    try {
        $result = rts_damaged_use_apply(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['row_id'] ?? 0),
            (string) ($input['outcome'] ?? ''),
            $input
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_rts_inventory_deduct_sell(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!api_rts_role_can_mutate((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    try {
        rts_inventory_deduct_sell(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['row_id'] ?? 0),
            is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : [],
            isset($input['qty_other']) ? (float) $input['qty_other'] : null,
            (string) ($input['sale_type'] ?? 'walk_in')
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['sold' => true]);
}

function api_rts_sync_status_action(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    api_ok(rts_inventory_sync_status(ims_pdo()));
}
