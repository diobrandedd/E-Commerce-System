<?php

declare(strict_types=1);

require_once __DIR__ . '/material_inventory.php';
require_once __DIR__ . '/parcel_pack_costing.php';
require_once __DIR__ . '/rts_schema.php';

function fulfillment_packing_bubble_generic_stock_name(): string
{
    return 'Bubble wrap roll';
}

/** @return list<int> */
function fulfillment_packing_bubble_widths_cm(): array
{
    return [13, 17, 22];
}

function fulfillment_packing_is_bubble_generic_name(string $name): bool
{
    return strtolower(trim($name)) === strtolower(fulfillment_packing_bubble_generic_stock_name());
}

function fulfillment_packing_is_bubble_sized_name(string $name): bool
{
    return (bool) preg_match('/^bubble wrap roll - \d+ cm$/i', trim($name));
}

function fulfillment_packing_bubble_sized_stock_name(int $widthCm): string
{
    return 'Bubble wrap roll - ' . $widthCm . ' cm';
}

function fulfillment_packing_bubble_sized_stock_id(PDO $pdo, int $widthCm): int
{
    if (!in_array($widthCm, fulfillment_packing_bubble_widths_cm(), true)) {
        throw new RuntimeException('Bubble width must be 13, 17, or 22 cm.');
    }
    $name = fulfillment_packing_bubble_sized_stock_name($widthCm);
    $st = $pdo->prepare(
        'SELECT id FROM stocks WHERE supplier_id IS NULL AND name = ? AND is_active = 1 LIMIT 1'
    );
    $st->execute([$name]);
    $id = $st->fetchColumn();
    if ($id === false) {
        throw new RuntimeException('Sized bubble SKU not found: ' . $name);
    }

    return (int) $id;
}

function fulfillment_packing_foam_generic_stock_name(): string
{
    return 'Insulation foam roll';
}

function fulfillment_packing_is_foam_generic_name(string $name): bool
{
    return strtolower(trim($name)) === strtolower(fulfillment_packing_foam_generic_stock_name());
}

function fulfillment_packing_is_foam_sized_name(string $name): bool
{
    return (bool) preg_match('/^insulation foam roll - /i', trim($name));
}

function fulfillment_packing_foam_generic_stock_id(PDO $pdo): int
{
    $name = fulfillment_packing_foam_generic_stock_name();
    $st = $pdo->prepare(
        'SELECT id FROM stocks WHERE supplier_id IS NULL AND LOWER(TRIM(name)) = LOWER(?) AND is_active = 1 LIMIT 1'
    );
    $st->execute([$name]);
    $id = $st->fetchColumn();
    if ($id === false) {
        throw new RuntimeException('Generic insulation foam roll SKU not found.');
    }

    return (int) $id;
}

function material_inventory_role_is_tl(string $role): bool
{
    return in_array($role, ['tl', 'csr_tl'], true);
}

function fulfillment_packing_staging_remaining(float $issued, float $consumed, float $damaged): float
{
    return round(max(0, $issued - $consumed - $damaged), 4);
}

/** @param array<string, mixed> $row */
function fulfillment_packing_bubble_piece_yield_from_row(array $row): ?float
{
    if (!fulfillment_packing_is_bubble_sized_name((string) ($row['name'] ?? ''))) {
        return null;
    }
    $yield = (float) ($row['pool_yield_qty'] ?? 0);

    return $yield > 0 ? $yield : null;
}

function fulfillment_packing_staging_piece_qty(float $rollQty, float $yield): float
{
    return round($rollQty * $yield, 2);
}

function fulfillment_packing_pieces_to_staging_rolls(float $pieces, float $yield): float
{
    if ($yield <= 0) {
        throw new RuntimeException('Bubble cut yield is not configured.');
    }

    return material_fulfillment_snap_sixteenths($pieces / $yield);
}

/**
 * Staging balance row for API/UI — sized bubble is shown in cut pieces (pc), stored internally as rolls.
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function fulfillment_packing_staging_balance_display_row(
    array $row,
    float $issued,
    float $consumed,
    float $damaged
): array {
    $name = (string) ($row['name'] ?? '');
    $yield = fulfillment_packing_bubble_piece_yield_from_row($row);
    $remainingRoll = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);
    $isBubbleSized = fulfillment_packing_is_bubble_sized_name($name);

    $out = [
        'stock_id' => (int) ($row['stock_id'] ?? 0),
        'name' => $name,
        'warehouse_on_hand' => round((float) ($row['warehouse_on_hand'] ?? 0), 4),
        'is_bubble_sized' => $isBubbleSized,
        'pool_yield_qty' => $yield,
    ];

    if ($yield !== null) {
        $out['unit'] = 'pc';
        $out['issued_qty'] = fulfillment_packing_staging_piece_qty($issued, $yield);
        $out['consumed_qty'] = fulfillment_packing_staging_piece_qty($consumed, $yield);
        $out['damaged_qty'] = fulfillment_packing_staging_piece_qty($damaged, $yield);
        $out['remaining_qty'] = fulfillment_packing_staging_piece_qty($remainingRoll, $yield);
        $out['issued_roll_qty'] = $issued;
        $out['remaining_roll_qty'] = $remainingRoll;
    } else {
        $out['unit'] = (string) ($row['unit'] ?? 'pcs');
        $out['issued_qty'] = $issued;
        $out['consumed_qty'] = $consumed;
        $out['damaged_qty'] = $damaged;
        $out['remaining_qty'] = $remainingRoll;
    }

    return $out;
}

/**
 * @param array<string, mixed> $stockRow
 * @return array{qty: float, unit: string, roll_qty: float|null}
 */
function fulfillment_packing_issue_line_display(array $stockRow, float $rollQty, string $unit): array
{
    $yield = fulfillment_packing_bubble_piece_yield_from_row($stockRow);
    if ($yield !== null && $unit === 'roll') {
        return [
            'qty' => fulfillment_packing_staging_piece_qty($rollQty, $yield),
            'unit' => 'pc',
            'roll_qty' => $rollQty,
        ];
    }

    return [
        'qty' => $rollQty,
        'unit' => $unit,
        'roll_qty' => null,
    ];
}

function fulfillment_packing_is_fulfillment_stock_name(string $name): bool
{
    $n = strtolower(trim($name));
    if ($n === '') {
        return false;
    }

    return str_starts_with($n, 'bubble wrap roll')
        || str_starts_with($n, 'cushion piece')
        || str_starts_with($n, 'cushion roll')
        || str_starts_with($n, 'courier pouch')
        || str_starts_with($n, 'insulation foam roll')
        || str_starts_with($n, 'cardboard box');
}

function fulfillment_packing_tables_ready(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $pdo->query('SELECT 1 FROM fulfillment_packing_balances LIMIT 1');
        $ready = true;
    } catch (Throwable) {
        $ready = false;
    }

    return $ready;
}

function material_inventory_role_can_issue_packing(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'tl', 'team_leader', 'csr_tl'], true);
}

function material_inventory_role_can_edit_packaging_price(string $role): bool
{
    return in_array($role, ['executive', 'hr'], true);
}

function material_inventory_role_can_view_packing_staging(string $role): bool
{
    return material_inventory_role_can_issue_packing($role);
}

function material_inventory_role_can_view_on_hold_parcels(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager', 'csr_tl', 'csr_staff'], true);
}

/** @return array{issued_qty: float, consumed_qty: float, remaining_qty: float}|null */
function fulfillment_packing_get_balance(PDO $pdo, int $stockId): ?array
{
    if (!fulfillment_packing_tables_ready($pdo)) {
        return null;
    }
    $hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');
    $cols = $hasDamaged ? 'issued_qty, consumed_qty, damaged_qty' : 'issued_qty, consumed_qty';
    $st = $pdo->prepare(
        "SELECT {$cols} FROM fulfillment_packing_balances WHERE stock_id = ? LIMIT 1"
    );
    $st->execute([$stockId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return ['issued_qty' => 0.0, 'consumed_qty' => 0.0, 'damaged_qty' => 0.0, 'remaining_qty' => 0.0];
    }
    $issued = round((float) ($row['issued_qty'] ?? 0), 4);
    $consumed = round((float) ($row['consumed_qty'] ?? 0), 4);
    $damaged = $hasDamaged ? round((float) ($row['damaged_qty'] ?? 0), 4) : 0.0;

    return [
        'issued_qty' => $issued,
        'consumed_qty' => $consumed,
        'damaged_qty' => $damaged,
        'remaining_qty' => fulfillment_packing_staging_remaining($issued, $consumed, $damaged),
    ];
}

/**
 * Expand issue lines for parcel estimate (generic bubble → sized staging stock).
 *
 * @param list<array<string,mixed>> $lines
 * @return list<array{stock_id: int, qty: float, unit: string}>
 */
function fulfillment_packing_estimate_lines(PDO $pdo, array $lines): array
{
    $expanded = [];
    foreach ($lines as $line) {
        $stockId = (int) ($line['stock_id'] ?? 0);
        $qty = (float) ($line['qty'] ?? 0);
        if ($stockId < 1 || $qty <= 0) {
            continue;
        }
        $row = material_inventory_fetch_stock($pdo, $stockId);
        if ($row === null) {
            continue;
        }
        $name = (string) $row['name'];
        if (fulfillment_packing_is_bubble_generic_name($name)) {
            $width = (int) ($line['bubble_width_cm'] ?? 0);
            if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {
                continue;
            }
            $stockId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);
        } elseif (fulfillment_packing_is_bubble_sized_name($name)) {
            continue;
        } elseif (fulfillment_packing_is_foam_sized_name($name)) {
            continue;
        }
        $expanded[] = [
            'stock_id' => $stockId,
            'qty' => $qty,
            'unit' => (string) ($line['unit'] ?? $row['unit'] ?? 'pcs'),
        ];
    }

    return $expanded;
}

/**
 * @param list<array{stock_id: int, qty: float, unit: string}> $lines
 * @return array{est_parcels: int, est_label: string, bottleneck_stock_id: int|null, per_tier: list<array<string,mixed>>}
 */
function fulfillment_packing_estimate_parcels(PDO $pdo, array $lines): array
{
    $lines = fulfillment_packing_estimate_lines($pdo, $lines);
    if ($lines === [] || !parcel_pack_tables_ready($pdo)) {
        return ['est_parcels' => 0, 'est_label' => '', 'bottleneck_stock_id' => null, 'per_tier' => []];
    }

    $qtyByStock = [];
    foreach ($lines as $line) {
        $sid = (int) ($line['stock_id'] ?? 0);
        $qty = (float) ($line['qty'] ?? 0);
        if ($sid < 1 || $qty <= 0) {
            continue;
        }
        $qtyByStock[$sid] = ($qtyByStock[$sid] ?? 0) + $qty;
    }
    if ($qtyByStock === []) {
        return ['est_parcels' => 0, 'est_label' => '', 'bottleneck_stock_id' => null, 'per_tier' => []];
    }

    $referenceKeys = [
        'honey_1x_1L',
        'honey_350ml_solo',
        'dept_pt_1_2',
        'dept_pt_3_5',
        'dept_brc_1_2',
    ];
    $minParcels = PHP_INT_MAX;
    $bestLabel = '';
    $bottleneckStockId = null;
    $perTier = [];

    foreach ($referenceKeys as $tierKey) {
        $tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, true);
        if ($tier === null) {
            continue;
        }
        $tierLines = parcel_pack_fetch_tier_lines($pdo, (int) $tier['id']);
        $tierMax = PHP_INT_MAX;
        $tierBottleneck = null;
        foreach ($tierLines as $tl) {
            $stockId = $tl['stock_id'] ?? null;
            if ($stockId === null || (int) $stockId < 1) {
                continue;
            }
            $sid = (int) $stockId;
            $stockRow = material_inventory_fetch_stock($pdo, $sid);
            if ($stockRow === null) {
                continue;
            }
            $unit = (string) ($tl['unit'] ?? 'pcs');
            $stockUnit = trim((string) ($stockRow['unit'] ?? 'pcs'));
            $stockName = (string) ($stockRow['name'] ?? '');
            $lookupId = $sid;
            if ($unit === 'foam_piece' && fulfillment_packing_is_foam_sized_name($stockName)) {
                try {
                    $lookupId = fulfillment_packing_foam_generic_stock_id($pdo);
                } catch (Throwable) {
                    continue;
                }
            }
            if (!isset($qtyByStock[$lookupId])) {
                continue;
            }
            $need = (float) ($tl['qty_typical'] ?? 0);
            if ($need <= 0) {
                continue;
            }
            $issuedQty = $qtyByStock[$lookupId];
            $consumable = $issuedQty;
            if (in_array($unit, ['bubble_piece', 'foam_piece', 'column'], true) && $stockUnit === 'roll') {
                $yield = (float) ($stockRow['pool_yield_qty'] ?? 0);
                if ($yield > 0) {
                    $consumable = $issuedQty * $yield;
                }
            } elseif ($unit === 'roll' && $stockUnit === 'roll') {
                $consumable = $issuedQty;
            }
            $maxForSku = (int) floor($consumable / $need);
            if ($maxForSku < $tierMax) {
                $tierMax = $maxForSku;
                $tierBottleneck = $sid;
            }
        }
        if ($tierMax === PHP_INT_MAX) {
            continue;
        }
        $perTier[] = [
            'tier_key' => $tierKey,
            'label' => (string) ($tier['label'] ?? $tierKey),
            'max_parcels' => max(0, $tierMax),
        ];
        if ($tierMax < $minParcels) {
            $minParcels = $tierMax;
            $bestLabel = (string) ($tier['label'] ?? $tierKey);
            $bottleneckStockId = $tierBottleneck;
        }
    }

    if ($minParcels === PHP_INT_MAX) {
        return ['est_parcels' => 0, 'est_label' => '', 'bottleneck_stock_id' => null, 'per_tier' => $perTier];
    }

    return [
        'est_parcels' => max(0, $minParcels),
        'est_label' => $bestLabel,
        'bottleneck_stock_id' => $bottleneckStockId,
        'per_tier' => $perTier,
    ];
}

/**
 * @param list<array{stock_id: int, qty: float, unit?: string, bubble_width_cm?: int}> $lines
 * @return array{issue_id: int, estimate: array<string,mixed>, lines: list<array<string,mixed>>}
 */
function fulfillment_packing_issue_create(
    PDO $pdo,
    int $userId,
    array $lines,
    ?string $note = null
): array {
    if (!fulfillment_packing_tables_ready($pdo)) {
        throw new RuntimeException('Packing staging is not available. Run migration phase 34.');
    }
    if ($lines === []) {
        throw new RuntimeException('Add at least one fulfillment SKU line.');
    }

    $normalized = [];
    foreach ($lines as $line) {
        $stockId = (int) ($line['stock_id'] ?? 0);
        $qty = round((float) ($line['qty'] ?? 0), 4);
        if ($stockId < 1 || $qty <= 0) {
            throw new RuntimeException('Each line needs stock_id and qty > 0.');
        }
        $row = material_inventory_fetch_stock($pdo, $stockId, true);
        if ($row === null) {
            throw new RuntimeException('Stock not found.');
        }
        $name = (string) $row['name'];

        if (fulfillment_packing_is_bubble_sized_name($name)) {
            throw new RuntimeException(
                'Issue unsized bubble rolls from warehouse, then pick 13 / 17 / 22 cm for packing.'
            );
        }

        if (fulfillment_packing_is_foam_sized_name($name)) {
            throw new RuntimeException(
                'Issue unsized Insulation foam roll from warehouse. Cut size is applied automatically when shipping cacao parcels.'
            );
        }

        if (fulfillment_packing_is_bubble_generic_name($name)) {
            $width = (int) ($line['bubble_width_cm'] ?? 0);
            if (!in_array($width, fulfillment_packing_bubble_widths_cm(), true)) {
                throw new RuntimeException('Bubble wrap issue requires width 13, 17, or 22 cm.');
            }
            $stagingId = fulfillment_packing_bubble_sized_stock_id($pdo, $width);
            $stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);
            if ($stagingRow === null) {
                throw new RuntimeException('Sized bubble SKU not found.');
            }
            $onHand = round((float) ($row['on_hand'] ?? 0), 4);
            if ($onHand < $qty) {
                throw new RuntimeException(
                    'Insufficient warehouse stock for ' . $name . '. On hand: ' . $onHand
                );
            }
            $normalized[] = [
                'warehouse_stock_id' => $stockId,
                'staging_stock_id' => $stagingId,
                'qty' => $qty,
                'unit' => 'roll',
                'stock_name' => fulfillment_packing_bubble_sized_stock_name($width),
                'dept_id' => (int) $row['dept_id'],
                'bubble_width_cm' => $width,
                'is_bubble_split' => true,
            ];
            continue;
        }

        if (!fulfillment_packing_is_fulfillment_stock_name($name)) {
            throw new RuntimeException('Only fulfillment packaging SKUs can be issued to packing.');
        }
        $onHand = round((float) ($row['on_hand'] ?? 0), 4);
        if ($onHand < $qty) {
            throw new RuntimeException(
                'Insufficient warehouse stock for ' . $name . '. On hand: ' . $onHand
            );
        }
        $normalized[] = [
            'warehouse_stock_id' => $stockId,
            'staging_stock_id' => $stockId,
            'qty' => $qty,
            'unit' => trim((string) ($line['unit'] ?? $row['unit'] ?? 'pcs')),
            'stock_name' => $name,
            'dept_id' => (int) $row['dept_id'],
            'bubble_width_cm' => null,
            'is_bubble_split' => false,
        ];
    }

    $estimate = fulfillment_packing_estimate_parcels($pdo, $lines);
    $note = $note !== null ? trim($note) : null;
    if ($note === '') {
        $note = null;
    }
    if ($note !== null && function_exists('mb_substr')) {
        $note = mb_substr($note, 0, 255);
    } elseif ($note !== null) {
        $note = substr($note, 0, 255);
    }

    $pdo->beginTransaction();
    try {
        $insIssue = $pdo->prepare(
            'INSERT INTO fulfillment_packing_issues
                (issued_by, note, est_parcels_typical, est_parcels_label)
             VALUES (?, ?, ?, ?)'
        );
        $insIssue->execute([
            $userId > 0 ? $userId : null,
            $note,
            $estimate['est_parcels'] > 0 ? (int) $estimate['est_parcels'] : null,
            $estimate['est_label'] !== '' ? $estimate['est_label'] : null,
        ]);
        $issueId = (int) $pdo->lastInsertId();

        $insLine = $pdo->prepare(
            'INSERT INTO fulfillment_packing_issue_lines
                (issue_id, stock_id, source_stock_id, bubble_width_cm, qty, unit)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $outLines = [];

        foreach ($normalized as $line) {
            $warehouseId = (int) $line['warehouse_stock_id'];
            $stagingId = (int) $line['staging_stock_id'];
            $qty = (float) $line['qty'];

            $pdo->prepare('UPDATE stocks SET on_hand = on_hand - ? WHERE id = ?')->execute([$qty, $warehouseId]);

            if (rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty')) {
                $bal = $pdo->prepare(
                    'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty, damaged_qty)
                     VALUES (?, ?, 0, 0)
                     ON DUPLICATE KEY UPDATE issued_qty = issued_qty + VALUES(issued_qty)'
                );
            } else {
                $bal = $pdo->prepare(
                    'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty)
                     VALUES (?, ?, 0)
                     ON DUPLICATE KEY UPDATE issued_qty = issued_qty + VALUES(issued_qty)'
                );
            }
            $bal->execute([$stagingId, $qty]);

            $batchLabel = 'packing_issue: #' . $issueId;
            $pdo->prepare(
                'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $warehouseId,
                (int) $line['dept_id'],
                -$qty,
                date('Y-m-d H:i:s'),
                $batchLabel,
                $userId,
            ]);

            $widthCm = $line['bubble_width_cm'] !== null ? (int) $line['bubble_width_cm'] : null;
            if (rts_db_has_column($pdo, 'fulfillment_packing_issue_lines', 'source_stock_id')) {
                $insLine->execute([
                    $issueId,
                    $stagingId,
                    $line['is_bubble_split'] ? $warehouseId : null,
                    $widthCm,
                    $qty,
                    (string) $line['unit'],
                ]);
            } else {
                $pdo->prepare(
                    'INSERT INTO fulfillment_packing_issue_lines (issue_id, stock_id, qty, unit) VALUES (?, ?, ?, ?)'
                )->execute([$issueId, $stagingId, $qty, (string) $line['unit']]);
            }
            $stagingRow = material_inventory_fetch_stock($pdo, $stagingId, true);
            $lineUnit = (string) $line['unit'];
            $display = $stagingRow !== null
                ? fulfillment_packing_issue_line_display($stagingRow, $qty, $lineUnit)
                : ['qty' => $qty, 'unit' => $lineUnit, 'roll_qty' => null];
            $outLines[] = [
                'stock_id' => $stagingId,
                'stock_name' => (string) $line['stock_name'],
                'qty' => $display['qty'],
                'unit' => $display['unit'],
                'roll_qty' => $display['roll_qty'],
                'bubble_width_cm' => $widthCm,
            ];
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'issue_id' => $issueId,
        'estimate' => $estimate,
        'lines' => $outLines,
    ];
}

/**
 * Consume staging qty for a parcel ship line.
 *
 * @return array{stock_id: int, qty_consumed: float, consumed_qty: float, issued_qty: float}
 */
function fulfillment_packing_consume_line(
    PDO $pdo,
    int $stockId,
    float $qty,
    string $lineUnit,
    string $stockName,
    int $parcelId
): array {
    if (!fulfillment_packing_tables_ready($pdo)) {
        throw new RuntimeException('Packing staging is not available. Run migration phase 34.');
    }
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be greater than 0.');
    }

    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }

    $stockUnit = trim((string) ($row['unit'] ?? 'pcs'));
    $deductQty = round($qty, 4);
    $stagingStockId = $stockId;
    if ($lineUnit === 'foam_piece' && fulfillment_packing_is_foam_sized_name((string) $row['name'])) {
        $stagingStockId = fulfillment_packing_foam_generic_stock_id($pdo);
    }
    if (in_array($lineUnit, ['bubble_piece', 'foam_piece', 'column'], true)) {
        $yield = (float) ($row['pool_yield_qty'] ?? 0);
        if ($yield <= 0) {
            throw new RuntimeException('Roll yield is not configured for ' . (string) $row['name'] . '.');
        }
        $deductQty = material_fulfillment_snap_sixteenths($qty / $yield);
    } elseif ($stockUnit === 'roll') {
        $deductQty = material_fulfillment_snap_sixteenths($qty);
    }

    $hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');
    $balCols = $hasDamaged ? 'issued_qty, consumed_qty, damaged_qty' : 'issued_qty, consumed_qty';
    $st = $pdo->prepare(
        "SELECT {$balCols} FROM fulfillment_packing_balances WHERE stock_id = ? FOR UPDATE"
    );
    $st->execute([$stagingStockId]);
    $bal = $st->fetch(PDO::FETCH_ASSOC);
    $issued = round((float) ($bal['issued_qty'] ?? 0), 4);
    $consumed = round((float) ($bal['consumed_qty'] ?? 0), 4);
    $damaged = $hasDamaged ? round((float) ($bal['damaged_qty'] ?? 0), 4) : 0.0;
    $remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);

    if ($remaining < $deductQty - 0.0001) {
        throw new RuntimeException(
            'No packing stock for ' . $stockName
            . ' — issue materials first. Need ' . $deductQty . ', remaining ' . $remaining . '.'
        );
    }

    if ($bal === false) {
        $pdo->prepare(
            'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty) VALUES (?, 0, ?)'
        )->execute([$stagingStockId, $deductQty]);
        $issued = 0.0;
        $consumed = 0.0;
    } else {
        $pdo->prepare(
            'UPDATE fulfillment_packing_balances SET consumed_qty = consumed_qty + ? WHERE stock_id = ?'
        )->execute([$deductQty, $stagingStockId]);
    }

    return [
        'stock_id' => $stagingStockId,
        'qty_consumed' => $deductQty,
        'consumed_qty' => round($consumed + $deductQty, 4),
        'issued_qty' => $issued,
    ];
}

/** @return list<array<string, mixed>> */
function fulfillment_packing_balances_list(PDO $pdo): array
{
    if (!fulfillment_packing_tables_ready($pdo)) {
        return [];
    }
    $hasDamaged = rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty');
    $damagedCol = $hasDamaged ? 'COALESCE(b.damaged_qty, 0) AS damaged_qty' : '0 AS damaged_qty';
    $poolCol = material_inventory_has_pool_columns($pdo) ? ', s.pool_yield_qty, s.pool_yield_unit' : '';
    $st = $pdo->query(
        "SELECT s.id AS stock_id, s.name, s.unit, s.on_hand AS warehouse_on_hand,
                COALESCE(b.issued_qty, 0) AS issued_qty,
                COALESCE(b.consumed_qty, 0) AS consumed_qty,
                {$damagedCol}{$poolCol}
         FROM stocks s
         LEFT JOIN fulfillment_packing_balances b ON b.stock_id = s.id
         WHERE s.supplier_id IS NULL AND s.is_active = 1 AND s.category = 'packaging'
           AND LOWER(TRIM(s.name)) <> 'bubble wrap roll'
           AND s.name NOT LIKE 'Insulation foam roll - %'
           AND (
             s.name LIKE 'Bubble wrap roll%'
             OR s.name LIKE 'Cushion piece%'
             OR s.name LIKE 'Cushion roll%'
             OR s.name LIKE 'Courier pouch%'
             OR s.name LIKE 'Insulation foam roll%'
             OR s.name LIKE 'Cardboard box%'
           )
         ORDER BY s.name ASC"
    );
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $issued = round((float) ($row['issued_qty'] ?? 0), 4);
        $consumed = round((float) ($row['consumed_qty'] ?? 0), 4);
        $damaged = round((float) ($row['damaged_qty'] ?? 0), 4);
        $rows[] = fulfillment_packing_staging_balance_display_row($row, $issued, $consumed, $damaged);
    }

    return $rows;
}

/**
 * Report damaged / unusable staging qty (reduces remaining; does not return to warehouse).
 *
 * @return array{stock_id: int, qty: float, unit: string, damaged_qty: float, remaining_qty: float, event_id: int}
 */
function fulfillment_packing_report_damage(
    PDO $pdo,
    int $userId,
    int $stockId,
    float $qty,
    string $note,
    ?string $qtyUnit = null
): array {
    if (!fulfillment_packing_tables_ready($pdo)) {
        throw new RuntimeException('Packing staging is not available. Run migration phase 34.');
    }
    if (!rts_db_has_column($pdo, 'fulfillment_packing_balances', 'damaged_qty')) {
        throw new RuntimeException('Damage reporting requires migration phase 38.');
    }
    if ($qty <= 0) {
        throw new RuntimeException('Damage quantity must be greater than 0.');
    }
    $note = trim($note);
    if ($note === '') {
        throw new RuntimeException('A note is required for damage reports.');
    }
    if (function_exists('mb_substr')) {
        $note = mb_substr($note, 0, 255);
    } else {
        $note = substr($note, 0, 255);
    }

    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if (!fulfillment_packing_is_fulfillment_stock_name((string) $row['name'])
        || fulfillment_packing_is_bubble_generic_name((string) $row['name'])) {
        throw new RuntimeException('Damage can only be reported on sized packing staging SKUs.');
    }

    $yield = fulfillment_packing_bubble_piece_yield_from_row($row);
    $inputUnit = $qtyUnit !== null ? strtolower(trim($qtyUnit)) : null;
    if ($yield !== null) {
        $inputUnit = $inputUnit ?? 'pc';
        if ($inputUnit === 'pc') {
            $rollQty = fulfillment_packing_pieces_to_staging_rolls($qty, $yield);
            $reportedQty = round($qty, 2);
            $displayUnit = 'pc';
        } elseif ($inputUnit === 'roll') {
            $rollQty = material_fulfillment_snap_sixteenths($qty);
            $reportedQty = $rollQty;
            $displayUnit = 'roll';
        } else {
            throw new RuntimeException('qty_unit must be pc or roll for sized bubble wrap.');
        }
    } else {
        $rollQty = round($qty, 4);
        $reportedQty = $rollQty;
        $displayUnit = trim((string) ($row['unit'] ?? 'pcs'));
        if ($displayUnit === '') {
            $displayUnit = 'pcs';
        }
    }

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare(
            'SELECT issued_qty, consumed_qty, damaged_qty
             FROM fulfillment_packing_balances WHERE stock_id = ? FOR UPDATE'
        );
        $st->execute([$stockId]);
        $bal = $st->fetch(PDO::FETCH_ASSOC);
        if ($bal === false) {
            throw new RuntimeException('No staging balance for this SKU — issue materials first.');
        }
        $issued = round((float) ($bal['issued_qty'] ?? 0), 4);
        $consumed = round((float) ($bal['consumed_qty'] ?? 0), 4);
        $damaged = round((float) ($bal['damaged_qty'] ?? 0), 4);
        $remaining = fulfillment_packing_staging_remaining($issued, $consumed, $damaged);
        if ($rollQty > $remaining + 0.0001) {
            $remainingDisplay = $yield !== null && ($inputUnit ?? 'pc') === 'pc'
                ? fulfillment_packing_staging_piece_qty($remaining, $yield) . ' pc'
                : $remaining . ' remaining';
            throw new RuntimeException(
                'Cannot report ' . $reportedQty . ' ' . $displayUnit
                . ' damaged — only ' . $remainingDisplay . ' on staging.'
            );
        }

        $pdo->prepare(
            'UPDATE fulfillment_packing_balances SET damaged_qty = damaged_qty + ? WHERE stock_id = ?'
        )->execute([$rollQty, $stockId]);

        $pdo->prepare(
            'INSERT INTO fulfillment_packing_damage_events (stock_id, qty, note, reported_by)
             VALUES (?, ?, ?, ?)'
        )->execute([$stockId, $rollQty, $note, $userId > 0 ? $userId : null]);
        $eventId = (int) $pdo->lastInsertId();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $newDamaged = round($damaged + $rollQty, 4);
    $remainingRoll = fulfillment_packing_staging_remaining($issued, $consumed, $newDamaged);

    if ($yield !== null && ($inputUnit ?? 'pc') === 'pc') {
        return [
            'stock_id' => $stockId,
            'qty' => $reportedQty,
            'unit' => 'pc',
            'damaged_qty' => fulfillment_packing_staging_piece_qty($newDamaged, $yield),
            'remaining_qty' => fulfillment_packing_staging_piece_qty($remainingRoll, $yield),
            'event_id' => $eventId,
        ];
    }

    return [
        'stock_id' => $stockId,
        'qty' => $reportedQty,
        'unit' => $displayUnit,
        'damaged_qty' => $newDamaged,
        'remaining_qty' => $remainingRoll,
        'event_id' => $eventId,
    ];
}

/** @return array{rows: list<array<string,mixed>>, total: int} */
function fulfillment_packing_damage_list(PDO $pdo, int $page, int $perPage): array
{
    if (!fulfillment_packing_tables_ready($pdo)) {
        return ['rows' => [], 'total' => 0];
    }
    try {
        $pdo->query('SELECT 1 FROM fulfillment_packing_damage_events LIMIT 1');
    } catch (Throwable) {
        return ['rows' => [], 'total' => 0];
    }
    $page = max(1, $page);
    $perPage = min(50, max(5, $perPage));
    $offset = ($page - 1) * $perPage;
    $total = (int) $pdo->query('SELECT COUNT(*) FROM fulfillment_packing_damage_events')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT e.id, e.stock_id, e.qty, e.note, e.created_at, s.name AS stock_name, s.unit AS stock_unit,
                s.pool_yield_qty, u.name AS reported_by_name
         FROM fulfillment_packing_damage_events e
         INNER JOIN stocks s ON s.id = e.stock_id
         LEFT JOIN users u ON u.id = e.reported_by
         ORDER BY e.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . $offset
    );
    $st->execute();
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $rollQty = round((float) $row['qty'], 4);
        $yield = fulfillment_packing_bubble_piece_yield_from_row([
            'name' => (string) $row['stock_name'],
            'pool_yield_qty' => $row['pool_yield_qty'],
        ]);
        $display = $yield !== null
            ? [
                'qty' => fulfillment_packing_staging_piece_qty($rollQty, $yield),
                'unit' => 'pc',
                'roll_qty' => $rollQty,
            ]
            : [
                'qty' => $rollQty,
                'unit' => (string) ($row['stock_unit'] ?? 'pcs'),
                'roll_qty' => null,
            ];
        $rows[] = [
            'id' => (int) $row['id'],
            'stock_id' => (int) $row['stock_id'],
            'stock_name' => (string) $row['stock_name'],
            'qty' => $display['qty'],
            'unit' => $display['unit'],
            'roll_qty' => $display['roll_qty'],
            'note' => (string) $row['note'],
            'created_at' => (string) $row['created_at'],
            'reported_by_name' => $row['reported_by_name'],
        ];
    }

    return ['rows' => $rows, 'total' => $total];
}

/** @return array{rows: list<array<string,mixed>>, total: int} */
function fulfillment_packing_issues_list(PDO $pdo, int $page, int $perPage): array
{
    if (!fulfillment_packing_tables_ready($pdo)) {
        return ['rows' => [], 'total' => 0];
    }
    $page = max(1, $page);
    $perPage = min(50, max(5, $perPage));
    $offset = ($page - 1) * $perPage;

    $total = (int) $pdo->query('SELECT COUNT(*) FROM fulfillment_packing_issues')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT i.id, i.note, i.est_parcels_typical, i.est_parcels_label, i.created_at,
                u.name AS issued_by_name
         FROM fulfillment_packing_issues i
         LEFT JOIN users u ON u.id = i.issued_by
         ORDER BY i.id DESC
         LIMIT ' . $perPage . ' OFFSET ' . $offset
    );
    $st->execute();
    $issues = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $hasBubbleCols = rts_db_has_column($pdo, 'fulfillment_packing_issue_lines', 'bubble_width_cm');
    $lineSql = $hasBubbleCols
        ? 'SELECT l.stock_id, l.qty, l.unit, l.bubble_width_cm, s.name AS stock_name,
                  s.pool_yield_qty, s.unit AS stock_unit
           FROM fulfillment_packing_issue_lines l
           INNER JOIN stocks s ON s.id = l.stock_id
           WHERE l.issue_id = ?
           ORDER BY l.id ASC'
        : 'SELECT l.stock_id, l.qty, l.unit, s.name AS stock_name, s.pool_yield_qty, s.unit AS stock_unit
           FROM fulfillment_packing_issue_lines l
           INNER JOIN stocks s ON s.id = l.stock_id
           WHERE l.issue_id = ?
           ORDER BY l.id ASC';
    $lineSt = $pdo->prepare($lineSql);

    $rows = [];
    foreach ($issues as $issue) {
        $lineSt->execute([(int) $issue['id']]);
        $lines = $lineSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $rows[] = [
            'id' => (int) $issue['id'],
            'note' => $issue['note'],
            'est_parcels_typical' => $issue['est_parcels_typical'] !== null
                ? (int) $issue['est_parcels_typical'] : null,
            'est_parcels_label' => $issue['est_parcels_label'],
            'created_at' => (string) $issue['created_at'],
            'issued_by_name' => $issue['issued_by_name'],
            'lines' => array_map(static function (array $l) use ($hasBubbleCols): array {
                $rollQty = round((float) $l['qty'], 4);
                $unit = (string) $l['unit'];
                $display = fulfillment_packing_issue_line_display([
                    'name' => (string) $l['stock_name'],
                    'pool_yield_qty' => $l['pool_yield_qty'] ?? null,
                ], $rollQty, $unit);
                $out = [
                    'stock_id' => (int) $l['stock_id'],
                    'stock_name' => (string) $l['stock_name'],
                    'qty' => $display['qty'],
                    'unit' => $display['unit'],
                    'roll_qty' => $display['roll_qty'],
                ];
                if ($hasBubbleCols && isset($l['bubble_width_cm']) && $l['bubble_width_cm'] !== null) {
                    $out['bubble_width_cm'] = (int) $l['bubble_width_cm'];
                }

                return $out;
            }, $lines),
        ];
    }

    return ['rows' => $rows, 'total' => $total];
}

/**
 * HR records invoice price without changing on-hand (WAC update only).
 *
 * @return array{stock_id: int, current_price: float, lot_unit: float, entry_id: int}
 */
function material_packaging_record_invoice_price(
    PDO $pdo,
    int $stockId,
    float $lotTotalPrice,
    float $lotQty,
    int $userId,
    ?string $note = null
): array {
    if ($lotTotalPrice < 0) {
        throw new RuntimeException('Total price must be 0 or greater.');
    }
    if ($lotQty <= 0) {
        throw new RuntimeException('Invoice quantity must be greater than 0.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging materials support invoice price recording.');
    }

    $oldPrice = round((float) ($row['current_price'] ?? 0), 6);
    $lotUnit = round($lotTotalPrice / $lotQty, 6);
    $onHand = round((float) ($row['on_hand'] ?? 0), 2);
    $stockUnit = trim((string) ($row['unit'] ?? 'pcs'));
    if ($stockUnit === '') {
        $stockUnit = 'pcs';
    }

    $batchLabel = sprintf(
        'price_lot: ₱%s/%s%s',
        number_format($lotTotalPrice, 2, '.', ''),
        rtrim(rtrim(number_format($lotQty, 4, '.', ''), '0'), '.'),
        $stockUnit
    );
    if ($note !== null && trim($note) !== '') {
        $batchLabel .= ' — ' . trim($note);
    }
    $batchLabel = substr($batchLabel, 0, 120);

    $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, 0, ?, ?, ?)'
    )->execute([
        $stockId,
        (int) $row['dept_id'],
        date('Y-m-d H:i:s'),
        $batchLabel,
        $userId,
    ]);
    $entryId = (int) $pdo->lastInsertId();

    $derivedPrice = material_pool_unit_cost_from_lots($pdo, $stockId, $onHand);
    $newPrice = $derivedPrice ?? round($lotUnit, 6);
    $newPriceRounded = round($newPrice, 2);
    $oldPriceRounded = round($oldPrice, 2);

    if ($newPriceRounded !== $oldPriceRounded) {
        if (!function_exists('api_costing_apply_price_change')) {
            require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';
        }
        api_costing_apply_price_change($pdo, $stockId, $oldPriceRounded, $newPriceRounded, $userId);
    } else {
        $pdo->prepare('UPDATE stocks SET current_price = ? WHERE id = ?')->execute([$newPriceRounded, $stockId]);
    }

    return [
        'stock_id' => $stockId,
        'current_price' => $newPriceRounded,
        'lot_unit' => round($lotUnit, 6),
        'entry_id' => $entryId,
    ];
}
