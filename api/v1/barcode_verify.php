<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/barcode_verify.php';

api_dispatch([
    'lookup' => 'api_barcode_verify_lookup',
]);

/** Any authenticated IMS account may verify a sticker (read-only). */
function api_barcode_verify_lookup(): void
{
    api_method_allowed(['GET', 'POST']);
    api_require_ims_user();
    $code = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = api_input();
        $code = (string) ($in['code'] ?? '');
    } else {
        $code = (string) ($_GET['code'] ?? '');
    }
    try {
        api_ok(['result' => barcode_verify_lookup(ims_pdo(), $code)]);
    } catch (RuntimeException $e) {
        api_fail($e->getMessage(), 404);
    }
}
