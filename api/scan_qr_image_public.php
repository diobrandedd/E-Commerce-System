<?php

declare(strict_types=1);

/**
 * Server-side QR decode for unauthenticated flows (e.g. deliveries bulk pre-read).
 * Same decode order as RTS (non-invert then invert). Rate limiting is left to the host.
 */

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__) . '/includes/qr_decode.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_readable($autoload)) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'PHP dependencies missing. From the RTS folder run: php composer.phar install',
    ]);
    exit;
}

if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Enable PHP extension gd or imagick in php.ini for server-side QR decode.',
    ]);
    exit;
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing file input: image']);
    exit;
}

$f = $_FILES['image'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Upload failed']);
    exit;
}

$tmp = (string) ($f['tmp_name'] ?? '');
$size = (int) ($f['size'] ?? 0);
if ($tmp === '' || $size < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Empty upload']);
    exit;
}
if ($size > 20 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'File too large (max 20MB)']);
    exit;
}

$blob = file_get_contents($tmp);
if ($blob === false || $blob === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Could not read upload']);
    exit;
}

$text = rts_qr_decode_image_upload_best($blob);
if ($text !== null) {
    echo json_encode(['ok' => true, 'text' => $text], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Could not read QR from image']);
