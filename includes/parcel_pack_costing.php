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
 * @return array{
 *   honey_1L_count: float,
 *   honey_250ml: float,
 *   honey_350ml: float,
 *   honey_gallon_count: float,
 *   honey_comb_count: float,
 *   dept_pt: float,
 *   dept_brc: float,
 *   dept_units: float,
 *   jar_units: float
 * }
 */
function parcel_pack_order_shape_from_parsed(array $parsed): array
{
    $honey1L = 0.0;
    $h250 = 0.0;
    $h350 = 0.0;
    $hGallon = 0.0;
    foreach ($parsed['deltas'] ?? [] as $d) {
        $size = (string) ($d['size'] ?? '');
        $qty = (float) ($d['qty'] ?? 0);
        if ($size === 'liter') {
            $honey1L += $qty;
        } elseif ($size === '250ml') {
            $h250 += $qty;
        } elseif ($size === '350ml') {
            $h350 += $qty;
        } elseif ($size === 'gallon') {
            $hGallon += $qty;
        }
    }

    $deptPt = 0.0;
    $deptBrc = 0.0;
    $jarUnits = 0.0;
    $honeyComb = 0.0;
    foreach ($parsed['other_items'] ?? [] as $item) {
        $key = isset($item['product_key']) ? trim((string) $item['product_key']) : '';
        if ($key === '') {
            $key = dept_inventory_resolve_other_item((string) ($item['label'] ?? '')) ?? '';
        }
        $label = (string) ($item['label'] ?? '');
        $qty = (float) ($item['qty'] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        if ($key === 'pt') {
            $deptPt += $qty;
        } elseif ($key === 'brc') {
            $deptBrc += $qty;
        } elseif ($key === 'jar_cacao') {
            $jarUnits += $qty;
        } elseif ($key === 'honey_comb') {
            $honeyComb += $qty;
        } elseif ($key === '' && preg_match('/honey\s*comb|honeycomb/i', $label)) {
            $honeyComb += $qty;
        }
    }

    return [
        'honey_1L_count' => round($honey1L, 4),
        'honey_250ml' => round($h250, 4),
        'honey_350ml' => round($h350, 4),
        'honey_gallon_count' => round($hGallon, 4),
        'honey_comb_count' => round($honeyComb, 4),
        'dept_pt' => round($deptPt, 4),
        'dept_brc' => round($deptBrc, 4),
        'dept_units' => round($deptPt + $deptBrc, 4),
        'jar_units' => round($jarUnits, 4),
    ];
}

function parcel_pack_shape_float_eq(float $a, float $b): bool
{
    return abs($a - $b) < 0.0001;
}

/** @param array<string, float> $shape */
function parcel_pack_shape_has_honey(array $shape): bool
{
    return ($shape['honey_1L_count'] ?? 0) > 0
        || ($shape['honey_250ml'] ?? 0) > 0
        || ($shape['honey_350ml'] ?? 0) > 0
        || ($shape['honey_gallon_count'] ?? 0) > 0;
}

/** @param array<string, float> $shape */
function parcel_pack_shape_is_bulk_only(array $shape, string $size): bool
{
    if (($shape['dept_pt'] ?? 0) > 0 || ($shape['dept_brc'] ?? 0) > 0 || ($shape['jar_units'] ?? 0) > 0) {
        return false;
    }
    if (($shape['honey_comb_count'] ?? 0) > 0) {
        return false;
    }
    if ($size === '350') {
        if (($shape['honey_250ml'] ?? 0) > 0 || ($shape['honey_1L_count'] ?? 0) > 0 || ($shape['honey_gallon_count'] ?? 0) > 0) {
            return false;
        }

        return ($shape['honey_350ml'] ?? 0) > 0;
    }
    if ($size === '250') {
        if (($shape['honey_350ml'] ?? 0) > 0 || ($shape['honey_1L_count'] ?? 0) > 0 || ($shape['honey_gallon_count'] ?? 0) > 0) {
            return false;
        }

        return ($shape['honey_250ml'] ?? 0) > 0;
    }

    return false;
}

/** @param array<string, float> $shape */
function parcel_pack_shape_is_solo_honey(array $shape): bool
{
    return parcel_pack_shape_has_honey($shape)
        && ($shape['dept_pt'] ?? 0) <= 0
        && ($shape['dept_brc'] ?? 0) <= 0
        && ($shape['jar_units'] ?? 0) <= 0
        && ($shape['honey_comb_count'] ?? 0) <= 0;
}

/** @param array<string, float> $shape */
function parcel_pack_shape_is_solo_dept_pt(array $shape): bool
{
    return ($shape['dept_pt'] ?? 0) > 0
        && ($shape['dept_brc'] ?? 0) <= 0
        && !parcel_pack_shape_has_honey($shape)
        && ($shape['jar_units'] ?? 0) <= 0
        && ($shape['honey_comb_count'] ?? 0) <= 0;
}

/** @param array<string, float> $shape */
function parcel_pack_shape_is_solo_dept_brc(array $shape): bool
{
    return ($shape['dept_brc'] ?? 0) > 0
        && ($shape['dept_pt'] ?? 0) <= 0
        && !parcel_pack_shape_has_honey($shape)
        && ($shape['jar_units'] ?? 0) <= 0
        && ($shape['honey_comb_count'] ?? 0) <= 0;
}

/** @param array<string, float> $shape */
function parcel_pack_shape_is_solo_jar(array $shape): bool
{
    return ($shape['jar_units'] ?? 0) > 0
        && ($shape['dept_pt'] ?? 0) <= 0
        && ($shape['dept_brc'] ?? 0) <= 0
        && !parcel_pack_shape_has_honey($shape)
        && ($shape['honey_comb_count'] ?? 0) <= 0;
}

/** @param array<string, float> $shape */
function parcel_pack_shape_is_combo(array $shape): bool
{
    $kinds = 0;
    if (parcel_pack_shape_has_honey($shape)) {
        $kinds++;
    }
    if (($shape['dept_pt'] ?? 0) > 0 || ($shape['dept_brc'] ?? 0) > 0) {
        $kinds++;
    }
    if (($shape['jar_units'] ?? 0) > 0) {
        $kinds++;
    }

    return $kinds >= 2;
}

/**
 * @param array<string, mixed> $rule
 * @param array<string, float> $shape
 */
function parcel_pack_tier_matches(array $rule, array $shape): bool
{
    if (isset($rule['bulk_350ml_exact'])) {
        $want = (int) $rule['bulk_350ml_exact'];
        if ((int) round((float) ($shape['honey_350ml'] ?? 0)) !== $want) {
            return false;
        }

        return parcel_pack_shape_is_bulk_only($shape, '350');
    }
    if (isset($rule['bulk_250ml_exact'])) {
        $want = (int) $rule['bulk_250ml_exact'];
        if ((int) round((float) ($shape['honey_250ml'] ?? 0)) !== $want) {
            return false;
        }

        return parcel_pack_shape_is_bulk_only($shape, '250');
    }
    if (!empty($rule['honey_comb_min'])) {
        if (($shape['honey_comb_count'] ?? 0) < (float) $rule['honey_comb_min']) {
            return false;
        }

        return ($shape['honey_comb_count'] ?? 0) > 0
            && !parcel_pack_shape_has_honey($shape)
            && ($shape['dept_pt'] ?? 0) <= 0
            && ($shape['dept_brc'] ?? 0) <= 0
            && ($shape['jar_units'] ?? 0) <= 0;
    }
    if (!empty($rule['combo'])) {
        if (!parcel_pack_shape_is_combo($shape)) {
            return false;
        }
    }
    if (!empty($rule['solo_honey']) && !parcel_pack_shape_is_solo_honey($shape)) {
        return false;
    }
    if (!empty($rule['solo_dept_pt']) && !parcel_pack_shape_is_solo_dept_pt($shape)) {
        return false;
    }
    if (!empty($rule['solo_dept_brc']) && !parcel_pack_shape_is_solo_dept_brc($shape)) {
        return false;
    }
    if (!empty($rule['solo_jar']) && !parcel_pack_shape_is_solo_jar($shape)) {
        return false;
    }

    $intFields = ['honey_1L_count', 'honey_250ml', 'honey_350ml', 'honey_gallon_count', 'jar_units', 'dept_pt', 'dept_brc'];
    foreach ($intFields as $field) {
        if (array_key_exists($field, $rule) && !str_ends_with($field, '_min') && !str_ends_with($field, '_max')) {
            if ((int) round((float) ($shape[$field] ?? 0)) !== (int) $rule[$field]) {
                return false;
            }
        }
    }

    $ranges = [
        ['dept_pt_min', 'dept_pt_max', 'dept_pt'],
        ['dept_brc_min', 'dept_brc_max', 'dept_brc'],
        ['dept_units_min', 'dept_units_max', 'dept_units'],
        ['jar_units_min', 'jar_units_max', 'jar_units'],
    ];
    foreach ($ranges as [$minKey, $maxKey, $shapeKey]) {
        if (isset($rule[$minKey], $rule[$maxKey])) {
            $units = (float) ($shape[$shapeKey] ?? 0);
            if ($units < (float) $rule[$minKey] || $units > (float) $rule[$maxKey]) {
                return false;
            }
        } elseif (isset($rule[$minKey])) {
            if ((float) ($shape[$shapeKey] ?? 0) < (float) $rule[$minKey]) {
                return false;
            }
        }
    }

    $honeyFields = ['honey_1L_count', 'honey_250ml', 'honey_350ml', 'honey_gallon_count'];
    if (!empty($rule['solo_honey']) || !empty($rule['combo'])) {
        foreach ($honeyFields as $field) {
            if (!array_key_exists($field, $rule) && ($shape[$field] ?? 0) > 0) {
                return false;
            }
        }
    }
    if (!empty($rule['combo'])) {
        if (isset($rule['dept_pt_min']) && !isset($rule['dept_brc_min']) && ($shape['dept_brc'] ?? 0) > 0) {
            return false;
        }
        if (isset($rule['dept_brc_min']) && !isset($rule['dept_pt_min']) && ($shape['dept_pt'] ?? 0) > 0) {
            return false;
        }
        if (isset($rule['jar_units_min']) || isset($rule['jar_units'])) {
            if (!isset($rule['dept_pt_min']) && !isset($rule['dept_brc_min'])) {
                if (($shape['dept_pt'] ?? 0) > 0 || ($shape['dept_brc'] ?? 0) > 0) {
                    // jar+honey combo allows dept 0
                }
            }
        }
        if ((isset($rule['dept_pt_min']) || isset($rule['dept_brc_min'])) && !isset($rule['jar_units_min']) && !isset($rule['jar_units'])) {
            if (($shape['jar_units'] ?? 0) > 0) {
                return false;
            }
        }
    }

    return true;
}

function parcel_pack_resolve_tier_key_from_shape(PDO $pdo, array $shape): ?string
{
    if (!parcel_pack_tables_ready($pdo)) {
        return null;
    }
    $st = $pdo->query(
        'SELECT tier_key, match_rule FROM parcel_pack_tiers WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
    );
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as $row) {
        $rule = $row['match_rule'];
        if (is_string($rule)) {
            $rule = json_decode($rule, true);
        }
        if (!is_array($rule)) {
            continue;
        }
        if (parcel_pack_tier_matches($rule, $shape)) {
            return (string) $row['tier_key'];
        }
    }

    return null;
}

function parcel_pack_pick_honey_tier_key(array $shape): ?string
{
    unset($shape);

    return null;
}

function parcel_pack_pick_dept_tier_key(array $shape): ?string
{
    unset($shape);

    return null;
}

function parcel_pack_pick_cacao_tier_key(array $shape): ?string
{
    unset($shape);

    return null;
}

/**
 * @return list<string>
 */
function parcel_pack_resolve_tier_keys_from_shape(array $shape): array
{
    return [];
}

/**
 * @return list<string>
 */
function parcel_pack_resolve_tier_keys_from_shape_db(PDO $pdo, array $shape): array
{
    $key = parcel_pack_resolve_tier_key_from_shape($pdo, $shape);

    return $key !== null ? [$key] : [];
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

    return parcel_pack_resolve_tier_keys_from_shape_db($pdo, $shape);
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
        'SELECT l.*, s.name AS stock_name, s.current_price, s.unit AS stock_unit, s.on_hand,
                s.pool_yield_qty, s.pool_yield_unit
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
    $lineUnit = (string) ($lineRow['unit'] ?? 'pcs');
    $stockName = (string) ($lineRow['stock_name'] ?? $lineRow['line_label'] ?? '');

    $rollCost = null;
    if ($lineUnit === 'bubble_piece' && material_packaging_is_bubble_sized_stock_name($stockName)) {
        $rollCost = material_packaging_generic_bubble_roll_unit_cost($pdo);
    }
    if ($lineUnit === 'foam_piece' && material_packaging_is_foam_sized_stock_name($stockName)) {
        $rollCost = material_packaging_generic_foam_roll_unit_cost($pdo);
    }
    if ($rollCost === null) {
        $lotUnit = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);
        $rollCost = ($lotUnit !== null && $lotUnit > 0)
            ? round($lotUnit, 6)
            : round((float) ($lineRow['current_price'] ?? 0), 6);
    }

    if (in_array($lineUnit, ['bubble_piece', 'foam_piece', 'column'], true)) {
        $yield = (float) ($lineRow['pool_yield_qty'] ?? 0);
        if ($yield > 0) {
            return round($rollCost / $yield, 6);
        }
    }

    return $rollCost;
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
    $rule = $tier['match_rule'] ?? [];
    if (is_string($rule)) {
        $rule = json_decode($rule, true);
    }
    $landed = parcel_pack_tier_landed_summary($pdo, is_array($rule) ? $rule : [], $est);

    return [
        'tier_key' => $tierKey,
        'tiers' => [$est],
        'cost_typical' => $est['cost_typical'],
        'cost_min' => $est['cost_min'],
        'cost_max' => $est['cost_max'],
        'product_cogs' => $landed,
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
            'product_cogs' => parcel_pack_tier_landed_summary(
                $pdo,
                is_array($rule) ? $rule : [],
                $estimate
            ),
        ];
    }

    return $out;
}

/** @return array<string, mixed> */
function parcel_pack_preview_from_items_raw(PDO $pdo, string $itemsRaw): array
{
    $parsed = honey_parcel_parse_items_string($itemsRaw);
    $shape = parcel_pack_order_shape_from_parsed($parsed);
    $tierKey = parcel_pack_resolve_tier_key_from_shape($pdo, $shape);
    $tierKeys = $tierKey !== null ? [$tierKey] : [];
    $tiers = [];
    $typical = 0.0;
    $min = 0.0;
    $max = 0.0;
    $productCogs = null;
    if ($tierKey !== null) {
        try {
            $est = parcel_pack_tier_cost_estimate_by_key($pdo, $tierKey);
            $tiers[] = $est;
            $typical = (float) $est['cost_typical'];
            $min = (float) $est['cost_min'];
            $max = (float) $est['cost_max'];
            $tierRow = parcel_pack_fetch_tier_by_key($pdo, $tierKey, false);
            $rule = $tierRow['match_rule'] ?? [];
            if (is_string($rule)) {
                $rule = json_decode($rule, true);
            }
            $productCogs = parcel_pack_tier_landed_summary($pdo, is_array($rule) ? $rule : [], $est);
        } catch (Throwable) {
            $tierKeys = [];
            $productCogs = null;
        }
    }

    $warnings = [];
    if ($parsed['errors'] !== []) {
        foreach ($parsed['errors'] as $err) {
            $warnings[] = $err;
        }
    }
    if ($tierKey === null && trim($itemsRaw) !== '') {
        $warnings[] = 'No parcel pack recipe matched this order shape.';
    }

    return [
        'items_raw' => $itemsRaw,
        'order_shape' => $shape,
        'tier_keys' => $tierKeys,
        'tier_key' => $tierKey,
        'tiers' => $tiers,
        'cost_typical' => round($typical, 4),
        'cost_min' => round($min, 4),
        'cost_max' => round($max, 4),
        'product_cogs' => $productCogs,
        'warnings' => $warnings,
        'parsed_errors' => $parsed['errors'],
    ];
}

/**
 * Resolve fulfillment packaging lines for ship deduct (typical qty per line).
 *
 * @return list<array{stock_id: int, qty: float, unit: string, stock_name: string}>
 */
function parcel_pack_fulfillment_lines_for_items_raw(PDO $pdo, string $itemsRaw): array
{
    $preview = parcel_pack_preview_from_items_raw($pdo, $itemsRaw);
    if (($preview['tier_key'] ?? null) === null || $preview['tiers'] === []) {
        throw new RuntimeException('No fulfillment packaging recipe for this parcel.');
    }
    $tier = $preview['tiers'][0];
    $out = [];
    foreach ($tier['lines'] as $line) {
        $stockId = $line['stock_id'] ?? null;
        if ($stockId === null || (int) $stockId < 1) {
            continue;
        }
        $out[] = [
            'stock_id' => (int) $stockId,
            'qty' => (float) ($line['qty_typical'] ?? 0),
            'unit' => (string) ($line['unit'] ?? 'pcs'),
            'stock_name' => (string) ($line['stock_name'] ?? $line['line_label'] ?? ''),
        ];
    }
    if ($out === []) {
        throw new RuntimeException('Fulfillment recipe has no stock lines.');
    }

    return $out;
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

function parcel_pack_formula_current_unit_sql(): string
{
    return 'CASE
        WHEN f.stock_id IS NULL THEN f.price_at_creation
        WHEN s.production_deduct = 0 AND COALESCE(s.pool_yield_qty, 0) > 0 THEN s.current_price / s.pool_yield_qty
        ELSE s.current_price
    END';
}

/** @param array<string, mixed> $rule */
function parcel_pack_representative_shape_from_match_rule(array $rule): array
{
    $shape = [
        'honey_1L_count' => 0.0,
        'honey_250ml' => 0.0,
        'honey_350ml' => 0.0,
        'honey_gallon_count' => 0.0,
        'honey_comb_count' => 0.0,
        'dept_pt' => 0.0,
        'dept_brc' => 0.0,
        'jar_units' => 0.0,
    ];
    foreach (['honey_1L_count', 'honey_250ml', 'honey_350ml', 'honey_gallon_count', 'honey_comb_count'] as $field) {
        if (isset($rule[$field])) {
            $shape[$field] = (float) $rule[$field];
        }
    }
    if (isset($rule['bulk_350ml_exact'])) {
        $shape['honey_350ml'] = (float) $rule['bulk_350ml_exact'];
    }
    if (isset($rule['bulk_250ml_exact'])) {
        $shape['honey_250ml'] = (float) $rule['bulk_250ml_exact'];
    }
    if (isset($rule['dept_pt_min'])) {
        $shape['dept_pt'] = (float) $rule['dept_pt_min'];
    }
    if (isset($rule['dept_brc_min'])) {
        $shape['dept_brc'] = (float) $rule['dept_brc_min'];
    }
    if (isset($rule['jar_units']) && !isset($rule['jar_units_min'])) {
        $shape['jar_units'] = (float) $rule['jar_units'];
    } elseif (isset($rule['jar_units_min'])) {
        $shape['jar_units'] = (float) $rule['jar_units_min'];
    }

    return $shape;
}

/**
 * @return array{product_id: int, name: string, unit_cogs: float}|null
 */
function parcel_pack_fetch_product_unit_cogs(
    PDO $pdo,
    ?string $deptProductKey,
    ?string $honeySize,
    string $honeyVariant = 'amber'
): ?array {
    $unitSql = parcel_pack_formula_current_unit_sql();
    $sql = "SELECT p.id, p.name,
            COALESCE(SUM(f.qty_per_unit * ({$unitSql})), 0) AS unit_cogs
            FROM products p
            LEFT JOIN product_formula f ON f.product_id = p.id
            LEFT JOIN stocks s ON s.id = f.stock_id
            WHERE 1=1";
    $params = [];
    if ($deptProductKey !== null && $deptProductKey !== '') {
        $sql .= ' AND p.dept_product_key = ?';
        $params[] = $deptProductKey;
    } else {
        $sql .= " AND (p.dept_product_key IS NULL OR p.dept_product_key = '')";
        if ($honeySize !== null && $honeySize !== '') {
            $sql .= ' AND p.size = ?';
            $params[] = $honeySize;
        }
        if ($honeyVariant !== '') {
            $sql .= ' AND LOWER(TRIM(p.variant)) = ?';
            $params[] = strtolower(trim($honeyVariant));
        }
    }
    $sql .= ' GROUP BY p.id ORDER BY p.id ASC LIMIT 1';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }

    return [
        'product_id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'unit_cogs' => round((float) ($row['unit_cogs'] ?? 0), 4),
    ];
}

/**
 * @param array<string, mixed> $matchRule
 * @return list<array{product_id: int|null, label: string, qty: float, unit_cogs: float|null, line_cogs: float|null, note: string|null}>
 */
function parcel_pack_tier_product_cogs_lines(PDO $pdo, array $matchRule): array
{
    $shape = parcel_pack_representative_shape_from_match_rule($matchRule);
    $lines = [];
    $honeyVariant = 'amber';

    $addDept = static function (string $key, float $qty) use ($pdo, &$lines): void {
        if ($qty <= 0) {
            return;
        }
        $product = parcel_pack_fetch_product_unit_cogs($pdo, $key, null);
        if ($product === null) {
            $lines[] = [
                'product_id' => null,
                'label' => (dept_product_by_key($key)['label'] ?? $key),
                'qty' => $qty,
                'unit_cogs' => null,
                'line_cogs' => null,
                'note' => 'No costing product found',
            ];

            return;
        }
        $unit = (float) $product['unit_cogs'];
        $lines[] = [
            'product_id' => $product['product_id'],
            'label' => $product['name'],
            'qty' => $qty,
            'unit_cogs' => $unit,
            'line_cogs' => round($qty * $unit, 4),
            'note' => null,
        ];
    };

    $addHoney = static function (string $size, float $qty, string $sizeLabel) use ($pdo, &$lines, $honeyVariant): void {
        if ($qty <= 0) {
            return;
        }
        $product = parcel_pack_fetch_product_unit_cogs($pdo, null, $size, $honeyVariant);
        if ($product === null) {
            $lines[] = [
                'product_id' => null,
                'label' => $sizeLabel . ' honey',
                'qty' => $qty,
                'unit_cogs' => null,
                'line_cogs' => null,
                'note' => 'No costing product for ' . $honeyVariant . ' ' . $size,
            ];

            return;
        }
        $unit = (float) $product['unit_cogs'];
        $lines[] = [
            'product_id' => $product['product_id'],
            'label' => $product['name'],
            'qty' => $qty,
            'unit_cogs' => $unit,
            'line_cogs' => round($qty * $unit, 4),
            'note' => null,
        ];
    };

    $addDept('pt', (float) ($shape['dept_pt'] ?? 0));
    $addDept('brc', (float) ($shape['dept_brc'] ?? 0));
    $addDept('jar_cacao', (float) ($shape['jar_units'] ?? 0));
    $addDept('honey_comb', (float) ($shape['honey_comb_count'] ?? 0));
    $addHoney('liter', (float) ($shape['honey_1L_count'] ?? 0), '1L');
    $addHoney('350ml', (float) ($shape['honey_350ml'] ?? 0), '350ml');
    $addHoney('250ml', (float) ($shape['honey_250ml'] ?? 0), '250ml');
    $addHoney('gallon', (float) ($shape['honey_gallon_count'] ?? 0), 'Gallon');

    return $lines;
}

/**
 * @param array<string, mixed> $matchRule
 * @param array<string, mixed> $packEstimate
 * @return array{
 *   lines: list<array<string, mixed>>,
 *   products_total: float,
 *   packaging_typical: float,
 *   landed_typical: float,
 *   honey_variant_ref: string
 * }
 */
function parcel_pack_tier_landed_summary(PDO $pdo, array $matchRule, array $packEstimate): array
{
    $lines = parcel_pack_tier_product_cogs_lines($pdo, $matchRule);
    $productsTotal = 0.0;
    foreach ($lines as $line) {
        if ($line['line_cogs'] !== null) {
            $productsTotal += (float) $line['line_cogs'];
        }
    }
    $productsTotal = round($productsTotal, 4);
    $packaging = round((float) ($packEstimate['cost_typical'] ?? 0), 4);

    return [
        'lines' => $lines,
        'products_total' => $productsTotal,
        'packaging_typical' => $packaging,
        'landed_typical' => round($productsTotal + $packaging, 4),
        'honey_variant_ref' => 'amber',
    ];
}

/**
 * Reference parcel add-on for product family (for Costing list).
 *
 * @return array{tier_key: string, scenario: string, cost_typical: float, cost_min: float, cost_max: float}|null
 */
function parcel_pack_reference_addon_for_product(PDO $pdo, ?string $deptProductKey, ?string $honeySize): ?array
{
    if (!parcel_pack_tables_ready($pdo)) {
        return null;
    }
    $tierKey = null;
    $scenario = null;
    if ($deptProductKey === 'pt') {
        $tierKey = 'dept_pt_1_2';
        $scenario = 'PT solo 1–2 units parcel';
    } elseif ($deptProductKey === 'brc') {
        $tierKey = 'dept_brc_1_2';
        $scenario = 'BRC solo 1–2 units parcel';
    } elseif ($deptProductKey === 'jar_cacao') {
        $tierKey = 'cacao_1_jar';
        $scenario = 'Cacao solo 1 jar parcel';
    } elseif ($deptProductKey === 'honey_comb') {
        $tierKey = 'honey_comb_solo';
        $scenario = 'Honey comb solo parcel (Excel row 13)';
    } elseif ($deptProductKey === null || $deptProductKey === '') {
        $honeyTierMap = [
            '250ml' => ['honey_250ml_solo', 'solo 250ml honey parcel'],
            '350ml' => ['honey_350ml_solo', 'solo 350ml honey parcel'],
            'liter' => ['honey_1x_1L', 'solo 1×1L honey parcel'],
            'gallon' => ['honey_1x_gallon', 'solo 1 gallon honey parcel'],
        ];
        if ($honeySize !== null && isset($honeyTierMap[$honeySize])) {
            [$tierKey, $scenario] = $honeyTierMap[$honeySize];
        }
    }
    if ($tierKey === null) {
        return null;
    }
    try {
        $est = parcel_pack_tier_cost_estimate_by_key($pdo, $tierKey);
    } catch (Throwable) {
        return null;
    }

    return [
        'tier_key' => $tierKey,
        'scenario' => $scenario,
        'cost_typical' => $est['cost_typical'],
        'cost_min' => $est['cost_min'],
        'cost_max' => $est['cost_max'],
    ];
}
