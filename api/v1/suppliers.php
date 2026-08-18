<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/suppliers.php';

api_dispatch([
    'list' => 'api_suppliers_list',
    'get' => 'api_suppliers_get',
    'get_by_code' => 'api_suppliers_get_by_code',
    'create' => 'api_suppliers_create',
    'update' => 'api_suppliers_update',
    'activate' => 'api_suppliers_activate',
    'deactivate' => 'api_suppliers_deactivate',
]);

function api_suppliers_can_list(array $user): bool
{
    return ims_user_can($user, 'suppliers.view');
}

function api_suppliers_list(): void
{
    api_method_allowed(['GET']);
    $user = api_require_ims_user();
    if (!api_suppliers_can_list($user)) {
        api_fail('Forbidden.', 403);
    }

    $category = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
    $active = isset($_GET['active']) ? ((string) $_GET['active'] === '1' || (string) $_GET['active'] === 'true') : null;
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : null;

    $includePayment = ims_user_can($user, 'suppliers.manage');
    $rows = supplier_list(
        ims_pdo(),
        $category !== '' ? $category : null,
        $active,
        $q,
        $includePayment
    );

    api_ok(['suppliers' => $rows]);
}

function api_suppliers_get(): void
{
    api_method_allowed(['GET']);
    api_require_permission('suppliers.manage');
    $id = (int) ($_GET['id'] ?? 0);
    $row = supplier_fetch(ims_pdo(), $id);
    if (!$row) {
        api_fail('Supplier not found.', 404);
    }
    api_ok(['supplier' => supplier_serialize($row, true)]);
}

function api_suppliers_get_by_code(): void
{
    api_method_allowed(['GET']);
    api_require_permission('suppliers.manage');
    $code = trim((string) ($_GET['code'] ?? ''));
    if ($code === '') {
        api_fail('Supplier code is required.', 422);
    }
    $row = supplier_fetch_by_honey_code(ims_pdo(), $code);
    if (!$row) {
        api_fail('No supplier master record linked to this code.', 404);
    }
    api_ok(['supplier' => supplier_serialize($row, true), 'supplier_code' => $code]);
}

function api_suppliers_create(): void
{
    api_method_allowed(['POST']);
    $user = api_require_permission('suppliers.manage');
    api_require_csrf($_POST);
    $file = $_FILES['valid_id'] ?? $_FILES['valid_id_photo'] ?? null;
    if (!is_array($file)) {
        api_fail('Valid ID photo is required.', 400);
    }
    try {
        $supplier = supplier_create(ims_pdo(), (int) $user['id'], $_POST, $file);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['supplier' => $supplier]);
}

function api_suppliers_update(): void
{
    api_method_allowed(['POST']);
    $user = api_require_permission('suppliers.manage');
    api_require_csrf($_POST);
    $id = (int) ($_POST['id'] ?? 0);
    $file = $_FILES['valid_id'] ?? $_FILES['valid_id_photo'] ?? null;
    try {
        $supplier = supplier_update(
            ims_pdo(),
            $id,
            $_POST,
            is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $file : null
        );
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['supplier' => $supplier]);
}

function api_suppliers_activate(): void
{
    api_method_allowed(['POST']);
    api_require_permission('suppliers.manage');
    $input = api_input();
    api_require_csrf($input);
    try {
        $supplier = supplier_set_active(ims_pdo(), (int) ($input['id'] ?? 0), true);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['supplier' => $supplier]);
}

function api_suppliers_deactivate(): void
{
    api_method_allowed(['POST']);
    api_require_permission('suppliers.manage');
    $input = api_input();
    api_require_csrf($input);
    try {
        $supplier = supplier_set_active(ims_pdo(), (int) ($input['id'] ?? 0), false);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
    api_ok(['supplier' => $supplier]);
}
