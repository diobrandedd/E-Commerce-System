<?php

declare(strict_types=1);

/**
 * Serves RTS / delivery images when storage lives outside the public URL tree.
 * Not needed when storage.web_base points to a folder under htdocs (recommended).
 */

require_once dirname(__DIR__) . '/includes/rts_storage.php';

$bucket = isset($_GET['b']) ? trim((string) $_GET['b']) : '';
$filename = isset($_GET['f']) ? trim((string) $_GET['f']) : '';

$allowed = [RTS_STORAGE_BUCKET_RTS, RTS_STORAGE_BUCKET_DELIVERIES];
if (!in_array($bucket, $allowed, true) || $filename === '') {
    http_response_code(404);
    exit;
}

$path = rts_storage_resolve_file($bucket, $filename);
if ($path === null) {
    http_response_code(404);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($path);
if ($mime === '' || $mime === 'application/octet-stream') {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=86400');
readfile($path);
exit;
