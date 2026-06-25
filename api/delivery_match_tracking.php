<?php

declare(strict_types=1);

/**
 * Find latest non-voided delivery batch item photo for a tracking number (RTS compare flow).
 */

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/rts_storage.php';
require_once dirname(__DIR__) . '/includes/auth.php';

rts_session_start();
$u = rts_auth_user();
if (!rts_can_access_rts_admin($u)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$tracking = isset($_GET['tracking']) ? trim((string) $_GET['tracking']) : '';
if ($tracking === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing tracking']);
    exit;
}

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

try {
    $st = $pdo->prepare(
        'SELECT i.id AS item_id, i.photo_filename, b.delivery_date, b.submitted_at
         FROM delivery_batch_items i
         INNER JOIN delivery_batches b ON b.id = i.batch_id
         WHERE i.voided_at IS NULL
           AND TRIM(COALESCE(i.tracking_number, \'\')) != \'\'
           AND LOWER(TRIM(i.tracking_number)) = LOWER(TRIM(?))
         ORDER BY b.submitted_at DESC, i.id DESC
         LIMIT 1'
    );
    $st->execute([$tracking]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Lookup failed']);
    exit;
}

if (!$row) {
    echo json_encode([
        'ok' => true,
        'match' => false,
        'item_id' => null,
        'photo_filename' => null,
        'delivery_date' => null,
        'file_url' => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fn = (string) $row['photo_filename'];
$fileUrl = rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_DELIVERIES, $fn);

echo json_encode([
    'ok' => true,
    'match' => true,
    'item_id' => (int) $row['item_id'],
    'photo_filename' => $fn,
    'delivery_date' => (string) $row['delivery_date'],
    'file_url' => $fileUrl,
], JSON_UNESCAPED_UNICODE);
