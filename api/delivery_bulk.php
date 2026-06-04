<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/**
 * Always return JSON on uncaught errors (avoids HTML fatal pages that look like "HTTP 200" failures in the app).
 */
set_exception_handler(static function (Throwable $e): void {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'ok' => false,
        'error' => 'Server error: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/deliveries_upload.php';

/** Max bytes per delivery photo (PHP upload_max_filesize / post_max_size must be at least this high). */
const RTS_DELIVERY_PHOTO_MAX_BYTES = 20 * 1024 * 1024;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

rts_session_start();
$authUser = rts_auth_user();

$deliveryDate = isset($_POST['delivery_date']) ? trim((string) $_POST['delivery_date']) : '';
if ($deliveryDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid delivery_date (use YYYY-MM-DD)']);
    exit;
}

if (!isset($_FILES['photos']) || !is_array($_FILES['photos'])) {
    $postMax = ini_get('post_max_size') ?: 'unknown';
    $uploadMax = ini_get('upload_max_filesize') ?: 'unknown';
    $contentLen = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $msg = 'No photos received.';
    if ($contentLen > 0) {
        $msg .= ' The upload may exceed server limits (post_max_size=' . $postMax . ', upload_max_filesize=' . $uploadMax . '). Try a smaller photo or raise PHP limits.';
    } else {
        $msg .= ' Send a photo with the request (empty body).';
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$names = $_FILES['photos']['name'];
$tmps = $_FILES['photos']['tmp_name'];
$errs = $_FILES['photos']['error'];
$sizes = $_FILES['photos']['size'];
if (!is_array($names)) {
    $names = [$names];
    $tmps = [$tmps];
    $errs = [$errs];
    $sizes = [$sizes];
}
$n = count($names);
if ($n < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Select at least one image']);
    exit;
}

$hints = null;
if (!empty($_POST['client_hints_json'])) {
    $decoded = json_decode((string) $_POST['client_hints_json'], true);
    if (is_array($decoded)) {
        $hints = $decoded;
    }
}
if ($hints !== null && count($hints) !== $n) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'client_hints_json must have one entry per photo']);
    exit;
}

try {
    $pdo = rts_pdo();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

$submitterId = rts_delivery_submitter_user_id($pdo);
if ($submitterId < 1) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No system user available for delivery batches']);
    exit;
}

$hintList = null;
if ($hints !== null) {
    $hintList = [];
    foreach ($hints as $h) {
        $hintList[] = trim((string) $h);
    }
}

try {
    $result = deliveries_upload_photos(
        $pdo,
        $deliveryDate,
        ['name' => $names, 'tmp_name' => $tmps, 'error' => $errs, 'size' => $sizes],
        $hintList,
        $submitterId
    );
    $legacyItems = [];
    foreach ($result['items'] as $it) {
        $legacyItems[] = [
            'photo_filename' => $it['photo_filename'],
            'item_status' => $it['item_status'],
            'tracking_number' => $it['tracking_number'],
        ];
    }
    echo json_encode([
        'ok' => true,
        'batch_id' => $result['batch_id'],
        'delivery_date' => $result['delivery_date'],
        'batch_item_count' => $result['batch_item_count'],
        'items' => $legacyItems,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
