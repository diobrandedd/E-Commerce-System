<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/material_inventory.php';

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
    'raw_snapshot' => 'api_materials_raw_snapshot',
    'raw_detail' => 'api_materials_raw_detail',
    'departments' => 'api_materials_departments',
    'stock_in_lot' => 'api_materials_stock_in_lot',
    'packaging_detail' => 'api_materials_packaging_detail',
    'set_on_hand' => 'api_materials_set_on_hand',
]);

function api_materials_require_view(): array
{
    $user = api_require_ims_user();
    if (!material_inventory_role_can_view((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_materials_catalog_list(): void
{
    api_method_allowed(['GET']);
    api_materials_require_view();
    $category = trim((string) ($_GET['category'] ?? ''));
    $pdo = ims_pdo();
    $items = material_inventory_list(
        $pdo,
        $category !== '' ? $category : null,
        !isset($_GET['include_inactive']) || (string) $_GET['include_inactive'] !== '1'
    );
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
    $user = api_require_role(['executive']);
    $name = trim((string) ($input['name'] ?? ''));
    $deptId = (int) ($input['dept_id'] ?? 0);
    $category = trim((string) ($input['category'] ?? ''));
    $unit = trim((string) ($input['unit'] ?? 'pcs'));
    $pdo = ims_pdo();
    try {
        $row = material_inventory_create($pdo, (int) $user['id'], $name, $deptId, $category, $unit);
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
    api_require_role(['executive']);
    $stockId = (int) ($input['stock_id'] ?? 0);
    $name = isset($input['name']) ? trim((string) $input['name']) : null;
    $unit = isset($input['unit']) ? trim((string) $input['unit']) : null;
    $isActive = isset($input['is_active']) ? (bool) $input['is_active'] : null;
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    $pdo = ims_pdo();
    try {
        $row = material_inventory_update($pdo, $stockId, $name, $unit, $isActive);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    api_ok(['stock' => $row]);
}

function api_materials_adjust(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
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
        if ($role !== 'hr') {
            api_fail('Only HR can request packaging count corrections.', 403);
        }
        if ($action !== 'set_on_hand') {
            api_fail('Packaging requests must set a target on-hand count.', 422);
        }
    } elseif ($category === 'supply') {
        if ($role !== 'prod_manager') {
            api_fail('Only production managers can request supply adjustments.', 403);
        }
        if (!in_array($action, ['add', 'deduct'], true)) {
            api_fail('Supply requests must add or deduct quantity.', 422);
        }
    } else {
        api_fail('This stock cannot be adjusted via request.', 422);
    }
    try {
        $id = material_adjustment_submit($pdo, $stockId, $action, $amount, $reason, (int) $user['id']);
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
    $requests = material_adjustment_list($pdo, $requestedBy);
    api_ok([
        'requests' => $requests,
        'readonly' => false,
        'can_approve' => $role === 'executive',
        'can_submit_packaging' => material_inventory_role_can_request_packaging($role),
        'can_submit_supply' => $role === 'prod_manager',
    ]);
}

function api_materials_request_approve(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
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
    $user = api_require_role(['executive']);
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
    if ((string) $user['role'] !== 'executive') {
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
    api_require_role(['executive']);
    $id = (int) ($input['id'] ?? 0);
    material_adjustment_mark_viewed(ims_pdo(), $id);
    api_ok(['viewed' => true]);
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
    api_materials_require_view();
    $stockId = (int) ($_GET['stock_id'] ?? 0);
    if ($stockId < 1) {
        api_fail('stock_id required.', 422);
    }
    $pdo = ims_pdo();
    $row = material_inventory_fetch_stock($pdo, $stockId);
    if ($row === null || (string) $row['category'] !== 'packaging') {
        api_fail('Packaging stock not found.', 404);
    }
    $row['on_hand'] = round((float) ($row['on_hand'] ?? 0), 2);
    $row['current_price'] = round((float) ($row['current_price'] ?? 0), 2);
    $entries = material_packaging_recent_entries($pdo, $stockId, 25);
    api_ok([
        'stock' => $row,
        'entries' => $entries,
    ]);
}

function api_materials_set_on_hand(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive']);
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
    $pdo->beginTransaction();
    try {
        $result = material_packaging_set_on_hand(
            $pdo,
            $stockId,
            (float) $onHandRaw,
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

function api_materials_stock_in_lot(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_role(['executive', 'hr']);
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
        api_fail('Quantity received must be greater than 0.', 422);
    }

    $pdo = ims_pdo();
    $pdo->beginTransaction();
    try {
        $result = material_packaging_stock_in_lot(
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
