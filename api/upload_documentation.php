<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/rts_attachments.php';
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

$f = null;
if (isset($_FILES['file']) && is_array($_FILES['file'])) {
    $f = $_FILES['file'];
} elseif (isset($_FILES['photo']) && is_array($_FILES['photo'])) {
    $f = $_FILES['photo'];
}

if ($f === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing file input (use file or photo)']);
    exit;
}

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

$recordDate = isset($_POST['record_date']) ? trim((string) $_POST['record_date']) : null;

try {
    $saved = rts_documentation_save_upload(
        $tmp,
        $size,
        isset($f['name']) ? (string) $f['name'] : null,
        $recordDate !== '' ? $recordDate : null
    );
} catch (RuntimeException $e) {
    $msg = $e->getMessage();
    if (str_contains($msg, 'too large')) {
        http_response_code(413);
    } elseif (str_contains($msg, 'Unsupported type')) {
        http_response_code(415);
    } else {
        http_response_code(400);
    }
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'file_name' => $saved['file_name'],
    'file_url' => $saved['file_url'],
    'media_kind' => $saved['media_kind'],
], JSON_UNESCAPED_UNICODE);
