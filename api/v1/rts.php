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
    'inventory_use_rts' => 'api_rts_inventory_deduct_sell',
    'inventory_use_log' => 'api_rts_inventory_use_log',
    'inventory_set_honey' => 'api_rts_inventory_set_honey',
    'inventory_set_other' => 'api_rts_inventory_set_other',
    'stock_in_scan' => 'api_rts_stock_in_scan',
    'use_damaged' => 'api_rts_use_damaged',
    'damaged_use_log' => 'api_rts_damaged_use_log',
    'sync_status' => 'api_rts_sync_status_action',
]);

function api_rts_role_allowed(array $user): bool
{
    return ims_user_can($user, 'rts.view');
}

function api_rts_role_can_mutate(array $user): bool
{
    return ims_user_can($user, 'rts.record');
}

function api_rts_role_can_use_damaged(array $user): bool
{
    return ims_user_can($user, 'rts.damaged.use');
}

function rts_inventory_pool_sql(PDO $pdo, string $pool): string
{
    return rts_inventory_pool_filter_sql($pdo, $pool);
}

function api_rts_pool(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed($user)) {
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
    if (!api_rts_role_allowed($user)) {
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
    if (!api_rts_role_allowed($user)) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    $pool = isset($_GET['pool']) ? trim((string) $_GET['pool']) : 'sellable';
    if (!in_array($pool, ['sellable', 'damaged'], true)) {
        $pool = 'sellable';
    }
    $poolSql = rts_inventory_pool_sql($pdo, $pool);
    $honey = rts_inventory_list_honey_aggregated($pdo, $pool);
    $other = rts_inventory_list_other_aggregated($pdo, $pool);
    $lineRowCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM rts_inventory_rows WHERE 1=1' . $poolSql
    )->fetchColumn();
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
            'inventory_row_count' => $lineRowCount,
            'inventory_line_count' => $lineRowCount,
        ],
        'readonly' => !api_rts_role_can_mutate($user) && !ims_user_can($user, 'rts.admin'),
        'can_mutate' => api_rts_role_can_mutate($user),
        'can_use_damaged' => api_rts_role_can_use_damaged($user),
        'can_executive_edit' => ims_user_can($user, 'honey.executive_edit'),
        'can_use_rts_qty' => !empty($sync['sellable_from_cases']),
        'barcode_backed_sellable' => empty($sync['sellable_from_cases']),
        'pool' => $pool,
    ]);
}

function api_rts_stock_in_scan(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!api_rts_role_can_mutate($user)) {
        api_fail('Forbidden.', 403);
    }
    $code = trim((string) ($input['code'] ?? ''));
    if ($code === '') {
        api_fail('Barcode is required.');
    }
    $qty = isset($input['qty']) ? (int) $input['qty'] : null;
    require_once dirname(__DIR__, 2) . '/includes/honey_bottle_barcodes.php';
    require_once dirname(__DIR__, 2) . '/includes/dept_pouch_barcodes.php';
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    try {
        $sku = sku_barcode_fetch_by_code(ims_pdo(), $code);
        if ($sku !== null) {
            if ($qty === null || $qty < 1) {
                api_fail('Enter how many to stock into RTS for this SKU.', 422);
            }
            api_ok([
                'kind' => (string) $sku['kind'],
                'lookup_kind' => 'sku',
                'unit' => sku_barcode_rts_stock_in(
                    ims_pdo(),
                    (int) $user['id'],
                    (string) $sku['kind'],
                    (string) ($sku['variant'] ?? ''),
                    (string) ($sku['size'] ?? ''),
                    (string) ($sku['product_key'] ?? ''),
                    $qty
                ),
            ]);
        }
        $honeyRow = honey_bottle_fetch_code_row(ims_pdo(), $code);
        if ($honeyRow !== null) {
            api_ok([
                'kind' => 'honey',
                'lookup_kind' => 'unique_honey',
                'unit' => honey_bottle_rts_stock_in_scan(ims_pdo(), (int) $user['id'], $code),
            ]);
        }
        api_ok([
            'kind' => 'dept',
            'lookup_kind' => 'unique_dept',
            'unit' => dept_pouch_rts_stock_in_scan(ims_pdo(), (int) $user['id'], $code),
        ]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_rts_use_damaged(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!api_rts_role_can_use_damaged($user)) {
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

function api_rts_damaged_use_log(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed($user)) {
        api_fail('Forbidden.', 403);
    }
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    api_ok(['rows' => rts_damaged_use_events_list(ims_pdo(), $limit)]);
}

function api_rts_inventory_deduct_sell(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!api_rts_role_can_mutate($user)) {
        api_fail('Forbidden.', 403);
    }
    try {
        $rowId = (int) ($input['row_id'] ?? 0);
        $honeyVariant = trim((string) ($input['honey_variant'] ?? ''));
        $productLabel = trim((string) ($input['product_label'] ?? ''));
        $sizeQtys = is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : [];
        $qtyOther = isset($input['qty_other']) ? (float) $input['qty_other'] : null;
        $saleType = (string) ($input['sale_type'] ?? 'walk_in');
        $pool = trim((string) ($input['pool'] ?? 'sellable'));
        if (!in_array($pool, ['sellable', 'damaged'], true)) {
            $pool = 'sellable';
        }
        if ($pool === 'sellable' && !rts_sellable_from_cases_enabled(ims_pdo())) {
            api_fail('Qty Use RTS is disabled — scan RTS barcodes for packing or POS.', 400);
        }
        $useOpts = [
            'used_where' => 'packaging',
            'actor_user_id' => (int) $user['id'],
        ];

        if ($honeyVariant !== '') {
            rts_inventory_deduct_sell_honey_variant(
                ims_pdo(),
                (int) $user['id'],
                $honeyVariant,
                $sizeQtys,
                $saleType,
                $pool,
                $useOpts
            );
        } elseif ($productLabel !== '') {
            rts_inventory_deduct_sell_other_label(
                ims_pdo(),
                (int) $user['id'],
                $productLabel,
                (float) ($qtyOther ?? 0),
                $saleType,
                $pool,
                $useOpts
            );
        } else {
            rts_inventory_deduct_sell(
                ims_pdo(),
                (int) $user['id'],
                $rowId,
                $sizeQtys,
                $qtyOther,
                $saleType,
                $useOpts
            );
        }
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['sold' => true, 'used' => true]);
}

function api_rts_inventory_use_log(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_rts_role_allowed($user)) {
        api_fail('Forbidden.', 403);
    }
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
    api_ok(['rows' => rts_pool_use_log_list(ims_pdo(), $limit)]);
}

function api_rts_sync_status_action(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    api_ok(rts_inventory_sync_status(ims_pdo()));
}

function api_rts_inventory_set_honey(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('rts.inventory.set');
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = rts_inventory_set_honey_variant_totals(
            $pdo,
            (int) $user['id'],
            (string) ($input['variant'] ?? ''),
            is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : [],
            (string) ($input['pool'] ?? 'sellable')
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_rts_inventory_set_other(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('rts.inventory.set');
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = rts_inventory_set_other_label_total(
            $pdo,
            (int) $user['id'],
            (string) ($input['product_label'] ?? ''),
            (float) ($input['qty'] ?? -1),
            (string) ($input['pool'] ?? 'sellable')
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

