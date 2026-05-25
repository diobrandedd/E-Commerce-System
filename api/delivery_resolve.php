<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/rts_storage.php';

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

if ($action === 'delete') {
    $password = isset($data['admin_password']) ? (string) $data['admin_password'] : '';
    if ($password === '' && isset($data['password'])) {
        $password = (string) $data['password'];
    }
    if (!rts_auth_verify_user_password((int) $u['id'], $password)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Invalid admin password']);
        exit;
    }

    $st = $pdo->prepare(
        'SELECT photo_filename FROM delivery_batch_items WHERE id = ? LIMIT 1'
    );
    $st->execute([$itemId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Item not found']);
        exit;
    }

    $del = $pdo->prepare('DELETE FROM delivery_batch_items WHERE id = ?');
    $del->execute([$itemId]);
    if ($del->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Item not found']);
        exit;
    }

    $photo = isset($row['photo_filename']) ? trim((string) $row['photo_filename']) : '';
    if ($photo !== '') {
        $path = rts_storage_resolve_file(RTS_STORAGE_BUCKET_DELIVERIES, $photo);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    echo json_encode(['ok' => true, 'message' => 'Entry deleted']);
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
