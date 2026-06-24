<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/online_orders_generator.php';
require_once __DIR__ . '/../../includes/shopee_spx_packing_list.php';

$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

if ($action === 'batch_download') {
    try {
        online_orders_serve_download(trim((string) ($_GET['token'] ?? '')));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 403);
    }
}

function api_online_orders_assert_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Not authenticated.', 401);
    }
    if (!online_orders_user_can_access($user)) {
        api_fail('Forbidden.', 403);
    }
    return $user;
}

function api_online_orders_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    $expected = api_csrf_token();
    if (!hash_equals($expected, $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

function api_online_orders_fail_pipeline(Throwable $e): void
{
    if ($e instanceof OnlineOrdersDuplicateTrackingException) {
        api_fail($e->getMessage(), 409, ['duplicates' => $e->duplicates]);
    }
    if ($e instanceof RuntimeException) {
        api_fail($e->getMessage(), 400);
    }
    throw $e;
}

function api_online_orders_mark_run_failed(PDO $pdo, int $runId): void
{
    $fail = $pdo->prepare('UPDATE csr_online_batch_runs SET status = ? WHERE id = ?');
    $fail->execute(['failed', $runId]);
}

api_dispatch([
    'batch_start' => 'api_online_orders_batch_start',
    'batch_get' => 'api_online_orders_batch_get',
    'batch_cancel' => 'api_online_orders_batch_cancel',
    'batch_finalize' => 'api_online_orders_batch_finalize',
    'lines_append' => 'api_online_orders_lines_append',
    'tool_vip_prepare' => 'api_online_orders_tool_vip_prepare',
    'tool_spx_prepare' => 'api_online_orders_tool_spx_prepare',
    'tool_excel_register' => 'api_online_orders_tool_excel_register',
    'run_unified_stamp' => 'api_online_orders_run_unified_stamp',
    'run_partner' => 'api_online_orders_run_partner',
    'run_vip_stamp' => 'api_online_orders_run_vip_stamp',
    'run_shopee_resolve' => 'api_online_orders_run_shopee_resolve',
    'run_shopee_stamp' => 'api_online_orders_run_shopee_stamp',
    'spx_import_packing_list' => 'api_online_orders_spx_import_packing_list',
    'packaging_stock' => 'api_online_orders_packaging_stock',
]);

function api_online_orders_batch_start(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        $batch = online_orders_batch_start($pdo, (int) $user['id']);
        api_ok(['batch' => $batch]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_batch_get(): void
{
    api_method_allowed(['GET']);
    api_online_orders_assert_access();

    $user = ims_current_user();
    $batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
    $pdo = ims_pdo();
    try {
        $batch = online_orders_batch_get($pdo, (int) $user['id'], $batchId > 0 ? $batchId : null);
        api_ok(['batch' => $batch]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_batch_cancel(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    if ($batchId < 1) {
        api_fail('batch_id required.', 400);
    }
    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        online_orders_batch_cancel($pdo, (int) $user['id'], $batchId);
        api_ok(null);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_batch_finalize(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    if ($batchId < 1) {
        api_fail('batch_id required.', 400);
    }
    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        $result = online_orders_batch_finalize($pdo, (int) $user['id'], $batchId);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_lines_append(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    $pipeline = trim((string) ($input['pipeline'] ?? ''));
    $lines = $input['lines'] ?? [];
    if ($batchId < 1 || !in_array($pipeline, ['partner', 'vip', 'shopee_spx'], true)) {
        api_fail('batch_id and valid pipeline required.', 400);
    }
    if (!is_array($lines)) {
        api_fail('lines must be an array.', 400);
    }

    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        online_orders_assert_active_batch($pdo, (int) $user['id'], $batchId);
        $runId = online_orders_run_start($pdo, $batchId, $pipeline);
        try {
            $added = online_orders_lines_append($pdo, $batchId, $runId, $pipeline, $lines);
        } catch (OnlineOrdersDuplicateTrackingException $e) {
            api_online_orders_mark_run_failed($pdo, $runId);
            throw $e;
        }
        online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'source' => 'manual_append']);
        api_ok(['lines_added' => $added, 'run_id' => $runId]);
    } catch (Throwable $e) {
        api_online_orders_fail_pipeline($e);
    }
}

function api_online_orders_tool_vip_prepare(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $input = api_input();
    $rows = $input['rows'] ?? [];
    if (!is_array($rows) || $rows === []) {
        api_fail('rows required.', 400);
    }

    try {
        $result = online_orders_tool_vip_prepare($rows);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_tool_spx_prepare(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $input = api_input();
    $rows = $input['rows'] ?? [];
    if (!is_array($rows) || $rows === []) {
        api_fail('rows required.', 400);
    }

    try {
        $result = online_orders_tool_spx_prepare($rows);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_tool_excel_register(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $input = api_input();
    $excelToken = trim((string) ($input['excel_token'] ?? ''));
    $excelTokens = is_array($input['excel_tokens'] ?? null) ? array_values($input['excel_tokens']) : [];
    if ($excelToken === '' && $excelTokens === []) {
        api_fail('excel_token required.', 400);
    }

    try {
        $result = online_orders_tool_excel_register($excelToken !== '' ? $excelToken : $excelTokens);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_run_unified_stamp(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    @ini_set('max_execution_time', '300');
    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    $pdfTokens = is_array($input['pdf_tokens'] ?? null) ? array_values($input['pdf_tokens']) : [];
    $excelToken = trim((string) ($input['excel_token'] ?? ''));
    $excelTokens = is_array($input['excel_tokens'] ?? null) ? array_values($input['excel_tokens']) : [];
    $pipeline = trim((string) ($input['pipeline'] ?? 'unified'));
    $partnerSeller = trim((string) ($input['partner_seller'] ?? ''));
    if ($batchId < 1) {
        api_fail('batch_id required.', 400);
    }
    if ($excelToken === '' && $excelTokens === []) {
        api_fail('excel_token required.', 400);
    }

    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        $excelInput = $excelToken !== '' ? $excelToken : $excelTokens;
        $result = online_orders_run_unified_stamp(
            $pdo,
            (int) $user['id'],
            $batchId,
            $pdfTokens,
            $excelInput,
            $pipeline,
            $partnerSeller !== '' ? $partnerSeller : null,
        );
        api_ok($result);
    } catch (Throwable $e) {
        api_online_orders_fail_pipeline($e);
    }
}

function api_online_orders_run_partner(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    @ini_set('max_execution_time', '300');
    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    $pdfTokens = is_array($input['pdf_tokens'] ?? null) ? array_values($input['pdf_tokens']) : [];
    $excelTokens = is_array($input['excel_tokens'] ?? null) ? array_values($input['excel_tokens']) : [];
    if ($batchId < 1) {
        api_fail('batch_id required.', 400);
    }

    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        $result = online_orders_run_partner_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelTokens);
        api_ok($result);
    } catch (Throwable $e) {
        api_online_orders_fail_pipeline($e);
    }
}

function api_online_orders_run_vip_stamp(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    @ini_set('max_execution_time', '300');
    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    $pdfTokens = is_array($input['pdf_tokens'] ?? null) ? array_values($input['pdf_tokens']) : [];
    $excelToken = trim((string) ($input['excel_token'] ?? ''));
    if ($batchId < 1 || $excelToken === '') {
        api_fail('batch_id and excel_token required.', 400);
    }

    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        $result = online_orders_run_vip_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);
        api_ok($result);
    } catch (Throwable $e) {
        api_online_orders_fail_pipeline($e);
    }
}

function api_online_orders_run_shopee_resolve(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    $rows = $input['rows'] ?? [];
    if ($batchId < 1 || !is_array($rows)) {
        api_fail('batch_id and rows required.', 400);
    }

    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        $result = online_orders_run_shopee_resolve($pdo, (int) $user['id'], $batchId, $rows);
        api_ok($result);
    } catch (Throwable $e) {
        api_online_orders_fail_pipeline($e);
    }
}

function api_online_orders_run_shopee_stamp(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    @ini_set('max_execution_time', '300');
    $input = api_input();
    $batchId = (int) ($input['batch_id'] ?? 0);
    $pdfTokens = is_array($input['pdf_tokens'] ?? null) ? array_values($input['pdf_tokens']) : [];
    $excelToken = trim((string) ($input['excel_token'] ?? ''));
    if ($batchId < 1 || $excelToken === '') {
        api_fail('batch_id and excel_token required.', 400);
    }

    $user = ims_current_user();
    $pdo = ims_pdo();
    try {
        $result = online_orders_run_shopee_stamp($pdo, (int) $user['id'], $batchId, $pdfTokens, $excelToken);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_spx_import_packing_list(): void
{
    api_method_allowed(['POST']);
    api_online_orders_assert_access();
    api_online_orders_require_csrf();

    @ini_set('max_execution_time', '300');
    $input = api_input();
    $excelToken = trim((string) ($input['excel_token'] ?? ''));
    $pdfTokens = is_array($input['pdf_tokens'] ?? null) ? array_values($input['pdf_tokens']) : [];
    if ($excelToken === '') {
        api_fail('excel_token required.', 400);
    }

    try {
        $result = online_orders_spx_import_packing_list($excelToken, $pdfTokens);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_online_orders_packaging_stock(): void
{
    api_method_allowed(['GET']);
    api_online_orders_assert_access();

    require_once dirname(__DIR__, 2) . '/includes/honey_inventory.php';
    api_ok(honey_packaging_total_inventory_snapshot(ims_pdo()));
}
