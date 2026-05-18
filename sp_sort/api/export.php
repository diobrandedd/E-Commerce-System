<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

sp_sort_user();
$pdo = rts_pdo();

$batchId = isset($_POST['batch_id']) ? (int) $_POST['batch_id'] : 0;
$preparedBy = isset($_POST['prepared_by']) ? trim((string) $_POST['prepared_by']) : '';
$action = isset($_POST['action']) ? (string) $_POST['action'] : 'download';

if ($batchId < 1) {
    http_response_code(400);
    exit('Invalid batch.');
}
if ($preparedBy === '') {
    http_response_code(400);
    exit('Prepared by name is required.');
}

sp_sort_batch_or_404($pdo, $batchId);

$printMetaOverrides = [
    'order_date_line' => trim((string) ($_POST['order_date_line'] ?? '')),
    'pickup_date_line' => trim((string) ($_POST['pickup_date_line'] ?? '')),
    'note_line_1' => trim((string) ($_POST['note_line_1'] ?? '')),
    'note_line_2' => trim((string) ($_POST['note_line_2'] ?? '')),
];

try {
    sp_sort_regenerate_batch_file($pdo, $batchId, $preparedBy, $printMetaOverrides);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Export failed: ' . $e->getMessage());
}

if ($action === 'print') {
    header('Location: ../print.php?batch=' . $batchId, true, 302);
    exit;
}

$st = $pdo->prepare('SELECT merged_filename FROM sp_sort_batches WHERE id = ? LIMIT 1');
$st->execute([$batchId]);
$row = $st->fetch(PDO::FETCH_ASSOC);
$filename = basename((string) ($row['merged_filename'] ?? 'export.xlsx'));
$path = sp_sort_storage_dir() . '/' . $filename;
if (!is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}

$downloadName = 'SP_Sort_batch_' . $batchId . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
