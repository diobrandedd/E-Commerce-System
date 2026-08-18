<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/dept_pouch_barcodes.php';

function api_dept_pouch_require_user(): array
{
    return api_require_ims_user();
}

function api_dept_pouch_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

function api_dept_pouch_require_ops(?string $productKey = null): array
{
    $user = api_dept_pouch_require_user();
    if (!dept_pouch_can_ops($user, $productKey)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_dept_pouch_require_ops_request(int $requestId): array
{
    try {
        $req = dept_pouch_request_get(ims_pdo(), $requestId);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }

    return api_dept_pouch_require_ops((string) ($req['product_key'] ?? ''));
}

function api_dept_pouch_require_ops_batch(int $batchId): array
{
    try {
        $batch = dept_pouch_batch_get(ims_pdo(), $batchId);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }

    return api_dept_pouch_require_ops((string) ($batch['product_key'] ?? ''));
}

/**
 * @param array<string, mixed> $in
 */
function api_dept_pouch_product_key(array $in = []): string
{
    $key = trim((string) ($in['product_key'] ?? $_GET['product_key'] ?? ''));
    if ($key === '') {
        api_fail('Missing product_key.', 422);
    }
    if (!dept_pouch_supports(strtolower($key))) {
        api_fail('Pouch barcodes are not enabled for this product.', 422);
    }

    return strtolower($key);
}

api_dispatch([
    'request_create' => 'api_dept_pouch_request_create',
    'request_list' => 'api_dept_pouch_request_list',
    'request_get' => 'api_dept_pouch_request_get',
    'request_cancel' => 'api_dept_pouch_request_cancel',
    'pending_count' => 'api_dept_pouch_pending_count',
    'pending_counts' => 'api_dept_pouch_pending_counts',
    'generate' => 'api_dept_pouch_generate',
    'batch_list' => 'api_dept_pouch_batch_list',
    'mark_printed' => 'api_dept_pouch_mark_printed',
    'print_sheet' => 'api_dept_pouch_print_sheet',
    'scan_stock_in' => 'api_dept_pouch_scan_stock_in',
    'scan_report' => 'api_dept_pouch_scan_report',
    'request_scan_log' => 'api_dept_pouch_request_scan_log',
    'pos_lookup' => 'api_dept_pouch_pos_lookup',
    'defect_scan' => 'api_dept_pouch_defect_scan',
    'defect_history' => 'api_dept_pouch_defect_history',
]);

function api_dept_pouch_request_create(): void
{
    api_method_allowed(['POST']);
    api_dept_pouch_require_csrf();
    $in = api_input();
    $productKey = api_dept_pouch_product_key($in);
    $user = api_dept_pouch_require_ops($productKey);
    $row = dept_pouch_request_create(
        ims_pdo(),
        (int) $user['id'],
        $productKey,
        (string) ($in['batch_no'] ?? ''),
        (int) ($in['quantity'] ?? 0),
        isset($in['notes']) ? (string) $in['notes'] : null
    );
    api_ok(['request' => $row]);
}

function api_dept_pouch_request_list(): void
{
    api_method_allowed(['GET']);
    $productKey = api_dept_pouch_product_key();
    api_dept_pouch_require_ops($productKey);
    $status = trim((string) ($_GET['status'] ?? 'all'));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    api_ok(dept_pouch_request_list(ims_pdo(), $productKey, $status, $page, 25));
}

function api_dept_pouch_request_get(): void
{
    api_method_allowed(['GET']);
    $id = (int) ($_GET['id'] ?? 0);
    api_dept_pouch_require_ops_request($id);
    api_ok(['request' => dept_pouch_request_get(ims_pdo(), $id)]);
}

function api_dept_pouch_request_cancel(): void
{
    api_method_allowed(['POST']);
    api_dept_pouch_require_csrf();
    $in = api_input();
    $id = (int) ($in['id'] ?? 0);
    $user = api_dept_pouch_require_ops_request($id);
    dept_pouch_request_cancel(
        ims_pdo(),
        $id,
        (int) $user['id'],
        (string) $user['role']
    );
    api_ok(['ok' => true]);
}

function api_dept_pouch_pending_count(): void
{
    api_method_allowed(['GET']);
    $productKey = api_dept_pouch_product_key();
    api_dept_pouch_require_ops($productKey);
    api_ok(['count' => dept_pouch_pending_request_count(ims_pdo(), $productKey)]);
}

function api_dept_pouch_pending_counts(): void
{
    api_method_allowed(['GET']);
    $user = api_dept_pouch_require_user();
    api_ok(['counts' => dept_pouch_pending_request_counts_for_user(ims_pdo(), $user)]);
}

function api_dept_pouch_generate(): void
{
    api_method_allowed(['POST']);
    api_dept_pouch_require_csrf();
    $in = api_input();
    $requestId = (int) ($in['request_id'] ?? 0);
    $user = api_dept_pouch_require_ops_request($requestId);
    $batch = dept_pouch_generate_for_request(
        ims_pdo(),
        (int) $user['id'],
        $requestId
    );
    api_ok(['batch' => $batch]);
}

function api_dept_pouch_batch_list(): void
{
    api_method_allowed(['GET']);
    $productKey = api_dept_pouch_product_key();
    api_dept_pouch_require_ops($productKey);
    api_ok(['rows' => dept_pouch_batch_list(ims_pdo(), $productKey)]);
}

function api_dept_pouch_mark_printed(): void
{
    api_method_allowed(['POST']);
    api_dept_pouch_require_csrf();
    $in = api_input();
    $batchId = (int) ($in['batch_id'] ?? 0);
    api_dept_pouch_require_ops_batch($batchId);
    api_ok(['batch' => dept_pouch_mark_printed(ims_pdo(), $batchId)]);
}

function api_dept_pouch_print_sheet(): void
{
    api_method_allowed(['GET']);
    $id = (int) ($_GET['id'] ?? 0);
    api_dept_pouch_require_ops_batch($id);
    header('Content-Type: text/html; charset=utf-8');
    echo dept_pouch_label_sheet_html(ims_pdo(), $id);
    exit;
}

function api_dept_pouch_scan_stock_in(): void
{
    api_method_allowed(['POST']);
    api_dept_pouch_require_csrf();
    $in = api_input();
    $productKey = api_dept_pouch_product_key($in);
    $user = api_dept_pouch_require_ops($productKey);
    $result = dept_pouch_scan_stock_in(
        ims_pdo(),
        (int) $user['id'],
        $productKey,
        (string) ($in['code'] ?? '')
    );
    api_ok($result);
}

function api_dept_pouch_request_scan_log(): void
{
    api_method_allowed(['GET']);
    $id = (int) ($_GET['request_id'] ?? $_GET['id'] ?? 0);
    api_dept_pouch_require_ops_request($id);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $scanDate = trim((string) ($_GET['scan_date'] ?? ''));
    try {
        api_ok(dept_pouch_request_scan_log(
            ims_pdo(),
            $id,
            $page,
            50,
            $scanDate !== '' ? $scanDate : null
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_dept_pouch_scan_report(): void
{
    api_method_allowed(['GET']);
    $productKey = api_dept_pouch_product_key();
    api_dept_pouch_require_ops($productKey);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $scanDate = trim((string) ($_GET['scan_date'] ?? ''));
    try {
        api_ok(dept_pouch_scan_report(
            ims_pdo(),
            $productKey,
            $scanDate !== '' ? $scanDate : null,
            $page,
            25
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_dept_pouch_pos_lookup(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    $code = (string) ($_GET['code'] ?? '');
    try {
        api_ok(['barcode' => dept_pouch_pos_lookup(ims_pdo(), $code)]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_dept_pouch_defect_scan(): void
{
    api_method_allowed(['POST']);
    api_dept_pouch_require_csrf();
    $in = api_input();
    $productKey = api_dept_pouch_product_key($in);
    $user = api_dept_pouch_require_ops($productKey);
    if (!dept_pouch_supports_defect($productKey)) {
        api_fail('Defect scan is not enabled for this product.', 422);
    }
    try {
        $result = dept_pouch_defect_scan(
            ims_pdo(),
            (int) $user['id'],
            $productKey,
            (string) ($in['code'] ?? ''),
            isset($in['note']) ? (string) $in['note'] : null
        );
        api_ok($result);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_dept_pouch_defect_history(): void
{
    api_method_allowed(['GET']);
    $productKey = api_dept_pouch_product_key();
    api_dept_pouch_require_ops($productKey);
    if (!dept_pouch_supports_defect($productKey)) {
        api_fail('Defect scan is not enabled for this product.', 422);
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    try {
        api_ok(dept_pouch_defect_history(ims_pdo(), $productKey, $page));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}
