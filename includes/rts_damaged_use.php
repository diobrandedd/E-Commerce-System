<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/catalog_bootstrap.php';
require_once __DIR__ . '/honey_volume.php';
require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/dept_production.php';
require_once __DIR__ . '/rts_inventory_sync.php';

/**
 * @param list<array{size:string, qty:float}> $deductLines
 */
function rts_damaged_total_bottles(array $deductLines): float
{
    $total = 0.0;
    foreach ($deductLines as $line) {
        $total += (float) ($line['qty'] ?? 0);
    }

    return $total;
}

/** @return list<array<string, mixed>> */
function rts_damaged_deduct_cap_seals(PDO $pdo, int $userId, float $bottleCount, string $label): array
{
    if ($bottleCount <= 0) {
        throw new RuntimeException('Enter at least one bottle to re-seal.');
    }
    $deptSt = $pdo->query("SELECT id FROM departments WHERE name IN ('honey', 'Honey') ORDER BY id ASC LIMIT 1");
    $deptId = $deptSt ? (int) $deptSt->fetchColumn() : 0;
    if ($deptId < 1) {
        throw new RuntimeException('Honey department not found.');
    }
    $st = $pdo->prepare(
        'SELECT id FROM stocks WHERE dept_id = ? AND name = ? AND supplier_id IS NULL AND production_deduct = 1 LIMIT 1'
    );
    $st->execute([$deptId, CATALOG_HONEY_CAP_SEAL_STOCK_NAME]);
    $stockId = (int) $st->fetchColumn();
    if ($stockId < 1) {
        throw new RuntimeException('Honey cap seal stock not found in packaging materials.');
    }
    $res = dept_packaging_deduct($pdo, $stockId, $bottleCount, $userId, $label);

    return [[
        'stock_name' => CATALOG_HONEY_CAP_SEAL_STOCK_NAME,
        'qty' => $bottleCount,
        'on_hand' => $res['on_hand'],
    ]];
}

/**
 * Credit resealed honey bottles into sellable RTS inventory (repack bucket — no line-item source).
 *
 * @param list<array{size:string, qty:float}> $deductLines
 */
function rts_inventory_credit_sellable_repack(PDO $pdo, array $damagedRow, array $deductLines): int
{
    $hasPool = rts_db_has_column($pdo, 'rts_inventory_rows', 'inventory_pool');
    $category = (string) $damagedRow['category'];
    $label = (string) $damagedRow['product_label'];
    $variant = $damagedRow['variant'] ?? null;

    $liter = $ml250 = $ml350 = $gallon = 0.0;
    foreach ($deductLines as $line) {
        $qty = (float) ($line['qty'] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        match (strtolower((string) ($line['size'] ?? ''))) {
            'liter' => $liter += $qty,
            '250ml' => $ml250 += $qty,
            '350ml' => $ml350 += $qty,
            'gallon' => $gallon += $qty,
            default => $liter += $qty,
        };
    }

    $poolSql = $hasPool ? " AND inventory_pool = 'sellable'" : '';
    $st = $pdo->prepare(
        'SELECT id FROM rts_inventory_rows
         WHERE category = ? AND product_label = ? AND (variant <=> ?)
           AND source_line_item_id IS NULL' . $poolSql . '
         LIMIT 1'
    );
    $st->execute([$category, $label, $variant]);
    $existingId = $st->fetchColumn();

    if ($existingId !== false) {
        $rowId = (int) $existingId;
        $pdo->prepare(
            'UPDATE rts_inventory_rows SET
                current_liter = current_liter + ?, initial_liter = initial_liter + ?,
                current_250ml = current_250ml + ?, initial_250ml = initial_250ml + ?,
                current_350ml = current_350ml + ?, initial_350ml = initial_350ml + ?,
                current_gallon = current_gallon + ?, initial_gallon = initial_gallon + ?,
                synced_at = NOW()
             WHERE id = ?'
        )->execute([$liter, $liter, $ml250, $ml250, $ml350, $ml350, $gallon, $gallon, $rowId]);

        return $rowId;
    }

    if ($hasPool) {
        $ins = $pdo->prepare(
            'INSERT INTO rts_inventory_rows (
                source_line_item_id, category, inventory_pool, product_label, variant,
                current_liter, initial_liter, current_250ml, initial_250ml,
                current_350ml, initial_350ml, current_gallon, initial_gallon,
                current_qty, initial_qty, synced_at
             ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())'
        );
        $ins->execute([
            $category,
            'sellable',
            $label,
            $variant,
            $liter, $liter,
            $ml250, $ml250,
            $ml350, $ml350,
            $gallon, $gallon,
        ]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO rts_inventory_rows (
                source_line_item_id, category, product_label, variant,
                current_liter, initial_liter, current_250ml, initial_250ml,
                current_350ml, initial_350ml, current_gallon, initial_gallon,
                current_qty, initial_qty, synced_at
             ) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())'
        );
        $ins->execute([
            $category,
            $label,
            $variant,
            $liter, $liter,
            $ml250, $ml250,
            $ml350, $ml350,
            $gallon, $gallon,
        ]);
    }

    return (int) $pdo->lastInsertId();
}

/**
 * Deduct quantities from a damaged RTS inventory row and apply mix_production, cap_reseal, or scrap.
 *
 * @param array<string, mixed> $input
 * @return array{outcome:string, volume_gallons:?float, production_row_id:?int, sellable_row_id:?int, bottles_resealed:?float, packaging_deduct:?list<array<string,mixed>>}
 */
function rts_damaged_use_apply(PDO $pdo, int $userId, int $rowId, string $outcome, array $input): array
{
    if (!in_array($outcome, ['mix_production', 'scrap', 'cap_reseal'], true)) {
        throw new RuntimeException('Invalid outcome.');
    }

    $st = $pdo->prepare('SELECT * FROM rts_inventory_rows WHERE id = ? LIMIT 1');
    $st->execute([$rowId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('RTS inventory row not found.');
    }
    if (rts_db_has_column($pdo, 'rts_inventory_rows', 'inventory_pool')
        && (string) ($row['inventory_pool'] ?? 'sellable') !== 'damaged') {
        throw new RuntimeException('This row is not in the damaged pool.');
    }

    $sizeQtys = is_array($input['size_qtys'] ?? null) ? $input['size_qtys'] : [];
    $qtyOther = isset($input['qty_other']) ? (float) $input['qty_other'] : null;
    $notes = isset($input['notes']) ? trim((string) $input['notes']) : '';
    $targetVariant = isset($input['variant']) ? trim((string) $input['variant']) : '';
    $volumeGallons = null;
    $productionRowId = null;
    $sellableRowId = null;
    $bottlesResealed = null;
    $packagingDeduct = [];

    if (in_array($outcome, ['mix_production', 'cap_reseal'], true) && $row['category'] !== 'honey') {
        throw new RuntimeException('This outcome is only available for honey damaged stock.');
    }
    if ($outcome === 'mix_production') {
        if (!in_array($targetVariant, ['light', 'amber', 'dark', 'other'], true)) {
            throw new RuntimeException('Select a honey variant for production mix.');
        }
    }

    $pdo->beginTransaction();
    try {
        $deductLines = [];
        if ($row['category'] === 'honey') {
            foreach (HONEY_SIZES as $size) {
                $q = (float) ($sizeQtys[$size] ?? 0);
                if ($q <= 0) {
                    continue;
                }
                [$curCol] = honey_packaging_size_columns($size);
                if ($q > (float) $row[$curCol]) {
                    throw new RuntimeException("Not enough {$size} on this damaged row.");
                }
                $pdo->prepare("UPDATE rts_inventory_rows SET {$curCol} = {$curCol} - ? WHERE id = ?")->execute([$q, $rowId]);
                $deductLines[] = ['size' => $size, 'qty' => $q];
            }
            if ($deductLines === []) {
                throw new RuntimeException('Enter quantity for at least one bottle size.');
            }
        } else {
            $q = (float) ($qtyOther ?? 0);
            if ($q <= 0 || $q > (float) $row['current_qty']) {
                throw new RuntimeException('Invalid quantity.');
            }
            $pdo->prepare('UPDATE rts_inventory_rows SET current_qty = current_qty - ? WHERE id = ?')->execute([$q, $rowId]);
        }

        if ($outcome === 'mix_production') {
            $volumeGallons = honey_lines_to_gallons($deductLines);
            if ($volumeGallons <= 0) {
                throw new RuntimeException('Could not compute honey volume from deducted quantities.');
            }
            $prodSt = $pdo->prepare(
                'SELECT id FROM honey_production_rows WHERE variant = ? ORDER BY id DESC LIMIT 1'
            );
            $prodSt->execute([$targetVariant]);
            $prodId = $prodSt->fetchColumn();
            if ($prodId === false) {
                throw new RuntimeException(
                    "No honey production pool exists for variant \"{$targetVariant}\". Create one in Honey Production first."
                );
            }
            $productionRowId = (int) $prodId;
            $pdo->prepare(
                'UPDATE honey_production_rows SET current_qty = current_qty + ?, initial_qty = initial_qty + ? WHERE id = ?'
            )->execute([$volumeGallons, $volumeGallons, $productionRowId]);
        } elseif ($outcome === 'cap_reseal') {
            $bottlesResealed = rts_damaged_total_bottles($deductLines);
            $packagingDeduct = rts_damaged_deduct_cap_seals(
                $pdo,
                $userId,
                $bottlesResealed,
                'rts cap reseal: ' . substr((string) $row['product_label'], 0, 80)
            );
            $sellableRowId = rts_inventory_credit_sellable_repack($pdo, $row, $deductLines);
        }

        if (rts_db_has_column($pdo, 'rts_damaged_use_events', 'id') || $pdo->query("SHOW TABLES LIKE 'rts_damaged_use_events'")->fetch()) {
            $pdo->prepare(
                'INSERT INTO rts_damaged_use_events
                 (rts_inventory_row_id, outcome, variant, volume_gallons, size_qtys_json, qty_other, notes, production_row_id, performed_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $rowId,
                $outcome,
                $outcome === 'mix_production' ? $targetVariant : null,
                $volumeGallons,
                $deductLines !== [] ? json_encode($deductLines, JSON_UNESCAPED_UNICODE) : null,
                $row['category'] === 'honey' ? null : ($qtyOther ?? 0),
                $notes !== '' ? $notes : null,
                $productionRowId,
                $userId,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'outcome' => $outcome,
        'volume_gallons' => $volumeGallons,
        'production_row_id' => $productionRowId,
        'sellable_row_id' => $sellableRowId,
        'bottles_resealed' => $bottlesResealed,
        'packaging_deduct' => $packagingDeduct,
    ];
}
