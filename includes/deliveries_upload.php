<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/qr_decode.php';
require_once __DIR__ . '/rts_storage.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/delivery_edit_requests.php';
require_once __DIR__ . '/online_order_parcels.php';

const DELIVERIES_UPLOAD_MAX_BYTES = 20 * 1024 * 1024;

/**
 * @param array{name: array, tmp_name: array, error: array, size: array} $files photos field
 * @param list<string>|null $hints client QR hints per photo
 * @return array{batch_id: int, delivery_date: string, batch_item_count: int, items: list<array<string, mixed>>}
 */
function deliveries_upload_photos(PDO $pdo, string $deliveryDate, array $files, ?array $hints, int $submitterAppUserId): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
        throw new InvalidArgumentException('Invalid delivery_date (use YYYY-MM-DD)');
    }

    $names = $files['name'];
    $tmps = $files['tmp_name'];
    $errs = $files['error'];
    $sizes = $files['size'];
    if (!is_array($names)) {
        $names = [$names];
        $tmps = [$tmps];
        $errs = [$errs];
        $sizes = [$sizes];
    }
    $n = count($names);
    if ($n < 1) {
        throw new RuntimeException('Select at least one image');
    }
    if ($hints !== null && count($hints) !== $n) {
        throw new RuntimeException('client_hints must have one entry per photo');
    }

    $monthKey = rts_storage_deliveries_month_key($deliveryDate);
    $targetDir = rts_storage_dir_deliveries_month($deliveryDate);

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if ($submitterAppUserId < 1) {
        throw new RuntimeException('No system user available for delivery batches');
    }

    $hasParcelLink = rts_db_has_column($pdo, 'delivery_batch_items', 'online_parcel_id');
    $outItems = [];
    $savedFiles = [];

    // DDL in ensure_schema implicitly commits MySQL transactions — run before beginTransaction.
    online_parcels_ensure_schema($pdo);

    $pdo->beginTransaction();
    try {
        $stFind = $pdo->prepare('SELECT id FROM delivery_batches WHERE delivery_date = ? LIMIT 1 FOR UPDATE');
        $stFind->execute([$deliveryDate]);
        $batchId = (int) ($stFind->fetchColumn() ?: 0);
        if ($batchId < 1) {
            $pdo->prepare('INSERT INTO delivery_batches (submitted_by_user_id, delivery_date) VALUES (?, ?)')
                ->execute([$submitterAppUserId, $deliveryDate]);
            $batchId = (int) $pdo->lastInsertId();
        }

        if ($hasParcelLink) {
            $stItem = $pdo->prepare(
                'INSERT INTO delivery_batch_items (batch_id, photo_filename, tracking_number, raw_qr_text, item_status, online_parcel_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
        } else {
            $stItem = $pdo->prepare(
                'INSERT INTO delivery_batch_items (batch_id, photo_filename, tracking_number, raw_qr_text, item_status)
                 VALUES (?, ?, ?, ?, ?)'
            );
        }

        for ($i = 0; $i < $n; $i++) {
            $uploadErr = (int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadErr !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload failed for image #' . ($i + 1));
            }
            $tmp = (string) ($tmps[$i] ?? '');
            $size = (int) ($sizes[$i] ?? 0);
            if ($tmp === '' || $size < 1 || $size > DELIVERIES_UPLOAD_MAX_BYTES) {
                throw new RuntimeException('Image #' . ($i + 1) . ' exceeds maximum size');
            }
            $mime = (string) $finfo->file($tmp);
            $ext = $allowedMime[$mime] ?? '';
            if ($ext === '') {
                throw new RuntimeException('Unsupported image type');
            }
            $hintRaw = ($hints !== null && isset($hints[$i])) ? trim((string) $hints[$i]) : '';
            if ($hintRaw !== '') {
                $class = deliveries_classify_upload('', $hintRaw);
            } else {
                $blob = file_get_contents($tmp);
                if ($blob === false || $blob === '') {
                    throw new RuntimeException('Could not read upload');
                }
                $class = deliveries_classify_upload($blob, '');
            }

            $baseName = 'deliv_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
            $name = $monthKey . '/' . $baseName;
            $dest = $targetDir . '/' . $baseName;
            if (!move_uploaded_file($tmp, $dest)) {
                throw new RuntimeException('Could not save file');
            }
            $savedFiles[] = $dest;

            $parcelId = null;
            $tn = $class['tracking'] ?? null;
            $tnNorm = '';
            if ($tn !== null && $tn !== '') {
                $tnNorm = online_parcels_normalize_tracking((string) $tn);
                if ($tnNorm !== '') {
                    $stParcel = $pdo->prepare('SELECT id FROM online_order_parcels WHERE tracking_number = ? LIMIT 1');
                    $stParcel->execute([$tnNorm]);
                    $pid = $stParcel->fetchColumn();
                    if ($pid !== false) {
                        $parcelId = (int) $pid;
                    }
                }
            }
            if ($tnNorm !== '' || $parcelId !== null) {
                deliveries_assert_tracking_not_duplicate($pdo, $tnNorm, 0, $parcelId);
            }

            if ($hasParcelLink) {
                $stItem->execute([
                    $batchId,
                    $name,
                    $class['tracking'],
                    $class['raw'],
                    $class['status'],
                    $parcelId,
                ]);
            } else {
                $stItem->execute([
                    $batchId,
                    $name,
                    $class['tracking'],
                    $class['raw'],
                    $class['status'],
                ]);
            }

            $itemId = (int) $pdo->lastInsertId();
            $outItems[] = [
                'delivery_item_id' => $itemId,
                'photo_filename' => $name,
                'item_status' => $class['status'],
                'tracking_number' => $class['tracking'],
            ];
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($savedFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        throw $e;
    }

    $stCount = $pdo->prepare('SELECT COUNT(*) FROM delivery_batch_items WHERE batch_id = ?');
    $stCount->execute([$batchId]);

    return [
        'batch_id' => $batchId,
        'delivery_date' => $deliveryDate,
        'batch_item_count' => (int) $stCount->fetchColumn(),
        'items' => $outItems,
    ];
}

/**
 * @return array{status: string, tracking: ?string, raw: ?string}
 */
function deliveries_classify_upload(string $blob, string $hintRaw): array
{
    if ($hintRaw !== '' && strlen($hintRaw) <= 512) {
        $ht = rts_normalize_tracking_from_qr($hintRaw);
        if ($ht !== '') {
            return [
                'status' => 'decoded_ok',
                'tracking' => $ht,
                'raw' => mb_substr($hintRaw, 0, 500),
            ];
        }
    }

    $class = rts_classify_delivery_qr_blob($blob);
    if ($hintRaw !== '' && in_array($class['status'], ['needs_manual', 'ambiguous'], true)) {
        $ht = rts_normalize_tracking_from_qr($hintRaw);
        if ($ht !== '') {
            return [
                'status' => 'decoded_ok',
                'tracking' => $ht,
                'raw' => mb_substr($hintRaw, 0, 500),
            ];
        }
    }

    return [
        'status' => (string) $class['status'],
        'tracking' => $class['tracking'],
        'raw' => $class['raw'],
    ];
}

function deliveries_ims_submitter_app_user_id(PDO $pdo): int
{
    return rts_delivery_submitter_user_id($pdo);
}
