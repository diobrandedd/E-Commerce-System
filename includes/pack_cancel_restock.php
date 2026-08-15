<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/dept_inventory.php';

/**
 * @return array<string, mixed>
 */
function pack_cancel_restock_start(PDO $pdo, int $userId): array
{
    $st = $pdo->prepare(
        'SELECT id FROM pack_cancel_restock_sessions
         WHERE started_by = ? AND status = "draft" ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$userId]);
    $existing = $st->fetchColumn();
    if ($existing) {
        return pack_cancel_restock_get($pdo, (int) $existing);
    }
    $pdo->prepare('INSERT INTO pack_cancel_restock_sessions (started_by, status) VALUES (?, "draft")')
        ->execute([$userId]);

    return pack_cancel_restock_get($pdo, (int) $pdo->lastInsertId());
}

/**
 * @return array<string, mixed>
 */
function pack_cancel_restock_get(PDO $pdo, int $sessionId): array
{
    $st = $pdo->prepare(
        'SELECT s.*,
                u_start.name AS started_by_name,
                u_conf.name AS confirmed_by_name
         FROM pack_cancel_restock_sessions s
         LEFT JOIN users u_start ON u_start.id = s.started_by
         LEFT JOIN users u_conf ON u_conf.id = s.confirmed_by
         WHERE s.id = ? LIMIT 1'
    );
    $st->execute([$sessionId]);
    $sess = $st->fetch(PDO::FETCH_ASSOC);
    if ($sess === false) {
        throw new RuntimeException('Restock session not found.');
    }

    $honeySt = $pdo->prepare(
        'SELECT i.id, i.scanned_at, c.code, u.variant, u.size, pk.batch_no
         FROM pack_cancel_restock_honey_items i
         INNER JOIN honey_bottle_barcodes c ON c.id = i.barcode_id
         INNER JOIN honey_bottle_units u ON u.id = i.unit_id
         LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $honeySt->execute([$sessionId]);

    $deptSt = $pdo->prepare(
        'SELECT i.id, i.product_key, i.scanned_at, c.code, un.batch_no
         FROM pack_cancel_restock_dept_items i
         INNER JOIN dept_pouch_barcodes c ON c.id = i.barcode_id
         INNER JOIN dept_pouch_units un ON un.id = i.unit_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $deptSt->execute([$sessionId]);

    $items = [];
    $grouped = [];
    foreach ($honeySt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $variant = (string) $row['variant'];
        $size = (string) $row['size'];
        $sizeLabel = honey_bottle_size_label($size);
        $batchNo = $row['batch_no'] !== null ? (string) $row['batch_no'] : null;
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => 'honey',
            'product_key' => 'honey',
            'product_label' => 'Honey',
            'code' => (string) $row['code'],
            'variant' => $variant,
            'size_label' => $sizeLabel,
            'batch_no' => $batchNo,
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $gKey = 'honey|' . $variant . '|' . $size;
        if (!isset($grouped[$gKey])) {
            $grouped[$gKey] = [
                'kind' => 'honey',
                'product_key' => 'honey',
                'product_label' => 'Honey',
                'variant' => $variant,
                'size_label' => $sizeLabel,
                'qty' => 0,
            ];
        }
        $grouped[$gKey]['qty']++;
    }
    foreach ($deptSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productKey = (string) $row['product_key'];
        $productLabel = dept_pouch_product_label($productKey);
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => 'dept',
            'product_key' => $productKey,
            'product_label' => $productLabel,
            'code' => (string) $row['code'],
            'variant' => $productLabel,
            'size_label' => dept_pouch_unit_singular($productKey),
            'batch_no' => (string) $row['batch_no'],
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $gKey = 'dept|' . $productKey;
        if (!isset($grouped[$gKey])) {
            $grouped[$gKey] = [
                'kind' => 'dept',
                'product_key' => $productKey,
                'product_label' => $productLabel,
                'variant' => $productLabel,
                'size_label' => dept_pouch_unit_singular($productKey),
                'qty' => 0,
            ];
        }
        $grouped[$gKey]['qty']++;
    }

    usort($items, static function (array $a, array $b): int {
        return strcmp((string) $a['scanned_at'], (string) $b['scanned_at']);
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
    ];
}

/**
 * @return array{result: string, session: array<string,mixed>, lookup?: array<string,mixed>|null}
 */
function pack_cancel_restock_scan(PDO $pdo, int $sessionId, string $code): array
{
    $code = honey_bottle_normalize_code($code);
    $sess = pack_cancel_restock_get($pdo, $sessionId);
    if ($sess['status'] !== 'draft') {
        throw new RuntimeException('This restock session is already closed.');
    }

    $honey = honey_bottle_fetch_code_row($pdo, $code);
    if ($honey !== null) {
        return pack_cancel_restock_scan_honey($pdo, $sessionId, $honey);
    }

    return pack_cancel_restock_scan_dept($pdo, $sessionId, $code);
}

/**
 * @param array<string, mixed> $row
 * @return array{result: string, session: array<string,mixed>, lookup?: array<string,mixed>|null}
 */
function pack_cancel_restock_scan_honey(PDO $pdo, int $sessionId, array $row): array
{
    $lookup = honey_bottle_serialize_lookup($row);
    $lookup['kind'] = 'honey';
    if ((string) $row['status'] === 'void') {
        return ['result' => 'void', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
    }
    $dup = $pdo->prepare(
        'SELECT 1 FROM pack_cancel_restock_honey_items WHERE session_id = ? AND barcode_id = ? LIMIT 1'
    );
    $dup->execute([$sessionId, (int) $row['id']]);
    if ($dup->fetchColumn()) {
        return ['result' => 'duplicate_session', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
    }
    $unitStatus = $row['unit_status'] !== null ? (string) $row['unit_status'] : '';
    if ($unitStatus !== 'packed' && $unitStatus !== 'shipped') {
        return [
            'result' => $unitStatus === 'in_stock' ? 'already_in_stock' : 'not_packed',
            'session' => pack_cancel_restock_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }
    if ($row['unit_id'] === null) {
        return ['result' => 'not_packed', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
    }
    $pdo->prepare(
        'INSERT INTO pack_cancel_restock_honey_items (session_id, barcode_id, unit_id) VALUES (?, ?, ?)'
    )->execute([$sessionId, (int) $row['id'], (int) $row['unit_id']]);

    return [
        'result' => 'ok',
        'session' => pack_cancel_restock_get($pdo, $sessionId),
        'lookup' => $lookup,
    ];
}

/**
 * @return array{result: string, session: array<string,mixed>, lookup?: array<string,mixed>|null}
 */
function pack_cancel_restock_scan_dept(PDO $pdo, int $sessionId, string $code): array
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
        return ['result' => 'unknown', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => null];
    }
    $productKey = (string) $row['product_key'];
    $lookup = [
        'kind' => 'dept',
        'product_key' => $productKey,
        'product_label' => dept_pouch_product_label($productKey),
        'code' => (string) $row['code'],
        'batch_no' => (string) $row['batch_no'],
        'unit_id' => $row['unit_id'] !== null ? (int) $row['unit_id'] : null,
        'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
        'variant' => dept_pouch_product_label($productKey),
        'size_label' => dept_pouch_unit_singular($productKey),
    ];
    if ((string) $row['barcode_status'] === 'void') {
        return ['result' => 'void', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
    }
    $dup = $pdo->prepare(
        'SELECT 1 FROM pack_cancel_restock_dept_items WHERE session_id = ? AND barcode_id = ? LIMIT 1'
    );
    $dup->execute([$sessionId, (int) $row['barcode_id']]);
    if ($dup->fetchColumn()) {
        return ['result' => 'duplicate_session', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
    }
    $unitStatus = $row['unit_status'] !== null ? (string) $row['unit_status'] : '';
    if ($unitStatus !== 'packed' && $unitStatus !== 'shipped') {
        return [
            'result' => $unitStatus === 'in_stock' ? 'already_in_stock' : 'not_packed',
            'session' => pack_cancel_restock_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }
    if ($row['unit_id'] === null) {
        return ['result' => 'not_packed', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
    }
    $pdo->prepare(
        'INSERT INTO pack_cancel_restock_dept_items (session_id, product_key, barcode_id, unit_id)
         VALUES (?, ?, ?, ?)'
    )->execute([$sessionId, $productKey, (int) $row['barcode_id'], (int) $row['unit_id']]);

    return [
        'result' => 'ok',
        'session' => pack_cancel_restock_get($pdo, $sessionId),
        'lookup' => $lookup,
    ];
}

/**
 * @return array<string, mixed>
 */
function pack_cancel_restock_confirm(PDO $pdo, int $userId, int $sessionId): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $lock = $pdo->prepare('SELECT * FROM pack_cancel_restock_sessions WHERE id = ? FOR UPDATE');
        $lock->execute([$sessionId]);
        $sess = $lock->fetch(PDO::FETCH_ASSOC);
        if ($sess === false) {
            throw new RuntimeException('Restock session not found.');
        }
        if ((string) $sess['status'] !== 'draft') {
            throw new RuntimeException('This restock session is already closed.');
        }

        $honeySt = $pdo->prepare(
            'SELECT i.unit_id, u.status, u.packaging_row_id, u.size, u.pool
             FROM pack_cancel_restock_honey_items i
             INNER JOIN honey_bottle_units u ON u.id = i.unit_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $honeySt->execute([$sessionId]);
        $honeyItems = $honeySt->fetchAll(PDO::FETCH_ASSOC);

        $deptSt = $pdo->prepare(
            'SELECT i.unit_id, i.product_key, un.status
             FROM pack_cancel_restock_dept_items i
             INNER JOIN dept_pouch_units un ON un.id = i.unit_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $deptSt->execute([$sessionId]);
        $deptItems = $deptSt->fetchAll(PDO::FETCH_ASSOC);

        if ($honeyItems === [] && $deptItems === []) {
            throw new RuntimeException('Scan at least one packed barcode before confirming.');
        }

        foreach ($honeyItems as $item) {
            $status = (string) $item['status'];
            if ($status !== 'packed' && $status !== 'shipped') {
                throw new RuntimeException('A honey unit is no longer packed. Discard and rescan.');
            }
            $unitId = (int) $item['unit_id'];
            $upd = $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET status = "in_stock", consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ? AND status IN ("packed", "shipped")'
            );
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                throw new RuntimeException('Could not restore a honey unit.');
            }
            $packId = (int) ($item['packaging_row_id'] ?? 0);
            if ($packId > 0 && (string) ($item['pool'] ?? 'packaging') === 'packaging') {
                honey_bottle_adjust_aggregate($pdo, $packId, (string) $item['size'], 1.0);
            }
        }

        foreach ($deptItems as $item) {
            $status = (string) $item['status'];
            if ($status !== 'packed' && $status !== 'shipped') {
                throw new RuntimeException('A pouch/jar is no longer packed. Discard and rescan.');
            }
            $unitId = (int) $item['unit_id'];
            $productKey = (string) $item['product_key'];
            $upd = $pdo->prepare(
                'UPDATE dept_pouch_units
                 SET status = "in_stock", consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ? AND status IN ("packed", "shipped")'
            );
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                throw new RuntimeException('Could not restore a pouch/jar unit.');
            }
            $fin = dept_inventory_ensure_row($pdo, $userId, $productKey);
            $pdo->prepare(
                'UPDATE dept_inventory_rows SET current_qty = current_qty + 1 WHERE id = ?'
            )->execute([(int) $fin['id']]);
        }

        $pdo->prepare(
            'UPDATE pack_cancel_restock_sessions
             SET status = "confirmed", confirmed_at = NOW(), confirmed_by = ?
             WHERE id = ?'
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

    return pack_cancel_restock_get($pdo, $sessionId);
}

function pack_cancel_restock_discard(PDO $pdo, int $sessionId): void
{
    $pdo->prepare(
        'UPDATE pack_cancel_restock_sessions SET status = "discarded" WHERE id = ? AND status = "draft"'
    )->execute([$sessionId]);
}

/**
 * @return array{rows: list<array<string,mixed>>, total: int, page: int, per_page: int, from: string, to: string}
 */
function pack_cancel_restock_list(
    PDO $pdo,
    ?string $from,
    ?string $to,
    ?string $productKey,
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
    if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
        throw new RuntimeException('Invalid date range.');
    }
    $from = date('Y-m-d', $fromTs);
    $to = date('Y-m-d', $toTs);
    $fromDt = $from . ' 00:00:00';
    $toDt = $to . ' 23:59:59';

    $productKey = $productKey !== null ? strtolower(trim($productKey)) : '';
    if ($productKey === 'all') {
        $productKey = '';
    }
    $joins = '';
    $params = [];
    if ($productKey === 'honey') {
        $joins = ' INNER JOIN (SELECT DISTINCT session_id FROM pack_cancel_restock_honey_items) ph ON ph.session_id = s.id';
    } elseif ($productKey !== '') {
        if (!dept_pouch_supports($productKey)) {
            throw new RuntimeException('Unknown product filter.');
        }
        $joins = ' INNER JOIN (
            SELECT DISTINCT session_id FROM pack_cancel_restock_dept_items WHERE product_key = ?
        ) pd ON pd.session_id = s.id';
        $params[] = $productKey;
    }
    $params[] = $fromDt;
    $params[] = $toDt;

    $countSt = $pdo->prepare(
        'SELECT COUNT(*) FROM pack_cancel_restock_sessions s' . $joins
        . ' WHERE s.status = "confirmed" AND s.confirmed_at BETWEEN ? AND ?'
    );
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $listSt = $pdo->prepare(
        'SELECT s.id, s.confirmed_at, s.created_at, s.started_by, s.confirmed_by,
                u_start.name AS started_by_name, u_conf.name AS confirmed_by_name,
                (
                  (SELECT COUNT(*) FROM pack_cancel_restock_honey_items h WHERE h.session_id = s.id)
                  + (SELECT COUNT(*) FROM pack_cancel_restock_dept_items d WHERE d.session_id = s.id)
                ) AS item_count
         FROM pack_cancel_restock_sessions s' . $joins
        . ' LEFT JOIN users u_start ON u_start.id = s.started_by
           LEFT JOIN users u_conf ON u_conf.id = s.confirmed_by
           WHERE s.status = "confirmed" AND s.confirmed_at BETWEEN ? AND ?
           ORDER BY s.confirmed_at DESC, s.id DESC
           LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
    );
    $listSt->execute($params);
    $rows = [];
    foreach ($listSt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $detail = pack_cancel_restock_get($pdo, (int) $r['id']);
        $rows[] = [
            'id' => (int) $r['id'],
            'confirmed_at' => (string) $r['confirmed_at'],
            'created_at' => (string) $r['created_at'],
            'started_by_name' => (string) ($r['started_by_name'] ?? ''),
            'confirmed_by_name' => (string) ($r['confirmed_by_name'] ?? ''),
            'item_count' => (int) $r['item_count'],
            'grouped' => $detail['grouped'],
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
