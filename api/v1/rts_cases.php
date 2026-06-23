<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/rts_case_handlers.php';
require_once dirname(__DIR__, 2) . '/includes/rts_inventory_sync.php';

api_dispatch([
    'case_get' => 'api_rts_cases_get',
    'case_create' => 'api_rts_cases_create',
    'case_append_lines' => 'api_rts_cases_append_lines',
    'case_update' => 'api_rts_cases_update',
    'case_void' => 'api_rts_cases_void',
    'case_update_claim' => 'api_rts_cases_update_claim',
    'cases_list' => 'api_rts_cases_list',
    'list_swapped' => 'api_rts_cases_list_swapped',
    'list_leaks' => 'api_rts_cases_list_leaks',
    'tracking_prefill' => 'api_rts_cases_tracking_prefill',
    'dashboard' => 'api_rts_cases_dashboard',
    'product_catalog' => 'api_rts_cases_product_catalog',
    'delivery_match' => 'api_rts_cases_delivery_match',
    'documentation_upload' => 'api_rts_cases_documentation_upload',
]);

function api_rts_cases_role_allowed(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'staff', 'hr', 'csr_tl', 'csr_staff'], true);
}

function api_rts_cases_role_can_record(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'csr_tl', 'csr_staff'], true);
}

function api_rts_cases_role_can_admin(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager'], true);
}

function api_rts_cases_require_allowed(): array
{
    $user = api_require_ims_user();
    if (!api_rts_cases_role_allowed((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

function api_rts_cases_fail_exception(RtsCaseException $e): void
{
    $payload = ['error' => $e->errorCode, 'message' => $e->getMessage()];
    if ($e->payload !== null) {
        $payload = array_merge($payload, $e->payload);
    }
    api_fail($e->getMessage(), $e->httpStatus, $payload);
}

function api_rts_cases_maybe_sync(PDO $pdo): void
{
    try {
        rts_inventory_sync_run($pdo);
    } catch (Throwable) {
        // Non-fatal after case save
    }
}

function api_rts_cases_get(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    $tracking = trim((string) ($_GET['tracking'] ?? ''));
    try {
        api_ok(rts_case_handler_get_by_tracking(ims_pdo(), $tracking));
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_create(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_rts_cases_require_allowed();
    if (!api_rts_cases_role_can_record((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    try {
        $pdo = ims_pdo();
        $result = rts_case_handler_create($pdo, $input);
        api_rts_cases_maybe_sync($pdo);
        api_ok($result);
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_append_lines(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_rts_cases_require_allowed();
    if (!api_rts_cases_role_can_record((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    $tracking = trim((string) ($input['tracking_number'] ?? ''));
    try {
        $pdo = ims_pdo();
        $result = rts_case_handler_append_lines($pdo, $tracking, $input);
        api_rts_cases_maybe_sync($pdo);
        api_ok($result);
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_update(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_rts_cases_require_allowed();
    if (!api_rts_cases_role_can_record((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    try {
        $pdo = ims_pdo();
        $result = rts_case_handler_update($pdo, (int) ($input['case_id'] ?? 0), $input);
        api_rts_cases_maybe_sync($pdo);
        api_ok($result);
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_void(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_rts_cases_require_allowed();
    if (!api_rts_cases_role_can_admin((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    try {
        rts_case_handler_void(ims_pdo(), (int) ($input['case_id'] ?? 0), (int) $user['id']);
        api_ok(['voided' => true]);
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_update_claim(): void
{
    api_method_allowed(['POST']);
    $input = api_input();
    api_require_csrf($input);
    $user = api_rts_cases_require_allowed();
    if (!api_rts_cases_role_can_admin((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    try {
        $result = rts_case_handler_update_claim(
            ims_pdo(),
            (int) ($input['case_id'] ?? 0),
            trim((string) ($input['claim_status'] ?? '')),
            trim((string) ($input['claim_status_note'] ?? ''))
        );
        api_ok($result);
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_list(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    try {
        api_ok(rts_case_handler_list(ims_pdo(), $_GET));
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_list_swapped(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    try {
        api_ok(rts_case_handler_list_swapped(ims_pdo(), $_GET));
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_list_leaks(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    try {
        api_ok(rts_case_handler_list_leaks(ims_pdo(), $_GET));
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_tracking_prefill(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    $tracking = trim((string) ($_GET['tracking'] ?? ''));
    try {
        api_ok(rts_case_handler_tracking_prefill_api(ims_pdo(), $tracking));
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_dashboard(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    api_ok(rts_case_handler_dashboard(ims_pdo()));
}

function api_rts_cases_product_catalog(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    api_ok(rts_case_handler_product_catalog());
}

function api_rts_cases_delivery_match(): void
{
    api_method_allowed(['GET']);
    api_rts_cases_require_allowed();
    $tracking = trim((string) ($_GET['tracking'] ?? ''));
    try {
        api_ok(rts_case_handler_delivery_match(ims_pdo(), $tracking));
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}

function api_rts_cases_documentation_upload(): void
{
    api_method_allowed(['POST']);
    $user = api_rts_cases_require_allowed();
    if (!api_rts_cases_role_can_record((string) $user['role'])) {
        api_fail('Forbidden.', 403);
    }
    $csrf = (string) ($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(api_csrf_token(), $csrf)) {
        api_fail('Invalid CSRF token.', 419);
    }
    $file = $_FILES['file'] ?? $_FILES['photo'] ?? null;
    try {
        api_ok(rts_case_handler_upload_documentation(is_array($file) ? $file : null));
    } catch (RtsCaseException $e) {
        api_rts_cases_fail_exception($e);
    }
}
