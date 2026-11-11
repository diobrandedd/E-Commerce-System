<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/system_activity_log.php';

function inventory_edit_log_has_source_column(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = $pdo->query("SHOW COLUMNS FROM inventory_edit_log LIKE 'source'");
        $cache = $st !== false && (bool) $st->fetch();
    } catch (Throwable) {
        $cache = false;
    }

    return $cache;
}

function inventory_edit_log_has_reason_column(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $st = $pdo->query("SHOW COLUMNS FROM inventory_edit_log LIKE 'reason'");
        $cache = $st !== false && (bool) $st->fetch();
    } catch (Throwable) {
        $cache = false;
    }

    return $cache;
}

/**
 * Human-readable label for a supplier id (for audit display).
 */
function inventory_edit_log_supplier_label(PDO $pdo, ?string $rawId): ?string
{
    if ($rawId === null || trim($rawId) === '' || !ctype_digit(trim($rawId))) {
        return null;
    }
    $id = (int) trim($rawId);
    if ($id < 1) {
        return null;
    }
    try {
        $st = $pdo->prepare('SELECT first_name, last_name FROM suppliers WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return 'supplier #' . $id;
        }
        $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));

        return $name !== '' ? $name : ('supplier #' . $id);
    } catch (Throwable) {
        return 'supplier #' . $id;
    }
}

/**
 * Resolve entity + field display for system Logs.
 *
 * @return array{entity: string, field: string, old: ?string, new: ?string, source: string}
 */
function inventory_edit_log_resolve_display(
    PDO $pdo,
    string $rowTable,
    int $rowId,
    string $field,
    ?string $old,
    ?string $new,
    ?string $source
): array {
    $entity = $rowTable . '#' . $rowId;
    $fieldLabel = match (true) {
        $field === 'on_hand' => 'on hand',
        $field === 'current_qty' => 'quantity',
        $field === 'current_liter' => 'liters',
        $field === 'current_250ml' => '250ml',
        $field === 'current_350ml' => '350ml',
        $field === 'current_gallon' => 'gallons',
        $field === 'initial_qty' => 'initial qty',
        $field === 'supplier_id' => 'supplier',
        $field === 'supplier_code' => 'supplier code',
        $field === 'supplier_name' => 'supplier name',
        $field === 'variant' => 'variant',
        $field === 'pool_yield_qty' => 'pool yield',
        $field === 'current_price' => 'price',
        str_ends_with($field, ':bags_on_hand') => 'bags on hand',
        default => $field,
    };
    $sourceKey = $source !== null ? trim($source) : '';
    $sourceLabel = match ($sourceKey) {
        'set_on_hand' => 'set on-hand',
        'executive_edit' => 'executive edit',
        'monthly_inventory' => 'monthly inventory',
        'count_correction' => 'count correction',
        '' => 'edit',
        default => $sourceKey,
    };

    $oldDisplay = $old;
    $newDisplay = $new;
    if ($field === 'supplier_id') {
        $oldDisplay = inventory_edit_log_supplier_label($pdo, $old) ?? $old;
        $newDisplay = inventory_edit_log_supplier_label($pdo, $new) ?? $new;
    }

    try {
        if ($rowTable === 'stocks') {
            $st = $pdo->prepare('SELECT name, category, unit FROM stocks WHERE id = ? LIMIT 1');
            $st->execute([$rowId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $name = trim((string) ($row['name'] ?? ''));
                $cat = trim((string) ($row['category'] ?? ''));
                $unit = trim((string) ($row['unit'] ?? ''));
                $bits = array_filter([$name !== '' ? $name : null, $cat !== '' ? $cat : null, $unit !== '' ? $unit : null]);
                $entity = $bits !== [] ? implode(' · ', $bits) : $entity;
            }
        } elseif ($rowTable === 'honey_warehouse_rows') {
            $st = $pdo->prepare(
                'SELECT supplier_code, supplier_name, variant, arrival_date FROM honey_warehouse_rows WHERE id = ? LIMIT 1'
            );
            $st->execute([$rowId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $code = trim((string) ($row['supplier_code'] ?? ''));
                $sname = trim((string) ($row['supplier_name'] ?? ''));
                $variant = trim((string) ($row['variant'] ?? ''));
                $arrival = trim((string) ($row['arrival_date'] ?? ''));
                $who = $code !== '' ? $code : ($sname !== '' ? $sname : null);
                $bits = array_filter([
                    'Honey warehouse',
                    $who,
                    $variant !== '' ? ucfirst($variant) : null,
                    $arrival !== '' ? $arrival : null,
                ]);
                $entity = implode(' · ', $bits);
            }
        } elseif ($rowTable === 'honey_production_rows') {
            $st = $pdo->prepare('SELECT supplier_code, variant FROM honey_production_rows WHERE id = ? LIMIT 1');
            $st->execute([$rowId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $code = trim((string) ($row['supplier_code'] ?? ''));
                $variant = trim((string) ($row['variant'] ?? ''));
                $bits = array_filter([
                    'Honey production',
                    $code !== '' ? $code : null,
                    $variant !== '' ? ucfirst($variant) : null,
                ]);
                $entity = implode(' · ', $bits);
            }
        } elseif ($rowTable === 'honey_packaging_rows') {
            $st = $pdo->prepare(
                'SELECT supplier_code, batch_no, variant FROM honey_packaging_rows WHERE id = ? LIMIT 1'
            );
            $st->execute([$rowId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $code = trim((string) ($row['supplier_code'] ?? ''));
                $batch = trim((string) ($row['batch_no'] ?? ''));
                $variant = trim((string) ($row['variant'] ?? ''));
                $bits = array_filter([
                    'Honey packaging',
                    $code !== '' ? $code : null,
                    $batch !== '' && $batch !== 'POOL' ? $batch : ($batch === 'POOL' ? 'pool' : null),
                    $variant !== '' ? ucfirst($variant) : null,
                ]);
                $entity = implode(' · ', $bits);
            }
        } elseif ($rowTable === 'rts_inventory_rows') {
            $st = $pdo->prepare(
                'SELECT product_label, category, variant FROM rts_inventory_rows WHERE id = ? LIMIT 1'
            );
            $st->execute([$rowId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $label = trim((string) ($row['product_label'] ?? ''));
                $cat = trim((string) ($row['category'] ?? ''));
                $variant = trim((string) ($row['variant'] ?? ''));
                $bits = array_filter([
                    'RTS',
                    $label !== '' ? $label : ($cat !== '' ? $cat : null),
                    $variant !== '' ? ucfirst($variant) : null,
                ]);
                $entity = implode(' · ', $bits);
            }
        } elseif ($rowTable === 'dept_inventory_rows') {
            $st = $pdo->prepare(
                'SELECT product_key, product_label FROM dept_inventory_rows WHERE id = ? LIMIT 1'
            );
            $st->execute([$rowId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row !== false) {
                $label = trim((string) ($row['product_label'] ?? ''));
                $key = trim((string) ($row['product_key'] ?? ''));
                $entity = 'Dept finished · ' . ($label !== '' ? $label : ($key !== '' ? $key : ('#' . $rowId)));
            }
        } elseif ($rowTable === 'dept_raw_bags') {
            // Synthetic audit key: row_id is the stocks.id for the raw-bag SKU.
            $productKey = null;
            if (preg_match('/^([a-z0-9_]+):bags_on_hand$/', $field, $m) === 1) {
                $productKey = $m[1];
            }
            $st = $pdo->prepare('SELECT name, category FROM stocks WHERE id = ? LIMIT 1');
            $st->execute([$rowId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $name = $row !== false ? trim((string) ($row['name'] ?? '')) : '';
            $bits = array_filter([
                'Raw bags',
                $name !== '' ? $name : null,
                $productKey !== null ? $productKey : null,
            ]);
            $entity = $bits !== [] ? implode(' · ', $bits) : $entity;
        }
    } catch (Throwable) {
        // Keep fallback entity label.
    }

    return [
        'entity' => $entity,
        'field' => $fieldLabel,
        'old' => $oldDisplay,
        'new' => $newDisplay,
        'source' => $sourceLabel,
    ];
}

/**
 * Append one inventory edit audit row.
 * Soft-fails if the log table is missing (pre-migration).
 */
function inventory_edit_log_write(
    PDO $pdo,
    int $userId,
    string $rowTable,
    int $rowId,
    string $field,
    ?string $old,
    ?string $new,
    ?string $source = null,
    ?string $reason = null
): void {
    try {
        $hasSource = $source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo);
        $hasReason = $reason !== null && $reason !== '' && inventory_edit_log_has_reason_column($pdo);

        if ($hasSource && $hasReason) {
            $pdo->prepare(
                'INSERT INTO inventory_edit_log (row_table, row_id, field_name, old_value, new_value, edited_by, source, reason)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$rowTable, $rowId, $field, $old, $new, $userId, $source, $reason]);
        } elseif ($hasSource) {
            $pdo->prepare(
                'INSERT INTO inventory_edit_log (row_table, row_id, field_name, old_value, new_value, edited_by, source)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$rowTable, $rowId, $field, $old, $new, $userId, $source]);
        } elseif ($hasReason) {
            $pdo->prepare(
                'INSERT INTO inventory_edit_log (row_table, row_id, field_name, old_value, new_value, edited_by, reason)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$rowTable, $rowId, $field, $old, $new, $userId, $reason]);
        } else {
            $pdo->prepare(
                'INSERT INTO inventory_edit_log (row_table, row_id, field_name, old_value, new_value, edited_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$rowTable, $rowId, $field, $old, $new, $userId]);
        }

        $oldNorm = $old === null ? '' : trim((string) $old);
        $newNorm = $new === null ? '' : trim((string) $new);
        // Skip no-op field writes from the unified Logs feed (still stored in inventory_edit_log).
        if ($oldNorm === $newNorm) {
            return;
        }

        $display = inventory_edit_log_resolve_display($pdo, $rowTable, $rowId, $field, $old, $new, $source);
        $reasonBit = $reason !== null && $reason !== '' ? (' — ' . $reason) : '';
        $summary = sprintf(
            '%s · %s (%s)%s',
            $display['entity'],
            $display['field'],
            $display['source'],
            $reasonBit
        );
        system_activity_log_write(
            $pdo,
            'inventory',
            'edit',
            $summary,
            [
                'entity_type' => $rowTable,
                'entity_id' => $rowId,
                'old_value' => $display['old'],
                'new_value' => $display['new'],
                'actor_user_id' => $userId,
                'meta' => [
                    'field' => $field,
                    'field_label' => $display['field'],
                    'source' => $source,
                    'reason' => $reason,
                    'entity_label' => $display['entity'],
                ],
            ]
        );
    } catch (Throwable) {
        // Log table may not exist before migration.
    }
}

/**
 * @return array{rows: list<array<string, mixed>>, total: int}
 */
function inventory_edit_log_list(
    PDO $pdo,
    int $limit = 50,
    int $offset = 0,
    ?string $rowTable = null,
    ?string $source = null,
    ?string $dateFrom = null,
    ?string $dateTo = null,
    ?string $q = null
): array {
    $limit = max(1, min(200, $limit));
    $offset = max(0, $offset);
    $where = ['1=1'];
    $params = [];

    if ($rowTable !== null && $rowTable !== '') {
        $where[] = 'l.row_table = ?';
        $params[] = $rowTable;
    }
    if ($source !== null && $source !== '' && inventory_edit_log_has_source_column($pdo)) {
        $where[] = 'l.source = ?';
        $params[] = $source;
    }
    if ($dateFrom !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'l.created_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'l.created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }
    if ($q !== null && trim($q) !== '') {
        $like = '%' . trim($q) . '%';
        if (inventory_edit_log_has_reason_column($pdo)) {
            $where[] = '(l.field_name LIKE ? OR l.old_value LIKE ? OR l.new_value LIKE ? OR CAST(l.row_id AS CHAR) LIKE ? OR u.name LIKE ? OR l.reason LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        } else {
            $where[] = '(l.field_name LIKE ? OR l.old_value LIKE ? OR l.new_value LIKE ? OR CAST(l.row_id AS CHAR) LIKE ? OR u.name LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
    }

    $whereSql = implode(' AND ', $where);
    $hasSource = inventory_edit_log_has_source_column($pdo);
    $hasReason = inventory_edit_log_has_reason_column($pdo);
    $sourceCol = $hasSource ? 'l.source' : 'NULL AS source';
    $reasonCol = $hasReason ? 'l.reason' : 'NULL AS reason';

    $countSt = $pdo->prepare(
        "SELECT COUNT(*) FROM inventory_edit_log l
         LEFT JOIN users u ON u.id = l.edited_by
         WHERE {$whereSql}"
    );
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $st = $pdo->prepare(
        "SELECT l.id, l.row_table, l.row_id, l.field_name, l.old_value, l.new_value,
                l.edited_by, {$sourceCol}, {$reasonCol}, l.created_at, u.name AS edited_by_name
         FROM inventory_edit_log l
         LEFT JOIN users u ON u.id = l.edited_by
         WHERE {$whereSql}
         ORDER BY l.created_at DESC, l.id DESC
         LIMIT {$limit} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return [
        'rows' => array_map(static function (array $r): array {
            return [
                'id' => (int) $r['id'],
                'row_table' => (string) $r['row_table'],
                'row_id' => (int) $r['row_id'],
                'field_name' => (string) $r['field_name'],
                'old_value' => $r['old_value'],
                'new_value' => $r['new_value'],
                'edited_by' => (int) $r['edited_by'],
                'edited_by_name' => (string) ($r['edited_by_name'] ?? ''),
                'source' => $r['source'] ?? null,
                'reason' => isset($r['reason']) && $r['reason'] !== null && $r['reason'] !== ''
                    ? (string) $r['reason']
                    : null,
                'created_at' => (string) $r['created_at'],
            ];
        }, $rows),
        'total' => $total,
    ];
}
