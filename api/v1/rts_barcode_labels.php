<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/rts_barcode_labels.php';

function api_rts_barcode_labels_require_user(): array
{
    return api_require_ims_user();
}

function api_rts_barcode_labels_require_csrf(): void
{
    $token = trim((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf'] ?? ''));
    if (!hash_equals(api_csrf_token(), $token)) {
        api_fail('Invalid CSRF token.', 419);
    }
}

function api_rts_barcode_labels_require_perm(): array
{
    $user = api_rts_barcode_labels_require_user();
    if (!rts_barcode_labels_can($user)) {
        api_fail('Forbidden.', 403);
    }

    return $user;
}

api_dispatch([
    'job_create' => 'api_rts_barcode_labels_job_create',
    'job_get' => 'api_rts_barcode_labels_job_get',
    'job_list' => 'api_rts_barcode_labels_job_list',
    'job_codes' => 'api_rts_barcode_labels_job_codes',
    'mark_printed' => 'api_rts_barcode_labels_mark_printed',
    'print_sheet' => 'api_rts_barcode_labels_print_sheet',
    'meta' => 'api_rts_barcode_labels_meta',
]);

function api_rts_barcode_labels_meta(): void
{
    api_method_allowed(['GET']);
    api_rts_barcode_labels_require_perm();
    $dept = [];
    foreach (DEPT_POUCH_BARCODE_KEYS as $key) {
        $dept[] = [
            'product_key' => $key,
            'label' => dept_pouch_product_label($key),
        ];
    }
    api_ok([
        'honey_variants' => HONEY_VARIANTS,
        'honey_sizes' => array_map(
            static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],
            HONEY_SIZES
        ),
        'dept_products' => $dept,
    ]);
}

function api_rts_barcode_labels_job_create(): void
{
    api_method_allowed(['POST']);
    api_rts_barcode_labels_require_csrf();
    $user = api_rts_barcode_labels_require_perm();
    $in = api_input();
    $lines = $in['lines'] ?? null;
    if (!is_array($lines)) {
        api_fail('lines must be an array.');
    }
    try {
        api_ok(['job' => rts_barcode_label_job_create(
            ims_pdo(),
            (int) $user['id'],
            $lines,
            isset($in['note']) ? (string) $in['note'] : null
        )]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_rts_barcode_labels_job_get(): void
{
    api_method_allowed(['GET']);
    api_rts_barcode_labels_require_perm();
    $id = (int) ($_GET['id'] ?? 0);
    try {
        api_ok(['job' => rts_barcode_label_job_get(ims_pdo(), $id)]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }
}

function api_rts_barcode_labels_job_list(): void
{
    api_method_allowed(['GET']);
    api_rts_barcode_labels_require_perm();
    api_ok(rts_barcode_label_job_list(
        ims_pdo(),
        (int) ($_GET['page'] ?? 1),
        (int) ($_GET['per_page'] ?? 25)
    ));
}

function api_rts_barcode_labels_job_codes(): void
{
    api_method_allowed(['GET']);
    api_rts_barcode_labels_require_perm();
    $id = (int) ($_GET['id'] ?? 0);
    try {
        api_ok(rts_barcode_label_job_codes(ims_pdo(), $id));
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }
}

function api_rts_barcode_labels_mark_printed(): void
{
    api_method_allowed(['POST']);
    api_rts_barcode_labels_require_csrf();
    api_rts_barcode_labels_require_perm();
    $in = api_input();
    try {
        api_ok(['job' => rts_barcode_label_job_mark_printed(ims_pdo(), (int) ($in['id'] ?? 0))]);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 400);
    }
}

function api_rts_barcode_labels_print_sheet(): void
{
    api_method_allowed(['GET']);
    api_rts_barcode_labels_require_perm();
    $id = (int) ($_GET['id'] ?? 0);
    if ($id < 1) {
        api_fail('id is required.');
    }
    try {
        $html = rts_barcode_label_print_sheet_html(ims_pdo(), $id);
    } catch (Throwable $e) {
        api_fail($e->getMessage(), 404);
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}
