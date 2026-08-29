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
    'generate_batch' => 'api_dept_pouch_generate_batch',
    'sku_print_list' => 'api_dept_pouch_sku_print_list',
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
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    $req = dept_pouch_request_get(ims_pdo(), $requestId);
    $productKey = (string) ($req['product_key'] ?? '');
    $sku = sku_barcode_fetch_dept(ims_pdo(), $productKey);
    if ($sku === null) {
        api_fail(
            'No SKU barcode configured for this product. Set it in System Settings → Barcode Settings first.',
            422
        );
    }
    $qty = (int) $req['quantity'];
    $jobId = sku_barcode_record_print(
        ims_pdo(),
        (int) $user['id'],
        (int) $sku['id'],
        $qty,
        'dept',
        null,
        $requestId,
        null
    );
    ims_pdo()->prepare(
        'UPDATE dept_pouch_requests SET status = "barcodes_generated" WHERE id = ? AND status IN ("pending","barcodes_generated")'
    )->execute([$requestId]);
    api_ok([
        'sku' => sku_barcode_serialize($sku),
        'print_job_id' => $jobId,
        'print_url' => '/rts/api/v1/sku_barcodes.php?action=print_sheet&sku_id=' . (int) $sku['id'] . '&qty=' . $qty,
        'request' => dept_pouch_request_get(ims_pdo(), $requestId),
        'message' => 'Print identical SKU stickers — unique per-unit codes are no longer generated.',
    ]);
}

function api_dept_pouch_generate_batch(): void
{
    api_method_allowed(['POST']);
    api_dept_pouch_require_csrf();
    $in = api_input();
    $productKey = api_dept_pouch_product_key($in);
    $user = api_dept_pouch_require_ops($productKey);
    $rawIds = $in['request_ids'] ?? null;
    if (!is_array($rawIds) || $rawIds === []) {
        api_fail('request_ids array is required.', 422);
    }
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    $pdo = ims_pdo();
    $groups = [];
    $batchIndex = 0;
    $requests = [];
    foreach ($rawIds as $rawId) {
        $requestId = (int) $rawId;
        if ($requestId < 1) {
            continue;
        }
        $req = dept_pouch_request_get($pdo, $requestId);
        if ((string) ($req['product_key'] ?? '') !== $productKey) {
            api_fail('Request does not match this product.', 422);
        }
        if ((string) ($req['status'] ?? '') === 'cancelled') {
            continue;
        }
        $sku = sku_barcode_fetch_dept($pdo, $productKey);
        if ($sku === null) {
            api_fail(
                'No SKU barcode configured for this product. Set it in System Settings → Barcode Settings first.',
                422
            );
        }
        $qty = (int) $req['quantity'];
        $batchIndex++;
        $letter = $batchIndex <= 26 ? chr(64 + $batchIndex) : (string) $batchIndex;
        sku_barcode_record_print(
            $pdo,
            (int) $user['id'],
            (int) $sku['id'],
            $qty,
            'dept',
            null,
            $requestId,
            null
        );
        $pdo->prepare(
            'UPDATE dept_pouch_requests SET status = "barcodes_generated" WHERE id = ? AND status IN ("pending","barcodes_generated")'
        )->execute([$requestId]);
        $groups[] = [
            'sku_id' => (int) $sku['id'],
            'qty' => $qty,
            'banner' => 'BATCH ' . $letter,
        ];
        $requests[] = dept_pouch_request_get($pdo, $requestId);
    }
    if ($groups === []) {
        api_fail('No valid requests to print.', 422);
    }
    api_ok([
        'print_url' => sku_barcode_print_sheet_url($groups),
        'requests' => $requests,
        'batch_count' => count($groups),
        'message' => 'Combined print sheet — ' . count($groups) . ' product batch(es).',
    ]);
}

function api_dept_pouch_sku_print_list(): void
{
    api_method_allowed(['GET']);
    $productKey = api_dept_pouch_product_key();
    api_dept_pouch_require_ops($productKey);
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    $rows = sku_barcode_print_job_list(ims_pdo(), 'dept', (int) ($_GET['limit'] ?? 40));
    $rows = array_values(array_filter(
        $rows,
        static fn (array $r): bool => (string) ($r['product_key'] ?? '') === $productKey
    ));
    api_ok(['rows' => $rows]);
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
    $code = (string) ($in['code'] ?? '');
    $qty = isset($in['qty']) ? (int) $in['qty'] : null;
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    $sku = sku_barcode_fetch_by_code(ims_pdo(), $code);
    if ($sku !== null) {
        if ((string) $sku['kind'] !== 'dept') {
            api_fail('This SKU is not a dept product.', 422);
        }
        if ((string) $sku['product_key'] !== $productKey) {
            api_fail('This SKU belongs to a different product.', 422);
        }
        if ($qty === null || $qty < 1) {
            api_fail('Enter how many to stock in for this SKU.', 422);
        }
        try {
            $stocked = sku_barcode_dept_stock_in(ims_pdo(), (int) $user['id'], $productKey, $qty);
            $payload = array_merge($stocked, [
                'lookup_kind' => 'sku',
                'code' => (string) $sku['code'],
            ]);
            if (!empty($stocked['primary_request_id'])) {
                $payload['request'] = dept_pouch_request_get(ims_pdo(), (int) $stocked['primary_request_id']);
            }
            api_ok($payload);
        } catch (Throwable $e) {
            api_fail($e->getMessage(), 422);
        }
    }
    $result = dept_pouch_scan_stock_in(
        ims_pdo(),
        (int) $user['id'],
        $productKey,
        $code
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
