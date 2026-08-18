<?php

declare(strict_types=1);

require_once __DIR__ . '/dashboard_inventory.php';
require_once __DIR__ . '/dept_products.php';

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
function honey_sales_log_where_clause(?array $range, string $channel = 'all'): array
{
    $where = ' WHERE h.source = "packaging"';
    if ($channel === 'walk_in') {
        $where .= ' AND h.sale_type = "walk_in"';
    } elseif ($channel === 'online') {
        $where .= ' AND h.sale_type = "parcel"';
    } else {
        $where .= ' AND h.sale_type IN ("walk_in","parcel")';
    }
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

/**
 * Product key for recent walk-in list: all | honey | pt | brc | jar_cacao | honey_comb.
 */
function honey_sales_normalize_product_filter(string $product): string
{
    $product = strtolower(trim($product));
    $allowed = ['all', 'honey', 'pt', 'brc', 'jar_cacao', 'honey_comb'];

    return in_array($product, $allowed, true) ? $product : 'all';
}

/**
 * Restrict receipts to those with at least one matching product line.
 *
 * @return array{0: string, 1: list<mixed>}
 */
function honey_sales_product_exists_clause(string $product): array
{
    $product = honey_sales_normalize_product_filter($product);
    if ($product === 'all') {
        return ['', []];
    }
    if ($product === 'honey') {
        return [
            ' AND EXISTS (
                SELECT 1 FROM pos_receipt_items pri_f
                INNER JOIN products p_f ON p_f.id = pri_f.product_id
                WHERE pri_f.sale_id = ps.id
                  AND (p_f.dept_product_key IS NULL OR p_f.dept_product_key = "")
              )',
            [],
        ];
    }

    return [
        ' AND EXISTS (
            SELECT 1 FROM pos_receipt_items pri_f
            INNER JOIN products p_f ON p_f.id = pri_f.product_id
            WHERE pri_f.sale_id = ps.id AND p_f.dept_product_key = ?
          )',
        [$product],
    ];
}

/**
 * @param array<string, mixed>|null $range
 * @return array{0: string, 1: list<mixed>}
 */
function honey_sales_checkout_where_clause(?array $range, ?string $receiptSearch = null, string $saleType = 'all', string $product = 'all'): array
{
    $where = ' WHERE (EXISTS (SELECT 1 FROM honey_sales_log h
                              WHERE h.pos_sale_id = ps.id AND h.source = "packaging" AND h.sale_type = "walk_in")
                      OR EXISTS (SELECT 1 FROM pos_receipt_items pri
                                 INNER JOIN products p ON p.id = pri.product_id
                                 WHERE pri.sale_id = ps.id
                                   AND p.dept_product_key IS NOT NULL AND p.dept_product_key <> ""))';
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
    [$productSql, $productParams] = honey_sales_product_exists_clause($product);
    $where .= $productSql;
    $params = array_merge($params, $productParams);

    return [$where, $params];
}

/**
 * Dept finished units moved in the period.
 * Walk-in comes from POS receipt lines (covers sticker sells and older quantity sells);
 * shipped comes from sticker units scanned in a confirmed pack session — packed for a parcel,
 * not a register sale. Pack-cancel restock clears the unit, so returns drop out on their own.
 *
 * @param array<string, mixed>|null $range
 * @return array{products: list<array<string, mixed>>, walk_in: float, shipped: float, total: float}
 */
function honey_sales_dept_breakdown(PDO $pdo, ?array $range, string $channel = 'all'): array
{
    $counts = [];

    $soldWhere = ' WHERE p.dept_product_key IS NOT NULL AND p.dept_product_key <> ""';
    $soldParams = [];
    if ($range !== null) {
        $soldWhere .= ' AND ps.sale_date >= ? AND ps.sale_date <= ?';
        $soldParams = [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59'];
    }
    $soldSql = "SELECT p.dept_product_key AS product_key, COALESCE(SUM(pri.qty), 0) AS qty
                FROM pos_receipt_items pri
                INNER JOIN products p ON p.id = pri.product_id
                INNER JOIN pos_sales ps ON ps.id = pri.sale_id{$soldWhere}
                GROUP BY p.dept_product_key";
    $soldSt = $pdo->prepare($soldSql);
    $soldSt->execute($soldParams);
    foreach ($soldSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $counts[(string) $row['product_key']]['walk_in'] = (float) $row['qty'];
    }

    $packWhere = " WHERE u.consumed_at IS NOT NULL
                     AND u.consumed_ref_type = 'pack_session'
                     AND u.status IN ('packed','shipped')";
    $packParams = [];
    if ($range !== null) {
        $packWhere .= ' AND u.consumed_at >= ? AND u.consumed_at <= ?';
        $packParams = [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59'];
    }
    try {
        $packSql = "SELECT u.product_key, COUNT(*) AS qty
                    FROM dept_pouch_units u{$packWhere}
                    GROUP BY u.product_key";
        $packSt = $pdo->prepare($packSql);
        $packSt->execute($packParams);
        foreach ($packSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['product_key']]['shipped'] = (float) $row['qty'];
        }
    } catch (PDOException $e) {
        // Sticker units table not migrated yet — report sold units only rather than breaking the page.
    }

    $products = [];
    $walkInTotal = 0.0;
    $shippedTotal = 0.0;
    foreach (dept_product_catalog() as $product) {
        $key = (string) $product['key'];
        $walkIn = $channel === 'online' ? 0.0 : (float) ($counts[$key]['walk_in'] ?? 0);
        $shipped = $channel === 'walk_in' ? 0.0 : (float) ($counts[$key]['shipped'] ?? 0);
        $walkInTotal += $walkIn;
        $shippedTotal += $shipped;
        $products[] = [
            'key' => $key,
            'label' => (string) $product['label'],
            'unit' => (string) ($product['inventory_unit'] ?? $product['unit'] ?? 'pcs'),
            'walk_in' => $walkIn,
            'shipped' => $shipped,
            'total' => $walkIn + $shipped,
        ];
    }

    return [
        'products' => $products,
        'walk_in' => $walkInTotal,
        'shipped' => $shippedTotal,
        'total' => $walkInTotal + $shippedTotal,
    ];
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
    $name = trim((string) ($row['product_name'] ?? ''));
    $sizeLabel = honey_sales_size_label($size);
    $label = $variant !== ''
        ? trim(ucfirst($variant) . ($sizeLabel !== '' ? ' · ' . $sizeLabel : ''))
        : ($name !== '' ? $name : 'Item');

    return [
        'variant' => $variant,
        'size' => $size,
        'size_label' => $sizeLabel,
        'label' => $label,
        'qty' => (float) ($row['qty'] ?? 0),
        'list_unit_price' => round((float) ($row['list_unit_price'] ?? $row['unit_price'] ?? 0), 2),
        'unit_price' => round((float) ($row['unit_price'] ?? 0), 2),
        'line_subtotal' => round((float) ($row['list_unit_price'] ?? $row['unit_price'] ?? 0) * (float) ($row['qty'] ?? 0), 2),
        'line_total' => round((float) ($row['line_total'] ?? 0), 2),
        'name' => (string) ($row['product_name'] ?? ''),
    ];
}

function honey_sales_pos_has_sold_by_user(PDO $pdo): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $st = $pdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $st->execute(['pos_sales', 'sold_by_user_id']);
    $cached = (bool) $st->fetchColumn();

    return $cached;
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
    string $saleType = 'all',
    string $product = 'all'
): array
{
    $perPage = HONEY_SALES_CHECKOUTS_PER_PAGE;
    $page = max(1, $page);
    if (!in_array($saleType, ['all', 'regular', 'discount', 'ar'], true)) {
        $saleType = 'all';
    }
    $product = honey_sales_normalize_product_filter($product);
    [$where, $params] = honey_sales_checkout_where_clause($range, $receiptSearch, $saleType, $product);

    $countSql = "SELECT COUNT(DISTINCT ps.id) AS cnt
                 FROM pos_sales ps{$where}";
    $cst = $pdo->prepare($countSql);
    $cst->execute($params);
    $total = (int) ($cst->fetchColumn() ?: 0);
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

    if ($totalPages > 0 && $page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;
    $hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);
    $sellerJoin = $hasUserSeller
        ? ' LEFT JOIN users su ON su.id = ps.sold_by_user_id LEFT JOIN pos_pins sp ON sp.id = ps.sold_by_pin_id'
        : ' LEFT JOIN pos_pins sp ON sp.id = ps.sold_by_pin_id';
    $sellerSelect = $hasUserSeller
        ? "COALESCE(NULLIF(TRIM(su.name), ''), NULLIF(TRIM(sp.label), ''), 'POS') AS sold_by_name"
        : "COALESCE(NULLIF(TRIM(sp.label), ''), 'POS') AS sold_by_name";
    $sellerGroup = $hasUserSeller ? ', su.name, sp.label' : ', sp.label';
    $listSql = "SELECT ps.id AS sale_id, ps.receipt_no, ps.sale_date AS sold_at,
                       ps.subtotal_amount, ps.discount_percent, ps.discount_amount, ps.total_amount,
                       ps.buyer_name, ps.buyer_credentials, COALESCE(ps.is_ar, 0) AS is_ar,
                       {$sellerSelect}
                FROM pos_sales ps{$sellerJoin}{$where}
                GROUP BY ps.id, ps.receipt_no, ps.sale_date, ps.subtotal_amount, ps.discount_percent, ps.discount_amount, ps.total_amount, ps.buyer_name, ps.buyer_credentials, ps.is_ar{$sellerGroup}
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
            'sold_by_name' => trim((string) ($s['sold_by_name'] ?? '')) !== '' ? (string) $s['sold_by_name'] : 'POS',
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
    string $saleType = 'all',
    string $channel = 'all',
    string $product = 'all'
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
    if (!in_array($channel, ['all', 'walk_in', 'online'], true)) {
        $channel = 'all';
    }
    $product = honey_sales_normalize_product_filter($product);
    [$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);

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

    $checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType, $product);

    $filterMeta = $range !== null
        ? ['period' => $range['period'], 'from' => $range['from'], 'to' => $range['to'], 'label' => $range['label']]
        : ['period' => 'all', 'from' => null, 'to' => null, 'label' => 'All time'];
    $filterMeta['receipt'] = $receiptSearch;
    $filterMeta['sale_type'] = $saleType;
    $filterMeta['channel'] = $channel;
    $filterMeta['product'] = $product;

    [$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');
    $shareSql = "SELECT h.sale_type,
                        COALESCE(SUM(h.qty_liter + h.qty_250ml + h.qty_350ml + h.qty_gallon), 0) AS qty
                 FROM honey_sales_log h
                 {$shareWhere}
                 GROUP BY h.sale_type";
    $shareSt = $pdo->prepare($shareSql);
    $shareSt->execute($shareParams);
    $walkInQty = 0.0;
    $onlineQty = 0.0;
    foreach ($shareSt->fetchAll(PDO::FETCH_ASSOC) as $sr) {
        if ((string) $sr['sale_type'] === 'walk_in') {
            $walkInQty = (float) $sr['qty'];
        } elseif ((string) $sr['sale_type'] === 'parcel') {
            $onlineQty = (float) $sr['qty'];
        }
    }
    $honeyWalkIn = $channel === 'online' ? 0.0 : $walkInQty;
    $honeyShipped = $channel === 'walk_in' ? 0.0 : $onlineQty;

    $dept = honey_sales_dept_breakdown($pdo, $range, $channel);
    $deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');
    $walkInQty += $deptShare['walk_in'];
    $onlineQty += $deptShare['shipped'];
    $shareTotal = $walkInQty + $onlineQty;

    return [
        'filter' => $filterMeta,
        'total_bottles_sold' => $all['total'],
        'total_units_sold' => $all['total'] + $dept['total'],
        'honey' => [
            'label' => 'Honey',
            'unit' => 'bottle',
            'walk_in' => $honeyWalkIn,
            'shipped' => $honeyShipped,
            'total' => $all['total'],
        ],
        'dept' => $dept,
        'channel_share' => [
            'walk_in' => $walkInQty,
            'online' => $onlineQty,
            'total' => $shareTotal,
            'walk_in_pct' => $shareTotal > 0 ? round(($walkInQty / $shareTotal) * 100, 1) : 0.0,
            'online_pct' => $shareTotal > 0 ? round(($onlineQty / $shareTotal) * 100, 1) : 0.0,
        ],
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
