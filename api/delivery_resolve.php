<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

rts_session_start();
$u = rts_auth_user();
if (!rts_can_access_rts_admin($u)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$itemId = isset($data['item_id']) ? (int) $data['item_id'] : 0;
$action = isset($data['action']) ? trim((string) $data['action']) : '';

if ($itemId < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid item_id']);
    exit;
}

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

if ($action === 'void') {
    $st = $pdo->prepare('UPDATE delivery_batch_items SET voided_at = NOW() WHERE id = ? AND voided_at IS NULL');
    $st->execute([$itemId]);
    echo json_encode(['ok' => true, 'message' => 'Row voided']);
    exit;
}

if ($action !== 'resolve') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    exit;
}

$tracking = isset($data['tracking_number']) ? trim((string) $data['tracking_number']) : '';
if ($tracking === '' || strlen($tracking) > 96) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid tracking_number']);
    exit;
}

$st = $pdo->prepare(
    'UPDATE delivery_batch_items
     SET tracking_number = ?, item_status = \'resolved\', resolved_at = NOW(), resolved_by_user_id = ?
     WHERE id = ? AND voided_at IS NULL'
);
$st->execute([$tracking, (int) $u['id'], $itemId]);
if ($st->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Item not found or voided']);
    exit;
}

echo json_encode(['ok' => true]);
