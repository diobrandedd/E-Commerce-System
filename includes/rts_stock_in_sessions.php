<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_bottle_barcodes.php';
require_once __DIR__ . '/dept_pouch_barcodes.php';
require_once __DIR__ . '/sku_barcodes.php';
require_once __DIR__ . '/rts_inventory_sync.php';

function rts_stock_in_request_key(string $value): string
{
    $value = trim($value);
    if ($value === '' || strlen($value) > 64 || preg_match('/^[A-Za-z0-9._:-]+$/', $value) !== 1) {
        throw new RuntimeException('A valid scan request key is required.');
    }

    return $value;
}

/** @return array<string,mixed> */
function rts_stock_in_session_row(PDO $pdo, int $sessionId, int $userId, bool $lock = false): array
{
    $sql = 'SELECT s.*, starter.name AS started_by_name, confirmer.name AS confirmed_by_name
            FROM rts_stock_in_sessions s
            LEFT JOIN users starter ON starter.id = s.started_by
            LEFT JOIN users confirmer ON confirmer.id = s.confirmed_by
            WHERE s.id = ? LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
    $st = $pdo->prepare($sql);
    $st->execute([$sessionId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        throw new RuntimeException('RTS stock-in session not found.');
    }
    if ((int) $row['started_by'] !== $userId) {
        throw new RuntimeException('This RTS stock-in session belongs to another user.');
    }

    return $row;
}

/** @return array<string,mixed> */
function rts_stock_in_session_get(PDO $pdo, int $sessionId, int $userId): array
{
    $session = rts_stock_in_session_row($pdo, $sessionId, $userId);
    $itemsSt = $pdo->prepare(
        'SELECT id, kind, lookup_kind, code, sku_id, honey_barcode_id, dept_barcode_id,
                variant, size, product_key, product_label, qty, created_at, updated_at
         FROM rts_stock_in_session_items
         WHERE session_id = ?
         ORDER BY id ASC'
    );
    $itemsSt->execute([$sessionId]);
    $items = [];
    $totalQty = 0;
    foreach ($itemsSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $qty = max(1, (int) $row['qty']);
        $totalQty += $qty;
        $items[] = [
            'id' => (int) $row['id'],
            'kind' => (string) $row['kind'],
            'lookup_kind' => (string) $row['lookup_kind'],
            'code' => (string) $row['code'],
            'sku_id' => $row['sku_id'] !== null ? (int) $row['sku_id'] : null,
            'variant' => $row['variant'] !== null ? (string) $row['variant'] : null,
            'size' => $row['size'] !== null ? (string) $row['size'] : null,
            'size_label' => (string) $row['kind'] === 'honey' && $row['size'] !== null
                ? honey_bottle_size_label((string) $row['size'])
                : null,
            'product_key' => $row['product_key'] !== null ? (string) $row['product_key'] : null,
            'product_label' => (string) $row['product_label'],
            'qty' => $qty,
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    return [
        'id' => (int) $session['id'],
        'status' => (string) $session['status'],
        'started_by' => (int) $session['started_by'],
        'started_by_name' => (string) ($session['started_by_name'] ?? ''),
        'confirmed_by_name' => $session['confirmed_by_name'] !== null ? (string) $session['confirmed_by_name'] : null,
        'created_at' => (string) $session['created_at'],
        'updated_at' => (string) $session['updated_at'],
        'confirmed_at' => $session['confirmed_at'] !== null ? (string) $session['confirmed_at'] : null,
        'discarded_at' => $session['discarded_at'] !== null ? (string) $session['discarded_at'] : null,
        'items' => $items,
        'line_count' => count($items),
        'total_qty' => $totalQty,
    ];
}

/** @return array<string,mixed> */
function rts_stock_in_session_start(PDO $pdo, int $userId): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare(
            'SELECT id FROM rts_stock_in_sessions
             WHERE started_by = ? AND status = "draft"
             ORDER BY id DESC LIMIT 1 FOR UPDATE'
        );
        $st->execute([$userId]);
        $existing = (int) $st->fetchColumn();
        if ($existing < 1) {
            $pdo->prepare('INSERT INTO rts_stock_in_sessions (started_by, status) VALUES (?, "draft")')
                ->execute([$userId]);
            $existing = (int) $pdo->lastInsertId();
        }
        if ($owns) {
            $pdo->commit();
        }

        return rts_stock_in_session_get($pdo, $existing, $userId);
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function rts_stock_in_event(
    PDO $pdo,
    int $sessionId,
    int $userId,
    string $type,
    ?int $itemId = null,
    ?string $requestKey = null,
    ?string $code = null,
    ?int $qtyDelta = null,
    ?array $payload = null
): bool {
    $st = $pdo->prepare(
        'INSERT IGNORE INTO rts_stock_in_session_events
            (session_id, item_id, event_type, request_key, code, qty_delta, actor_user_id, payload_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $st->execute([
        $sessionId,
        $itemId,
        $type,
        $requestKey,
        $code,
        $qtyDelta,
        $userId,
        $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES) : null,
    ]);

    return $st->rowCount() > 0;
}

/** @param array<string,mixed> $row */
function rts_stock_in_validate_honey(array $row): void
{
    if ((string) $row['status'] === 'void') {
        throw new RuntimeException('This barcode is void.');
    }
    $unitId = $row['unit_id'] !== null ? (int) $row['unit_id'] : 0;
    if ($unitId < 1) {
        if ((int) ($row['packaging_row_id'] ?? 0) < 1) {
            throw new RuntimeException('Barcode is not tied to a packaging batch.');
        }
        return;
    }
    $status = (string) ($row['unit_status'] ?? '');
    $pool = (string) ($row['unit_pool'] ?? '');
    if ($pool === 'rts_sellable' && $status === 'in_stock') {
        throw new RuntimeException('Already in RTS inventory.');
    }
    if ($pool === 'packaging' && $status === 'in_stock') {
        throw new RuntimeException('This bottle is still in packaging stock.');
    }
    if (!in_array($status, ['packed', 'sold', 'shipped', 'rts'], true)) {
        throw new RuntimeException('This bottle cannot enter RTS inventory (' . $status . ').');
    }
}

/** @return array<string,mixed>|null */
function rts_stock_in_dept_lookup(PDO $pdo, string $code): ?array
{
    $st = $pdo->prepare(
        'SELECT c.id AS barcode_id, c.code, c.status AS barcode_status, b.product_key, b.batch_no,
                u.id AS unit_id, u.status AS unit_status, u.pool AS unit_pool
         FROM dept_pouch_barcodes c
         INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
         LEFT JOIN dept_pouch_units u ON u.barcode_id = c.id
         WHERE c.code = ? LIMIT 1'
    );
    $st->execute([dept_pouch_normalize_code($code)]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/** @param array<string,mixed> $row */
function rts_stock_in_validate_dept(array $row): void
{
    if ((string) $row['barcode_status'] === 'void') {
        throw new RuntimeException('This barcode is void.');
    }
    $unitId = $row['unit_id'] !== null ? (int) $row['unit_id'] : 0;
    if ($unitId < 1) {
        return;
    }
    $status = (string) ($row['unit_status'] ?? '');
    $pool = (string) ($row['unit_pool'] ?? 'finished');
    if ($pool === 'rts_sellable' && $status === 'in_stock') {
        throw new RuntimeException('Already in RTS inventory.');
    }
    if ($pool === 'finished' && $status === 'in_stock') {
        throw new RuntimeException('This unit is still in finished stock.');
    }
    if (!in_array($status, ['packed', 'sold', 'shipped', 'rts'], true)) {
        throw new RuntimeException('This unit cannot enter RTS inventory (' . $status . ').');
    }
}

/** @return array{result:string,session:array<string,mixed>,lookup:array<string,mixed>|null} */
function rts_stock_in_session_scan(
    PDO $pdo,
    int $sessionId,
    int $userId,
    string $code,
    int $qty,
    string $requestKey
): array {
    $code = strtoupper(trim($code));
    if ($code === '') {
        throw new RuntimeException('Barcode is required.');
    }
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    $requestKey = rts_stock_in_request_key($requestKey);
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);
        if ((string) $session['status'] !== 'draft') {
            throw new RuntimeException('This RTS stock-in session is already closed.');
        }
        if (!rts_stock_in_event($pdo, $sessionId, $userId, 'scan', null, $requestKey, $code, 0)) {
            if ($owns) {
                $pdo->commit();
            }
            return ['result' => 'replayed', 'session' => rts_stock_in_session_get($pdo, $sessionId, $userId), 'lookup' => null];
        }

        $lookup = null;
        $sku = sku_barcode_fetch_by_code($pdo, $code);
        if ($sku !== null) {
            $kind = (string) $sku['kind'];
            $variant = $kind === 'honey' ? honey_normalize_variant((string) $sku['variant']) : null;
            $size = $kind === 'honey' ? (string) $sku['size'] : null;
            $productKey = $kind === 'dept' ? (string) $sku['product_key'] : null;
            $label = $kind === 'honey'
                ? ucfirst((string) $variant) . ' · ' . honey_bottle_size_label((string) $size)
                : dept_pouch_product_label((string) $productKey);
            $pdo->prepare(
                'INSERT INTO rts_stock_in_session_items
                    (session_id, kind, lookup_kind, code, sku_id, variant, size, product_key, product_label, qty)
                 VALUES (?, ?, "sku", ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), updated_at = NOW()'
            )->execute([$sessionId, $kind, $code, (int) $sku['id'], $variant, $size, $productKey, $label, $qty]);
            $itemSt = $pdo->prepare('SELECT id, qty FROM rts_stock_in_session_items WHERE session_id = ? AND code = ?');
            $itemSt->execute([$sessionId, $code]);
            $item = $itemSt->fetch(PDO::FETCH_ASSOC);
            $itemId = (int) ($item['id'] ?? 0);
            $pdo->prepare(
                'UPDATE rts_stock_in_session_events SET item_id = ?, qty_delta = ? WHERE request_key = ?'
            )->execute([$itemId, $qty, $requestKey]);
            $lookup = [
                'kind' => $kind,
                'lookup_kind' => 'sku',
                'code' => $code,
                'product_label' => $label,
                'variant' => $variant,
                'size' => $size,
                'size_label' => $size !== null ? honey_bottle_size_label($size) : null,
                'product_key' => $productKey,
                'qty' => (int) ($item['qty'] ?? $qty),
                'added_qty' => $qty,
            ];
        } else {
            $honey = honey_bottle_fetch_code_row($pdo, $code);
            if ($honey !== null) {
                rts_stock_in_validate_honey($honey);
                $kind = 'honey';
                $lookupKind = 'unique_honey';
                $label = ucfirst((string) $honey['variant']) . ' · ' . honey_bottle_size_label((string) $honey['size']);
                $args = [$sessionId, $kind, $lookupKind, $code, (int) $honey['id'], (string) $honey['variant'], (string) $honey['size'], $label];
                $lookup = [
                    'kind' => $kind, 'lookup_kind' => $lookupKind, 'code' => $code, 'product_label' => $label,
                    'variant' => (string) $honey['variant'], 'size' => (string) $honey['size'],
                    'size_label' => honey_bottle_size_label((string) $honey['size']), 'qty' => 1, 'added_qty' => 1,
                ];
                $sql = 'INSERT IGNORE INTO rts_stock_in_session_items
                            (session_id, kind, lookup_kind, code, honey_barcode_id, variant, size, product_label, qty)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)';
            } else {
                $dept = rts_stock_in_dept_lookup($pdo, $code);
                if ($dept === null) {
                    throw new RuntimeException('Unknown barcode.');
                }
                rts_stock_in_validate_dept($dept);
                $productKey = dept_pouch_normalize_product_key((string) $dept['product_key']);
                $label = dept_pouch_product_label($productKey);
                $kind = 'dept';
                $lookupKind = 'unique_dept';
                $args = [$sessionId, $kind, $lookupKind, $code, (int) $dept['barcode_id'], $productKey, $label];
                $lookup = [
                    'kind' => $kind, 'lookup_kind' => $lookupKind, 'code' => $code,
                    'product_key' => $productKey, 'product_label' => $label, 'qty' => 1, 'added_qty' => 1,
                ];
                $sql = 'INSERT IGNORE INTO rts_stock_in_session_items
                            (session_id, kind, lookup_kind, code, dept_barcode_id, product_key, product_label, qty)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)';
            }
            $insert = $pdo->prepare($sql);
            $insert->execute($args);
            if ($insert->rowCount() < 1) {
                $pdo->prepare(
                    'UPDATE rts_stock_in_session_events SET qty_delta = 0, payload_json = ? WHERE request_key = ?'
                )->execute([json_encode(['result' => 'duplicate_session']), $requestKey]);
                if ($owns) {
                    $pdo->commit();
                }
                return [
                    'result' => 'duplicate_session',
                    'session' => rts_stock_in_session_get($pdo, $sessionId, $userId),
                    'lookup' => $lookup,
                ];
            }
            $itemId = (int) $pdo->lastInsertId();
            $pdo->prepare(
                'UPDATE rts_stock_in_session_events SET item_id = ?, qty_delta = 1 WHERE request_key = ?'
            )->execute([$itemId, $requestKey]);
        }

        if ($owns) {
            $pdo->commit();
        }

        return ['result' => 'ok', 'session' => rts_stock_in_session_get($pdo, $sessionId, $userId), 'lookup' => $lookup];
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** @return array<string,mixed> */
function rts_stock_in_session_update_qty(PDO $pdo, int $sessionId, int $itemId, int $userId, int $qty): array
{
    if ($qty < 1 || $qty > 5000) {
        throw new RuntimeException('Quantity must be between 1 and 5000.');
    }
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);
        if ((string) $session['status'] !== 'draft') {
            throw new RuntimeException('This RTS stock-in session is already closed.');
        }
        $st = $pdo->prepare(
            'SELECT id, qty, lookup_kind, code FROM rts_stock_in_session_items
             WHERE id = ? AND session_id = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$itemId, $sessionId]);
        $item = $st->fetch(PDO::FETCH_ASSOC);
        if ($item === false) {
            throw new RuntimeException('Session item not found.');
        }
        if ((string) $item['lookup_kind'] !== 'sku') {
            throw new RuntimeException('Unique stickers always have quantity 1.');
        }
        $oldQty = (int) $item['qty'];
        $pdo->prepare('UPDATE rts_stock_in_session_items SET qty = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$qty, $itemId]);
        rts_stock_in_event($pdo, $sessionId, $userId, 'update', $itemId, null, (string) $item['code'], $qty - $oldQty);
        if ($owns) {
            $pdo->commit();
        }

        return rts_stock_in_session_get($pdo, $sessionId, $userId);
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** @return array<string,mixed> */
function rts_stock_in_session_remove(PDO $pdo, int $sessionId, int $itemId, int $userId): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);
        if ((string) $session['status'] !== 'draft') {
            throw new RuntimeException('This RTS stock-in session is already closed.');
        }
        $st = $pdo->prepare(
            'SELECT code, qty FROM rts_stock_in_session_items
             WHERE id = ? AND session_id = ? LIMIT 1 FOR UPDATE'
        );
        $st->execute([$itemId, $sessionId]);
        $item = $st->fetch(PDO::FETCH_ASSOC);
        if ($item !== false) {
            rts_stock_in_event($pdo, $sessionId, $userId, 'remove', $itemId, null, (string) $item['code'], -(int) $item['qty']);
            $pdo->prepare('DELETE FROM rts_stock_in_session_items WHERE id = ? AND session_id = ?')
                ->execute([$itemId, $sessionId]);
        }
        if ($owns) {
            $pdo->commit();
        }

        return rts_stock_in_session_get($pdo, $sessionId, $userId);
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** @return array<string,mixed> */
function rts_stock_in_session_discard(PDO $pdo, int $sessionId, int $userId): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);
        if ((string) $session['status'] === 'confirmed') {
            throw new RuntimeException('A confirmed RTS stock-in session cannot be cancelled.');
        }
        if ((string) $session['status'] === 'draft') {
            $pdo->prepare(
                'UPDATE rts_stock_in_sessions
                 SET status = "discarded", discarded_at = NOW(), discarded_by = ? WHERE id = ?'
            )->execute([$userId, $sessionId]);
            rts_stock_in_event($pdo, $sessionId, $userId, 'discard');
        }
        if ($owns) {
            $pdo->commit();
        }

        return rts_stock_in_session_get($pdo, $sessionId, $userId);
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** @return array<string,mixed> */
function rts_stock_in_session_confirm(PDO $pdo, int $sessionId, int $userId): array
{
    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);
        if ((string) $session['status'] === 'confirmed') {
            if ($owns) {
                $pdo->commit();
            }
            return rts_stock_in_session_get($pdo, $sessionId, $userId);
        }
        if ((string) $session['status'] !== 'draft') {
            throw new RuntimeException('This RTS stock-in session was cancelled.');
        }
        $st = $pdo->prepare(
            'SELECT * FROM rts_stock_in_session_items WHERE session_id = ? ORDER BY lookup_kind <> "sku" DESC, id ASC FOR UPDATE'
        );
        $st->execute([$sessionId]);
        $items = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($items === []) {
            throw new RuntimeException('Scan at least one product before confirming.');
        }
        foreach ($items as $item) {
            $lookupKind = (string) $item['lookup_kind'];
            if ($lookupKind === 'sku') {
                $sku = sku_barcode_fetch_by_code($pdo, (string) $item['code']);
                if ($sku === null || (int) $sku['id'] !== (int) $item['sku_id']) {
                    throw new RuntimeException('A SKU barcode changed after it was scanned. Cancel and rescan.');
                }
                sku_barcode_rts_stock_in(
                    $pdo,
                    $userId,
                    (string) $sku['kind'],
                    (string) ($sku['variant'] ?? ''),
                    (string) ($sku['size'] ?? ''),
                    (string) ($sku['product_key'] ?? ''),
                    (int) $item['qty']
                );
            } elseif ($lookupKind === 'unique_honey') {
                honey_bottle_rts_stock_in_scan($pdo, $userId, (string) $item['code']);
            } else {
                dept_pouch_rts_stock_in_scan($pdo, $userId, (string) $item['code']);
            }
        }
        $pdo->prepare(
            'UPDATE rts_stock_in_sessions
             SET status = "confirmed", confirmed_at = NOW(), confirmed_by = ? WHERE id = ?'
        )->execute([$userId, $sessionId]);
        rts_stock_in_event($pdo, $sessionId, $userId, 'confirm', null, null, null, null, [
            'line_count' => count($items),
            'total_qty' => array_sum(array_map(static fn(array $row): int => (int) $row['qty'], $items)),
        ]);
        if ($owns) {
            $pdo->commit();
        }

        return rts_stock_in_session_get($pdo, $sessionId, $userId);
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
