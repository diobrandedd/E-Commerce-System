<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/honey_pos.php';

const FINISHED_LEAK_DAMAGE_HISTORY_PER_PAGE = 25;

function finished_leak_damage_normalize_reason(string $reason): string
{
    $reason = strtolower(trim($reason));
    if (!in_array($reason, ['leaked', 'damaged'], true)) {
        throw new RuntimeException('Choose Leaked or Damaged.');
    }

    return $reason;
}

function finished_leak_damage_reason_label(string $reason): string
{
    return $reason === 'leaked' ? 'Leaked' : 'Damaged';
}

/**
 * @return array<string, mixed>
 */
function finished_leak_damage_product_label(array $row): string
{
    if (($row['kind'] ?? '') === 'honey') {
        $variant = variant_label_for_display((string) ($row['variant'] ?? ''));
        $size = honey_bottle_size_label((string) ($row['size'] ?? ''));

        return trim("Honey {$variant} {$size}");
    }

    return dept_pouch_product_label((string) ($row['product_key'] ?? ''));
}

function variant_label_for_display(string $variant): string
{
    $variant = strtolower(trim($variant));

    return match ($variant) {
        'light' => 'Light',
        'amber' => 'Amber',
        'dark' => 'Dark',
        default => ucfirst($variant),
    };
}

/**
 * @return array<string, mixed>
 */
function finished_leak_damage_scan(PDO $pdo, int $userId, string $code, string $reason, ?string $note = null): array
{
    if ($userId < 1) {
        throw new RuntimeException('User is required.');
    }
    $reason = finished_leak_damage_normalize_reason($reason);
    $note = $note !== null ? trim($note) : '';
    if ($note === '') {
        $note = null;
    } else {
        $note = substr($note, 0, 255);
    }

    $honeyRow = honey_bottle_fetch_code_row($pdo, $code);
    if ($honeyRow !== null) {
        return finished_leak_damage_scan_honey($pdo, $userId, $honeyRow, $reason, $note);
    }

    $deptCode = dept_pouch_normalize_code($code);
    if ($deptCode === '') {
        throw new RuntimeException('Scan a barcode.');
    }

    return finished_leak_damage_scan_dept($pdo, $userId, $deptCode, $reason, $note);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function finished_leak_damage_scan_honey(PDO $pdo, int $userId, array $row, string $reason, ?string $note): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $code = honey_bottle_normalize_code((string) ($row['code'] ?? ''));
        $lock = $pdo->prepare(
            'SELECT c.id AS barcode_id, c.code, c.status AS barcode_status, c.size,
                    b.variant, b.batch_no, b.packaging_row_id,
                    u.id AS unit_id, u.status AS unit_status, u.pool, u.packaging_row_id AS unit_packaging_row_id
             FROM honey_bottle_barcodes c
             INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
             LEFT JOIN honey_bottle_units u ON u.barcode_id = c.id
             WHERE c.code = ?
             LIMIT 1
             FOR UPDATE'
        );
        $lock->execute([$code]);
        $fresh = $lock->fetch(PDO::FETCH_ASSOC);
        if ($fresh === false) {
            throw new RuntimeException('Unknown barcode.');
        }
        if ((string) ($fresh['barcode_status'] ?? '') === 'void') {
            throw new RuntimeException('Not in inventory.');
        }
        if ($fresh['unit_id'] === null || (string) ($fresh['unit_status'] ?? '') !== 'in_stock') {
            throw new RuntimeException('Not in inventory.');
        }
        if ((string) ($fresh['pool'] ?? '') !== 'packaging') {
            throw new RuntimeException('Not in inventory.');
        }

        $unitId = (int) $fresh['unit_id'];
        $barcodeId = (int) $fresh['barcode_id'];
        $packId = (int) ($fresh['unit_packaging_row_id'] ?? $fresh['packaging_row_id'] ?? 0);
        $size = (string) $fresh['size'];
        $variant = (string) $fresh['variant'];
        $batchNo = (string) ($fresh['batch_no'] ?? '');

        $upd = $pdo->prepare(
            'UPDATE honey_bottle_units
             SET status = "damaged", consumed_at = NOW(), consumed_ref_type = "leak_damage", consumed_ref_id = NULL
             WHERE id = ? AND status = "in_stock"'
        );
        $upd->execute([$unitId]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Not in inventory.');
        }

        if ($packId > 0) {
            honey_bottle_adjust_aggregate($pdo, $packId, $size, -1.0);
        }

        $ins = $pdo->prepare(
            'INSERT INTO finished_unit_leak_damage_log
                (kind, product_key, variant, size, barcode_id, unit_id, code, batch_no, reason, scanned_by, note)
             VALUES ("honey", NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$variant, $size, $barcodeId, $unitId, $code, $batchNo, $reason, $userId, $note]);
        $logId = (int) $pdo->lastInsertId();

        $pdo->prepare('UPDATE honey_bottle_units SET consumed_ref_id = ? WHERE id = ?')
            ->execute([$logId, $unitId]);

        if ($owns) {
            $pdo->commit();
        }

        $payload = [
            'id' => $logId,
            'kind' => 'honey',
            'product_key' => null,
            'variant' => $variant,
            'size' => $size,
            'code' => $code,
            'batch_no' => $batchNo,
            'reason' => $reason,
            'reason_label' => finished_leak_damage_reason_label($reason),
            'product_label' => finished_leak_damage_product_label([
                'kind' => 'honey',
                'variant' => $variant,
                'size' => $size,
            ]),
        ];
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $payload;
}

/**
 * @return array<string, mixed>
 */
function finished_leak_damage_scan_dept(PDO $pdo, int $userId, string $code, string $reason, ?string $note): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT c.id AS barcode_id, c.code, c.status AS barcode_status,
                    b.product_key, b.batch_no,
                    un.id AS unit_id, un.status AS unit_status
             FROM dept_pouch_barcodes c
             INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
             LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
             WHERE c.code = ?
             LIMIT 1
             FOR UPDATE'
        );
        $st->execute([$code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw new RuntimeException('Unknown barcode.');
        }
        if ((string) ($row['barcode_status'] ?? '') === 'void') {
            throw new RuntimeException('Not in inventory.');
        }
        if ($row['unit_id'] === null || (string) ($row['unit_status'] ?? '') !== 'in_stock') {
            throw new RuntimeException('Not in inventory.');
        }

        $productKey = (string) ($row['product_key'] ?? '');
        $unitId = (int) $row['unit_id'];
        $barcodeId = (int) $row['barcode_id'];
        $batchNo = (string) ($row['batch_no'] ?? '');

        $upd = $pdo->prepare(
            'UPDATE dept_pouch_units
             SET status = "damaged", consumed_at = NOW(), consumed_ref_type = "leak_damage", consumed_ref_id = NULL
             WHERE id = ? AND status = "in_stock"'
        );
        $upd->execute([$unitId]);
        if ($upd->rowCount() < 1) {
            throw new RuntimeException('Not in inventory.');
        }

        dept_inventory_deduct($pdo, $productKey, 1.0);

        $ins = $pdo->prepare(
            'INSERT INTO finished_unit_leak_damage_log
                (kind, product_key, variant, size, barcode_id, unit_id, code, batch_no, reason, scanned_by, note)
             VALUES ("dept", ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$productKey, $barcodeId, $unitId, $code, $batchNo, $reason, $userId, $note]);
        $logId = (int) $pdo->lastInsertId();

        $pdo->prepare('UPDATE dept_pouch_units SET consumed_ref_id = ? WHERE id = ?')
            ->execute([$logId, $unitId]);

        if ($owns) {
            $pdo->commit();
        }

        $payload = [
            'id' => $logId,
            'kind' => 'dept',
            'product_key' => $productKey,
            'variant' => null,
            'size' => null,
            'code' => $code,
            'batch_no' => $batchNo,
            'reason' => $reason,
            'reason_label' => finished_leak_damage_reason_label($reason),
            'product_label' => dept_pouch_product_label($productKey),
        ];
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $payload;
}

/**
 * @param array{reason?: string|null, product_key?: string|null, scan_date?: string|null} $filters
 * @return array{rows: list<array<string,mixed>>, total: int, page: int, per_page: int, total_pages: int}
 */
function finished_leak_damage_history(PDO $pdo, int $page = 1, int $perPage = FINISHED_LEAK_DAMAGE_HISTORY_PER_PAGE, array $filters = []): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $where = ' WHERE 1=1';
    $params = [];

    $reason = isset($filters['reason']) ? strtolower(trim((string) $filters['reason'])) : '';
    if ($reason !== '' && in_array($reason, ['leaked', 'damaged'], true)) {
        $where .= ' AND l.reason = ?';
        $params[] = $reason;
    }

    $productKey = isset($filters['product_key']) ? strtolower(trim((string) $filters['product_key'])) : '';
    if ($productKey !== '') {
        $where .= ' AND (l.product_key = ? OR (l.kind = "honey" AND ? = "honey"))';
        $params[] = $productKey;
        $params[] = $productKey;
    }

    $scanDate = isset($filters['scan_date']) ? trim((string) $filters['scan_date']) : '';
    if ($scanDate !== '' && strtolower($scanDate) !== 'all') {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $scanDate);
        if ($dt === false || $dt->format('Y-m-d') !== $scanDate) {
            throw new RuntimeException('Invalid scan date.');
        }
        $where .= ' AND l.scanned_at >= ? AND l.scanned_at <= ?';
        $params[] = $scanDate . ' 00:00:00';
        $params[] = $scanDate . ' 23:59:59';
    }

    $countSt = $pdo->prepare('SELECT COUNT(*) FROM finished_unit_leak_damage_log l' . $where);
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $sql = 'SELECT l.*, u.username AS scanned_by_name
            FROM finished_unit_leak_damage_log l
            INNER JOIN users u ON u.id = l.scanned_by'
        . $where
        . ' ORDER BY l.scanned_at DESC, l.id DESC
            LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
    $st = $pdo->prepare($sql);
    $st->execute($params);

    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[] = [
            'id' => (int) $row['id'],
            'kind' => (string) $row['kind'],
            'product_key' => $row['product_key'] !== null ? (string) $row['product_key'] : null,
            'variant' => $row['variant'] !== null ? (string) $row['variant'] : null,
            'size' => $row['size'] !== null ? (string) $row['size'] : null,
            'code' => (string) $row['code'],
            'batch_no' => (string) $row['batch_no'],
            'reason' => (string) $row['reason'],
            'reason_label' => finished_leak_damage_reason_label((string) $row['reason']),
            'product_label' => finished_leak_damage_product_label([
                'kind' => (string) $row['kind'],
                'product_key' => $row['product_key'],
                'variant' => $row['variant'],
                'size' => $row['size'],
            ]),
            'scanned_by' => (int) $row['scanned_by'],
            'scanned_by_name' => (string) $row['scanned_by_name'],
            'scanned_at' => (string) $row['scanned_at'],
            'note' => $row['note'] !== null ? (string) $row['note'] : null,
        ];
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}
