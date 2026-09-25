<?php

declare(strict_types=1);

/**
 * Daily finished-unit stock-in series for dashboard / inventory trend charts.
 * Source of truth: sku_barcode_stock_events (post barcode cutover).
 *
 * Honey multi-line rules:
 * - family=honey, no variant → one line per variant (all sizes summed)
 * - family=honey, variant set, no size → one line per size for that variant
 * - family=honey, variant + size → single line
 */

require_once __DIR__ . '/honey_inventory.php';
if (!function_exists('honey_bottle_size_label')) {
    require_once __DIR__ . '/honey_bottle_barcodes.php';
}

/** @return list<string> */
function dashboard_stock_ins_family_keys(): array
{
    return ['all', 'honey', 'pt', 'brc', 'jar_cacao', 'honey_comb'];
}

/**
 * @return list<array{key:string,label:string}>
 */
function dashboard_stock_ins_family_options(): array
{
    return [
        ['key' => 'all', 'label' => 'All products'],
        ['key' => 'honey', 'label' => 'Honey'],
        ['key' => 'pt', 'label' => 'Paragis Tea'],
        ['key' => 'brc', 'label' => 'Black Rice Coffee'],
        ['key' => 'jar_cacao', 'label' => 'Jar Cacao'],
        ['key' => 'honey_comb', 'label' => 'Honey Comb'],
    ];
}

/**
 * Product-family lines used when filter family = "all".
 *
 * @return list<array{key:string,label:string,color:string}>
 */
function dashboard_stock_ins_product_series_defs(): array
{
    return [
        ['key' => 'honey', 'label' => 'Honey', 'color' => '#C8900A'],
        ['key' => 'pt', 'label' => 'Paragis Tea', 'color' => '#5B8C5A'],
        ['key' => 'brc', 'label' => 'Black Rice Coffee', 'color' => '#3A96DD'],
        ['key' => 'jar_cacao', 'label' => 'Jar Cacao', 'color' => '#8B4513'],
        ['key' => 'honey_comb', 'label' => 'Honey Comb', 'color' => '#7C6BC4'],
    ];
}

/**
 * @return list<array{key:string,label:string,color:string}>
 */
function dashboard_stock_ins_variant_series_defs(): array
{
    return [
        ['key' => 'light', 'label' => 'Light', 'color' => '#E6C35C'],
        ['key' => 'amber', 'label' => 'Amber', 'color' => '#C8900A'],
        ['key' => 'dark', 'label' => 'Dark', 'color' => '#5C3D2E'],
    ];
}

/**
 * @return list<array{key:string,label:string,color:string}>
 */
function dashboard_stock_ins_size_series_defs(): array
{
    $colors = [
        '250ml' => '#3A96DD',
        '350ml' => '#5B8C5A',
        'liter' => '#C8900A',
        'gallon' => '#7C6BC4',
    ];
    $out = [];
    foreach (HONEY_SIZES as $size) {
        $out[] = [
            'key' => $size,
            'label' => function_exists('honey_bottle_size_label')
                ? honey_bottle_size_label($size)
                : $size,
            'color' => $colors[$size] ?? '#C8900A',
        ];
    }

    return $out;
}

/**
 * @return list<array{key:string,label:string}>
 */
function dashboard_stock_ins_variant_options(): array
{
    $out = [['key' => '', 'label' => 'All variants']];
    foreach (dashboard_stock_ins_variant_series_defs() as $v) {
        $out[] = ['key' => $v['key'], 'label' => $v['label']];
    }

    return $out;
}

/**
 * @return list<array{key:string,label:string}>
 */
function dashboard_stock_ins_size_options(): array
{
    $out = [['key' => '', 'label' => 'All sizes']];
    foreach (dashboard_stock_ins_size_series_defs() as $s) {
        $out[] = ['key' => $s['key'], 'label' => $s['label']];
    }

    return $out;
}

/**
 * @return array{from:string,to:string,days:int,label:string}
 */
function dashboard_stock_ins_resolve_days(int $days): array
{
    if (!in_array($days, [7, 30, 90], true)) {
        throw new InvalidArgumentException('Invalid days. Use 7, 30, or 90.');
    }
    $to = new DateTimeImmutable('today');
    $from = $to->modify('-' . ($days - 1) . ' days');

    return [
        'from' => $from->format('Y-m-d'),
        'to' => $to->format('Y-m-d'),
        'days' => $days,
        'label' => 'Last ' . $days . ' days',
    ];
}

/**
 * Fill every day in range; each point has date + keys from $seriesDefs (0 default).
 *
 * @param array<string, array<string, int>> $byDateKey  date => [seriesKey => qty]
 * @param list<array{key:string,label:string,color?:string}> $seriesDefs
 * @return array{points:list<array<string,mixed>>,total:int}
 */
function dashboard_stock_ins_fill_points(
    array $range,
    array $byDateKey,
    array $seriesDefs
): array {
    $keys = [];
    foreach ($seriesDefs as $def) {
        $keys[] = $def['key'];
    }

    $points = [];
    $total = 0;
    $cursor = new DateTimeImmutable($range['from']);
    $end = new DateTimeImmutable($range['to']);
    while ($cursor <= $end) {
        $day = $cursor->format('Y-m-d');
        $row = ['date' => $day];
        $dayMap = $byDateKey[$day] ?? [];
        $dayTotal = 0;
        foreach ($keys as $key) {
            $qty = (int) ($dayMap[$key] ?? 0);
            $row[$key] = $qty;
            $dayTotal += $qty;
        }
        if (count($keys) === 1) {
            $row['qty'] = $dayTotal;
        }
        $points[] = $row;
        $total += $dayTotal;
        $cursor = $cursor->modify('+1 day');
    }

    return ['points' => $points, 'total' => $total];
}

/**
 * @return array{
 *   family:string,
 *   variant:string,
 *   size:string,
 *   filter:array{from:string,to:string,days:int,label:string},
 *   chart_mode:string,
 *   group_by:?string,
 *   series:list<array{key:string,label:string,color:string}>,
 *   points:list<array<string,mixed>>,
 *   total:int,
 *   families:list<array{key:string,label:string}>,
 *   variants:list<array{key:string,label:string}>,
 *   sizes:list<array{key:string,label:string}>
 * }
 */
function dashboard_stock_ins_daily_series(
    PDO $pdo,
    string $family = 'all',
    string $variant = '',
    string $size = '',
    int $days = 30
): array {
    $family = strtolower(trim($family));
    if ($family === '') {
        $family = 'all';
    }
    if (!in_array($family, dashboard_stock_ins_family_keys(), true)) {
        throw new InvalidArgumentException('Invalid family.');
    }

    $variant = strtolower(trim($variant));
    $size = strtolower(trim($size));
    if ($family !== 'honey') {
        $variant = '';
        $size = '';
    } else {
        if ($variant !== '' && !in_array($variant, ['light', 'amber', 'dark'], true)) {
            throw new InvalidArgumentException('Invalid honey variant.');
        }
        if ($size !== '' && !in_array($size, HONEY_SIZES, true)) {
            throw new InvalidArgumentException('Invalid honey size.');
        }
    }

    $range = dashboard_stock_ins_resolve_days($days);
    $fromDt = $range['from'] . ' 00:00:00';
    $toExclusive = (new DateTimeImmutable($range['to']))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

    $baseWhere = ['e.scanned_at >= ?', 'e.scanned_at < ?'];
    $baseParams = [$fromDt, $toExclusive];

    $chartMode = 'single';
    $groupBy = null;
    /** @var list<array{key:string,label:string,color:string}> $series */
    $series = [['key' => 'qty', 'label' => 'Stocked in', 'color' => '#C8900A']];
    $byDateKey = [];

    try {
        if ($family === 'all') {
            // Multi-line: one series per product family
            $chartMode = 'multi';
            $groupBy = 'family';
            $series = dashboard_stock_ins_product_series_defs();
            $sql = 'SELECT DATE(e.scanned_at) AS d,
                           CASE
                             WHEN e.kind = "honey" THEN "honey"
                             ELSE s.product_key
                           END AS series_key,
                           COALESCE(SUM(e.qty), 0) AS qty
                    FROM sku_barcode_stock_events e
                    INNER JOIN product_sku_barcodes s ON s.id = e.sku_id
                    WHERE e.scanned_at >= ? AND e.scanned_at < ?
                    GROUP BY DATE(e.scanned_at),
                             CASE
                               WHEN e.kind = "honey" THEN "honey"
                               ELSE s.product_key
                             END
                    ORDER BY d ASC';
            $st = $pdo->prepare($sql);
            $st->execute($baseParams);
            $allowed = [];
            foreach ($series as $def) {
                $allowed[$def['key']] = true;
            }
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $d = (string) $row['d'];
                $k = (string) $row['series_key'];
                if ($k === '' || !isset($allowed[$k])) {
                    continue;
                }
                if (!isset($byDateKey[$d])) {
                    $byDateKey[$d] = [];
                }
                $byDateKey[$d][$k] = (int) $row['qty'];
            }
        } elseif ($family === 'honey' && $variant === '') {
            // Multi-line: one series per variant (optional size filter still applies)
            $chartMode = 'multi';
            $groupBy = 'variant';
            $series = dashboard_stock_ins_variant_series_defs();
            $where = array_merge($baseWhere, ['e.kind = "honey"', 's.kind = "honey"']);
            $params = $baseParams;
            if ($size !== '') {
                $where[] = 's.size = ?';
                $params[] = $size;
            }
            $sql = 'SELECT DATE(e.scanned_at) AS d, LOWER(s.variant) AS series_key, COALESCE(SUM(e.qty), 0) AS qty
                    FROM sku_barcode_stock_events e
                    INNER JOIN product_sku_barcodes s ON s.id = e.sku_id
                    WHERE ' . implode(' AND ', $where) . '
                    GROUP BY DATE(e.scanned_at), LOWER(s.variant)
                    ORDER BY d ASC';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $d = (string) $row['d'];
                $k = (string) $row['series_key'];
                if ($k === '') {
                    continue;
                }
                if (!isset($byDateKey[$d])) {
                    $byDateKey[$d] = [];
                }
                $byDateKey[$d][$k] = (int) $row['qty'];
            }
        } elseif ($family === 'honey' && $variant !== '' && $size === '') {
            // Multi-line: one series per size for the chosen variant
            $chartMode = 'multi';
            $groupBy = 'size';
            $series = dashboard_stock_ins_size_series_defs();
            $where = array_merge($baseWhere, [
                'e.kind = "honey"',
                's.kind = "honey"',
                'LOWER(s.variant) = ?',
            ]);
            $params = array_merge($baseParams, [$variant]);
            $sql = 'SELECT DATE(e.scanned_at) AS d, s.size AS series_key, COALESCE(SUM(e.qty), 0) AS qty
                    FROM sku_barcode_stock_events e
                    INNER JOIN product_sku_barcodes s ON s.id = e.sku_id
                    WHERE ' . implode(' AND ', $where) . '
                    GROUP BY DATE(e.scanned_at), s.size
                    ORDER BY d ASC';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $d = (string) $row['d'];
                $k = (string) $row['series_key'];
                if ($k === '') {
                    continue;
                }
                if (!isset($byDateKey[$d])) {
                    $byDateKey[$d] = [];
                }
                $byDateKey[$d][$k] = (int) $row['qty'];
            }
        } else {
            // Single line (specific honey SKU, or any non-honey family)
            $where = $baseWhere;
            $params = $baseParams;
            if ($family === 'honey') {
                $where[] = 'e.kind = "honey"';
                $where[] = 's.kind = "honey"';
                $where[] = 'LOWER(s.variant) = ?';
                $params[] = $variant;
                $where[] = 's.size = ?';
                $params[] = $size;
                $label = ucfirst($variant) . ' · ' . (
                    function_exists('honey_bottle_size_label')
                        ? honey_bottle_size_label($size)
                        : $size
                );
                $series = [['key' => 'qty', 'label' => $label, 'color' => '#C8900A']];
            } else {
                // Single product family (pt / brc / jar_cacao / honey_comb)
                $where[] = 'e.kind = "dept"';
                $where[] = 's.kind = "dept"';
                $where[] = 's.product_key = ?';
                $params[] = $family;
                foreach (dashboard_stock_ins_product_series_defs() as $opt) {
                    if ($opt['key'] === $family) {
                        $series = [['key' => 'qty', 'label' => $opt['label'], 'color' => $opt['color']]];
                        break;
                    }
                }
            }

            $sql = 'SELECT DATE(e.scanned_at) AS d, COALESCE(SUM(e.qty), 0) AS qty
                    FROM sku_barcode_stock_events e
                    INNER JOIN product_sku_barcodes s ON s.id = e.sku_id
                    WHERE ' . implode(' AND ', $where) . '
                    GROUP BY DATE(e.scanned_at)
                    ORDER BY d ASC';
            $st = $pdo->prepare($sql);
            $st->execute($params);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $d = (string) $row['d'];
                $byDateKey[$d] = ['qty' => (int) $row['qty']];
            }
        }
    } catch (Throwable) {
        $byDateKey = [];
    }

    $filled = dashboard_stock_ins_fill_points($range, $byDateKey, $series);

    return [
        'family' => $family,
        'variant' => $variant,
        'size' => $size,
        'filter' => $range,
        'chart_mode' => $chartMode,
        'group_by' => $groupBy,
        'series' => $series,
        'points' => $filled['points'],
        'total' => $filled['total'],
        'families' => dashboard_stock_ins_family_options(),
        'variants' => dashboard_stock_ins_variant_options(),
        'sizes' => dashboard_stock_ins_size_options(),
    ];
}
