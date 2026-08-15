<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/dept_production.php';

const DEPT_POUCH_CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
const DEPT_POUCH_CODE_LENGTH = 6;

/** Dept products that use the barcode stock-in flow (pouches or jars). */
const DEPT_POUCH_BARCODE_KEYS = ['pt', 'brc', 'jar_cacao'];

function dept_pouch_can_ops(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'staff', 'admin'], true);
}

function dept_pouch_supports(string $productKey): bool
{
    return in_array($productKey, DEPT_POUCH_BARCODE_KEYS, true);
}

function dept_pouch_normalize_product_key(string $productKey): string
{
    $productKey = strtolower(trim($productKey));
    if (!dept_pouch_supports($productKey)) {
        throw new RuntimeException('Pouch barcodes are not enabled for this product.');
    }

    return $productKey;
}

function dept_pouch_product_label(string $productKey): string
{
    $product = dept_product_by_key($productKey);

    return (string) ($product['label'] ?? $productKey);
}

/** Sell-unit noun for labels / print sheet titles (pouch vs jar). */
function dept_pouch_unit_singular(string $productKey): string
{
    return $productKey === 'jar_cacao' ? 'jar' : 'pouch';
}

function dept_pouch_normalize_code(string $code): string
{
    return strtoupper(trim($code));
}

function dept_pouch_normalize_batch_no(string $batchNo): string
{
    $batchNo = strtoupper(trim($batchNo));
    if (!preg_match('/^[A-Z0-9][A-Z0-9-]{2,31}$/', $batchNo)) {
        throw new RuntimeException('Batch code must be 3–32 letters, digits or dashes (e.g. JR073026).');
    }

    return $batchNo;
}

/**
 * Codes are unique across every dept product so a sticker can never be
 * ambiguous at the scanner.
 *
 * @param array<string, true> $used
 */
function dept_pouch_random_code(PDO $pdo, array &$used): string
{
    $alphabet = DEPT_POUCH_CODE_ALPHABET;
    $max = strlen($alphabet) - 1;
    $claim = $pdo->prepare('INSERT INTO dept_pouch_codes_issued (code) VALUES (?)');
    for ($attempt = 0; $attempt < 80; $attempt++) {
        $code = '';
        $hasLetter = false;
        $hasDigit = false;
        for ($i = 0; $i < DEPT_POUCH_CODE_LENGTH; $i++) {
            $ch = $alphabet[random_int(0, $max)];
            $code .= $ch;
            if (ctype_alpha($ch)) {
                $hasLetter = true;
            } else {
                $hasDigit = true;
            }
        }
        if (!$hasLetter || !$hasDigit || isset($used[$code])) {
            continue;
        }
        try {
            $claim->execute([$code]);
        } catch (PDOException $e) {
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            $driver = (int) ($e->errorInfo[1] ?? 0);
            if ($sqlState === '23000' || $driver === 1062) {
                continue;
            }
            throw $e;
        }
        $used[$code] = true;

        return $code;
    }

    throw new RuntimeException('Could not allocate a unique pouch barcode.');
}

function dept_pouch_next_sequence(PDO $pdo, int $qty): int
{
    $st = $pdo->query('SELECT next_seq FROM dept_pouch_barcode_seq WHERE id = 1 FOR UPDATE');
    $start = (int) $st->fetchColumn();
    if ($start < 1) {
        $start = 1;
    }
    $pdo->prepare('UPDATE dept_pouch_barcode_seq SET next_seq = ? WHERE id = 1')
        ->execute([$start + $qty]);

    return $start;
}

/**
 * @return array<string, mixed>
 */
function dept_pouch_serialize_request(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'product_key' => (string) ($row['product_key'] ?? 'pt'),
        'requested_by' => (int) $row['requested_by'],
        'requested_by_name' => (string) ($row['requested_by_name'] ?? ''),
        'batch_no' => (string) $row['batch_no'],
        'quantity' => (int) $row['quantity'],
        'status' => (string) $row['status'],
        'notes' => $row['notes'] !== null ? (string) $row['notes'] : null,
        'created_at' => (string) $row['created_at'],
        'scanned_count' => isset($row['scanned_count']) ? (int) $row['scanned_count'] : 0,
        'generated_count' => isset($row['generated_count']) ? (int) $row['generated_count'] : 0,
        'latest_batch_id' => isset($row['latest_batch_id']) && $row['latest_batch_id'] !== null
            ? (int) $row['latest_batch_id'] : null,
    ];
}

/**
 * @return array<string, mixed>
 */
function dept_pouch_serialize_batch(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'product_key' => (string) ($row['product_key'] ?? 'pt'),
        'request_id' => $row['request_id'] !== null ? (int) $row['request_id'] : null,
        'generated_by' => (int) $row['generated_by'],
        'generated_by_name' => (string) ($row['generated_by_name'] ?? ''),
        'batch_no' => (string) $row['batch_no'],
        'batch_date' => (string) $row['batch_date'],
        'quantity_generated' => (int) $row['quantity_generated'],
        'printed_at' => $row['printed_at'] !== null ? (string) $row['printed_at'] : null,
        'created_at' => (string) $row['created_at'],
        'scanned_count' => isset($row['scanned_count']) ? (int) $row['scanned_count'] : 0,
    ];
}

/**
 * @return array<string, mixed>
 */
function dept_pouch_request_create(
    PDO $pdo,
    int $userId,
    string $productKey,
    string $batchNo,
    int $qty,
    ?string $notes
): array {
    $productKey = dept_pouch_normalize_product_key($productKey);
    $batchNo = dept_pouch_normalize_batch_no($batchNo);
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    $notes = $notes !== null ? trim($notes) : null;
    if ($notes === '') {
        $notes = null;
    }
    if ($notes !== null && strlen($notes) > 512) {
        $notes = substr($notes, 0, 512);
    }
    $pdo->prepare(
        'INSERT INTO dept_pouch_requests (requested_by, product_key, batch_no, quantity, notes)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$userId, $productKey, $batchNo, $qty, $notes]);

    return dept_pouch_request_get($pdo, (int) $pdo->lastInsertId());
}

/**
 * @return array<string, mixed>
 */
function dept_pouch_request_get(PDO $pdo, int $id): array
{
    $st = $pdo->prepare(
        'SELECT r.*, u.name AS requested_by_name,
                (SELECT COUNT(*) FROM dept_pouch_barcode_batches b
                  INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
                  WHERE b.request_id = r.id AND c.status = "scanned") AS scanned_count,
                (SELECT COUNT(*) FROM dept_pouch_barcode_batches b
                  INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
                  WHERE b.request_id = r.id) AS generated_count,
                (SELECT MAX(b.id) FROM dept_pouch_barcode_batches b WHERE b.request_id = r.id) AS latest_batch_id
         FROM dept_pouch_requests r
         LEFT JOIN users u ON u.id = r.requested_by
         WHERE r.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Request not found.');
    }

    return dept_pouch_serialize_request($row);
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int}
 */
function dept_pouch_request_list(PDO $pdo, string $productKey, string $status, int $page, int $perPage): array
{
    $productKey = dept_pouch_normalize_product_key($productKey);
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $where = 'r.product_key = ?';
    $params = [$productKey];
    if ($status !== '' && $status !== 'all') {
        $where .= ' AND r.status = ?';
        $params[] = $status;
    }
    $cst = $pdo->prepare("SELECT COUNT(*) FROM dept_pouch_requests r WHERE {$where}");
    $cst->execute($params);
    $total = (int) $cst->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $st = $pdo->prepare(
        "SELECT r.*, u.name AS requested_by_name,
                (SELECT COUNT(*) FROM dept_pouch_barcode_batches b
                  INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
                  WHERE b.request_id = r.id AND c.status = \"scanned\") AS scanned_count,
                (SELECT COUNT(*) FROM dept_pouch_barcode_batches b
                  INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
                  WHERE b.request_id = r.id) AS generated_count,
                (SELECT MAX(b.id) FROM dept_pouch_barcode_batches b WHERE b.request_id = r.id) AS latest_batch_id
         FROM dept_pouch_requests r
         LEFT JOIN users u ON u.id = r.requested_by
         WHERE {$where}
         ORDER BY r.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = dept_pouch_serialize_request($row);
    }

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
}

function dept_pouch_request_cancel(PDO $pdo, int $id, int $userId, string $role): void
{
    $req = dept_pouch_request_get($pdo, $id);
    if ($req['status'] !== 'pending') {
        throw new RuntimeException('Only pending requests can be cancelled.');
    }
    if ((int) $req['requested_by'] !== $userId && !in_array($role, ['executive', 'prod_manager', 'admin'], true)) {
        throw new RuntimeException('Forbidden.');
    }
    $pdo->prepare('UPDATE dept_pouch_requests SET status = "cancelled" WHERE id = ?')->execute([$id]);
}

function dept_pouch_pending_request_count(PDO $pdo, string $productKey): int
{
    $productKey = dept_pouch_normalize_product_key($productKey);
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM dept_pouch_requests WHERE product_key = ? AND status = "pending"'
    );
    $st->execute([$productKey]);

    return (int) $st->fetchColumn();
}

/**
 * @return array<string, mixed>
 */
function dept_pouch_batch_get(PDO $pdo, int $id): array
{
    $st = $pdo->prepare(
        'SELECT b.*, u.name AS generated_by_name,
                (SELECT COUNT(*) FROM dept_pouch_barcodes c WHERE c.batch_id = b.id AND c.status = "scanned") AS scanned_count
         FROM dept_pouch_barcode_batches b
         LEFT JOIN users u ON u.id = b.generated_by
         WHERE b.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Batch not found.');
    }

    return dept_pouch_serialize_batch($row);
}

/**
 * @return list<array<string, mixed>>
 */
function dept_pouch_batch_list(PDO $pdo, string $productKey): array
{
    $productKey = dept_pouch_normalize_product_key($productKey);
    $st = $pdo->prepare(
        'SELECT b.*, u.name AS generated_by_name,
                (SELECT COUNT(*) FROM dept_pouch_barcodes c WHERE c.batch_id = b.id AND c.status = "scanned") AS scanned_count
         FROM dept_pouch_barcode_batches b
         LEFT JOIN users u ON u.id = b.generated_by
         WHERE b.product_key = ?
         ORDER BY b.id DESC
         LIMIT 50'
    );
    $st->execute([$productKey]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = dept_pouch_serialize_batch($row);
    }

    return $rows;
}

/**
 * @return array<string, mixed>
 */
function dept_pouch_generate_for_request(PDO $pdo, int $userId, int $requestId): array
{
    $req = dept_pouch_request_get($pdo, $requestId);
    if ($req['status'] === 'cancelled') {
        throw new RuntimeException('Cannot generate barcodes for a cancelled request.');
    }
    if ($req['status'] !== 'pending' && (int) $req['generated_count'] > 0) {
        throw new RuntimeException('Barcodes already generated for this request.');
    }
    $productKey = dept_pouch_normalize_product_key((string) $req['product_key']);
    $qty = (int) $req['quantity'];
    $batchNo = (string) $req['batch_no'];
    $batchDate = date('Y-m-d');
    if (preg_match('/(\d{6})$/', $batchNo, $m)) {
        $mm = (int) substr($m[1], 0, 2);
        $dd = (int) substr($m[1], 2, 2);
        $yy = (int) substr($m[1], 4, 2);
        if (checkdate($mm, $dd, 2000 + $yy)) {
            $batchDate = sprintf('%04d-%02d-%02d', 2000 + $yy, $mm, $dd);
        }
    }

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $start = dept_pouch_next_sequence($pdo, $qty);
        $pdo->prepare(
            'INSERT INTO dept_pouch_barcode_batches (
                request_id, generated_by, product_key, batch_no, batch_date, quantity_generated
             ) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$requestId, $userId, $productKey, $batchNo, $batchDate, $qty]);
        $batchId = (int) $pdo->lastInsertId();
        $insC = $pdo->prepare(
            'INSERT INTO dept_pouch_barcodes (batch_id, code, sequence_no, status)
             VALUES (?, ?, ?, "generated")'
        );
        $used = [];
        for ($i = 0; $i < $qty; $i++) {
            $insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);
        }
        $pdo->prepare(
            'UPDATE dept_pouch_requests SET status = "barcodes_generated"
             WHERE id = ? AND status IN ("pending","barcodes_generated","in_progress")'
        )->execute([$requestId]);
        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return dept_pouch_batch_get($pdo, $batchId);
}

/**
 * @return array<string, mixed>
 */
function dept_pouch_mark_printed(PDO $pdo, int $batchId): array
{
    $pdo->prepare(
        'UPDATE dept_pouch_barcode_batches SET printed_at = COALESCE(printed_at, NOW()) WHERE id = ?'
    )->execute([$batchId]);
    $pdo->prepare(
        'UPDATE dept_pouch_barcodes SET status = "printed"
         WHERE batch_id = ? AND status = "generated"'
    )->execute([$batchId]);

    return dept_pouch_batch_get($pdo, $batchId);
}

/**
 * Scan sticker → unit + finished pouch +1. No WIP / packaging deduct (MVP).
 *
 * @return array<string, mixed>
 */
function dept_pouch_scan_stock_in(PDO $pdo, int $userId, string $productKey, string $code): array
{
    $productKey = dept_pouch_normalize_product_key($productKey);
    $code = dept_pouch_normalize_code($code);
    $unitId = 0;
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT c.*, b.batch_no, b.request_id, b.product_key
             FROM dept_pouch_barcodes c
             INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
             WHERE c.code = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('Unknown barcode.');
        }
        $codeProduct = (string) ($row['product_key'] ?? 'pt');
        if ($codeProduct !== $productKey) {
            throw new RuntimeException(
                'This barcode belongs to ' . dept_pouch_product_label($codeProduct) . '.'
            );
        }
        if ((string) $row['status'] === 'void') {
            throw new RuntimeException('This barcode is void.');
        }
        $exist = $pdo->prepare('SELECT id FROM dept_pouch_units WHERE barcode_id = ? LIMIT 1');
        $exist->execute([(int) $row['id']]);
        if ($exist->fetchColumn() || (string) $row['status'] === 'scanned') {
            throw new RuntimeException('Already scanned into inventory.');
        }
        $pdo->prepare(
            'UPDATE dept_pouch_barcodes SET status = "scanned", scanned_at = NOW(), scanned_by = ? WHERE id = ?'
        )->execute([$userId, (int) $row['id']]);
        $pdo->prepare(
            'INSERT INTO dept_pouch_units (barcode_id, product_key, batch_no, status, created_date)
             VALUES (?, ?, ?, "in_stock", CURDATE())'
        )->execute([(int) $row['id'], $productKey, (string) $row['batch_no']]);
        $unitId = (int) $pdo->lastInsertId();

        $fin = dept_inventory_ensure_row($pdo, $userId, $productKey);
        $pdo->prepare(
            'UPDATE dept_inventory_rows
             SET current_qty = current_qty + 1, initial_qty = initial_qty + 1,
                 product_label = ?, last_stock_in_at = NOW()
             WHERE id = ?'
        )->execute([dept_pouch_product_label($productKey), (int) $fin['id']]);

        if (!empty($row['request_id'])) {
            $reqId = (int) $row['request_id'];
            $counts = $pdo->prepare(
                'SELECT
                    SUM(c.status = "scanned") AS scanned,
                    COUNT(*) AS total
                 FROM dept_pouch_barcode_batches b
                 INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
                 WHERE b.request_id = ?'
            );
            $counts->execute([$reqId]);
            $c = $counts->fetch(PDO::FETCH_ASSOC) ?: [];
            $scanned = (int) ($c['scanned'] ?? 0);
            $total = (int) ($c['total'] ?? 0);
            $status = $scanned >= $total && $total > 0 ? 'completed' : 'in_progress';
            $pdo->prepare('UPDATE dept_pouch_requests SET status = ? WHERE id = ? AND status <> "cancelled"')
                ->execute([$status, $reqId]);
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

    $finished = dept_inventory_fetch_row($pdo, $productKey);
    $out = [
        'code' => $code,
        'product_key' => $productKey,
        'batch_no' => (string) $row['batch_no'],
        'unit_id' => $unitId,
        'finished_on_hand' => $finished !== null ? (float) $finished['current_qty'] : null,
    ];
    if (!empty($row['request_id'])) {
        $out['request'] = dept_pouch_request_get($pdo, (int) $row['request_id']);
    }

    return $out;
}

/**
 * @return list<array<string, mixed>>
 */
function dept_pouch_batch_codes(PDO $pdo, int $batchId): array
{
    $st = $pdo->prepare(
        'SELECT c.code, c.sequence_no, c.status, b.batch_no
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         WHERE c.batch_id = ?
         ORDER BY c.sequence_no ASC'
    );
    $st->execute([$batchId]);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function dept_pouch_label_sheet_html(PDO $pdo, int $batchId): string
{
    require_once __DIR__ . '/honey_bottle_barcodes.php';
    $batch = dept_pouch_batch_get($pdo, $batchId);
    $codes = dept_pouch_batch_codes($pdo, $batchId);
    $batchNo = htmlspecialchars((string) $batch['batch_no'], ENT_QUOTES, 'UTF-8');
    $productLabel = dept_pouch_product_label((string) $batch['product_key']);
    $labels = [];
    foreach ($codes as $row) {
        if ((string) $row['status'] === 'void') {
            continue;
        }
        $code = (string) $row['code'];
        try {
            $svg = honey_bottle_code128_svg($code);
        } catch (Throwable) {
            $svg = '<div class="err">barcode lib missing</div>';
        }
        $safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $labels[] = '<div class="label"><div class="bc" title="' . $safe . '">' . $svg
            . '</div><div class="code">' . $safe . '</div></div>';
    }

    $cols = 4;
    $rowsPerPage = 17;
    $perPage = $cols * $rowsPerPage;
    $pagesHtml = '';
    $chunks = array_chunk($labels, $perPage);
    if ($chunks === []) {
        $chunks = [[]];
    }
    foreach ($chunks as $i => $chunk) {
        while (count($chunk) < $perPage) {
            $chunk[] = '<div class="label empty"></div>';
        }
        $pageClass = $i === 0 ? 'page' : 'page page-break';
        $pagesHtml .= '<section class="' . $pageClass . '"><div class="sheet">'
            . implode('', $chunk) . '</div></section>';
    }
    $unit = dept_pouch_unit_singular((string) $batch['product_key']);
    $title = $productLabel . ' ' . $unit . ' labels · ' . $batchNo;
    $count = count($labels);

    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</title><style>
        @page { size: A4 portrait; margin: 5mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #fff; color: #111; }
        .toolbar { margin: 10px 12px 0; }
        .toolbar button { font-size: 14px; padding: 8px 14px; cursor: pointer; }
        .toolbar .hint { font-size: 12px; color: #666; margin-left: 10px; }
        .page { width: 200mm; min-height: 287mm; margin: 0 auto; padding: 0; }
        .sheet {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          grid-template-rows: repeat(17, 1fr);
          gap: 0;
          width: 100%;
          height: 287mm;
          border-top: 0.35pt dashed #999;
          border-left: 0.35pt dashed #999;
        }
        .label {
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
        }
        .label.empty .bc { visibility: hidden; }
        .bc { line-height: 0; width: 100%; }
        .bc svg { width: 100%; height: auto; display: block; }
        .code {
          margin-top: 0.6mm;
          font-size: 7pt;
          font-weight: 700;
          letter-spacing: 0.6pt;
          font-family: Consolas, monospace;
          line-height: 1;
        }
        .label.empty .code { visibility: hidden; }
        .page-break { page-break-before: always; }
        @media print {
          .toolbar { display: none; }
          body { margin: 0; }
          .page { width: auto; min-height: auto; margin: 0; }
          .sheet {
            height: 287mm;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
        }
        </style></head><body>
        <div class="toolbar">
          <button type="button" onclick="window.print()">Print A4</button>
          <span class="hint">' . (int) $count . ' label(s) · ' . htmlspecialchars($productLabel, ENT_QUOTES, 'UTF-8')
        . ' · batch ' . $batchNo . ' · 68 per A4 · print at 100% scale</span>
        </div>
        ' . $pagesHtml . '
        </body></html>';
}
