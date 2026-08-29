<?php

declare(strict_types=1);

require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';

function rts_barcode_labels_can(array $user): bool
{
    return ims_user_can($user, 'rts.barcode_labels');
}

/**
 * @return array<string, mixed>
 */
function rts_barcode_label_serialize_job(array $row, array $lines = []): array
{
    $labelCount = isset($row['label_count']) ? (int) $row['label_count'] : array_sum(array_map(
        static fn (array $l): int => (int) ($l['quantity'] ?? 0),
        $lines
    ));
    $codeCount = isset($row['code_count']) ? (int) $row['code_count'] : $labelCount;
    $scannedCount = isset($row['scanned_count']) ? (int) $row['scanned_count'] : 0;

    return [
        'id' => (int) $row['id'],
        'created_by' => (int) $row['created_by'],
        'created_by_name' => (string) ($row['created_by_name'] ?? ''),
        'note' => $row['note'] !== null ? (string) $row['note'] : null,
        'printed_at' => $row['printed_at'] !== null ? (string) $row['printed_at'] : null,
        'created_at' => (string) $row['created_at'],
        'line_count' => isset($row['line_count']) ? (int) $row['line_count'] : count($lines),
        'label_count' => $labelCount,
        'code_count' => $codeCount,
        'scanned_count' => $scannedCount,
        'lines' => $lines,
    ];
}

/**
 * Actual barcode rows + stock-in scans for a job (honey + dept batches linked on lines).
 *
 * @return array{code_count: int, scanned_count: int}
 */
function rts_barcode_label_job_scan_stats(PDO $pdo, int $jobId): array
{
    $honey = $pdo->prepare(
        'SELECT COUNT(*) AS total,
                COALESCE(SUM(c.status = "scanned"), 0) AS scanned
         FROM rts_barcode_label_job_lines l
         INNER JOIN honey_bottle_barcodes c ON c.batch_id = l.honey_batch_id
         WHERE l.job_id = ? AND l.kind = "honey" AND l.honey_batch_id IS NOT NULL'
    );
    $honey->execute([$jobId]);
    $h = $honey->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'scanned' => 0];

    $dept = $pdo->prepare(
        'SELECT COUNT(*) AS total,
                COALESCE(SUM(c.status = "scanned"), 0) AS scanned
         FROM rts_barcode_label_job_lines l
         INNER JOIN dept_pouch_barcodes c ON c.batch_id = l.dept_batch_id
         WHERE l.job_id = ? AND l.kind = "dept" AND l.dept_batch_id IS NOT NULL'
    );
    $dept->execute([$jobId]);
    $d = $dept->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'scanned' => 0];

    return [
        'code_count' => (int) $h['total'] + (int) $d['total'],
        'scanned_count' => (int) $h['scanned'] + (int) $d['scanned'],
    ];
}

/**
 * @return array<string, mixed>
 */
function rts_barcode_label_serialize_line(array $row): array
{
    $kind = (string) $row['kind'];
    $productKey = (string) $row['product_key'];
    $productLabel = $kind === 'honey'
        ? 'Honey'
        : dept_pouch_product_label($productKey);
    if ($kind === 'honey' && !empty($row['variant'])) {
        $productLabel .= ' · ' . ucfirst((string) $row['variant']);
    }
    $size = $row['size'] !== null ? (string) $row['size'] : null;

    return [
        'id' => (int) $row['id'],
        'job_id' => (int) $row['job_id'],
        'sort_order' => (int) $row['sort_order'],
        'kind' => $kind,
        'honey_batch_id' => $row['honey_batch_id'] !== null ? (int) $row['honey_batch_id'] : null,
        'dept_batch_id' => $row['dept_batch_id'] !== null ? (int) $row['dept_batch_id'] : null,
        'product_key' => $productKey,
        'product_label' => $productLabel,
        'size' => $size,
        'size_label' => $size !== null ? honey_bottle_size_label($size) : null,
        'variant' => $row['variant'] !== null ? (string) $row['variant'] : null,
        'batch_no' => (string) $row['batch_no'],
        'bottling_date' => (string) $row['bottling_date'],
        'quantity' => (int) $row['quantity'],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function rts_barcode_label_job_lines(PDO $pdo, int $jobId): array
{
    $st = $pdo->prepare(
        'SELECT * FROM rts_barcode_label_job_lines WHERE job_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $st->execute([$jobId]);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[] = rts_barcode_label_serialize_line($row);
    }

    return $out;
}

/**
 * @return array<string, mixed>
 */
function rts_barcode_label_job_get(PDO $pdo, int $jobId): array
{
    $st = $pdo->prepare(
        'SELECT j.*, u.name AS created_by_name,
                (SELECT COUNT(*) FROM rts_barcode_label_job_lines l WHERE l.job_id = j.id) AS line_count,
                (SELECT COALESCE(SUM(l.quantity), 0) FROM rts_barcode_label_job_lines l WHERE l.job_id = j.id) AS label_count
         FROM rts_barcode_label_jobs j
         LEFT JOIN users u ON u.id = j.created_by
         WHERE j.id = ? LIMIT 1'
    );
    $st->execute([$jobId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('RTS label job not found.');
    }
    $stats = rts_barcode_label_job_scan_stats($pdo, $jobId);
    $row['code_count'] = $stats['code_count'];
    $row['scanned_count'] = $stats['scanned_count'];

    return rts_barcode_label_serialize_job($row, rts_barcode_label_job_lines($pdo, $jobId));
}

/**
 * @return array{rows: list<array<string, mixed>>, page: int, per_page: int, total: int}
 */
function rts_barcode_label_job_list(PDO $pdo, int $page = 1, int $perPage = 25): array
{
    $page = max(1, $page);
    $perPage = max(5, min(100, $perPage));
    $total = (int) $pdo->query('SELECT COUNT(*) FROM rts_barcode_label_jobs')->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $st = $pdo->prepare(
        'SELECT j.*, u.name AS created_by_name,
                (SELECT COUNT(*) FROM rts_barcode_label_job_lines l WHERE l.job_id = j.id) AS line_count,
                (SELECT COALESCE(SUM(l.quantity), 0) FROM rts_barcode_label_job_lines l WHERE l.job_id = j.id) AS label_count,
                (
                  (SELECT COUNT(*) FROM rts_barcode_label_job_lines l
                   INNER JOIN honey_bottle_barcodes c ON c.batch_id = l.honey_batch_id
                   WHERE l.job_id = j.id AND l.kind = "honey" AND l.honey_batch_id IS NOT NULL)
                + (SELECT COUNT(*) FROM rts_barcode_label_job_lines l
                   INNER JOIN dept_pouch_barcodes c ON c.batch_id = l.dept_batch_id
                   WHERE l.job_id = j.id AND l.kind = "dept" AND l.dept_batch_id IS NOT NULL)
                ) AS code_count,
                (
                  (SELECT COALESCE(SUM(c.status = "scanned"), 0) FROM rts_barcode_label_job_lines l
                   INNER JOIN honey_bottle_barcodes c ON c.batch_id = l.honey_batch_id
                   WHERE l.job_id = j.id AND l.kind = "honey" AND l.honey_batch_id IS NOT NULL)
                + (SELECT COALESCE(SUM(c.status = "scanned"), 0) FROM rts_barcode_label_job_lines l
                   INNER JOIN dept_pouch_barcodes c ON c.batch_id = l.dept_batch_id
                   WHERE l.job_id = j.id AND l.kind = "dept" AND l.dept_batch_id IS NOT NULL)
                ) AS scanned_count
         FROM rts_barcode_label_jobs j
         LEFT JOIN users u ON u.id = j.created_by
         ORDER BY j.id DESC
         LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
    );
    $st->execute();
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = rts_barcode_label_serialize_job($row, []);
    }

    return [
        'rows' => $rows,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
    ];
}

/**
 * @param list<array<string, mixed>> $lines
 * @return array<string, mixed>
 */
function rts_barcode_label_job_create(PDO $pdo, int $userId, array $lines, ?string $note = null): array
{
    if ($lines === []) {
        throw new RuntimeException('Add at least one batch line.');
    }
    if (count($lines) > 100) {
        throw new RuntimeException('Too many lines (max 100).');
    }
    $note = $note !== null ? trim($note) : null;
    if ($note === '') {
        $note = null;
    }
    if ($note !== null && strlen($note) > 255) {
        $note = substr($note, 0, 255);
    }

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $pdo->prepare(
            'INSERT INTO rts_barcode_label_jobs (created_by, note) VALUES (?, ?)'
        )->execute([$userId, $note]);
        $jobId = (int) $pdo->lastInsertId();
        $sort = 0;
        foreach ($lines as $raw) {
            if (!is_array($raw)) {
                throw new RuntimeException('Invalid line.');
            }
            $sort++;
            $productKey = strtolower(trim((string) ($raw['product_key'] ?? '')));
            $batchNo = strtoupper(trim((string) ($raw['batch_no'] ?? '')));
            $bottlingDate = trim((string) ($raw['bottling_date'] ?? ''));
            $qty = (int) ($raw['quantity'] ?? 0);
            if ($qty < 1 || $qty > 500) {
                throw new RuntimeException('Quantity per line must be between 1 and 500.');
            }
            if ($bottlingDate === '') {
                throw new RuntimeException('Bottling date is required on every line.');
            }
            $ts = strtotime($bottlingDate);
            if ($ts === false) {
                throw new RuntimeException('Invalid bottling date on line ' . $sort . '.');
            }
            $bottlingDate = date('Y-m-d', $ts);

            if ($productKey === 'honey') {
                $variant = honey_normalize_variant((string) ($raw['variant'] ?? 'light'));
                $size = (string) ($raw['size'] ?? '');
                if (!in_array($size, HONEY_SIZES, true)) {
                    throw new RuntimeException('Honey size is required (250ml / 350ml / 1L / Gallon).');
                }
                require_once __DIR__ . '/sku_barcodes.php';
                $sku = sku_barcode_fetch_honey($pdo, $variant, $size);
                if ($sku === null) {
                    throw new RuntimeException(
                        'No SKU barcode for ' . ucfirst($variant) . ' ' . honey_bottle_size_label($size)
                        . '. Set it in System Settings → Barcode Settings.'
                    );
                }
                sku_barcode_record_print($pdo, $userId, (int) $sku['id'], $qty, 'rts', null, null, $jobId);
                $pdo->prepare(
                    'INSERT INTO rts_barcode_label_job_lines (
                        job_id, sort_order, kind, honey_batch_id, dept_batch_id,
                        product_key, size, variant, batch_no, bottling_date, quantity
                     ) VALUES (?, ?, "honey", NULL, NULL, "honey", ?, ?, ?, ?, ?)'
                )->execute([
                    $jobId,
                    $sort,
                    $size,
                    $variant,
                    $batchNo !== '' ? $batchNo : (string) $sku['code'],
                    $bottlingDate,
                    $qty,
                ]);
            } elseif (dept_pouch_supports($productKey)) {
                require_once __DIR__ . '/sku_barcodes.php';
                $sku = sku_barcode_fetch_dept($pdo, $productKey);
                if ($sku === null) {
                    throw new RuntimeException(
                        'No SKU barcode for ' . dept_pouch_product_label($productKey)
                        . '. Set it in System Settings → Barcode Settings.'
                    );
                }
                sku_barcode_record_print($pdo, $userId, (int) $sku['id'], $qty, 'rts', null, null, $jobId);
                $pdo->prepare(
                    'INSERT INTO rts_barcode_label_job_lines (
                        job_id, sort_order, kind, honey_batch_id, dept_batch_id,
                        product_key, size, variant, batch_no, bottling_date, quantity
                     ) VALUES (?, ?, "dept", NULL, NULL, ?, NULL, NULL, ?, ?, ?)'
                )->execute([
                    $jobId,
                    $sort,
                    $productKey,
                    $batchNo !== '' ? $batchNo : (string) $sku['code'],
                    $bottlingDate,
                    $qty,
                ]);
            } else {
                throw new RuntimeException('Unknown product_key on line ' . $sort . '.');
            }
        }
        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return rts_barcode_label_job_get($pdo, $jobId);
}

function rts_barcode_label_job_mark_printed(PDO $pdo, int $jobId): array
{
    $job = rts_barcode_label_job_get($pdo, $jobId);
    foreach ($job['lines'] as $line) {
        if (($line['kind'] ?? '') === 'honey' && !empty($line['honey_batch_id'])) {
            honey_bottle_mark_printed($pdo, (int) $line['honey_batch_id']);
        }
        if (($line['kind'] ?? '') === 'dept' && !empty($line['dept_batch_id'])) {
            dept_pouch_mark_printed($pdo, (int) $line['dept_batch_id']);
        }
    }
    $pdo->prepare(
        'UPDATE rts_barcode_label_jobs SET printed_at = COALESCE(printed_at, NOW()) WHERE id = ?'
    )->execute([$jobId]);

    return rts_barcode_label_job_get($pdo, $jobId);
}

/**
 * All barcodes for a job, ordered by line then sequence (for expandable history UI).
 *
 * @return array{
 *   job: array<string, mixed>,
 *   codes: list<array<string, mixed>>
 * }
 */
function rts_barcode_label_job_codes(PDO $pdo, int $jobId): array
{
    $job = rts_barcode_label_job_get($pdo, $jobId);
    $codes = [];
    foreach ($job['lines'] as $line) {
        $lineId = (int) $line['id'];
        $productLabel = (string) $line['product_label'];
        $batchNo = (string) $line['batch_no'];
        $sizeLabel = $line['size_label'] !== null ? (string) $line['size_label'] : null;
        if (($line['kind'] ?? '') === 'honey' && !empty($line['honey_batch_id'])) {
            $st = $pdo->prepare(
                'SELECT c.code, c.sequence_no, c.status, c.scanned_at, u.pool AS unit_pool
                 FROM honey_bottle_barcodes c
                 LEFT JOIN honey_bottle_units u ON u.barcode_id = c.id
                 WHERE c.batch_id = ?
                 ORDER BY c.sequence_no ASC'
            );
            $st->execute([(int) $line['honey_batch_id']]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $codes[] = [
                    'line_id' => $lineId,
                    'kind' => 'honey',
                    'product_label' => $productLabel,
                    'batch_no' => $batchNo,
                    'size_label' => $sizeLabel,
                    'code' => (string) $row['code'],
                    'sequence_no' => (int) $row['sequence_no'],
                    'status' => (string) $row['status'],
                    'scanned_at' => $row['scanned_at'] !== null ? (string) $row['scanned_at'] : null,
                    'unit_pool' => $row['unit_pool'] !== null ? (string) $row['unit_pool'] : null,
                ];
            }
        } elseif (($line['kind'] ?? '') === 'dept' && !empty($line['dept_batch_id'])) {
            $st = $pdo->prepare(
                'SELECT c.code, c.sequence_no, c.status, c.scanned_at
                 FROM dept_pouch_barcodes c
                 WHERE c.batch_id = ?
                 ORDER BY c.sequence_no ASC'
            );
            $st->execute([(int) $line['dept_batch_id']]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $codes[] = [
                    'line_id' => $lineId,
                    'kind' => 'dept',
                    'product_label' => $productLabel,
                    'batch_no' => $batchNo,
                    'size_label' => null,
                    'code' => (string) $row['code'],
                    'sequence_no' => (int) $row['sequence_no'],
                    'status' => (string) $row['status'],
                    'scanned_at' => $row['scanned_at'] !== null ? (string) $row['scanned_at'] : null,
                    'unit_pool' => (string) $row['status'] === 'scanned' ? 'finished' : null,
                ];
            }
        }
    }

    return [
        'job' => $job,
        'codes' => $codes,
    ];
}

/**
 * Continuous A4 sheet: banners + labels pack onto the same pages (no break per batch).
 */
function rts_barcode_label_print_sheet_html(PDO $pdo, int $jobId): string
{
    require_once __DIR__ . '/sku_barcodes.php';
    $job = rts_barcode_label_job_get($pdo, $jobId);
    /** @var list<array{slots: int, html: string}> $stream */
    $stream = [];
    $labelCount = 0;
    $batchIndex = 0;

    foreach ($job['lines'] as $line) {
        $batchIndex++;
        $letter = $batchIndex <= 26 ? chr(64 + $batchIndex) : (string) $batchIndex;
        $stream[] = [
            'slots' => SKU_LABEL_SHEET_COLS,
            'html' => sku_barcode_label_banner_html('BATCH ' . $letter),
        ];

        $codes = [];
        $qty = (int) $line['quantity'];
        if (($line['kind'] ?? '') === 'honey' && !empty($line['honey_batch_id'])) {
            $codes = honey_bottle_batch_codes($pdo, (int) $line['honey_batch_id']);
        } elseif (($line['kind'] ?? '') === 'dept' && !empty($line['dept_batch_id'])) {
            $codes = dept_pouch_batch_codes($pdo, (int) $line['dept_batch_id']);
        } else {
            $sku = null;
            if (($line['kind'] ?? '') === 'honey') {
                $sku = sku_barcode_fetch_honey(
                    $pdo,
                    (string) ($line['variant'] ?? ''),
                    (string) ($line['size'] ?? '')
                );
            } else {
                $sku = sku_barcode_fetch_dept($pdo, (string) ($line['product_key'] ?? ''));
            }
            if ($sku !== null) {
                $skuCode = (string) $sku['code'];
                for ($i = 0; $i < $qty; $i++) {
                    $codes[] = ['code' => $skuCode, 'status' => 'generated', 'sequence_no' => $i + 1];
                }
            }
        }
        foreach ($codes as $row) {
            if ((string) ($row['status'] ?? '') === 'void') {
                continue;
            }
            $stream[] = [
                'slots' => 1,
                'html' => sku_barcode_label_cell_html((string) $row['code']),
            ];
            $labelCount++;
        }
    }

    $title = 'RTS labels · job #' . (int) $job['id'];

    return sku_barcode_render_packed_label_sheet($stream, $title, $labelCount);
}
