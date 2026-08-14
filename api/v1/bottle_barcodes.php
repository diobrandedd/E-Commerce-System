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
    if (!honey_bottle_can_request((string) $user['role'])) {
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
    if (!honey_bottle_can_scan((string) $user['role'])) {
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
    'batch_list' => 'api_bottle_batch_list',
    'batch_get' => 'api_bottle_batch_get',
    'mark_printed' => 'api_bottle_mark_printed',
    'print_sheet' => 'api_bottle_print_sheet',
    'void' => 'api_bottle_void',
    'lookup' => 'api_bottle_lookup',
    'scan_stock_in' => 'api_bottle_scan_stock_in',
    'pack_start' => 'api_bottle_pack_start',
    'pack_get' => 'api_bottle_pack_get',
    'pack_scan' => 'api_bottle_pack_scan',
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
    'ensure_packaging_shell' => 'api_bottle_ensure_packaging_shell',
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
    api_bottle_require_request();
    api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);
}

function api_bottle_ensure_packaging_shell(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_generate();
    $in = api_input();
    $productionRowId = (int) ($in['production_row_id'] ?? 0);
    $before = honey_fetch_packaging_by_production(ims_pdo(), $productionRowId);
    $pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);
    api_ok([
        'packaging' => honey_bottle_serialize_packaging_option($pk, $before === null),
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
    api_bottle_require_generate();
    api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);
}

function api_bottle_generate(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_generate();
    $in = api_input();
    $requestId = (int) ($in['request_id'] ?? 0);
    $packId = isset($in['packaging_row_id']) ? (int) $in['packaging_row_id'] : null;
    $productionRowId = isset($in['production_row_id']) ? (int) $in['production_row_id'] : null;
    if ($requestId < 1) {
        api_fail('request_id is required.');
    }
    $batch = honey_bottle_generate_for_request(
        ims_pdo(),
        (int) $user['id'],
        $requestId,
        $packId !== null && $packId > 0 ? $packId : null,
        $productionRowId !== null && $productionRowId > 0 ? $productionRowId : null
    );
    api_ok(['batch' => $batch]);
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
    $adopt = !empty($in['adopt']);
    api_ok(honey_bottle_scan_stock_in(ims_pdo(), (int) $user['id'], (string) ($in['code'] ?? ''), $adopt));
}

function api_bottle_pack_start(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_scan();
    api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);
}

function api_bottle_pack_get(): void
{
    api_method_allowed(['GET']);
    api_bottle_require_scan();
    $id = (int) ($_GET['id'] ?? 0);
    api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);
}

function api_bottle_pack_scan(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    api_bottle_require_scan();
    $in = api_input();
    api_ok(honey_bottle_pack_session_scan(
        ims_pdo(),
        (int) ($in['session_id'] ?? 0),
        (string) ($in['code'] ?? '')
    ));
}

function api_bottle_pack_confirm(): void
{
    api_method_allowed(['POST']);
    api_bottle_require_csrf();
    $user = api_bottle_require_scan();
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
    api_bottle_require_scan();
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
