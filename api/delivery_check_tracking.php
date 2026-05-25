<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/delivery_duplicates.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
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

$duplicate = rts_delivery_tracking_is_duplicate($pdo, $tracking);

echo json_encode([
    'ok' => true,
    'tracking' => $tracking,
    'duplicate' => $duplicate,
], JSON_UNESCAPED_UNICODE);
