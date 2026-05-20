<?php

declare(strict_types=1);

/**
 * Decode QR from an uploaded image on the server (chillerlan/php-qrcode).
 * Works over plain HTTP / LAN — no browser camera API required for decoding.
 *
 * @see https://github.com/chillerlan/php-qrcode
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/auth.php';
rts_session_start();
$authUser = rts_auth_user();
if (!rts_can_access_rts_admin($authUser)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_readable($autoload)) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'PHP dependencies missing. From the RTS folder run: php composer.phar install',
    ]);
    exit;
}

require_once $autoload;

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
if ($size > 12 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'File too large (max 12MB)']);
    exit;
}

$blob = file_get_contents($tmp);
if ($blob === false || $blob === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Could not read upload']);
    exit;
}

require_once dirname(__DIR__) . '/includes/qr_decode.php';

$text = rts_qr_decode_image_upload_best($blob);
if ($text !== null) {
    echo json_encode(['ok' => true, 'text' => $text], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Could not read QR from image']);
