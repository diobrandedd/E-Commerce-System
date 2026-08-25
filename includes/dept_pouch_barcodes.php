<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/dept_production.php';
require_once __DIR__ . '/rts_schema.php';

const DEPT_POUCH_CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
const DEPT_POUCH_CODE_LENGTH = 6;

/** Dept products that use the barcode stock-in flow (pouches or jars). */
const DEPT_POUCH_BARCODE_KEYS = ['pt', 'brc', 'jar_cacao', 'honey_comb'];

/** Products that support finished-unit defect barcode scan (MVP: Jar Cacao). */
const DEPT_POUCH_DEFECT_KEYS = ['jar_cacao'];

function dept_pouch_can_ops(array $user, ?string $productKey = null): bool
{
    if ($productKey !== null && trim($productKey) !== '') {
        return ims_user_can_dept_line($user, $productKey, 'barcodes');
    }

    return ims_user_can($user, 'dept.pouch_barcodes')
        || ims_user_can($user, 'dept.paragis.barcodes')
        || ims_user_can($user, 'dept.brc.barcodes')
        || ims_user_can($user, 'dept.cacao.barcodes')
        || ims_user_can($user, 'dept.comb.barcodes');
}

function dept_pouch_supports_defect(string $productKey): bool
{
    return in_array(strtolower(trim($productKey)), DEPT_POUCH_DEFECT_KEYS, true);
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
    return in_array($productKey, ['jar_cacao', 'honey_comb'], true) ? 'jar' : 'pouch';
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
        'source' => (string) ($row['source'] ?? 'request'),
        'rts_label_job_id' => isset($row['rts_label_job_id']) && $row['rts_label_job_id'] !== null
            ? (int) $row['rts_label_job_id'] : null,
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
 * Pending barcode-request counts for product lines the user can operate.
 *
 * @return array<string, int>
 */
function dept_pouch_pending_request_counts_for_user(PDO $pdo, array $user): array
{
    $counts = [];
    foreach (DEPT_POUCH_BARCODE_KEYS as $key) {
        if (!dept_pouch_can_ops($user, $key)) {
            continue;
        }
        $counts[$key] = dept_pouch_pending_request_count($pdo, $key);
    }

    return $counts;
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
 * Generate dept pouch/jar barcodes for an RTS label job (no request row).
 *
 * @return array<string, mixed>
 */
function dept_pouch_generate_rts_label(
    PDO $pdo,
    int $userId,
    string $productKey,
    string $batchNo,
    string $bottlingDate,
    int $qty,
    int $rtsLabelJobId
): array {
    $productKey = dept_pouch_normalize_product_key($productKey);
    $batchNo = dept_pouch_normalize_batch_no($batchNo);
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    if ($rtsLabelJobId < 1) {
        throw new RuntimeException('rts_label_job_id is required.');
    }
    $ts = strtotime(trim($bottlingDate));
    if ($ts === false) {
        throw new RuntimeException('Invalid bottling date.');
    }
    $batchDate = date('Y-m-d', $ts);

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $start = dept_pouch_next_sequence($pdo, $qty);
        $pdo->prepare(
            'INSERT INTO dept_pouch_barcode_batches (
                request_id, source, rts_label_job_id, generated_by, product_key, batch_no, batch_date, quantity_generated
             ) VALUES (NULL, "rts_label", ?, ?, ?, ?, ?, ?)'
        )->execute([$rtsLabelJobId, $userId, $productKey, $batchNo, $batchDate, $qty]);
        $batchId = (int) $pdo->lastInsertId();
        $insC = $pdo->prepare(
            'INSERT INTO dept_pouch_barcodes (batch_id, code, sequence_no, status)
             VALUES (?, ?, ?, "generated")'
        );
        $used = [];
        for ($i = 0; $i < $qty; $i++) {
            $insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);
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

function dept_pouch_scan_report_date(?string $scanDate): ?string
{
    $scanDate = trim((string) $scanDate);
    if ($scanDate === '' || strtolower($scanDate) === 'all') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $scanDate);
    if ($date === false || $date->format('Y-m-d') !== $scanDate) {
        throw new RuntimeException('Invalid scan date.');
    }

    return $scanDate;
}

/**
 * Gross finished-unit scan-in counts by request/batch.
 *
 * @return array{dates:list<string>,rows:list<array<string,mixed>>,total:int,page:int,per_page:int,scan_date:?string}
 */
function dept_pouch_scan_report(
    PDO $pdo,
    string $productKey,
    ?string $scanDate = null,
    int $page = 1,
    int $perPage = 25
): array {
    $productKey = dept_pouch_normalize_product_key($productKey);
    $scanDate = dept_pouch_scan_report_date($scanDate);
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));

    $datesSt = $pdo->prepare(
        'SELECT DISTINCT DATE(c.scanned_at) AS scan_date
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         WHERE b.product_key = ? AND c.scanned_at IS NOT NULL
         ORDER BY scan_date DESC'
    );
    $datesSt->execute([$productKey]);
    $dates = [];
    foreach ($datesSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $dates[] = (string) $row['scan_date'];
    }

    $scanWhere = 'b.product_key = ? AND c.scanned_at IS NOT NULL';
    $scanParams = [$productKey];
    if ($scanDate !== null) {
        $from = $scanDate . ' 00:00:00';
        $to = (new DateTimeImmutable($scanDate))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        $scanWhere .= ' AND c.scanned_at >= ? AND c.scanned_at < ?';
        $scanParams[] = $from;
        $scanParams[] = $to;
    }
    $scanSubquery = "SELECT b.request_id, COUNT(*) AS scanned_count
                     FROM dept_pouch_barcode_batches b
                     INNER JOIN dept_pouch_barcodes c ON c.batch_id = b.id
                     WHERE {$scanWhere}
                     GROUP BY b.request_id";
    $joinType = $scanDate === null ? 'LEFT JOIN' : 'INNER JOIN';

    if ($scanDate === null) {
        $countSt = $pdo->prepare(
            'SELECT COUNT(*) FROM dept_pouch_requests r WHERE r.product_key = ?'
        );
        $countSt->execute([$productKey]);
    } else {
        $countSt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM dept_pouch_requests r
             INNER JOIN ({$scanSubquery}) s ON s.request_id = r.id
             WHERE r.product_key = ?"
        );
        $countSt->execute(array_merge($scanParams, [$productKey]));
    }
    $total = (int) $countSt->fetchColumn();
    $offset = ($page - 1) * $perPage;

    $st = $pdo->prepare(
        "SELECT r.*, u.name AS requested_by_name,
                COALESCE(s.scanned_count, 0) AS scanned_count,
                (SELECT COUNT(*) FROM dept_pouch_barcode_batches b2
                  INNER JOIN dept_pouch_barcodes c2 ON c2.batch_id = b2.id
                  WHERE b2.request_id = r.id) AS generated_count,
                (SELECT MAX(b3.id) FROM dept_pouch_barcode_batches b3
                  WHERE b3.request_id = r.id) AS latest_batch_id
         FROM dept_pouch_requests r
         LEFT JOIN users u ON u.id = r.requested_by
         {$joinType} ({$scanSubquery}) s ON s.request_id = r.id
         WHERE r.product_key = ?
         ORDER BY r.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute(array_merge($scanParams, [$productKey]));
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = dept_pouch_serialize_request($row);
    }

    $sumSt = $pdo->prepare(
        "SELECT COUNT(*) FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         WHERE {$scanWhere}"
    );
    $sumSt->execute($scanParams);
    $scannedTotal = (int) $sumSt->fetchColumn();

    return [
        'dates' => $dates,
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'scan_date' => $scanDate,
        'scanned_total' => $scannedTotal,
    ];
}

/**
 * Scanned stickers for a label request — who stocked them in and when (read-only).
 *
 * @return array{request: array<string,mixed>, rows: list<array<string,mixed>>, total: int, page: int, per_page: int}
 */
function dept_pouch_request_scan_log(
    PDO $pdo,
    int $requestId,
    int $page = 1,
    int $perPage = 50,
    ?string $scanDate = null
): array
{
    $req = dept_pouch_request_get($pdo, $requestId);
    $page = max(1, $page);
    $perPage = max(1, min(200, $perPage));
    $offset = ($page - 1) * $perPage;
    $scanDate = dept_pouch_scan_report_date($scanDate);
    $dateSql = '';
    $params = [$requestId];
    if ($scanDate !== null) {
        $from = $scanDate . ' 00:00:00';
        $to = (new DateTimeImmutable($scanDate))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        $dateSql = ' AND c.scanned_at >= ? AND c.scanned_at < ?';
        $params[] = $from;
        $params[] = $to;
    }

    $cst = $pdo->prepare(
        'SELECT COUNT(*)
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         WHERE b.request_id = ? AND c.scanned_at IS NOT NULL' . $dateSql
    );
    $cst->execute($params);
    $total = (int) $cst->fetchColumn();

    $st = $pdo->prepare(
        'SELECT c.code, c.scanned_at, c.scanned_by,
                u.name AS scanned_by_name,
                b.batch_no,
                un.status AS unit_status
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN users u ON u.id = c.scanned_by
         LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
         WHERE b.request_id = ? AND c.scanned_at IS NOT NULL' . $dateSql . '
         ORDER BY c.scanned_at DESC, c.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . $offset
    );
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = [
            'code' => (string) $row['code'],
            'batch_no' => (string) $row['batch_no'],
            'scanned_at' => (string) $row['scanned_at'],
            'scanned_by' => $row['scanned_by'] !== null ? (int) $row['scanned_by'] : null,
            'scanned_by_name' => (string) ($row['scanned_by_name'] ?? ''),
            'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
        ];
    }

    return [
        'request' => $req,
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'scan_date' => $scanDate,
    ];
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
        .page { width: 200mm; margin: 0 auto; padding: 0; }
        /* Fixed row height, never 1fr — fractional rows get re-solved when the
           grid crosses a page boundary and stack cells on page 2. See
           honey_bottle_label_sheet_html(). */
        .sheet {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          grid-auto-rows: 16.8mm;
          gap: 0;
          width: 100%;
          border-top: 0.35pt dashed #999;
          border-left: 0.35pt dashed #999;
        }
        .label {
          height: 16.8mm;
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
          .page { width: auto; margin: 0; }
          .sheet {
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

/**
 * @return array<string, mixed>
 */
function dept_pouch_pos_lookup(PDO $pdo, string $code): array
{
    $code = dept_pouch_normalize_code($code);
    if ($code === '') {
        throw new RuntimeException('Scan a barcode.');
    }
    $st = $pdo->prepare(
        'SELECT c.id AS barcode_id, c.code, c.status AS barcode_status,
                b.product_key, b.batch_no,
                un.id AS unit_id, un.status AS unit_status
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
         WHERE c.code = ?
         LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Unknown barcode.');
    }
    if ((string) $row['barcode_status'] === 'void') {
        throw new RuntimeException('This barcode is void.');
    }
    if ($row['unit_id'] === null) {
        throw new RuntimeException('This unit is not in inventory yet.');
    }
    $unitStatus = (string) ($row['unit_status'] ?? '');
    if ($unitStatus !== 'in_stock') {
        throw new RuntimeException('This unit is not available (' . $unitStatus . ').');
    }
    $productKey = (string) $row['product_key'];

    return [
        'kind' => 'dept',
        'barcode_id' => (int) $row['barcode_id'],
        'code' => (string) $row['code'],
        'product_key' => $productKey,
        'product_label' => dept_pouch_product_label($productKey),
        'batch_no' => (string) $row['batch_no'],
        'unit_id' => (int) $row['unit_id'],
        'unit_status' => $unitStatus,
        'unit_noun' => dept_pouch_unit_singular($productKey),
    ];
}

/**
 * @param list<int> $unitIds
 */
function dept_pouch_pos_consume(PDO $pdo, array $unitIds, int $saleId): void
{
    if ($unitIds === []) {
        return;
    }
    require_once __DIR__ . '/dept_inventory.php';
    require_once __DIR__ . '/rts_inventory_sync.php';
    $hasPool = rts_db_has_column($pdo, 'dept_pouch_units', 'pool');
    foreach ($unitIds as $unitId) {
        $unitId = (int) $unitId;
        if ($unitId < 1) {
            throw new RuntimeException('Invalid pouch/jar unit.');
        }
        $st = $pdo->prepare('SELECT * FROM dept_pouch_units WHERE id = ? FOR UPDATE');
        $st->execute([$unitId]);
        $unit = $st->fetch(PDO::FETCH_ASSOC);
        if ($unit === false) {
            throw new RuntimeException('Pouch/jar unit not found.');
        }
        if ((string) $unit['status'] !== 'in_stock') {
            throw new RuntimeException('A scanned unit is no longer in stock.');
        }
        $productKey = (string) $unit['product_key'];
        $pool = $hasPool ? (string) ($unit['pool'] ?? 'finished') : 'finished';
        $upd = $pdo->prepare(
            'UPDATE dept_pouch_units
             SET status = "sold", consumed_at = NOW(), consumed_ref_type = "pos_sale", consumed_ref_id = ?
             WHERE id = ? AND status = "in_stock"'
        );
        $upd->execute([$saleId, $unitId]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Could not mark unit as sold.');
        }
        if ($pool === 'rts_sellable') {
            rts_inventory_deduct_sell_other_label(
                $pdo,
                0,
                dept_pouch_product_label($productKey),
                1.0,
                'walk_in',
                'sellable',
                [
                    'used_where' => 'pos',
                    'pos_sale_id' => $saleId,
                ]
            );
        } else {
            dept_inventory_deduct($pdo, $productKey, 1.0);
        }
    }
}

/**
 * @param list<string|int> $codes
 * @return array{received: list<array<string,mixed>>, warnings: list<string>}
 */
function dept_pouch_rts_receive(PDO $pdo, array $codes, string $pool): array
{
    if (!in_array($pool, ['rts_sellable', 'rts_damaged'], true)) {
        throw new RuntimeException('Invalid RTS pool.');
    }
    $hasPool = rts_db_has_column($pdo, 'dept_pouch_units', 'pool');
    $targetStatus = $pool === 'rts_damaged' ? 'damaged' : 'in_stock';
    $received = [];
    $warnings = [];
    foreach ($codes as $raw) {
        $code = dept_pouch_normalize_code((string) $raw);
        if ($code === '') {
            continue;
        }
        $st = $pdo->prepare(
            'SELECT c.id AS barcode_id, c.code, un.id AS unit_id, un.status AS unit_status, b.product_key
             FROM dept_pouch_barcodes c
             INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
             LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
             WHERE c.code = ?
             LIMIT 1'
        );
        $st->execute([$code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            $warnings[] = $code . ': unknown barcode (unreadable sticker fallback needed).';
            continue;
        }
        if ($row['unit_id'] === null) {
            $warnings[] = $code . ': never stocked in — skipped.';
            continue;
        }
        $unitStatus = (string) ($row['unit_status'] ?? '');
        if ($unitStatus === 'in_stock') {
            $warnings[] = $code . ': still in stock — pack scan was missed.';
        }
        if ($hasPool) {
            $pdo->prepare(
                'UPDATE dept_pouch_units
                 SET status = ?, pool = ?, consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ?'
            )->execute([$targetStatus, $pool, (int) $row['unit_id']]);
        } else {
            $legacyStatus = $pool === 'rts_damaged' ? 'damaged' : 'rts';
            $pdo->prepare(
                'UPDATE dept_pouch_units
                 SET status = ?, consumed_at = NOW(), consumed_ref_type = "rts_case", consumed_ref_id = NULL
                 WHERE id = ?'
            )->execute([$legacyStatus, (int) $row['unit_id']]);
        }
        $productKey = (string) $row['product_key'];
        $received[] = [
            'code' => (string) $row['code'],
            'product_key' => $productKey,
            'product_label' => dept_pouch_product_label($productKey),
            'unit_id' => (int) $row['unit_id'],
            'pool' => $pool,
        ];
    }

    return ['received' => $received, 'warnings' => $warnings];
}

/**
 * Scan a dept pouch/jar barcode into RTS sellable inventory.
 *
 * @return array<string, mixed>
 */
function dept_pouch_rts_stock_in_scan(PDO $pdo, int $userId, string $code): array
{
    require_once __DIR__ . '/rts_inventory_sync.php';
    if (!rts_db_has_column($pdo, 'dept_pouch_units', 'pool')) {
        throw new RuntimeException('Run migration phase68 (dept_pouch_units.pool) before RTS stock-in.');
    }
    $code = dept_pouch_normalize_code($code);
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT c.*, b.product_key, b.batch_no,
                    un.id AS unit_id, un.status AS unit_status, un.pool AS unit_pool
             FROM dept_pouch_barcodes c
             INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
             LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
             WHERE c.code = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('Unknown barcode.');
        }
        if ((string) $row['status'] === 'void') {
            throw new RuntimeException('This barcode is void.');
        }
        $productKey = dept_pouch_normalize_product_key((string) $row['product_key']);
        $label = dept_pouch_product_label($productKey);
        $unitId = $row['unit_id'] !== null ? (int) $row['unit_id'] : 0;

        if ($unitId > 0) {
            $unitStatus = (string) ($row['unit_status'] ?? '');
            $unitPool = (string) ($row['unit_pool'] ?? 'finished');
            if ($unitPool === 'rts_sellable' && $unitStatus === 'in_stock') {
                throw new RuntimeException('Already in RTS inventory.');
            }
            if ($unitPool === 'finished' && $unitStatus === 'in_stock') {
                throw new RuntimeException(
                    'This unit is still in finished stock. Use the dept scanner — do not move with RTS stock-in.'
                );
            }
            if (!in_array($unitStatus, ['packed', 'sold', 'shipped', 'rts'], true)) {
                throw new RuntimeException('This unit cannot enter RTS inventory (' . $unitStatus . ').');
            }
            $pdo->prepare(
                'UPDATE dept_pouch_units
                 SET status = "in_stock", pool = "rts_sellable",
                     consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ?'
            )->execute([$unitId]);
            if ((string) $row['status'] !== 'scanned') {
                $pdo->prepare(
                    'UPDATE dept_pouch_barcodes SET status = "scanned", scanned_at = COALESCE(scanned_at, NOW()), scanned_by = ?
                     WHERE id = ?'
                )->execute([$userId, (int) $row['id']]);
            }
            rts_inventory_credit_sellable_other($pdo, $label, 1.0);
        } else {
            $pdo->prepare(
                'UPDATE dept_pouch_barcodes SET status = "scanned", scanned_at = NOW(), scanned_by = ? WHERE id = ?'
            )->execute([$userId, (int) $row['id']]);
            $pdo->prepare(
                'INSERT INTO dept_pouch_units (barcode_id, product_key, batch_no, status, pool, created_date)
                 VALUES (?, ?, ?, "in_stock", "rts_sellable", CURDATE())'
            )->execute([(int) $row['id'], $productKey, (string) $row['batch_no']]);
            $unitId = (int) $pdo->lastInsertId();
            rts_inventory_credit_sellable_other($pdo, $label, 1.0);
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

    return [
        'code' => $code,
        'product_key' => $productKey,
        'product_label' => $label,
        'unit_id' => $unitId,
        'rts_stock_in' => true,
    ];
}

/**
 * Mark an in-stock finished unit as defective: remove from inventory + history row.
 * Does not touch the RTS damaged pool.
 *
 * @return array<string, mixed>
 */
function dept_pouch_defect_scan(PDO $pdo, int $userId, string $productKey, string $code, ?string $note = null): array
{
    require_once __DIR__ . '/finished_leak_damage.php';
    unset($productKey);

    return finished_leak_damage_scan($pdo, $userId, $code, 'damaged', $note);
}

/**
 * @return array{rows: list<array<string,mixed>>, total: int, page: int, per_page: int}
 */
function dept_pouch_defect_history(PDO $pdo, string $productKey, int $page = 1, int $perPage = 25): array
{
    $productKey = dept_pouch_normalize_product_key($productKey);
    if (!dept_pouch_supports_defect($productKey)) {
        throw new RuntimeException('Defect scan is not enabled for this product.');
    }
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));

    $cst = $pdo->prepare('SELECT COUNT(*) FROM dept_pouch_defect_log WHERE product_key = ?');
    $cst->execute([$productKey]);
    $total = (int) $cst->fetchColumn();
    $offset = ($page - 1) * $perPage;

    $st = $pdo->prepare(
        'SELECT d.id, d.product_key, d.code, d.batch_no, d.unit_id, d.scanned_at, d.note,
                d.scanned_by, COALESCE(u.name, CONCAT("User #", d.scanned_by)) AS scanned_by_name
         FROM dept_pouch_defect_log d
         LEFT JOIN users u ON u.id = d.scanned_by
         WHERE d.product_key = ?
         ORDER BY d.scanned_at DESC, d.id DESC
         LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
    );
    $st->execute([$productKey]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = [
            'id' => (int) $row['id'],
            'product_key' => (string) $row['product_key'],
            'code' => (string) $row['code'],
            'batch_no' => (string) $row['batch_no'],
            'unit_id' => (int) $row['unit_id'],
            'scanned_at' => (string) $row['scanned_at'],
            'scanned_by' => (int) $row['scanned_by'],
            'scanned_by_name' => (string) $row['scanned_by_name'],
            'note' => $row['note'] !== null ? (string) $row['note'] : null,
        ];
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ];
}
