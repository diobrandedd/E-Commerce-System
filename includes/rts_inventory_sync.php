<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rts_schema.php';

function rts_inv_product_label(string $productType, ?string $combo): string
{
    $map = [
        'honey' => 'Honey',
        'honey_with_other' => 'Honey w/ Other',
        'pouch_pt' => 'Paragis Tea',
        'pouch_pt_with_honey' => 'Paragis w/ Honey',
        'pouch_brc' => 'Black Rice Coffee',
        'jar_cacao' => 'Jar Cacao',
        'jar_cacao_with_honey' => 'Jar Cacao w/ Honey',
        'jar_honey_comb' => 'Honey Comb',
    ];
    $base = $map[$productType] ?? $productType;
    if ($combo) {
        return $base . ' + ' . $combo;
    }

    return $base;
}

function rts_inv_is_honey_line(string $productType): bool
{
    return str_contains($productType, 'honey');
}

/** @return array{synced:int, last_synced_at:?string, last_error:?string} */
function rts_inventory_sync_run(PDO $pdo): array
{
    $state = $pdo->query('SELECT * FROM rts_sync_state WHERE id = 1 LIMIT 1')->fetch();
    $lastId = (int) ($state['last_line_item_id'] ?? 0);
    $synced = 0;

    $dispositionSql = rts_db_has_column($pdo, 'rts_cases', 'case_disposition')
        ? " AND COALESCE(c.case_disposition, 'standard') IN ('standard', 'leak')"
        : '';

    $hasLineRole = rts_db_has_column($pdo, 'rts_line_items', 'line_role');
    $hasInvPool = rts_db_has_column($pdo, 'rts_inventory_rows', 'inventory_pool');
    $lineRoleCol = $hasLineRole ? ', COALESCE(li.line_role, \'reusable\') AS line_role' : '';

    $st = $pdo->prepare(
        'SELECT li.id, li.product_type, li.honey_size, li.honey_variant, li.combo_product, li.quantity' . $lineRoleCol . '
         FROM rts_line_items li
         INNER JOIN rts_cases c ON c.id = li.case_id
         WHERE c.voided_at IS NULL' . $dispositionSql . ' AND li.id > ?
         ORDER BY li.id ASC'
    );
    $st->execute([$lastId]);
    $lines = $st->fetchAll();

    if ($hasInvPool) {
        $ins = $pdo->prepare(
            'INSERT INTO rts_inventory_rows (
                source_line_item_id, category, inventory_pool, product_label, variant,
                current_liter, initial_liter, current_250ml, initial_250ml,
                current_350ml, initial_350ml, current_gallon, initial_gallon,
                current_qty, initial_qty, synced_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO rts_inventory_rows (
                source_line_item_id, category, product_label, variant,
                current_liter, initial_liter, current_250ml, initial_250ml,
                current_350ml, initial_350ml, current_gallon, initial_gallon,
                current_qty, initial_qty, synced_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
    }

    $pdo->beginTransaction();
    try {
        $maxId = $lastId;
        foreach ($lines as $li) {
            $lineId = (int) $li['id'];
            $maxId = max($maxId, $lineId);
            $productType = (string) $li['product_type'];
            $isHoney = rts_inv_is_honey_line($productType);
            $category = $isHoney ? 'honey' : 'other';
            $label = rts_inv_product_label($productType, $li['combo_product'] ?? null);
            $variant = $li['honey_variant'] ?? null;
            $qty = (float) $li['quantity'];

            $liter = $ml250 = $ml350 = $gallon = 0.0;
            if ($isHoney) {
                $size = strtolower((string) ($li['honey_size'] ?? ''));
                match ($size) {
                    'liter' => $liter = $qty,
                    '250ml' => $ml250 = $qty,
                    '350ml' => $ml350 = $qty,
                    'gallon' => $gallon = $qty,
                    default => $liter = $qty,
                };
            }

            $pool = ($hasLineRole && (string) ($li['line_role'] ?? 'reusable') === 'damaged') ? 'damaged' : 'sellable';
            if ($hasInvPool) {
                $ins->execute([
                    $lineId,
                    $category,
                    $pool,
                    $label,
                    $variant,
                    $liter, $liter,
                    $ml250, $ml250,
                    $ml350, $ml350,
                    $gallon, $gallon,
                    $isHoney ? 0 : $qty,
                    $isHoney ? 0 : $qty,
                ]);
            } else {
                $ins->execute([
                    $lineId,
                    $category,
                    $label,
                    $variant,
                    $liter, $liter,
                    $ml250, $ml250,
                    $ml350, $ml350,
                    $gallon, $gallon,
                    $isHoney ? 0 : $qty,
                    $isHoney ? 0 : $qty,
                ]);
            }
            $synced++;
        }

        $pdo->prepare(
            'UPDATE rts_sync_state SET last_line_item_id = ?, last_synced_at = NOW(), last_error = NULL WHERE id = 1'
        )->execute([$maxId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pdo->prepare('UPDATE rts_sync_state SET last_error = ? WHERE id = 1')->execute([$e->getMessage()]);

        throw $e;
    }

    $state = $pdo->query('SELECT last_synced_at, last_error FROM rts_sync_state WHERE id = 1')->fetch();

    return [
        'synced' => $synced,
        'last_synced_at' => $state['last_synced_at'] ?? null,
        'last_error' => $state['last_error'] ?? null,
    ];
}

/**
 * True when no RTS stock was sold, used, or adjusted from this mirrored row.
 *
 * @param array<string, mixed> $row
 */
function rts_inventory_row_is_untouched(array $row): bool
{
    $pairs = [
        ['current_liter', 'initial_liter'],
        ['current_250ml', 'initial_250ml'],
        ['current_350ml', 'initial_350ml'],
        ['current_gallon', 'initial_gallon'],
        ['current_qty', 'initial_qty'],
    ];
    foreach ($pairs as [$cur, $init]) {
        if (abs((float) ($row[$cur] ?? 0) - (float) ($row[$init] ?? 0)) > 0.0001) {
            return false;
        }
    }

    return true;
}

/**
 * Replace all product lines on a case (edit flow).
 * Drops synced inventory rows first when still untouched so FK does not block line delete.
 *
 * @param list<array<string, mixed>> $lines
 */
function rts_case_replace_line_items(PDO $pdo, int $caseId, array $lines): void
{
    $st = $pdo->prepare(
        'SELECT ir.id, ir.source_line_item_id,
                ir.current_liter, ir.initial_liter,
                ir.current_250ml, ir.initial_250ml,
                ir.current_350ml, ir.initial_350ml,
                ir.current_gallon, ir.initial_gallon,
                ir.current_qty, ir.initial_qty
         FROM rts_inventory_rows ir
         INNER JOIN rts_line_items li ON li.id = ir.source_line_item_id
         WHERE li.case_id = ?'
    );
    $st->execute([$caseId]);
    $invRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($invRows as $ir) {
        $rowId = (int) $ir['id'];
        if (!rts_inventory_row_is_untouched($ir)) {
            throw new RuntimeException(
                'Cannot change product lines — RTS stock from this case was already used or sold.'
            );
        }
        $salesSt = $pdo->prepare('SELECT COUNT(*) FROM honey_sales_log WHERE rts_inventory_row_id = ?');
        $salesSt->execute([$rowId]);
        if ((int) $salesSt->fetchColumn() > 0) {
            throw new RuntimeException(
                'Cannot change product lines — RTS stock from this case was already sold.'
            );
        }
        try {
            $useSt = $pdo->prepare('SELECT COUNT(*) FROM rts_damaged_use_events WHERE rts_inventory_row_id = ?');
            $useSt->execute([$rowId]);
            if ((int) $useSt->fetchColumn() > 0) {
                throw new RuntimeException(
                    'Cannot change product lines — damaged stock from this case was already used.'
                );
            }
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), "doesn't exist")) {
                throw $e;
            }
        }
        $pdo->prepare('DELETE FROM rts_inventory_rows WHERE id = ?')->execute([$rowId]);
    }

    $pdo->prepare('DELETE FROM rts_line_items WHERE case_id = ?')->execute([$caseId]);
    rts_line_items_insert_batch($pdo, $caseId, $lines);
}

function rts_inventory_sync_status(PDO $pdo): array
{
    $state = $pdo->query('SELECT last_synced_at, last_error, last_line_item_id FROM rts_sync_state WHERE id = 1')->fetch();

    return [
        'last_synced_at' => $state['last_synced_at'] ?? null,
        'last_error' => $state['last_error'] ?? null,
        'last_line_item_id' => (int) ($state['last_line_item_id'] ?? 0),
    ];
}

function rts_inventory_pool_filter_sql(PDO $pdo, string $pool): string
{
    if (!rts_db_has_column($pdo, 'rts_inventory_rows', 'inventory_pool')) {
        return $pool === 'damaged' ? ' AND 1=0' : '';
    }
    $pool = $pool === 'damaged' ? 'damaged' : 'sellable';

    return " AND inventory_pool = '{$pool}'";
}

/** @return list<array<string, mixed>> */
function rts_inventory_list_honey_aggregated(PDO $pdo, string $pool = 'sellable'): array
{
    $poolSql = rts_inventory_pool_filter_sql($pdo, $pool);
    $st = $pdo->query(
        "SELECT
            COALESCE(NULLIF(TRIM(variant), ''), 'other') AS variant,
            MIN(product_label) AS product_label,
            'honey' AS category,
            COALESCE(SUM(current_liter), 0) AS current_liter,
            COALESCE(SUM(initial_liter), 0) AS initial_liter,
            COALESCE(SUM(current_250ml), 0) AS current_250ml,
            COALESCE(SUM(initial_250ml), 0) AS initial_250ml,
            COALESCE(SUM(current_350ml), 0) AS current_350ml,
            COALESCE(SUM(initial_350ml), 0) AS initial_350ml,
            COALESCE(SUM(current_gallon), 0) AS current_gallon,
            COALESCE(SUM(initial_gallon), 0) AS initial_gallon,
            COUNT(*) AS line_count,
            MIN(id) AS first_row_id
         FROM rts_inventory_rows
         WHERE category = 'honey'{$poolSql}
         GROUP BY COALESCE(NULLIF(TRIM(variant), ''), 'other')
         HAVING (current_liter + current_250ml + current_350ml + current_gallon) > 0
            OR (initial_liter + initial_250ml + initial_350ml + initial_gallon) > 0
         ORDER BY FIELD(variant, 'light', 'amber', 'dark', 'other'), variant"
    );

    return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
}

/** @return list<array<string, mixed>> */
function rts_inventory_list_other_aggregated(PDO $pdo, string $pool = 'sellable'): array
{
    $poolSql = rts_inventory_pool_filter_sql($pdo, $pool);
    $st = $pdo->query(
        "SELECT
            product_label,
            'other' AS category,
            NULL AS variant,
            COALESCE(SUM(current_qty), 0) AS current_qty,
            COALESCE(SUM(initial_qty), 0) AS initial_qty,
            COUNT(*) AS line_count,
            MIN(id) AS first_row_id
         FROM rts_inventory_rows
         WHERE category = 'other'{$poolSql}
         GROUP BY product_label
         HAVING current_qty > 0 OR initial_qty > 0
         ORDER BY product_label ASC"
    );

    return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * FIFO deduct across all honey rows for a variant (sell / parcel).
 *
 * @param array<string, float|int|string> $sizeQtys
 */
function rts_inventory_deduct_sell_honey_variant(
    PDO $pdo,
    int $userId,
    string $variant,
    array $sizeQtys,
    string $saleType,
    string $pool = 'sellable'
): void {
    $variant = strtolower(trim($variant));
    if ($variant === '') {
        throw new RuntimeException('Variant is required.');
    }
    if (!in_array($saleType, ['walk_in', 'parcel'], true)) {
        throw new RuntimeException('Invalid sale type.');
    }

    require_once __DIR__ . '/honey_inventory.php';

    $hasQty = false;
    foreach (HONEY_SIZES as $size) {
        if ((float) ($sizeQtys[$size] ?? 0) > 0) {
            $hasQty = true;
            break;
        }
    }
    if (!$hasQty) {
        throw new RuntimeException('Enter quantity for at least one size.');
    }

    $poolSql = rts_inventory_pool_filter_sql($pdo, $pool);
    $pdo->beginTransaction();
    try {
        foreach (HONEY_SIZES as $size) {
            $need = (float) ($sizeQtys[$size] ?? 0);
            if ($need <= 0) {
                continue;
            }
            [$curCol] = honey_packaging_size_columns($size);
            $remaining = $need;

            while ($remaining > 0.0001) {
                $st = $pdo->prepare(
                    "SELECT id, {$curCol} AS on_hand, product_label, variant
                     FROM rts_inventory_rows
                     WHERE category = 'honey'{$poolSql}
                       AND LOWER(TRIM(COALESCE(variant, ''))) = ?
                       AND {$curCol} > 0
                     ORDER BY id ASC
                     LIMIT 1"
                );
                $st->execute([$variant]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    throw new RuntimeException("Not enough {$size} for {$variant}.");
                }
                $rowId = (int) $row['id'];
                $take = min($remaining, (float) $row['on_hand']);
                $pdo->prepare("UPDATE rts_inventory_rows SET {$curCol} = {$curCol} - ? WHERE id = ?")
                    ->execute([$take, $rowId]);

                $logQtys = ['liter' => 0.0, '250ml' => 0.0, '350ml' => 0.0, 'gallon' => 0.0];
                $logQtys[$size] = $take;
                $pdo->prepare(
                    'INSERT INTO honey_sales_log (source, rts_inventory_row_id, variant, qty_liter, qty_250ml, qty_350ml, qty_gallon, product_label, sale_type, sold_by)
                     VALUES ("rts", ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $rowId,
                    $row['variant'],
                    $logQtys['liter'],
                    $logQtys['250ml'],
                    $logQtys['350ml'],
                    $logQtys['gallon'],
                    $row['product_label'],
                    $saleType,
                    $userId,
                ]);

                $remaining -= $take;
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * FIFO deduct other-product RTS stock by product label.
 */
function rts_inventory_deduct_sell_other_label(
    PDO $pdo,
    int $userId,
    string $productLabel,
    float $qty,
    string $saleType,
    string $pool = 'sellable'
): void {
    $productLabel = trim($productLabel);
    if ($productLabel === '' || $qty <= 0) {
        throw new RuntimeException('Invalid quantity.');
    }
    if (!in_array($saleType, ['walk_in', 'parcel'], true)) {
        throw new RuntimeException('Invalid sale type.');
    }

    $poolSql = rts_inventory_pool_filter_sql($pdo, $pool);
    $pdo->beginTransaction();
    try {
        $remaining = $qty;
        while ($remaining > 0.0001) {
            $st = $pdo->prepare(
                "SELECT id, current_qty, product_label
                 FROM rts_inventory_rows
                 WHERE category = 'other'{$poolSql}
                   AND product_label = ?
                   AND current_qty > 0
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $st->execute([$productLabel]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new RuntimeException('Not enough stock for ' . $productLabel . '.');
            }
            $rowId = (int) $row['id'];
            $take = min($remaining, (float) $row['current_qty']);
            $pdo->prepare('UPDATE rts_inventory_rows SET current_qty = current_qty - ? WHERE id = ?')
                ->execute([$take, $rowId]);
            $pdo->prepare(
                'INSERT INTO honey_sales_log (source, rts_inventory_row_id, qty_other, product_label, sale_type, sold_by)
                 VALUES ("rts", ?, ?, ?, ?, ?)'
            )->execute([$rowId, $take, $row['product_label'], $saleType, $userId]);
            $remaining -= $take;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function rts_inventory_deduct_sell(PDO $pdo, int $userId, int $rowId, array $sizeQtys, ?float $qtyOther, string $saleType): array
{
    $st = $pdo->prepare('SELECT * FROM rts_inventory_rows WHERE id = ? LIMIT 1');
    $st->execute([$rowId]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('RTS inventory row not found.');
    }
    if (rts_db_has_column($pdo, 'rts_inventory_rows', 'inventory_pool')
        && (string) ($row['inventory_pool'] ?? 'sellable') === 'damaged') {
        throw new RuntimeException('Damaged stock cannot be sold — use Damaged stock workflow.');
    }
    if (!in_array($saleType, ['walk_in', 'parcel'], true)) {
        throw new RuntimeException('Invalid sale type.');
    }

    $pdo->beginTransaction();
    try {
        if ($row['category'] === 'honey') {
            require_once __DIR__ . '/honey_inventory.php';
            $deducts = [];
            foreach (HONEY_SIZES as $size) {
                $q = (float) ($sizeQtys[$size] ?? 0);
                if ($q <= 0) {
                    continue;
                }
                [$curCol] = honey_packaging_size_columns($size);
                if ($q > (float) $row[$curCol]) {
                    throw new RuntimeException("Not enough {$size}.");
                }
                $pdo->prepare("UPDATE rts_inventory_rows SET {$curCol} = {$curCol} - ? WHERE id = ?")->execute([$q, $rowId]);
                $deducts[$size] = $q;
            }
            if (!$deducts) {
                throw new RuntimeException('Enter quantity for at least one size.');
            }
            $pdo->prepare(
                'INSERT INTO honey_sales_log (source, rts_inventory_row_id, variant, qty_liter, qty_250ml, qty_350ml, qty_gallon, product_label, sale_type, sold_by)
                 VALUES ("rts", ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $rowId,
                $row['variant'],
                $deducts['liter'] ?? 0,
                $deducts['250ml'] ?? 0,
                $deducts['350ml'] ?? 0,
                $deducts['gallon'] ?? 0,
                $row['product_label'],
                $saleType,
                $userId,
            ]);
        } else {
            $q = (float) ($qtyOther ?? 0);
            if ($q <= 0 || $q > (float) $row['current_qty']) {
                throw new RuntimeException('Invalid quantity.');
            }
            $pdo->prepare('UPDATE rts_inventory_rows SET current_qty = current_qty - ? WHERE id = ?')->execute([$q, $rowId]);
            $pdo->prepare(
                'INSERT INTO honey_sales_log (source, rts_inventory_row_id, qty_other, product_label, sale_type, sold_by)
                 VALUES ("rts", ?, ?, ?, ?, ?)'
            )->execute([$rowId, $q, $row['product_label'], $saleType, $userId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return ['ok' => true];
}
