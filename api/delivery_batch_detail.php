<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

rts_session_start();
$u = rts_auth_user();
if (!rts_can_access_rts_admin($u)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
if ($batchId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid batch_id']);
    exit;
}

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

$st = $pdo->prepare(
    'SELECT b.id, b.delivery_date, b.submitted_at FROM delivery_batches b WHERE b.id = ?'
);
$st->execute([$batchId]);
$batch = $st->fetch(PDO::FETCH_ASSOC);
if (!$batch) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Batch not found']);
    exit;
}

$it = $pdo->prepare(
    'SELECT id, photo_filename, item_status, tracking_number, raw_qr_text, voided_at
     FROM delivery_batch_items WHERE batch_id = ? ORDER BY id ASC'
);
$it->execute([$batchId]);
$rows = $it->fetchAll(PDO::FETCH_ASSOC);

$dup = $pdo->prepare(
    'SELECT tracking_number, COUNT(*) AS c FROM delivery_batch_items
     WHERE batch_id = ? AND voided_at IS NULL AND tracking_number IS NOT NULL AND tracking_number != \'\'
     GROUP BY tracking_number HAVING c > 1'
);
$dup->execute([$batchId]);
$dupSet = [];
while ($r = $dup->fetch(PDO::FETCH_ASSOC)) {
    $dupSet[(string) $r['tracking_number']] = true;
}

$items = [];
foreach ($rows as $row) {
    $tr = $row['tracking_number'] !== null && $row['tracking_number'] !== ''
        ? (string) $row['tracking_number'] : '';
    $voided = $row['voided_at'] !== null && $row['voided_at'] !== '';
    $items[] = [
        'id' => (int) $row['id'],
        'photo_filename' => (string) $row['photo_filename'],
        'item_status' => (string) $row['item_status'],
        'tracking_number' => $tr,
        'raw_qr_text' => $row['raw_qr_text'] !== null ? (string) $row['raw_qr_text'] : '',
        'voided' => $voided,
        'dup_in_batch' => $tr !== '' && isset($dupSet[$tr]) && !$voided,
    ];
}

echo json_encode([
    'ok' => true,
    'batch' => [
        'id' => (int) $batch['id'],
        'delivery_date' => (string) $batch['delivery_date'],
        'submitted_at' => (string) $batch['submitted_at'],
    ],
    'items' => $items,
], JSON_UNESCAPED_UNICODE);
