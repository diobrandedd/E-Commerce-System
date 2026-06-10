<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/honey_inventory.php';
require_once dirname(__DIR__, 2) . '/includes/honey_pricing.php';
require_once dirname(__DIR__, 2) . '/includes/dept_inventory.php';
require_once dirname(__DIR__, 2) . '/includes/dept_pricing.php';

api_dispatch([
    'warehouse_list' => 'api_honey_warehouse_list',
    'warehouse_stock_in' => 'api_honey_warehouse_stock_in',
    'warehouse_stock_out' => 'api_honey_warehouse_stock_out',
    'production_list' => 'api_honey_production_list',
    'production_stock_out' => 'api_honey_production_stock_out',
    'packaging_list' => 'api_honey_packaging_list',
    'packaging_total_inventory' => 'api_honey_packaging_total_inventory',
    'packaging_stock_out' => 'api_honey_packaging_stock_out',
    'traceability' => 'api_honey_traceability',
    'correction_submit' => 'api_honey_correction_submit',
    'correction_list' => 'api_honey_correction_list',
    'correction_row_options' => 'api_honey_correction_row_options',
    'correction_approve' => 'api_honey_correction_approve',
    'correction_reject' => 'api_honey_correction_reject',
    'correction_pending_count' => 'api_honey_correction_pending_count',
    'executive_adjust' => 'api_honey_executive_adjust',
    'executive_edit' => 'api_honey_executive_edit',
    'correction_unread_count' => 'api_honey_correction_unread_count',
    'correction_unread_list' => 'api_honey_correction_unread_list',
    'correction_mark_viewed' => 'api_honey_correction_mark_viewed',
    'dept_list' => 'api_honey_dept_list',
    'dept_catalog' => 'api_honey_dept_catalog',
    'dept_stock_in' => 'api_honey_dept_stock_in',
    'dept_add_row' => 'api_honey_dept_add_row',
    'supplier_honey_prices' => 'api_honey_supplier_honey_prices',
    'supplier_honey_prices_save' => 'api_honey_supplier_honey_prices_save',
    'supplier_dept_prices' => 'api_honey_supplier_dept_prices',
    'supplier_dept_prices_save' => 'api_honey_supplier_dept_prices_save',
    'warehouse_pending_prices' => 'api_honey_warehouse_pending_prices',
    'warehouse_set_unit_cost' => 'api_honey_warehouse_set_unit_cost',
    'pricing_sync_stocks' => 'api_honey_pricing_sync_stocks',
]);

function api_honey_require_view(): array
{
    $user = api_require_ims_user();
    if (!honey_role_can_view((string) $user['role']) && !honey_role_can_mutate((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_honey_require_mutate(): array
{
    $user = api_require_ims_user();
    if (!honey_role_can_mutate((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_honey_warehouse_list(): void
{
    api_method_allowed(['GET']);
    $user = api_honey_require_view();
    $role = (string) $user['role'];
    $pdo = ims_pdo();
    $costCol = honey_warehouse_unit_cost_column($pdo);
    $cols = $costCol !== null
        ? "id, supplier_id, supplier_name, supplier_code, arrival_date, variant, current_qty, initial_qty,
           {$costCol}, supplier_price_id, cost_override, created_at"
        : 'id, supplier_id, supplier_name, supplier_code, arrival_date, variant, current_qty, initial_qty, created_at';
    $st = $pdo->query(
        "SELECT {$cols} FROM honey_warehouse_rows ORDER BY arrival_date DESC, id DESC"
    );
    $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    if ($costCol !== null) {
        $rows = array_map(
            static fn (array $r) => honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($r)),
            $rows
        );
    }
    api_ok([
        'rows' => $rows,
        'can_view_cost' => honey_pricing_role_can_view_cost($role),
        'pending_count' => honey_warehouse_has_pricing_columns($pdo) ? honey_pricing_pending_count($pdo) : 0,
    ]);
}

function api_honey_warehouse_stock_in(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_mutate();
    $variants = is_array($input['variants'] ?? null) ? $input['variants'] : [];
    try {
        $result = honey_warehouse_stock_in(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['supplier_id'] ?? 0),
            (string) ($input['arrival_date'] ?? date('Y-m-d')),
            $variants
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_warehouse_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_mutate();
    try {
        $result = honey_warehouse_stock_out(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['warehouse_row_id'] ?? 0),
            (float) ($input['qty'] ?? 0)
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_production_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $pdo = ims_pdo();
    $st = $pdo->query(
        'SELECT pr.id, pr.warehouse_row_id, pr.supplier_code, pr.arrival_date, pr.variant,
                pr.current_qty, pr.initial_qty, pr.transferred_at, pr.created_at,
                pk.batch_no AS packaging_batch_no, pk.bottling_date AS packaging_bottling_date
         FROM honey_production_rows pr
         LEFT JOIN honey_packaging_rows pk ON pk.production_row_id = pr.id
         ORDER BY pr.transferred_at DESC, pr.id DESC'
    );
    api_ok(['rows' => $st ? $st->fetchAll() : []]);
}

function api_honey_production_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_mutate();
    try {
        $lines = honey_parse_bottle_lines_from_input($input);
        $result = honey_production_stock_out(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['production_row_id'] ?? 0),
            $lines
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_packaging_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $pdo = ims_pdo();
    $st = $pdo->query(
        'SELECT id, production_row_id, supplier_code, batch_no, bottling_date, variant,
                current_liter, initial_liter, current_250ml, initial_250ml,
                current_350ml, initial_350ml, current_gallon, initial_gallon, created_at
         FROM honey_packaging_rows ORDER BY bottling_date DESC, id DESC'
    );
    api_ok(['rows' => $st ? $st->fetchAll() : []]);
}

function api_honey_packaging_total_inventory(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));
}

function api_honey_packaging_stock_out(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_mutate();
    try {
        $result = honey_packaging_stock_out_sold(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['packaging_row_id'] ?? 0),
            is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : [],
            (string) ($input['sale_type'] ?? 'walk_in')
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_honey_traceability(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $q = trim((string) ($_GET['q'] ?? ''));
    api_ok(honey_traceability(ims_pdo(), $q));
}

function api_honey_correction_submit(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_mutate();
    $stage = (string) ($input['stage'] ?? '');
    $rowTable = (string) ($input['row_table'] ?? '');
    $rowId = (int) ($input['row_id'] ?? 0);
    $reason = trim((string) ($input['reason'] ?? ''));
    $correctionType = (string) ($input['correction_type'] ?? 'qty_delta');
    $action = (string) ($input['action'] ?? 'add');
    $amount = (float) ($input['amount'] ?? 0);
    $size = isset($input['size']) ? (string) $input['size'] : null;
    $newValue = isset($input['new_value']) ? trim((string) $input['new_value']) : null;

    if ($reason === '' || $rowId < 1) {
        api_fail('Invalid correction request.', 422);
    }

    if ($correctionType === 'qty_delta' && !in_array($action, ['add', 'deduct'], true)) {
        api_fail('Invalid action.', 422);
    }
    if ($correctionType === 'qty_delta' && $amount <= 0) {
        api_fail('Amount must be positive.', 422);
    }
    if (in_array($correctionType, ['variant', 'set_qty', 'supplier', 'packaging_size'], true) && ($newValue === null || $newValue === '')) {
        api_fail('New value is required for this correction type.', 422);
    }

    $pdo = ims_pdo();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO inventory_correction_requests (
                stage, row_table, row_id, correction_type, new_value, action, amount, size, reason, requested_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $stage,
            $rowTable,
            $rowId,
            $correctionType,
            $newValue,
            $action,
            $amount,
            $size ?: null,
            $reason,
            (int) $user['id'],
        ]);
    } catch (Throwable $e) {
        $ins = $pdo->prepare(
            'INSERT INTO inventory_correction_requests (stage, row_table, row_id, action, amount, size, reason, requested_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$stage, $rowTable, $rowId, $action, $amount, $size ?: null, $reason, (int) $user['id']]);
    }
    api_ok(['id' => (int) $pdo->lastInsertId()]);
}

function api_honey_correction_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!in_array($role, ['executive', 'prod_manager', 'hr'], true)) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    $sql = 'SELECT icr.*, u.name AS requested_by_name, r.name AS reviewed_by_name
            FROM inventory_correction_requests icr
            INNER JOIN users u ON u.id = icr.requested_by
            LEFT JOIN users r ON r.id = icr.reviewed_by';
    $params = [];
    if ($role === 'prod_manager') {
        $sql .= ' WHERE icr.requested_by = ?';
        $params[] = (int) $user['id'];
    }
    $sql .= ' ORDER BY icr.status = "pending" DESC, icr.created_at DESC LIMIT 500';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    foreach ($rows as &$row) {
        honey_correction_enrich($pdo, $row);
    }
    api_ok(['requests' => $rows, 'readonly' => $role === 'hr']);
}

function api_honey_correction_row_options(): void
{
    api_method_allowed(['GET']);
    api_honey_require_mutate();
    $stage = (string) ($_GET['stage'] ?? '');
    api_ok(['options' => honey_correction_row_options(ims_pdo(), $stage)]);
}

function api_honey_correction_pending_count(): void
{
    api_method_allowed(['GET']);
    api_honey_correction_unread_count();
}

function api_honey_correction_unread_count(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if ((string) $user['role'] !== 'executive') {
        api_ok(['count' => 0]);

        return;
    }
    $pdo = ims_pdo();
    $count = honey_correction_unread_count($pdo);
    if (is_file(dirname(__DIR__, 2) . '/includes/material_inventory.php')) {
        require_once dirname(__DIR__, 2) . '/includes/material_inventory.php';
        $count += material_adjustment_pending_count($pdo);
    }
    api_ok(['count' => $count]);
}

function api_honey_correction_unread_list(): void
{
    api_method_allowed(['GET']);
    api_require_role(['executive']);
    $limit = (int) ($_GET['limit'] ?? 10);
    $rows = honey_correction_unread_list(ims_pdo(), $limit);
    api_ok(['requests' => $rows]);
}

function api_honey_correction_mark_viewed(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid request id.', 422);
    }
    honey_correction_mark_viewed(ims_pdo(), $id);
    api_ok(['viewed' => true]);
}

function api_honey_correction_approve(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    $pdo = ims_pdo();
    $st = $pdo->prepare('SELECT * FROM inventory_correction_requests WHERE id = ? AND status = "pending" LIMIT 1');
    $st->execute([$id]);
    $req = $st->fetch();
    if (!$req) {
        api_fail('Request not found.', 404);
    }
    $pdo->beginTransaction();
    try {
        honey_apply_correction_request($pdo, $req);
        $pdo->prepare(
            'UPDATE inventory_correction_requests SET status = "approved", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?'
        )->execute([(int) $user['id'], $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['approved' => true]);
}

function api_honey_correction_reject(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    $note = trim((string) ($input['rejection_note'] ?? ''));
    if ($note === '') {
        api_fail('Rejection note is required.', 422);
    }
    $upd = ims_pdo()->prepare(
        'UPDATE inventory_correction_requests SET status = "rejected", rejection_note = ?, reviewed_by = ?, reviewed_at = NOW()
         WHERE id = ? AND status = "pending"'
    );
    $upd->execute([$note, (int) $user['id'], $id]);
    if ($upd->rowCount() < 1) {
        api_fail('Request not found.', 404);
    }
    api_ok(['rejected' => true]);
}

function api_honey_executive_adjust(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_role(['executive']);
    try {
        honey_executive_adjust(
            ims_pdo(),
            (string) ($input['stage'] ?? ''),
            (string) ($input['row_table'] ?? ''),
            (int) ($input['row_id'] ?? 0),
            (float) ($input['new_current'] ?? 0),
            isset($input['size']) ? (string) $input['size'] : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['adjusted' => true]);
}

function api_honey_executive_edit(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        honey_executive_edit_row(
            $pdo,
            (int) $user['id'],
            (string) ($input['stage'] ?? ''),
            (string) ($input['row_table'] ?? ''),
            (int) ($input['row_id'] ?? 0),
            is_array($input['fields'] ?? null) ? $input['fields'] : $input
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['saved' => true]);
}

function api_honey_dept_list(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $deptKey = trim((string) ($_GET['dept'] ?? ''));
    $productKey = trim((string) ($_GET['product_key'] ?? ''));
    $pdo = ims_pdo();
    $catalog = dept_inventory_catalog_snapshot(
        $pdo,
        $deptKey !== '' ? $deptKey : null,
        $productKey !== '' ? $productKey : null
    );
    $rows = array_map(static function (array $item): array {
        return [
            'id' => $item['dept_row_id'],
            'dept_id' => $item['dept_id'],
            'dept_name' => $item['dept'],
            'product_key' => $item['key'],
            'product_label' => $item['label'],
            'unit' => $item['unit'],
            'stock_in_unit' => $item['stock_in_unit'],
            'inventory_unit' => $item['inventory_unit'],
            'uses_supplier_pricing' => $item['uses_supplier_pricing'],
            'tea_bags_per_pouch' => $item['tea_bags_per_pouch'],
            'tea_bags_per_bag' => $item['tea_bags_per_bag'],
            'grams_per_pouch' => $item['grams_per_pouch'],
            'grams_per_bag' => $item['grams_per_bag'],
            'pouches_per_bag' => $item['pouches_per_bag'],
            'sell_units_per_bag' => $item['sell_units_per_bag'] ?? $item['pouches_per_bag'],
            'grams_per_sell_unit' => $item['grams_per_sell_unit'] ?? null,
            'current_qty' => $item['current_qty'],
            'initial_qty' => $item['initial_qty'],
            'last_stock_in_at' => $item['last_stock_in_at'],
            'product_id' => $item['product_id'],
            'selling_price' => $item['selling_price'],
        ];
    }, $catalog);
    api_ok(['rows' => $rows]);
}

function api_honey_dept_catalog(): void
{
    api_method_allowed(['GET']);
    api_honey_require_view();
    $deptKey = trim((string) ($_GET['dept'] ?? ''));
    $pdo = ims_pdo();
    api_ok(['products' => dept_inventory_catalog_snapshot($pdo, $deptKey !== '' ? $deptKey : null)]);
}

function api_honey_dept_stock_in(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_honey_require_mutate();
    $productKey = trim((string) ($input['product_key'] ?? ''));
    $qty = (float) ($input['qty'] ?? 0);
    $supplierId = isset($input['supplier_id']) ? (int) $input['supplier_id'] : null;
    $unitCost = isset($input['unit_cost']) ? (float) $input['unit_cost'] : null;
    $arrivalDate = trim((string) ($input['arrival_date'] ?? date('Y-m-d')));
    $note = trim((string) ($input['note'] ?? ''));
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = dept_inventory_stock_in(
            $pdo,
            (int) $user['id'],
            $productKey,
            $qty,
            $arrivalDate,
            $note !== '' ? $note : null,
            $supplierId,
            $unitCost
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

function api_honey_dept_add_row(): void
{
    api_method_allowed(['POST']);
    api_fail('Use dept_stock_in with a catalog product_key instead.', 410);
}

function api_honey_supplier_dept_prices(): void
{
    api_method_allowed(['GET']);
    api_require_role(['executive']);
    $supplierId = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
    $productKey = trim((string) ($_GET['product_key'] ?? ''));
    $pdo = ims_pdo();
    $prices = dept_pricing_list_supplier_prices(
        $pdo,
        $supplierId > 0 ? $supplierId : null,
        $productKey !== '' ? $productKey : null
    );

    $latestByKey = [];
    foreach ($prices as $p) {
        $key = (int) $p['supplier_id'] . '|' . (string) $p['product_key'];
        if (!isset($latestByKey[$key])) {
            $latestByKey[$key] = [
                'supplier_id' => (int) $p['supplier_id'],
                'product_key' => (string) $p['product_key'],
                'price_per_unit' => round((float) $p['price_per_unit'], 2),
                'effective_from' => (string) $p['effective_from'],
                'id' => (int) $p['id'],
            ];
        }
    }

    api_ok([
        'prices' => array_values($latestByKey),
        'history' => array_map(static function (array $p): array {
            return [
                'id' => (int) $p['id'],
                'supplier_id' => (int) $p['supplier_id'],
                'product_key' => (string) $p['product_key'],
                'price_per_unit' => round((float) $p['price_per_unit'], 2),
                'effective_from' => (string) $p['effective_from'],
                'supplier_name' => supplier_display_name((string) $p['first_name'], (string) $p['last_name']),
            ];
        }, $prices),
    ]);
}

function api_honey_supplier_dept_prices_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $supplierId = (int) ($input['supplier_id'] ?? 0);
    $effectiveFrom = trim((string) ($input['effective_from'] ?? date('Y-m-d')));
    $products = is_array($input['products'] ?? null) ? $input['products'] : [];

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $saved = dept_pricing_save_supplier_prices($pdo, $supplierId, $products, $effectiveFrom, (int) $user['id']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }

    api_ok(['saved' => $saved]);
}

function api_honey_supplier_honey_prices(): void
{
    api_method_allowed(['GET']);
    api_require_role(['executive']);
    $supplierId = isset($_GET['supplier_id']) ? (int) $_GET['supplier_id'] : 0;
    $pdo = ims_pdo();
    $prices = honey_pricing_list_supplier_prices($pdo, $supplierId > 0 ? $supplierId : null);

    $latestByKey = [];
    foreach ($prices as $p) {
        $key = (int) $p['supplier_id'] . '|' . (string) $p['variant'];
        if (!isset($latestByKey[$key])) {
            $latestByKey[$key] = [
                'supplier_id' => (int) $p['supplier_id'],
                'variant' => (string) $p['variant'],
                'price_per_gallon' => round((float) $p['price_per_gallon'], 2),
                'effective_from' => (string) $p['effective_from'],
                'id' => (int) $p['id'],
            ];
        }
    }

    api_ok([
        'prices' => array_values($latestByKey),
        'history' => array_map(static function (array $p) {
            return [
                'id' => (int) $p['id'],
                'supplier_id' => (int) $p['supplier_id'],
                'variant' => (string) $p['variant'],
                'price_per_gallon' => round((float) $p['price_per_gallon'], 2),
                'effective_from' => (string) $p['effective_from'],
                'supplier_name' => supplier_display_name((string) $p['first_name'], (string) $p['last_name']),
            ];
        }, $prices),
        'pending_count' => honey_pricing_pending_count($pdo),
    ]);
}

function api_honey_supplier_honey_prices_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $supplierId = (int) ($input['supplier_id'] ?? 0);
    $effectiveFrom = trim((string) ($input['effective_from'] ?? date('Y-m-d')));
    $variants = is_array($input['variants'] ?? null) ? $input['variants'] : [];

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $saved = honey_pricing_save_supplier_prices($pdo, $supplierId, $variants, $effectiveFrom, (int) $user['id']);
        $repriced = honey_pricing_reprice_pending_for_supplier($pdo, $supplierId, (int) $user['id']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }

    api_ok([
        'saved' => $saved,
        'repriced_rows' => $repriced,
        'pending_count' => honey_pricing_pending_count($pdo),
    ]);
}

function api_honey_warehouse_pending_prices(): void
{
    api_method_allowed(['GET']);
    api_require_role(['executive']);
    $pdo = ims_pdo();
    api_ok([
        'rows' => honey_pricing_pending_warehouse_rows($pdo),
        'pending_count' => honey_pricing_pending_count($pdo),
    ]);
}

function api_honey_warehouse_set_unit_cost(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    $warehouseRowId = (int) ($input['warehouse_row_id'] ?? 0);
    $raw = trim((string) ($input['unit_cost_per_gallon'] ?? ''));
    if ($warehouseRowId < 1) {
        api_fail('Invalid warehouse row.', 422);
    }
    if (!is_numeric($raw) || (float) $raw < 0) {
        api_fail('Unit cost per gallon must be 0 or greater.', 422);
    }

    try {
        $row = honey_pricing_set_warehouse_row_cost(ims_pdo(), $warehouseRowId, (float) $raw, (int) $user['id']);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }

    api_ok([
        'row' => $row,
        'pending_count' => honey_pricing_pending_count(ims_pdo()),
    ]);
}

function api_honey_pricing_sync_stocks(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
    try {
        $synced = honey_pricing_sync_all_variant_stocks(ims_pdo(), (int) $user['id']);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['synced' => $synced]);
}
