<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/auth.php';
rts_session_start();
$authUser = rts_auth_user();
if ($authUser === null || $authUser['role'] !== RTS_ROLE_ADMIN) {
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

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($tmp);

$imageMax = 12 * 1024 * 1024;
$videoMax = 120 * 1024 * 1024;

$kind = '';
$ext = '';
$maxForType = $imageMax;

switch ($mime) {
    case 'image/jpeg':
        $ext = 'jpg';
        $kind = 'image';
        break;
    case 'image/png':
        $ext = 'png';
        $kind = 'image';
        break;
    case 'image/webp':
        $ext = 'webp';
        $kind = 'image';
        break;
    case 'image/gif':
        $ext = 'gif';
        $kind = 'image';
        break;
    case 'video/mp4':
        $ext = 'mp4';
        $kind = 'video';
        $maxForType = $videoMax;
        break;
    case 'video/webm':
        $ext = 'webm';
        $kind = 'video';
        $maxForType = $videoMax;
        break;
    case 'video/quicktime':
        $ext = 'mov';
        $kind = 'video';
        $maxForType = $videoMax;
        break;
    default:
        http_response_code(415);
        echo json_encode(['ok' => false, 'error' => 'Unsupported type (use JPG/PNG/WebP/GIF or MP4/WebM/MOV)']);
        exit;
}

if ($size > $maxForType) {
    http_response_code(413);
    $mb = (int) round($maxForType / (1024 * 1024));
    echo json_encode(['ok' => false, 'error' => 'File too large (max about ' . $mb . 'MB for this type)']);
    exit;
}

$targetDir = dirname(__DIR__) . '/assets/rts_documentation';
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not create documentation directory']);
    exit;
}

$prefix = $kind === 'video' ? 'rtsvid_' : 'rtsdoc_';
$name = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$targetPath = $targetDir . '/' . $name;

if (!move_uploaded_file($tmp, $targetPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save uploaded file']);
    exit;
}

echo json_encode([
    'ok' => true,
    'file_name' => $name,
    'file_url' => 'assets/rts_documentation/' . rawurlencode($name),
    'media_kind' => $kind,
], JSON_UNESCAPED_UNICODE);
