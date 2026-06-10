<?php

declare(strict_types=1);

/**
 * Dashboard packaging / dept inventory aggregates (date-filtered).
 */

/** @return array{period: string, from: string|null, to: string|null, label: string}|null */
function dashboard_inventory_resolve_range(?string $period, ?string $from, ?string $to): ?array
{
    $period = strtolower(trim((string) $period));
    $today = new DateTimeImmutable('today');

    if ($period === '' || $period === 'all') {
        return null;
    }

    if ($period === 'custom') {
        $from = trim((string) $from);
        $to = trim((string) $to);
        if ($from === '' || $to === '') {
            throw new InvalidArgumentException('Custom range requires from and to dates.');
        }
        $fromDt = DateTimeImmutable::createFromFormat('Y-m-d', $from);
        $toDt = DateTimeImmutable::createFromFormat('Y-m-d', $to);
        if (!$fromDt || !$toDt || $fromDt > $toDt) {
            throw new InvalidArgumentException('Invalid custom date range.');
        }

        return [
            'period' => 'custom',
            'from' => $fromDt->format('Y-m-d'),
            'to' => $toDt->format('Y-m-d'),
            'label' => $fromDt->format('M j, Y') . ' – ' . $toDt->format('M j, Y'),
        ];
    }

    $rangeFrom = $today->format('Y-m-d');
    $rangeTo = $today->format('Y-m-d');
    $label = 'Today';

    if ($period === 'day') {
        // already set
    } elseif ($period === 'week') {
        $rangeFrom = $today->modify('monday this week')->format('Y-m-d');
        $label = 'This week';
    } elseif ($period === 'month') {
        $rangeFrom = $today->format('Y-m-01');
        $label = 'This month';
    } elseif ($period === 'year') {
        $rangeFrom = $today->format('Y-01-01');
        $label = 'This year';
    } else {
        throw new InvalidArgumentException('Unknown period filter.');
    }

    return [
        'period' => $period,
        'from' => $rangeFrom,
        'to' => $rangeTo,
        'label' => $label,
    ];
}

/** @return array<string, mixed> */
function dashboard_honey_packaging_snapshot(PDO $pdo, ?array $range): array
{
    $where = '';
    $params = [];
    if ($range !== null) {
        $where = ' WHERE bottling_date >= ? AND bottling_date <= ?';
        $params = [$range['from'], $range['to']];
    }

    $st = $pdo->prepare(
        "SELECT variant,
                COALESCE(SUM(current_liter), 0) AS liter,
                COALESCE(SUM(current_250ml), 0) AS ml250,
                COALESCE(SUM(current_350ml), 0) AS ml350,
                COALESCE(SUM(current_gallon), 0) AS gallon
         FROM honey_packaging_rows{$where}
         GROUP BY variant
         ORDER BY variant"
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $sizes = [
        ['key' => '250ml', 'label' => '250ml'],
        ['key' => '350ml', 'label' => '350ml'],
        ['key' => 'liter', 'label' => '1L'],
        ['key' => 'gallon', 'label' => 'Gallon'],
    ];

    $variants = [];
    $grandTotal = 0.0;

    foreach ($rows as $row) {
        $bySize = [
            '250ml' => (float) $row['ml250'],
            '350ml' => (float) $row['ml350'],
            'liter' => (float) $row['liter'],
            'gallon' => (float) $row['gallon'],
        ];
        $variantTotal = array_sum($bySize);
        $grandTotal += $variantTotal;

        $slices = [];
        foreach ($sizes as $size) {
            $qty = $bySize[$size['key']];
            $slices[] = [
                'size' => $size['key'],
                'label' => $size['label'],
                'qty' => $qty,
                'pct' => $variantTotal > 0 ? round(($qty / $variantTotal) * 100, 1) : 0.0,
            ];
        }

        $variants[] = [
            'variant' => (string) $row['variant'],
            'total_bottles' => $variantTotal,
            'sizes' => $slices,
        ];
    }

    return [
        'total_bottles_on_hand' => $grandTotal,
        'variants' => $variants,
        'size_columns' => array_column($sizes, 'label', 'key'),
    ];
}

/** @return list<array{key: string, label: string, dept: string, patterns: list<string>}> */
function dashboard_dept_product_definitions(): array
{
    if (!function_exists('dept_product_catalog')) {
        require_once __DIR__ . '/dept_products.php';
    }
    $defs = [];
    foreach (dept_product_catalog() as $def) {
        $defs[] = [
            'key' => $def['key'],
            'label' => $def['label'],
            'dept' => $def['dept'],
            'patterns' => $def['patterns'],
        ];
    }

    return $defs;
}

function dashboard_label_matches_product(string $label, array $patterns): bool
{
    $norm = strtolower(trim(preg_replace('/\s+/', ' ', $label) ?? $label));
    foreach ($patterns as $pattern) {
        $p = strtolower(trim($pattern));
        if ($norm === $p || str_contains($norm, $p)) {
            return true;
        }
    }

    return false;
}

/** @return array<string, mixed> */
function dashboard_dept_products_snapshot(PDO $pdo, ?array $range): array
{
    $where = '';
    $params = [];
    if ($range !== null) {
        $where = ' AND dir.created_at >= ? AND dir.created_at <= ?';
        $params = [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59'];
    }

    $st = $pdo->prepare(
        'SELECT dir.product_key, dir.product_label, dir.current_qty, d.name AS dept_name
         FROM dept_inventory_rows dir
         INNER JOIN departments d ON d.id = dir.dept_id
         WHERE dir.current_qty > 0' . $where
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $products = [];
    foreach (dashboard_dept_product_definitions() as $def) {
        $total = 0.0;
        foreach ($rows as $row) {
            if ((string) $row['dept_name'] !== $def['dept']) {
                continue;
            }
            $rowKey = trim((string) ($row['product_key'] ?? ''));
            if ($rowKey !== '' && $rowKey === $def['key']) {
                $total += (float) $row['current_qty'];
                continue;
            }
            if ($rowKey === '' && dashboard_label_matches_product((string) $row['product_label'], $def['patterns'])) {
                $total += (float) $row['current_qty'];
            }
        }
        $products[] = [
            'key' => $def['key'],
            'label' => $def['label'],
            'total_qty' => $total,
        ];
    }

    return ['products' => $products];
}

/** @return array<string, mixed> */
function dashboard_inventory_snapshot(PDO $pdo, ?string $period, ?string $from, ?string $to): array
{
    $range = dashboard_inventory_resolve_range($period, $from, $to);

    return [
        'filter' => [
            'period' => $range === null ? 'all' : $range['period'],
            'from' => $range['from'] ?? null,
            'to' => $range['to'] ?? null,
            'label' => $range === null ? 'All time (on hand)' : $range['label'],
        ],
        'honey_packaging' => dashboard_honey_packaging_snapshot($pdo, $range),
        'dept_products' => dashboard_dept_products_snapshot($pdo, $range),
    ];
}
