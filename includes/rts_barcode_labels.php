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
                $reused = false;
                $pk = honey_bottle_create_custom_packaging(
                    $pdo,
                    $userId,
                    '',
                    $variant,
                    $bottlingDate,
                    $batchNo,
                    $reused
                );
                $batch = honey_bottle_generate_batch(
                    $pdo,
                    $userId,
                    $qty,
                    (int) $pk['id'],
                    $size,
                    'rts_label',
                    null,
                    $bottlingDate,
                    $jobId
                );
                $pdo->prepare(
                    'INSERT INTO rts_barcode_label_job_lines (
                        job_id, sort_order, kind, honey_batch_id, dept_batch_id,
                        product_key, size, variant, batch_no, bottling_date, quantity
                     ) VALUES (?, ?, "honey", ?, NULL, "honey", ?, ?, ?, ?, ?)'
                )->execute([
                    $jobId,
                    $sort,
                    (int) $batch['id'],
                    $size,
                    $variant,
                    (string) $batch['batch_no'],
                    $bottlingDate,
                    $qty,
                ]);
            } elseif (dept_pouch_supports($productKey)) {
                $batch = dept_pouch_generate_rts_label(
                    $pdo,
                    $userId,
                    $productKey,
                    $batchNo,
                    $bottlingDate,
                    $qty,
                    $jobId
                );
                $pdo->prepare(
                    'INSERT INTO rts_barcode_label_job_lines (
                        job_id, sort_order, kind, honey_batch_id, dept_batch_id,
                        product_key, size, variant, batch_no, bottling_date, quantity
                     ) VALUES (?, ?, "dept", NULL, ?, ?, NULL, NULL, ?, ?, ?)'
                )->execute([
                    $jobId,
                    $sort,
                    (int) $batch['id'],
                    $productKey,
                    (string) $batch['batch_no'],
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
    $job = rts_barcode_label_job_get($pdo, $jobId);
    /** @var list<array{slots: int, html: string}> $stream */
    $stream = [];
    $labelCount = 0;

    foreach ($job['lines'] as $line) {
        $batchNo = htmlspecialchars((string) $line['batch_no'], ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars((string) $line['bottling_date'], ENT_QUOTES, 'UTF-8');
        $product = htmlspecialchars((string) $line['product_label'], ENT_QUOTES, 'UTF-8');
        $sizeBit = '';
        if (!empty($line['size_label'])) {
            $sizeBit = ' · ' . htmlspecialchars((string) $line['size_label'], ENT_QUOTES, 'UTF-8');
        }
        $qty = (int) $line['quantity'];
        $bannerText = 'Batch ' . $batchNo . ' · Bottled ' . $date . ' · ' . $product . $sizeBit . ' · qty ' . $qty;
        $stream[] = [
            'slots' => 4,
            'html' => '<div class="banner" title="' . $bannerText . '"><div class="banner-inner">'
                . $bannerText . '</div></div>',
        ];

        $codes = [];
        if (($line['kind'] ?? '') === 'honey' && !empty($line['honey_batch_id'])) {
            $codes = honey_bottle_batch_codes($pdo, (int) $line['honey_batch_id']);
        } elseif (($line['kind'] ?? '') === 'dept' && !empty($line['dept_batch_id'])) {
            $codes = dept_pouch_batch_codes($pdo, (int) $line['dept_batch_id']);
        }
        foreach ($codes as $row) {
            if ((string) ($row['status'] ?? '') === 'void') {
                continue;
            }
            $code = (string) $row['code'];
            try {
                $svg = honey_bottle_code128_svg($code);
            } catch (Throwable) {
                $svg = '<div class="err">barcode lib missing</div>';
            }
            $safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            $stream[] = [
                'slots' => 1,
                'html' => '<div class="label"><div class="bc" title="' . $safe . '">' . $svg
                    . '</div><div class="code">' . $safe . '</div></div>',
            ];
            $labelCount++;
        }
    }

    // Pack by visual rows (17 × 4), not abstract “slots”. A full-width banner is
    // one row via grid-column:1/-1. If the previous batch ended mid-row, pad empties
    // so the next banner starts at column 1 — otherwise auto-placement leaves holes,
    // later pages overflow the fixed 17-row sheet, and barcodes clip (overflow:hidden).
    $cols = 4;
    $rowsPerPage = 17;
    $pages = [];
    $current = [];
    $col = 0;
    $rowsUsed = 0;

    $pushEmpty = static function () use (&$current, &$col, &$rowsUsed, $cols): void {
        $current[] = '<div class="label empty"></div>';
        $col++;
        if ($col >= $cols) {
            $col = 0;
            $rowsUsed++;
        }
    };

    $flushPage = static function () use (
        &$pages,
        &$current,
        &$col,
        &$rowsUsed,
        $cols,
        $rowsPerPage,
        $pushEmpty
    ): void {
        while ($col > 0) {
            $pushEmpty();
        }
        while ($rowsUsed < $rowsPerPage) {
            for ($i = 0; $i < $cols; $i++) {
                $current[] = '<div class="label empty"></div>';
            }
            $rowsUsed++;
        }
        $pages[] = $current;
        $current = [];
        $col = 0;
        $rowsUsed = 0;
    };

    $ensureRowStart = static function () use (&$col, $pushEmpty): void {
        while ($col > 0) {
            $pushEmpty();
        }
    };

    foreach ($stream as $item) {
        $need = (int) $item['slots'];
        if ($need >= $cols) {
            // Banner: always a full row starting at column 1.
            $ensureRowStart();
            if ($rowsUsed >= $rowsPerPage) {
                $flushPage();
            }
            $current[] = $item['html'];
            $rowsUsed++;
            $col = 0;
            if ($rowsUsed >= $rowsPerPage) {
                $flushPage();
            }
            continue;
        }
        // Label (1 cell).
        if ($rowsUsed >= $rowsPerPage && $col === 0) {
            $flushPage();
        }
        if ($rowsUsed >= $rowsPerPage) {
            $flushPage();
        }
        $current[] = $item['html'];
        $col++;
        if ($col >= $cols) {
            $col = 0;
            $rowsUsed++;
            if ($rowsUsed >= $rowsPerPage) {
                $flushPage();
            }
        }
    }
    if ($current !== [] || $pages === []) {
        $flushPage();
    }

    $pagesHtml = '';
    foreach ($pages as $i => $chunk) {
        $pageClass = $i === 0 ? 'page' : 'page page-break';
        $pagesHtml .= '<section class="' . $pageClass . '"><div class="sheet">'
            . implode('', $chunk) . '</div></section>';
    }

    $title = 'RTS labels · job #' . (int) $job['id'];
    $lineCount = count($job['lines']);

    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</title><style>
        @page { size: A4 portrait; margin: 5mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #fff; color: #111; }
        .toolbar { margin: 10px 12px 0; }
        .toolbar button { font-size: 14px; padding: 8px 14px; cursor: pointer; }
        .toolbar .hint { font-size: 12px; color: #666; margin-left: 10px; }
        .page { width: 200mm; margin: 0 auto; padding: 0; page-break-inside: avoid; break-inside: avoid; }
        /* Fixed row height, never 1fr — a grid that crosses a browser print page
           boundary re-solves rows and stacks/clips barcodes (see honey sheets).
           Cap the sheet at exactly 17 rows so our .page sections stay atomic. */
        .sheet {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          grid-auto-rows: 16.8mm;
          gap: 0;
          width: 100%;
          height: 285.6mm;
          max-height: 285.6mm;
          overflow: hidden;
          border-top: 0.35pt dashed #999;
          border-left: 0.35pt dashed #999;
          page-break-inside: avoid;
          break-inside: avoid;
        }
        .label, .banner {
          height: 16.8mm;
          max-height: 16.8mm;
          border: none;
          border-right: 0.35pt dashed #999;
          border-bottom: 0.35pt dashed #999;
          padding: 2mm 5mm;
          text-align: center;
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          overflow: hidden;
          break-inside: avoid;
          page-break-inside: avoid;
        }
        .banner {
          grid-column: 1 / -1;
          background: #f3f3f3;
          padding: 1.5mm 3mm;
        }
        .banner-inner {
          font-size: 8pt;
          font-weight: 700;
          letter-spacing: 0.2pt;
          line-height: 1.2;
          width: 100%;
          text-align: left;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .label.empty .bc { visibility: hidden; }
        .bc { line-height: 0; width: 100%; max-height: 11mm; overflow: hidden; }
        .bc svg { width: 100%; max-height: 11mm; height: auto; display: block; }
        .code {
          margin-top: 0.6mm;
          font-size: 7pt;
          font-weight: 700;
          letter-spacing: 0.6pt;
          font-family: Consolas, monospace;
          line-height: 1;
        }
        .label.empty .code { visibility: hidden; }
        .page-break { page-break-before: always; break-before: page; }
        @media print {
          .toolbar { display: none; }
          body { margin: 0; }
          .page { width: auto; margin: 0; }
          .sheet, .banner {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
        }
        </style></head><body>
        <div class="toolbar">
          <button type="button" onclick="window.print()">Print A4</button>
          <span class="hint">' . (int) $labelCount . ' label(s) · ' . (int) $lineCount
        . ' batch line(s) · continuous packing · 68 slots/page · print at 100% scale</span>
        </div>
        ' . $pagesHtml . '
        </body></html>';
}
