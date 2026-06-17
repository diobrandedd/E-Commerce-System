<?php

declare(strict_types=1);

require_once __DIR__ . '/material_inventory.php';
require_once __DIR__ . '/parcel_pack_costing.php';

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
    return in_array($role, ['executive', 'prod_manager'], true);
}

function material_inventory_role_can_edit_packaging_price(string $role): bool
{
    return in_array($role, ['executive', 'hr'], true);
}

function material_inventory_role_can_view_packing_staging(string $role): bool
{
    return in_array($role, ['executive', 'prod_manager'], true);
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
    $st = $pdo->prepare(
        'SELECT issued_qty, consumed_qty FROM fulfillment_packing_balances WHERE stock_id = ? LIMIT 1'
    );
    $st->execute([$stockId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return ['issued_qty' => 0.0, 'consumed_qty' => 0.0, 'remaining_qty' => 0.0];
    }
    $issued = round((float) ($row['issued_qty'] ?? 0), 4);
    $consumed = round((float) ($row['consumed_qty'] ?? 0), 4);

    return [
        'issued_qty' => $issued,
        'consumed_qty' => $consumed,
        'remaining_qty' => round(max(0, $issued - $consumed), 4),
    ];
}

/**
 * @param list<array{stock_id: int, qty: float, unit: string}> $lines
 * @return array{est_parcels: int, est_label: string, bottleneck_stock_id: int|null, per_tier: list<array<string,mixed>>}
 */
function fulfillment_packing_estimate_parcels(PDO $pdo, array $lines): array
{
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
            if (!isset($qtyByStock[$sid])) {
                continue;
            }
            $need = (float) ($tl['qty_typical'] ?? 0);
            if ($need <= 0) {
                continue;
            }
            $stockRow = material_inventory_fetch_stock($pdo, $sid);
            if ($stockRow === null) {
                continue;
            }
            $unit = (string) ($tl['unit'] ?? 'pcs');
            $stockUnit = trim((string) ($stockRow['unit'] ?? 'pcs'));
            $issuedQty = $qtyByStock[$sid];
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
 * @param list<array{stock_id: int, qty: float, unit?: string}> $lines
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
        if (!fulfillment_packing_is_fulfillment_stock_name((string) $row['name'])) {
            throw new RuntimeException('Only fulfillment packaging SKUs can be issued to packing.');
        }
        $onHand = round((float) ($row['on_hand'] ?? 0), 4);
        if ($onHand < $qty) {
            throw new RuntimeException(
                'Insufficient warehouse stock for ' . (string) $row['name'] . '. On hand: ' . $onHand
            );
        }
        $normalized[] = [
            'stock_id' => $stockId,
            'qty' => $qty,
            'unit' => trim((string) ($line['unit'] ?? $row['unit'] ?? 'pcs')),
            'stock_name' => (string) $row['name'],
            'dept_id' => (int) $row['dept_id'],
        ];
    }

    $estimate = fulfillment_packing_estimate_parcels($pdo, $normalized);
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
            'INSERT INTO fulfillment_packing_issue_lines (issue_id, stock_id, qty, unit) VALUES (?, ?, ?, ?)'
        );
        $outLines = [];

        foreach ($normalized as $line) {
            $stockId = (int) $line['stock_id'];
            $qty = (float) $line['qty'];

            $pdo->prepare('UPDATE stocks SET on_hand = on_hand - ? WHERE id = ?')->execute([$qty, $stockId]);

            $bal = $pdo->prepare(
                'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty)
                 VALUES (?, ?, 0)
                 ON DUPLICATE KEY UPDATE issued_qty = issued_qty + VALUES(issued_qty)'
            );
            $bal->execute([$stockId, $qty]);

            $batchLabel = 'packing_issue: #' . $issueId;
            $pdo->prepare(
                'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $stockId,
                (int) $line['dept_id'],
                -$qty,
                date('Y-m-d H:i:s'),
                $batchLabel,
                $userId,
            ]);

            $insLine->execute([$issueId, $stockId, $qty, (string) $line['unit']]);
            $outLines[] = [
                'stock_id' => $stockId,
                'stock_name' => (string) $line['stock_name'],
                'qty' => $qty,
                'unit' => (string) $line['unit'],
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
    if (in_array($lineUnit, ['bubble_piece', 'foam_piece', 'column'], true)) {
        $yield = (float) ($row['pool_yield_qty'] ?? 0);
        if ($yield <= 0) {
            throw new RuntimeException('Roll yield is not configured for ' . (string) $row['name'] . '.');
        }
        $deductQty = material_fulfillment_snap_sixteenths($qty / $yield);
    } elseif ($stockUnit === 'roll') {
        $deductQty = material_fulfillment_snap_sixteenths($qty);
    }

    $st = $pdo->prepare(
        'SELECT issued_qty, consumed_qty FROM fulfillment_packing_balances WHERE stock_id = ? FOR UPDATE'
    );
    $st->execute([$stockId]);
    $bal = $st->fetch(PDO::FETCH_ASSOC);
    $issued = round((float) ($bal['issued_qty'] ?? 0), 4);
    $consumed = round((float) ($bal['consumed_qty'] ?? 0), 4);
    $remaining = round($issued - $consumed, 4);

    if ($remaining < $deductQty - 0.0001) {
        throw new RuntimeException(
            'No packing stock for ' . $stockName
            . ' — issue materials first. Need ' . $deductQty . ', remaining ' . $remaining . '.'
        );
    }

    if ($bal === false) {
        $pdo->prepare(
            'INSERT INTO fulfillment_packing_balances (stock_id, issued_qty, consumed_qty) VALUES (?, 0, ?)'
        )->execute([$stockId, $deductQty]);
        $issued = 0.0;
        $consumed = 0.0;
    } else {
        $pdo->prepare(
            'UPDATE fulfillment_packing_balances SET consumed_qty = consumed_qty + ? WHERE stock_id = ?'
        )->execute([$deductQty, $stockId]);
    }

    return [
        'stock_id' => $stockId,
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
    $st = $pdo->query(
        "SELECT s.id AS stock_id, s.name, s.unit, s.on_hand AS warehouse_on_hand,
                COALESCE(b.issued_qty, 0) AS issued_qty,
                COALESCE(b.consumed_qty, 0) AS consumed_qty
         FROM stocks s
         LEFT JOIN fulfillment_packing_balances b ON b.stock_id = s.id
         WHERE s.supplier_id IS NULL AND s.is_active = 1 AND s.category = 'packaging'
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
        $rows[] = [
            'stock_id' => (int) $row['stock_id'],
            'name' => (string) $row['name'],
            'unit' => (string) $row['unit'],
            'warehouse_on_hand' => round((float) ($row['warehouse_on_hand'] ?? 0), 4),
            'issued_qty' => $issued,
            'consumed_qty' => $consumed,
            'remaining_qty' => round(max(0, $issued - $consumed), 4),
        ];
    }

    return $rows;
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
    $lineSt = $pdo->prepare(
        'SELECT l.stock_id, l.qty, l.unit, s.name AS stock_name
         FROM fulfillment_packing_issue_lines l
         INNER JOIN stocks s ON s.id = l.stock_id
         WHERE l.issue_id = ?
         ORDER BY l.id ASC'
    );

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
            'lines' => array_map(static function (array $l): array {
                return [
                    'stock_id' => (int) $l['stock_id'],
                    'stock_name' => (string) $l['stock_name'],
                    'qty' => round((float) $l['qty'], 4),
                    'unit' => (string) $l['unit'],
                ];
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
