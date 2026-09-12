<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/honey_bottle_barcodes.php';

function api_bottle_require_user(): array
{
    return api_require_ims_user();
}

function api_bottle_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

function api_bottle_require_request(): array
{
    $user = api_bottle_require_user();
    if (!honey_bottle_can_request($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

/** Read-only packaging scan report / lot options — honey.view is enough. */
function api_bottle_require_packaging_view(): array
{
    $user = api_bottle_require_user();
    if (!honey_bottle_can_view_packaging_report($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_bottle_require_generate(): array
{
    $user = api_bottle_require_user();
    if (!honey_bottle_user_can_generate($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_bottle_require_scan(): array
{
    $user = api_bottle_require_user();
    if (!honey_bottle_can_scan($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_bottle_require_pack_scan(): array
{
    $user = api_bottle_require_user();
    if (!honey_bottle_can_pack_scan($user) && !honey_bottle_can_scan($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

api_dispatch([
    'meta' => 'api_bottle_meta',
    'request_create' => 'api_bottle_request_create',
    'request_list' => 'api_bottle_request_list',
    'request_get' => 'api_bottle_request_get',
    'request_cancel' => 'api_bottle_request_cancel',
    'pending_count' => 'api_bottle_pending_count',
    'generate' => 'api_bottle_generate',
    'generate_batch' => 'api_bottle_generate_batch',
    'sku_print_list' => 'api_bottle_sku_print_list',
    'batch_list' => 'api_bottle_batch_list',
    'batch_get' => 'api_bottle_batch_get',
    'mark_printed' => 'api_bottle_mark_printed',
    'print_sheet' => 'api_bottle_print_sheet',
    'void' => 'api_bottle_void',
    'lookup' => 'api_bottle_lookup',
    'scan_stock_in' => 'api_bottle_scan_stock_in',
    'pack_start' => 'api_bottle_pack_start',
    'pack_get' => 'api_bottle_pack_get',
    'pack_session_list' => 'api_bottle_pack_session_list',
    'pack_today_stats' => 'api_bottle_pack_today_stats',
    'pack_scan' => 'api_bottle_pack_scan',
    'pack_remove_item' => 'api_bottle_pack_remove_item',
    'pack_set_item_qty' => 'api_bottle_pack_set_item_qty',
    'pack_confirm' => 'api_bottle_pack_confirm',
    'pack_discard' => 'api_bottle_pack_discard',
    'pos_lookup' => 'api_bottle_pos_lookup',
    'opened_return_scan' => 'api_bottle_opened_return_scan',
    'rts_receive' => 'api_bottle_rts_receive',
    'backfill_preview' => 'api_bottle_backfill_preview',
    'backfill_generate' => 'api_bottle_backfill_generate',
    'reconcile' => 'api_bottle_reconcile',
    'stock_summary' => 'api_bottle_stock_summary',
    'packaging_options' => 'api_bottle_packaging_options',
    'packaging_scan_report' => 'api_bottle_packaging_scan_report',
    'packaging_scan_log' => 'api_bottle_packaging_scan_log',
    'ensure_packaging_shell' => 'api_bottle_ensure_packaging_shell',
    'create_custom_packaging' => 'api_bottle_create_custom_packaging',
]);

function api_bottle_meta(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_request();
    api_ok([
        'variants' => HONEY_VARIANTS,
        'sizes' => array_map(
            static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],
            HONEY_SIZES
        ),
    ]);
}

function api_bottle_packaging_options(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_packaging_view();
    api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);
}

function api_bottle_packaging_scan_log(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_packaging_view();
    $packId = (int) ($_GET['packaging_row_id'] ?? 0);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $scanDate = trim((string) ($_GET['scan_date'] ?? ''));
    api_ok(honey_bottle_packaging_scan_log(
        ims_pdo(),
        $packId,
        $page,
        50,
        $scanDate !== '' ? $scanDate : null
    ));
}

function api_bottle_packaging_scan_report(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_packaging_view();
    $scanDate = trim((string) ($_GET['scan_date'] ?? ''));
    $bottlingDate = trim((string) ($_GET['bottling_date'] ?? ''));
    api_ok(honey_bottle_packaging_scan_report(
        ims_pdo(),
        $scanDate !== '' ? $scanDate : null,
        $bottlingDate !== '' ? $bottlingDate : null
    ));
}

function api_bottle_ensure_packaging_shell(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_generate();
    $in = api_input();
    $productionRowId = (int) ($in['production_row_id'] ?? 0);
    $before = honey_fetch_packaging_by_production(ims_pdo(), $productionRowId, date('Y-m-d'));
    $pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);
    api_ok([
        'packaging' => honey_bottle_serialize_packaging_option($pk, $before === null),
    ]);
}

function api_bottle_create_custom_packaging(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    // Honey staff (request) and CSR/exec (generate) both need this when old lots are gone.
    $user = api_bottle_require_user();
    $role = (string) $user['role'];
    if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {
        api_fail('Forbidden.', 403);
    }
    $in = api_input();
    $reused = false;
    $pk = honey_bottle_create_custom_packaging(
        ims_pdo(),
        (int) $user['id'],
        (string) ($in['supplier_name'] ?? ''),
        (string) ($in['variant'] ?? ''),
        (string) ($in['bottling_date'] ?? date('Y-m-d')),
        isset($in['code']) ? (string) $in['code'] : null,
        $reused
    );
    api_ok([
        'packaging' => honey_bottle_serialize_packaging_option($pk, !$reused),
        'reused' => $reused,
    ]);
}

function api_bottle_request_create(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_request();
    $in = api_input();
    $packId = isset($in['packaging_row_id']) ? (int) $in['packaging_row_id'] : 0;
    $row = honey_bottle_request_create(
        ims_pdo(),
        (int) $user['id'],
        (string) ($in['variant'] ?? ''),
        (string) ($in['size'] ?? ''),
        (int) ($in['quantity'] ?? 0),
        $packId > 0 ? $packId : null,
        isset($in['notes']) ? (string) $in['notes'] : null
    );
    api_ok(['request' => $row]);
}

function api_bottle_request_list(): void
{
    api_method_allowed(['GET']);
    $user = api_bottle_require_request();
    $mine = trim((string) ($_GET['mine'] ?? '')) === '1';
    $status = trim((string) ($_GET['status'] ?? 'all'));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $filterUser = $mine ? (int) $user['id'] : 0;
    api_ok(honey_bottle_request_list(ims_pdo(), $status, $filterUser, $page, 25));
}

function api_bottle_request_get(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_request();
    $id = (int) ($_GET['id'] ?? 0);
    api_ok(['request' => honey_bottle_request_get(ims_pdo(), $id)]);
}

function api_bottle_request_cancel(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_request();
    $in = api_input();
    honey_bottle_request_cancel(
        ims_pdo(),
        (int) ($in['id'] ?? 0),
        (int) $user['id'],
        (string) $user['role'],
        honey_bottle_user_dept_key($user)
    );
    api_ok(['ok' => true]);
}

function api_bottle_pending_count(): void
{
    api_method_allowed(['GET']);
    $user = api_bottle_require_user();
    if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {
        api_fail('Forbidden.', 403);
    }
    api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);
}

function api_bottle_generate(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_generate();
    $in = api_input();
    $requestId = (int) ($in['request_id'] ?? 0);
    if ($requestId < 1) {
        api_fail('request_id is required.');
    }
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    $req = honey_bottle_request_get(ims_pdo(), $requestId);
    $sku = sku_barcode_fetch_honey(ims_pdo(), (string) $req['variant'], (string) $req['size']);
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
        'honey',
        $requestId,
        null,
        null
    );
    ims_pdo()->prepare(
        'UPDATE honey_bottle_requests SET status = "barcodes_generated" WHERE id = ? AND status IN ("pending","barcodes_generated")'
    )->execute([$requestId]);
    api_ok([
        'sku' => sku_barcode_serialize($sku),
        'print_job_id' => $jobId,
        'print_url' => '/rts/api/v1/sku_barcodes.php?action=print_sheet&sku_id=' . (int) $sku['id'] . '&qty=' . $qty,
        'request' => honey_bottle_request_get(ims_pdo(), $requestId),
        'message' => 'Print identical SKU stickers — unique per-bottle codes are no longer generated.',
    ]);
}

function api_bottle_generate_batch(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_generate();
    $in = api_input();
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
        $req = honey_bottle_request_get($pdo, $requestId);
        if ((string) ($req['status'] ?? '') === 'cancelled') {
            continue;
        }
        $sku = sku_barcode_fetch_honey($pdo, (string) $req['variant'], (string) $req['size']);
        if ($sku === null) {
            api_fail(
                'No SKU barcode for ' . (string) $req['variant'] . ' ' . (string) $req['size_label']
                . '. Set it in Barcode Settings first.',
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
            'honey',
            $requestId,
            null,
            null
        );
        $pdo->prepare(
            'UPDATE honey_bottle_requests SET status = "barcodes_generated" WHERE id = ? AND status IN ("pending","barcodes_generated")'
        )->execute([$requestId]);
        $groups[] = [
            'sku_id' => (int) $sku['id'],
            'qty' => $qty,
            'banner' => 'BATCH ' . $letter,
        ];
        $requests[] = honey_bottle_request_get($pdo, $requestId);
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

function api_bottle_sku_print_list(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_generate();
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    api_ok(['rows' => sku_barcode_print_job_list(ims_pdo(), 'honey', (int) ($_GET['limit'] ?? 40))]);
}

function api_bottle_batch_list(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_request();
    $source = trim((string) ($_GET['source'] ?? ''));
    api_ok(['rows' => honey_bottle_batch_list(ims_pdo(), $source)]);
}

function api_bottle_batch_get(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_request();
    $id = (int) ($_GET['id'] ?? 0);
    api_ok(['batch' => honey_bottle_batch_get(ims_pdo(), $id)]);
}

function api_bottle_mark_printed(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    api_bottle_require_generate();
    $in = api_input();
    api_ok(['batch' => honey_bottle_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);
}

function api_bottle_print_sheet(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_generate();
    $id = (int) ($_GET['id'] ?? 0);
    if ($id < 1) {
        api_fail('id is required.');
    }
    $html = honey_bottle_label_sheet_html(ims_pdo(), $id);
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function api_bottle_void(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_generate();
    $in = api_input();
    honey_bottle_void_code(
        ims_pdo(),
        (string) ($in['code'] ?? ''),
        (int) $user['id'],
        (string) ($in['reason'] ?? '')
    );
    api_ok(['ok' => true]);
}

function api_bottle_lookup(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_scan();
    $code = (string) ($_GET['code'] ?? '');
    api_ok(['barcode' => honey_bottle_lookup(ims_pdo(), $code)]);
}

function api_bottle_scan_stock_in(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_scan();
    $in = api_input();
    $code = (string) ($in['code'] ?? '');
    $qty = isset($in['qty']) ? (int) $in['qty'] : null;
    $packId = isset($in['packaging_row_id']) ? (int) $in['packaging_row_id'] : 0;
    $customBatchNo = trim((string) ($in['batch_no'] ?? ''));
    $productionRowId = isset($in['production_row_id']) ? (int) $in['production_row_id'] : 0;
    require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';
    require_once dirname(__DIR__, 2) . '/includes/honey_inventory.php';
    $pdo = ims_pdo();
    $sku = sku_barcode_fetch_by_code($pdo, $code);
    if ($sku !== null) {
        if ((string) $sku['kind'] !== 'honey') {
            api_fail('This SKU is not a honey product. Use the dept scan-in page.', 422);
        }
        if ($qty === null || $qty < 1) {
            api_fail('Enter how many bottles to stock in for this SKU.', 422);
        }
        try {
            if ($productionRowId > 0) {
                $stocked = honey_production_bottle_stock_in(
                    $pdo,
                    (int) $user['id'],
                    $productionRowId,
                    (string) $sku['variant'],
                    (string) $sku['size'],
                    $qty
                );
                $payload = array_merge($stocked, [
                    'lookup_kind' => 'sku',
                    'code' => (string) $sku['code'],
                    'variant' => (string) $sku['variant'],
                    'size' => (string) $sku['size'],
                    'size_label' => honey_bottle_size_label((string) $sku['size']),
                ]);
                if (!empty($stocked['primary_request_id'])) {
                    require_once dirname(__DIR__, 2) . '/includes/honey_bottle_barcodes.php';
                    $payload['request'] = honey_bottle_request_get($pdo, (int) $stocked['primary_request_id']);
                }
                api_ok($payload);
            }
            if ($packId < 1 && $customBatchNo === '') {
                api_fail('Select a production lot or enter a custom batch number before stocking in SKU barcodes.', 422);
            }
            if ($packId < 1 && $customBatchNo !== '') {
                $reused = false;
                $customPack = honey_bottle_create_custom_packaging(
                    $pdo,
                    (int) $user['id'],
                    $customBatchNo,
                    (string) $sku['variant'],
                    date('Y-m-d'),
                    $customBatchNo,
                    $reused
                );
                $packId = (int) $customPack['id'];
            }
            $stocked = sku_barcode_honey_stock_in(
                $pdo,
                (int) $user['id'],
                (string) $sku['variant'],
                (string) $sku['size'],
                $qty,
                $packId
            );
            $payload = array_merge($stocked, [
                'lookup_kind' => 'sku',
                'code' => (string) $sku['code'],
                'variant' => (string) $sku['variant'],
                'size' => (string) $sku['size'],
                'size_label' => honey_bottle_size_label((string) $sku['size']),
            ]);
            if (!empty($stocked['primary_request_id'])) {
                require_once dirname(__DIR__, 2) . '/includes/honey_bottle_barcodes.php';
                $payload['request'] = honey_bottle_request_get($pdo, (int) $stocked['primary_request_id']);
            }
            api_ok($payload);
        } catch (Throwable $e) {
            api_fail($e->getMessage(), 422);
        }
    }
    $adopt = !empty($in['adopt']);
    if ($productionRowId > 0) {
        try {
            require_once dirname(__DIR__, 2) . '/includes/honey_bottle_barcodes.php';
            $pdo->beginTransaction();
            try {
                $scanned = honey_bottle_scan_stock_in($pdo, (int) $user['id'], $code, $adopt);
                $packFromScan = (int) ($scanned['packaging_row_id'] ?? 0);
                if ($packFromScan < 1) {
                    throw new RuntimeException('Barcode is not tied to a packaging batch.');
                }
                $deduct = honey_production_deduct_for_unique_stock_in(
                    $pdo,
                    (int) $user['id'],
                    $productionRowId,
                    $packFromScan,
                    (string) ($scanned['size'] ?? '')
                );
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
            api_ok(array_merge($scanned, $deduct, [
                'production_row_id' => $productionRowId,
            ]));
        } catch (Throwable $e) {
            api_fail($e->getMessage(), 422);
        }
    }
    api_ok(honey_bottle_scan_stock_in($pdo, (int) $user['id'], $code, $adopt));
}

function api_bottle_pack_start(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_pack_scan();
    api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);
}

function api_bottle_pack_get(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_pack_scan();
    $id = (int) ($_GET['id'] ?? 0);
    api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);
}

function api_bottle_pack_session_list(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_pack_scan();
    try {
        api_ok(honey_bottle_pack_session_list(
            ims_pdo(),
            isset($_GET['from']) ? (string) $_GET['from'] : null,
            isset($_GET['to']) ? (string) $_GET['to'] : null,
            isset($_GET['batch_no']) ? (string) $_GET['batch_no'] : null,
            (int) ($_GET['page'] ?? 1),
            isset($_GET['product_key']) ? (string) $_GET['product_key'] : null
        ));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bottle_pack_today_stats(): void
{
    api_method_allowed(['GET']);
    $user = api_bottle_require_pack_scan();
    api_ok(honey_bottle_pack_session_today_stats(ims_pdo(), (int) $user['id']));
}

function api_bottle_pack_scan(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    api_bottle_require_pack_scan();
    $in = api_input();
    api_ok(honey_bottle_pack_session_scan(
        ims_pdo(),
        (int) ($in['session_id'] ?? 0),
        (string) ($in['code'] ?? ''),
        isset($in['qty']) ? (int) $in['qty'] : null,
        isset($in['pool']) ? (string) $in['pool'] : null,
        isset($in['packaging_row_id']) ? (int) $in['packaging_row_id'] : null
    ));
}

function api_bottle_pack_remove_item(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    api_bottle_require_pack_scan();
    $in = api_input();
    try {
        api_ok(['session' => honey_bottle_pack_session_remove_item(
            ims_pdo(),
            (int) ($in['session_id'] ?? 0),
            (string) ($in['kind'] ?? 'honey'),
            (int) ($in['item_id'] ?? 0)
        )]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bottle_pack_set_item_qty(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    api_bottle_require_pack_scan();
    $in = api_input();
    try {
        api_ok(['session' => honey_bottle_pack_session_set_item_qty(
            ims_pdo(),
            (int) ($in['session_id'] ?? 0),
            (string) ($in['kind'] ?? 'honey'),
            (int) ($in['item_id'] ?? 0),
            (int) ($in['qty'] ?? 0)
        )]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_bottle_pack_confirm(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_pack_scan();
    $in = api_input();
    api_ok(['session' => honey_bottle_pack_session_confirm(
        ims_pdo(),
        (int) $user['id'],
        (int) ($in['session_id'] ?? 0)
    )]);
}

function api_bottle_pack_discard(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    api_bottle_require_pack_scan();
    $in = api_input();
    honey_bottle_pack_session_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));
    api_ok(['ok' => true]);
}

function api_bottle_pos_lookup(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    $code = (string) ($_GET['code'] ?? '');
    api_ok(['barcode' => honey_bottle_pos_lookup(ims_pdo(), $code)]);
}

function api_bottle_opened_return_scan(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_scan();
    $in = api_input();
    api_ok(['barcode' => honey_bottle_opened_return_scan(
        ims_pdo(),
        (int) $user['id'],
        (string) ($in['code'] ?? '')
    )]);
}

function api_bottle_rts_receive(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    api_require_ims_user();
    $in = api_input();
    $codes = $in['codes'] ?? [];
    if (!is_array($codes)) {
        api_fail('codes must be an array.');
    }
    $pool = (string) ($in['pool'] ?? 'rts_sellable');
    api_ok(honey_bottle_rts_receive(ims_pdo(), (int) api_require_ims_user()['id'], $codes, $pool));
}

function api_bottle_backfill_preview(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_generate();
    api_ok(honey_bottle_backfill_preview(ims_pdo()));
}

function api_bottle_backfill_generate(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_generate();
    $created = honey_bottle_backfill_generate(ims_pdo(), (int) $user['id']);
    api_ok(['batches' => $created, 'count' => count($created)]);
}

function api_bottle_reconcile(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_generate();
    api_ok(honey_bottle_reconcile(ims_pdo()));
}

function api_bottle_stock_summary(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_request();
    api_ok(['rows' => honey_bottle_stock_summary(ims_pdo())]);
}
