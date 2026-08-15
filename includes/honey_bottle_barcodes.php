<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/rts_schema.php';

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

function honey_bottle_can_request(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'staff'], true);
}

/**
 * Generate / print / void / backfill — executives and CSR (tl/staff in csr dept) only.
 * Honey department staff / TL / prod_manager cannot generate.
 */
function honey_bottle_can_generate(string $role, ?string $deptKey = null): bool
{
    if ($role === 'executive' || $role === 'admin') {
        return true;
    }
    if (in_array($role, ['csr_tl', 'csr_staff'], true)) {
        return true;
    }
    $dept = $deptKey !== null ? strtolower($deptKey) : null;
    if ($dept === 'csr' && in_array($role, ['tl', 'staff'], true)) {
        return true;
    }

    return false;
}

function honey_bottle_can_scan(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'staff'], true);
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
    return honey_bottle_can_generate((string) ($user['role'] ?? ''), honey_bottle_user_dept_key($user));
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
                (SELECT COUNT(*) FROM honey_barcode_batches b
                  INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                 WHERE b.request_id = r.id) AS generated_count,
                (SELECT COUNT(*) FROM honey_barcode_batches b
                  INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                 WHERE b.request_id = r.id AND c.status = "scanned") AS scanned_count,
                (SELECT MAX(b.id) FROM honey_barcode_batches b WHERE b.request_id = r.id) AS latest_batch_id
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
                (SELECT COUNT(*) FROM honey_barcode_batches b
                  INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                 WHERE b.request_id = r.id) AS generated_count,
                (SELECT COUNT(*) FROM honey_barcode_batches b
                  INNER JOIN honey_bottle_barcodes c ON c.batch_id = b.id
                 WHERE b.request_id = r.id AND c.status = \"scanned\") AS scanned_count,
                (SELECT MAX(b.id) FROM honey_barcode_batches b WHERE b.request_id = r.id) AS latest_batch_id
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
    ?int $requestId
): array {
    if (!in_array($source, ['request', 'backfill'], true)) {
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
    $batchDate = date('Y-m-d');

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $start = honey_bottle_next_sequence($pdo, $qty);
        $insB = $pdo->prepare(
            'INSERT INTO honey_barcode_batches (
                request_id, source, generated_by, packaging_row_id, supplier_code, batch_no,
                batch_date, variant, size, quantity_generated
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insB->execute([
            $requestId,
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
    if ($source === 'request' || $source === 'backfill') {
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

/**
 * Scanned stickers for a packaging row — who stocked them in and when.
 *
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int, packaging: array<string, mixed>|null}
 */
function honey_bottle_packaging_scan_log(PDO $pdo, int $packagingRowId, int $page = 1, int $perPage = 50): array
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
    $cst = $pdo->prepare(
        'SELECT COUNT(*)
         FROM honey_bottle_barcodes c
         INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
         WHERE b.packaging_row_id = ? AND c.status = "scanned" AND c.scanned_at IS NOT NULL'
    );
    $cst->execute([$packagingRowId]);
    $total = (int) $cst->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $st = $pdo->prepare(
        'SELECT c.code, c.size, c.scanned_at, c.scanned_by,
                u.name AS scanned_by_name,
                un.status AS unit_status, un.pool AS unit_pool
         FROM honey_bottle_barcodes c
         INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN users u ON u.id = c.scanned_by
         LEFT JOIN honey_bottle_units un ON un.barcode_id = c.id
         WHERE b.packaging_row_id = ? AND c.status = "scanned" AND c.scanned_at IS NOT NULL
         ORDER BY c.scanned_at DESC, c.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . $offset
    );
    $st->execute([$packagingRowId]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = [
            'code' => (string) $row['code'],
            'size' => (string) $row['size'],
            'size_label' => honey_bottle_size_label((string) $row['size']),
            'scanned_at' => (string) $row['scanned_at'],
            'scanned_by' => $row['scanned_by'] !== null ? (int) $row['scanned_by'] : null,
            'scanned_by_name' => (string) ($row['scanned_by_name'] ?? ''),
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
        'page' => $page,
        'per_page' => $perPage,
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
        'SELECT i.id, i.barcode_id, i.unit_id, i.scanned_at, c.code, u.variant, u.size,
                pk.batch_no
         FROM honey_bottle_pack_session_items i
         INNER JOIN honey_bottle_barcodes c ON c.id = i.barcode_id
         INNER JOIN honey_bottle_units u ON u.id = i.unit_id
         LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $itemsSt->execute([$sessionId]);
    $items = [];
    $grouped = [];
    $byBatch = [];
    foreach ($itemsSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $variant = (string) $row['variant'];
        $size = (string) $row['size'];
        $batchNo = $row['batch_no'] !== null ? (string) $row['batch_no'] : '';
        $items[] = [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'variant' => $variant,
            'size' => $size,
            'size_label' => honey_bottle_size_label($size),
            'batch_no' => $batchNo !== '' ? $batchNo : null,
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $key = $variant . '|' . $size;
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'variant' => $variant,
                'size' => $size,
                'size_label' => honey_bottle_size_label($size),
                'qty' => 0,
            ];
        }
        $grouped[$key]['qty']++;
        $bKey = $batchNo . '|' . $variant . '|' . $size;
        if (!isset($byBatch[$bKey])) {
            $byBatch[$bKey] = [
                'batch_no' => $batchNo !== '' ? $batchNo : null,
                'variant' => $variant,
                'size' => $size,
                'size_label' => honey_bottle_size_label($size),
                'qty' => 0,
            ];
        }
        $byBatch[$bKey]['qty']++;
    }

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
    int $page = 1
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
    $batchJoin = '';
    if ($batchNo !== '') {
        if (!preg_match('/^[A-Z0-9][A-Z0-9-]{0,31}$/', $batchNo)) {
            throw new RuntimeException('Batch code search must be letters, digits or dashes.');
        }
        $batchJoin = ' INNER JOIN (
            SELECT DISTINCT i.session_id
            FROM honey_bottle_pack_session_items i
            INNER JOIN honey_bottle_units u ON u.id = i.unit_id
            INNER JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
            WHERE UPPER(pk.batch_no) LIKE ?
        ) bb ON bb.session_id = s.id';
    }

    $params = [];
    if ($batchNo !== '') {
        $params[] = $batchNo . '%';
    }
    $params[] = $fromDt;
    $params[] = $toDt;

    $countSql = 'SELECT COUNT(*) FROM honey_bottle_pack_sessions s' . $batchJoin
        . ' WHERE s.status = "confirmed" AND s.confirmed_at IS NOT NULL'
        . ' AND s.confirmed_at BETWEEN ? AND ?';
    $countSt = $pdo->prepare($countSql);
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $listSql = 'SELECT s.id, s.status, s.started_by, s.confirmed_by, s.confirmed_at, s.created_at,
                       u_start.name AS started_by_name,
                       u_conf.name AS confirmed_by_name,
                       (SELECT COUNT(*) FROM honey_bottle_pack_session_items i WHERE i.session_id = s.id) AS item_count
                FROM honey_bottle_pack_sessions s'
        . $batchJoin
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
    ];
}

/**
 * @return array{result: string, session: array<string, mixed>, lookup?: array<string, mixed>}
 */
function honey_bottle_pack_session_scan(PDO $pdo, int $sessionId, string $code): array
{
    $code = honey_bottle_normalize_code($code);
    $sess = honey_bottle_pack_session_get($pdo, $sessionId);
    if ($sess['status'] !== 'draft') {
        throw new RuntimeException('This pack session is already closed.');
    }
    $row = honey_bottle_fetch_code_row($pdo, $code);
    if ($row === null) {
        return ['result' => 'unknown', 'session' => $sess, 'lookup' => null];
    }
    if ((string) $row['status'] === 'void') {
        return ['result' => 'void', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];
    }
    $dup = $pdo->prepare(
        'SELECT 1 FROM honey_bottle_pack_session_items WHERE session_id = ? AND barcode_id = ? LIMIT 1'
    );
    $dup->execute([$sessionId, (int) $row['id']]);
    if ($dup->fetchColumn()) {
        return ['result' => 'duplicate_session', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];
    }
    $unitStatus = $row['unit_status'] !== null ? (string) $row['unit_status'] : '';
    if ($unitStatus === 'sold' || $unitStatus === 'shipped' || $unitStatus === 'packed') {
        return ['result' => 'already_consumed', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];
    }
    if ($row['unit_id'] === null || $unitStatus !== 'in_stock') {
        return ['result' => 'not_in_stock', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];
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
            'SELECT i.unit_id, u.packaging_row_id, u.variant, u.size, u.status, u.barcode_id, pk.batch_no
             FROM honey_bottle_pack_session_items i
             INNER JOIN honey_bottle_units u ON u.id = i.unit_id
             LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $itemsSt->execute([$sessionId]);
        $items = $itemsSt->fetchAll(PDO::FETCH_ASSOC);
        if ($items === []) {
            throw new RuntimeException('Scan at least one bottle before confirming.');
        }
        $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
        $hasSessCol = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');
        foreach ($items as $item) {
            if ((string) $item['status'] !== 'in_stock') {
                throw new RuntimeException('A bottle in this session is no longer in stock. Discard and rescan.');
            }
            $unitId = (int) $item['unit_id'];
            $packId = (int) ($item['packaging_row_id'] ?? 0);
            $size = (string) $item['size'];
            $variant = honey_normalize_variant((string) $item['variant']);
            $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET status = "packed", consumed_at = NOW(), consumed_ref_type = "pack_session", consumed_ref_id = ?
                 WHERE id = ? AND status = "in_stock"'
            )->execute([$sessionId, $unitId]);
            if ($packId > 0) {
                honey_bottle_adjust_aggregate($pdo, $packId, $size, -1);
            }
            honey_bottle_write_sale_log($pdo, [
                'source' => 'packaging',
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
        if ($packId > 0 && $pool === 'packaging') {
            honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], -1);
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
    $targetStatus = $pool === 'rts_damaged' ? 'damaged' : 'rts';
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
        if ($unitStatus === 'in_stock') {
            $warnings[] = $code . ': still in stock — pack scan was missed.';
        }
        $pdo->prepare(
            'UPDATE honey_bottle_units
             SET status = ?, pool = ?, consumed_at = NOW(), consumed_ref_type = "rts_case"
             WHERE id = ?'
        )->execute([$targetStatus, $pool, (int) $row['unit_id']]);
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
