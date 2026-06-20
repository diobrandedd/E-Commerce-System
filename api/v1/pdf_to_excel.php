<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../includes/pdf_to_excel.php';

$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

if ($action === 'download') {
    try {
        pdf_to_excel_serve_download(trim((string) ($_GET['token'] ?? '')));
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 403);
    }
}

/**
 * PDF → Excel API (CSR TL + Executive).
 *
 *   POST multipart ?action=stash     — file (one PDF per request)
 *   POST JSON ?action=extract        — { pdf_tokens: string[] }
 *   POST JSON ?action=confirm        — { job_token: string }
 *   GET  ?action=download&token=...
 */

function api_pdf_to_excel_assert_access(): array
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

function api_pdf_to_excel_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    $expected = api_csrf_token();
    if (!hash_equals($expected, $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

api_dispatch([
    'stash' => 'api_pdf_to_excel_stash',
    'extract' => 'api_pdf_to_excel_extract',
    'confirm' => 'api_pdf_to_excel_confirm',
]);

function api_pdf_to_excel_stash(): void
{
    api_method_allowed(['POST']);
    api_pdf_to_excel_assert_access();
    api_pdf_to_excel_require_csrf();

    try {
        waybill_stamper_upload_preflight();
        $token = pdf_to_excel_stash_upload();
        api_ok(['token' => $token]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_pdf_to_excel_extract(): void
{
    api_method_allowed(['POST']);
    api_pdf_to_excel_assert_access();
    api_pdf_to_excel_require_csrf();

    @ini_set('max_execution_time', '300');

    $input = api_input();
    $pdfTokens = $input['pdf_tokens'] ?? null;
    if (!is_array($pdfTokens) || $pdfTokens === []) {
        api_fail('pdf_tokens is required.', 400);
    }

    $tokenList = array_values($pdfTokens);
    $stashToRelease = $tokenList;

    try {
        $pdfs = pdf_to_excel_resolve_stash_tokens($tokenList);
        if ($pdfs === []) {
            api_fail('No PDF files found for the given tokens.', 400);
        }
        $result = pdf_to_excel_extract($pdfs);
        pdf_to_excel_release_stash_tokens($stashToRelease);
        api_ok($result);
    } catch (Throwable $e) {
        pdf_to_excel_release_stash_tokens($stashToRelease);
        api_fail($e->getMessage(), 400);
    }
}

function api_pdf_to_excel_confirm(): void
{
    api_method_allowed(['POST']);
    api_pdf_to_excel_assert_access();
    api_pdf_to_excel_require_csrf();

    $input = api_input();
    $jobToken = trim((string) ($input['job_token'] ?? ''));
    if ($jobToken === '') {
        api_fail('job_token is required.', 400);
    }

    try {
        $result = pdf_to_excel_confirm($jobToken);
        api_ok($result);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 400);
    }
}
