<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

sp_sort_user();
$pdo = rts_pdo();

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}', true);
$batchId = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;

if ($batchId < 1) {
    echo json_encode(['ok' => false, 'error' => 'Invalid batch.']);
    exit;
}

$st = $pdo->prepare('SELECT id, status FROM sp_sort_batches WHERE id = ? LIMIT 1');
$st->execute([$batchId]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Batch not found.']);
    exit;
}
if (($row['status'] ?? '') === 'completed') {
    echo json_encode(['ok' => true]);
    exit;
}

$upd = $pdo->prepare(
    "UPDATE sp_sort_batches SET status = 'completed', completed_at = NOW() WHERE id = ?"
);
$upd->execute([$batchId]);

echo json_encode(['ok' => true]);
