<?php

declare(strict_types=1);

require_once __DIR__ . '/honey_parcel_item_parser.php';
require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/material_inventory.php';

function parcel_pack_tables_ready(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $pdo->query('SELECT 1 FROM parcel_pack_tiers LIMIT 1');
        $ready = true;
    } catch (Throwable) {
        $ready = false;
    }

    return $ready;
}

/**
 * @return array{honey_1L_count: float, dept_units: float, jar_units: float, honey_250ml: float, honey_350ml: float}
 */
function parcel_pack_order_shape_from_parsed(array $parsed): array
{
    $honey1L = 0.0;
    $h250 = 0.0;
    $h350 = 0.0;
    foreach ($parsed['deltas'] ?? [] as $d) {
        $size = (string) ($d['size'] ?? '');
        $qty = (float) ($d['qty'] ?? 0);
        if ($size === 'liter') {
            $honey1L += $qty;
        } elseif ($size === '250ml') {
            $h250 += $qty;
        } elseif ($size === '350ml') {
            $h350 += $qty;
        }
    }

    $deptUnits = 0.0;
    $jarUnits = 0.0;
    foreach ($parsed['other_items'] ?? [] as $item) {
        $key = isset($item['product_key']) ? trim((string) $item['product_key']) : '';
        if ($key === '') {
            $key = dept_inventory_resolve_other_item((string) ($item['label'] ?? '')) ?? '';
        }
        $qty = (float) ($item['qty'] ?? 0);
        if ($qty <= 0 || $key === '') {
            continue;
        }
        if ($key === 'pt' || $key === 'brc') {
            $deptUnits += $qty;
        } elseif ($key === 'jar_cacao') {
            $jarUnits += $qty;
        }
    }

    return [
        'honey_1L_count' => round($honey1L, 4),
        'dept_units' => round($deptUnits, 4),
        'jar_units' => round($jarUnits, 4),
        'honey_250ml' => round($h250, 4),
        'honey_350ml' => round($h350, 4),
    ];
}

/**
 * @param array<string, mixed> $rule
 * @param array<string, float> $shape
 */
function parcel_pack_tier_matches(array $rule, array $shape): bool
{
    if (!empty($rule['honey_250_350_solo'])) {
        return false;
    }
    if (isset($rule['honey_1L_count'])) {
        $want = (int) $rule['honey_1L_count'];

        return (int) round((float) ($shape['honey_1L_count'] ?? 0)) === $want;
    }
    if (isset($rule['dept_units_min'], $rule['dept_units_max'])) {
        $units = (float) ($shape['dept_units'] ?? 0);
        if ($units <= 0) {
            return false;
        }

        return $units >= (float) $rule['dept_units_min'] && $units <= (float) $rule['dept_units_max'];
    }
    if (isset($rule['jar_units_min'], $rule['jar_units_max'])) {
        $units = (float) ($shape['jar_units'] ?? 0);
        if ($units <= 0) {
            return false;
        }

        return $units >= (float) $rule['jar_units_min'] && $units <= (float) $rule['jar_units_max'];
    }

    return false;
}

function parcel_pack_pick_honey_tier_key(array $shape): ?string
{
    $count = (int) round((float) ($shape['honey_1L_count'] ?? 0));
    if ($count === 1) {
        return 'honey_1x_1L';
    }
    if ($count === 2) {
        return 'honey_2x_1L';
    }
    if ($count >= 3) {
        return 'honey_3x_1L';
    }

    return null;
}

function parcel_pack_pick_dept_tier_key(array $shape): ?string
{
    $units = (float) ($shape['dept_units'] ?? 0);
    if ($units <= 0) {
        return null;
    }
    if ($units <= 2) {
        return 'dept_1_2_units';
    }
    if ($units <= 4) {
        return 'dept_3_4_units';
    }

    return 'dept_3_4_units';
}

function parcel_pack_pick_cacao_tier_key(array $shape): ?string
{
    $units = (float) ($shape['jar_units'] ?? 0);
    if ($units <= 0) {
        return null;
    }
    if ($units <= 2) {
        return 'cacao_1_2_jars';
    }

    return 'cacao_3_4_jars';
}

/**
 * @return list<string>
 */
function parcel_pack_resolve_tier_keys_from_shape(array $shape): array
{
    $keys = [];
    $honeyKey = parcel_pack_pick_honey_tier_key($shape);
    if ($honeyKey !== null) {
        $keys[] = $honeyKey;
    }
    $deptKey = parcel_pack_pick_dept_tier_key($shape);
    if ($deptKey !== null) {
        $keys[] = $deptKey;
    }
    $cacaoKey = parcel_pack_pick_cacao_tier_key($shape);
    if ($cacaoKey !== null) {
        $keys[] = $cacaoKey;
    }

    return $keys;
}

/**
 * @return list<string>
 */
function parcel_pack_resolve_tier_keys_from_items_raw(PDO $pdo, string $itemsRaw): array
{
    if (!parcel_pack_tables_ready($pdo)) {
        return [];
    }
    $parsed = honey_parcel_parse_items_string($itemsRaw);
    $shape = parcel_pack_order_shape_from_parsed($parsed);

    return parcel_pack_resolve_tier_keys_from_shape($shape);
}

/** @return array<string, mixed>|null */
function parcel_pack_fetch_tier_by_key(PDO $pdo, string $tierKey, bool $activeOnly = true): ?array
{
    $sql = 'SELECT * FROM parcel_pack_tiers WHERE tier_key = ?';
    if ($activeOnly) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' LIMIT 1';
    $st = $pdo->prepare($sql);
    $st->execute([$tierKey]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/** @return list<array<string, mixed>> */
function parcel_pack_fetch_tier_lines(PDO $pdo, int $tierId): array
{
    $st = $pdo->prepare(
        'SELECT l.*, s.name AS stock_name, s.current_price, s.unit AS stock_unit, s.on_hand
         FROM parcel_pack_tier_lines l
         LEFT JOIN stocks s ON s.id = l.stock_id
         WHERE l.tier_id = ?
         ORDER BY l.sort_order ASC, l.id ASC'
    );
    $st->execute([$tierId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row) use ($pdo): array {
        return [
            'id' => (int) $row['id'],
            'tier_id' => (int) $row['tier_id'],
            'stock_id' => $row['stock_id'] !== null ? (int) $row['stock_id'] : null,
            'stock_name' => $row['stock_name'] ?? $row['line_label'],
            'line_label' => $row['line_label'],
            'qty_typical' => (float) $row['qty_typical'],
            'qty_min' => $row['qty_min'] !== null ? (float) $row['qty_min'] : null,
            'qty_max' => $row['qty_max'] !== null ? (float) $row['qty_max'] : null,
            'unit' => (string) $row['unit'],
            'sort_order' => (int) $row['sort_order'],
            'unit_cost' => parcel_pack_line_unit_cost($pdo, $row),
        ];
    }, $rows);
}

/**
 * @param array<string, mixed> $lineRow joined stock row
 */
function parcel_pack_line_unit_cost(PDO $pdo, array $lineRow): float
{
    if ($lineRow['stock_id'] === null || (int) $lineRow['stock_id'] < 1) {
        return 0.0;
    }
    $onHand = (float) ($lineRow['on_hand'] ?? 0);
    $stockId = (int) $lineRow['stock_id'];
    $lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);
    if ($lotUnit !== null && $lotUnit > 0) {
        return round($lotUnit, 6);
    }

    return round((float) ($lineRow['current_price'] ?? 0), 6);
}

/**
 * @param list<array<string, mixed>> $lines
 * @return array{typical: float, min: float, max: float, lines: list<array<string, mixed>>}
 */
function parcel_pack_sum_line_costs(array $lines): array
{
    $typical = 0.0;
    $min = 0.0;
    $max = 0.0;
    $out = [];
    foreach ($lines as $line) {
        $unitCost = (float) ($line['unit_cost'] ?? 0);
        $qtyTyp = (float) ($line['qty_typical'] ?? 0);
        $qtyMin = $line['qty_min'] !== null ? (float) $line['qty_min'] : $qtyTyp;
        $qtyMax = $line['qty_max'] !== null ? (float) $line['qty_max'] : $qtyTyp;
        $lineTyp = round($qtyTyp * $unitCost, 4);
        $lineMin = round($qtyMin * $unitCost, 4);
        $lineMax = round($qtyMax * $unitCost, 4);
        $typical += $lineTyp;
        $min += $lineMin;
        $max += $lineMax;
        $out[] = array_merge($line, [
            'cost_typical' => $lineTyp,
            'cost_min' => $lineMin,
            'cost_max' => $lineMax,
        ]);
    }

    return [
        'typical' => round($typical, 4),
        'min' => round($min, 4),
        'max' => round($max, 4),
        'lines' => $out,
    ];
}

/** @return array<string, mixed> */
function parcel_pack_tier_cost_estimate(PDO $pdo, int $tierId): array
{
    $st = $pdo->prepare('SELECT * FROM parcel_pack_tiers WHERE id = ? LIMIT 1');
    $st->execute([$tierId]);
    $tier = $st->fetch(PDO::FETCH_ASSOC);
    if (!$tier) {
        throw new RuntimeException('Parcel pack tier not found.');
    }
    $lines = parcel_pack_fetch_tier_lines($pdo, $tierId);
    $sum = parcel_pack_sum_line_costs($lines);

    return [
        'tier_id' => (int) $tier['id'],
        'tier_key' => (string) $tier['tier_key'],
        'label' => (string) $tier['label'],
        'family' => (string) $tier['family'],
        'cost_typical' => $sum['typical'],
        'cost_min' => $sum['min'],
        'cost_max' => $sum['max'],
        'lines' => $sum['lines'],
    ];
}

/** @return array<string, mixed> */
function parcel_pack_tier_cost_estimate_by_key(PDO $pdo, string $tierKey, bool $activeOnly = true): array
{
    $tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, $activeOnly);
    if ($tier === null) {
        throw new RuntimeException('Parcel pack tier not found: ' . $tierKey);
    }

    return parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);
}

/**
 * @param array<string, mixed> $tierRow
 */
function parcel_pack_tier_is_placeholder(array $tierRow): bool
{
    if ((int) ($tierRow['is_active'] ?? 1) === 0) {
        return true;
    }
    $rule = $tierRow['match_rule'] ?? [];
    if (is_string($rule)) {
        $rule = json_decode($rule, true);
    }

    return is_array($rule) && !empty($rule['honey_250_350_solo']);
}

/** @return array<string, mixed> */
function parcel_pack_preview_tier_by_key(PDO $pdo, string $tierKey): array
{
    $tier = parcel_pack_fetch_tier_by_key($pdo, $tierKey, false);
    if ($tier === null) {
        throw new RuntimeException('Parcel pack tier not found.');
    }
    if (parcel_pack_tier_is_placeholder($tier)) {
        return [
            'tier_key' => $tierKey,
            'tiers' => [],
            'cost_typical' => 0.0,
            'cost_min' => 0.0,
            'cost_max' => 0.0,
            'warnings' => ['This tier is not defined yet — add rules and materials when research is complete.'],
        ];
    }

    $est = parcel_pack_tier_cost_estimate($pdo, (int) $tier['id']);

    return [
        'tier_key' => $tierKey,
        'tiers' => [$est],
        'cost_typical' => $est['cost_typical'],
        'cost_min' => $est['cost_min'],
        'cost_max' => $est['cost_max'],
        'warnings' => [],
    ];
}

/** @return list<array<string, mixed>> */
function parcel_pack_list_tiers(PDO $pdo, bool $includeInactive = true): array
{
    if (!parcel_pack_tables_ready($pdo)) {
        return [];
    }
    $sql = 'SELECT * FROM parcel_pack_tiers';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY family ASC, sort_order ASC, id ASC';
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $out = [];
    foreach ($rows as $row) {
        $tierId = (int) $row['id'];
        $estimate = parcel_pack_tier_cost_estimate($pdo, $tierId);
        $rule = $row['match_rule'];
        if (is_string($rule)) {
            $rule = json_decode($rule, true);
        }
        $out[] = [
            'id' => $tierId,
            'family' => (string) $row['family'],
            'tier_key' => (string) $row['tier_key'],
            'label' => (string) $row['label'],
            'match_rule' => is_array($rule) ? $rule : [],
            'is_active' => (int) $row['is_active'] === 1,
            'sort_order' => (int) $row['sort_order'],
            'cost_typical' => $estimate['cost_typical'],
            'cost_min' => $estimate['cost_min'],
            'cost_max' => $estimate['cost_max'],
            'lines' => $estimate['lines'],
        ];
    }

    return $out;
}

/** @return array<string, mixed> */
function parcel_pack_preview_from_items_raw(PDO $pdo, string $itemsRaw): array
{
    $parsed = honey_parcel_parse_items_string($itemsRaw);
    $shape = parcel_pack_order_shape_from_parsed($parsed);
    $tierKeys = parcel_pack_resolve_tier_keys_from_shape($shape);
    $tiers = [];
    $typical = 0.0;
    $min = 0.0;
    $max = 0.0;
    foreach ($tierKeys as $key) {
        try {
            $est = parcel_pack_tier_cost_estimate_by_key($pdo, $key);
            $tiers[] = $est;
            $typical += (float) $est['cost_typical'];
            $min += (float) $est['cost_min'];
            $max += (float) $est['cost_max'];
        } catch (Throwable) {
            continue;
        }
    }

    $warnings = [];
    if ($shape['honey_250ml'] > 0 || $shape['honey_350ml'] > 0) {
        $warnings[] = '250ml/350ml honey rules are not defined yet (TBD tier).';
    }
    if ($parsed['errors'] !== []) {
        foreach ($parsed['errors'] as $err) {
            $warnings[] = $err;
        }
    }
    if ($tierKeys === [] && trim($itemsRaw) !== '') {
        $warnings[] = 'No parcel pack tier matched this order shape.';
    }

    return [
        'items_raw' => $itemsRaw,
        'order_shape' => $shape,
        'tier_keys' => $tierKeys,
        'tiers' => $tiers,
        'cost_typical' => round($typical, 4),
        'cost_min' => round($min, 4),
        'cost_max' => round($max, 4),
        'warnings' => $warnings,
        'parsed_errors' => $parsed['errors'],
    ];
}

/** @return array<string, mixed> */
function parcel_pack_save_tier_line(
    PDO $pdo,
    int $tierId,
    ?int $lineId,
    ?int $stockId,
    ?string $lineLabel,
    float $qtyTypical,
    ?float $qtyMin,
    ?float $qtyMax,
    string $unit,
    int $sortOrder
): array {
    if ($qtyTypical <= 0) {
        throw new RuntimeException('Typical quantity must be > 0.');
    }
    if ($lineId !== null && $lineId > 0) {
        $pdo->prepare(
            'UPDATE parcel_pack_tier_lines
             SET stock_id = ?, line_label = ?, qty_typical = ?, qty_min = ?, qty_max = ?, unit = ?, sort_order = ?
             WHERE id = ? AND tier_id = ?'
        )->execute([
            $stockId,
            $lineLabel,
            $qtyTypical,
            $qtyMin,
            $qtyMax,
            $unit,
            $sortOrder,
            $lineId,
            $tierId,
        ]);
    } else {
        $pdo->prepare(
            'INSERT INTO parcel_pack_tier_lines
             (tier_id, stock_id, line_label, qty_typical, qty_min, qty_max, unit, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $tierId,
            $stockId,
            $lineLabel,
            $qtyTypical,
            $qtyMin,
            $qtyMax,
            $unit,
            $sortOrder,
        ]);
        $lineId = (int) $pdo->lastInsertId();
    }

    $lines = parcel_pack_fetch_tier_lines($pdo, $tierId);

    return ['line_id' => $lineId, 'lines' => $lines];
}

function parcel_pack_delete_tier_line(PDO $pdo, int $lineId, int $tierId): void
{
    $pdo->prepare('DELETE FROM parcel_pack_tier_lines WHERE id = ? AND tier_id = ?')->execute([$lineId, $tierId]);
}

/**
 * Reference parcel add-on for product family (for Costing list).
 *
 * @return array{scenario: string, cost_typical: float, cost_min: float, cost_max: float}|null
 */
function parcel_pack_reference_addon_for_product(PDO $pdo, ?string $deptProductKey, ?string $honeySize): ?array
{
    if (!parcel_pack_tables_ready($pdo)) {
        return null;
    }
    if ($deptProductKey === 'pt' || $deptProductKey === 'brc') {
        $est = parcel_pack_tier_cost_estimate_by_key($pdo, 'dept_1_2_units');

        return [
            'scenario' => '1–2 units parcel',
            'cost_typical' => $est['cost_typical'],
            'cost_min' => $est['cost_min'],
            'cost_max' => $est['cost_max'],
        ];
    }
    if ($deptProductKey === 'jar_cacao') {
        $est = parcel_pack_tier_cost_estimate_by_key($pdo, 'cacao_1_2_jars');

        return [
            'scenario' => '1–2 jars parcel',
            'cost_typical' => $est['cost_typical'],
            'cost_min' => $est['cost_min'],
            'cost_max' => $est['cost_max'],
        ];
    }
    if ($deptProductKey === null || $deptProductKey === '') {
        if ($honeySize === 'liter') {
            $est = parcel_pack_tier_cost_estimate_by_key($pdo, 'honey_1x_1L');

            return [
                'scenario' => 'solo 1×1L parcel',
                'cost_typical' => $est['cost_typical'],
                'cost_min' => $est['cost_min'],
                'cost_max' => $est['cost_max'],
            ];
        }
    }

    return null;
}
