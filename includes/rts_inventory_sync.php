<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function rts_inv_product_label(string $productType, ?string $combo): string
{
    $map = [
        'honey' => 'Honey',
        'honey_with_other' => 'Honey w/ Other',
        'pouch_pt' => 'Paragis',
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

    $st = $pdo->prepare(
        'SELECT li.id, li.product_type, li.honey_size, li.honey_variant, li.combo_product, li.quantity
         FROM rts_line_items li
         INNER JOIN rts_cases c ON c.id = li.case_id
         WHERE c.voided_at IS NULL AND li.id > ?
         ORDER BY li.id ASC'
    );
    $st->execute([$lastId]);
    $lines = $st->fetchAll();

    $ins = $pdo->prepare(
        'INSERT INTO rts_inventory_rows (
            source_line_item_id, category, product_label, variant,
            current_liter, initial_liter, current_250ml, initial_250ml,
            current_350ml, initial_350ml, current_gallon, initial_gallon,
            current_qty, initial_qty, synced_at
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );

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

function rts_inventory_sync_status(PDO $pdo): array
{
    $state = $pdo->query('SELECT last_synced_at, last_error, last_line_item_id FROM rts_sync_state WHERE id = 1')->fetch();

    return [
        'last_synced_at' => $state['last_synced_at'] ?? null,
        'last_error' => $state['last_error'] ?? null,
        'last_line_item_id' => (int) ($state['last_line_item_id'] ?? 0),
    ];
}

function rts_inventory_deduct_sell(PDO $pdo, int $userId, int $rowId, array $sizeQtys, ?float $qtyOther, string $saleType): array
{
    $st = $pdo->prepare('SELECT * FROM rts_inventory_rows WHERE id = ? LIMIT 1');
    $st->execute([$rowId]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('RTS inventory row not found.');
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
