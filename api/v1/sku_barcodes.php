<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/sku_barcodes.php';

/**
 * SKU barcode catalog + print sheets.
 *
 *   GET  ?action=list
 *   GET  ?action=lookup&code=
 *   GET  ?action=print_sheet&sku_id=&qty=
 *   GET  ?action=freebie_list
 *   GET  ?action=freebie_print_sheet&product_key=
 *   POST ?action=freebie_save_bulk -> { items: [{ product_key, code }] }
 *   POST ?action=save           -> { kind, variant?, size?, product_key?, code }
 *   POST ?action=save_bulk      -> { items: [{ kind, variant?, size?, product_key?, code, selling_price? }] }
 *   POST ?action=stock_in       -> { code|sku_id, qty, packaging_row_id?, pool? }
 *   POST ?action=print_record   -> { sku_id, qty, request_kind?, honey_request_id?, dept_request_id? }
 */

api_dispatch([
    'list' => 'api_sku_barcodes_list',
    'lookup' => 'api_sku_barcodes_lookup',
    'save' => 'api_sku_barcodes_save',
    'save_bulk' => 'api_sku_barcodes_save_bulk',
    'print_sheet' => 'api_sku_barcodes_print_sheet',
    'freebie_list' => 'api_sku_barcodes_freebie_list',
    'freebie_save_bulk' => 'api_sku_barcodes_freebie_save_bulk',
    'freebie_print_sheet' => 'api_sku_barcodes_freebie_print_sheet',
    'print_record' => 'api_sku_barcodes_print_record',
    'stock_in' => 'api_sku_barcodes_stock_in',
]);

function api_sku_barcodes_list(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Unauthorized', 401);
    }
    // Anyone authenticated can list (needed for print/request UIs); save is settings.manage.
    api_ok(['rows' => sku_barcode_list_settings(ims_pdo())]);
}

function api_sku_barcodes_lookup(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    $code = (string) ($_GET['code'] ?? '');
    try {
        api_ok(['lookup' => sku_barcode_lookup(ims_pdo(), $code)]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_sku_barcodes_save(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('settings.manage');
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Unauthorized', 401);
    }
    try {
        $row = sku_barcode_save(
            ims_pdo(),
            (int) $user['id'],
            (string) ($input['kind'] ?? ''),
            isset($input['variant']) ? (string) $input['variant'] : null,
            isset($input['size']) ? (string) $input['size'] : null,
            isset($input['product_key']) ? (string) $input['product_key'] : null,
            (string) ($input['code'] ?? '')
        );
        api_ok(['row' => $row]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_sku_barcodes_save_bulk(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('settings.manage');
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Unauthorized', 401);
    }
    $rawItems = $input['items'] ?? null;
    if (!is_array($rawItems)) {
        api_fail('items array required.', 422);
    }
    try {
        $rows = sku_barcode_save_bulk(ims_pdo(), (int) $user['id'], $rawItems);
        api_ok(['rows' => $rows, 'saved_count' => count($rows)]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_sku_barcodes_print_sheet(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    $pdo = ims_pdo();
    $linesRaw = trim((string) ($_GET['lines'] ?? ''));
    try {
        if ($linesRaw !== '') {
            $decoded = json_decode($linesRaw, true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Invalid lines JSON.');
            }
            $groups = [];
            foreach ($decoded as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $groups[] = [
                    'sku_id' => (int) ($line['sku_id'] ?? 0),
                    'qty' => (int) ($line['qty'] ?? 0),
                    'banner' => isset($line['banner']) ? (string) $line['banner'] : null,
                ];
            }
            $html = sku_barcode_combined_label_sheet_html($pdo, $groups);
        } else {
            $skuId = (int) ($_GET['sku_id'] ?? 0);
            $qty = (int) ($_GET['qty'] ?? 0);
            $html = sku_barcode_label_sheet_html($pdo, $skuId, $qty);
        }
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function api_sku_barcodes_freebie_list(): void
{
    api_method_allowed(['GET']);
    api_require_ims_user();
    require_once dirname(__DIR__, 2) . '/includes/dept_raw_freebies.php';
    api_ok(['rows' => dept_raw_freebie_barcode_list(ims_pdo())]);
}

function api_sku_barcodes_freebie_save_bulk(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    api_require_permission('settings.manage');
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Unauthorized', 401);
    }
    $rawItems = $input['items'] ?? null;
    if (!is_array($rawItems)) {
        api_fail('items array required.', 422);
    }
    require_once dirname(__DIR__, 2) . '/includes/dept_raw_freebies.php';
    try {
        $rows = dept_raw_freebie_barcode_save_bulk(ims_pdo(), (int) $user['id'], $rawItems);
        api_ok(['rows' => $rows]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_sku_barcodes_freebie_print_sheet(): void
{
    api_method_allowed(['GET']);
    api_require_permission('settings.manage');
    require_once dirname(__DIR__, 2) . '/includes/dept_raw_freebies.php';
    try {
        $html = dept_raw_freebie_print_sheet_html(ims_pdo(), (string) ($_GET['product_key'] ?? ''));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function api_sku_barcodes_print_record(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    // Print: honey generate OR dept barcodes OR rts labels OR settings
    if (
        !ims_user_can($user, 'honey.bottle.generate')
        && !ims_user_can($user, 'dept.pouch_barcodes')
        && !ims_user_can($user, 'rts.barcode_labels')
        && !ims_user_can($user, 'settings.manage')
        && (string) ($user['role'] ?? '') !== 'executive'
    ) {
        // Also allow any dept.*.barcodes
        $ok = ims_user_can($user, 'dept.paragis.barcodes')
            || ims_user_can($user, 'dept.brc.barcodes')
            || ims_user_can($user, 'dept.cacao.barcodes')
            || ims_user_can($user, 'dept.comb.barcodes');
        if (!$ok) {
            api_fail('Forbidden', 403);
        }
    }
    try {
        $jobId = sku_barcode_record_print(
            ims_pdo(),
            (int) $user['id'],
            (int) ($input['sku_id'] ?? 0),
            (int) ($input['qty'] ?? 0),
            (string) ($input['request_kind'] ?? 'ad_hoc'),
            isset($input['honey_request_id']) ? (int) $input['honey_request_id'] : null,
            isset($input['dept_request_id']) ? (int) $input['dept_request_id'] : null,
            isset($input['rts_label_job_id']) ? (int) $input['rts_label_job_id'] : null
        );
        $honeyReq = isset($input['honey_request_id']) ? (int) $input['honey_request_id'] : 0;
        if ($honeyReq > 0) {
            require_once dirname(__DIR__, 2) . '/includes/honey_bottle_barcodes.php';
            ims_pdo()->prepare(
                'UPDATE honey_bottle_requests SET status = "barcodes_generated" WHERE id = ? AND status IN ("pending","barcodes_generated")'
            )->execute([$honeyReq]);
        }
        $deptReq = isset($input['dept_request_id']) ? (int) $input['dept_request_id'] : 0;
        if ($deptReq > 0) {
            ims_pdo()->prepare(
                'UPDATE dept_pouch_requests SET status = "barcodes_generated" WHERE id = ? AND status IN ("pending","barcodes_generated")'
            )->execute([$deptReq]);
        }
        api_ok(['print_job_id' => $jobId, 'print_url' => '/rts/api/v1/sku_barcodes.php?action=print_sheet&sku_id='
            . (int) ($input['sku_id'] ?? 0) . '&qty=' . (int) ($input['qty'] ?? 0)]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}

function api_sku_barcodes_stock_in(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_require_ims_user();
    $pdo = ims_pdo();
    $qty = (int) ($input['qty'] ?? 0);
    $pool = (string) ($input['pool'] ?? 'packaging'); // packaging | rts_sellable | finished
    try {
        $sku = null;
        if (!empty($input['sku_id'])) {
            $sku = sku_barcode_fetch_by_id($pdo, (int) $input['sku_id']);
        } elseif (!empty($input['code'])) {
            $sku = sku_barcode_fetch_by_code($pdo, (string) $input['code']);
        }
        if ($sku === null) {
            api_fail('Unknown SKU barcode. Configure it in Barcode Settings.', 422);
        }
        if ($pool === 'rts_sellable') {
            if (!ims_user_can($user, 'rts.view') && (string) ($user['role'] ?? '') !== 'executive') {
                // allow same roles as RTS stock-in
            }
            $result = sku_barcode_rts_stock_in(
                $pdo,
                (int) $user['id'],
                (string) $sku['kind'],
                (string) ($sku['variant'] ?? ''),
                (string) ($sku['size'] ?? ''),
                (string) ($sku['product_key'] ?? ''),
                $qty
            );
        } elseif ((string) $sku['kind'] === 'honey') {
            $result = sku_barcode_honey_stock_in(
                $pdo,
                (int) $user['id'],
                (string) $sku['variant'],
                (string) $sku['size'],
                $qty,
                (int) ($input['packaging_row_id'] ?? 0)
            );
        } else {
            $result = sku_barcode_dept_stock_in(
                $pdo,
                (int) $user['id'],
                (string) $sku['product_key'],
                $qty
            );
        }
        api_ok(['stock_in' => $result, 'sku' => sku_barcode_serialize($sku)]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 422);
    }
}
