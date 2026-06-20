<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/waybill_stamper.php';

$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

if ($action === 'download') {
    try {
        waybill_stamper_serve_download(trim((string) ($_GET['token'] ?? '')));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 403);
    }
}

if ($action === 'stash_download') {
    try {
        waybill_stamper_serve_stash_download(trim((string) ($_GET['token'] ?? '')));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 403);
    }
}

/**
 * Waybill stamper API (CSR TL + Executive).
 *
 *   POST multipart ?action=stash     — kind=pdf|excel, file (one per request)
 *   POST JSON/multipart ?action=process — { pdf_tokens, excel_tokens } or pdfs[] + excels[]
 *   GET  ?action=download&token=...
 */

function api_waybill_assert_access(): array
{
    $user = ims_current_user();
    if ($user === null) {
        api_fail('Not authenticated.', 401);
    }
    if (!waybill_stamper_user_can_access($user)) {
        api_fail('Forbidden.', 403);
    }
    return $user;
}

function api_waybill_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    $expected = api_csrf_token();
    if (!hash_equals($expected, $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'stash' => 'api_waybill_stash',
    'process' => 'api_waybill_process',
]);

function api_waybill_stash(): void
{
    api_method_allowed(['POST']);
    api_waybill_assert_access();
    api_waybill_require_csrf();

    $kind = strtolower(trim((string) ($_POST['kind'] ?? '')));
    if (!in_array($kind, ['pdf', 'excel'], true)) {
        api_fail('Invalid kind. Use pdf or excel.', 400);
    }

    try {
        waybill_stamper_upload_preflight();
        $token = waybill_stamper_stash_upload($kind);
        api_ok(['token' => $token, 'kind' => $kind]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_waybill_process(): void
{
    api_method_allowed(['POST']);
    api_waybill_assert_access();
    api_waybill_require_csrf();

    @ini_set('max_execution_time', '300');

    $input = api_input();
    $pdfTokens = $input['pdf_tokens'] ?? null;
    $excelTokens = $input['excel_tokens'] ?? null;
    $useStash = is_array($pdfTokens) || is_array($excelTokens);

    $stashTokensToRelease = [];

    try {
        if ($useStash) {
            $pdfTokenList = is_array($pdfTokens) ? array_values($pdfTokens) : [];
            $excelTokenList = is_array($excelTokens) ? array_values($excelTokens) : [];
            $stashTokensToRelease = array_merge($pdfTokenList, $excelTokenList);

            $pdfs = waybill_stamper_resolve_stash_tokens($pdfTokenList, 'pdf');
            $excels = waybill_stamper_resolve_stash_tokens($excelTokenList, 'excel');
        } else {
            waybill_stamper_upload_preflight();

            $pdfs = [];
            $pdfField = waybill_stamper_files_field('pdfs');
            if ($pdfField !== null) {
                $pdfs = waybill_stamper_collect_uploads($pdfField, ['pdf'], 'pdf_');
            }
            $excels = [];
            $excelField = waybill_stamper_files_field('excels');
            if ($excelField !== null) {
                $excels = waybill_stamper_collect_uploads($excelField, ['xlsx', 'xls'], 'xls_');
            }
        }

        $result = waybill_stamper_process_uploads($pdfs, $excels);
        waybill_stamper_release_stash_tokens($stashTokensToRelease);
        api_ok($result);
    } catch (Throwable $e) {
        if ($stashTokensToRelease !== []) {
            waybill_stamper_release_stash_tokens($stashTokensToRelease);
        }
        api_fail($e->getMessage(), 400);
    }
}
