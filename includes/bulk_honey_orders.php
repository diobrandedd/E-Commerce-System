<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_inventory.php';
require_once __DIR__ . '/honey_pricing.php';

const BULK_HONEY_VARIANTS = ['light', 'amber', 'dark'];
const BULK_HONEY_NEGOTIATION_MODES = ['margin_pct', 'unit_price', 'total_price'];

function bulk_honey_orders_assert_executive(array $user): void
{
    if (!ims_user_can($user, 'sales.bulk_orders')) {
        throw new RuntimeException('Forbidden.');
    }
}

function bulk_honey_orders_table_exists(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $st = $pdo->query(
        "SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bulk_honey_orders' LIMIT 1"
    );
    $cache = (bool) ($st && $st->fetchColumn());

    return $cache;
}

function bulk_honey_normalize_variant(string $variant): string
{
    $v = strtolower(trim($variant));
    if (!in_array($v, BULK_HONEY_VARIANTS, true)) {
        throw new RuntimeException('Invalid honey variant.');
    }

    return $v;
}

function bulk_honey_normalize_negotiation_mode(?string $mode): ?string
{
    if ($mode === null || $mode === '') {
        return null;
    }
    $m = strtolower(trim($mode));
    if (!in_array($m, BULK_HONEY_NEGOTIATION_MODES, true)) {
        throw new RuntimeException('Invalid negotiation mode.');
    }

    return $m;
}

/**
 * @return list<array<string, mixed>>
 */
function bulk_honey_warehouse_rows_for_variant(PDO $pdo, string $variant): array
{
    $variant = bulk_honey_normalize_variant($variant);
    $costCol = honey_warehouse_unit_cost_column($pdo);
    $cols = $costCol !== null
        ? "id, supplier_code, arrival_date, variant, current_qty, {$costCol} AS unit_cost_per_gallon"
        : 'id, supplier_code, arrival_date, variant, current_qty';
    $st = $pdo->prepare(
        "SELECT {$cols} FROM honey_warehouse_rows
         WHERE variant = ? AND current_qty > 0
         ORDER BY arrival_date ASC, id ASC"
    );
    $st->execute([$variant]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($rows as $row) {
        $enriched = $costCol !== null
            ? honey_pricing_enrich_warehouse_row(honey_warehouse_normalize_cost_row($row))
            : $row;
        $unit = isset($enriched['unit_cost_per_gallon']) && $enriched['unit_cost_per_gallon'] !== null
            ? round((float) $enriched['unit_cost_per_gallon'], 4)
            : null;
        $out[] = [
            'warehouse_row_id' => (int) $row['id'],
            'supplier_code' => (string) ($row['supplier_code'] ?? ''),
            'arrival_date' => (string) ($row['arrival_date'] ?? ''),
            'variant' => (string) ($row['variant'] ?? ''),
            'current_qty' => round((float) ($row['current_qty'] ?? 0), 3),
            'unit_cost_per_gallon' => $unit,
            'pricing_status' => $enriched['pricing_status'] ?? ($unit !== null ? 'priced' : 'pending'),
        ];
    }

    return $out;
}

/**
 * @return list<array{warehouse_row_id:int, qty_gallons:float}>
 */
function bulk_honey_suggest_fifo_allocations(PDO $pdo, string $variant, float $qtyGallons): array
{
    if ($qtyGallons <= 0) {
        return [];
    }
    $rows = bulk_honey_warehouse_rows_for_variant($pdo, $variant);
    $remaining = $qtyGallons;
    $alloc = [];
    foreach ($rows as $row) {
        if ($remaining <= 1e-9) {
            break;
        }
        $avail = (float) $row['current_qty'];
        if ($avail <= 0) {
            continue;
        }
        $take = min($avail, $remaining);
        $alloc[] = [
            'warehouse_row_id' => (int) $row['warehouse_row_id'],
            'qty_gallons' => round($take, 3),
        ];
        $remaining -= $take;
    }
    if ($remaining > 1e-6) {
        throw new RuntimeException('Not enough warehouse stock to suggest FIFO allocations for this quantity.');
    }

    return $alloc;
}

/**
 * @param list<array<string, mixed>> $allocations
 * @return array{line_cogs:float, cogs_per_gallon:float, slices:list<array<string,mixed>>}
 */
function bulk_honey_compute_allocation_cogs(PDO $pdo, float $qtyGallons, array $allocations): array
{
    if ($qtyGallons <= 0) {
        throw new RuntimeException('Quantity must be positive.');
    }
    $totalCogs = 0.0;
    $slices = [];
    $allocSum = 0.0;
    foreach ($allocations as $a) {
        if (!is_array($a)) {
            continue;
        }
        $wid = (int) ($a['warehouse_row_id'] ?? 0);
        $sliceQty = round((float) ($a['qty_gallons'] ?? 0), 3);
        if ($wid < 1 || $sliceQty <= 0) {
            continue;
        }
        $wh = honey_fetch_warehouse($pdo, $wid);
        if (!$wh) {
            throw new RuntimeException("Warehouse row #{$wid} not found.");
        }
        $costCol = honey_warehouse_unit_cost_column($pdo);
        $unit = null;
        if ($costCol !== null) {
            $enriched = honey_pricing_enrich_warehouse_row(
                honey_warehouse_normalize_cost_row($wh)
            );
            $unit = $enriched['unit_cost_per_gallon'] !== null
                ? (float) $enriched['unit_cost_per_gallon']
                : null;
        }
        if ($unit === null) {
            $code = (string) ($wh['supplier_code'] ?? '');
            throw new RuntimeException(
                "Supplier code {$code} has no unit cost — set warehouse price before bulk order."
            );
        }
        $sliceCogs = round($sliceQty * $unit, 2);
        $totalCogs += $sliceCogs;
        $allocSum += $sliceQty;
        $slices[] = [
            'warehouse_row_id' => $wid,
            'supplier_code' => (string) ($wh['supplier_code'] ?? ''),
            'variant' => (string) ($wh['variant'] ?? ''),
            'qty_gallons' => $sliceQty,
            'unit_cost_per_gallon' => round($unit, 4),
            'line_cogs_slice' => $sliceCogs,
        ];
    }
    if (abs($allocSum - $qtyGallons) > 0.01) {
        throw new RuntimeException(
            'Allocations must sum to line quantity (' . round($qtyGallons, 3) . ' gal).'
        );
    }

    return [
        'line_cogs' => round($totalCogs, 2),
        'cogs_per_gallon' => round($totalCogs / $qtyGallons, 4),
        'slices' => $slices,
    ];
}

/**
 * Resolve unit sale price from negotiation mode.
 */
function bulk_honey_resolve_unit_sale_price(
    float $qtyGallons,
    float $cogsPerGallon,
    ?string $negotiationMode,
    ?float $targetMarginPct,
    ?float $unitSalePrice,
    ?float $totalDealPrice
): float {
    $mode = bulk_honey_normalize_negotiation_mode($negotiationMode) ?? 'unit_price';
    if ($mode === 'margin_pct') {
        $pct = $targetMarginPct ?? 0.0;
        if ($pct < 0 || $pct >= 100) {
            throw new RuntimeException('Margin % must be between 0 and 99.99.');
        }
        if ($cogsPerGallon <= 0) {
            throw new RuntimeException('Cannot compute price from margin when COGS is zero.');
        }
        $denom = 1.0 - ($pct / 100.0);
        if ($denom <= 0) {
            throw new RuntimeException('Invalid margin %.');
        }

        return round($cogsPerGallon / $denom, 4);
    }
    if ($mode === 'total_price') {
        $total = $totalDealPrice ?? 0.0;
        if ($total <= 0 || $qtyGallons <= 0) {
            throw new RuntimeException('Total deal price must be positive.');
        }

        return round($total / $qtyGallons, 4);
    }
    $unit = $unitSalePrice ?? 0.0;
    if ($unit < 0) {
        throw new RuntimeException('Unit sale price cannot be negative.');
    }

    return round($unit, 4);
}

/**
 * @param list<array<string, mixed>> $lines
 * @return array{lines:list<array<string,mixed>>, totals:array<string,mixed>}
 */
function bulk_honey_preview_cost(PDO $pdo, array $lines): array
{
    $outLines = [];
    $sumGal = 0.0;
    $sumCogs = 0.0;
    $sumRev = 0.0;
    foreach ($lines as $idx => $line) {
        if (!is_array($line)) {
            continue;
        }
        $variant = bulk_honey_normalize_variant((string) ($line['variant'] ?? ''));
        $qty = round((float) ($line['qty_gallons'] ?? 0), 3);
        if ($qty <= 0) {
            throw new RuntimeException('Line ' . ($idx + 1) . ': quantity must be positive.');
        }
        $allocs = is_array($line['allocations'] ?? null) ? $line['allocations'] : [];
        $cogsData = bulk_honey_compute_allocation_cogs($pdo, $qty, $allocs);
        $cogsPerGal = (float) $cogsData['cogs_per_gallon'];
        $lineCogs = (float) $cogsData['line_cogs'];
        $unitPrice = bulk_honey_resolve_unit_sale_price(
            $qty,
            $cogsPerGal,
            isset($line['negotiation_mode']) ? (string) $line['negotiation_mode'] : null,
            isset($line['target_margin_pct']) ? (float) $line['target_margin_pct'] : null,
            isset($line['unit_sale_price']) ? (float) $line['unit_sale_price'] : null,
            isset($line['total_deal_price']) ? (float) $line['total_deal_price'] : null
        );
        $revenue = round($qty * $unitPrice, 2);
        $margin = round($revenue - $lineCogs, 2);
        $marginPct = $revenue > 0 ? round(($margin / $revenue) * 100, 4) : null;
        $belowCost = $unitPrice < $cogsPerGal - 1e-9;
        $outLines[] = [
            'variant' => $variant,
            'qty_gallons' => $qty,
            'unit_sale_price' => $unitPrice,
            'negotiation_mode' => $line['negotiation_mode'] ?? 'unit_price',
            'target_margin_pct' => $line['target_margin_pct'] ?? null,
            'line_cogs' => $lineCogs,
            'cogs_per_gallon' => $cogsPerGal,
            'break_even_total' => $lineCogs,
            'line_revenue' => $revenue,
            'line_margin' => $margin,
            'line_margin_pct' => $marginPct,
            'below_cost' => $belowCost,
            'allocations' => $cogsData['slices'],
            'margin_suggestions' => bulk_honey_margin_suggestions($cogsPerGal, $qty),
        ];
        $sumGal += $qty;
        $sumCogs += $lineCogs;
        $sumRev += $revenue;
    }
    if ($outLines === []) {
        throw new RuntimeException('Add at least one line.');
    }
    $sumMargin = round($sumRev - $sumCogs, 2);

    return [
        'lines' => $outLines,
        'totals' => [
            'total_gallons' => round($sumGal, 3),
            'total_cogs' => round($sumCogs, 2),
            'total_revenue' => round($sumRev, 2),
            'total_margin' => $sumMargin,
            'margin_pct' => $sumRev > 0 ? round(($sumMargin / $sumRev) * 100, 4) : null,
        ],
    ];
}

/** @return list<array{margin_pct:float, unit_sale_price:float, total_revenue:float}> */
function bulk_honey_margin_suggestions(float $cogsPerGallon, float $qty): array
{
    $out = [];
    foreach ([10.0, 15.0, 20.0, 25.0] as $pct) {
        if ($cogsPerGallon <= 0) {
            continue;
        }
        $unit = round($cogsPerGallon / (1.0 - $pct / 100.0), 4);
        $out[] = [
            'margin_pct' => $pct,
            'unit_sale_price' => $unit,
            'total_revenue' => round($unit * $qty, 2),
        ];
    }

    return $out;
}

function bulk_honey_next_order_no(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'BULK-' . $year . '-';
    $st = $pdo->prepare(
        'SELECT order_no FROM bulk_honey_orders WHERE order_no LIKE ? ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$prefix . '%']);
    $last = $st->fetchColumn();
    $seq = 1;
    if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
        $seq = (int) $m[1] + 1;
    }

    return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

/**
 * @param array<string, mixed> $payload
 */
function bulk_honey_save_draft(PDO $pdo, int $userId, ?int $orderId, array $payload): array
{
    if (!bulk_honey_orders_table_exists($pdo)) {
        throw new RuntimeException('Bulk orders tables missing — run migration phase 51.');
    }
    $customer = trim((string) ($payload['customer_name'] ?? ''));
    if ($customer === '') {
        throw new RuntimeException('Customer name is required.');
    }
    $linesIn = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];
    $preview = bulk_honey_preview_cost($pdo, $linesIn);
    $totals = $preview['totals'];

    $pdo->beginTransaction();
    try {
        if ($orderId !== null && $orderId > 0) {
            $st = $pdo->prepare('SELECT id, status FROM bulk_honey_orders WHERE id = ? FOR UPDATE');
            $st->execute([$orderId]);
            $existing = $st->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                throw new RuntimeException('Order not found.');
            }
            if ((string) $existing['status'] !== 'draft') {
                throw new RuntimeException('Only draft orders can be edited.');
            }
            $upd = $pdo->prepare(
                'UPDATE bulk_honey_orders SET
                    customer_name = ?, partner_label = ?, notes = ?,
                    shipment_date = ?, courier = ?, tracking_ref = ?,
                    total_gallons = ?, total_cogs = ?, total_revenue = ?, total_margin = ?, margin_pct = ?,
                    updated_at = NOW()
                 WHERE id = ?'
            );
            $upd->execute([
                $customer,
                bulk_honey_nullable_str($payload['partner_label'] ?? null),
                bulk_honey_nullable_str($payload['notes'] ?? null),
                bulk_honey_nullable_date($payload['shipment_date'] ?? null),
                bulk_honey_nullable_str($payload['courier'] ?? null),
                bulk_honey_nullable_str($payload['tracking_ref'] ?? null),
                $totals['total_gallons'],
                $totals['total_cogs'],
                $totals['total_revenue'],
                $totals['total_margin'],
                $totals['margin_pct'],
                $orderId,
            ]);
            $pdo->prepare('DELETE FROM bulk_honey_order_allocations WHERE order_id = ?')->execute([$orderId]);
            $pdo->prepare('DELETE FROM bulk_honey_order_lines WHERE order_id = ?')->execute([$orderId]);
        } else {
            $orderNo = bulk_honey_next_order_no($pdo);
            $ins = $pdo->prepare(
                'INSERT INTO bulk_honey_orders (
                    order_no, customer_name, partner_label, notes, shipment_date, courier, tracking_ref,
                    status, total_gallons, total_cogs, total_revenue, total_margin, margin_pct, created_by
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, \'draft\', ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $orderNo,
                $customer,
                bulk_honey_nullable_str($payload['partner_label'] ?? null),
                bulk_honey_nullable_str($payload['notes'] ?? null),
                bulk_honey_nullable_date($payload['shipment_date'] ?? null),
                bulk_honey_nullable_str($payload['courier'] ?? null),
                bulk_honey_nullable_str($payload['tracking_ref'] ?? null),
                $totals['total_gallons'],
                $totals['total_cogs'],
                $totals['total_revenue'],
                $totals['total_margin'],
                $totals['margin_pct'],
                $userId,
            ]);
            $orderId = (int) $pdo->lastInsertId();
        }

        $lineIns = $pdo->prepare(
            'INSERT INTO bulk_honey_order_lines (
                order_id, line_sort, variant, qty_gallons, unit_sale_price,
                line_cogs, cogs_per_gallon, line_revenue, line_margin, line_margin_pct,
                negotiation_mode, target_margin_pct
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $allocIns = $pdo->prepare(
            'INSERT INTO bulk_honey_order_allocations (
                order_id, line_id, warehouse_row_id, supplier_code, variant,
                qty_gallons, unit_cost_per_gallon, line_cogs_slice
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($preview['lines'] as $sort => $line) {
            $mode = bulk_honey_normalize_negotiation_mode(
                isset($line['negotiation_mode']) ? (string) $line['negotiation_mode'] : null
            );
            $lineIns->execute([
                $orderId,
                $sort,
                $line['variant'],
                $line['qty_gallons'],
                $line['unit_sale_price'],
                $line['line_cogs'],
                $line['cogs_per_gallon'],
                $line['line_revenue'],
                $line['line_margin'],
                $line['line_margin_pct'],
                $mode,
                $line['target_margin_pct'] ?? null,
            ]);
            $lineId = (int) $pdo->lastInsertId();
            foreach ($line['allocations'] as $slice) {
                $allocIns->execute([
                    $orderId,
                    $lineId,
                    $slice['warehouse_row_id'],
                    $slice['supplier_code'],
                    $slice['variant'],
                    $slice['qty_gallons'],
                    $slice['unit_cost_per_gallon'],
                    $slice['line_cogs_slice'],
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return bulk_honey_order_get($pdo, $orderId);
}

function bulk_honey_nullable_str(mixed $v): ?string
{
    if ($v === null) {
        return null;
    }
    $s = trim((string) $v);

    return $s === '' ? null : $s;
}

function bulk_honey_nullable_date(mixed $v): ?string
{
    $s = bulk_honey_nullable_str($v);
    if ($s === null) {
        return null;
    }
    $ts = strtotime($s);

    return $ts ? date('Y-m-d', $ts) : null;
}

function bulk_honey_order_confirm(PDO $pdo, int $userId, int $orderId): array
{
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM bulk_honey_orders WHERE id = ? FOR UPDATE');
        $st->execute([$orderId]);
        $order = $st->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ((string) $order['status'] !== 'draft') {
            throw new RuntimeException('Only draft orders can be confirmed.');
        }

        $allocSt = $pdo->prepare(
            'SELECT warehouse_row_id, qty_gallons, supplier_code FROM bulk_honey_order_allocations WHERE order_id = ?'
        );
        $allocSt->execute([$orderId]);
        $allocs = $allocSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($allocs === []) {
            throw new RuntimeException('Order has no warehouse allocations.');
        }

        foreach ($allocs as $a) {
            honey_warehouse_bulk_deduct(
                $pdo,
                (int) $a['warehouse_row_id'],
                (float) $a['qty_gallons']
            );
        }

        $upd = $pdo->prepare(
            'UPDATE bulk_honey_orders SET status = \'confirmed\', confirmed_by = ?, confirmed_at = NOW() WHERE id = ?'
        );
        $upd->execute([$userId, $orderId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return bulk_honey_order_get($pdo, $orderId);
}

function bulk_honey_order_void(PDO $pdo, int $userId, int $orderId): array
{
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM bulk_honey_orders WHERE id = ? FOR UPDATE');
        $st->execute([$orderId]);
        $order = $st->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ((string) $order['status'] !== 'confirmed') {
            throw new RuntimeException('Only confirmed orders can be voided.');
        }

        $allocSt = $pdo->prepare(
            'SELECT warehouse_row_id, qty_gallons FROM bulk_honey_order_allocations WHERE order_id = ?'
        );
        $allocSt->execute([$orderId]);
        foreach ($allocSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $a) {
            honey_warehouse_bulk_restore($pdo, (int) $a['warehouse_row_id'], (float) $a['qty_gallons']);
        }

        $upd = $pdo->prepare(
            'UPDATE bulk_honey_orders SET status = \'voided\', voided_by = ?, voided_at = NOW() WHERE id = ?'
        );
        $upd->execute([$userId, $orderId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return bulk_honey_order_get($pdo, $orderId);
}

function bulk_honey_order_delete_draft(PDO $pdo, int $orderId): void
{
    $st = $pdo->prepare('SELECT status FROM bulk_honey_orders WHERE id = ?');
    $st->execute([$orderId]);
    $status = $st->fetchColumn();
    if ($status === false) {
        throw new RuntimeException('Order not found.');
    }
    if ((string) $status !== 'draft') {
        throw new RuntimeException('Only draft orders can be deleted.');
    }
    $pdo->prepare('DELETE FROM bulk_honey_orders WHERE id = ?')->execute([$orderId]);
}

/** @return array<string, mixed> */
function bulk_honey_order_get(PDO $pdo, int $orderId): array
{
    $st = $pdo->prepare('SELECT * FROM bulk_honey_orders WHERE id = ? LIMIT 1');
    $st->execute([$orderId]);
    $order = $st->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        throw new RuntimeException('Order not found.');
    }

    $lineSt = $pdo->prepare(
        'SELECT * FROM bulk_honey_order_lines WHERE order_id = ? ORDER BY line_sort ASC, id ASC'
    );
    $lineSt->execute([$orderId]);
    $lines = $lineSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $allocSt = $pdo->prepare(
        'SELECT * FROM bulk_honey_order_allocations WHERE order_id = ? ORDER BY id ASC'
    );
    $allocSt->execute([$orderId]);
    $allocs = $allocSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $byLine = [];
    foreach ($allocs as $a) {
        $lid = (int) $a['line_id'];
        $byLine[$lid][] = $a;
    }
    foreach ($lines as &$line) {
        $line['allocations'] = $byLine[(int) $line['id']] ?? [];
    }
    unset($line);

    $supplierSummary = [];
    foreach ($allocs as $a) {
        $code = (string) $a['supplier_code'];
        $supplierSummary[$code] = ($supplierSummary[$code] ?? 0) + (float) $a['qty_gallons'];
    }
    $supplierCodes = [];
    foreach ($supplierSummary as $code => $gal) {
        $supplierCodes[] = ['supplier_code' => $code, 'qty_gallons' => round($gal, 3)];
    }

    return [
        'order' => bulk_honey_format_order($order),
        'lines' => array_map('bulk_honey_format_line', $lines),
        'supplier_codes_used' => $supplierCodes,
    ];
}

/** @return list<array<string, mixed>> */
function bulk_honey_orders_list(PDO $pdo, ?string $status = null, int $limit = 100): array
{
    $sql = 'SELECT id, order_no, customer_name, partner_label, status, total_gallons,
                   total_cogs, total_revenue, total_margin, margin_pct, created_at, confirmed_at
            FROM bulk_honey_orders';
    $params = [];
    if ($status !== null && $status !== '' && $status !== 'all') {
        $sql .= ' WHERE status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT ' . max(1, min(500, $limit));
    $st = $pdo->prepare($sql);
    $st->execute($params);

    return array_map(
        static fn (array $row) => bulk_honey_format_order($row),
        $st->fetchAll(PDO::FETCH_ASSOC) ?: []
    );
}

/** @param array<string, mixed> $row */
function bulk_honey_format_order(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'order_no' => (string) ($row['order_no'] ?? ''),
        'customer_name' => (string) ($row['customer_name'] ?? ''),
        'partner_label' => $row['partner_label'] !== null ? (string) $row['partner_label'] : null,
        'notes' => $row['notes'] ?? null,
        'shipment_date' => $row['shipment_date'] ?? null,
        'courier' => $row['courier'] ?? null,
        'tracking_ref' => $row['tracking_ref'] ?? null,
        'status' => (string) ($row['status'] ?? 'draft'),
        'total_gallons' => round((float) ($row['total_gallons'] ?? 0), 3),
        'total_cogs' => round((float) ($row['total_cogs'] ?? 0), 2),
        'total_revenue' => round((float) ($row['total_revenue'] ?? 0), 2),
        'total_margin' => round((float) ($row['total_margin'] ?? 0), 2),
        'margin_pct' => $row['margin_pct'] !== null ? round((float) $row['margin_pct'], 4) : null,
        'created_at' => $row['created_at'] ?? null,
        'confirmed_at' => $row['confirmed_at'] ?? null,
        'voided_at' => $row['voided_at'] ?? null,
    ];
}

/** @param array<string, mixed> $row */
function bulk_honey_format_line(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'variant' => (string) ($row['variant'] ?? ''),
        'qty_gallons' => round((float) ($row['qty_gallons'] ?? 0), 3),
        'unit_sale_price' => round((float) ($row['unit_sale_price'] ?? 0), 4),
        'line_cogs' => round((float) ($row['line_cogs'] ?? 0), 2),
        'cogs_per_gallon' => round((float) ($row['cogs_per_gallon'] ?? 0), 4),
        'line_revenue' => round((float) ($row['line_revenue'] ?? 0), 2),
        'line_margin' => round((float) ($row['line_margin'] ?? 0), 2),
        'line_margin_pct' => $row['line_margin_pct'] !== null ? round((float) $row['line_margin_pct'], 4) : null,
        'negotiation_mode' => $row['negotiation_mode'] ?? null,
        'target_margin_pct' => $row['target_margin_pct'] !== null
            ? round((float) $row['target_margin_pct'], 4) : null,
        'allocations' => array_map(static function (array $a): array {
            return [
                'id' => (int) $a['id'],
                'warehouse_row_id' => (int) $a['warehouse_row_id'],
                'supplier_code' => (string) ($a['supplier_code'] ?? ''),
                'variant' => (string) ($a['variant'] ?? ''),
                'qty_gallons' => round((float) ($a['qty_gallons'] ?? 0), 3),
                'unit_cost_per_gallon' => $a['unit_cost_per_gallon'] !== null
                    ? round((float) $a['unit_cost_per_gallon'], 4) : null,
                'line_cogs_slice' => round((float) ($a['line_cogs_slice'] ?? 0), 2),
            ];
        }, $row['allocations'] ?? []),
    ];
}
