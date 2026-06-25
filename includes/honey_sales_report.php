<?php

declare(strict_types=1);

require_once __DIR__ . '/dashboard_inventory.php';

const HONEY_SALES_SIZE_LABELS = [
    '250ml' => '250ml',
    '350ml' => '350ml',
    'liter' => '1L',
    'gallon' => 'Gallon',
];

const HONEY_SALES_CHECKOUTS_PER_PAGE = 10;

/** @return array{sql: string, params: list<mixed>, range: array<string, mixed>|null} */
function honey_sales_resolve_filters(?string $period, ?string $from, ?string $to): array
{
    $range = null;
    if ($period !== null && $period !== '' && strtolower($period) !== 'all') {
        $range = dashboard_inventory_resolve_range($period, $from, $to);
    }

    return ['range' => $range];
}

/** @param array<string, mixed>|null $range */
function honey_sales_log_where_clause(?array $range): array
{
    $where = ' WHERE h.source = "packaging" AND h.sale_type = "walk_in"';
    $params = [];
    if ($range !== null) {
        $where .= ' AND h.created_at >= ? AND h.created_at <= ?';
        $params = [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59'];
    }

    return [$where, $params];
}

/**
 * @param array<string, mixed>|null $range
 * @param string|null $receiptSearch partial match on receipt_no (case-insensitive)
 */
function honey_sales_pos_checkout_where_clause(?array $range, ?string $receiptSearch = null, string $saleType = 'all'): array
{
    $where = ' WHERE h.source = "packaging" AND h.sale_type = "walk_in" AND h.pos_sale_id IS NOT NULL';
    $params = [];
    if ($range !== null) {
        $where .= ' AND ps.sale_date >= ? AND ps.sale_date <= ?';
        $params = [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59'];
    }
    $receiptSearch = trim((string) $receiptSearch);
    if ($receiptSearch !== '') {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtoupper($receiptSearch));
        $where .= ' AND UPPER(ps.receipt_no) LIKE ?';
        $params[] = '%' . $escaped . '%';
    }
    if ($saleType === 'regular') {
        $where .= ' AND COALESCE(ps.is_ar, 0) = 0 AND (ps.discount_percent IS NULL OR ps.discount_percent = 0)';
    } elseif ($saleType === 'discount') {
        $where .= ' AND COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent IN (10, 20)';
    } elseif ($saleType === 'ar') {
        $where .= ' AND ps.is_ar = 1';
    }

    return [$where, $params];
}

function honey_sales_size_label(?string $size): string
{
    $size = (string) $size;

    return HONEY_SALES_SIZE_LABELS[$size] ?? $size;
}

/** @param array<string, mixed> $row */
function honey_sales_serialize_receipt_item(array $row): array
{
    $variant = strtolower(trim((string) ($row['variant'] ?? $row['p_variant'] ?? '')));
    $size = trim((string) ($row['size'] ?? $row['p_size'] ?? ''));

    return [
        'variant' => $variant,
        'size' => $size,
        'size_label' => honey_sales_size_label($size),
        'qty' => (float) ($row['qty'] ?? 0),
        'list_unit_price' => round((float) ($row['list_unit_price'] ?? $row['unit_price'] ?? 0), 2),
        'unit_price' => round((float) ($row['unit_price'] ?? 0), 2),
        'line_subtotal' => round((float) ($row['list_unit_price'] ?? $row['unit_price'] ?? 0) * (float) ($row['qty'] ?? 0), 2),
        'line_total' => round((float) ($row['line_total'] ?? 0), 2),
        'name' => (string) ($row['product_name'] ?? ''),
    ];
}

/**
 * @param array<string, mixed>|null $range
 * @return array{page: int, per_page: int, total: int, total_pages: int, rows: list<array<string, mixed>>}
 */
function honey_sales_fetch_checkouts(
    PDO $pdo,
    ?array $range,
    int $page,
    ?string $receiptSearch = null,
    string $saleType = 'all'
): array
{
    $perPage = HONEY_SALES_CHECKOUTS_PER_PAGE;
    $page = max(1, $page);
    if (!in_array($saleType, ['all', 'regular', 'discount', 'ar'], true)) {
        $saleType = 'all';
    }
    [$where, $params] = honey_sales_pos_checkout_where_clause($range, $receiptSearch, $saleType);

    $countSql = "SELECT COUNT(DISTINCT ps.id) AS cnt
                 FROM pos_sales ps
                 INNER JOIN honey_sales_log h ON h.pos_sale_id = ps.id{$where}";
    $cst = $pdo->prepare($countSql);
    $cst->execute($params);
    $total = (int) ($cst->fetchColumn() ?: 0);
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

    if ($totalPages > 0 && $page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;
    $listSql = "SELECT ps.id AS sale_id, ps.receipt_no, ps.sale_date AS sold_at,
                       ps.subtotal_amount, ps.discount_percent, ps.discount_amount, ps.total_amount,
                       ps.buyer_name, ps.buyer_credentials, COALESCE(ps.is_ar, 0) AS is_ar
                FROM pos_sales ps
                INNER JOIN honey_sales_log h ON h.pos_sale_id = ps.id{$where}
                GROUP BY ps.id, ps.receipt_no, ps.sale_date, ps.subtotal_amount, ps.discount_percent, ps.discount_amount, ps.total_amount, ps.buyer_name, ps.buyer_credentials, ps.is_ar
                ORDER BY ps.sale_date DESC
                LIMIT {$perPage} OFFSET {$offset}";
    $lst = $pdo->prepare($listSql);
    $lst->execute($params);
    $sales = $lst->fetchAll(PDO::FETCH_ASSOC);

    if (!$sales) {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'rows' => [],
        ];
    }

    $saleIds = array_map(static fn ($r) => (int) $r['sale_id'], $sales);
    $placeholders = implode(',', array_fill(0, count($saleIds), '?'));

    $itemSql = "SELECT pri.sale_id, pri.qty, pri.unit_price, pri.list_unit_price, pri.line_total,
                       pri.variant, pri.size,
                       p.variant AS p_variant, p.size AS p_size, p.name AS product_name
                FROM pos_receipt_items pri
                LEFT JOIN products p ON p.id = pri.product_id
                WHERE pri.sale_id IN ({$placeholders})
                ORDER BY pri.id ASC";
    $ist = $pdo->prepare($itemSql);
    $ist->execute($saleIds);
    $itemsBySale = [];
    foreach ($ist->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $sid = (int) $item['sale_id'];
        $itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);
    }

    $rows = [];
    foreach ($sales as $s) {
        $saleId = (int) $s['sale_id'];
        $items = $itemsBySale[$saleId] ?? [];
        $rows[] = [
            'sale_id' => $saleId,
            'receipt_no' => (string) $s['receipt_no'],
            'sold_at' => (string) $s['sold_at'],
            'subtotal_amount' => round((float) ($s['subtotal_amount'] ?? $s['total_amount']), 2),
            'discount_percent' => $s['discount_percent'] !== null ? (int) $s['discount_percent'] : null,
            'discount_amount' => round((float) ($s['discount_amount'] ?? 0), 2),
            'total_amount' => round((float) $s['total_amount'], 2),
            'buyer_name' => (string) $s['buyer_name'],
            'buyer_credentials' => $s['buyer_credentials'] !== null ? (string) $s['buyer_credentials'] : null,
            'is_ar' => (int) ($s['is_ar'] ?? 0) === 1,
            'item_count' => count($items),
            'items' => $items,
        ];
    }

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'rows' => $rows,
    ];
}

/**
 * @param list<array<string, mixed>> $rows
 * @return array{variants:list<array<string,mixed>>,total:float}
 */
function honey_sales_variant_breakdown(array $rows): array
{
    $sizeKeys = [
        ['key' => '250ml', 'col' => 'ml250'],
        ['key' => '350ml', 'col' => 'ml350'],
        ['key' => 'liter', 'col' => 'liter'],
        ['key' => 'gallon', 'col' => 'gallon'],
    ];

    $variants = [];
    $grandTotal = 0.0;
    foreach ($rows as $row) {
        $variantTotal = 0.0;
        foreach ($sizeKeys as $sk) {
            $variantTotal += (float) ($row[$sk['col']] ?? 0);
        }
        $grandTotal += $variantTotal;
        $sizes = [];
        foreach ($sizeKeys as $sk) {
            $qty = (float) ($row[$sk['col']] ?? 0);
            $sizes[] = [
                'size' => $sk['key'],
                'label' => HONEY_SALES_SIZE_LABELS[$sk['key']] ?? $sk['key'],
                'qty' => $qty,
                'pct' => $variantTotal > 0 ? round(($qty / $variantTotal) * 100, 1) : 0.0,
            ];
        }
        $variants[] = [
            'variant' => (string) ($row['variant'] ?? ''),
            'total_bottles' => $variantTotal,
            'sizes' => $sizes,
        ];
    }

    return ['variants' => $variants, 'total' => $grandTotal];
}

/** @return array<string, mixed> */
function honey_sales_sold_snapshot(
    PDO $pdo,
    ?string $period,
    ?string $from,
    ?string $to,
    int $page = 1,
    ?string $receiptSearch = null,
    string $saleType = 'all'
): array {
    $filters = honey_sales_resolve_filters($period, $from, $to);
    $range = $filters['range'];
    $receiptSearch = trim((string) $receiptSearch);
    if ($receiptSearch === '') {
        $receiptSearch = null;
    }
    if (!in_array($saleType, ['all', 'regular', 'discount', 'ar'], true)) {
        $saleType = 'all';
    }
    [$logWhere, $logParams] = honey_sales_log_where_clause($range);

    $sqlAll = "SELECT h.variant,
                      COALESCE(SUM(h.qty_liter), 0) AS liter,
                      COALESCE(SUM(h.qty_250ml), 0) AS ml250,
                      COALESCE(SUM(h.qty_350ml), 0) AS ml350,
                      COALESCE(SUM(h.qty_gallon), 0) AS gallon
               FROM honey_sales_log h
               LEFT JOIN pos_sales ps ON ps.id = h.pos_sale_id{$logWhere}
               GROUP BY h.variant
               ORDER BY h.variant";
    $stAll = $pdo->prepare($sqlAll);
    $stAll->execute($logParams);
    $all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));

    $sqlRegular = "SELECT h.variant,
                          COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND (h.pos_sale_id IS NULL OR ps.discount_percent IS NULL OR ps.discount_percent = 0) THEN h.qty_liter ELSE 0 END), 0) AS liter,
                          COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND (h.pos_sale_id IS NULL OR ps.discount_percent IS NULL OR ps.discount_percent = 0) THEN h.qty_250ml ELSE 0 END), 0) AS ml250,
                          COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND (h.pos_sale_id IS NULL OR ps.discount_percent IS NULL OR ps.discount_percent = 0) THEN h.qty_350ml ELSE 0 END), 0) AS ml350,
                          COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND (h.pos_sale_id IS NULL OR ps.discount_percent IS NULL OR ps.discount_percent = 0) THEN h.qty_gallon ELSE 0 END), 0) AS gallon
                   FROM honey_sales_log h
                   LEFT JOIN pos_sales ps ON ps.id = h.pos_sale_id{$logWhere}
                   GROUP BY h.variant
                   ORDER BY h.variant";
    $stRegular = $pdo->prepare($sqlRegular);
    $stRegular->execute($logParams);
    $regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));

    $sqlDiscount10 = "SELECT h.variant,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 10 THEN h.qty_liter ELSE 0 END), 0) AS liter,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 10 THEN h.qty_250ml ELSE 0 END), 0) AS ml250,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 10 THEN h.qty_350ml ELSE 0 END), 0) AS ml350,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 10 THEN h.qty_gallon ELSE 0 END), 0) AS gallon
                      FROM honey_sales_log h
                      LEFT JOIN pos_sales ps ON ps.id = h.pos_sale_id{$logWhere}
                      GROUP BY h.variant
                      ORDER BY h.variant";
    $stDiscount10 = $pdo->prepare($sqlDiscount10);
    $stDiscount10->execute($logParams);
    $discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));

    $sqlDiscount20 = "SELECT h.variant,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 20 THEN h.qty_liter ELSE 0 END), 0) AS liter,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 20 THEN h.qty_250ml ELSE 0 END), 0) AS ml250,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 20 THEN h.qty_350ml ELSE 0 END), 0) AS ml350,
                             COALESCE(SUM(CASE WHEN COALESCE(ps.is_ar, 0) = 0 AND ps.discount_percent = 20 THEN h.qty_gallon ELSE 0 END), 0) AS gallon
                      FROM honey_sales_log h
                      LEFT JOIN pos_sales ps ON ps.id = h.pos_sale_id{$logWhere}
                      GROUP BY h.variant
                      ORDER BY h.variant";
    $stDiscount20 = $pdo->prepare($sqlDiscount20);
    $stDiscount20->execute($logParams);
    $discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));
    $discountedTotal = $discount10['total'] + $discount20['total'];

    [$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');
    $summarySql = "SELECT
                     COALESCE(SUM(CASE WHEN COALESCE(x.is_ar, 0) = 0 AND (x.discount_percent IS NULL OR x.discount_percent = 0) THEN x.total_amount ELSE 0 END), 0) AS revenue_regular,
                     COALESCE(SUM(CASE WHEN COALESCE(x.is_ar, 0) = 0 AND x.discount_percent IN (10, 20) THEN x.total_amount ELSE 0 END), 0) AS revenue_discounted,
                     COALESCE(SUM(CASE WHEN COALESCE(x.is_ar, 0) = 0 AND x.discount_percent IN (10, 20) THEN x.discount_amount ELSE 0 END), 0) AS discounts_given,
                     COALESCE(SUM(CASE WHEN x.is_ar = 1 THEN x.total_amount ELSE 0 END), 0) AS revenue_ar
                   FROM (
                     SELECT ps.id, ps.total_amount, ps.discount_amount, ps.discount_percent, COALESCE(ps.is_ar, 0) AS is_ar
                     FROM pos_sales ps
                     INNER JOIN honey_sales_log h ON h.pos_sale_id = ps.id{$salesWhere}
                     GROUP BY ps.id, ps.total_amount, ps.discount_amount, ps.discount_percent, ps.is_ar
                   ) x";
    $sst = $pdo->prepare($summarySql);
    $sst->execute($salesParams);
    $summaryRow = $sst->fetch(PDO::FETCH_ASSOC) ?: [];

    $checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType);

    $filterMeta = $range !== null
        ? ['period' => $range['period'], 'from' => $range['from'], 'to' => $range['to'], 'label' => $range['label']]
        : ['period' => 'all', 'from' => null, 'to' => null, 'label' => 'All time'];
    $filterMeta['receipt'] = $receiptSearch;
    $filterMeta['sale_type'] = $saleType;

    return [
        'filter' => $filterMeta,
        'total_bottles_sold' => $all['total'],
        'summary' => [
            'total_bottles_regular' => $regular['total'],
            'total_bottles_discounted' => $discountedTotal,
            'revenue_regular' => round((float) ($summaryRow['revenue_regular'] ?? 0), 2),
            'revenue_discounted' => round((float) ($summaryRow['revenue_discounted'] ?? 0), 2),
            'discounts_given' => round((float) ($summaryRow['discounts_given'] ?? 0), 2),
            'revenue_ar' => round((float) ($summaryRow['revenue_ar'] ?? 0), 2),
        ],
        'variants_all' => $all['variants'],
        'variants_regular' => $regular['variants'],
        'variants_discount_10' => $discount10['variants'],
        'variants_discount_20' => $discount20['variants'],
        'checkouts' => $checkouts,
    ];
}
