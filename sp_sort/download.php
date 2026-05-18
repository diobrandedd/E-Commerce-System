<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

sp_sort_user();
$pdo = rts_pdo();
$batchId = isset($_GET['batch']) ? (int) $_GET['batch'] : 0;
if ($batchId < 1) {
    http_response_code(400);
    exit('Invalid batch.');
}
$batch = sp_sort_batch_or_404($pdo, $batchId);
if (trim((string) ($batch['prepared_by'] ?? '')) === '') {
    header('Location: workflow.php?batch=' . $batchId, true, 302);
    exit;
}
$filename = basename((string) ($batch['merged_filename'] ?? ''));
if ($filename === '' || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
    http_response_code(404);
    exit('File missing.');
}
$path = sp_sort_storage_dir() . '/' . $filename;
if (!is_file($path)) {
    http_response_code(404);
    exit('File not found on disk.');
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="sp_sort_batch_' . $batchId . '.xlsx"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
