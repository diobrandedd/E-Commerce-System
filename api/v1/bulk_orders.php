<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/bulk_honey_orders.php';

api_dispatch([
    'list' => 'api_bulk_orders_list',
    'get' => 'api_bulk_orders_get',
    'warehouse_rows' => 'api_bulk_orders_warehouse_rows',
    'suggest_allocations' => 'api_bulk_orders_suggest_allocations',
    'preview_cost' => 'api_bulk_orders_preview_cost',
    'create' => 'api_bulk_orders_create',
    'update' => 'api_bulk_orders_update',
    'confirm' => 'api_bulk_orders_confirm',
    'void' => 'api_bulk_orders_void',
    'delete' => 'api_bulk_orders_delete',
]);

function api_bulk_orders_require_executive(): array
{
    $user = api_require_ims_user();
    try {
        bulk_honey_orders_assert_executive($user);
    } catch (RuntimeException) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_bulk_orders_list(): void
{
    api_method_allowed(['GET']);
    api_bulk_orders_require_executive();
    $pdo = ims_pdo();
    if (!bulk_honey_orders_table_exists($pdo)) {
        api_ok(['orders' => [], 'missing_migration' => true]);
        return;
    }
    $status = isset($_GET['status']) ? (string) $_GET['status'] : 'all';
    api_ok(['orders' => bulk_honey_orders_list($pdo, $status)]);
}

function api_bulk_orders_get(): void
{
    api_method_allowed(['GET']);
    api_bulk_orders_require_executive();
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id < 1) {
        api_fail('Invalid order id.', 422);
    }
    try {
        api_ok(bulk_honey_order_get(ims_pdo(), $id));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_warehouse_rows(): void
{
    api_method_allowed(['GET']);
    api_bulk_orders_require_executive();
    $variant = (string) ($_GET['variant'] ?? '');
    try {
        api_ok(['rows' => bulk_honey_warehouse_rows_for_variant(ims_pdo(), $variant)]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_suggest_allocations(): void
{
    api_method_allowed(['GET', 'POST']);
    api_bulk_orders_require_executive();
    $input = $_SERVER['REQUEST_METHOD'] === 'POST' ? api_input() : $_GET;
    $variant = (string) ($input['variant'] ?? '');
    $qty = (float) ($input['qty_gallons'] ?? 0);
    try {
        api_ok([
            'allocations' => bulk_honey_suggest_fifo_allocations(ims_pdo(), $variant, $qty),
        ]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_preview_cost(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_bulk_orders_require_executive();
    $lines = is_array($input['lines'] ?? null) ? $input['lines'] : [];
    try {
        api_ok(bulk_honey_preview_cost(ims_pdo(), $lines));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_bulk_orders_require_executive();
    try {
        $result = bulk_honey_save_draft(ims_pdo(), (int) $user['id'], null, $input);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_update(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_bulk_orders_require_executive();
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid order id.', 422);
    }
    try {
        $result = bulk_honey_save_draft(ims_pdo(), (int) $user['id'], $id, $input);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_confirm(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_bulk_orders_require_executive();
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid order id.', 422);
    }
    try {
        api_ok(bulk_honey_order_confirm(ims_pdo(), (int) $user['id'], $id));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_void(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_bulk_orders_require_executive();
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid order id.', 422);
    }
    try {
        api_ok(bulk_honey_order_void(ims_pdo(), (int) $user['id'], $id));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bulk_orders_delete(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_bulk_orders_require_executive();
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
        api_fail('Invalid order id.', 422);
    }
    try {
        bulk_honey_order_delete_draft(ims_pdo(), $id);
        api_ok(['deleted' => true]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}
