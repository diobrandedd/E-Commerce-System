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
require_once dirname(__DIR__) . '/includes/qr_decode.php';
require_once dirname(__DIR__) . '/includes/rts_storage.php';
require_once dirname(__DIR__) . '/includes/delivery_duplicates.php';

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
    $monthKey = rts_storage_deliveries_month_key($deliveryDate);
    $targetDir = rts_storage_dir_deliveries_month($deliveryDate);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not create storage directory']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];

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

$outItems = [];
$savedFiles = [];

try {
    $pdo->beginTransaction();

    $stFind = $pdo->prepare(
        'SELECT id FROM delivery_batches WHERE delivery_date = ? LIMIT 1 FOR UPDATE'
    );
    $stFind->execute([$deliveryDate]);
    $batchId = (int) ($stFind->fetchColumn() ?: 0);

    if ($batchId < 1) {
        $stBatch = $pdo->prepare(
            'INSERT INTO delivery_batches (submitted_by_user_id, delivery_date) VALUES (?, ?)'
        );
        $stBatch->execute([$submitterId, $deliveryDate]);
        $batchId = (int) $pdo->lastInsertId();
    }

    $stCount = $pdo->prepare('SELECT COUNT(*) FROM delivery_batch_items WHERE batch_id = ?');

    $stItem = $pdo->prepare(
        'INSERT INTO delivery_batch_items (batch_id, photo_filename, tracking_number, raw_qr_text, item_status)
         VALUES (?, ?, ?, ?, ?)'
    );

    for ($i = 0; $i < $n; $i++) {
        $uploadErr = (int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadErr !== UPLOAD_ERR_OK) {
            $errLabel = match ($uploadErr) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'file too large for server',
                UPLOAD_ERR_PARTIAL => 'upload interrupted',
                UPLOAD_ERR_NO_FILE => 'file missing (re-select photos on this device)',
                default => 'upload error code ' . $uploadErr,
            };
            throw new RuntimeException('Upload failed for image #' . ($i + 1) . ': ' . $errLabel);
        }
        $tmp = (string) ($tmps[$i] ?? '');
        $size = (int) ($sizes[$i] ?? 0);
        if ($tmp === '' || $size < 1 || $size > RTS_DELIVERY_PHOTO_MAX_BYTES) {
            $mb = (int) round(RTS_DELIVERY_PHOTO_MAX_BYTES / 1024 / 1024);
            throw new RuntimeException('Image #' . ($i + 1) . ' exceeds maximum size (' . $mb . ' MB per file)');
        }
        $mime = (string) $finfo->file($tmp);
        $ext = $allowedMime[$mime] ?? '';
        if ($ext === '') {
            throw new RuntimeException('Unsupported image type');
        }
        $blob = file_get_contents($tmp);
        if ($blob === false || $blob === '') {
            throw new RuntimeException('Could not read upload');
        }

        $hintRaw = ($hints !== null && isset($hints[$i])) ? trim((string) $hints[$i]) : '';
        $class = null;
        if ($hintRaw !== '' && strlen($hintRaw) <= 512) {
            $ht = rts_normalize_tracking_from_qr($hintRaw);
            if ($ht !== '') {
                $class = [
                    'status' => 'decoded_ok',
                    'tracking' => $ht,
                    'raw' => mb_substr($hintRaw, 0, 500),
                ];
            }
        }
        if ($class === null) {
            $class = rts_classify_delivery_qr_blob($blob);
            if ($hintRaw !== '' && in_array($class['status'], ['needs_manual', 'ambiguous'], true)) {
                $ht = rts_normalize_tracking_from_qr($hintRaw);
                if ($ht !== '') {
                    $class = [
                        'status' => 'decoded_ok',
                        'tracking' => $ht,
                        'raw' => mb_substr($hintRaw, 0, 500),
                    ];
                }
            }
        }

        $trackingToSave = trim((string) ($class['tracking'] ?? ''));
        if ($trackingToSave !== '' && rts_delivery_tracking_is_duplicate($pdo, $trackingToSave)) {
            throw new RuntimeException(
                'duplicate_tracking: This tracking number is already saved. Scan a different parcel or remove the duplicate in admin review.'
            );
        }

        $baseName = 'deliv_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
        $name = $monthKey . '/' . $baseName;
        $dest = $targetDir . '/' . $baseName;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Could not save file');
        }
        $savedFiles[] = $dest;

        $stItem->execute([
            $batchId,
            $name,
            $class['tracking'],
            $class['raw'],
            $class['status'],
        ]);

        $outItems[] = [
            'photo_filename' => $name,
            'item_status' => $class['status'],
            'tracking_number' => $class['tracking'],
        ];
    }

    $pdo->commit();
    $stCount->execute([$batchId]);
    $batchItemCount = (int) $stCount->fetchColumn();
    echo json_encode([
        'ok' => true,
        'batch_id' => $batchId,
        'delivery_date' => $deliveryDate,
        'batch_item_count' => $batchItemCount,
        'items' => $outItems,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($savedFiles as $path) {
        if (is_string($path) && $path !== '' && is_file($path)) {
            @unlink($path);
        }
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
