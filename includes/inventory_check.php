<?php

declare(strict_types=1);

require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/inventory_edit_log.php';

const INVENTORY_CHECK_HONEY_SIZE_COLUMNS = [
    '250ml' => 'current_250ml',
    '350ml' => 'current_350ml',
    'liter' => 'current_liter',
    'gallon' => 'current_gallon',
];

function inventory_check_can_scan(array $user): bool
{
    return ims_user_can($user, 'inventory.check.scan');
}

function inventory_check_can_view_all(array $user): bool
{
    return ims_user_can($user, 'inventory.check.view_all');
}

function inventory_check_can_manage(array $user): bool
{
    return ims_user_can($user, 'inventory.check.manage');
}

/* ---------------------------------------------------------------- events */

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function inventory_check_serialize_event(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'count_date' => (string) $row['count_date'],
        'status' => (string) $row['status'],
        'note' => $row['note'] !== null ? (string) $row['note'] : null,
        'opened_by' => (int) $row['opened_by'],
        'opened_by_name' => (string) ($row['opened_by_name'] ?? ''),
        'opened_at' => (string) $row['opened_at'],
        'closed_by_name' => isset($row['closed_by_name']) && $row['closed_by_name'] !== null
            ? (string) $row['closed_by_name']
            : null,
        'closed_at' => $row['closed_at'] !== null ? (string) $row['closed_at'] : null,
        'scan_count' => isset($row['scan_count']) ? (int) $row['scan_count'] : 0,
        'scanner_count' => isset($row['scanner_count']) ? (int) $row['scanner_count'] : 0,
        'compare_run_count' => isset($row['compare_run_count']) ? (int) $row['compare_run_count'] : 0,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function inventory_check_event_fetch(PDO $pdo, int $eventId): ?array
{
    $st = $pdo->prepare(
        'SELECT e.*, uo.name AS opened_by_name, uc.name AS closed_by_name,
                (SELECT COUNT(*) FROM inventory_check_scans s WHERE s.event_id = e.id) AS scan_count,
                (SELECT COUNT(DISTINCT s.scanned_by) FROM inventory_check_scans s WHERE s.event_id = e.id) AS scanner_count,
                (SELECT COUNT(*) FROM inventory_check_compare_runs r WHERE r.event_id = e.id) AS compare_run_count
         FROM inventory_check_events e
         LEFT JOIN users uo ON uo.id = e.opened_by
         LEFT JOIN users uc ON uc.id = e.closed_by
         WHERE e.id = ? LIMIT 1'
    );
    $st->execute([$eventId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/**
 * @return array<string, mixed>
 */
function inventory_check_event_get(PDO $pdo, int $eventId): array
{
    $row = inventory_check_event_fetch($pdo, $eventId);
    if ($row === null) {
        throw new RuntimeException('Inventory count not found.');
    }

    return inventory_check_serialize_event($row);
}

/**
 * The open audit owned by this user, if any.
 *
 * @return array<string, mixed>|null
 */
function inventory_check_event_current(PDO $pdo, int $userId): ?array
{
    if ($userId < 1) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT id FROM inventory_check_events
         WHERE status = "open" AND opened_by = ?
         ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$userId]);
    $id = (int) $st->fetchColumn();

    return $id > 0 ? inventory_check_event_get($pdo, $id) : null;
}

/**
 * Whether the user may view or act on this event (owner, or manage/view_all).
 *
 * @param array{id:int,role?:string} $user
 */
function inventory_check_user_can_access_event(array $user, array $event): bool
{
    if ((int) ($event['opened_by'] ?? 0) === (int) ($user['id'] ?? 0)) {
        return true;
    }

    return inventory_check_can_view_all($user) || inventory_check_can_manage($user);
}

/**
 * @param array{id:int,role?:string} $user
 * @return array<string, mixed>
 */
function inventory_check_event_require_access(PDO $pdo, int $eventId, array $user): array
{
    $event = inventory_check_event_get($pdo, $eventId);
    if (!inventory_check_user_can_access_event($user, $event)) {
        throw new RuntimeException('Forbidden.');
    }

    return $event;
}

/**
 * Open a private audit session for this user (does not join anyone else's).
 *
 * @return array<string, mixed>
 */
function inventory_check_event_open(PDO $pdo, int $userId, ?string $note = null): array
{
    $open = inventory_check_event_current($pdo, $userId);
    if ($open !== null) {
        return $open;
    }

    $today = date('Y-m-d');
    $note = $note !== null ? trim($note) : '';
    $pdo->prepare('INSERT INTO inventory_check_events (count_date, opened_by, note) VALUES (?, ?, ?)')
        ->execute([$today, $userId, $note !== '' ? mb_substr($note, 0, 255) : null]);

    return inventory_check_event_get($pdo, (int) $pdo->lastInsertId());
}

/**
 * @return array<string, mixed>
 */
function inventory_check_event_close(PDO $pdo, int $userId, int $eventId): array
{
    $row = inventory_check_event_fetch($pdo, $eventId);
    if ($row === null) {
        throw new RuntimeException('Inventory count not found.');
    }
    if ((string) $row['status'] === 'closed') {
        return inventory_check_serialize_event($row);
    }
    $pdo->prepare(
        'UPDATE inventory_check_events SET status = "closed", closed_by = ?, closed_at = NOW() WHERE id = ?'
    )->execute([$userId, $eventId]);

    return inventory_check_event_get($pdo, $eventId);
}

/**
 * Discard an open audit session without saving to history (deletes event + scans).
 */
function inventory_check_event_cancel(PDO $pdo, int $eventId): void
{
    $row = inventory_check_event_fetch($pdo, $eventId);
    if ($row === null) {
        throw new RuntimeException('Inventory count not found.');
    }
    if ((string) $row['status'] !== 'open') {
        throw new RuntimeException('Only an open audit session can be cancelled.');
    }

    $runIds = $pdo->prepare('SELECT id FROM inventory_check_compare_runs WHERE event_id = ?');
    $runIds->execute([$eventId]);
    $ids = $runIds->fetchAll(PDO::FETCH_COLUMN);
    if ($ids) {
        $in = implode(',', array_map('intval', $ids));
        $pdo->exec('DELETE FROM inventory_check_variance_lines WHERE run_id IN (' . $in . ')');
        $pdo->prepare('DELETE FROM inventory_check_compare_runs WHERE event_id = ?')->execute([$eventId]);
    }
    $pdo->prepare('DELETE FROM inventory_check_scans WHERE event_id = ?')->execute([$eventId]);
    $pdo->prepare('DELETE FROM inventory_check_events WHERE id = ?')->execute([$eventId]);
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int}
 */
function inventory_check_event_list(PDO $pdo, int $page = 1, int $perPage = 25, ?int $onlyOpenedBy = null): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));

    $where = '';
    $params = [];
    if ($onlyOpenedBy !== null && $onlyOpenedBy > 0) {
        $where = ' WHERE e.opened_by = ?';
        $params[] = $onlyOpenedBy;
        $countSt = $pdo->prepare('SELECT COUNT(*) FROM inventory_check_events e' . $where);
        $countSt->execute($params);
        $total = (int) $countSt->fetchColumn();
    } else {
        $total = (int) $pdo->query('SELECT COUNT(*) FROM inventory_check_events')->fetchColumn();
    }

    $st = $pdo->prepare(
        'SELECT e.*, uo.name AS opened_by_name, uc.name AS closed_by_name,
                (SELECT COUNT(*) FROM inventory_check_scans s WHERE s.event_id = e.id) AS scan_count,
                (SELECT COUNT(DISTINCT s.scanned_by) FROM inventory_check_scans s WHERE s.event_id = e.id) AS scanner_count,
                (SELECT COUNT(*) FROM inventory_check_compare_runs r WHERE r.event_id = e.id) AS compare_run_count
         FROM inventory_check_events e
         LEFT JOIN users uo ON uo.id = e.opened_by
         LEFT JOIN users uc ON uc.id = e.closed_by
         ' . $where . '
         ORDER BY e.count_date DESC, e.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
    );
    $st->execute($params);

    return [
        'rows' => array_map('inventory_check_serialize_event', $st->fetchAll(PDO::FETCH_ASSOC)),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ];
}

/* ----------------------------------------------------------------- scans */

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function inventory_check_serialize_scan(array $row): array
{
    $kind = (string) $row['kind'];
    $productKey = (string) $row['product_key'];
    $size = $row['size'] !== null ? (string) $row['size'] : null;

    return [
        'id' => (int) $row['id'],
        'kind' => $kind,
        'product_key' => $productKey,
        'product_label' => $kind === 'honey' ? 'Honey' : dept_pouch_product_label($productKey),
        'code' => (string) $row['code'],
        'packaging_row_id' => $row['packaging_row_id'] !== null ? (int) $row['packaging_row_id'] : null,
        'supplier_code' => $row['supplier_code'] !== null ? (string) $row['supplier_code'] : null,
        'batch_no' => $row['batch_no'] !== null ? (string) $row['batch_no'] : null,
        'bottling_date' => $row['bottling_date'] !== null ? (string) $row['bottling_date'] : null,
        'variant' => $row['variant'] !== null ? (string) $row['variant'] : null,
        'size' => $size,
        'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,
        'flag' => (string) $row['flag'],
        'counted_qty' => isset($row['counted_qty']) ? (int) $row['counted_qty'] : 1,
        'sku_id' => isset($row['sku_id']) && $row['sku_id'] !== null ? (int) $row['sku_id'] : null,
        'scanned_by' => (int) $row['scanned_by'],
        'scanned_by_name' => (string) ($row['scanned_by_name'] ?? ''),
        'scanned_at' => (string) $row['scanned_at'],
    ];
}

/**
 * Record a count line. SKU barcodes take counted_qty (upsert by code+batch). Unique leftovers are +1 per code.
 * Never mutates stock.
 *
 * @return array{result: string, scan: array<string, mixed>|null, message: string}
 */
/**
 * Optional documentation fields. Empty / invalid date → null (not required).
 *
 * @return array{0: ?string, 1: ?string} [batch_no|null, bottling_date Y-m-d|null]
 */
function inventory_check_optional_doc_fields(?string $batchNo, ?string $bottlingDate): array
{
    $batch = strtoupper(trim((string) $batchNo));
    if ($batch === '' || strlen($batch) > 64) {
        $batch = '';
    }
    $date = trim((string) $bottlingDate);
    if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt === false || $dt->format('Y-m-d') !== $date) {
            $date = '';
        }
    } else {
        $date = '';
    }

    return [$batch !== '' ? $batch : null, $date !== '' ? $date : null];
}

function inventory_check_scan(
    PDO $pdo,
    int $eventId,
    int $userId,
    string $rawCode,
    ?int $countedQty = null,
    ?string $batchNo = null,
    ?string $bottlingDate = null
): array {
    $event = inventory_check_event_fetch($pdo, $eventId);
    if ($event === null) {
        throw new RuntimeException('Inventory count not found.');
    }
    if ((string) $event['status'] !== 'open') {
        throw new RuntimeException('This inventory count is already closed.');
    }
    if ((int) ($event['opened_by'] ?? 0) !== $userId) {
        throw new RuntimeException('You can only scan into your own audit session.');
    }

    $code = strtoupper(trim($rawCode));
    if ($code === '' || strlen($code) > 64) {
        throw new RuntimeException('Scan or type a barcode.');
    }
    [$docBatch, $docDate] = inventory_check_optional_doc_fields($batchNo, $bottlingDate);

    require_once __DIR__ . '/sku_barcodes.php';
    $sku = sku_barcode_fetch_by_code($pdo, $code);
    if ($sku !== null) {
        $qty = $countedQty !== null ? $countedQty : 0;
        if ($qty < 1 || $qty > 100000) {
            throw new RuntimeException('Enter how many you counted for this product.');
        }
        $kind = (string) $sku['kind'];
        $productKey = $kind === 'honey' ? 'honey' : (string) $sku['product_key'];
        $variant = $kind === 'honey' ? (string) $sku['variant'] : null;
        $size = $kind === 'honey' ? (string) $sku['size'] : null;
        $packagingRowId = null;
        $supplierCode = null;
        // Live honey stock is the variant POOL — link for reference only.
        if ($kind === 'honey' && $variant !== null && $variant !== '') {
            require_once __DIR__ . '/honey_inventory.php';
            $pool = honey_packaging_ensure_variant_pool($pdo, $userId, $variant);
            $packagingRowId = (int) $pool['id'];
            $supplierCode = (string) ($pool['supplier_code'] ?? '');
            if ($supplierCode === '') {
                $supplierCode = null;
            }
        }

        $dup = $pdo->prepare(
            'SELECT s.*, u.name AS scanned_by_name FROM inventory_check_scans s
             LEFT JOIN users u ON u.id = s.scanned_by
             WHERE s.event_id = ? AND s.code = ?
             LIMIT 1'
        );
        $dup->execute([$eventId, $code]);
        $dupRow = $dup->fetch(PDO::FETCH_ASSOC);
        if ($dupRow !== false) {
            $totalQty = (int) ($dupRow['counted_qty'] ?? 0) + $qty;
            if ($totalQty > 100000) {
                throw new RuntimeException('Counted quantity for this product cannot exceed 100000.');
            }
            $pdo->prepare(
                'UPDATE inventory_check_scans
                 SET counted_qty = ?, scanned_by = ?, scanned_at = NOW(), flag = "ok",
                     packaging_row_id = COALESCE(?, packaging_row_id),
                     supplier_code = COALESCE(?, supplier_code),
                     variant = COALESCE(?, variant),
                     size = COALESCE(?, size)
                 WHERE id = ?'
            )->execute([
                $totalQty,
                $userId,
                $packagingRowId,
                $supplierCode,
                $variant,
                $size,
                (int) $dupRow['id'],
            ]);
            $scan = inventory_check_scan_get($pdo, (int) $dupRow['id']);

            return [
                'result' => 'ok',
                'scan' => $scan,
                'message' => 'Added ' . $qty . ' (total ' . $totalQty . ').',
            ];
        }
        $pdo->prepare(
            'INSERT INTO inventory_check_scans
                (event_id, kind, product_key, barcode_id, sku_id, unit_id, code, packaging_row_id,
                 supplier_code, batch_no, bottling_date, variant, size, flag, counted_qty, scanned_by)
             VALUES (?, ?, ?, NULL, ?, NULL, ?, ?, ?, ?, ?, ?, ?, "ok", ?, ?)'
        )->execute([
            $eventId,
            $kind,
            $productKey,
            (int) $sku['id'],
            $code,
            $packagingRowId,
            $supplierCode,
            $docBatch,
            $docDate,
            $variant,
            $size,
            $qty,
            $userId,
        ]);
        $scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());

        return ['result' => 'ok', 'scan' => $scan, 'message' => 'Counted ' . $qty . '.'];
    }

    $dup = $pdo->prepare(
        'SELECT s.*, u.name AS scanned_by_name FROM inventory_check_scans s
         LEFT JOIN users u ON u.id = s.scanned_by
         WHERE s.event_id = ? AND s.code = ? LIMIT 1'
    );
    $dup->execute([$eventId, $code]);
    $dupRow = $dup->fetch(PDO::FETCH_ASSOC);
    if ($dupRow !== false) {
        return [
            'result' => 'duplicate_event',
            'scan' => inventory_check_serialize_scan($dupRow),
            'message' => 'Already counted by ' . ((string) ($dupRow['scanned_by_name'] ?? 'someone')) . '.',
        ];
    }

    $honey = inventory_check_resolve_honey($pdo, $code);
    $resolved = $honey ?? inventory_check_resolve_dept($pdo, $code);
    if ($resolved === null) {
        return ['result' => 'unknown', 'scan' => null, 'message' => 'Unknown barcode — not counted.'];
    }

    // Prefer sticker metadata when the client did not supply docs.
    $storeBatch = $docBatch ?? (isset($resolved['batch_no']) ? (string) $resolved['batch_no'] : null);
    $storeDate = $docDate ?? (isset($resolved['bottling_date']) ? (string) $resolved['bottling_date'] : null);
    if ($storeBatch !== null && trim($storeBatch) === '') {
        $storeBatch = null;
    }
    if ($storeDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $storeDate)) {
        $storeDate = null;
    }

    $qty = $countedQty !== null && $countedQty > 0 ? min(100000, $countedQty) : 1;

    $pdo->prepare(
        'INSERT INTO inventory_check_scans
            (event_id, kind, product_key, barcode_id, unit_id, code, packaging_row_id,
             supplier_code, batch_no, bottling_date, variant, size, flag, counted_qty, scanned_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $eventId,
        $resolved['kind'],
        $resolved['product_key'],
        $resolved['barcode_id'],
        $resolved['unit_id'],
        $code,
        $resolved['packaging_row_id'],
        $resolved['supplier_code'],
        $storeBatch,
        $storeDate,
        $resolved['variant'],
        $resolved['size'],
        $resolved['flag'],
        $qty,
        $userId,
    ]);

    $scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());
    $message = match ($resolved['flag']) {
        'void' => 'Counted, but this barcode is voided in the system.',
        'not_in_stock' => 'Counted, but the system does not show this unit in stock.',
        default => 'Counted.',
    };

    return ['result' => $resolved['flag'] === 'ok' ? 'ok' : $resolved['flag'], 'scan' => $scan, 'message' => $message];
}

/**
 * @return array<string, mixed>|null
 */
function inventory_check_packaging_by_batch(PDO $pdo, string $batchNo, ?string $variant = null): ?array
{
    $batchNo = strtoupper(trim($batchNo));
    if ($batchNo === '') {
        return null;
    }
    if ($variant !== null && trim($variant) !== '') {
        $st = $pdo->prepare(
            'SELECT * FROM honey_packaging_rows
             WHERE UPPER(TRIM(batch_no)) = ? AND LOWER(TRIM(variant)) = ?
             ORDER BY id DESC LIMIT 1'
        );
        $st->execute([$batchNo, strtolower(trim($variant))]);
    } else {
        $st = $pdo->prepare(
            'SELECT * FROM honey_packaging_rows WHERE UPPER(TRIM(batch_no)) = ? ORDER BY id DESC LIMIT 1'
        );
        $st->execute([$batchNo]);
    }
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/**
 * Update counted qty on an open-event scan row.
 *
 * @return array<string, mixed>
 */
function inventory_check_scan_update(
    PDO $pdo,
    int $scanId,
    int $userId,
    bool $isManager,
    ?int $countedQty = null,
    ?string $batchNo = null,
    ?string $bottlingDate = null
): array {
    $st = $pdo->prepare(
        'SELECT s.*, e.status FROM inventory_check_scans s
         INNER JOIN inventory_check_events e ON e.id = s.event_id
         WHERE s.id = ? LIMIT 1'
    );
    $st->execute([$scanId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Scan not found.');
    }
    if ((string) $row['status'] !== 'open') {
        throw new RuntimeException('This inventory count is already closed.');
    }
    if (!$isManager && (int) $row['scanned_by'] !== $userId) {
        throw new RuntimeException('You can only edit your own lines.');
    }

    $qty = $countedQty !== null ? $countedQty : (int) ($row['counted_qty'] ?? 1);
    if ($qty < 1 || $qty > 100000) {
        throw new RuntimeException('Quantity must be between 1 and 100000.');
    }

    // Optional docs — only overwrite when the client sends them.
    $sets = ['counted_qty = ?', 'scanned_by = ?', 'scanned_at = NOW()'];
    $params = [$qty, $userId];
    if ($batchNo !== null || $bottlingDate !== null) {
        [$newBatch, $newDate] = inventory_check_optional_doc_fields($batchNo, $bottlingDate);
        $sets[] = 'batch_no = ?';
        $sets[] = 'bottling_date = ?';
        $params[] = $newBatch;
        $params[] = $newDate;
    }
    $params[] = $scanId;
    $pdo->prepare('UPDATE inventory_check_scans SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

    return inventory_check_scan_get($pdo, $scanId);
}

/**
 * Read-only variance preview (no DB write).
 *
 * @param list<int>|null $eventIds when set, merge counts across these events
 * @return array<string, mixed>
 */
function inventory_check_compare_preview(PDO $pdo, int $eventId, ?array $eventIds = null): array
{
    $ids = $eventIds !== null && $eventIds !== []
        ? array_values(array_unique(array_filter(array_map('intval', $eventIds))))
        : [$eventId];
    if ($ids === []) {
        throw new RuntimeException('Select at least one audit session.');
    }

    $event = inventory_check_event_get($pdo, $ids[0]);
    $built = inventory_check_compare_build($pdo, $ids);
    if ($built['scope'] === []) {
        throw new RuntimeException('Nothing has been counted yet.');
    }

    $countedTotal = 0;
    $systemTotal = 0.0;
    $mismatch = 0;
    $lines = [];
    foreach ($built['lines'] as $i => $line) {
        $countedTotal += (int) $line['counted_qty'];
        $systemTotal += (float) $line['system_qty'];
        if (abs((float) $line['variance']) > 0.001) {
            $mismatch++;
        }
        $lines[] = array_merge($line, ['id' => $i + 1]);
    }

    $scopeLabel = implode(', ', $built['scope_labels']);
    if (count($ids) > 1) {
        $scopeLabel = 'Merged · ' . $scopeLabel;
    }

    return [
        'preview' => true,
        'run' => [
            'id' => 0,
            'event_id' => $ids[0],
            'event_ids' => $ids,
            'scope' => $scopeLabel,
            'line_count' => count($lines),
            'mismatch_count' => $mismatch,
            'counted_total' => $countedTotal,
            'system_total' => (int) round($systemTotal),
            'run_by_name' => '',
            'run_at' => date('Y-m-d H:i:s'),
        ],
        'lines' => $lines,
        'batches' => inventory_check_batch_docs_build($pdo, $ids),
        'event' => $event,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function inventory_check_resolve_honey(PDO $pdo, string $code): ?array
{
    $st = $pdo->prepare(
        'SELECT c.id AS barcode_id, c.status AS barcode_status, c.size,
                b.supplier_code, b.batch_no, b.batch_date, b.variant,
                b.packaging_row_id AS batch_packaging_row_id,
                un.id AS unit_id, un.status AS unit_status, un.packaging_row_id AS unit_packaging_row_id,
                pk.bottling_date, pk.supplier_code AS pk_supplier_code, pk.batch_no AS pk_batch_no,
                pk.variant AS pk_variant
         FROM honey_bottle_barcodes c
         INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN honey_bottle_units un ON un.barcode_id = c.id
         LEFT JOIN honey_packaging_rows pk
                ON pk.id = COALESCE(un.packaging_row_id, b.packaging_row_id)
         WHERE c.code = ? LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    $packagingRowId = $row['unit_packaging_row_id'] ?? $row['batch_packaging_row_id'];
    $unitStatus = $row['unit_status'] !== null ? (string) $row['unit_status'] : '';

    return [
        'kind' => 'honey',
        'product_key' => 'honey',
        'barcode_id' => (int) $row['barcode_id'],
        'unit_id' => $row['unit_id'] !== null ? (int) $row['unit_id'] : null,
        'packaging_row_id' => $packagingRowId !== null ? (int) $packagingRowId : null,
        'supplier_code' => (string) ($row['pk_supplier_code'] ?? $row['supplier_code']),
        'batch_no' => (string) ($row['pk_batch_no'] ?? $row['batch_no']),
        'bottling_date' => (string) ($row['bottling_date'] ?? $row['batch_date']),
        'variant' => (string) ($row['pk_variant'] ?? $row['variant']),
        'size' => (string) $row['size'],
        'flag' => inventory_check_flag((string) $row['barcode_status'], $unitStatus),
    ];
}

/**
 * @return array<string, mixed>|null
 */
function inventory_check_resolve_dept(PDO $pdo, string $code): ?array
{
    $st = $pdo->prepare(
        'SELECT c.id AS barcode_id, c.status AS barcode_status,
                b.product_key, b.batch_no, b.batch_date,
                un.id AS unit_id, un.status AS unit_status
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN dept_pouch_units un ON un.barcode_id = c.id
         WHERE c.code = ? LIMIT 1'
    );
    $st->execute([$code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    $productKey = (string) $row['product_key'];

    return [
        'kind' => 'dept',
        'product_key' => $productKey,
        'barcode_id' => (int) $row['barcode_id'],
        'unit_id' => $row['unit_id'] !== null ? (int) $row['unit_id'] : null,
        'packaging_row_id' => null,
        'supplier_code' => (string) $row['batch_no'],
        'batch_no' => (string) $row['batch_no'],
        'bottling_date' => (string) $row['batch_date'],
        'variant' => dept_pouch_product_label($productKey),
        'size' => dept_pouch_unit_singular($productKey),
        'flag' => inventory_check_flag((string) $row['barcode_status'], $row['unit_status'] !== null ? (string) $row['unit_status'] : ''),
    ];
}

function inventory_check_flag(string $barcodeStatus, string $unitStatus): string
{
    if ($barcodeStatus === 'void') {
        return 'void';
    }

    return $unitStatus === 'in_stock' ? 'ok' : 'not_in_stock';
}

/**
 * @return array<string, mixed>
 */
function inventory_check_scan_get(PDO $pdo, int $scanId): array
{
    $st = $pdo->prepare(
        'SELECT s.*, u.name AS scanned_by_name FROM inventory_check_scans s
         LEFT JOIN users u ON u.id = s.scanned_by
         WHERE s.id = ? LIMIT 1'
    );
    $st->execute([$scanId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Scan not found.');
    }

    return inventory_check_serialize_scan($row);
}

/**
 * A user may undo their own mis-scan; managers may remove anyone's.
 */
function inventory_check_scan_remove(PDO $pdo, int $scanId, int $userId, bool $isManager): void
{
    $st = $pdo->prepare(
        'SELECT s.scanned_by, e.status FROM inventory_check_scans s
         INNER JOIN inventory_check_events e ON e.id = s.event_id
         WHERE s.id = ? LIMIT 1'
    );
    $st->execute([$scanId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('Scan not found.');
    }
    if ((string) $row['status'] !== 'open') {
        throw new RuntimeException('This inventory count is already closed.');
    }
    if (!$isManager && (int) $row['scanned_by'] !== $userId) {
        throw new RuntimeException('You can only remove your own scans.');
    }
    $pdo->prepare('DELETE FROM inventory_check_scans WHERE id = ?')->execute([$scanId]);
}

/**
 * Grouped counted rows: one row per batch + variant + size, like the packaging scan report.
 *
 * @return array<string, mixed>
 */
function inventory_check_summary(PDO $pdo, int $eventId, ?int $onlyUserId = null): array
{
    $event = inventory_check_event_get($pdo, $eventId);

    $where = 'WHERE s.event_id = ?';
    $params = [$eventId];
    if ($onlyUserId !== null) {
        $where .= ' AND s.scanned_by = ?';
        $params[] = $onlyUserId;
    }

    $st = $pdo->prepare(
        'SELECT s.kind, s.product_key, s.packaging_row_id, s.supplier_code, s.batch_no,
                s.bottling_date, s.variant, s.size,
                COALESCE(SUM(s.counted_qty), 0) AS qty,
                SUM(s.flag <> "ok") AS flagged_qty,
                COUNT(DISTINCT s.scanned_by) AS scanner_count,
                MAX(s.scanned_at) AS last_scanned_at,
                GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ", ") AS scanner_names
         FROM inventory_check_scans s
         LEFT JOIN users u ON u.id = s.scanned_by
         ' . $where . '
         GROUP BY s.kind, s.product_key, s.packaging_row_id, s.supplier_code, s.batch_no,
                  s.bottling_date, s.variant, s.size
         ORDER BY s.kind ASC, s.product_key ASC, s.batch_no ASC, s.size ASC'
    );
    $st->execute($params);

    $rows = [];
    $total = 0;
    $flagged = 0;
    $byProduct = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $kind = (string) $r['kind'];
        $productKey = (string) $r['product_key'];
        $productLabel = $kind === 'honey' ? 'Honey' : dept_pouch_product_label($productKey);
        $size = $r['size'] !== null ? (string) $r['size'] : null;
        $qty = (int) $r['qty'];
        $total += $qty;
        $flagged += (int) $r['flagged_qty'];

        $rows[] = [
            'kind' => $kind,
            'product_key' => $productKey,
            'product_label' => $productLabel,
            'packaging_row_id' => $r['packaging_row_id'] !== null ? (int) $r['packaging_row_id'] : null,
            'supplier_code' => $r['supplier_code'] !== null ? (string) $r['supplier_code'] : null,
            'batch_no' => $r['batch_no'] !== null ? (string) $r['batch_no'] : null,
            'bottling_date' => $r['bottling_date'] !== null ? (string) $r['bottling_date'] : null,
            'variant' => $r['variant'] !== null ? (string) $r['variant'] : null,
            'size' => $size,
            'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,
            'qty' => $qty,
            'flagged_qty' => (int) $r['flagged_qty'],
            'scanner_count' => (int) $r['scanner_count'],
            'scanner_names' => (string) ($r['scanner_names'] ?? ''),
            'last_scanned_at' => $r['last_scanned_at'] !== null ? (string) $r['last_scanned_at'] : null,
        ];

        if (!isset($byProduct[$productKey])) {
            $byProduct[$productKey] = [
                'kind' => $kind,
                'product_key' => $productKey,
                'product_label' => $productLabel,
                'qty' => 0,
            ];
        }
        $byProduct[$productKey]['qty'] += $qty;
    }

    return [
        'event' => $event,
        'rows' => $rows,
        'by_product' => array_values($byProduct),
        'total_units' => $total,
        'flagged_units' => $flagged,
        'scoped_to_user' => $onlyUserId !== null,
    ];
}

/**
 * Individual codes behind one summary row — who scanned what.
 *
 * @param array<string, mixed> $filters
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int}
 */
function inventory_check_scan_log(PDO $pdo, int $eventId, array $filters, int $page = 1, int $perPage = 50): array
{
    $page = max(1, $page);
    $perPage = max(1, min(200, $perPage));

    $where = ['s.event_id = ?'];
    $params = [$eventId];

    foreach (['kind', 'product_key', 'batch_no', 'size', 'variant'] as $col) {
        if (!isset($filters[$col]) || $filters[$col] === null || $filters[$col] === '') {
            continue;
        }
        $where[] = 's.' . $col . ' = ?';
        $params[] = (string) $filters[$col];
    }
    if (isset($filters['packaging_row_id']) && (int) $filters['packaging_row_id'] > 0) {
        $where[] = 's.packaging_row_id = ?';
        $params[] = (int) $filters['packaging_row_id'];
    }
    if (isset($filters['scanned_by']) && (int) $filters['scanned_by'] > 0) {
        $where[] = 's.scanned_by = ?';
        $params[] = (int) $filters['scanned_by'];
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $countSt = $pdo->prepare('SELECT COUNT(*) FROM inventory_check_scans s ' . $whereSql);
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $st = $pdo->prepare(
        'SELECT s.*, u.name AS scanned_by_name
         FROM inventory_check_scans s
         LEFT JOIN users u ON u.id = s.scanned_by
         ' . $whereSql . '
         ORDER BY s.id ASC
         LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage)
    );
    $st->execute($params);

    return [
        'rows' => array_map('inventory_check_serialize_scan', $st->fetchAll(PDO::FETCH_ASSOC)),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ];
}

/* --------------------------------------------------------------- compare */

/**
 * Counted vs system stock, limited to the product lines that were actually counted.
 *
 * @return array<string, mixed>
 */
function inventory_check_compare_run(PDO $pdo, int $eventId, int $userId): array
{
    return inventory_check_compare_run_for_events($pdo, [$eventId], $userId);
}

/**
 * @param list<int> $eventIds
 * @return array{lines: list<array<string, mixed>>, scope: list<string>, scope_labels: list<string>}
 */
function inventory_check_compare_build(PDO $pdo, array $eventIds): array
{
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if ($eventIds === []) {
        return ['lines' => [], 'scope' => [], 'scope_labels' => []];
    }

    $scope = [];
    $scopeLabels = [];
    $lines = [];

    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $countedSt = $pdo->prepare(
        'SELECT kind, product_key, packaging_row_id, supplier_code, batch_no, bottling_date, variant, size,
                COALESCE(SUM(counted_qty), 0) AS qty
         FROM inventory_check_scans
         WHERE event_id IN (' . $placeholders . ')
         GROUP BY kind, product_key, packaging_row_id, supplier_code, batch_no, bottling_date, variant, size'
    );
    $countedSt->execute($eventIds);
    $counted = $countedSt->fetchAll(PDO::FETCH_ASSOC);

    $honeyCounted = [];
    $honeyMeta = [];
    $honeyBatches = [];
    $deptCounted = [];
    $deptBatches = [];

    foreach ($counted as $row) {
        $kind = (string) $row['kind'];
        $productKey = (string) $row['product_key'];
        $qty = (int) $row['qty'];
        if ($qty <= 0) {
            continue;
        }

        if ($kind === 'honey') {
            if (!in_array('honey', $scope, true)) {
                $scope[] = 'honey';
                $scopeLabels[] = 'Honey';
            }
            $variantKey = strtolower(trim((string) ($row['variant'] ?? '')));
            $sizeKey = strtolower(trim((string) ($row['size'] ?? '')));
            $key = $variantKey . '|' . $sizeKey;
            $honeyCounted[$key] = ($honeyCounted[$key] ?? 0) + $qty;
            if (!isset($honeyMeta[$key])) {
                $honeyMeta[$key] = $row;
            }
            $bn = $row['batch_no'] !== null ? strtoupper(trim((string) $row['batch_no'])) : '';
            if ($bn !== '') {
                $honeyBatches[$key][$bn] = true;
            }
            continue;
        }

        if (!in_array($productKey, $scope, true)) {
            $scope[] = $productKey;
            $scopeLabels[] = dept_pouch_product_label($productKey);
        }
        $deptCounted[$productKey] = ($deptCounted[$productKey] ?? 0) + $qty;
        $deptBatches[$productKey][] = $row;
    }

    if (in_array('honey', $scope, true)) {
        $lines = array_merge(
            $lines,
            inventory_check_compare_honey_lines($pdo, $honeyCounted, $honeyMeta, $honeyBatches)
        );
    }

    foreach ($deptCounted as $productKey => $qty) {
        $systemQty = inventory_check_dept_system_qty($pdo, (string) $productKey);
        $batchNos = [];
        foreach ($deptBatches[$productKey] ?? [] as $b) {
            $bn = $b['batch_no'] !== null ? strtoupper(trim((string) $b['batch_no'])) : '';
            if ($bn !== '') {
                $batchNos[$bn] = true;
            }
        }
        $lines[] = [
            'kind' => 'dept',
            'product_key' => (string) $productKey,
            'product_label' => dept_pouch_product_label((string) $productKey),
            'packaging_row_id' => null,
            'supplier_code' => null,
            'batch_no' => $batchNos !== [] ? implode(', ', array_keys($batchNos)) : null,
            'bottling_date' => null,
            'variant' => dept_pouch_product_label((string) $productKey),
            'size' => dept_pouch_unit_singular((string) $productKey),
            'size_label' => dept_pouch_unit_singular((string) $productKey),
            'counted_qty' => $qty,
            'system_qty' => $systemQty,
            'variance' => round($qty - $systemQty, 2),
            'counted_batches' => array_map(
                static fn (array $b): array => [
                    'batch_no' => $b['batch_no'] !== null ? (string) $b['batch_no'] : null,
                    'bottling_date' => $b['bottling_date'] !== null ? (string) $b['bottling_date'] : null,
                    'qty' => (int) $b['qty'],
                ],
                $deptBatches[$productKey] ?? []
            ),
        ];
    }

    return ['lines' => $lines, 'scope' => $scope, 'scope_labels' => $scopeLabels];
}

/**
 * Honey variance for counted variant+size keys only (system qty = finished aggregate).
 *
 * @param array<string, int> $countedQty keyed "variant|size"
 * @param array<string, array<string, mixed>> $meta
 * @param array<string, array<string, true>> $honeyBatches
 * @return list<array<string, mixed>>
 */
function inventory_check_compare_honey_lines(
    PDO $pdo,
    array $countedQty,
    array $meta,
    array $honeyBatches = []
): array {
    $sizeCols = INVENTORY_CHECK_HONEY_SIZE_COLUMNS;
    $system = [];

    $st = $pdo->query(
        'SELECT variant, current_250ml, current_350ml, current_liter, current_gallon
         FROM honey_packaging_rows'
    );
    foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        $variantKey = strtolower(trim((string) ($row['variant'] ?? '')));
        foreach ($sizeCols as $size => $col) {
            $qty = (float) $row[$col];
            if ($qty <= 0) {
                continue;
            }
            $key = $variantKey . '|' . $size;
            $system[$key] = ($system[$key] ?? 0.0) + $qty;
        }
    }

    $keys = array_keys($countedQty);
    $lines = [];
    foreach ($keys as $key) {
        [$variantKey, $size] = array_pad(explode('|', (string) $key, 2), 2, '');
        $scan = $meta[$key] ?? null;
        $countedQtyValue = (int) ($countedQty[$key] ?? 0);
        if ($countedQtyValue <= 0) {
            continue;
        }
        $systemQty = (float) ($system[$key] ?? 0.0);

        $variantLabel = $variantKey !== ''
            ? $variantKey
            : ($scan !== null && $scan['variant'] !== null ? (string) $scan['variant'] : null);
        $sizeLabel = $size !== '' ? honey_bottle_size_label($size) : null;
        $batchLabel = isset($honeyBatches[$key]) && $honeyBatches[$key] !== []
            ? implode(', ', array_keys($honeyBatches[$key]))
            : ($scan !== null && !empty($scan['batch_no']) ? (string) $scan['batch_no'] : null);

        $lines[] = [
            'kind' => 'honey',
            'product_key' => 'honey',
            'product_label' => 'Honey',
            'packaging_row_id' => null,
            'supplier_code' => null,
            'batch_no' => $batchLabel,
            'bottling_date' => null,
            'variant' => $variantLabel,
            'size' => $size !== '' ? $size : null,
            'size_label' => $sizeLabel,
            'counted_qty' => $countedQtyValue,
            'system_qty' => round($systemQty, 2),
            'variance' => round($countedQtyValue - $systemQty, 2),
            'counted_batches' => [],
        ];
    }

    usort($lines, static function (array $a, array $b): int {
        return [(string) $a['variant'], (string) $a['size']] <=> [(string) $b['variant'], (string) $b['size']];
    });

    return $lines;
}

function inventory_check_dept_system_qty(PDO $pdo, string $productKey): float
{
    $row = dept_inventory_fetch_row($pdo, $productKey);

    return $row !== null ? (float) $row['current_qty'] : 0.0;
}

/**
 * @param array<string, mixed>|null $event
 * @param list<int>|null $batchEventIds
 * @return array<string, mixed>
 */
function inventory_check_compare_get(PDO $pdo, int $runId, ?array $event = null, ?array $batchEventIds = null): array
{
    $st = $pdo->prepare(
        'SELECT r.*, u.name AS run_by_name, ua.name AS stock_applied_by_name
         FROM inventory_check_compare_runs r
         LEFT JOIN users u ON u.id = r.run_by
         LEFT JOIN users ua ON ua.id = r.stock_applied_by
         WHERE r.id = ? LIMIT 1'
    );
    $st->execute([$runId]);
    $run = $st->fetch(PDO::FETCH_ASSOC);
    if ($run === false) {
        throw new RuntimeException('Comparison run not found.');
    }

    $linesSt = $pdo->prepare(
        'SELECT * FROM inventory_check_variance_lines WHERE run_id = ? ORDER BY kind ASC, batch_no ASC, size ASC'
    );
    $linesSt->execute([$runId]);

    $lines = [];
    foreach ($linesSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $kind = (string) $row['kind'];
        $size = $row['size'] !== null ? (string) $row['size'] : null;
        $lines[] = [
            'id' => (int) $row['id'],
            'kind' => $kind,
            'product_key' => (string) $row['product_key'],
            'product_label' => $kind === 'honey' ? 'Honey' : dept_pouch_product_label((string) $row['product_key']),
            'packaging_row_id' => $row['packaging_row_id'] !== null ? (int) $row['packaging_row_id'] : null,
            'supplier_code' => $row['supplier_code'] !== null ? (string) $row['supplier_code'] : null,
            'batch_no' => $row['batch_no'] !== null ? (string) $row['batch_no'] : null,
            'bottling_date' => $row['bottling_date'] !== null ? (string) $row['bottling_date'] : null,
            'variant' => $row['variant'] !== null ? (string) $row['variant'] : null,
            'size' => $size,
            'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,
            'counted_qty' => (int) $row['counted_qty'],
            'system_qty' => round((float) $row['system_qty'], 2),
            'variance' => round((float) $row['variance'], 2),
        ];
    }

    $eventId = (int) $run['event_id'];
    $docIds = $batchEventIds;
    if ($docIds === null && !empty($run['merged_event_ids'])) {
        $docIds = array_values(array_filter(array_map('intval', explode(',', (string) $run['merged_event_ids']))));
    }
    if ($docIds === null || $docIds === []) {
        $docIds = [$eventId];
    }

    return [
        'run' => inventory_check_serialize_compare_run($run),
        'lines' => $lines,
        'batches' => inventory_check_batch_docs_build($pdo, $docIds),
        'event' => $event ?? inventory_check_event_get($pdo, $eventId),
    ];
}

/**
 * Documentation rollup for confirm/history: one expandable group per batch number.
 * Not used for inventory matching.
 *
 * @param list<int> $eventIds
 * @return list<array<string, mixed>>
 */
function inventory_check_batch_docs_build(PDO $pdo, array $eventIds): array
{
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if ($eventIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $st = $pdo->prepare(
        'SELECT kind, product_key, batch_no, bottling_date, variant, size,
                COALESCE(SUM(counted_qty), 0) AS qty
         FROM inventory_check_scans
         WHERE event_id IN (' . $placeholders . ')
           AND batch_no IS NOT NULL AND TRIM(batch_no) <> ""
         GROUP BY kind, product_key, batch_no, bottling_date, variant, size
         ORDER BY batch_no ASC, bottling_date ASC, variant ASC, size ASC'
    );
    $st->execute($eventIds);

    /** @var array<string, array{batch_no:string,total_qty:int,lines:list<array<string,mixed>>}> $groups */
    $groups = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $batch = strtoupper(trim((string) $row['batch_no']));
        if ($batch === '') {
            continue;
        }
        $qty = (int) $row['qty'];
        if ($qty <= 0) {
            continue;
        }
        $kind = (string) $row['kind'];
        $productKey = (string) $row['product_key'];
        $variant = $row['variant'] !== null ? (string) $row['variant'] : null;
        $size = $row['size'] !== null ? (string) $row['size'] : null;
        $sizeLabel = $kind === 'honey' && $size !== null
            ? honey_bottle_size_label($size)
            : ($kind === 'dept' ? dept_pouch_unit_singular($productKey) : $size);
        $productLabel = $kind === 'honey' ? 'Honey' : dept_pouch_product_label($productKey);

        if (!isset($groups[$batch])) {
            $groups[$batch] = [
                'batch_no' => $batch,
                'total_qty' => 0,
                'lines' => [],
            ];
        }
        $groups[$batch]['total_qty'] += $qty;
        $groups[$batch]['lines'][] = [
            'bottling_date' => $row['bottling_date'] !== null ? (string) $row['bottling_date'] : null,
            'kind' => $kind,
            'product_key' => $productKey,
            'product_label' => $productLabel,
            'variant' => $variant,
            'size' => $size,
            'size_label' => $sizeLabel,
            'qty' => $qty,
        ];
    }

    return array_values($groups);
}

/**
 * Comparison runs for a session: owned primary event_id, or listed in merged_event_ids.
 *
 * @return list<array<string, mixed>>
 */
function inventory_check_compare_list(PDO $pdo, int $eventId): array
{
    $st = $pdo->prepare(
        'SELECT r.*, u.name AS run_by_name, ua.name AS stock_applied_by_name
         FROM inventory_check_compare_runs r
         LEFT JOIN users u ON u.id = r.run_by
         LEFT JOIN users ua ON ua.id = r.stock_applied_by
         WHERE r.event_id = ?
            OR FIND_IN_SET(?, REPLACE(COALESCE(r.merged_event_ids, ""), " ", "")) > 0
         ORDER BY r.id DESC'
    );
    $st->execute([$eventId, $eventId]);

    return array_map('inventory_check_serialize_compare_run', $st->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Parse merged_event_ids CSV into distinct positive ints (includes primary when passed).
 *
 * @return list<int>
 */
function inventory_check_parse_merged_event_ids(?string $csv, ?int $primaryEventId = null): array
{
    $ids = [];
    if ($primaryEventId !== null && $primaryEventId > 0) {
        $ids[] = $primaryEventId;
    }
    if ($csv !== null && trim($csv) !== '') {
        foreach (explode(',', $csv) as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    }

    return array_values(array_unique($ids));
}

/**
 * Session summaries for ids (order preserved).
 *
 * @param list<int> $eventIds
 * @return list<array{id:int,count_date:string,opened_by_name:string,status:string}>
 */
function inventory_check_event_summaries_by_ids(PDO $pdo, array $eventIds): array
{
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if ($eventIds === []) {
        return [];
    }
    $in = implode(',', array_fill(0, count($eventIds), '?'));
    $st = $pdo->prepare(
        "SELECT e.id, e.count_date, e.status, u.name AS opened_by_name
         FROM inventory_check_events e
         LEFT JOIN users u ON u.id = e.opened_by
         WHERE e.id IN ({$in})"
    );
    $st->execute($eventIds);
    $byId = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byId[(int) $row['id']] = [
            'id' => (int) $row['id'],
            'count_date' => (string) $row['count_date'],
            'opened_by_name' => (string) ($row['opened_by_name'] ?? ''),
            'status' => (string) $row['status'],
        ];
    }
    $out = [];
    foreach ($eventIds as $id) {
        if (isset($byId[$id])) {
            $out[] = $byId[$id];
        }
    }

    return $out;
}

/**
 * Whether the user may open a compare run (primary session or any merged member).
 *
 * @param array{id:int,role?:string} $user
 */
function inventory_check_user_can_access_compare_run(PDO $pdo, array $user, array $runRow): bool
{
    if (inventory_check_can_view_all($user) || inventory_check_can_manage($user)) {
        return true;
    }
    $ids = inventory_check_parse_merged_event_ids(
        isset($runRow['merged_event_ids']) ? (string) $runRow['merged_event_ids'] : null,
        (int) ($runRow['event_id'] ?? 0)
    );
    if ($ids === []) {
        return false;
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare(
        "SELECT 1 FROM inventory_check_events WHERE opened_by = ? AND id IN ({$in}) LIMIT 1"
    );
    $st->execute(array_merge([(int) ($user['id'] ?? 0)], $ids));

    return (bool) $st->fetchColumn();
}

/**
 * Stock-applied compare runs (single + merged confirms), newest first.
 *
 * @return array{rows:list<array<string,mixed>>,total:int,page:int,per_page:int}
 */
function inventory_check_applied_runs_list(PDO $pdo, int $page = 1, int $perPage = 25): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $total = (int) $pdo->query(
        'SELECT COUNT(*) FROM inventory_check_compare_runs WHERE stock_applied = 1'
    )->fetchColumn();

    $st = $pdo->prepare(
        'SELECT r.*, u.name AS run_by_name, ua.name AS stock_applied_by_name,
                e.count_date AS primary_count_date, eo.name AS primary_opened_by_name
         FROM inventory_check_compare_runs r
         LEFT JOIN users u ON u.id = r.run_by
         LEFT JOIN users ua ON ua.id = r.stock_applied_by
         LEFT JOIN inventory_check_events e ON e.id = r.event_id
         LEFT JOIN users eo ON eo.id = e.opened_by
         WHERE r.stock_applied = 1
         ORDER BY COALESCE(r.stock_applied_at, r.run_at) DESC, r.id DESC
         LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
    );
    $st->execute();

    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $run = inventory_check_serialize_compare_run($row);
        $sessionIds = inventory_check_parse_merged_event_ids(
            $run['merged_event_ids'] !== null ? (string) $run['merged_event_ids'] : null,
            (int) $run['event_id']
        );
        $sessions = inventory_check_event_summaries_by_ids($pdo, $sessionIds);
        $rows[] = array_merge($run, [
            'primary_count_date' => (string) ($row['primary_count_date'] ?? ''),
            'primary_opened_by_name' => (string) ($row['primary_opened_by_name'] ?? ''),
            'is_merged' => count($sessionIds) > 1,
            'sessions' => $sessions,
        ]);
    }

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ];
}

require_once __DIR__ . '/inventory_check_apply.php';
