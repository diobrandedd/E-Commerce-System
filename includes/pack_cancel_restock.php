<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/rts_inventory_sync.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/honey_pos.php';
require_once __DIR__ . '/sku_barcodes.php';

/** Packed (ship), shipped, or POS walk-in sold — all can return to on-hand. */
function pack_cancel_restock_is_restorable(string $unitStatus): bool
{
    return in_array($unitStatus, ['packed', 'shipped', 'sold'], true);
}

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
        'SELECT i.id, i.scanned_at, c.code, u.variant, u.size, u.pool, u.status AS unit_status, pk.batch_no
         FROM pack_cancel_restock_honey_items i
         INNER JOIN honey_bottle_barcodes c ON c.id = i.barcode_id
         INNER JOIN honey_bottle_units u ON u.id = i.unit_id
         LEFT JOIN honey_packaging_rows pk ON pk.id = u.packaging_row_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $honeySt->execute([$sessionId]);

    $deptPoolSelect = rts_db_has_column($pdo, 'dept_pouch_units', 'pool')
        ? ', un.pool'
        : ', "finished" AS pool';
    $deptSt = $pdo->prepare(
        'SELECT i.id, i.product_key, i.scanned_at, c.code, un.batch_no, un.status AS unit_status' . $deptPoolSelect . '
         FROM pack_cancel_restock_dept_items i
         INNER JOIN dept_pouch_barcodes c ON c.id = i.barcode_id
         INNER JOIN dept_pouch_units un ON un.id = i.unit_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $deptSt->execute([$sessionId]);

    $skuSt = $pdo->prepare(
        'SELECT i.id, i.sku_id, i.qty, i.pool, i.packaging_row_id, i.scanned_by, i.scanned_at,
                s.code, s.kind, s.variant, s.size, s.product_key,
                u.name AS scanned_by_name, pk.batch_no
         FROM pack_cancel_restock_sku_items i
         INNER JOIN product_sku_barcodes s ON s.id = i.sku_id
         LEFT JOIN users u ON u.id = i.scanned_by
         LEFT JOIN honey_packaging_rows pk ON pk.id = i.packaging_row_id
         WHERE i.session_id = ?
         ORDER BY i.id ASC'
    );
    $skuSt->execute([$sessionId]);

    $items = [];
    $grouped = [];
    foreach ($honeySt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $variant = (string) $row['variant'];
        $size = (string) $row['size'];
        $sizeLabel = honey_bottle_size_label($size);
        $batchNo = $row['batch_no'] !== null ? (string) $row['batch_no'] : null;
        $pool = (string) ($row['pool'] ?? 'packaging');
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => 'honey',
            'product_key' => 'honey',
            'product_label' => 'Honey',
            'code' => (string) $row['code'],
            'variant' => $variant,
            'size_label' => $sizeLabel,
            'batch_no' => $batchNo,
            'unit_status' => (string) ($row['unit_status'] ?? ''),
            'unit_pool' => $pool,
            'stock_label' => $pool === 'rts_sellable' ? 'RTS' : 'Packaging',
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $gKey = 'honey|' . $variant . '|' . $size . '|' . $pool;
        if (!isset($grouped[$gKey])) {
            $grouped[$gKey] = [
                'kind' => 'honey',
                'product_key' => 'honey',
                'product_label' => 'Honey',
                'variant' => $variant,
                'size_label' => $sizeLabel,
                'unit_pool' => $pool,
                'stock_label' => $pool === 'rts_sellable' ? 'RTS' : 'Packaging',
                'qty' => 0,
            ];
        }
        $grouped[$gKey]['qty']++;
    }
    foreach ($skuSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $kind = (string) $row['kind'];
        $pool = (string) $row['pool'];
        $qty = (int) $row['qty'];
        $isHoney = $kind === 'honey';
        $productKey = (string) ($row['product_key'] ?? '');
        $variant = $isHoney ? (string) $row['variant'] : '';
        $size = $isHoney ? (string) $row['size'] : '';
        $productLabel = $isHoney
            ? 'Honey'
            : dept_pouch_product_label($productKey);
        $sizeLabel = $isHoney ? honey_bottle_size_label($size) : dept_pouch_unit_singular($productKey);
        $stockLabel = $pool === 'rts_sellable'
            ? 'RTS'
            : ($isHoney ? 'Packaging' : 'Finished');
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => $kind,
            'lookup_kind' => 'sku',
            'product_key' => $isHoney ? 'honey' : $productKey,
            'product_label' => $productLabel,
            'code' => (string) $row['code'],
            'variant' => $isHoney ? $variant : $productLabel,
            'size_label' => $sizeLabel,
            'batch_no' => $row['batch_no'] !== null ? (string) $row['batch_no'] : null,
            'qty' => $qty,
            'unit_status' => null,
            'unit_pool' => $pool,
            'stock_label' => $stockLabel,
            'scanned_by' => (int) $row['scanned_by'],
            'scanned_by_name' => (string) ($row['scanned_by_name'] ?? ''),
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $gKey = 'sku|' . $kind . '|' . ($isHoney ? $variant . '|' . $size : $productKey) . '|' . $pool;
        if (!isset($grouped[$gKey])) {
            $grouped[$gKey] = [
                'kind' => $kind,
                'lookup_kind' => 'sku',
                'product_key' => $isHoney ? 'honey' : $productKey,
                'product_label' => $productLabel,
                'variant' => $isHoney ? $variant : $productLabel,
                'size_label' => $sizeLabel,
                'unit_pool' => $pool,
                'stock_label' => $stockLabel,
                'qty' => 0,
            ];
        }
        $grouped[$gKey]['qty'] += $qty;
    }
    foreach ($deptSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productKey = (string) $row['product_key'];
        $productLabel = dept_pouch_product_label($productKey);
        $pool = (string) ($row['pool'] ?? 'finished');
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => 'dept',
            'product_key' => $productKey,
            'product_label' => $productLabel,
            'code' => (string) $row['code'],
            'variant' => $productLabel,
            'size_label' => dept_pouch_unit_singular($productKey),
            'batch_no' => (string) $row['batch_no'],
            'unit_status' => (string) ($row['unit_status'] ?? ''),
            'unit_pool' => $pool,
            'stock_label' => $pool === 'rts_sellable' ? 'RTS' : 'Finished',
            'scanned_at' => (string) $row['scanned_at'],
        ];
        $gKey = 'dept|' . $productKey . '|' . $pool;
        if (!isset($grouped[$gKey])) {
            $grouped[$gKey] = [
                'kind' => 'dept',
                'product_key' => $productKey,
                'product_label' => $productLabel,
                'variant' => $productLabel,
                'size_label' => dept_pouch_unit_singular($productKey),
                'unit_pool' => $pool,
                'stock_label' => $pool === 'rts_sellable' ? 'RTS' : 'Finished',
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
        'item_count' => array_sum(array_map(static fn (array $item): int => (int) ($item['qty'] ?? 1), $items)),
        'entry_count' => count($items),
        'items' => $items,
        'grouped' => array_values($grouped),
    ];
}

/**
 * @return array{result: string, session: array<string,mixed>, lookup?: array<string,mixed>|null}
 */
function pack_cancel_restock_scan(
    PDO $pdo,
    int $userId,
    int $sessionId,
    string $code,
    ?int $qty = null,
    ?string $pool = null
): array
{
    $code = sku_barcode_normalize_code($code);
    $sess = pack_cancel_restock_get($pdo, $sessionId);
    if ($sess['status'] !== 'draft') {
        throw new RuntimeException('This restock session is already closed.');
    }

    $sku = sku_barcode_fetch_by_code($pdo, $code);
    if ($sku !== null) {
        $lookup = sku_barcode_serialize($sku);
        $lookup['lookup_kind'] = 'sku';
        $lookup['requires_qty'] = true;
        $lookup['allowed_pools'] = (string) $sku['kind'] === 'honey'
            ? ['packaging', 'rts_sellable']
            : ['finished', 'rts_sellable'];
        if ($qty === null || $pool === null || trim($pool) === '') {
            return [
                'result' => 'sku_requires_details',
                'session' => $sess,
                'lookup' => $lookup,
            ];
        }

        return pack_cancel_restock_scan_sku($pdo, $userId, $sessionId, $sku, $qty, $pool);
    }

    $code = honey_bottle_normalize_code($code);
    $honey = honey_bottle_fetch_code_row($pdo, $code);
    if ($honey !== null) {
        return pack_cancel_restock_scan_honey($pdo, $sessionId, $honey);
    }

    return pack_cancel_restock_scan_dept($pdo, $sessionId, $code);
}

/**
 * Queue a quantity-based permanent SKU return. This does not create fake unit
 * rows; inventory is credited only when the draft session is confirmed.
 *
 * @param array<string,mixed> $sku
 * @return array{result: string, session: array<string,mixed>, lookup: array<string,mixed>}
 */
function pack_cancel_restock_scan_sku(
    PDO $pdo,
    int $userId,
    int $sessionId,
    array $sku,
    int $qty,
    string $pool
): array {
    if ($userId < 1) {
        throw new RuntimeException('A valid scanner is required.');
    }
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Return quantity must be between 1 and 5000.');
    }
    $kind = (string) ($sku['kind'] ?? '');
    $pool = strtolower(trim($pool));
    $allowedPools = $kind === 'honey'
        ? ['packaging', 'rts_sellable']
        : ['finished', 'rts_sellable'];
    if (!in_array($pool, $allowedPools, true)) {
        throw new RuntimeException(
            $kind === 'honey'
                ? 'Honey returns may go to Packaging or RTS.'
                : 'Department returns may go to Finished or RTS.'
        );
    }

    $lookup = sku_barcode_serialize($sku);
    $lookup['lookup_kind'] = 'sku';
    $lookup['requires_qty'] = true;
    $lookup['allowed_pools'] = $allowedPools;
    $lookup['selected_qty'] = $qty;
    $lookup['selected_pool'] = $pool;

    $pdo->prepare(
        'INSERT INTO pack_cancel_restock_sku_items
            (session_id, sku_id, qty, pool, scanned_by)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$sessionId, (int) $sku['id'], $qty, $pool, $userId]);

    return [
        'result' => 'ok',
        'session' => pack_cancel_restock_get($pdo, $sessionId),
        'lookup' => $lookup,
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array{result: string, session: array<string,mixed>, lookup?: array<string,mixed>|null}
 */
function pack_cancel_restock_scan_honey(PDO $pdo, int $sessionId, array $row): array
{
    $lookup = honey_bottle_serialize_lookup($row);
    $lookup['kind'] = 'honey';
    $pool = (string) ($lookup['unit_pool'] ?? 'packaging');
    $lookup['stock_label'] = $pool === 'rts_sellable' ? 'RTS' : 'Packaging';
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
    if (!pack_cancel_restock_is_restorable($unitStatus)) {
        return [
            'result' => $unitStatus === 'in_stock' ? 'already_in_stock' : 'not_restorable',
            'session' => pack_cancel_restock_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }
    if ($row['unit_id'] === null) {
        return ['result' => 'not_restorable', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
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
    $poolCol = rts_db_has_column($pdo, 'dept_pouch_units', 'pool')
        ? ', un.pool AS unit_pool'
        : ', NULL AS unit_pool';
    $st = $pdo->prepare(
        'SELECT c.id AS barcode_id, c.code, c.status AS barcode_status,
                b.product_key, b.batch_no,
                un.id AS unit_id, un.status AS unit_status' . $poolCol . '
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
    $pool = $row['unit_pool'] !== null ? (string) $row['unit_pool'] : 'finished';
    $lookup = [
        'kind' => 'dept',
        'product_key' => $productKey,
        'product_label' => dept_pouch_product_label($productKey),
        'code' => (string) $row['code'],
        'batch_no' => (string) $row['batch_no'],
        'unit_id' => $row['unit_id'] !== null ? (int) $row['unit_id'] : null,
        'unit_status' => $row['unit_status'] !== null ? (string) $row['unit_status'] : null,
        'unit_pool' => $pool,
        'stock_label' => $pool === 'rts_sellable' ? 'RTS' : 'Finished',
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
    if (!pack_cancel_restock_is_restorable($unitStatus)) {
        return [
            'result' => $unitStatus === 'in_stock' ? 'already_in_stock' : 'not_restorable',
            'session' => pack_cancel_restock_get($pdo, $sessionId),
            'lookup' => $lookup,
        ];
    }
    if ($row['unit_id'] === null) {
        return ['result' => 'not_restorable', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];
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
            'SELECT i.unit_id, u.status, u.packaging_row_id, u.size, u.variant, u.pool,
                    u.consumed_ref_type, u.consumed_ref_id
             FROM pack_cancel_restock_honey_items i
             INNER JOIN honey_bottle_units u ON u.id = i.unit_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $honeySt->execute([$sessionId]);
        $honeyItems = $honeySt->fetchAll(PDO::FETCH_ASSOC);

        $deptPoolSelect = rts_db_has_column($pdo, 'dept_pouch_units', 'pool')
            ? ', un.pool'
            : ', "finished" AS pool';
        $deptSt = $pdo->prepare(
            'SELECT i.unit_id, i.product_key, un.status, un.consumed_ref_type, un.consumed_ref_id' . $deptPoolSelect . '
             FROM pack_cancel_restock_dept_items i
             INNER JOIN dept_pouch_units un ON un.id = i.unit_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $deptSt->execute([$sessionId]);
        $deptItems = $deptSt->fetchAll(PDO::FETCH_ASSOC);

        $skuSt = $pdo->prepare(
            'SELECT i.id, i.sku_id, i.qty, i.pool, i.packaging_row_id,
                    s.kind, s.variant, s.size, s.product_key
             FROM pack_cancel_restock_sku_items i
             INNER JOIN product_sku_barcodes s ON s.id = i.sku_id
             WHERE i.session_id = ?
             FOR UPDATE'
        );
        $skuSt->execute([$sessionId]);
        $skuItems = $skuSt->fetchAll(PDO::FETCH_ASSOC);

        if ($honeyItems === [] && $deptItems === [] && $skuItems === []) {
            throw new RuntimeException('Scan at least one barcode before confirming.');
        }

        $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
        foreach ($honeyItems as $item) {
            $status = (string) $item['status'];
            if (!pack_cancel_restock_is_restorable($status)) {
                throw new RuntimeException('A honey unit is no longer restorable. Discard and rescan.');
            }
            $unitId = (int) $item['unit_id'];
            $pool = (string) ($item['pool'] ?? 'packaging');
            $size = (string) $item['size'];
            $variant = honey_normalize_variant((string) $item['variant']);
            $saleId = 0;
            if ($status === 'sold') {
                $refType = (string) ($item['consumed_ref_type'] ?? '');
                $refId = $item['consumed_ref_id'] !== null ? (int) $item['consumed_ref_id'] : null;
                $saleId = pos_sale_id_for_honey_unit(
                    $pdo,
                    $unitId,
                    $refType === 'pos_sale' ? $refId : null
                );
            }
            $upd = $pdo->prepare(
                'UPDATE honey_bottle_units
                 SET status = "in_stock", consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ? AND status IN ("packed", "shipped", "sold")'
            );
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                throw new RuntimeException('Could not restore a honey unit.');
            }
            $packId = (int) ($item['packaging_row_id'] ?? 0);
            if ($pool === 'rts_sellable') {
                rts_inventory_credit_sellable_honey($pdo, $variant, $size, 1.0);
            } elseif ($packId > 0) {
                honey_bottle_adjust_aggregate($pdo, $packId, $size, 1.0);
            }
            // Reverse POS walk-in sales qty + receipt money so Sales totals stay honest.
            if ($status === 'sold') {
                if ($saleId > 0) {
                    pos_sale_reverse_one_unit($pdo, $saleId, 'honey', [
                        'variant' => $variant,
                        'size' => $size,
                    ]);
                }
                honey_bottle_write_sale_log($pdo, [
                    'source' => $pool === 'rts_sellable' ? 'rts' : 'packaging',
                    'packaging_row_id' => $packId > 0 ? $packId : null,
                    'variant' => $variant,
                    'size' => $size,
                    'qty' => -1,
                    'sale_type' => 'walk_in',
                    'sold_by' => $userId,
                    'pos_sale_id' => $saleId > 0 ? $saleId : null,
                    'bottle_unit_id' => $hasUnitCol ? $unitId : null,
                ]);
            }
        }

        foreach ($deptItems as $item) {
            $status = (string) $item['status'];
            if (!pack_cancel_restock_is_restorable($status)) {
                throw new RuntimeException('A pouch/jar is no longer restorable. Discard and rescan.');
            }
            $unitId = (int) $item['unit_id'];
            $productKey = (string) $item['product_key'];
            $pool = (string) ($item['pool'] ?? 'finished');
            $saleId = 0;
            if ($status === 'sold'
                && (string) ($item['consumed_ref_type'] ?? '') === 'pos_sale'
                && $item['consumed_ref_id'] !== null) {
                $saleId = (int) $item['consumed_ref_id'];
            }
            $upd = $pdo->prepare(
                'UPDATE dept_pouch_units
                 SET status = "in_stock", consumed_at = NULL, consumed_ref_type = NULL, consumed_ref_id = NULL
                 WHERE id = ? AND status IN ("packed", "shipped", "sold")'
            );
            $upd->execute([$unitId]);
            if ($upd->rowCount() < 1) {
                throw new RuntimeException('Could not restore a pouch/jar unit.');
            }
            if ($pool === 'rts_sellable') {
                rts_inventory_credit_sellable_other($pdo, dept_pouch_product_label($productKey), 1.0);
            } else {
                $fin = dept_inventory_ensure_row($pdo, $userId, $productKey);
                $pdo->prepare(
                    'UPDATE dept_inventory_rows SET current_qty = current_qty + 1 WHERE id = ?'
                )->execute([(int) $fin['id']]);
            }
            if ($status === 'sold' && $saleId > 0) {
                pos_sale_reverse_one_unit($pdo, $saleId, 'dept', [
                    'product_key' => $productKey,
                ]);
            }
        }

        foreach ($skuItems as $item) {
            $qty = (int) $item['qty'];
            if ($qty < 1 || $qty > 5000) {
                throw new RuntimeException('A SKU return quantity is invalid. Discard and rescan.');
            }
            $kind = (string) $item['kind'];
            $pool = (string) $item['pool'];
            $packagingRowId = $item['packaging_row_id'] !== null ? (int) $item['packaging_row_id'] : null;
            if ($kind === 'honey') {
                $variant = honey_normalize_variant((string) $item['variant']);
                $size = (string) $item['size'];
                if (!in_array($size, HONEY_SIZES, true)) {
                    throw new RuntimeException('A SKU honey size is invalid. Discard and rescan.');
                }
                if ($pool === 'packaging' && ($packagingRowId === null || $packagingRowId < 1)) {
                    $packagingRowId = pack_cancel_restock_system_packaging_row($pdo, $userId, $variant);
                    $pdo->prepare(
                        'UPDATE pack_cancel_restock_sku_items SET packaging_row_id = ? WHERE id = ?'
                    )->execute([$packagingRowId, (int) $item['id']]);
                }
                sku_barcode_honey_credit(
                    $pdo,
                    $variant,
                    $size,
                    $qty,
                    $pool,
                    $packagingRowId
                );
            } elseif ($kind === 'dept') {
                $productKey = strtolower(trim((string) $item['product_key']));
                if (!in_array($productKey, DEPT_PRODUCT_KEYS, true)) {
                    throw new RuntimeException('A SKU department product is invalid. Discard and rescan.');
                }
                sku_barcode_dept_credit($pdo, $userId, $productKey, $qty, $pool);
            } else {
                throw new RuntimeException('A SKU return type is invalid. Discard and rescan.');
            }
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

/**
 * Honey packaging requires a production row for legacy foreign-key reasons.
 * This reserved row is deliberately unassigned to a production batch.
 */
function pack_cancel_restock_system_packaging_row(PDO $pdo, int $userId, string $variant): int
{
    $variant = honey_normalize_variant($variant);
    $batchNo = 'CANCELLED-RESTOCK-' . strtoupper($variant);
    $st = $pdo->prepare(
        'SELECT id FROM honey_packaging_rows WHERE batch_no = ? LIMIT 1 FOR UPDATE'
    );
    $st->execute([$batchNo]);
    $existing = $st->fetchColumn();
    if ($existing !== false) {
        return (int) $existing;
    }

    $pdo->prepare(
        'INSERT INTO honey_packaging_rows (
            production_row_id, supplier_code, batch_no, bottling_date, variant, created_by
         ) VALUES (NULL, "CANCELLED", ?, CURDATE(), ?, ?)'
    )->execute([$batchNo, $variant, $userId]);

    return (int) $pdo->lastInsertId();
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
        $joins = ' INNER JOIN (
            SELECT DISTINCT session_id FROM pack_cancel_restock_honey_items
            UNION
            SELECT DISTINCT i.session_id
            FROM pack_cancel_restock_sku_items i
            INNER JOIN product_sku_barcodes sku ON sku.id = i.sku_id
            WHERE sku.kind = "honey"
        ) ph ON ph.session_id = s.id';
    } elseif ($productKey !== '') {
        if (!dept_pouch_supports($productKey)) {
            throw new RuntimeException('Unknown product filter.');
        }
        $joins = ' INNER JOIN (
            SELECT DISTINCT session_id FROM pack_cancel_restock_dept_items WHERE product_key = ?
            UNION
            SELECT DISTINCT i.session_id
            FROM pack_cancel_restock_sku_items i
            INNER JOIN product_sku_barcodes sku ON sku.id = i.sku_id
            WHERE sku.kind = "dept" AND sku.product_key = ?
        ) pd ON pd.session_id = s.id';
        $params[] = $productKey;
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
                  + (SELECT COALESCE(SUM(sk.qty), 0) FROM pack_cancel_restock_sku_items sk WHERE sk.session_id = s.id)
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
