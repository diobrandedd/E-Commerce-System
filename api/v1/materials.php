<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/material_inventory.php';
require_once dirname(__DIR__, 2) . '/includes/fulfillment_packing.php';

api_dispatch([
    'catalog_list' => 'api_materials_catalog_list',
    'catalog_create' => 'api_materials_catalog_create',
    'catalog_update' => 'api_materials_catalog_update',
    'on_hand_list' => 'api_materials_on_hand_list',
    'adjust' => 'api_materials_adjust',
    'request_submit' => 'api_materials_request_submit',
    'request_list' => 'api_materials_request_list',
    'request_approve' => 'api_materials_request_approve',
    'request_reject' => 'api_materials_request_reject',
    'request_pending_count' => 'api_materials_request_pending_count',
    'request_mark_viewed' => 'api_materials_request_mark_viewed',
    'request_unread_list' => 'api_materials_request_unread_list',
    'raw_snapshot' => 'api_materials_raw_snapshot',
    'raw_detail' => 'api_materials_raw_detail',
    'departments' => 'api_materials_departments',
    'stock_in_lot' => 'api_materials_stock_in_lot',
    'packaging_detail' => 'api_materials_packaging_detail',
    'supply_detail' => 'api_materials_supply_detail',
    'set_on_hand' => 'api_materials_set_on_hand',
    'set_pool_yield' => 'api_materials_set_pool_yield',
    'set_foam_cacao_cut_yields' => 'api_materials_set_foam_cacao_cut_yields',
    'packaging_deduct' => 'api_materials_packaging_deduct',
    'packaging_report_damage' => 'api_materials_packaging_report_damage',
    'packaging_damage_totals' => 'api_materials_packaging_damage_totals',
    'packaging_catalog_remove' => 'api_materials_packaging_catalog_remove',
    'packaging_price_lot' => 'api_materials_packaging_price_lot',
    'packaging_print_sheet' => 'api_materials_packaging_print_sheet',
    'packaging_lookup_barcode' => 'api_materials_packaging_lookup_barcode',
    'packaging_access_roster' => 'api_materials_packaging_access_roster',
    'packaging_stock_out_history' => 'api_materials_packaging_stock_out_history',
    'supply_categories_list' => 'api_materials_supply_categories_list',
    'supply_category_create' => 'api_materials_supply_category_create',
    'supply_category_assign' => 'api_materials_supply_category_assign',
]);

function api_materials_require_view(): array
{
    $user = api_require_ims_user();
    if (!material_inventory_role_can_view($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_materials_catalog_list(): void
{
    api_method_allowed(['GET']);
    $category = trim((string) ($_GET['category'] ?? ''));
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!material_inventory_role_can_view($user)) {
        // For packing issuers (staff, TL, PM) need packaging catalog only — not full materials hub.
        if ($category !== 'packaging' || !material_inventory_role_can_issue_packing($user)) {
            api_fail('Forbidden.', 403);
        }
    }
    $pdo = ims_pdo();
    $items = material_inventory_list(
        $pdo,
        $category !== '' ? $category : null,
        !isset($_GET['include_inactive']) || (string) $_GET['include_inactive'] !== '1'
    );
    if (!ims_user_can($user, 'honey.pricing.view_cost')) {
        foreach ($items as &$row) {
            unset($row['current_price'], $row['supplier_id'], $row['supplier_name'], $row['supplier_code']);
        }
        unset($row);
    }
    api_ok(['items' => $items]);
}

function api_materials_on_hand_list(): void
{
    api_materials_catalog_list();
}

function api_materials_catalog_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $name = trim((string) ($input['name'] ?? ''));
    $deptId = (int) ($input['dept_id'] ?? 0);
    $category = trim((string) ($input['category'] ?? ''));
    $unit = trim((string) ($input['unit'] ?? 'pcs'));
    $supplyCategoryId = isset($input['supply_category_id']) ? (int) $input['supply_category_id'] : null;
    if ($supplyCategoryId !== null && $supplyCategoryId < 1) {
        $supplyCategoryId = null;
    }
    $supplyNote = array_key_exists('supply_note', $input) ? trim((string) $input['supply_note']) : null;
    if ($category === 'supply') {
        if (!material_inventory_role_can_create_supply_catalog($user)) {
            api_fail('Forbidden.', 403);
        }
    } elseif (!ims_user_can($user, 'packaging.catalog')) {
        api_fail('Forbidden.', 403);
    }
    $pdo = ims_pdo();
    try {
        $row = material_inventory_create(
            $pdo,
            (int) $user['id'],
            $name,
            $deptId,
            $category,
            $unit,
            $supplyCategoryId,
            $supplyNote
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['stock' => $row]);
}

function api_materials_catalog_update(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('packaging.catalog');
    $stockId = (int) ($input['stock_id'] ?? 0);
    $name = isset($input['name']) ? trim((string) $input['name']) : null;
    $unit = isset($input['unit']) ? trim((string) $input['unit']) : null;
    $isActive = isset($input['is_active']) ? (bool) $input['is_active'] : null;
    $setSupplyCategory = array_key_exists('supply_category_id', $input);
    $supplyCategoryId = $setSupplyCategory ? (int) ($input['supply_category_id'] ?? 0) : null;
    if ($supplyCategoryId !== null && $supplyCategoryId < 1) {
        $supplyCategoryId = null;
    }
    $setNote = array_key_exists('supply_note', $input);
    $supplyNote = $setNote ? trim((string) $input['supply_note']) : null;
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    $pdo = ims_pdo();
    try {
        $row = material_inventory_update(
            $pdo,
            $stockId,
            $name,
            $unit,
            $isActive,
            $setSupplyCategory,
            $supplyCategoryId,
            $setNote,
            $supplyNote
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['stock' => $row]);
}

function api_materials_supply_categories_list(): void
{
    api_method_allowed(['GET']);
    api_materials_require_view();
    api_ok(['categories' => material_supply_categories_list(ims_pdo())]);
}

function api_materials_supply_category_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('packaging.catalog');
    $name = trim((string) ($input['name'] ?? ''));
    try {
        $row = material_supply_category_create(ims_pdo(), (int) $user['id'], $name);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['category' => $row]);
}

function api_materials_supply_category_assign(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('packaging.catalog');
    $categoryId = (int) ($input['supply_category_id'] ?? 0);
    $stockIds = $input['stock_ids'] ?? [];
    if (!is_array($stockIds)) {
        api_fail('stock_ids must be a list.', 422);
    }
    try {
        $updated = material_supply_category_assign_many(ims_pdo(), $categoryId, $stockIds);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['updated' => $updated]);
}

function api_materials_adjust(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('packaging.adjust');
    $stockId = (int) ($input['stock_id'] ?? 0);
    $action = trim((string) ($input['action'] ?? ''));
    $amount = (float) ($input['amount'] ?? 0);
    $reason = trim((string) ($input['reason'] ?? ''));
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if ($reason === '') {
        api_fail('Reason is required.', 422);
    }
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = material_inventory_adjust($pdo, $stockId, $action, $amount, (int) $user['id'], $reason);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_materials_request_submit(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    $stockId = (int) ($input['stock_id'] ?? 0);
    $action = trim((string) ($input['action'] ?? ''));
    $amount = (float) ($input['amount'] ?? 0);
    $reason = trim((string) ($input['reason'] ?? ''));
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    $pdo = ims_pdo();
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null) {
        api_fail('Stock not found.', 404);
    }
    $category = (string) $row['category'];
    if ($category === 'packaging') {
        if (!material_inventory_role_can_request_packaging($user)) {
            api_fail('Only HR can request packaging count corrections.', 403);
        }
        if ($action !== 'set_on_hand') {
            api_fail('Packaging requests must set a target on-hand count.', 422);
        }
    } elseif ($category === 'supply') {
        if (!material_inventory_role_can_request_supply($user)) {
            api_fail('Only HR can submit supply issue or count requests.', 403);
        }
        if (!in_array($action, ['issue', 'set_on_hand'], true)) {
            api_fail('Supply requests must issue stock or set on-hand count.', 422);
        }
        if ($action === 'issue' && trim((string) ($input['requester_name'] ?? '')) === '') {
            api_fail('Requester name is required for supply issues.', 422);
        }
    } else {
        api_fail('This stock cannot be adjusted via request.', 422);
    }
    try {
        $id = material_adjustment_submit(
            $pdo,
            $stockId,
            $action,
            $amount,
            $reason,
            (int) $user['id'],
            isset($input['requester_name']) ? trim((string) $input['requester_name']) : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['id' => $id]);
}

function api_materials_request_list(): void
{
    api_method_allowed(['GET']);
    $user = api_materials_require_view();
    $role = (string) $user['role'];
    $pdo = ims_pdo();
    $requestedBy = in_array($role, ['prod_manager', 'hr'], true) ? (int) $user['id'] : null;
    $category = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
    // `action` is the API router param — use `request_action` for mar.action filter.
    $requestAction = isset($_GET['request_action']) ? trim((string) $_GET['request_action']) : null;
    $requests = material_adjustment_list($pdo, $requestedBy, $category ?: null, $requestAction ?: null);
    api_ok([
        'requests' => $requests,
        'readonly' => false,
        'can_approve' => ims_user_can($user, 'inventory.requests.approve'),
        'can_submit_packaging' => material_inventory_role_can_request_packaging($user),
        'can_submit_supply' => material_inventory_role_can_request_supply($user),
    ]);
}

function api_materials_request_approve(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('inventory.requests.approve');
    $id = (int) ($input['id'] ?? 0);
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        material_adjustment_approve($pdo, $id, (int) $user['id']);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok(['approved' => true]);
}

function api_materials_request_reject(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('inventory.requests.approve');
    $id = (int) ($input['id'] ?? 0);
    $note = trim((string) ($input['rejection_note'] ?? ''));
    $pdo = ims_pdo();
    try {
        material_adjustment_reject($pdo, $id, (int) $user['id'], $note);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['rejected' => true]);
}

function api_materials_request_pending_count(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'inventory.requests.approve')) {
        api_ok(['count' => 0]);

        return;
    }
    api_ok(['count' => material_adjustment_pending_count(ims_pdo())]);
}

function api_materials_request_mark_viewed(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('inventory.requests.approve');
    $id = (int) ($input['id'] ?? 0);
    material_adjustment_mark_viewed(ims_pdo(), $id);
    api_ok(['viewed' => true]);
}

function api_materials_request_unread_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_permission('inventory.requests.approve');
    unset($user);
    $limit = (int) ($_GET['limit'] ?? 10);
    $requests = material_adjustment_unread_list(ims_pdo(), $limit);
    api_ok(['requests' => $requests]);
}

function api_materials_raw_snapshot(): void
{
    api_method_allowed(['GET']);
    api_materials_require_view();
    api_ok(material_raw_snapshot(ims_pdo()));
}

function api_materials_raw_detail(): void
{
    api_method_allowed(['GET']);
    api_materials_require_view();
    $slug = trim((string) ($_GET['slug'] ?? ''));
    $row = material_inventory_raw_stock_by_slug(ims_pdo(), $slug);
    if ($row === null) {
        api_fail('Unknown raw material.', 404);
    }
    api_ok(['stock' => $row]);
}

function api_materials_departments(): void
{
    api_method_allowed(['GET']);
    api_materials_require_view();
    $st = ims_pdo()->query('SELECT id, name FROM departments ORDER BY name ASC');
    api_ok(['departments' => $st ? $st->fetchAll() : []]);
}

function api_materials_packaging_detail(): void
{
    api_method_allowed(['GET']);
    $user = api_materials_require_view();
    $role = (string) $user['role'];
    $stockId = (int) ($_GET['stock_id'] ?? 0);
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    $pdo = ims_pdo();
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null || (string) $row['category'] !== 'packaging') {
        api_fail('Packaging stock not found.', 404);
    }
    if (material_inventory_has_barcode_column($pdo)) {
        $row['barcode'] = material_packaging_ensure_barcode($pdo, $stockId);
    }
    $lot = material_packaging_last_lot_qty($pdo, $stockId);
    $row = material_inventory_enrich_packaging_row($row, $lot);
    $logLimit = ims_user_can($user, 'packaging.set_on_hand') ? 100 : 25;
    $entries = material_packaging_recent_entries($pdo, $stockId, $logLimit);
    $isGenericFoam = material_packaging_is_generic_foam_roll_name((string) $row['name']);
    $payload = [
        'stock' => $row,
        'entries' => $entries,
        'permissions' => [
            'can_stock_in' => material_inventory_role_can_stock_in_packaging($user),
            'can_edit_price' => material_inventory_role_can_edit_packaging_price($user),
            'can_deduct' => material_inventory_role_can_deduct_packaging($user),
            'can_set_on_hand' => material_inventory_role_can_set_packaging_on_hand($user),
            'can_set_pool_yield' => ims_user_can($user, 'packaging.set_on_hand') && !$isGenericFoam,
            'can_set_foam_cacao_cut_yields' => ims_user_can($user, 'packaging.set_on_hand') && $isGenericFoam,
            'can_view_extended_log' => ims_user_can($user, 'packaging.set_on_hand'),
            'can_remove_catalog' => ims_user_can($user, 'packaging.catalog'),
        ],
    ];
    if ($isGenericFoam) {
        $payload['foam_cacao_cut_yields'] = material_packaging_foam_cacao_cut_yields($pdo);
    }
    api_ok($payload);
}

function api_materials_packaging_catalog_remove(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('packaging.catalog');
    $stockId = (int) ($input['stock_id'] ?? 0);
    $password = (string) ($input['confirm_password'] ?? $input['password'] ?? '');
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (trim($password) === '') {
        api_fail('Removal password is required.', 422);
    }
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = material_packaging_catalog_remove($pdo, $stockId, $password);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $msg = $e->getMessage();
        $code = str_contains(strtolower($msg), 'password') ? 403 : 400;
        api_fail($msg, $code);
    }
    api_ok($result);
}

function api_materials_set_foam_cacao_cut_yields(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('packaging.set_on_hand');
    $stockId = (int) ($input['stock_id'] ?? 0);
    $nineRaw = trim((string) ($input['cuts_9x12'] ?? ''));
    $twelveRaw = trim((string) ($input['cuts_12_5x12'] ?? ''));
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (!is_numeric($nineRaw) || (float) $nineRaw <= 0 || !is_numeric($twelveRaw) || (float) $twelveRaw <= 0) {
        api_fail('Both cut yields must be greater than zero.', 422);
    }
    $pdo = ims_pdo();
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null || !material_packaging_is_generic_foam_roll_name((string) $row['name'])) {
        api_fail('Generic insulation foam roll not found.', 404);
    }
    $pdo->beginTransaction();
    try {
        $result = material_packaging_set_foam_cacao_cut_yields(
            $pdo,
            (float) $nineRaw,
            (float) $twelveRaw,
            (int) $user['id']
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok([
        'result' => $result,
        'foam_cacao_cut_yields' => material_packaging_foam_cacao_cut_yields($pdo),
    ]);
}

function api_materials_packaging_deduct(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!material_inventory_role_can_deduct_packaging($user)) {
        api_fail('Forbidden.', 403);
    }
    $stockId = (int) ($input['stock_id'] ?? 0);
    $qtyRaw = trim((string) ($input['qty'] ?? $input['amount'] ?? ''));
    $reason = trim((string) ($input['reason'] ?? ''));
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
        api_fail('Quantity must be greater than 0.', 422);
    }
    if ($reason === '') {
        api_fail('Reason is required.', 422);
    }
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = material_packaging_deduct_for_use(
            $pdo,
            $stockId,
            (float) $qtyRaw,
            (int) $user['id'],
            $reason
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

function api_materials_packaging_report_damage(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    if (!material_inventory_role_can_deduct_packaging($user)) {
        api_fail('Forbidden.', 403);
    }
    $stockId = (int) ($input['stock_id'] ?? 0);
    $qtyRaw = trim((string) ($input['qty'] ?? $input['amount'] ?? ''));
    $reason = trim((string) ($input['reason'] ?? ''));
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
        api_fail('Quantity must be greater than 0.', 422);
    }
    if ($reason === '') {
        api_fail('Reason is required.', 422);
    }
    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = material_packaging_report_damage(
            $pdo,
            $stockId,
            (float) $qtyRaw,
            (int) $user['id'],
            $reason
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

function api_materials_packaging_damage_totals(): void
{
    api_method_allowed(['GET']);
    api_materials_require_view();
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    if ($from === '' || $to === '') {
        api_fail('from and to (YYYY-MM-DD) are required.', 422);
    }
    try {
        $totals = material_packaging_damage_totals(ims_pdo(), $from, $to);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    $rows = [];
    foreach ($totals as $stockId => $qty) {
        $rows[] = ['stock_id' => (int) $stockId, 'qty' => (float) $qty];
    }
    api_ok([
        'from' => $from,
        'to' => $to,
        'totals' => $rows,
    ]);
}

function api_materials_set_pool_yield(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('packaging.set_on_hand');
    $stockId = (int) ($input['stock_id'] ?? 0);
    $yieldRaw = trim((string) ($input['pool_yield_qty'] ?? ''));
    $yieldUnit = isset($input['pool_yield_unit']) ? trim((string) $input['pool_yield_unit']) : null;
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (!is_numeric($yieldRaw) || (float) $yieldRaw <= 0) {
        api_fail('pool_yield_qty must be greater than zero.', 422);
    }
    $pdo = ims_pdo();
    $stockRow = material_inventory_fetch_stock($pdo, $stockId);
    if ($stockRow === null) {
        api_fail('Stock not found.', 404);
    }
    if (material_packaging_is_generic_foam_roll_name((string) $stockRow['name'])) {
        api_fail('Set Jar Cacao cut yields on the insulation foam roll instead.', 422);
    }
    $pdo->beginTransaction();
    try {
        $result = material_packaging_set_pool_yield(
            $pdo,
            $stockId,
            (float) $yieldRaw,
            $yieldUnit,
            (int) $user['id']
        );
        $pdo->commit();
        $row = material_inventory_fetch_stock($pdo, $stockId);
        api_ok([
            'result' => $result,
            'stock' => $row !== null ? material_inventory_enrich_packaging_row($row) : null,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
}

function api_materials_supply_detail(): void
{
    api_method_allowed(['GET']);
    $user = api_materials_require_view();
    $role = (string) $user['role'];
    $stockId = (int) ($_GET['stock_id'] ?? 0);
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    $pdo = ims_pdo();
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null || (string) $row['category'] !== 'supply') {
        api_fail('Supply stock not found.', 404);
    }
    $row['on_hand'] = round((float) ($row['on_hand'] ?? 0), 2);
    $row['current_price'] = round((float) ($row['current_price'] ?? 0), 2);
    $logLimit = ims_user_can($user, 'packaging.set_on_hand') ? 100 : 25;
    $entries = material_supply_recent_entries($pdo, $stockId, $logLimit);
    api_ok([
        'stock' => $row,
        'entries' => $entries,
        'permissions' => material_supply_detail_permissions($user),
    ]);
}

function api_materials_set_on_hand(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_permission('packaging.set_on_hand');
    $monthly = !empty($input['monthly_inventory'])
        || strtolower(trim((string) ($input['note'] ?? ''))) === 'monthly inventory';
    if ($monthly) {
        require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
        api_require_admin_override_password($input);
    }
    $stockId = (int) ($input['stock_id'] ?? 0);
    $onHandRaw = trim((string) ($input['on_hand'] ?? ''));
    $note = isset($input['note']) ? trim((string) $input['note']) : null;
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (!is_numeric($onHandRaw) || (float) $onHandRaw < 0) {
        api_fail('On-hand must be 0 or greater.', 422);
    }
    $pdo = ims_pdo();
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null) {
        api_fail('Stock not found.', 404);
    }
    $category = (string) $row['category'];
    $pdo->beginTransaction();
    try {
        if ($category === 'packaging') {
            $result = material_packaging_set_on_hand(
                $pdo,
                $stockId,
                (float) $onHandRaw,
                (int) $user['id'],
                $note
            );
        } elseif ($category === 'supply') {
            $result = material_supply_set_on_hand(
                $pdo,
                $stockId,
                (float) $onHandRaw,
                (int) $user['id'],
                $note
            );
        } else {
            throw new RuntimeException('Only packaging or supply items support count correction.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_materials_stock_in_lot(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $monthly = !empty($input['monthly_inventory'])
        || strtolower(trim((string) ($input['note'] ?? ''))) === 'monthly inventory';
    if ($monthly) {
        require_once dirname(__DIR__, 2) . '/includes/admin_override_password.php';
        api_require_admin_override_password($input);
    }
    $role = (string) $user['role'];
    $stockId = (int) ($input['stock_id'] ?? 0);
    $totalRaw = trim((string) ($input['lot_total_price'] ?? ''));
    $qtyRaw = trim((string) ($input['lot_qty'] ?? ''));
    $yieldRaw = isset($input['pool_yield_qty']) ? trim((string) $input['pool_yield_qty']) : '';
    $note = isset($input['note']) ? trim((string) $input['note']) : null;

    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (!is_numeric($totalRaw) || (float) $totalRaw < 0) {
        api_fail('Total price must be 0 or greater.', 422);
    }
    if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
        api_fail('Quantity received must be greater than 0.', 422);
    }
    $poolYieldQty = null;
    if ($yieldRaw !== '') {
        if (!is_numeric($yieldRaw) || (float) $yieldRaw <= 0) {
            api_fail('Pool yield must be greater than 0.', 422);
        }
        if (!in_array($role, ['executive', 'hr'], true)) {
            api_fail('Forbidden.', 403);
        }
        $poolYieldQty = (float) $yieldRaw;
    }

    $pdo = ims_pdo();
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null) {
        api_fail('Stock not found.', 404);
    }
    $category = (string) $row['category'];
    if (!in_array($category, ['packaging', 'supply'], true)) {
        api_fail('Only packaging or supply items support lot stock-in.', 422);
    }
    if ($category === 'packaging' && !material_inventory_role_can_stock_in_packaging($user)) {
        api_fail('Forbidden.', 403);
    }
    if ($category === 'supply' && !in_array($role, ['executive', 'hr'], true)) {
        api_fail('Forbidden.', 403);
    }
    $pdo->beginTransaction();
    try {
        if ($category === 'packaging') {
            $result = material_packaging_stock_in_lot(
                $pdo,
                $stockId,
                (float) $totalRaw,
                (float) $qtyRaw,
                (int) $user['id'],
                $note,
                $poolYieldQty
            );
        } else {
            $result = material_supply_stock_in_lot(
                $pdo,
                $stockId,
                (float) $totalRaw,
                (float) $qtyRaw,
                (int) $user['id'],
                $note
            );
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        api_fail($e->getMessage(), 400);
    }
    api_ok($result);
}

function api_materials_packaging_price_lot(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $role = (string) $user['role'];
    if (!material_inventory_role_can_edit_packaging_price($user)) {
        api_fail('Forbidden.', 403);
    }
    $stockId = (int) ($input['stock_id'] ?? 0);
    $totalRaw = trim((string) ($input['lot_total_price'] ?? ''));
    $qtyRaw = trim((string) ($input['lot_qty'] ?? ''));
    $note = isset($input['note']) ? trim((string) $input['note']) : null;

    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    if (!is_numeric($totalRaw) || (float) $totalRaw < 0) {
        api_fail('Total price must be 0 or greater.', 422);
    }
    if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) {
        api_fail('Invoice quantity must be greater than 0.', 422);
    }

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = material_packaging_record_invoice_price(
            $pdo,
            $stockId,
            (float) $totalRaw,
            (float) $qtyRaw,
            (int) $user['id'],
            $note
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

function api_materials_packaging_print_sheet(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'packaging.view') && !material_inventory_role_can_issue_packing($user)) {
        api_fail('Forbidden.', 403);
    }
    $html = material_packaging_print_sheet_html(ims_pdo());
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo $html;
    exit;
}

function api_materials_packaging_lookup_barcode(): void
{
    api_method_allowed(['GET', 'POST']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'packaging.view') && !material_inventory_role_can_deduct_packaging($user)) {
        api_fail('Forbidden.', 403);
    }
    $input = api_input();
    $code = trim((string) ($input['code'] ?? $_GET['code'] ?? ''));
    if ($code === '') {
        api_fail('Barcode code is required.', 422);
    }
    $row = material_packaging_lookup_by_barcode(ims_pdo(), $code);
    if ($row === null) {
        api_fail('No packaging material matches that barcode.', 404);
    }
    api_ok([
        'stock' => $row,
        'permissions' => [
            'can_deduct' => material_inventory_role_can_deduct_packaging($user),
            'can_stock_in' => material_inventory_role_can_stock_in_packaging($user),
        ],
    ]);
}

function api_materials_packaging_access_roster(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'packaging.view')) {
        api_fail('Forbidden.', 403);
    }
    api_ok(material_packaging_access_roster(ims_pdo()));
}

function api_materials_packaging_stock_out_history(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!ims_user_can($user, 'packaging.view')) {
        api_fail('Forbidden.', 403);
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 50)));
    $filters = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'stock_id' => (int) ($_GET['stock_id'] ?? 0),
        'user_id' => (int) ($_GET['user_id'] ?? 0),
        'from' => trim((string) ($_GET['from'] ?? '')),
        'to' => trim((string) ($_GET['to'] ?? '')),
    ];
    api_ok(material_packaging_stock_out_history(ims_pdo(), $filters, $page, $perPage));
}
