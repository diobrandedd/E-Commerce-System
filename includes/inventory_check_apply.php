<?php

declare(strict_types=1);

/**
 * Check inventory → sync finished stock to audited counts (confirm apply).
 * Loaded from inventory_check.php.
 */

/**
 * Persist a compare run (+ variance lines) for one or more events.
 *
 * @param list<int> $eventIds
 * @return array<string, mixed>
 */
function inventory_check_compare_run_for_events(PDO $pdo, array $eventIds, int $userId): array
{
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if ($eventIds === []) {
        throw new RuntimeException('No audit selected.');
    }
    $primaryId = $eventIds[0];
    $event = inventory_check_event_get($pdo, $primaryId);
    $lines = inventory_check_compare_build($pdo, $eventIds);
    if ($lines['scope'] === []) {
        throw new RuntimeException('Nothing has been counted yet.');
    }

    $countedTotal = 0;
    $systemTotal = 0.0;
    $mismatch = 0;
    foreach ($lines['lines'] as $line) {
        $countedTotal += (int) $line['counted_qty'];
        $systemTotal += (float) $line['system_qty'];
        if (abs((float) $line['variance']) > 0.001) {
            $mismatch++;
        }
    }

    $merged = count($eventIds) > 1 ? implode(',', $eventIds) : null;

    $pdo->prepare(
        'INSERT INTO inventory_check_compare_runs
            (event_id, scope, line_count, mismatch_count, counted_total, system_total, run_by, merged_event_ids)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $primaryId,
        implode(', ', $lines['scope_labels']),
        count($lines['lines']),
        $mismatch,
        $countedTotal,
        (int) round($systemTotal),
        $userId,
        $merged,
    ]);
    $runId = (int) $pdo->lastInsertId();

    $ins = $pdo->prepare(
        'INSERT INTO inventory_check_variance_lines
            (run_id, kind, product_key, packaging_row_id, supplier_code, batch_no, bottling_date,
             variant, size, counted_qty, system_qty, variance)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($lines['lines'] as $line) {
        $ins->execute([
            $runId,
            $line['kind'],
            $line['product_key'],
            $line['packaging_row_id'],
            $line['supplier_code'],
            $line['batch_no'],
            $line['bottling_date'],
            $line['variant'],
            $line['size'],
            $line['counted_qty'],
            $line['system_qty'],
            $line['variance'],
        ]);
    }

    return inventory_check_compare_get($pdo, $runId, $event, $eventIds);
}

/**
 * Unique sticker codes counted in these audits, keyed by compare scope key.
 * Honey: "honey|{variant}|{size}". Dept: "dept|{product_key}".
 * SKU-only lines (sku_id set) are excluded.
 *
 * @param list<int> $eventIds
 * @return array<string, array<string, true>>
 */
function inventory_check_audited_unique_codes(PDO $pdo, array $eventIds): array
{
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if ($eventIds === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
    $st = $pdo->prepare(
        'SELECT kind, product_key, variant, size, code
         FROM inventory_check_scans
         WHERE event_id IN (' . $placeholders . ')
           AND barcode_id IS NOT NULL
           AND (sku_id IS NULL OR sku_id = 0)
           AND code IS NOT NULL AND TRIM(code) <> ""'
    );
    $st->execute($eventIds);
    $map = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $kind = (string) $row['kind'];
        $code = strtoupper(trim((string) $row['code']));
        if ($code === '') {
            continue;
        }
        if ($kind === 'honey') {
            $variant = strtolower(trim((string) ($row['variant'] ?? '')));
            $size = strtolower(trim((string) ($row['size'] ?? '')));
            if ($variant === '' || $size === '') {
                continue;
            }
            $key = 'honey|' . $variant . '|' . $size;
        } else {
            $pk = trim((string) $row['product_key']);
            if ($pk === '') {
                continue;
            }
            $key = 'dept|' . $pk;
        }
        $map[$key][$code] = true;
    }

    return $map;
}

/**
 * @param array<string, array<string, true>> $auditedUniques
 */
function inventory_check_void_missing_uniques(PDO $pdo, array $auditedUniques, int $runId): int
{
    $voided = 0;
    $reason = 'inventory_check_apply run#' . $runId;

    foreach ($auditedUniques as $scopeKey => $keepers) {
        if ($keepers === []) {
            continue;
        }
        $parts = explode('|', (string) $scopeKey, 3);
        $kind = $parts[0] ?? '';

        if ($kind === 'honey' && count($parts) === 3) {
            $variant = honey_normalize_variant($parts[1]);
            $size = (string) $parts[2];
            if (!in_array($size, HONEY_SIZES, true)) {
                continue;
            }
            $st = $pdo->prepare(
                'SELECT c.code
                 FROM honey_bottle_units un
                 INNER JOIN honey_bottle_barcodes c ON c.id = un.barcode_id
                 INNER JOIN honey_barcode_batches b ON b.id = c.batch_id
                 LEFT JOIN honey_packaging_rows pk ON pk.id = COALESCE(un.packaging_row_id, b.packaging_row_id)
                 WHERE un.status = "in_stock"
                   AND c.size = ?
                   AND LOWER(TRIM(COALESCE(pk.variant, b.variant))) = ?'
            );
            $st->execute([$size, strtolower($variant)]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $code = strtoupper(trim((string) $row['code']));
                if ($code === '' || isset($keepers[$code])) {
                    continue;
                }
                try {
                    honey_bottle_void_code($pdo, $code, 0, $reason);
                    $voided++;
                } catch (Throwable) {
                    /* skip */
                }
            }
            continue;
        }

        if ($kind === 'dept' && count($parts) >= 2) {
            $productKey = (string) $parts[1];
            $st = $pdo->prepare(
                'SELECT c.code, c.id AS barcode_id, un.id AS unit_id, c.status AS barcode_status
                 FROM dept_pouch_units un
                 INNER JOIN dept_pouch_barcodes c ON c.id = un.barcode_id
                 INNER JOIN dept_pouch_barcode_batches b ON b.id = c.batch_id
                 WHERE un.status = "in_stock"
                   AND (un.product_key = ? OR b.product_key = ?)'
            );
            $st->execute([$productKey, $productKey]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $code = strtoupper(trim((string) $row['code']));
                if ($code === '' || isset($keepers[$code])) {
                    continue;
                }
                if ((string) $row['barcode_status'] === 'void') {
                    continue;
                }
                $pdo->prepare(
                    'UPDATE dept_pouch_barcodes SET status = "void", void_reason = ? WHERE id = ?'
                )->execute([substr($reason, 0, 255), (int) $row['barcode_id']]);
                $pdo->prepare('UPDATE dept_pouch_units SET status = "void" WHERE id = ?')
                    ->execute([(int) $row['unit_id']]);
                $voided++;
            }
        }
    }

    return $voided;
}

function inventory_check_apply_honey_qty(PDO $pdo, int $userId, string $variant, string $size, int $target): void
{
    if ($target < 0) {
        throw new RuntimeException('Audited quantity cannot be negative.');
    }
    $variant = honey_normalize_variant($variant);
    if (!in_array($size, HONEY_SIZES, true)) {
        throw new RuntimeException('Invalid honey size.');
    }
    [$curCol, $iniCol] = honey_packaging_size_columns($size);

    $st = $pdo->prepare(
        "SELECT id, {$curCol} AS cur, {$iniCol} AS ini
         FROM honey_packaging_rows
         WHERE variant = ?
         ORDER BY id ASC
         FOR UPDATE"
    );
    $st->execute([$variant]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $sum = 0.0;
    foreach ($rows as $row) {
        $sum += (float) $row['cur'];
    }
    $delta = (float) $target - $sum;
    if (abs($delta) < 0.001) {
        return;
    }

    if ($rows === []) {
        throw new RuntimeException(
            'No honey packaging lot for ' . ucfirst($variant) . ' ' . honey_bottle_size_label($size)
            . '. Seed a packaging batch before applying this audit.'
        );
    }

    if ($delta > 0) {
        $last = $rows[count($rows) - 1];
        $id = (int) $last['id'];
        $old = (float) $last['cur'];
        $new = $old + $delta;
        honey_log_edit($pdo, $userId, 'honey_packaging_rows', $id, $curCol, (string) $old, (string) $new, 'inventory_check_apply');
        $pdo->prepare(
            "UPDATE honey_packaging_rows SET {$curCol} = {$curCol} + ?, {$iniCol} = {$iniCol} + ? WHERE id = ?"
        )->execute([$delta, $delta, $id]);

        return;
    }

    $need = abs($delta);
    foreach ($rows as $row) {
        if ($need < 0.001) {
            break;
        }
        $id = (int) $row['id'];
        $cur = (float) $row['cur'];
        if ($cur <= 0) {
            continue;
        }
        $take = min($cur, $need);
        honey_log_edit(
            $pdo,
            $userId,
            'honey_packaging_rows',
            $id,
            $curCol,
            (string) $cur,
            (string) ($cur - $take),
            'inventory_check_apply'
        );
        $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = {$curCol} - ? WHERE id = ? AND {$curCol} >= ?")
            ->execute([$take, $id, $take]);
        $need -= $take;
    }

    if ($need > 0.001) {
        foreach ($rows as $row) {
            $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = 0 WHERE id = ?")->execute([(int) $row['id']]);
        }
        $lastId = (int) $rows[count($rows) - 1]['id'];
        honey_log_edit($pdo, $userId, 'honey_packaging_rows', $lastId, $curCol, '0', (string) $target, 'inventory_check_apply');
        $pdo->prepare("UPDATE honey_packaging_rows SET {$curCol} = ?, {$iniCol} = GREATEST({$iniCol}, ?) WHERE id = ?")
            ->execute([(float) $target, (float) $target, $lastId]);
    }
}

function inventory_check_apply_dept_qty(PDO $pdo, int $userId, string $productKey, int $target): void
{
    if ($target < 0) {
        throw new RuntimeException('Audited quantity cannot be negative.');
    }
    $row = dept_inventory_ensure_row($pdo, $userId, $productKey);
    $rowId = (int) $row['id'];
    $old = (float) $row['current_qty'];
    if (abs($old - $target) < 0.001) {
        return;
    }
    honey_log_edit(
        $pdo,
        $userId,
        'dept_inventory_rows',
        $rowId,
        'current_qty',
        (string) $old,
        (string) $target,
        'inventory_check_apply'
    );
    honey_log_edit(
        $pdo,
        $userId,
        'dept_inventory_rows',
        $rowId,
        'initial_qty',
        (string) ($row['initial_qty'] ?? $old),
        (string) $target,
        'inventory_check_apply'
    );
    $pdo->prepare('UPDATE dept_inventory_rows SET current_qty = ?, initial_qty = ? WHERE id = ?')
        ->execute([(float) $target, (float) $target, $rowId]);
}

/**
 * @param list<array<string, mixed>> $lines
 */
function inventory_check_apply_qty_to_audited(PDO $pdo, int $userId, array $lines): void
{
    foreach ($lines as $line) {
        $kind = (string) ($line['kind'] ?? '');
        $target = (int) ($line['counted_qty'] ?? 0);
        if ($kind === 'honey') {
            $variant = (string) ($line['variant'] ?? '');
            $size = (string) ($line['size'] ?? '');
            if ($variant === '' || $size === '') {
                continue;
            }
            inventory_check_apply_honey_qty($pdo, $userId, $variant, $size, $target);
            continue;
        }
        if ($kind === 'dept') {
            $pk = (string) ($line['product_key'] ?? '');
            if ($pk === '') {
                continue;
            }
            inventory_check_apply_dept_qty($pdo, $userId, $pk, $target);
        }
    }
}

/**
 * @param list<int> $eventIds
 */
function inventory_check_assert_not_already_applied(PDO $pdo, array $eventIds): void
{
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if ($eventIds === []) {
        return;
    }
    $wanted = array_fill_keys($eventIds, true);
    $st = $pdo->query(
        'SELECT id, event_id, merged_event_ids FROM inventory_check_compare_runs WHERE stock_applied = 1'
    );
    foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
        $ids = [(int) $row['event_id']];
        if (!empty($row['merged_event_ids'])) {
            foreach (explode(',', (string) $row['merged_event_ids']) as $p) {
                $n = (int) trim($p);
                if ($n > 0) {
                    $ids[] = $n;
                }
            }
        }
        foreach ($ids as $id) {
            if (isset($wanted[$id])) {
                throw new RuntimeException(
                    'Stock was already updated from an audit that includes session #' . $id
                    . '. Review history instead of applying again.'
                );
            }
        }
    }
}

/**
 * @param array{id:int,role?:string} $user
 * @param list<int> $eventIds
 * @return array{event: array<string, mixed>, compare: array<string, mixed>, closed: bool}
 */
function inventory_check_finalize_with_sync(
    PDO $pdo,
    array $user,
    array $eventIds,
    bool $closeOpenPrimary
): array {
    $userId = (int) ($user['id'] ?? 0);
    $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds))));
    if ($eventIds === []) {
        throw new RuntimeException('No audit selected.');
    }
    if (!inventory_check_can_view_all($user)) {
        throw new RuntimeException('Forbidden.');
    }

    inventory_check_assert_not_already_applied($pdo, $eventIds);

    $owns = !$pdo->inTransaction();
    if ($owns) {
        $pdo->beginTransaction();
    }
    try {
        $compare = inventory_check_compare_run_for_events($pdo, $eventIds, $userId);
        $runId = (int) $compare['run']['id'];
        $lines = $compare['lines'];

        $uniques = inventory_check_audited_unique_codes($pdo, $eventIds);
        $voided = inventory_check_void_missing_uniques($pdo, $uniques, $runId);
        inventory_check_apply_qty_to_audited($pdo, $userId, $lines);

        $pdo->prepare(
            'UPDATE inventory_check_compare_runs
             SET stock_applied = 1, stock_applied_at = NOW(), stock_applied_by = ?, barcodes_voided_count = ?
             WHERE id = ?'
        )->execute([$userId, $voided, $runId]);

        $closed = false;
        $event = inventory_check_event_get($pdo, $eventIds[0]);
        if ($closeOpenPrimary && (string) ($event['status'] ?? '') === 'open') {
            $event = inventory_check_event_close($pdo, $userId, $eventIds[0]);
            $closed = true;
        }

        if ($owns) {
            $pdo->commit();
        }

        return [
            'event' => $event,
            'compare' => inventory_check_compare_get($pdo, $runId, $event, $eventIds),
            'closed' => $closed,
        ];
    } catch (Throwable $e) {
        if ($owns && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * @param array<string, mixed> $r
 * @return array<string, mixed>
 */
function inventory_check_serialize_compare_run(array $r): array
{
    $mergedCsv = isset($r['merged_event_ids']) && $r['merged_event_ids'] !== null
        ? (string) $r['merged_event_ids']
        : null;
    $eventId = (int) $r['event_id'];
    $eventIds = inventory_check_parse_merged_event_ids($mergedCsv, $eventId);

    return [
        'id' => (int) $r['id'],
        'event_id' => $eventId,
        'event_ids' => $eventIds,
        'scope' => (string) $r['scope'],
        'line_count' => (int) $r['line_count'],
        'mismatch_count' => (int) $r['mismatch_count'],
        'counted_total' => (int) $r['counted_total'],
        'system_total' => (int) $r['system_total'],
        'run_by_name' => (string) ($r['run_by_name'] ?? ''),
        'run_at' => (string) $r['run_at'],
        'stock_applied' => !empty($r['stock_applied']),
        'stock_applied_at' => isset($r['stock_applied_at']) && $r['stock_applied_at'] !== null
            ? (string) $r['stock_applied_at']
            : null,
        'stock_applied_by_name' => isset($r['stock_applied_by_name']) && $r['stock_applied_by_name'] !== null
            ? (string) $r['stock_applied_by_name']
            : null,
        'barcodes_voided_count' => (int) ($r['barcodes_voided_count'] ?? 0),
        'merged_event_ids' => $mergedCsv,
        'is_merged' => count($eventIds) > 1,
    ];
}
