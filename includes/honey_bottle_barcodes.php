<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/dept_inventory.php';

const HONEY_BOTTLE_SIZE_LABELS = [
    '250ml' => '250ml',
    '350ml' => '350ml',
    'liter' => '1L',
    'gallon' => 'Gallon',
];

function honey_bottle_size_label(string $size): string
{
    return HONEY_BOTTLE_SIZE_LABELS[$size] ?? $size;
}

function honey_bottle_can_request(array $user): bool
{
    return ims_user_can($user, 'honey.bottle.request');
}

function honey_bottle_can_generate(array $user): bool
{
    return ims_user_can($user, 'honey.bottle.generate');
}

function honey_bottle_can_scan(array $user): bool
{
    return ims_user_can($user, 'honey.bottle.scan');
}

function honey_bottle_can_pack_scan(array $user): bool
{
    return ims_user_can($user, 'deliveries.pack_scan');
}

/** Resolve IMS department key for a session user (honey, csr, …). */
function honey_bottle_user_dept_key(array $user): ?string
{
    $deptId = isset($user['dept_id']) ? (int) $user['dept_id'] : 0;
    if ($deptId < 1) {
        return null;
    }
    try {
        $st = ims_pdo()->prepare('SELECT name FROM departments WHERE id = ? LIMIT 1');
        $st->execute([$deptId]);
        $name = $st->fetchColumn();

        return $name !== false ? strtolower((string) $name) : null;
    } catch (Throwable) {
        return null;
    }
}

function honey_bottle_user_can_generate(array $user): bool
{
    return honey_bottle_can_generate($user);
}

function honey_bottle_normalize_code(string $raw): string
{
    $code = strtoupper(preg_replace('/\s+/', '', trim($raw)) ?? '');

    return $code;
}

/** Unambiguous Code128 alphabet (no 0/O, 1/I/L). */
const HONEY_BOTTLE_CODE_ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
const HONEY_BOTTLE_CODE_LENGTH = 6;

/**
 * Claim a short unique sticker code (6 mixed letters + digits).
 * Inserts into the append-only issued ledger first so the value can never be reused,
 * including after void. Supplier code + bottling date live on the barcode batch.
 *
 * @param array<string, true> $used
 */
function honey_bottle_random_code(PDO $pdo, array &$used): string
{
    $alphabet = HONEY_BOTTLE_CODE_ALPHABET;
    $max = strlen($alphabet) - 1;
    $claim = $pdo->prepare('INSERT INTO honey_bottle_codes_issued (code) VALUES (?)');
    for ($attempt = 0; $attempt < 80; $attempt++) {
        $code = '';
        $hasLetter = false;
        $hasDigit = false;
        for ($i = 0; $i < HONEY_BOTTLE_CODE_LENGTH; $i++) {
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
            if ($sqlState === '42S02' || $driver === 1146) {
                throw new RuntimeException(
                    'Run sql/migration_phase55_honey_bottle_codes_issued.sql so bottle codes cannot be reused.'
                );
            }
            if ($sqlState === '23000' || $driver === 1062) {
                continue;
            }
            throw $e;
        }
        $used[$code] = true;

        return $code;
    }

    throw new RuntimeException('Could not allocate a unique bottle barcode.');
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_serialize_request(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'requested_by' => (int) $row['requested_by'],
        'requested_by_name' => (string) ($row['requested_by_name'] ?? ''),
        'packaging_row_id' => $row['packaging_row_id'] !== null ? (int) $row['packaging_row_id'] : null,
        'batch_no' => (string) ($row['batch_no'] ?? ''),
        'variant' => (string) $row['variant'],
        'size' => (string) $row['size'],
        'size_label' => honey_bottle_size_label((string) $row['size']),
        'quantity' => (int) $row['quantity'],
        'status' => (string) $row['status'],
        'notes' => $row['notes'] !== null ? (string) $row['notes'] : null,
        'created_at' => (string) $row['created_at'],
        'scanned_count' => isset($row['scanned_count']) ? (int) $row['scanned_count'] : 0,
        'generated_count' => isset($row['generated_count']) ? (int) $row['generated_count'] : 0,
        'sku_scanned_qty' => isset($row['sku_scanned_qty']) ? (int) $row['sku_scanned_qty'] : 0,
        'sku_print_qty' => isset($row['sku_print_qty']) ? (int) $row['sku_print_qty'] : 0,
        'label_kind' => ((int) ($row['sku_print_qty'] ?? 0) > 0 && (int) ($row['latest_batch_id'] ?? 0) > 0)
            ? 'mixed'
            : ((int) ($row['sku_print_qty'] ?? 0) > 0 ? 'sku' : ((int) ($row['latest_batch_id'] ?? 0) > 0 ? 'legacy' : 'sku')),
        'latest_batch_id' => isset($row['latest_batch_id']) && $row['latest_batch_id'] !== null
            ? (int) $row['latest_batch_id'] : null,
    ];
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_serialize_batch(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'request_id' => $row['request_id'] !== null ? (int) $row['request_id'] : null,
        'rts_label_job_id' => isset($row['rts_label_job_id']) && $row['rts_label_job_id'] !== null
            ? (int) $row['rts_label_job_id'] : null,
        'source' => (string) $row['source'],
        'generated_by' => (int) $row['generated_by'],
        'generated_by_name' => (string) ($row['generated_by_name'] ?? ''),
        'packaging_row_id' => $row['packaging_row_id'] !== null ? (int) $row['packaging_row_id'] : null,
        'supplier_code' => (string) $row['supplier_code'],
        'batch_no' => (string) $row['batch_no'],
        'batch_date' => (string) $row['batch_date'],
        'variant' => (string) $row['variant'],
        'size' => (string) $row['size'],
        'size_label' => honey_bottle_size_label((string) $row['size']),
        'quantity_generated' => (int) $row['quantity_generated'],
        'printed_at' => $row['printed_at'] !== null ? (string) $row['printed_at'] : null,
        'created_at' => (string) $row['created_at'],
        'scanned_count' => isset($row['scanned_count']) ? (int) $row['scanned_count'] : 0,
    ];
}

function honey_bottle_next_sequence(PDO $pdo, int $count): int
{
    if ($count < 1) {
        throw new RuntimeException('Quantity must be at least 1.');
    }
    $st = $pdo->query('SELECT next_seq FROM honey_bottle_barcode_seq WHERE id = 1 FOR UPDATE');
    $start = (int) $st->fetchColumn();
    if ($start < 1) {
        $start = 1;
    }
    $pdo->prepare('UPDATE honey_bottle_barcode_seq SET next_seq = ? WHERE id = 1')
        ->execute([$start + $count]);

    return $start;
}

function honey_bottle_adjust_aggregate(PDO $pdo, int $packagingRowId, string $size, float $delta): void
{
    if ($packagingRowId < 1 || abs($delta) < 1e-9) {
        return;
    }
    [$curCol, $iniCol] = honey_packaging_size_columns($size);
    if ($delta > 0) {
        $pdo->prepare(
            "UPDATE honey_packaging_rows SET {$curCol} = {$curCol} + ?, {$iniCol} = {$iniCol} + ? WHERE id = ?"
        )->execute([$delta, $delta, $packagingRowId]);
    } else {
        $take = abs($delta);
        $upd = $pdo->prepare(
            "UPDATE honey_packaging_rows SET {$curCol} = {$curCol} - ? WHERE id = ? AND {$curCol} >= ?"
        );
        $upd->execute([$take, $packagingRowId, $take]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Not enough packaging stock to deduct this bottle.');
        }
    }
}

/**
 * Deduct one finished bottle from packaging aggregates.
 * Prefer the unit's linked batch; if that row is empty (SKU FIFO already drained it),
 * take from any other packaging row of the same variant+size (FIFO by id).
 *
 * @return int packaging_row_id that was decremented
 */
function honey_bottle_deduct_packaging_one(
    PDO $pdo,
    string $variant,
    string $size,
    int $preferredPackagingRowId = 0
): int {
    $variant = honey_normalize_variant($variant);
    if (!in_array($size, HONEY_SIZES, true)) {
        throw new RuntimeException('Invalid size.');
    }
    [$curCol] = honey_packaging_size_columns($size);

    if ($preferredPackagingRowId > 0) {
        $upd = $pdo->prepare(
            "UPDATE honey_packaging_rows
             SET {$curCol} = {$curCol} - 1
             WHERE id = ? AND variant = ? AND {$curCol} >= 1"
        );
        $upd->execute([$preferredPackagingRowId, $variant]);
        if ($upd->rowCount() >= 1) {
            return $preferredPackagingRowId;
        }
    }

    $st = $pdo->prepare(
        "SELECT id FROM honey_packaging_rows
         WHERE variant = ? AND {$curCol} >= 1
         ORDER BY id ASC
         LIMIT 1
         FOR UPDATE"
    );
    $st->execute([$variant]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException(
            'Not enough packaging stock for '
            . ucfirst($variant) . ' ' . honey_bottle_size_label($size) . '.'
        );
    }
    $id = (int) $row['id'];
    honey_bottle_adjust_aggregate($pdo, $id, $size, -1.0);

    return $id;
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_request_create(
    PDO $pdo,
    int $userId,
    string $variant,
    string $size,
    int $qty,
    ?int $packagingRowId,
    ?string $notes
): array {
    $variant = honey_normalize_variant($variant);
    if (!in_array($size, HONEY_SIZES, true)) {
        throw new RuntimeException('Invalid size.');
    }
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
    if ($packagingRowId !== null && $packagingRowId > 0) {
        $pk = honey_fetch_packaging($pdo, $packagingRowId);
        if ($pk === null) {
            throw new RuntimeException('Packaging row not found.');
        }
        if (honey_normalize_variant((string) $pk['variant']) !== $variant) {
            throw new RuntimeException('Request variant does not match the packaging batch.');
        }
    } else {
        $packagingRowId = null;
    }

    $ins = $pdo->prepare(
        'INSERT INTO honey_bottle_requests (requested_by, packaging_row_id, variant, size, quantity, notes)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$userId, $packagingRowId, $variant, $size, $qty, $notes]);

    return honey_bottle_request_get($pdo, (int) $pdo->lastInsertId());
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_request_get(PDO $pdo, int $id): array
{
    $st = $pdo->prepare(
        'SELECT r.*, u.name AS requested_by_name, pk.batch_no,
                COALESCE(r.sku_scanned_qty, 0) AS sku_scanned_qty,
                (SELECT COALESCE(SUM(j.quantity), 0) FROM sku_barcode_print_jobs j WHERE j.honey_request_id = r.id)
                 + (SELECT COUNT(*) FROM honey_barcode_batches b
                    INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                    WHERE b.request_id = r.id) AS generated_count,
                COALESCE(r.sku_scanned_qty, 0)
                 + (SELECT COUNT(*) FROM honey_barcode_batches b
                    INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                    WHERE b.request_id = r.id AND c.status = "scanned") AS scanned_count,
                (SELECT MAX(b.id) FROM honey_barcode_batches b WHERE b.request_id = r.id) AS latest_batch_id,
                (SELECT COALESCE(SUM(j.quantity), 0) FROM sku_barcode_print_jobs j WHERE j.honey_request_id = r.id) AS sku_print_qty
         FROM honey_bottle_requests r
         LEFT JOIN users u ON u.id = r.requested_by
         LEFT JOIN honey_packaging_rows pk ON pk.id = r.packaging_row_id
         WHERE r.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Request not found.');
    }

    return honey_bottle_serialize_request($row);
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int}
 */
function honey_bottle_request_list(PDO $pdo, string $status, int $userIdFilter, int $page, int $perPage): array
{
    $page = max(1, $page);
    $perPage = min(100, max(10, $perPage));
    $where = ['1=1'];
    $params = [];
    if ($status !== '' && $status !== 'all') {
        $where[] = 'r.status = ?';
        $params[] = $status;
    }
    if ($userIdFilter > 0) {
        $where[] = 'r.requested_by = ?';
        $params[] = $userIdFilter;
    }
    $whereSql = implode(' AND ', $where);
    $cst = $pdo->prepare("SELECT COUNT(*) FROM honey_bottle_requests r WHERE {$whereSql}");
    $cst->execute($params);
    $total = (int) $cst->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $st = $pdo->prepare(
        "SELECT r.*, u.name AS requested_by_name, pk.batch_no,
                COALESCE(r.sku_scanned_qty, 0) AS sku_scanned_qty,
                (SELECT COALESCE(SUM(j.quantity), 0) FROM sku_barcode_print_jobs j WHERE j.honey_request_id = r.id)
                 + (SELECT COUNT(*) FROM honey_barcode_batches b
                    INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                    WHERE b.request_id = r.id) AS generated_count,
                COALESCE(r.sku_scanned_qty, 0)
                 + (SELECT COUNT(*) FROM honey_barcode_batches b
                    INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                    WHERE b.request_id = r.id AND c.status = \"scanned\") AS scanned_count,
                (SELECT MAX(b.id) FROM honey_barcode_batches b WHERE b.request_id = r.id) AS latest_batch_id,
                (SELECT COALESCE(SUM(j.quantity), 0) FROM sku_barcode_print_jobs j WHERE j.honey_request_id = r.id) AS sku_print_qty
         FROM honey_bottle_requests r
         LEFT JOIN users u ON u.id = r.requested_by
         LEFT JOIN honey_packaging_rows pk ON pk.id = r.packaging_row_id
         WHERE {$whereSql}
         ORDER BY r.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = honey_bottle_serialize_request($row);
    }

    return ['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
}

function honey_bottle_request_cancel(PDO $pdo, int $id, int $userId, string $role, ?string $deptKey = null): void
{
    $req = honey_bottle_request_get($pdo, $id);
    if ($req['status'] === 'cancelled' || $req['status'] === 'completed') {
        throw new RuntimeException('This request cannot be cancelled.');
    }
    if ($req['generated_count'] > 0 && !honey_bottle_can_generate($role, $deptKey)) {
        throw new RuntimeException('Barcodes already generated — ask an executive or CSR to cancel.');
    }
    if ((int) $req['requested_by'] !== $userId && !honey_bottle_can_generate($role, $deptKey)) {
        throw new RuntimeException('You can only cancel your own requests.');
    }
    $pdo->prepare('UPDATE honey_bottle_requests SET status = "cancelled" WHERE id = ?')->execute([$id]);
}

function honey_bottle_pending_request_count(PDO $pdo): int
{
    return (int) $pdo->query(
        'SELECT COUNT(*) FROM honey_bottle_requests WHERE status = "pending"'
    )->fetchColumn();
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_generate_batch(
    PDO $pdo,
    int $userId,
    int $qty,
    int $packagingRowId,
    string $size,
    string $source,
    ?int $requestId,
    ?string $batchDateOverride = null,
    ?int $rtsLabelJobId = null
): array {
    if (!in_array($source, ['request', 'backfill', 'rts_label'], true)) {
        throw new RuntimeException('Invalid batch source.');
    }
    if (!in_array($size, HONEY_SIZES, true)) {
        throw new RuntimeException('Invalid size.');
    }
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    $pk = honey_fetch_packaging($pdo, $packagingRowId);
    if ($pk === null) {
        throw new RuntimeException('Packaging row not found.');
    }
    $variant = honey_normalize_variant((string) $pk['variant']);
    $batchNo = (string) $pk['batch_no'];
    $supplier = (string) $pk['supplier_code'];
    // Stickers are made on the day the bottles are filled, so each generation run
    // stamps its own date. The packaging row keeps the lot's original bottling_date —
    // one lot bottled across several days yields several sticker batches with real dates.
    // RTS label jobs pass the user-entered bottling date instead.
    if ($batchDateOverride !== null && trim($batchDateOverride) !== '') {
        $ts = strtotime(trim($batchDateOverride));
        if ($ts === false) {
            throw new RuntimeException('Invalid batch date.');
        }
        $batchDate = date('Y-m-d', $ts);
    } else {
        $batchDate = date('Y-m-d');
    }
    $rtsJobId = ($rtsLabelJobId !== null && $rtsLabelJobId > 0) ? $rtsLabelJobId : null;

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $start = honey_bottle_next_sequence($pdo, $qty);
        $insB = $pdo->prepare(
            'INSERT INTO honey_barcode_batches (
                request_id, rts_label_job_id, source, generated_by, packaging_row_id, supplier_code, batch_no,
                batch_date, variant, size, quantity_generated
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insB->execute([
            $requestId,
            $rtsJobId,
            $source,
            $userId,
            $packagingRowId,
            $supplier,
            $batchNo,
            $batchDate,
            $variant,
            $size,
            $qty,
        ]);
        $batchId = (int) $pdo->lastInsertId();
        $insC = $pdo->prepare(
            'INSERT INTO honey_bottle_barcodes (batch_id, code, sequence_no, size, status)
             VALUES (?, ?, ?, ?, "generated")'
        );
        $used = [];
        for ($i = 0; $i < $qty; $i++) {
            $seq = $start + $i;
            $insC->execute([$batchId, honey_bottle_random_code($pdo, $used), $seq, $size]);
        }
        if ($requestId !== null && $requestId > 0) {
            $pdo->prepare(
                'UPDATE honey_bottle_requests SET status = "barcodes_generated", packaging_row_id = ?
                 WHERE id = ? AND status IN ("pending","barcodes_generated","in_progress")'
            )->execute([$packagingRowId, $requestId]);
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

    return honey_bottle_batch_get($pdo, $batchId);
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_generate_for_request(
    PDO $pdo,
    int $userId,
    int $requestId,
    ?int $packagingRowId,
    ?int $productionRowId = null
): array {
    $req = honey_bottle_request_get($pdo, $requestId);
    if ($req['status'] === 'cancelled') {
        throw new RuntimeException('Cannot generate barcodes for a cancelled request.');
    }
    $packId = $packagingRowId ?? $req['packaging_row_id'];
    if (($packId === null || $packId < 1) && $productionRowId !== null && $productionRowId > 0) {
        $shell = honey_bottle_ensure_packaging_shell($pdo, $userId, $productionRowId);
        $packId = (int) $shell['id'];
        $shellVariant = honey_normalize_variant((string) $shell['variant']);
        if ($shellVariant !== (string) $req['variant']) {
            throw new RuntimeException('Production lot variant does not match this request.');
        }
    }
    if ($packId === null || $packId < 1) {
        throw new RuntimeException('Pick a packaging batch (or production lot) before generating barcodes.');
    }

    return honey_bottle_generate_batch(
        $pdo,
        $userId,
        (int) $req['quantity'],
        (int) $packId,
        (string) $req['size'],
        'request',
        $requestId
    );
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_batch_get(PDO $pdo, int $id): array
{
    $st = $pdo->prepare(
        'SELECT b.*, u.name AS generated_by_name,
                (SELECT COUNT(*) FROM honey_bottle_barcodes c WHERE c.batch_id = b.id AND c.status = "scanned") AS scanned_count
         FROM honey_barcode_batches b
         LEFT JOIN users u ON u.id = b.generated_by
         WHERE b.id = ? LIMIT 1'
    );
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Batch not found.');
    }

    return honey_bottle_serialize_batch($row);
}

/**
 * @return list<array<string, mixed>>
 */
function honey_bottle_batch_list(PDO $pdo, string $source = '', int $limit = 50): array
{
    $limit = min(200, max(10, $limit));
    $sql = 'SELECT b.*, u.name AS generated_by_name,
                   (SELECT COUNT(*) FROM honey_bottle_barcodes c WHERE c.batch_id = b.id AND c.status = "scanned") AS scanned_count
            FROM honey_barcode_batches b
            LEFT JOIN users u ON u.id = b.generated_by';
    $params = [];
    if ($source === 'request' || $source === 'backfill' || $source === 'rts_label') {
        $sql .= ' WHERE b.source = ?';
        $params[] = $source;
    }
    $sql .= " ORDER BY b.id DESC LIMIT {$limit}";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = honey_bottle_serialize_batch($row);
    }

    return $rows;
}

function honey_bottle_mark_printed(PDO $pdo, int $batchId): array
{
    $pdo->prepare(
        'UPDATE honey_barcode_batches SET printed_at = COALESCE(printed_at, NOW()) WHERE id = ?'
    )->execute([$batchId]);
    $pdo->prepare(
        'UPDATE honey_bottle_barcodes SET status = "printed"
         WHERE batch_id = ? AND status = "generated"'
    )->execute([$batchId]);

    return honey_bottle_batch_get($pdo, $batchId);
}

function honey_bottle_void_code(PDO $pdo, string $code, int $userId, string $reason): void
{
    unset($userId);
    $code = honey_bottle_normalize_code($code);
    $st = $pdo->prepare(
        'SELECT c.*, u.id AS unit_id, u.status AS unit_status
         FROM honey_bottle_barcodes c
         LEFT JOIN honey_bottle_units u ON u.barcode_id = c.id
         WHERE c.code = ? LIMIT 1 FOR UPDATE'
    );
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st->execute([$code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('Unknown barcode.');
        }
        if ((string) $row['status'] === 'void') {
            throw new RuntimeException('Barcode is already void.');
        }
        if ($row['unit_id'] !== null && (string) $row['unit_status'] !== 'in_stock') {
            throw new RuntimeException('Cannot void a bottle that has already left stock.');
        }
        $pdo->prepare(
            'UPDATE honey_bottle_barcodes SET status = "void", void_reason = ? WHERE id = ?'
        )->execute([substr(trim($reason) !== '' ? trim($reason) : 'voided', 0, 255), (int) $row['id']]);
        if ($row['unit_id'] !== null) {
            $pdo->prepare('UPDATE honey_bottle_units SET status = "void" WHERE id = ?')
                ->execute([(int) $row['unit_id']]);
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
}

/**
 * @return array<string, mixed>|null
 */
function honey_bottle_fetch_code_row(PDO $pdo, string $code): ?array
{
    $code = honey_bottle_normalize_code($code);
    if ($code === '') {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT c.*, b.variant, b.supplier_code, b.batch_no, b.packaging_row_id, b.request_id, b.source AS batch_source,
                u.id AS unit_id, u.status AS unit_status, u.pool AS unit_pool, u.packaging_row_id AS unit_packaging_row_id
         FROM honey_bottle_barcodes c
         INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN honey_bottle_units u ON u.barcode_id = c.id
         WHERE c.code = ? LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_lookup(PDO $pdo, string $code): array
{
    $row = honey_bottle_fetch_code_row($pdo, $code);
    if ($row === null) {
        throw new RuntimeException('Unknown barcode.');
    }

    return honey_bottle_serialize_lookup($row);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function honey_bottle_serialize_lookup(array $row): array
{
    return [
        'barcode_id' => (int) $row['id'],
        'code' => (string) $row['code'],
        'barcode_status' => (string) $row['status'],
        'size' => (string) $row['size'],
        'size_label' => honey_bottle_size_label((string) $row['size']),
        'variant' => (string) $row['variant'],
        'supplier_code' => (string) $row['supplier_code'],
        'batch_no' => (string) $row['batch_no'],
        'packaging_row_id' => $row['packaging_row_id'] !== null ? (int) $row['packaging_row_id'] : null,
        'request_id' => $row['request_id'] !== null ? (int) $row['request_id'] : null,
        'batch_source' => (string) $row['batch_source'],
        'unit_id' => $row['unit_id'] !== null ? (int) $row['unit_id'] : null,
        'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
        'unit_pool' => $row['unit_pool'] !== null ? (string) $row['unit_pool'] : null,
        'scanned_at' => $row['scanned_at'] !== null ? (string) $row['scanned_at'] : null,
    ];
}

function honey_bottle_scan_report_date(?string $scanDate): ?string
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
 * Gross bottle stock-in events grouped by packaging row and size.
 * Includes both individual sticker scans and aggregated SKU stock-ins.
 * Later unit deductions never alter these counts.
 *
 * @return array{dates: list<string>, rows: list<array<string,mixed>>, scan_date: ?string}
 */
function honey_bottle_packaging_scan_report(PDO $pdo, ?string $scanDate = null): array
{
    $scanDate = honey_bottle_scan_report_date($scanDate);
    $scanSourceSql = '(
        SELECT b.packaging_row_id, c.size, c.scanned_at, 1 AS qty
        FROM honey_bottle_barcodes c
        INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
        WHERE c.scanned_at IS NOT NULL AND b.packaging_row_id IS NOT NULL
        UNION ALL
        SELECT e.packaging_row_id, s.size, e.scanned_at, e.qty
        FROM sku_barcode_stock_events e
        INNER JOIN product_sku_barcodes s ON s.id = e.sku_id
        WHERE e.kind = "honey" AND e.packaging_row_id IS NOT NULL
    )';
    $datesSt = $pdo->query(
        "SELECT DISTINCT DATE(scan_events.scanned_at) AS scan_date
         FROM {$scanSourceSql} scan_events
         ORDER BY scan_date DESC"
    );
    $dates = [];
    foreach (($datesSt ? $datesSt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
        $dates[] = (string) $row['scan_date'];
    }

    $where = 'scan_events.scanned_at IS NOT NULL';
    $params = [];
    if ($scanDate !== null) {
        $from = $scanDate . ' 00:00:00';
        $to = (new DateTimeImmutable($scanDate))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        $where .= ' AND scan_events.scanned_at >= ? AND scan_events.scanned_at < ?';
        $params = [$from, $to];
    }

    $st = $pdo->prepare(
        "SELECT pk.id, pk.production_row_id, pk.supplier_code, pk.batch_no,
                pk.bottling_date, pk.variant, pk.created_at,
                pk.current_liter, pk.initial_liter,
                pk.current_250ml, pk.initial_250ml,
                pk.current_350ml, pk.initial_350ml,
                pk.current_gallon, pk.initial_gallon,
                SUM(CASE WHEN scan_events.size = 'liter' THEN scan_events.qty ELSE 0 END) AS scanned_liter,
                SUM(CASE WHEN scan_events.size = '250ml' THEN scan_events.qty ELSE 0 END) AS scanned_250ml,
                SUM(CASE WHEN scan_events.size = '350ml' THEN scan_events.qty ELSE 0 END) AS scanned_350ml,
                SUM(CASE WHEN scan_events.size = 'gallon' THEN scan_events.qty ELSE 0 END) AS scanned_gallon
         FROM {$scanSourceSql} scan_events
         INNER JOIN honey_packaging_rows pk ON pk.id = scan_events.packaging_row_id
         WHERE {$where}
         GROUP BY pk.id, pk.production_row_id, pk.supplier_code, pk.batch_no,
                  pk.bottling_date, pk.variant, pk.created_at,
                  pk.current_liter, pk.initial_liter,
                  pk.current_250ml, pk.initial_250ml,
                  pk.current_350ml, pk.initial_350ml,
                  pk.current_gallon, pk.initial_gallon
         ORDER BY pk.bottling_date DESC, pk.id DESC"
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $variantSt = $pdo->prepare(
        "SELECT LOWER(pk.variant) AS variant,
                SUM(scan_events.qty) AS total,
                SUM(CASE WHEN scan_events.size = 'liter' THEN scan_events.qty ELSE 0 END) AS liter,
                SUM(CASE WHEN scan_events.size = '250ml' THEN scan_events.qty ELSE 0 END) AS ml250,
                SUM(CASE WHEN scan_events.size = '350ml' THEN scan_events.qty ELSE 0 END) AS ml350,
                SUM(CASE WHEN scan_events.size = 'gallon' THEN scan_events.qty ELSE 0 END) AS gallon
         FROM {$scanSourceSql} scan_events
         INNER JOIN honey_packaging_rows pk ON pk.id = scan_events.packaging_row_id
         WHERE {$where}
         GROUP BY LOWER(pk.variant)
         ORDER BY LOWER(pk.variant)"
    );
    $variantSt->execute($params);
    $byVariant = [];
    $grandTotal = 0;
    foreach ($variantSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $total = (int) $row['total'];
        $grandTotal += $total;
        $byVariant[] = [
            'variant' => (string) $row['variant'],
            'total' => $total,
            'liter' => (int) $row['liter'],
            '250ml' => (int) $row['ml250'],
            '350ml' => (int) $row['ml350'],
            'gallon' => (int) $row['gallon'],
        ];
    }

    return [
        'dates' => $dates,
        'rows' => $rows,
        'scan_date' => $scanDate,
        'totals' => [
            'total' => $grandTotal,
            'by_variant' => $byVariant,
        ],
    ];
}

/**
 * Packaging stock-in entries for a row — who stocked them in and how many.
 *
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int, packaging: array<string, mixed>|null}
 */
function honey_bottle_packaging_scan_log(
    PDO $pdo,
    int $packagingRowId,
    int $page = 1,
    int $perPage = 50,
    ?string $scanDate = null
): array
{
    if ($packagingRowId < 1) {
        throw new RuntimeException('packaging_row_id is required.');
    }
    $pk = honey_fetch_packaging($pdo, $packagingRowId);
    if ($pk === null) {
        throw new RuntimeException('Packaging row not found.');
    }
    $page = max(1, $page);
    $perPage = max(1, min(200, $perPage));
    $scanDate = honey_bottle_scan_report_date($scanDate);
    $scanSourceSql = '(
        SELECT b.packaging_row_id, c.id AS event_id, c.code, c.size, c.scanned_at, c.scanned_by,
               un.status AS unit_status, un.pool AS unit_pool, 1 AS qty, "sticker" AS scan_kind
        FROM honey_bottle_barcodes c
        INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
        LEFT JOIN honey_bottle_units un ON un.barcode_id = c.id
        WHERE c.scanned_at IS NOT NULL AND b.packaging_row_id IS NOT NULL
        UNION ALL
        SELECT e.packaging_row_id, e.id AS event_id, s.code, s.size, e.scanned_at, e.scanned_by,
               NULL AS unit_status, NULL AS unit_pool, e.qty, "sku" AS scan_kind
        FROM sku_barcode_stock_events e
        INNER JOIN product_sku_barcodes s ON s.id = e.sku_id
        WHERE e.kind = "honey" AND e.packaging_row_id IS NOT NULL
    )';
    $dateSql = '';
    $params = [$packagingRowId];
    if ($scanDate !== null) {
        $from = $scanDate . ' 00:00:00';
        $to = (new DateTimeImmutable($scanDate))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        $dateSql = ' AND scan_events.scanned_at >= ? AND scan_events.scanned_at < ?';
        $params[] = $from;
        $params[] = $to;
    }
    $cst = $pdo->prepare(
        "SELECT COUNT(*) AS entry_count, COALESCE(SUM(scan_events.qty), 0) AS total_qty
         FROM {$scanSourceSql} scan_events
         WHERE scan_events.packaging_row_id = ?" . $dateSql
    );
    $cst->execute($params);
    $countRow = $cst->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int) ($countRow['entry_count'] ?? 0);
    $totalQty = (int) ($countRow['total_qty'] ?? 0);
    $offset = ($page - 1) * $perPage;
    $st = $pdo->prepare(
        "SELECT scan_events.code, scan_events.size, scan_events.scanned_at, scan_events.scanned_by,
                scan_events.qty, scan_events.scan_kind,
                u.name AS scanned_by_name,
                scan_events.unit_status, scan_events.unit_pool
         FROM {$scanSourceSql} scan_events
         LEFT JOIN users u ON u.id = scan_events.scanned_by
         WHERE scan_events.packaging_row_id = ?" . $dateSql . "
         ORDER BY scan_events.scanned_at DESC, scan_events.event_id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = [
            'code' => (string) $row['code'],
            'size' => (string) $row['size'],
            'size_label' => honey_bottle_size_label((string) $row['size']),
            'scanned_at' => (string) $row['scanned_at'],
            'scanned_by' => $row['scanned_by'] !== null ? (int) $row['scanned_by'] : null,
            'scanned_by_name' => (string) ($row['scanned_by_name'] ?? ''),
            'qty' => (int) $row['qty'],
            'scan_kind' => (string) $row['scan_kind'],
            'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
            'unit_pool' => $row['unit_pool'] !== null ? (string) $row['unit_pool'] : null,
        ];
    }

    return [
        'packaging' => [
            'id' => (int) $pk['id'],
            'batch_no' => (string) $pk['batch_no'],
            'supplier_code' => (string) $pk['supplier_code'],
            'variant' => (string) $pk['variant'],
            'bottling_date' => (string) $pk['bottling_date'],
        ],
        'rows' => $rows,
        'total' => $total,
        'total_qty' => $totalQty,
        'page' => $page,
        'per_page' => $perPage,
        'scan_date' => $scanDate,
    ];
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_scan_stock_in(PDO $pdo, int $userId, string $code, bool $adoptOnly = false): array
{
    $code = honey_bottle_normalize_code($code);
    $unitId = 0;
    $shouldIncrement = false;
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT c.*, b.variant, b.supplier_code, b.batch_no, b.packaging_row_id, b.request_id, b.source AS batch_source
             FROM honey_bottle_barcodes c
             INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
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
        $exist = $pdo->prepare('SELECT id FROM honey_bottle_units WHERE barcode_id = ? LIMIT 1');
        $exist->execute([(int) $row['id']]);
        if ($exist->fetchColumn()) {
            throw new RuntimeException('Already scanned into inventory.');
        }
        if ((string) $row['status'] === 'scanned') {
            throw new RuntimeException('Already scanned into inventory.');
        }
        $packId = (int) ($row['packaging_row_id'] ?? 0);
        if ($packId < 1) {
            throw new RuntimeException('Barcode is not tied to a packaging batch.');
        }
        $shouldIncrement = !$adoptOnly && (string) $row['batch_source'] !== 'backfill';
        $pdo->prepare(
            'UPDATE honey_bottle_barcodes SET status = "scanned", scanned_at = NOW(), scanned_by = ? WHERE id = ?'
        )->execute([$userId, (int) $row['id']]);
        $ins = $pdo->prepare(
            'INSERT INTO honey_bottle_units (
                barcode_id, variant, size, supplier_code, packaging_row_id, pool, status, created_date
             ) VALUES (?, ?, ?, ?, ?, "packaging", "in_stock", CURDATE())'
        );
        $ins->execute([
            (int) $row['id'],
            honey_normalize_variant((string) $row['variant']),
            (string) $row['size'],
            (string) $row['supplier_code'],
            $packId,
        ]);
        $unitId = (int) $pdo->lastInsertId();
        if ($shouldIncrement) {
            honey_bottle_adjust_aggregate($pdo, $packId, (string) $row['size'], 1);
        }
        if (!empty($row['request_id'])) {
            $reqId = (int) $row['request_id'];
            $counts = $pdo->prepare(
                'SELECT
                    SUM(c.status = "scanned") AS scanned,
                    COUNT(*) AS total
                 FROM honey_barcode_batches b
                 INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                 WHERE b.request_id = ?'
            );
            $counts->execute([$reqId]);
            $c = $counts->fetch(PDO::FETCH_ASSOC) ?: [];
            $scanned = (int) ($c['scanned'] ?? 0);
            $total = (int) ($c['total'] ?? 0);
            $status = $scanned >= $total && $total > 0 ? 'completed' : 'in_progress';
            $pdo->prepare('UPDATE honey_bottle_requests SET status = ? WHERE id = ? AND status <> "cancelled"')
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

    $fresh = honey_bottle_fetch_code_row($pdo, $code);
    $out = honey_bottle_serialize_lookup($fresh ?? $row);
    $out['unit_id'] = $unitId;
    $out['adopted'] = !$shouldIncrement;
    if (!empty($row['request_id'])) {
        $out['request'] = honey_bottle_request_get($pdo, (int) $row['request_id']);
    }

    return $out;
}

/**
 * Scan a honey barcode into RTS sellable inventory (not packaging).
 *
 * @return array<string, mixed>
 */
function honey_bottle_rts_stock_in_scan(PDO $pdo, int $userId, string $code): array
{
    require_once __DIR__ . '/rts_inventory_sync.php';
    $code = honey_bottle_normalize_code($code);
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT c.*, b.variant, b.supplier_code, b.batch_no, b.packaging_row_id, b.request_id, b.source AS batch_source,
                    u.id AS unit_id, u.status AS unit_status, u.pool AS unit_pool
             FROM honey_bottle_barcodes c
             INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
             LEFT JOIN honey_bottle_units u ON u.barcode_id = c.id
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
        $packId = (int) ($row['packaging_row_id'] ?? 0);
        $variant = honey_normalize_variant((string) $row['variant']);
        $size = (string) $row['size'];
        $unitId = $row['unit_id'] !== null ? (int) $row['unit_id'] : 0;

        if ($unitId > 0) {
            $unitStatus = (string) ($row['unit_status'] ?? '');
            $unitPool = (string) ($row['unit_pool'] ?? '');
            if ($unitPool === 'rts_sellable' && $unitStatus === 'in_stock') {
                throw new RuntimeException('Already in RTS inventory.');
            }
            if ($unitPool === 'packaging' && $unitStatus === 'in_stock') {
                throw new RuntimeException(
                    'This bottle is still in packaging stock. Use packaging inventory — do not move with RTS stock-in.'
                );
            }
            if (!in_array($unitStatus, ['packed', 'sold', 'shipped', 'rts'], true)) {
                throw new RuntimeException('This bottle cannot enter RTS inventory (' . $unitStatus . ').');
            }
            // Returned unit that already had a barcode: move into RTS sellable on-hand.
            $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET status = "in_stock", pool = "rts_sellable",
                     consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ?'
            )->execute([$unitId]);
            if ((string) $row['status'] !== 'scanned') {
                $pdo->prepare(
                    'UPDATE honey_bottle_barcodes SET status = "scanned", scanned_at = COALESCE(scanned_at, NOW()), scanned_by = ?
                     WHERE id = ?'
                )->execute([$userId, (int) $row['id']]);
            }
            rts_inventory_credit_sellable_honey($pdo, $variant, $size, 1.0);
        } else {
            if ($packId < 1) {
                throw new RuntimeException('Barcode is not tied to a packaging batch.');
            }
            $pdo->prepare(
                'UPDATE honey_bottle_barcodes SET status = "scanned", scanned_at = NOW(), scanned_by = ? WHERE id = ?'
            )->execute([$userId, (int) $row['id']]);
            $pdo->prepare(
                'INSERT INTO honey_bottle_units (
                    barcode_id, variant, size, supplier_code, packaging_row_id, pool, status, created_date
                 ) VALUES (?, ?, ?, ?, ?, "rts_sellable", "in_stock", CURDATE())'
            )->execute([
                (int) $row['id'],
                $variant,
                $size,
                (string) $row['supplier_code'],
                $packId,
            ]);
            $unitId = (int) $pdo->lastInsertId();
            rts_inventory_credit_sellable_honey($pdo, $variant, $size, 1.0);
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

    $fresh = honey_bottle_fetch_code_row($pdo, $code);
    $out = honey_bottle_serialize_lookup($fresh ?? []);
    $out['unit_id'] = $unitId;
    $out['rts_stock_in'] = true;

    return $out;
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_pack_session_start(PDO $pdo, int $userId): array
{
    $open = $pdo->prepare(
        'SELECT id FROM honey_bottle_pack_sessions WHERE started_by = ? AND status = "draft" ORDER BY id DESC LIMIT 1'
    );
    $open->execute([$userId]);
    $existing = (int) $open->fetchColumn();
    if ($existing > 0) {
        return honey_bottle_pack_session_get($pdo, $existing);
    }
    $pdo->prepare('INSERT INTO honey_bottle_pack_sessions (started_by, status) VALUES (?, "draft")')
        ->execute([$userId]);

    return honey_bottle_pack_session_get($pdo, (int) $pdo->lastInsertId());
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_pack_session_get(PDO $pdo, int $sessionId): array
{
    $st = $pdo->prepare(
        'SELECT s.*,
                u_start.name AS started_by_name,
                u_conf.name AS confirmed_by_name
         FROM honey_bottle_pack_sessions s
         LEFT JOIN users u_start ON u_start.id = s.started_by
         LEFT JOIN users u_conf ON u_conf.id = s.confirmed_by
         WHERE s.id = ? LIMIT 1'
    );
    $st->execute([$sessionId]);
    $sess = $st->fetch(PDO::FETCH_ASSOC);
    if ($sess === false) {
        throw new RuntimeException('Pack session not found.');
    }

    $itemsSt = $pdo->prepare(
        'SELECT i.id, i.barcode_id, i.unit_id, i.sku_id, i.sku_code, i.qty, i.pool AS item_pool,
                i.packaging_row_id AS item_packaging_row_id, i.variant AS item_variant, i.size AS item_size,
                i.scanned_at, c.code AS unique_code, u.variant AS unit_variant, u.size AS unit_size,
                COALESCE(pk.batch_no, pk2.batch_no) AS batch_no
         FROM honey_bottle_pack_session_items i
         LEFT JOIN honey_bottle_barcodes c ON c.id = i.barcode_id
         LEFT JOIN honey_bottle_units u ON u.id = i.unit_id
         LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
         LEFT JOIN honey_packaging_rows pk2 ON pk2.id = i.packaging_row_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $itemsSt->execute([$sessionId]);

    $deptSt = $pdo->prepare(
        'SELECT i.id, i.product_key, i.barcode_id, i.unit_id, i.sku_id, i.sku_code, i.qty, i.pool AS item_pool,
                i.scanned_at, c.code AS unique_code, un.batch_no, un.status AS unit_status
         FROM honey_bottle_pack_session_dept_items i
         LEFT JOIN dept_pouch_barcodes c ON c.id = i.barcode_id
         LEFT JOIN dept_pouch_units un ON un.id = i.unit_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $deptSt->execute([$sessionId]);

    $items = [];
    $grouped = [];
    $byBatch = [];

    foreach ($itemsSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $isSku = $row['sku_id'] !== null && (int) $row['sku_id'] > 0 && ($row['unit_id'] === null || (int) $row['unit_id'] < 1);
        $variant = $isSku ? (string) ($row['item_variant'] ?? '') : (string) ($row['unit_variant'] ?? '');
        $size = $isSku ? (string) ($row['item_size'] ?? '') : (string) ($row['unit_size'] ?? '');
        $qty = max(1, (int) ($row['qty'] ?? 1));
        $batchNo = $row['batch_no'] !== null ? (string) $row['batch_no'] : '';
        $sizeLabel = honey_bottle_size_label($size);
        $code = $isSku ? (string) ($row['sku_code'] ?? '') : (string) ($row['unique_code'] ?? '');
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => 'honey',
            'lookup_kind' => $isSku ? 'sku' : 'unique_honey',
            'product_key' => 'honey',
            'product_label' => 'Honey',
            'code' => $code,
            'variant' => $variant,
            'size' => $size,
            'size_label' => $sizeLabel,
            'batch_no' => $batchNo !== '' ? $batchNo : null,
            'qty' => $qty,
            'pool' => $row['item_pool'] !== null ? (string) $row['item_pool'] : null,
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $key = 'honey|' . $variant . '|' . $size;
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'kind' => 'honey',
                'product_key' => 'honey',
                'product_label' => 'Honey',
                'variant' => $variant,
                'size' => $size,
                'size_label' => $sizeLabel,
                'qty' => 0,
            ];
        }
        $grouped[$key]['qty'] += $qty;
        $bKey = 'honey|' . $batchNo . '|' . $variant . '|' . $size;
        if (!isset($byBatch[$bKey])) {
            $byBatch[$bKey] = [
                'kind' => 'honey',
                'product_key' => 'honey',
                'product_label' => 'Honey',
                'batch_no' => $batchNo !== '' ? $batchNo : null,
                'variant' => $variant,
                'size' => $size,
                'size_label' => $sizeLabel,
                'qty' => 0,
            ];
        }
        $byBatch[$bKey]['qty'] += $qty;
    }

    foreach ($deptSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productKey = (string) $row['product_key'];
        $productLabel = dept_pouch_product_label($productKey);
        $unitNoun = dept_pouch_unit_singular($productKey);
        $isSku = $row['sku_id'] !== null && (int) $row['sku_id'] > 0 && ($row['unit_id'] === null || (int) $row['unit_id'] < 1);
        $qty = max(1, (int) ($row['qty'] ?? 1));
        $batchNo = $row['batch_no'] !== null ? (string) $row['batch_no'] : '';
        $code = $isSku ? (string) ($row['sku_code'] ?? '') : (string) ($row['unique_code'] ?? '');
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => 'dept',
            'lookup_kind' => $isSku ? 'sku' : 'unique_dept',
            'product_key' => $productKey,
            'product_label' => $productLabel,
            'code' => $code,
            'variant' => $productLabel,
            'size' => $unitNoun,
            'size_label' => $unitNoun,
            'batch_no' => $batchNo !== '' ? $batchNo : null,
            'qty' => $qty,
            'pool' => $row['item_pool'] !== null ? (string) $row['item_pool'] : null,
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $key = 'dept|' . $productKey;
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'kind' => 'dept',
                'product_key' => $productKey,
                'product_label' => $productLabel,
                'variant' => $productLabel,
                'size' => $unitNoun,
                'size_label' => $unitNoun,
                'qty' => 0,
            ];
        }
        $grouped[$key]['qty'] += $qty;
        $bKey = 'dept|' . $batchNo . '|' . $productKey;
        if (!isset($byBatch[$bKey])) {
            $byBatch[$bKey] = [
                'kind' => 'dept',
                'product_key' => $productKey,
                'product_label' => $productLabel,
                'batch_no' => $batchNo !== '' ? $batchNo : null,
                'variant' => $productLabel,
                'size' => $unitNoun,
                'size_label' => $unitNoun,
                'qty' => 0,
            ];
        }
        $byBatch[$bKey]['qty'] += $qty;
    }

    usort($items, static function (array $a, array $b): int {
        $cmp = strcmp((string) $a['scanned_at'], (string) $b['scanned_at']);
        if ($cmp !== 0) {
            return $cmp;
        }
        if ($a['kind'] !== $b['kind']) {
            return $a['kind'] === 'honey' ? -1 : 1;
        }

        return ((int) $a['id']) <=> ((int) $b['id']);
    });

    return [
        'id' => (int) $sess['id'],
        'status' => (string) $sess['status'],
        'started_by' => (int) $sess['started_by'],
        'started_by_name' => (string) ($sess['started_by_name'] ?? ''),
        'confirmed_by' => $sess['confirmed_by'] !== null ? (int) $sess['confirmed_by'] : null,
        'confirmed_by_name' => (string) ($sess['confirmed_by_name'] ?? ''),
        'confirmed_at' => $sess['confirmed_at'] !== null ? (string) $sess['confirmed_at'] : null,
        'created_at' => (string) $sess['created_at'],
        'item_count' => count($items),
        'items' => $items,
        'grouped' => array_values($grouped),
        'by_batch' => array_values($byBatch),
    ];
}

/**
 * Read-only list of confirmed pack sessions for History.
 *
 * @return array{rows: list<array<string,mixed>>, total: int, page: int, per_page: int, from: string, to: string}
 */
function honey_bottle_pack_session_list(
    PDO $pdo,
    ?string $from,
    ?string $to,
    ?string $batchNo,
    int $page = 1,
    ?string $productKey = null
): array {
    $page = max(1, $page);
    $perPage = 30;
    $offset = ($page - 1) * $perPage;

    if ($from === null || $from === '') {
        $from = date('Y-m-d', strtotime('-13 days'));
    }
    if ($to === null || $to === '') {
        $to = date('Y-m-d');
    }
    $fromTs = strtotime($from);
    $toTs = strtotime($to);
    if ($fromTs === false || $toTs === false) {
        throw new RuntimeException('Invalid date range.');
    }
    if ($fromTs > $toTs) {
        throw new RuntimeException('From date must be on or before To date.');
    }
    $from = date('Y-m-d', $fromTs);
    $to = date('Y-m-d', $toTs);
    $fromDt = $from . ' 00:00:00';
    $toDt = $to . ' 23:59:59';

    $batchNo = $batchNo !== null ? strtoupper(trim($batchNo)) : '';
    $productKey = $productKey !== null ? strtolower(trim($productKey)) : '';
    if ($productKey === 'all') {
        $productKey = '';
    }
    if ($productKey !== '' && $productKey !== 'honey' && !dept_pouch_supports($productKey)) {
        throw new RuntimeException('Unknown product filter.');
    }

    $joins = [];
    $params = [];

    if ($batchNo !== '') {
        if (!preg_match('/^[A-Z0-9][A-Z0-9-]{0,31}$/', $batchNo)) {
            throw new RuntimeException('Batch code search must be letters, digits or dashes.');
        }
        $joins[] = ' INNER JOIN (
            SELECT session_id FROM (
                SELECT DISTINCT i.session_id
                FROM honey_bottle_pack_session_items i
                INNER JOIN honey_bottle_units u ON u.id = i.unit_id
                INNER JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
                WHERE UPPER(pk.batch_no) LIKE ?
                UNION
                SELECT DISTINCT di.session_id
                FROM honey_bottle_pack_session_dept_items di
                INNER JOIN dept_pouch_units du ON du.id = di.unit_id
                WHERE UPPER(du.batch_no) LIKE ?
            ) bx
        ) bb ON bb.session_id = s.id';
        $params[] = $batchNo . '%';
        $params[] = $batchNo . '%';
    }

    if ($productKey === 'honey') {
        $joins[] = ' INNER JOIN (
            SELECT DISTINCT session_id FROM honey_bottle_pack_session_items
        ) ph ON ph.session_id = s.id';
    } elseif ($productKey !== '') {
        $joins[] = ' INNER JOIN (
            SELECT DISTINCT session_id
            FROM honey_bottle_pack_session_dept_items
            WHERE product_key = ?
        ) pd ON pd.session_id = s.id';
        $params[] = $productKey;
    }

    $joinSql = implode('', $joins);
    $params[] = $fromDt;
    $params[] = $toDt;

    $countSql = 'SELECT COUNT(*) FROM honey_bottle_pack_sessions s' . $joinSql
        . ' WHERE s.status = "confirmed" AND s.confirmed_at IS NOT NULL'
        . ' AND s.confirmed_at BETWEEN ? AND ?';
    $countSt = $pdo->prepare($countSql);
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $listSql = 'SELECT s.id, s.status, s.started_by, s.confirmed_by, s.confirmed_at, s.created_at,
                       u_start.name AS started_by_name,
                       u_conf.name AS confirmed_by_name,
                       (
                         (SELECT COUNT(*) FROM honey_bottle_pack_session_items i WHERE i.session_id = s.id)
                         + (SELECT COUNT(*) FROM honey_bottle_pack_session_dept_items d WHERE d.session_id = s.id)
                       ) AS item_count
                FROM honey_bottle_pack_sessions s'
        . $joinSql
        . ' LEFT JOIN users u_start ON u_start.id = s.started_by
           LEFT JOIN users u_conf ON u_conf.id = s.confirmed_by
           WHERE s.status = "confirmed" AND s.confirmed_at IS NOT NULL
             AND s.confirmed_at BETWEEN ? AND ?
           ORDER BY s.confirmed_at DESC, s.id DESC
           LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;

    $listSt = $pdo->prepare($listSql);
    $listSt->execute($params);
    $rows = [];
    foreach ($listSt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $detail = honey_bottle_pack_session_get($pdo, (int) $r['id']);
        $rows[] = [
            'id' => (int) $r['id'],
            'status' => (string) $r['status'],
            'started_by' => (int) $r['started_by'],
            'started_by_name' => (string) ($r['started_by_name'] ?? ''),
            'confirmed_by' => $r['confirmed_by'] !== null ? (int) $r['confirmed_by'] : null,
            'confirmed_by_name' => (string) ($r['confirmed_by_name'] ?? ''),
            'confirmed_at' => (string) $r['confirmed_at'],
            'created_at' => (string) $r['created_at'],
            'item_count' => (int) $r['item_count'],
            'grouped' => $detail['grouped'],
            'by_batch' => $detail['by_batch'],
            'items' => $detail['items'],
        ];
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'from' => $from,
        'to' => $to,
        'product_key' => $productKey !== '' ? $productKey : 'all',
    ];
}

/**
 * @return array{result: string, session: array<string, mixed>, lookup?: array<string, mixed>|null}
 */
function honey_bottle_pack_session_scan(
    PDO $pdo,
    int $sessionId,
    string $code,
    ?int $qty = null,
    ?string $pool = null,
    ?int $packagingRowId = null
): array {
    $code = honey_bottle_normalize_code($code);
    $sess = honey_bottle_pack_session_get($pdo, $sessionId);
    if ($sess['status'] !== 'draft') {
        throw new RuntimeException('This pack session is already closed.');
    }

    require_once __DIR__ . '/sku_barcodes.php';
    $sku = sku_barcode_fetch_by_code($pdo, $code);
    if ($sku !== null) {
        return honey_bottle_pack_session_scan_sku(
            $pdo,
            $sessionId,
            $sku,
            $qty ?? 0,
            $pool,
            $packagingRowId
        );
    }

    $row = honey_bottle_fetch_code_row($pdo, $code);
    if ($row !== null) {
        return honey_bottle_pack_session_scan_honey($pdo, $sessionId, $row);
    }

    return honey_bottle_pack_session_scan_dept($pdo, $sessionId, $code);
}

/**
 * @param array<string, mixed> $sku
 * @return array{result: string, session: array<string, mixed>, lookup?: array<string, mixed>|null}
 */
function honey_bottle_pack_session_scan_sku(
    PDO $pdo,
    int $sessionId,
    array $sku,
    int $qty,
    ?string $pool,
    ?int $packagingRowId
): array {
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Enter how many units to pack for this SKU.');
    }
    $skuId = (int) $sku['id'];
    $code = (string) $sku['code'];
    if ((string) $sku['kind'] === 'honey') {
        $variant = honey_normalize_variant((string) $sku['variant']);
        $size = (string) $sku['size'];
        $poolUse = in_array((string) $pool, ['packaging', 'rts_sellable'], true)
            ? (string) $pool
            : 'packaging';
        $existing = $pdo->prepare(
            'SELECT id, qty FROM honey_bottle_pack_session_items
             WHERE session_id = ? AND sku_id = ? AND unit_id IS NULL LIMIT 1'
        );
        $existing->execute([$sessionId, $skuId]);
        $ex = $existing->fetch(PDO::FETCH_ASSOC);
        $totalQty = $qty;
        if ($ex !== false) {
            $totalQty = (int) $ex['qty'] + $qty;
            if ($totalQty > 5000) {
                throw new RuntimeException('Session quantity for this SKU cannot exceed 5000.');
            }
            $pdo->prepare(
                'UPDATE honey_bottle_pack_session_items
                 SET qty = ?, pool = ?, packaging_row_id = ?, variant = ?, size = ?, sku_code = ?
                 WHERE id = ?'
            )->execute([
                $totalQty,
                $poolUse,
                $packagingRowId !== null && $packagingRowId > 0 ? $packagingRowId : null,
                $variant,
                $size,
                $code,
                (int) $ex['id'],
            ]);
        } else {
            $pdo->prepare(
                'INSERT INTO honey_bottle_pack_session_items
                    (session_id, barcode_id, unit_id, sku_id, sku_code, qty, pool, packaging_row_id, variant, size)
                 VALUES (?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $sessionId,
                $skuId,
                $code,
                $qty,
                $poolUse,
                $packagingRowId !== null && $packagingRowId > 0 ? $packagingRowId : null,
                $variant,
                $size,
            ]);
        }

        return [
            'result' => 'ok',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => [
                'lookup_kind' => 'sku',
                'kind' => 'honey',
                'code' => $code,
                'variant' => $variant,
                'size' => $size,
                'size_label' => honey_bottle_size_label($size),
                'qty' => $totalQty,
                'added_qty' => $qty,
                'pool' => $poolUse,
            ],
        ];
    }

    $productKey = (string) $sku['product_key'];
    $poolUse = in_array((string) $pool, ['finished', 'rts_sellable'], true)
        ? (string) $pool
        : 'finished';
    $existing = $pdo->prepare(
        'SELECT id, qty FROM honey_bottle_pack_session_dept_items
         WHERE session_id = ? AND sku_id = ? AND unit_id IS NULL LIMIT 1'
    );
    $existing->execute([$sessionId, $skuId]);
    $ex = $existing->fetch(PDO::FETCH_ASSOC);
    $totalQty = $qty;
    if ($ex !== false) {
        $totalQty = (int) $ex['qty'] + $qty;
        if ($totalQty > 5000) {
            throw new RuntimeException('Session quantity for this SKU cannot exceed 5000.');
        }
        $pdo->prepare(
            'UPDATE honey_bottle_pack_session_dept_items
             SET qty = ?, pool = ?, sku_code = ?, product_key = ? WHERE id = ?'
        )->execute([$totalQty, $poolUse, $code, $productKey, (int) $ex['id']]);
    } else {
        $pdo->prepare(
            'INSERT INTO honey_bottle_pack_session_dept_items
                (session_id, product_key, barcode_id, unit_id, sku_id, sku_code, qty, pool)
             VALUES (?, ?, NULL, NULL, ?, ?, ?, ?)'
        )->execute([$sessionId, $productKey, $skuId, $code, $qty, $poolUse]);
    }

    return [
        'result' => 'ok',
        'session' => honey_bottle_pack_session_get($pdo, $sessionId),
        'lookup' => [
            'lookup_kind' => 'sku',
            'kind' => 'dept',
            'product_key' => $productKey,
            'product_label' => dept_pouch_product_label($productKey),
            'code' => $code,
            'qty' => $totalQty,
            'added_qty' => $qty,
            'pool' => $poolUse,
        ],
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array{result: string, session: array<string, mixed>, lookup?: array<string, mixed>|null}
 */
function honey_bottle_pack_session_scan_honey(PDO $pdo, int $sessionId, array $row): array
{
    if ((string) $row['status'] === 'void') {
        return [
            'result' => 'void',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => honey_bottle_serialize_lookup($row),
        ];
    }
    $dup = $pdo->prepare(
        'SELECT 1 FROM honey_bottle_pack_session_items WHERE session_id = ? AND barcode_id = ? LIMIT 1'
    );
    $dup->execute([$sessionId, (int) $row['id']]);
    if ($dup->fetchColumn()) {
        return [
            'result' => 'duplicate_session',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => honey_bottle_serialize_lookup($row),
        ];
    }
    $unitStatus = $row['unit_status'] !== null ? (string) $row['unit_status'] : '';
    if ($unitStatus === 'sold' || $unitStatus === 'shipped' || $unitStatus === 'packed') {
        return [
            'result' => 'already_consumed',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => honey_bottle_serialize_lookup($row),
        ];
    }
    if ($row['unit_id'] === null || $unitStatus !== 'in_stock') {
        return [
            'result' => 'not_in_stock',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => honey_bottle_serialize_lookup($row),
        ];
    }
    $pdo->prepare(
        'INSERT INTO honey_bottle_pack_session_items (session_id, barcode_id, unit_id) VALUES (?, ?, ?)'
    )->execute([$sessionId, (int) $row['id'], (int) $row['unit_id']]);

    return [
        'result' => 'ok',
        'session' => honey_bottle_pack_session_get($pdo, $sessionId),
        'lookup' => honey_bottle_serialize_lookup($row),
    ];
}

/**
 * @return array{result: string, session: array<string, mixed>, lookup?: array<string, mixed>|null}
 */
function honey_bottle_pack_session_scan_dept(PDO $pdo, int $sessionId, string $code): array
{
    $code = dept_pouch_normalize_code($code);
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
        return [
            'result' => 'unknown',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => null,
        ];
    }

    $productKey = (string) $row['product_key'];
    $productLabel = dept_pouch_product_label($productKey);
    $unitNoun = dept_pouch_unit_singular($productKey);
    $lookup = [
        'kind' => 'dept',
        'product_key' => $productKey,
        'product_label' => $productLabel,
        'barcode_id' => (int) $row['barcode_id'],
        'code' => (string) $row['code'],
        'barcode_status' => (string) $row['barcode_status'],
        'batch_no' => (string) $row['batch_no'],
        'variant' => $productLabel,
        'size' => $unitNoun,
        'size_label' => $unitNoun,
        'unit_id' => $row['unit_id'] !== null ? (int) $row['unit_id'] : null,
        'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
    ];

    if ((string) $row['barcode_status'] === 'void') {
        return [
            'result' => 'void',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }

    $dup = $pdo->prepare(
        'SELECT 1 FROM honey_bottle_pack_session_dept_items WHERE session_id = ? AND barcode_id = ? LIMIT 1'
    );
    $dup->execute([$sessionId, (int) $row['barcode_id']]);
    if ($dup->fetchColumn()) {
        return [
            'result' => 'duplicate_session',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }

    $unitStatus = $row['unit_status'] !== null ? (string) $row['unit_status'] : '';
    if ($unitStatus === 'sold' || $unitStatus === 'shipped' || $unitStatus === 'packed') {
        return [
            'result' => 'already_consumed',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }
    if ($row['unit_id'] === null || $unitStatus !== 'in_stock') {
        return [
            'result' => 'not_in_stock',
            'session' => honey_bottle_pack_session_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }

    $pdo->prepare(
        'INSERT INTO honey_bottle_pack_session_dept_items (session_id, product_key, barcode_id, unit_id)
         VALUES (?, ?, ?, ?)'
    )->execute([$sessionId, $productKey, (int) $row['barcode_id'], (int) $row['unit_id']]);

    return [
        'result' => 'ok',
        'session' => honey_bottle_pack_session_get($pdo, $sessionId),
        'lookup' => $lookup,
    ];
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_pack_session_confirm(PDO $pdo, int $userId, int $sessionId): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $lock = $pdo->prepare('SELECT * FROM honey_bottle_pack_sessions WHERE id = ? FOR UPDATE');
        $lock->execute([$sessionId]);
        $sess = $lock->fetch(PDO::FETCH_ASSOC);
        if ($sess === false) {
            throw new RuntimeException('Pack session not found.');
        }
        if ((string) $sess['status'] !== 'draft') {
            throw new RuntimeException('This pack session is already closed.');
        }
        $itemsSt = $pdo->prepare(
            'SELECT i.id AS item_id, i.unit_id, i.sku_id, i.qty, i.pool AS item_pool,
                    i.packaging_row_id AS item_packaging_row_id, i.variant AS item_variant, i.size AS item_size,
                    u.packaging_row_id, u.variant, u.size, u.status, u.pool, u.barcode_id, pk.batch_no
             FROM honey_bottle_pack_session_items i
             LEFT JOIN honey_bottle_units u ON u.id = i.unit_id
             LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $itemsSt->execute([$sessionId]);
        $items = $itemsSt->fetchAll(PDO::FETCH_ASSOC);

        $deptSt = $pdo->prepare(
            'SELECT i.id AS item_id, i.unit_id, i.product_key, i.sku_id, i.qty, i.pool AS item_pool,
                    un.status, un.batch_no, un.pool
             FROM honey_bottle_pack_session_dept_items i
             LEFT JOIN dept_pouch_units un ON un.id = i.unit_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $deptSt->execute([$sessionId]);
        $deptItems = $deptSt->fetchAll(PDO::FETCH_ASSOC);

        if ($items === [] && $deptItems === []) {
            throw new RuntimeException('Scan at least one barcode before confirming.');
        }

        require_once __DIR__ . '/rts_inventory_sync.php';
        require_once __DIR__ . '/sku_barcodes.php';
        $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
        $hasSessCol = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');

        // Unique stickers deduct from their linked packaging batch. SKU qty uses FIFO
        // across batches. Process uniques first so SKU FIFO cannot empty a batch that
        // a leftover sticker in this same session still needs.
        $honeyUniqueItems = [];
        $honeySkuItems = [];
        foreach ($items as $item) {
            $skuId = isset($item['sku_id']) ? (int) $item['sku_id'] : 0;
            $unitId = isset($item['unit_id']) ? (int) $item['unit_id'] : 0;
            if ($skuId > 0 && $unitId < 1) {
                $honeySkuItems[] = $item;
            } else {
                $honeyUniqueItems[] = $item;
            }
        }

        foreach (array_merge($honeyUniqueItems, $honeySkuItems) as $item) {
            $skuId = isset($item['sku_id']) ? (int) $item['sku_id'] : 0;
            $unitId = isset($item['unit_id']) ? (int) $item['unit_id'] : 0;
            if ($skuId > 0 && $unitId < 1) {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $variant = honey_normalize_variant((string) ($item['item_variant'] ?? ''));
                $size = (string) ($item['item_size'] ?? '');
                $pool = (string) ($item['item_pool'] ?? 'packaging');
                $packId = isset($item['item_packaging_row_id']) ? (int) $item['item_packaging_row_id'] : null;
                sku_barcode_honey_deduct(
                    $pdo,
                    $userId,
                    $variant,
                    $size,
                    $qty,
                    $pool,
                    $packId > 0 ? $packId : null,
                    'parcel',
                    null,
                    $sessionId
                );
                continue;
            }
            if ((string) $item['status'] !== 'in_stock') {
                throw new RuntimeException('A bottle in this session is no longer in stock. Discard and rescan.');
            }
            $unitId = (int) $item['unit_id'];
            $packId = (int) ($item['packaging_row_id'] ?? 0);
            $size = (string) $item['size'];
            $variant = honey_normalize_variant((string) $item['variant']);
            $pool = (string) ($item['pool'] ?? 'packaging');
            $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET status = "packed", consumed_at = NOW(), consumed_ref_type = "pack_session", consumed_ref_id = ?
                 WHERE id = ? AND status = "in_stock"'
            )->execute([$sessionId, $unitId]);
            if ($pool === 'rts_sellable') {
                rts_inventory_deduct_sell_honey_variant(
                    $pdo,
                    $userId,
                    $variant,
                    [$size => 1.0],
                    'parcel',
                    'sellable',
                    [
                        'used_where' => 'packaging',
                        'actor_user_id' => $userId,
                    ]
                );
            } else {
                $packId = honey_bottle_deduct_packaging_one($pdo, $variant, $size, $packId);
            }
            honey_bottle_write_sale_log($pdo, [
                'source' => $pool === 'rts_sellable' ? 'rts' : 'packaging',
                'packaging_row_id' => $packId > 0 ? $packId : null,
                'batch_no' => $item['batch_no'] !== null ? (string) $item['batch_no'] : null,
                'variant' => $variant,
                'size' => $size,
                'qty' => 1,
                'sale_type' => 'parcel',
                'sold_by' => $userId,
                'bottle_unit_id' => $hasUnitCol ? $unitId : null,
                'pack_session_id' => $hasSessCol ? $sessionId : null,
            ]);
        }

        $deptUniqueItems = [];
        $deptSkuItems = [];
        foreach ($deptItems as $item) {
            $skuId = isset($item['sku_id']) ? (int) $item['sku_id'] : 0;
            $unitId = isset($item['unit_id']) ? (int) $item['unit_id'] : 0;
            if ($skuId > 0 && $unitId < 1) {
                $deptSkuItems[] = $item;
            } else {
                $deptUniqueItems[] = $item;
            }
        }

        foreach (array_merge($deptUniqueItems, $deptSkuItems) as $item) {
            $skuId = isset($item['sku_id']) ? (int) $item['sku_id'] : 0;
            $unitId = isset($item['unit_id']) ? (int) $item['unit_id'] : 0;
            if ($skuId > 0 && $unitId < 1) {
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $productKey = (string) $item['product_key'];
                $pool = (string) ($item['item_pool'] ?? 'finished');
                sku_barcode_dept_deduct($pdo, $userId, $productKey, $qty, $pool, 'parcel');
                continue;
            }
            if ((string) $item['status'] !== 'in_stock') {
                throw new RuntimeException('A pouch/jar in this session is no longer in stock. Discard and rescan.');
            }
            $unitId = (int) $item['unit_id'];
            $productKey = (string) $item['product_key'];
            $pool = (string) ($item['pool'] ?? 'finished');
            $upd = $pdo->prepare(
                'UPDATE dept_pouch_units
                 SET status = "packed", consumed_at = NOW(), consumed_ref_type = "pack_session", consumed_ref_id = ?
                 WHERE id = ? AND status = "in_stock"'
            );
            $upd->execute([$sessionId, $unitId]);
            if ($upd->rowCount() < 1) {
                throw new RuntimeException('Could not mark a pouch/jar as packed. Discard and rescan.');
            }
            if ($pool === 'rts_sellable') {
                rts_inventory_deduct_sell_other_label(
                    $pdo,
                    $userId,
                    dept_pouch_product_label($productKey),
                    1.0,
                    'parcel',
                    'sellable',
                    [
                        'used_where' => 'packaging',
                        'actor_user_id' => $userId,
                    ]
                );
            } else {
                dept_inventory_deduct($pdo, $productKey, 1.0);
            }
        }

        $pdo->prepare(
            'UPDATE honey_bottle_pack_sessions SET status = "confirmed", confirmed_at = NOW(), confirmed_by = ? WHERE id = ?'
        )->execute([$userId, $sessionId]);
        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return honey_bottle_pack_session_get($pdo, $sessionId);
}

/**
 * Drop one scanned sticker from a draft pack session. Units are not consumed until confirm.
 *
 * @return array<string, mixed>
 */
function honey_bottle_pack_session_remove_item(PDO $pdo, int $sessionId, string $kind, int $itemId): array
{
    $sess = honey_bottle_pack_session_get($pdo, $sessionId);
    if ($sess['status'] !== 'draft') {
        throw new RuntimeException('This pack session is already closed.');
    }
    if ($itemId < 1) {
        throw new RuntimeException('Invalid session item.');
    }
    $kind = $kind === 'dept' ? 'dept' : 'honey';
    $sql = $kind === 'dept'
        ? 'DELETE FROM honey_bottle_pack_session_dept_items WHERE id = ? AND session_id = ?'
        : 'DELETE FROM honey_bottle_pack_session_items WHERE id = ? AND session_id = ?';
    $st = $pdo->prepare($sql);
    $st->execute([$itemId, $sessionId]);
    if ($st->rowCount() < 1) {
        throw new RuntimeException('That barcode is not in this session.');
    }

    return honey_bottle_pack_session_get($pdo, $sessionId);
}

function honey_bottle_pack_session_discard(PDO $pdo, int $sessionId): void
{
    $pdo->prepare(
        'UPDATE honey_bottle_pack_sessions SET status = "discarded" WHERE id = ? AND status = "draft"'
    )->execute([$sessionId]);
}

/**
 * @param array{
 *   source: string,
 *   packaging_row_id: int|null,
 *   batch_no: string|null,
 *   variant: string,
 *   size: string,
 *   qty: float,
 *   sale_type: string,
 *   sold_by: int,
 *   pos_sale_id?: int|null,
 *   bottle_unit_id?: int|null,
 *   pack_session_id?: int|null
 * } $row
 */
function honey_bottle_write_sale_log(PDO $pdo, array $row): void
{
    $qtyCols = ['liter' => 0.0, '250ml' => 0.0, '350ml' => 0.0, 'gallon' => 0.0];
    $size = (string) $row['size'];
    if (!isset($qtyCols[$size])) {
        throw new RuntimeException('Invalid size.');
    }
    $qtyCols[$size] = (float) $row['qty'];
    $hasUnit = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
    $hasSess = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');
    $hasPos = rts_db_has_column($pdo, 'honey_sales_log', 'pos_sale_id');
    $cols = [
        'source', 'packaging_row_id', 'batch_no', 'variant',
        'qty_liter', 'qty_250ml', 'qty_350ml', 'qty_gallon',
        'sale_type', 'sold_by',
    ];
    $vals = [
        $row['source'],
        $row['packaging_row_id'],
        $row['batch_no'],
        $row['variant'],
        $qtyCols['liter'],
        $qtyCols['250ml'],
        $qtyCols['350ml'],
        $qtyCols['gallon'],
        $row['sale_type'],
        $row['sold_by'],
    ];
    if ($hasPos) {
        $cols[] = 'pos_sale_id';
        $vals[] = $row['pos_sale_id'] ?? null;
    }
    if ($hasUnit) {
        $cols[] = 'bottle_unit_id';
        $vals[] = $row['bottle_unit_id'] ?? null;
    }
    if ($hasSess) {
        $cols[] = 'pack_session_id';
        $vals[] = $row['pack_session_id'] ?? null;
    }
    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $pdo->prepare(
        'INSERT INTO honey_sales_log (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')'
    )->execute($vals);
}

/**
 * @return array<string, mixed>
 */
function honey_bottle_pos_lookup(PDO $pdo, string $code): array
{
    $row = honey_bottle_fetch_code_row($pdo, $code);
    if ($row === null) {
        throw new RuntimeException('Unknown barcode.');
    }
    $lookup = honey_bottle_serialize_lookup($row);
    if ($lookup['unit_id'] === null) {
        throw new RuntimeException('This bottle is not in inventory yet.');
    }
    if ($lookup['unit_status'] !== 'in_stock') {
        throw new RuntimeException('This bottle is not available (' . (string) $lookup['unit_status'] . ').');
    }

    return $lookup;
}

/**
 * Consume scanned POS units. Caller must be in a transaction.
 *
 * @param list<int> $unitIds
 */
function honey_bottle_pos_consume(PDO $pdo, array $unitIds, int $saleId, int $userId): void
{
    if ($unitIds === []) {
        return;
    }
    $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
    foreach ($unitIds as $unitId) {
        $unitId = (int) $unitId;
        if ($unitId < 1) {
            throw new RuntimeException('Invalid bottle unit.');
        }
        $st = $pdo->prepare(
            'SELECT u.*, pk.batch_no FROM honey_bottle_units u
             LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
             WHERE u.id = ? FOR UPDATE'
        );
        $st->execute([$unitId]);
        $unit = $st->fetch(PDO::FETCH_ASSOC);
        if ($unit === false) {
            throw new RuntimeException('Bottle unit not found.');
        }
        if ((string) $unit['status'] !== 'in_stock') {
            throw new RuntimeException('A scanned bottle is no longer in stock.');
        }
        $pdo->prepare(
            'UPDATE honey_bottle_units
             SET status = "sold", consumed_at = NOW(), consumed_ref_type = "pos_sale", consumed_ref_id = ?
             WHERE id = ? AND status = "in_stock"'
        )->execute([$saleId, $unitId]);
        $packId = (int) ($unit['packaging_row_id'] ?? 0);
        $pool = (string) $unit['pool'];
        if ($pool === 'packaging') {
            $packId = honey_bottle_deduct_packaging_one(
                $pdo,
                honey_normalize_variant((string) $unit['variant']),
                (string) $unit['size'],
                $packId
            );
        } elseif ($pool === 'rts_sellable') {
            require_once __DIR__ . '/rts_inventory_sync.php';
            rts_inventory_deduct_sell_honey_variant(
                $pdo,
                0,
                honey_normalize_variant((string) $unit['variant']),
                [(string) $unit['size'] => 1.0],
                'walk_in',
                'sellable',
                [
                    'used_where' => 'pos',
                    'pos_sale_id' => $saleId,
                    'actor_user_id' => $userId > 0 ? $userId : null,
                ]
            );
        }
        honey_bottle_write_sale_log($pdo, [
            'source' => (string) $unit['pool'] === 'rts_sellable' ? 'rts' : 'packaging',
            'packaging_row_id' => $packId > 0 ? $packId : null,
            'batch_no' => $unit['batch_no'] !== null ? (string) $unit['batch_no'] : null,
            'variant' => honey_normalize_variant((string) $unit['variant']),
            'size' => (string) $unit['size'],
            'qty' => 1,
            'sale_type' => 'walk_in',
            'sold_by' => $userId > 0 ? $userId : 1,
            'pos_sale_id' => $saleId,
            'bottle_unit_id' => $hasUnitCol ? $unitId : null,
        ]);
    }
}

/**
 * Opened return: packed/sold/shipped → in_stock, reverse the sale row.
 *
 * @return array<string, mixed>
 */
function honey_bottle_opened_return_scan(PDO $pdo, int $userId, string $code): array
{
    $code = honey_bottle_normalize_code($code);
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT u.*, c.code, pk.batch_no
             FROM honey_bottle_barcodes c
             INNER JOIN honey_bottle_units u ON u.barcode_id = c.id
             LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
             WHERE c.code = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$code]);
        $unit = $st->fetch(PDO::FETCH_ASSOC);
        if ($unit === false) {
            throw new RuntimeException('Unknown barcode or bottle not in inventory.');
        }
        $status = (string) $unit['status'];
        if ($status === 'in_stock') {
            throw new RuntimeException('This bottle is already in stock — pack scan may have been missed.');
        }
        if (!in_array($status, ['packed', 'sold', 'shipped'], true)) {
            throw new RuntimeException('This bottle cannot be returned to stock (' . $status . ').');
        }
        $pdo->prepare(
            'UPDATE honey_bottle_units
             SET status = "in_stock", pool = "packaging", consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
             WHERE id = ?'
        )->execute([(int) $unit['id']]);
        $packId = (int) ($unit['packaging_row_id'] ?? 0);
        if ($packId > 0) {
            honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], 1);
        }
        honey_bottle_write_sale_log($pdo, [
            'source' => 'packaging',
            'packaging_row_id' => $packId > 0 ? $packId : null,
            'batch_no' => $unit['batch_no'] !== null ? (string) $unit['batch_no'] : null,
            'variant' => honey_normalize_variant((string) $unit['variant']),
            'size' => (string) $unit['size'],
            'qty' => -1,
            'sale_type' => 'parcel',
            'sold_by' => $userId,
            'bottle_unit_id' => (int) $unit['id'],
        ]);
        if ($owns) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return honey_bottle_lookup($pdo, $code);
}

/**
 * @param list<string> $codes
 * @return array{received: list<array<string,mixed>>, warnings: list<string>}
 */
function honey_bottle_rts_receive(PDO $pdo, int $userId, array $codes, string $pool): array
{
    unset($userId);
    if (!in_array($pool, ['rts_sellable', 'rts_damaged'], true)) {
        throw new RuntimeException('Invalid RTS pool.');
    }
    // Cutover: available sellable uses in_stock + rts_sellable (POS/pack compatible).
    // Damaged keeps status=damaged. Do not bump rts_inventory_rows here — sellable is barcode stock-in only.
    $targetStatus = $pool === 'rts_damaged' ? 'damaged' : 'in_stock';
    $received = [];
    $warnings = [];
    foreach ($codes as $raw) {
        $code = honey_bottle_normalize_code((string) $raw);
        if ($code === '') {
            continue;
        }
        $row = honey_bottle_fetch_code_row($pdo, $code);
        if ($row === null) {
            $warnings[] = $code . ': unknown barcode (unreadable sticker fallback needed).';
            continue;
        }
        if ($row['unit_id'] === null) {
            $warnings[] = $code . ': never stocked in — skipped.';
            continue;
        }
        $unitStatus = (string) ($row['unit_status'] ?? '');
        if ($unitStatus === 'in_stock' && (string) ($row['unit_pool'] ?? '') === 'packaging') {
            $warnings[] = $code . ': still in packaging stock — pack scan was missed.';
        }
        $pdo->prepare(
            'UPDATE honey_bottle_units
             SET status = ?, pool = ?, consumed_at = NOW(), consumed_ref_type = "rts_case"
             WHERE id = ?'
        )->execute([$targetStatus, $pool, (int) $row['unit_id']]);
        // Sellable cutover path: clear consumed_* so unit is available on-hand.
        if ($pool === 'rts_sellable') {
            $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ?'
            )->execute([(int) $row['unit_id']]);
        }
        $received[] = honey_bottle_serialize_lookup($row);
    }

    return ['received' => $received, 'warnings' => $warnings];
}

/**
 * @return array{rows: list<array<string,mixed>>, fractions: list<array<string,mixed>>}
 */
function honey_bottle_backfill_preview(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT id, batch_no, supplier_code, variant, bottling_date,
                current_liter, current_250ml, current_350ml, current_gallon
         FROM honey_packaging_rows
         ORDER BY id ASC'
    );
    $rows = [];
    $fractions = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $pk) {
        $sizes = [];
        foreach (HONEY_SIZES as $size) {
            [$col] = honey_packaging_size_columns($size);
            $qty = (float) $pk[$col];
            $floor = (int) floor($qty + 1e-9);
            $already = honey_bottle_unit_count_for_row($pdo, (int) $pk['id'], $size);
            $need = max(0, $floor - $already);
            $frac = $qty - $floor;
            if ($frac > 0.001) {
                $fractions[] = [
                    'packaging_row_id' => (int) $pk['id'],
                    'batch_no' => (string) $pk['batch_no'],
                    'size' => $size,
                    'current' => $qty,
                ];
            }
            $sizes[$size] = [
                'current' => $qty,
                'floor' => $floor,
                'already_units' => $already,
                'to_generate' => $need,
            ];
        }
        $rows[] = [
            'packaging_row_id' => (int) $pk['id'],
            'batch_no' => (string) $pk['batch_no'],
            'supplier_code' => (string) $pk['supplier_code'],
            'variant' => (string) $pk['variant'],
            'bottling_date' => (string) $pk['bottling_date'],
            'sizes' => $sizes,
        ];
    }

    return ['rows' => $rows, 'fractions' => $fractions];
}

function honey_bottle_unit_count_for_row(PDO $pdo, int $packagingRowId, string $size): int
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM honey_bottle_units WHERE packaging_row_id = ? AND size = ? AND status <> "void"'
    );
    $st->execute([$packagingRowId, $size]);

    return (int) $st->fetchColumn();
}

/**
 * Generate backfill barcodes (no aggregate increment). Does not create units until scanned.
 *
 * @return list<array<string, mixed>>
 */
function honey_bottle_backfill_generate(PDO $pdo, int $userId): array
{
    $preview = honey_bottle_backfill_preview($pdo);
    $created = [];
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        foreach ($preview['rows'] as $row) {
            foreach (HONEY_SIZES as $size) {
                $need = (int) ($row['sizes'][$size]['to_generate'] ?? 0);
                if ($need < 1) {
                    continue;
                }
                $created[] = honey_bottle_generate_batch(
                    $pdo,
                    $userId,
                    $need,
                    (int) $row['packaging_row_id'],
                    $size,
                    'backfill',
                    null
                );
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

    return $created;
}

/**
 * @return array{rows: list<array<string,mixed>>, drifted: int}
 */
function honey_bottle_reconcile(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT id, batch_no, variant, current_liter, current_250ml, current_350ml, current_gallon
         FROM honey_packaging_rows ORDER BY id ASC'
    );
    $rows = [];
    $drifted = 0;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $pk) {
        $packId = (int) $pk['id'];
        $sizes = [];
        $rowDrift = false;
        foreach (HONEY_SIZES as $size) {
            [$col] = honey_packaging_size_columns($size);
            $agg = (float) $pk[$col];
            $cntSt = $pdo->prepare(
                'SELECT COUNT(*) FROM honey_bottle_units
                 WHERE packaging_row_id = ? AND size = ? AND pool = "packaging" AND status = "in_stock"'
            );
            $cntSt->execute([$packId, $size]);
            $units = (int) $cntSt->fetchColumn();
            $diff = $agg - $units;
            if (abs($diff) > 0.001) {
                $rowDrift = true;
            }
            $sizes[$size] = ['aggregate' => $agg, 'units' => $units, 'diff' => $diff];
        }
        if ($rowDrift) {
            $drifted++;
        }
        $rows[] = [
            'packaging_row_id' => $packId,
            'batch_no' => (string) $pk['batch_no'],
            'variant' => (string) $pk['variant'],
            'drift' => $rowDrift,
            'sizes' => $sizes,
        ];
    }

    return ['rows' => $rows, 'drifted' => $drifted];
}

/**
 * @return list<array<string, mixed>>
 */
function honey_bottle_stock_summary(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT variant, size, COUNT(*) AS qty
         FROM honey_bottle_units
         WHERE status = "in_stock" AND pool = "packaging"
         GROUP BY variant, size
         ORDER BY variant, size'
    );
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[] = [
            'variant' => (string) $row['variant'],
            'size' => (string) $row['size'],
            'size_label' => honey_bottle_size_label((string) $row['size']),
            'qty' => (int) $row['qty'],
        ];
    }

    return $out;
}

function honey_bottle_code128_svg(string $code): string
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Barcode library is not installed (composer).');
    }
    require_once $autoload;
    $generator = new Picqer\Barcode\BarcodeGeneratorSVG();

    // 1 unit per module keeps the intrinsic aspect ratio predictable so the
    // printed bar width (X-dimension) is set purely by the CSS mm width below.
    // Height 24 keeps bars ~9-10mm tall at 40mm wide, leaving room under the
    // symbol for the human-readable code without dropping label density.
    return $generator->getBarcode($code, $generator::TYPE_CODE_128, 1.0, 24);
}

/**
 * @return list<array<string, mixed>>
 */
function honey_bottle_batch_codes(PDO $pdo, int $batchId): array
{
    $st = $pdo->prepare(
        'SELECT c.code, c.sequence_no, c.status, b.variant, b.size, b.batch_no, b.supplier_code
         FROM honey_bottle_barcodes c
         INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
         WHERE c.batch_id = ?
         ORDER BY c.sequence_no ASC'
    );
    $st->execute([$batchId]);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Ensure a packaging batch row exists for a production lot without deducting gallons.
 * Bottle counts stay 0 until stock-in scans.
 *
 * @return array<string, mixed>
 */
function honey_bottle_ensure_packaging_shell(PDO $pdo, int $userId, int $productionRowId): array
{
    if ($productionRowId < 1) {
        throw new RuntimeException('production_row_id is required.');
    }
    $existing = honey_fetch_packaging_by_production($pdo, $productionRowId);
    if ($existing !== null) {
        return $existing;
    }
    $pr = honey_fetch_production($pdo, $productionRowId);
    if ($pr === null) {
        throw new RuntimeException('Production row not found.');
    }
    $bottlingDate = date('Y-m-d');
    $batchNo = honey_unique_packaging_batch_no(
        $pdo,
        honey_batch_no((string) $pr['supplier_code'], $bottlingDate),
        (string) $pr['variant']
    );
    $ins = $pdo->prepare(
        'INSERT INTO honey_packaging_rows (
            production_row_id, supplier_code, batch_no, bottling_date, variant,
            current_liter, initial_liter, current_250ml, initial_250ml,
            current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
         ) VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
    );
    $ins->execute([
        $productionRowId,
        $pr['supplier_code'],
        $batchNo,
        $bottlingDate,
        $pr['variant'],
        $userId,
    ]);
    $pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());
    if ($pack === null) {
        throw new RuntimeException('Could not create packaging batch.');
    }

    return $pack;
}

/**
 * Create a packaging batch with a custom supplier (no production lot).
 * Used when older suppliers/lots are gone but bottles still need stickers.
 *
 * A batch code is reused across sizes and re-counts, so typing one that already
 * exists for this variant returns that row instead of failing — size lives on the
 * label request, not on the packaging row.
 *
 * @return array<string, mixed>
 */
function honey_bottle_create_custom_packaging(
    PDO $pdo,
    int $userId,
    string $supplierName,
    string $variant,
    string $bottlingDate,
    ?string $manualCode = null,
    bool &$reused = false
): array {
    $reused = false;
    $variant = honey_normalize_variant($variant);
    $ts = strtotime($bottlingDate);
    if ($ts === false) {
        throw new RuntimeException('Invalid bottling date.');
    }
    $bottlingDate = date('Y-m-d', $ts);

    $manualCode = $manualCode !== null ? strtoupper(trim($manualCode)) : '';
    if ($manualCode !== '') {
        // User typed the batch code directly, e.g. JR073026.
        if (!preg_match('/^[A-Z0-9][A-Z0-9-]{2,31}$/', $manualCode)) {
            throw new RuntimeException('Batch code must be 3–32 letters, digits or dashes (e.g. JR073026).');
        }
        $st = $pdo->prepare(
            'SELECT * FROM honey_packaging_rows WHERE batch_no = ? AND variant = ? LIMIT 1'
        );
        $st->execute([$manualCode, $variant]);
        $existing = $st->fetch(PDO::FETCH_ASSOC);
        if ($existing !== false) {
            $reused = true;

            return $existing;
        }
        // The Bottling date field is authoritative. A trailing MMDDYY in the code is
        // just part of the human label — it used to silently override the picked date.
        $supplierCode = $manualCode;
        $batchNo = $manualCode;
    } else {
        $supplierName = trim(preg_replace('/\s+/', ' ', $supplierName) ?? '');
        if ($supplierName === '' || mb_strlen($supplierName) > 120) {
            throw new RuntimeException('Supplier name is required (max 120 characters).');
        }
        $supplierCode = honey_supplier_code($supplierName, $bottlingDate);
        // Custom batch code is just the supplier code (initials + MMDDYY), e.g. JR063026.
        // Same supplier + date + variant means the same batch — reuse it rather than
        // minting JR063026-2 behind the user's back.
        $st = $pdo->prepare(
            'SELECT * FROM honey_packaging_rows WHERE batch_no = ? AND variant = ? LIMIT 1'
        );
        $st->execute([$supplierCode, $variant]);
        $existing = $st->fetch(PDO::FETCH_ASSOC);
        if ($existing !== false) {
            $reused = true;

            return $existing;
        }
        $batchNo = honey_unique_packaging_batch_no($pdo, $supplierCode, $variant);
    }

    $ins = $pdo->prepare(
        'INSERT INTO honey_packaging_rows (
            production_row_id, supplier_code, batch_no, bottling_date, variant,
            current_liter, initial_liter, current_250ml, initial_250ml,
            current_350ml, initial_350ml, current_gallon, initial_gallon, created_by
         ) VALUES (NULL, ?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, ?)'
    );
    $ins->execute([
        $supplierCode,
        $batchNo,
        $bottlingDate,
        $variant,
        $userId,
    ]);
    $pack = honey_fetch_packaging($pdo, (int) $pdo->lastInsertId());
    if ($pack === null) {
        throw new RuntimeException('Could not create custom packaging batch.');
    }

    return $pack;
}

/**
 * @return array{id: int, batch_no: string, supplier_code: string, variant: string, bottling_date: string, label: string, created: bool}
 */
function honey_bottle_serialize_packaging_option(array $pk, bool $created = false): array
{
    $custom = !isset($pk['production_row_id']) || $pk['production_row_id'] === null || (int) $pk['production_row_id'] < 1;
    $label = $pk['batch_no'] . ' · ' . ucfirst((string) $pk['variant']);
    if ($custom) {
        $label .= ' · custom';
    }

    return [
        'id' => (int) $pk['id'],
        'batch_no' => (string) $pk['batch_no'],
        'supplier_code' => (string) $pk['supplier_code'],
        'variant' => (string) $pk['variant'],
        'bottling_date' => (string) $pk['bottling_date'],
        'label' => $label,
        'custom' => $custom,
        'created' => $created,
    ];
}

function honey_bottle_label_sheet_html(PDO $pdo, int $batchId): string
{
    $batch = honey_bottle_batch_get($pdo, $batchId);
    $codes = honey_bottle_batch_codes($pdo, $batchId);
    $variant = htmlspecialchars(ucfirst((string) $batch['variant']), ENT_QUOTES, 'UTF-8');
    $sizeLabel = htmlspecialchars((string) $batch['size_label'], ENT_QUOTES, 'UTF-8');
    $batchNo = htmlspecialchars((string) $batch['batch_no'], ENT_QUOTES, 'UTF-8');
    $labels = [];
    foreach ($codes as $row) {
        $code = (string) $row['code'];
        if ((string) $row['status'] === 'void') {
            continue;
        }
        try {
            $svg = honey_bottle_code128_svg($code);
        } catch (Throwable) {
            $svg = '<div class="err">barcode lib missing</div>';
        }
        $safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        // Barcode + human-readable code (manual-entry fallback); cut on dashed guides.
        $labels[] = '<div class="label"><div class="bc" title="' . $safe . '">' . $svg
            . '</div><div class="code">' . $safe . '</div></div>';
    }

    // A4 portrait: 4 columns × 17 rows = 68 labels per page.
    // Grid is capped by scannability, not paper: a 6-char Code 128 is 101
    // modules wide, so a 40mm symbol gives ~0.4mm bars — about the minimum a
    // phone camera resolves. Narrower columns stop scanning reliably.
    $cols = 4;
    $rowsPerPage = 17;
    $perPage = $cols * $rowsPerPage;
    $pagesHtml = '';
    $chunks = array_chunk($labels, $perPage);
    if ($chunks === []) {
        $chunks = [[]];
    }
    foreach ($chunks as $i => $chunk) {
        // Pad to a full grid so cut guides stay complete on the last page.
        while (count($chunk) < $perPage) {
            $chunk[] = '<div class="label empty"></div>';
        }
        $pageClass = $i === 0 ? 'page' : 'page page-break';
        $pagesHtml .= '<section class="' . $pageClass . '"><div class="sheet">'
            . implode('', $chunk) . '</div></section>';
    }

    $title = 'Bottle labels · ' . $batchNo . ' · ' . $variant . ' ' . $sizeLabel;
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
        /* Rows are a fixed physical height, never 1fr: fractional rows have no
           intrinsic size, so when a grid crosses a page boundary the engine
           re-solves them and stacks cells on top of each other (two barcodes in
           one box on page 2). 17 x 16.8mm = 285.6mm, just under the 287mm
           printable height, so a page never spills a phantom row either. */
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
        /* Cell is 50mm wide; 5mm side padding leaves a ~40mm symbol plus the
           quiet zone Code 128 needs on both ends. */
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
          <span class="hint">' . (int) $count . ' label(s) · 68 per A4 (4×17) · barcode + code text · dashed lines = cut guides · print at 100% scale, no "fit to page"</span>
        </div>
        ' . $pagesHtml . '
        </body></html>';
}

/**
 * @return list<array<string, mixed>>
 */
function honey_bottle_packaging_options(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT id, production_row_id, batch_no, supplier_code, variant, bottling_date,
                current_liter, current_250ml, current_350ml, current_gallon
         FROM honey_packaging_rows
         ORDER BY id DESC
         LIMIT 300'
    );
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $rows[] = honey_bottle_serialize_packaging_option($r, false);
    }

    return $rows;
}
