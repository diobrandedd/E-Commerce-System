<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/online_orders_generator.php';

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

api_dispatch([
    'batch_start' => 'api_online_orders_batch_start',
    'batch_get' => 'api_online_orders_batch_get',
    'batch_cancel' => 'api_online_orders_batch_cancel',
    'batch_finalize' => 'api_online_orders_batch_finalize',
    'lines_append' => 'api_online_orders_lines_append',
    'run_partner' => 'api_online_orders_run_partner',
    'run_vip_stamp' => 'api_online_orders_run_vip_stamp',
    'run_shopee_resolve' => 'api_online_orders_run_shopee_resolve',
    'run_shopee_stamp' => 'api_online_orders_run_shopee_stamp',
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
        $added = online_orders_lines_append($pdo, $batchId, $runId, $pipeline, $lines);
        online_orders_run_complete($pdo, $runId, ['lines_added' => $added, 'source' => 'manual_append']);
        api_ok(['lines_added' => $added, 'run_id' => $runId]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
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
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    } catch (Throwable $e) {
        api_fail('Partner stamp failed: ' . $e->getMessage(), 500);
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
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
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
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
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
