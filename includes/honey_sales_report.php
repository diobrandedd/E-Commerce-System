<?php

declare(strict_types=1);

require_once __DIR__ . '/dashboard_inventory.php';
require_once __DIR__ . '/dept_products.php';
require_once __DIR__ . '/rts_schema.php';
require_once __DIR__ . '/honey_pos.php';

const HONEY_SALES_SIZE_LABELS = [
    '250ml' => '250ml',
    '350ml' => '350ml',
    'liter' => '1L',
    'gallon' => 'Gallon',
];

const HONEY_SALES_CHECKOUTS_PER_PAGE = 10;

/** Rows of detail data per Excel sheet (Shipped 1, Shipped 2, …). */
const HONEY_SALES_EXPORT_ROWS_PER_SHEET = 5000;

/**
 * Absolute safety cap for one export (all detail rows across sheets).
 * Prevents unbounded all-time dumps from exhausting PHP memory.
 */
const HONEY_SALES_EXPORT_MAX_TOTAL_ROWS = 100000;

/** @deprecated Use HONEY_SALES_EXPORT_ROWS_PER_SHEET — kept as alias for older references. */
const HONEY_SALES_EXPORT_MAX_ROWS = HONEY_SALES_EXPORT_ROWS_PER_SHEET;

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
 * RTS scan units that represent real pack/POS lines (skip bare FIFO deduct mirrors that double-count stickers).
 *
 * @param array<string, mixed>|null $range
 * @return array{
 *   walk_in: float,
 *   parcel: float,
 *   honey_walk_in: float,
 *   honey_parcel: float
 * }
 */
function honey_sales_rts_scan_units(PDO $pdo, ?array $range): array
{
    $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
    $hasSessCol = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');
    $hasParcelCol = rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id');
    $hasQtyOther = rts_db_has_column($pdo, 'honey_sales_log', 'qty_other');

    $unitExpr = $hasUnitCol ? 'h.bottle_unit_id' : 'NULL';
    $sessExpr = $hasSessCol ? 'h.pack_session_id' : 'NULL';
    $parcelExpr = $hasParcelCol ? 'h.online_parcel_id' : 'NULL';
    $qtyOtherExpr = $hasQtyOther ? 'COALESCE(h.qty_other, 0)' : '0';

    $where = " WHERE h.source = 'rts'
                 AND h.sale_type IN ('walk_in','parcel')
                 AND (
                   ({$unitExpr} IS NOT NULL)
                   OR ({$sessExpr} IS NOT NULL)
                   OR ({$parcelExpr} IS NOT NULL)
                   OR ({$qtyOtherExpr} > 0)
                 )";
    $params = [];
    if ($range !== null) {
        $where .= ' AND h.created_at >= ? AND h.created_at <= ?';
        $params = [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59'];
    }

    $sql = "SELECT h.sale_type,
                   COALESCE(SUM(h.qty_liter + h.qty_250ml + h.qty_350ml + h.qty_gallon), 0) AS honey_qty,
                   COALESCE(SUM({$qtyOtherExpr}), 0) AS other_qty
            FROM honey_sales_log h
            {$where}
            GROUP BY h.sale_type";
    $st = $pdo->prepare($sql);
    $st->execute($params);

    $out = [
        'walk_in' => 0.0,
        'parcel' => 0.0,
        'honey_walk_in' => 0.0,
        'honey_parcel' => 0.0,
    ];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $honey = (float) ($row['honey_qty'] ?? 0);
        $other = (float) ($row['other_qty'] ?? 0);
        $sum = $honey + $other;
        if ((string) $row['sale_type'] === 'walk_in') {
            $out['walk_in'] = $sum;
            $out['honey_walk_in'] = $honey;
        } elseif ((string) $row['sale_type'] === 'parcel') {
            $out['parcel'] = $sum;
            $out['honey_parcel'] = $honey;
        }
    }

    return $out;
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
 * shipped = unique stickers packed in a confirmed pack session + SKU qty lines on those
 * same sessions (matches Pack scan History). Pack-cancel restock clears unique units;
 * SKU lines remain on the confirmed session (same as History).
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

    // SKU qty packs on confirmed sessions (no dept_pouch_units row — inventory-only deduct).
    try {
        $skuWhere = ' WHERE s.status = "confirmed"
                        AND s.confirmed_at IS NOT NULL
                        AND di.sku_id IS NOT NULL AND di.sku_id > 0
                        AND (di.unit_id IS NULL OR di.unit_id < 1)';
        $skuParams = [];
        if ($range !== null) {
            $skuWhere .= ' AND s.confirmed_at >= ? AND s.confirmed_at <= ?';
            $skuParams = [$range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59'];
        }
        $skuSql = "SELECT di.product_key, COALESCE(SUM(di.qty), 0) AS qty
                   FROM honey_bottle_pack_session_dept_items di
                   INNER JOIN honey_bottle_pack_sessions s ON s.id = di.session_id
                   {$skuWhere}
                   GROUP BY di.product_key";
        $skuSt = $pdo->prepare($skuSql);
        $skuSt->execute($skuParams);
        foreach ($skuSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string) $row['product_key'];
            $counts[$key]['shipped'] = (float) ($counts[$key]['shipped'] ?? 0) + (float) $row['qty'];
        }
    } catch (PDOException $e) {
        // Pack session / dept item tables missing — stickers-only is still useful.
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
    $productKey = strtolower(trim((string) ($row['dept_product_key'] ?? '')));

    return [
        'kind' => $variant !== '' ? 'honey' : 'dept',
        'variant' => $variant,
        'size' => $size,
        'size_label' => $sizeLabel,
        'label' => $label,
        'product_key' => $productKey !== '' ? $productKey : null,
        'qty' => (float) ($row['qty'] ?? 0),
        'list_unit_price' => round((float) ($row['list_unit_price'] ?? $row['unit_price'] ?? 0), 2),
        'unit_price' => round((float) ($row['unit_price'] ?? 0), 2),
        'line_subtotal' => round((float) ($row['list_unit_price'] ?? $row['unit_price'] ?? 0) * (float) ($row['qty'] ?? 0), 2),
        'line_total' => round((float) ($row['line_total'] ?? 0), 2),
        'name' => (string) ($row['product_name'] ?? ''),
        'barcodes' => [],
    ];
}

/**
 * POS unit barcodes for checkout receipts (honey sales_log + still-linked units).
 *
 * @param list<int> $saleIds
 * @return array<int, list<array{kind: string, match_key: string, code: string, returned: bool}>>
 */
function honey_sales_load_checkout_barcodes(PDO $pdo, array $saleIds): array
{
    $saleIds = array_values(array_filter(array_map('intval', $saleIds), static fn ($id) => $id > 0));
    if ($saleIds === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
    /** @var array<int, list<array{kind: string, match_key: string, code: string, returned: bool}>> $bySale */
    $bySale = [];
    /** @var array<string, true> $seen */
    $seen = [];

    $push = static function (int $saleId, string $kind, string $matchKey, string $code, bool $returned) use (&$bySale, &$seen): void {
        $code = trim($code);
        if ($saleId < 1 || $code === '') {
            return;
        }
        $dedupe = $saleId . '|' . $code;
        if (isset($seen[$dedupe])) {
            return;
        }
        $seen[$dedupe] = true;
        $bySale[$saleId][] = [
            'kind' => $kind,
            'match_key' => $matchKey,
            'code' => $code,
            'returned' => $returned,
        ];
    };

    $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
    if ($hasUnitCol) {
        $st = $pdo->prepare(
            "SELECT h.pos_sale_id, u.variant, u.size, c.code, u.status AS unit_status,
                    (COALESCE(h.qty_liter, 0) + COALESCE(h.qty_250ml, 0)
                     + COALESCE(h.qty_350ml, 0) + COALESCE(h.qty_gallon, 0)) AS qty_signed
             FROM honey_sales_log h
             INNER JOIN honey_bottle_units u ON u.id = h.bottle_unit_id
             INNER JOIN honey_bottle_barcodes c ON c.id = u.barcode_id
             WHERE h.pos_sale_id IN ({$placeholders})
               AND h.bottle_unit_id IS NOT NULL
             ORDER BY h.id ASC"
        );
        $st->execute($saleIds);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ((float) ($row['qty_signed'] ?? 0) <= 0) {
                continue;
            }
            $variant = strtolower(trim((string) $row['variant']));
            $size = trim((string) $row['size']);
            $returned = (string) ($row['unit_status'] ?? '') !== 'sold';
            $push(
                (int) $row['pos_sale_id'],
                'honey',
                'honey|' . $variant . '|' . $size,
                (string) $row['code'],
                $returned
            );
        }
    }

    $st = $pdo->prepare(
        "SELECT u.consumed_ref_id AS sale_id, u.variant, u.size, c.code, u.status AS unit_status
         FROM honey_bottle_units u
         INNER JOIN honey_bottle_barcodes c ON c.id = u.barcode_id
         WHERE u.consumed_ref_type = 'pos_sale' AND u.consumed_ref_id IN ({$placeholders})
         ORDER BY u.id ASC"
    );
    $st->execute($saleIds);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $variant = strtolower(trim((string) $row['variant']));
        $size = trim((string) $row['size']);
        $push(
            (int) $row['sale_id'],
            'honey',
            'honey|' . $variant . '|' . $size,
            (string) $row['code'],
            (string) ($row['unit_status'] ?? '') !== 'sold'
        );
    }

    $st = $pdo->prepare(
        "SELECT un.consumed_ref_id AS sale_id, un.product_key, c.code, un.status AS unit_status
         FROM dept_pouch_units un
         INNER JOIN dept_pouch_barcodes c ON c.id = un.barcode_id
         WHERE un.consumed_ref_type = 'pos_sale' AND un.consumed_ref_id IN ({$placeholders})
         ORDER BY un.id ASC"
    );
    $st->execute($saleIds);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productKey = strtolower(trim((string) $row['product_key']));
        $push(
            (int) $row['sale_id'],
            'dept',
            'dept|' . $productKey,
            (string) $row['code'],
            (string) ($row['unit_status'] ?? '') !== 'sold'
        );
    }

    return $bySale;
}

/**
 * Attach scanned barcodes onto receipt lines (qty units → that many codes).
 *
 * @param list<array<string, mixed>> $items
 * @param list<array{kind: string, match_key: string, code: string, returned: bool}> $units
 * @return list<array<string, mixed>>
 */
function honey_sales_attach_barcodes_to_items(array $items, array $units): array
{
    $queues = [];
    foreach ($units as $unit) {
        if (!empty($unit['returned'])) {
            continue;
        }
        $queues[$unit['match_key']][] = $unit;
    }

    foreach ($items as &$item) {
        $variant = strtolower(trim((string) ($item['variant'] ?? '')));
        $size = trim((string) ($item['size'] ?? ''));
        $productKey = strtolower(trim((string) ($item['product_key'] ?? '')));
        if ($variant !== '' && $size !== '') {
            $matchKey = 'honey|' . $variant . '|' . $size;
        } elseif ($productKey !== '') {
            $matchKey = 'dept|' . $productKey;
        } else {
            $matchKey = '';
            foreach (array_keys($queues) as $key) {
                if (str_starts_with((string) $key, 'dept|') && ($queues[$key] ?? []) !== []) {
                    $matchKey = (string) $key;
                    break;
                }
            }
        }
        $need = max(1, (int) round((float) ($item['qty'] ?? 0)));
        $picked = [];
        while ($need > 0 && $matchKey !== '' && ($queues[$matchKey] ?? []) !== []) {
            $unit = array_shift($queues[$matchKey]);
            $picked[] = [
                'code' => $unit['code'],
                'returned' => false,
            ];
            $need--;
        }
        $item['barcodes'] = $picked;
    }
    unset($item);

    return $items;
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
    string $product = 'all',
    ?int $perPageOverride = null
): array
{
    $perPage = $perPageOverride !== null && $perPageOverride > 0
        ? $perPageOverride
        : HONEY_SALES_CHECKOUTS_PER_PAGE;
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
    $hasSellerName = honey_pos_column_exists($pdo, 'pos_sales', 'seller_name');
    $sellerJoin = $hasUserSeller
        ? ' LEFT JOIN users su ON su.id = ps.sold_by_user_id LEFT JOIN pos_pins sp ON sp.id = ps.sold_by_pin_id'
        : ' LEFT JOIN pos_pins sp ON sp.id = ps.sold_by_pin_id';
    if ($hasSellerName) {
        $sellerSelect = $hasUserSeller
            ? "COALESCE(NULLIF(TRIM(ps.seller_name), ''), NULLIF(TRIM(su.name), ''), NULLIF(TRIM(sp.label), ''), 'POS') AS sold_by_name"
            : "COALESCE(NULLIF(TRIM(ps.seller_name), ''), NULLIF(TRIM(sp.label), ''), 'POS') AS sold_by_name";
        $sellerGroup = $hasUserSeller ? ', ps.seller_name, su.name, sp.label' : ', ps.seller_name, sp.label';
    } else {
        $sellerSelect = $hasUserSeller
            ? "COALESCE(NULLIF(TRIM(su.name), ''), NULLIF(TRIM(sp.label), ''), 'POS') AS sold_by_name"
            : "COALESCE(NULLIF(TRIM(sp.label), ''), 'POS') AS sold_by_name";
        $sellerGroup = $hasUserSeller ? ', su.name, sp.label' : ', sp.label';
    }
    $hasRefunded = honey_pos_column_exists($pdo, 'pos_sales', 'refunded_at');
    $refundSelect = $hasRefunded ? ', ps.refunded_at, ps.refund_reason' : ', NULL AS refunded_at, NULL AS refund_reason';
    $refundGroup = $hasRefunded ? ', ps.refunded_at, ps.refund_reason' : '';
    $listSql = "SELECT ps.id AS sale_id, ps.receipt_no, ps.sale_date AS sold_at,
                       ps.subtotal_amount, ps.discount_percent, ps.discount_amount, ps.total_amount,
                       ps.buyer_name, ps.buyer_credentials, COALESCE(ps.is_ar, 0) AS is_ar,
                       {$sellerSelect}{$refundSelect}
                FROM pos_sales ps{$sellerJoin}{$where}
                GROUP BY ps.id, ps.receipt_no, ps.sale_date, ps.subtotal_amount, ps.discount_percent, ps.discount_amount, ps.total_amount, ps.buyer_name, ps.buyer_credentials, ps.is_ar{$sellerGroup}{$refundGroup}
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

    // Heal receipts that were restocked before money reverse existed (idempotent).
    foreach ($saleIds as $saleId) {
        $ownsHeal = !$pdo->inTransaction();
        try {
            if ($ownsHeal) {
                $pdo->beginTransaction();
            }
            pos_sale_heal_returned_units($pdo, $saleId);
            if ($ownsHeal) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownsHeal && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Non-fatal: still show checkouts even if heal fails.
        }
    }

    // Refresh sale money after possible heal.
    if ($saleIds !== []) {
        $hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);
        $refreshSql = 'SELECT ps.id AS sale_id, ps.subtotal_amount, ps.discount_amount, ps.total_amount
                       FROM pos_sales ps WHERE ps.id IN (' . $placeholders . ')';
        $rst = $pdo->prepare($refreshSql);
        $rst->execute($saleIds);
        $moneyById = [];
        foreach ($rst->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $moneyById[(int) $m['sale_id']] = $m;
        }
        foreach ($sales as &$s) {
            $sid = (int) $s['sale_id'];
            if (isset($moneyById[$sid])) {
                $s['subtotal_amount'] = $moneyById[$sid]['subtotal_amount'];
                $s['discount_amount'] = $moneyById[$sid]['discount_amount'];
                $s['total_amount'] = $moneyById[$sid]['total_amount'];
            }
        }
        unset($s);
        unset($hasUserSeller);
    }

    $itemSql = "SELECT pri.sale_id, pri.qty, pri.unit_price, pri.list_unit_price, pri.line_total,
                       pri.variant, pri.size,
                       p.variant AS p_variant, p.size AS p_size, p.name AS product_name,
                       p.dept_product_key
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

    $barcodesBySale = honey_sales_load_checkout_barcodes($pdo, $saleIds);
    foreach ($itemsBySale as $sid => $items) {
        $itemsBySale[$sid] = honey_sales_attach_barcodes_to_items($items, $barcodesBySale[$sid] ?? []);
    }
    $returnedBySale = [];
    foreach ($barcodesBySale as $sid => $units) {
        $returnedBySale[$sid] = [];
        foreach ($units as $unit) {
            if (!empty($unit['returned'])) {
                $returnedBySale[$sid][] = [
                    'code' => $unit['code'],
                    'kind' => $unit['kind'],
                    'match_key' => $unit['match_key'],
                ];
            }
        }
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
            'refunded' => !empty($s['refunded_at']),
            'refunded_at' => !empty($s['refunded_at']) ? (string) $s['refunded_at'] : null,
            'refund_reason' => isset($s['refund_reason']) && $s['refund_reason'] !== null ? (string) $s['refund_reason'] : null,
            'item_count' => count($items),
            'items' => $items,
            'returned_units' => $returnedBySale[$saleId] ?? [],
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
    $packWalkIn = 0.0;
    $packShipped = 0.0;
    foreach ($shareSt->fetchAll(PDO::FETCH_ASSOC) as $sr) {
        if ((string) $sr['sale_type'] === 'walk_in') {
            $packWalkIn = (float) $sr['qty'];
        } elseif ((string) $sr['sale_type'] === 'parcel') {
            $packShipped = (float) $sr['qty'];
        }
    }

    $rtsScan = honey_sales_rts_scan_units($pdo, $range);
    $rtsWalkIn = (float) $rtsScan['walk_in'];
    $rtsShipped = (float) $rtsScan['parcel'];
    $rtsHoneyWalkIn = (float) $rtsScan['honey_walk_in'];
    $rtsHoneyShipped = (float) $rtsScan['honey_parcel'];

    $honeyWalkIn = $channel === 'online' ? 0.0 : ($packWalkIn + $rtsHoneyWalkIn);
    $honeyShipped = $channel === 'walk_in' ? 0.0 : ($packShipped + $rtsHoneyShipped);

    $dept = honey_sales_dept_breakdown($pdo, $range, $channel);
    $deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');

    // Channel share = packaging honey + counted RTS scans + dept (always both channels for the %).
    $walkInQty = $packWalkIn + $rtsWalkIn + (float) $deptShare['walk_in'];
    $onlineQty = $packShipped + $rtsShipped + (float) $deptShare['shipped'];
    $shareTotal = $walkInQty + $onlineQty;

    $rtsHoneyForChannel = 0.0;
    if ($channel === 'walk_in') {
        $rtsHoneyForChannel = $rtsHoneyWalkIn;
    } elseif ($channel === 'online') {
        $rtsHoneyForChannel = $rtsHoneyShipped;
    } else {
        $rtsHoneyForChannel = $rtsHoneyWalkIn + $rtsHoneyShipped;
    }
    $honeyTotal = (float) $all['total'] + $rtsHoneyForChannel;

    return [
        'filter' => $filterMeta,
        'total_bottles_sold' => $honeyTotal,
        'total_units_sold' => $honeyTotal + (float) $dept['total'],
        'honey' => [
            'label' => 'Honey',
            'unit' => 'bottle',
            'walk_in' => $honeyWalkIn,
            'shipped' => $honeyShipped,
            'total' => $honeyTotal,
        ],
        'dept' => $dept,
        'rts' => [
            'walk_in' => $rtsWalkIn,
            'shipped' => $rtsShipped,
            'total' => $rtsWalkIn + $rtsShipped,
        ],
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

/**
 * All matching walk-in checkouts for Excel (ignores UI pagination).
 *
 * @return array{total: int, rows: list<array<string, mixed>>, filter_label: string}
 */
function honey_sales_fetch_checkouts_export(
    PDO $pdo,
    ?string $period,
    ?string $from,
    ?string $to,
    ?string $receiptSearch = null,
    string $saleType = 'all',
    string $product = 'all'
): array {
    $filters = honey_sales_resolve_filters($period, $from, $to);
    $range = $filters['range'];
    $page1 = honey_sales_fetch_checkouts(
        $pdo,
        $range,
        1,
        $receiptSearch,
        $saleType,
        $product,
        HONEY_SALES_EXPORT_MAX_TOTAL_ROWS
    );
    if ((int) $page1['total'] > HONEY_SALES_EXPORT_MAX_TOTAL_ROWS) {
        throw new InvalidArgumentException(
            'Too many walk-in checkouts to export (max ' . HONEY_SALES_EXPORT_MAX_TOTAL_ROWS . '). Narrow the period or filters.'
        );
    }

    $label = $range !== null
        ? ((string) ($range['label'] ?? ($range['from'] . ' – ' . $range['to'])))
        : 'All time';

    return [
        'total' => (int) $page1['total'],
        'rows' => $page1['rows'],
        'filter_label' => $label,
    ];
}

/**
 * Whether a checkout used any RTS-sellable scanned units.
 *
 * @param list<int> $saleIds
 * @return array<int, bool>
 */
function honey_sales_checkout_rts_flags(PDO $pdo, array $saleIds): array
{
    $saleIds = array_values(array_filter(array_map('intval', $saleIds), static fn ($id) => $id > 0));
    $flags = [];
    foreach ($saleIds as $id) {
        $flags[$id] = false;
    }
    if ($saleIds === []) {
        return $flags;
    }
    $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
    $st = $pdo->prepare(
        "SELECT DISTINCT h.pos_sale_id
         FROM honey_sales_log h
         WHERE h.pos_sale_id IN ({$placeholders})
           AND h.source = 'rts'
           AND h.sale_type = 'walk_in'"
    );
    $st->execute($saleIds);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $flags[(int) $row['pos_sale_id']] = true;
    }

    return $flags;
}

/**
 * Pack-scan / shipped lines for Excel.
 *
 * @return list<array<string, mixed>>
 */
function honey_sales_fetch_shipped_export(
    PDO $pdo,
    ?string $period,
    ?string $from,
    ?string $to,
    string $product = 'all'
): array {
    $filters = honey_sales_resolve_filters($period, $from, $to);
    $range = $filters['range'];
    $product = honey_sales_normalize_product_filter($product);
    $rows = [];

    $hasUnitCol = rts_db_has_column($pdo, 'honey_sales_log', 'bottle_unit_id');
    $hasSessCol = rts_db_has_column($pdo, 'honey_sales_log', 'pack_session_id');
    $hasParcelCol = rts_db_has_column($pdo, 'honey_sales_log', 'online_parcel_id');
    $hasQtyOther = rts_db_has_column($pdo, 'honey_sales_log', 'qty_other');

    // Honey unique stickers + honey SKU / legacy parcel log lines (skip bare RTS FIFO duplicates).
    if ($product === 'all' || $product === 'honey') {
        $where = ' WHERE h.sale_type = \'parcel\'';
        $params = [];
        if ($range !== null) {
            $where .= ' AND h.created_at >= ? AND h.created_at <= ?';
            $params[] = $range['from'] . ' 00:00:00';
            $params[] = $range['to'] . ' 23:59:59';
        }

        $unitExpr = $hasUnitCol ? 'h.bottle_unit_id' : 'NULL';
        $sessExpr = $hasSessCol ? 'h.pack_session_id' : 'NULL';
        $parcelExpr = $hasParcelCol ? 'h.online_parcel_id' : 'NULL';
        $qtyOtherExpr = $hasQtyOther ? 'COALESCE(h.qty_other, 0)' : '0';

        // Keep unit/session/parcel rows and packaging legacy; keep RTS only when qty_other (dept) or linked unit/session.
        $where .= " AND (
            ({$unitExpr} IS NOT NULL)
            OR ({$sessExpr} IS NOT NULL)
            OR ({$parcelExpr} IS NOT NULL)
            OR (h.source = 'packaging')
            OR (h.source = 'rts' AND {$qtyOtherExpr} > 0)
        )";

        $barcodeJoin = '';
        $barcodeSelect = 'NULL AS barcode';
        if ($hasUnitCol) {
            $barcodeJoin = ' LEFT JOIN honey_bottle_units u ON u.id = h.bottle_unit_id
                             LEFT JOIN honey_bottle_barcodes bc ON bc.id = u.barcode_id';
            $barcodeSelect = 'bc.code AS barcode';
        }

        $trackJoin = '';
        $trackSelect = 'NULL AS tracking_number';
        if ($hasParcelCol) {
            $trackJoin = ' LEFT JOIN online_order_parcels op ON op.id = h.online_parcel_id';
            $trackSelect = 'op.tracking_number';
        }

        $packerJoin = ' LEFT JOIN users u_sold ON u_sold.id = h.sold_by';
        $packerSelect = "COALESCE(NULLIF(TRIM(u_sold.name), ''), '') AS packed_by_name";
        if ($hasSessCol) {
            $packerJoin .= ' LEFT JOIN honey_bottle_pack_sessions psess ON psess.id = h.pack_session_id
                             LEFT JOIN users u_conf ON u_conf.id = psess.confirmed_by
                             LEFT JOIN users u_start ON u_start.id = psess.started_by';
            $packerSelect = "COALESCE(
                NULLIF(TRIM(u_conf.name), ''),
                NULLIF(TRIM(u_start.name), ''),
                NULLIF(TRIM(u_sold.name), ''),
                ''
            ) AS packed_by_name";
        }

        $sql = "SELECT h.id, h.created_at, h.source, h.variant, h.product_label,
                       h.qty_liter, h.qty_250ml, h.qty_350ml, h.qty_gallon,
                       {$qtyOtherExpr} AS qty_other,
                       {$sessExpr} AS pack_session_id,
                       {$parcelExpr} AS online_parcel_id,
                       {$barcodeSelect},
                       {$trackSelect},
                       {$packerSelect}
                FROM honey_sales_log h
                {$barcodeJoin}
                {$trackJoin}
                {$packerJoin}
                {$where}
                ORDER BY h.created_at DESC, h.id DESC
                LIMIT " . (HONEY_SALES_EXPORT_MAX_TOTAL_ROWS + 1);
        $st = $pdo->prepare($sql);
        $st->execute($params);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $qty = (float) ($row['qty_liter'] ?? 0)
                + (float) ($row['qty_250ml'] ?? 0)
                + (float) ($row['qty_350ml'] ?? 0)
                + (float) ($row['qty_gallon'] ?? 0)
                + (float) ($row['qty_other'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $sizeQtys = [
                'liter' => (float) ($row['qty_liter'] ?? 0),
                '250ml' => (float) ($row['qty_250ml'] ?? 0),
                '350ml' => (float) ($row['qty_350ml'] ?? 0),
                'gallon' => (float) ($row['qty_gallon'] ?? 0),
            ];
            $sizeMap = [
                'liter' => '1L',
                '250ml' => '250ml',
                '350ml' => '350ml',
                'gallon' => 'Gallon',
            ];
            $otherQty = (float) ($row['qty_other'] ?? 0);
            if ($product === 'honey' && $otherQty > 0) {
                continue;
            }
            $productLabel = trim((string) ($row['product_label'] ?? ''));
            $variant = trim((string) ($row['variant'] ?? ''));

            $tracking = trim((string) ($row['tracking_number'] ?? ''));
            $sessId = (int) ($row['pack_session_id'] ?? 0);
            $packRef = $tracking !== '' ? $tracking : ($sessId > 0 ? 'Pack session #' . $sessId : '');
            $packedBy = trim((string) ($row['packed_by_name'] ?? ''));
            $isRts = strtolower((string) ($row['source'] ?? '')) === 'rts';

            if ($otherQty > 0 && $productLabel !== '') {
                $rows[] = [
                    'when_packed' => (string) $row['created_at'],
                    'product' => $productLabel,
                    'variant' => $productLabel,
                    'size_label' => '—',
                    'qty' => $otherQty,
                    'tracking_or_session' => $packRef,
                    'barcode' => trim((string) ($row['barcode'] ?? '')),
                    'is_rts_resellable' => $isRts,
                    'kind' => 'dept',
                    'size_qtys' => ['dept' => $otherQty],
                    'packed_by_name' => $packedBy,
                ];
                continue;
            }

            $variantLabel = $variant !== '' ? ucfirst($variant) : ($productLabel !== '' ? $productLabel : 'Honey');
            $barcode = trim((string) ($row['barcode'] ?? ''));
            $emitted = false;
            foreach ($sizeMap as $sizeKey => $sizeLabel) {
                $q = (float) ($sizeQtys[$sizeKey] ?? 0);
                if ($q <= 0) {
                    continue;
                }
                $emitted = true;
                $rows[] = [
                    'when_packed' => (string) $row['created_at'],
                    'product' => trim($variantLabel . ' ' . $sizeLabel . (abs($q - 1.0) > 0.001 ? ' ×' . $q : '')),
                    'variant' => $variantLabel,
                    'size_label' => $sizeLabel,
                    'qty' => $q,
                    'tracking_or_session' => $packRef,
                    'barcode' => $barcode,
                    'is_rts_resellable' => $isRts,
                    'kind' => 'honey',
                    'size_qtys' => [$sizeKey => $q],
                    'packed_by_name' => $packedBy,
                ];
            }
            if (!$emitted) {
                $rows[] = [
                    'when_packed' => (string) $row['created_at'],
                    'product' => $variantLabel,
                    'variant' => $variantLabel,
                    'size_label' => '—',
                    'qty' => $qty,
                    'tracking_or_session' => $packRef,
                    'barcode' => $barcode,
                    'is_rts_resellable' => $isRts,
                    'kind' => 'honey',
                    'size_qtys' => ['other' => $qty],
                    'packed_by_name' => $packedBy,
                ];
            }
        }
    }

    // Dept unique stickers + SKU qty packs from confirmed pack sessions.
    if ($product === 'all' || in_array($product, ['pt', 'brc', 'jar_cacao', 'honey_comb'], true)) {
        $deptLabels = [];
        foreach (dept_product_catalog() as $p) {
            $deptLabels[(string) $p['key']] = (string) $p['label'];
        }
        $packWhere = " WHERE u.consumed_at IS NOT NULL
                         AND u.consumed_ref_type = 'pack_session'
                         AND u.status IN ('packed','shipped')";
        $packParams = [];
        if ($range !== null) {
            $packWhere .= ' AND u.consumed_at >= ? AND u.consumed_at <= ?';
            $packParams[] = $range['from'] . ' 00:00:00';
            $packParams[] = $range['to'] . ' 23:59:59';
        }
        if ($product !== 'all') {
            $packWhere .= ' AND u.product_key = ?';
            $packParams[] = $product;
        }
        try {
            $packSql = "SELECT u.consumed_at, u.product_key, u.pool, u.consumed_ref_id AS pack_session_id, c.code,
                               COALESCE(
                                 NULLIF(TRIM(u_conf.name), ''),
                                 NULLIF(TRIM(u_start.name), ''),
                                 ''
                               ) AS packed_by_name
                        FROM dept_pouch_units u
                        LEFT JOIN dept_pouch_barcodes c ON c.id = u.barcode_id
                        LEFT JOIN honey_bottle_pack_sessions psess ON psess.id = u.consumed_ref_id
                        LEFT JOIN users u_conf ON u_conf.id = psess.confirmed_by
                        LEFT JOIN users u_start ON u_start.id = psess.started_by
                        {$packWhere}
                        ORDER BY u.consumed_at DESC, u.id DESC
                        LIMIT " . (HONEY_SALES_EXPORT_MAX_TOTAL_ROWS + 1);
            $packSt = $pdo->prepare($packSql);
            $packSt->execute($packParams);
            foreach ($packSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = strtolower(trim((string) $row['product_key']));
                $sessId = (int) ($row['pack_session_id'] ?? 0);
                $rows[] = [
                    'when_packed' => (string) $row['consumed_at'],
                    'product' => $deptLabels[$key] ?? $key,
                    'variant' => $deptLabels[$key] ?? $key,
                    'size_label' => '—',
                    'qty' => 1.0,
                    'tracking_or_session' => $sessId > 0 ? 'Pack session #' . $sessId : '',
                    'barcode' => trim((string) ($row['code'] ?? '')),
                    'is_rts_resellable' => (string) ($row['pool'] ?? '') === 'rts_sellable',
                    'kind' => 'dept',
                    'size_qtys' => ['dept' => 1.0],
                    'packed_by_name' => trim((string) ($row['packed_by_name'] ?? '')),
                ];
            }
        } catch (PDOException $e) {
            // Table missing — skip dept sticker rows.
        }

        $skuWhere = ' WHERE s.status = "confirmed"
                        AND s.confirmed_at IS NOT NULL
                        AND di.sku_id IS NOT NULL AND di.sku_id > 0
                        AND (di.unit_id IS NULL OR di.unit_id < 1)';
        $skuParams = [];
        if ($range !== null) {
            $skuWhere .= ' AND s.confirmed_at >= ? AND s.confirmed_at <= ?';
            $skuParams[] = $range['from'] . ' 00:00:00';
            $skuParams[] = $range['to'] . ' 23:59:59';
        }
        if ($product !== 'all') {
            $skuWhere .= ' AND di.product_key = ?';
            $skuParams[] = $product;
        }
        try {
            $skuSql = "SELECT s.confirmed_at, di.product_key, di.qty, di.sku_code, di.pool, s.id AS pack_session_id,
                              COALESCE(
                                NULLIF(TRIM(u_conf.name), ''),
                                NULLIF(TRIM(u_start.name), ''),
                                ''
                              ) AS packed_by_name
                       FROM honey_bottle_pack_session_dept_items di
                       INNER JOIN honey_bottle_pack_sessions s ON s.id = di.session_id
                       LEFT JOIN users u_conf ON u_conf.id = s.confirmed_by
                       LEFT JOIN users u_start ON u_start.id = s.started_by
                       {$skuWhere}
                       ORDER BY s.confirmed_at DESC, di.id DESC
                       LIMIT " . (HONEY_SALES_EXPORT_MAX_TOTAL_ROWS + 1);
            $skuSt = $pdo->prepare($skuSql);
            $skuSt->execute($skuParams);
            foreach ($skuSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = strtolower(trim((string) $row['product_key']));
                $qty = (float) ($row['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $sessId = (int) ($row['pack_session_id'] ?? 0);
                $label = $deptLabels[$key] ?? $key;
                $rows[] = [
                    'when_packed' => (string) $row['confirmed_at'],
                    'product' => $label . (abs($qty - 1.0) > 0.001 ? ' ×' . $qty : ''),
                    'variant' => $label,
                    'size_label' => '—',
                    'qty' => $qty,
                    'tracking_or_session' => $sessId > 0 ? 'Pack session #' . $sessId : '',
                    'barcode' => trim((string) ($row['sku_code'] ?? '')),
                    'is_rts_resellable' => (string) ($row['pool'] ?? '') === 'rts_sellable',
                    'kind' => 'dept',
                    'size_qtys' => ['dept' => $qty],
                    'packed_by_name' => trim((string) ($row['packed_by_name'] ?? '')),
                ];
            }
        } catch (PDOException $e) {
            // Pack session tables missing — stickers-only export still useful.
        }
    }

    usort($rows, static function ($a, $b) {
        return strcmp((string) ($b['when_packed'] ?? ''), (string) ($a['when_packed'] ?? ''));
    });

    if (count($rows) > HONEY_SALES_EXPORT_MAX_TOTAL_ROWS) {
        throw new InvalidArgumentException(
            'Too many shipped/pack-scan rows to export (max ' . HONEY_SALES_EXPORT_MAX_TOTAL_ROWS . '). Narrow the period or filters.'
        );
    }

    return $rows;
}

/**
 * Labels only (no qty) for one checkout — order matches honey_sales_export_checkout_item_qtys.
 *
 * @param list<array<string, mixed>> $items
 */
function honey_sales_export_checkout_item_labels(array $items): string
{
    if ($items === []) {
        return '—';
    }
    $parts = [];
    foreach ($items as $i) {
        if (!is_array($i)) {
            continue;
        }
        if (!empty($i['variant'])) {
            $parts[] = trim(ucfirst((string) $i['variant']) . ' ' . (string) ($i['size_label'] ?? $i['size'] ?? ''));
        } else {
            $parts[] = trim((string) ($i['label'] ?? $i['name'] ?? 'Item'));
        }
    }

    return $parts === [] ? '—' : implode('; ', $parts);
}

/**
 * Qtys only — same order as honey_sales_export_checkout_item_labels.
 *
 * @param list<array<string, mixed>> $items
 */
function honey_sales_export_checkout_item_qtys(array $items): string
{
    if ($items === []) {
        return '—';
    }
    $parts = [];
    foreach ($items as $i) {
        if (!is_array($i)) {
            continue;
        }
        $qty = $i['qty'] ?? 0;
        $parts[] = is_numeric($qty)
            ? (abs((float) $qty - round((float) $qty)) < 0.001
                ? (string) (int) round((float) $qty)
                : rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.'))
            : (string) $qty;
    }

    return $parts === [] ? '—' : implode('; ', $parts);
}

/**
 * @param list<array<string, mixed>> $items
 * @deprecated Prefer labels + qtys columns; kept for callers that need a single cell.
 */
function honey_sales_export_checkout_items_summary(array $items): string
{
    if ($items === []) {
        return '—';
    }
    $labels = explode('; ', honey_sales_export_checkout_item_labels($items));
    $qtys = explode('; ', honey_sales_export_checkout_item_qtys($items));
    $parts = [];
    $n = max(count($labels), count($qtys));
    for ($i = 0; $i < $n; $i++) {
        $label = trim((string) ($labels[$i] ?? ''));
        $qty = trim((string) ($qtys[$i] ?? ''));
        if ($label === '' || $label === '—') {
            continue;
        }
        $parts[] = $label . ' ×' . ($qty !== '' ? $qty : '0');
    }

    return $parts === [] ? '—' : implode('; ', $parts);
}

/**
 * Normalize receipt/item size to export bucket.
 */
function honey_sales_export_size_bucket(?string $size, ?string $sizeLabel = null): string
{
    $raw = strtolower(trim((string) ($size !== null && $size !== '' ? $size : $sizeLabel)));
    if ($raw === 'liter' || $raw === '1l' || $raw === '1 l') {
        return 'liter';
    }
    if ($raw === '250ml' || $raw === '250 ml') {
        return '250ml';
    }
    if ($raw === '350ml' || $raw === '350 ml') {
        return '350ml';
    }
    if ($raw === 'gallon') {
        return 'gallon';
    }

    return 'other';
}

/**
 * Totals from Walk-in receipt lines (matches sheet Items column).
 *
 * @param list<array<string, mixed>> $checkoutRows
 * @return array{
 *   total: float,
 *   by_size: array<string, float>,
 *   by_variant: array<string, float>,
 *   by_variant_size: array<string, float>,
 *   rts_units: float
 * }
 */
function honey_sales_export_summarize_walk_in(array $checkoutRows, array $rtsFlags): array
{
    $bySize = [
        '250ml' => 0.0,
        '350ml' => 0.0,
        'liter' => 0.0,
        'gallon' => 0.0,
        'dept' => 0.0,
        'other' => 0.0,
    ];
    $byVariant = [];
    $byVariantSize = [];
    $total = 0.0;
    $rtsUnits = 0.0;

    foreach ($checkoutRows as $row) {
        $saleId = (int) ($row['sale_id'] ?? 0);
        $isRts = !empty($rtsFlags[$saleId]);
        $items = is_array($row['items'] ?? null) ? $row['items'] : [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $qty = (float) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $total += $qty;
            if ($isRts) {
                $rtsUnits += $qty;
            }
            $deptKey = trim((string) ($item['product_key'] ?? ''));
            if ($deptKey !== '' || (($item['kind'] ?? '') === 'dept' && empty($item['variant']))) {
                $bySize['dept'] += $qty;
                $vLabel = trim((string) ($item['label'] ?? $item['name'] ?? $deptKey));
                if ($vLabel === '') {
                    $vLabel = 'Dept';
                }
                $byVariant[$vLabel] = ($byVariant[$vLabel] ?? 0) + $qty;
                $vsKey = $vLabel . ' · —';
                $byVariantSize[$vsKey] = ($byVariantSize[$vsKey] ?? 0) + $qty;
                continue;
            }
            $bucket = honey_sales_export_size_bucket(
                isset($item['size']) ? (string) $item['size'] : null,
                isset($item['size_label']) ? (string) $item['size_label'] : null
            );
            if (!isset($bySize[$bucket])) {
                $bucket = 'other';
            }
            $bySize[$bucket] += $qty;
            $variant = trim((string) ($item['variant'] ?? ''));
            $vLabel = $variant !== '' ? ucfirst($variant) : trim((string) ($item['label'] ?? $item['name'] ?? 'Honey'));
            $sizeLabel = honey_sales_size_label($bucket === 'liter' ? 'liter' : ($bucket === 'other' ? '' : $bucket));
            if ($sizeLabel === '' && !empty($item['size_label'])) {
                $sizeLabel = (string) $item['size_label'];
            }
            if ($sizeLabel === '') {
                $sizeLabel = '—';
            }
            $byVariant[$vLabel] = ($byVariant[$vLabel] ?? 0) + $qty;
            $vsKey = $vLabel . ' · ' . $sizeLabel;
            $byVariantSize[$vsKey] = ($byVariantSize[$vsKey] ?? 0) + $qty;
        }
    }

    arsort($byVariant);
    arsort($byVariantSize);

    return [
        'total' => $total,
        'by_size' => $bySize,
        'by_variant' => $byVariant,
        'by_variant_size' => $byVariantSize,
        'rts_units' => $rtsUnits,
    ];
}

/**
 * Totals from Shipped sheet rows (includes packaging + RTS).
 *
 * @param list<array<string, mixed>> $shippedRows
 * @return array{
 *   total: float,
 *   by_size: array<string, float>,
 *   by_variant: array<string, float>,
 *   by_variant_size: array<string, float>,
 *   rts_units: float
 * }
 */
function honey_sales_export_summarize_shipped(array $shippedRows): array
{
    $bySize = [
        '250ml' => 0.0,
        '350ml' => 0.0,
        'liter' => 0.0,
        'gallon' => 0.0,
        'dept' => 0.0,
        'other' => 0.0,
    ];
    $byVariant = [];
    $byVariantSize = [];
    $total = 0.0;
    $rtsUnits = 0.0;

    foreach ($shippedRows as $row) {
        $qty = (float) ($row['qty'] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $total += $qty;
        if (!empty($row['is_rts_resellable'])) {
            $rtsUnits += $qty;
        }
        $sizeQtys = is_array($row['size_qtys'] ?? null) ? $row['size_qtys'] : [];
        if ($sizeQtys === []) {
            $bySize['other'] += $qty;
        } else {
            foreach ($sizeQtys as $size => $q) {
                $q = (float) $q;
                if ($q <= 0) {
                    continue;
                }
                $bucket = $size === 'dept' ? 'dept' : honey_sales_export_size_bucket((string) $size);
                if (!isset($bySize[$bucket])) {
                    $bucket = 'other';
                }
                $bySize[$bucket] += $q;
            }
        }

        $vLabel = trim((string) ($row['variant'] ?? ''));
        if ($vLabel === '') {
            $vLabel = trim((string) ($row['product'] ?? 'Item'));
        }
        $sizeLabel = trim((string) ($row['size_label'] ?? ''));
        if ($sizeLabel === '') {
            $sizeLabel = '—';
        }
        $byVariant[$vLabel] = ($byVariant[$vLabel] ?? 0) + $qty;
        $vsKey = $vLabel . ' · ' . $sizeLabel;
        $byVariantSize[$vsKey] = ($byVariantSize[$vsKey] ?? 0) + $qty;
    }

    arsort($byVariant);
    arsort($byVariantSize);

    return [
        'total' => $total,
        'by_size' => $bySize,
        'by_variant' => $byVariant,
        'by_variant_size' => $byVariantSize,
        'rts_units' => $rtsUnits,
    ];
}

/**
 * Header + breakdown for one channel only (Walk-in sheet or Shipped sheet).
 *
 * @param array{total: float, by_size: array<string, float>, by_variant?: array<string, float>, by_variant_size?: array<string, float>, rts_units: float} $summary
 * @param 'walk_in'|'shipped' $channel
 * @return int next empty row index
 */
function honey_sales_export_write_summary_block(
    \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
    string $title,
    string $filterLabel,
    array $summary,
    string $channel
): int {
    $total = (float) ($summary['total'] ?? 0);
    $rts = (float) ($summary['rts_units'] ?? 0);
    $unitsLabel = $channel === 'shipped'
        ? 'Shipped / pack-scan (units in sheet)'
        : 'Walk-in sold (units on receipts)';

    $sheet->setCellValue('A1', $title);
    $sheet->setCellValue('A2', 'Period');
    $sheet->setCellValue('B2', $filterLabel);
    $sheet->setCellValue('A3', $unitsLabel);
    $sheet->setCellValue('B3', $total);
    $sheet->setCellValue('A4', 'Of which RTS resellable');
    $sheet->setCellValue('B4', $rts);

    $sheet->setCellValue('A6', 'By bottle / size');
    $sheet->setCellValue('B6', 'Qty');
    $sheet->getStyle('A6:B6')->getFont()->setBold(true);

    $labels = [
        '250ml' => '250ml',
        '350ml' => '350ml',
        'liter' => '1L',
        'gallon' => 'Gallon',
        'dept' => 'Dept (pouch/jar/etc.)',
        'other' => 'Other',
    ];
    $r = 7;
    $bySize = is_array($summary['by_size'] ?? null) ? $summary['by_size'] : [];
    foreach ($labels as $key => $label) {
        $q = (float) ($bySize[$key] ?? 0);
        if ($q <= 0) {
            continue;
        }
        $sheet->setCellValue('A' . $r, $label);
        $sheet->setCellValue('B' . $r, $q);
        $r++;
    }
    if ($r === 7) {
        $sheet->setCellValue('A7', '(none)');
        $r = 8;
    }

    $r += 1;
    $sheet->setCellValue('A' . $r, 'By variant');
    $sheet->setCellValue('B' . $r, 'Qty');
    $sheet->getStyle('A' . $r . ':B' . $r)->getFont()->setBold(true);
    $r++;

    $variants = is_array($summary['by_variant'] ?? null) ? $summary['by_variant'] : [];
    $variantKeys = array_keys($variants);
    usort($variantKeys, static function ($a, $b) use ($variants) {
        return (float) ($variants[$b] ?? 0) <=> (float) ($variants[$a] ?? 0);
    });
    $variantStart = $r;
    foreach ($variantKeys as $vk) {
        $q = (float) ($variants[$vk] ?? 0);
        if ($q <= 0) {
            continue;
        }
        $sheet->setCellValue('A' . $r, $vk);
        $sheet->setCellValue('B' . $r, $q);
        $r++;
    }
    if ($r === $variantStart) {
        $sheet->setCellValue('A' . $r, '(none)');
        $r++;
    }

    $r += 1;
    $sheet->setCellValue('A' . $r, 'By variant × size');
    $sheet->setCellValue('B' . $r, 'Qty');
    $sheet->getStyle('A' . $r . ':B' . $r)->getFont()->setBold(true);
    $r++;

    $vs = is_array($summary['by_variant_size'] ?? null) ? $summary['by_variant_size'] : [];
    $vsKeys = array_keys($vs);
    usort($vsKeys, static function ($a, $b) use ($vs) {
        return (float) ($vs[$b] ?? 0) <=> (float) ($vs[$a] ?? 0);
    });
    $vsStart = $r;
    foreach ($vsKeys as $vk) {
        $q = (float) ($vs[$vk] ?? 0);
        if ($q <= 0) {
            continue;
        }
        $sheet->setCellValue('A' . $r, $vk);
        $sheet->setCellValue('B' . $r, $q);
        $r++;
    }
    if ($r === $vsStart) {
        $sheet->setCellValue('A' . $r, '(none)');
        $r++;
    }

    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2:A4')->getFont()->setBold(true);

    return $r + 1;
}

/**
 * Split detail rows into chunks for multi-sheet Excel export.
 *
 * @param list<array<string, mixed>> $rows
 * @return list<list<array<string, mixed>>>
 */
function honey_sales_export_chunk_rows(array $rows, int $perSheet = HONEY_SALES_EXPORT_ROWS_PER_SHEET): array
{
    $perSheet = max(1, $perSheet);
    if ($rows === []) {
        return [];
    }

    return array_chunk($rows, $perSheet);
}

/** Two-letter month codes for weekly sheet titles (JA, FE, …). */
function honey_sales_export_month_code(int $month): string
{
    static $codes = [
        1 => 'JA', 2 => 'FE', 3 => 'MR', 4 => 'AP', 5 => 'MY', 6 => 'JN',
        7 => 'JL', 8 => 'AU', 9 => 'SE', 10 => 'OC', 11 => 'NO', 12 => 'DE',
    ];

    return $codes[$month] ?? 'XX';
}

/**
 * Monday-start week containing $datetime.
 *
 * @return array{key: string, start: string, end: string, label: string, year: string}
 */
function honey_sales_export_week_meta(string $datetime): array
{
    $datePart = substr(trim($datetime), 0, 10);
    $ts = strtotime($datePart);
    if ($ts === false) {
        $ts = time();
    }
    $dow = (int) date('N', $ts); // 1=Mon … 7=Sun
    $weekStart = strtotime('-' . ($dow - 1) . ' days', $ts);
    $weekEnd = strtotime('+6 days', $weekStart);
    $sm = (int) date('n', $weekStart);
    $sd = (int) date('j', $weekStart);
    $em = (int) date('n', $weekEnd);
    $ed = (int) date('j', $weekEnd);
    if ($sm === $em) {
        $label = honey_sales_export_month_code($sm) . ' ' . $sd . '-' . $ed;
    } else {
        $label = honey_sales_export_month_code($sm) . ' ' . $sd . '-'
            . honey_sales_export_month_code($em) . ' ' . $ed;
    }

    return [
        'key' => date('Y-m-d', $weekStart),
        'start' => date('Y-m-d', $weekStart),
        'end' => date('Y-m-d', $weekEnd),
        'label' => $label,
        'year' => date('y', $weekStart),
    ];
}

/**
 * Excel sheet title (max 31 chars). Examples: SE 4-10-shp, JN 28-JL 4-wlk, SE 4-10-shp (2)
 *
 * @param list<string> $usedTitles
 */
function honey_sales_export_unique_sheet_title(
    string $weekLabel,
    string $channelSuffix,
    int $part,
    bool $includeYear,
    string $year,
    array &$usedTitles
): string {
    $label = $includeYear ? ($weekLabel . ' ' . $year) : $weekLabel;
    $base = $label . '-' . $channelSuffix;
    if ($part > 1) {
        $base .= ' (' . $part . ')';
    }
    if (strlen($base) > 31) {
        $base = substr($base, 0, 31);
    }
    $candidate = $base;
    $n = 2;
    while (isset($usedTitles[$candidate])) {
        $suffix = ' (' . $n . ')';
        $candidate = substr($base, 0, max(1, 31 - strlen($suffix))) . $suffix;
        $n++;
    }
    $usedTitles[$candidate] = true;

    return $candidate;
}

/**
 * Group detail rows into Monday-start weeks (oldest week first).
 *
 * @param list<array<string, mixed>> $rows
 * @return list<array{key: string, label: string, start: string, end: string, year: string, rows: list<array<string, mixed>>}>
 */
function honey_sales_export_group_by_week(array $rows, string $dateField): array
{
    $buckets = [];
    foreach ($rows as $row) {
        $when = trim((string) ($row[$dateField] ?? ''));
        if ($when === '') {
            $when = date('Y-m-d H:i:s');
        }
        $meta = honey_sales_export_week_meta($when);
        $k = $meta['key'];
        if (!isset($buckets[$k])) {
            $buckets[$k] = [
                'key' => $k,
                'label' => $meta['label'],
                'start' => $meta['start'],
                'end' => $meta['end'],
                'year' => $meta['year'],
                'rows' => [],
            ];
        }
        $buckets[$k]['rows'][] = $row;
    }
    ksort($buckets);

    return array_values($buckets);
}

/**
 * English month short name for summary sheet titles (Sep Summary, Aug Summary).
 */
function honey_sales_export_month_name(int $month): string
{
    static $names = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    return $names[$month] ?? 'Mon';
}

/**
 * Group detail rows by calendar month (Y-m).
 *
 * @param list<array<string, mixed>> $rows
 * @return array<string, array{key: string, code: string, year: string, label: string, name: string, rows: list<array<string, mixed>>}>
 */
function honey_sales_export_group_by_month(array $rows, string $dateField): array
{
    $buckets = [];
    foreach ($rows as $row) {
        $when = trim((string) ($row[$dateField] ?? ''));
        if ($when === '') {
            $when = date('Y-m-d H:i:s');
        }
        $ts = strtotime(substr($when, 0, 10));
        if ($ts === false) {
            $ts = time();
        }
        $key = date('Y-m', $ts);
        if (!isset($buckets[$key])) {
            $month = (int) date('n', $ts);
            $year = date('y', $ts);
            $code = honey_sales_export_month_code($month);
            $name = honey_sales_export_month_name($month);
            $buckets[$key] = [
                'key' => $key,
                'code' => $code,
                'year' => $year,
                'name' => $name,
                'label' => $name, // Sep / Aug — year added on sheet title when multi-year
                'rows' => [],
            ];
        }
        $buckets[$key]['rows'][] = $row;
    }
    ksort($buckets);

    return $buckets;
}

/**
 * Write size / variant / variant×size blocks for one channel onto a summary sheet.
 *
 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
 * @param array{total?: float, by_size?: array<string, float>, by_variant?: array<string, float>, by_variant_size?: array<string, float>, rts_units?: float} $summary
 */
function honey_sales_export_write_channel_breakdown_block(
    $sheet,
    int $startRow,
    string $heading,
    string $unitsLabel,
    array $summary
): int {
    $r = $startRow;
    $sheet->setCellValue('A' . $r, $heading);
    $sheet->getStyle('A' . $r)->getFont()->setBold(true)->setSize(12);
    $r++;
    $sheet->setCellValue('A' . $r, $unitsLabel);
    $sheet->setCellValue('B' . $r, (float) ($summary['total'] ?? 0));
    $sheet->getStyle('A' . $r)->getFont()->setBold(true);
    $r++;
    $sheet->setCellValue('A' . $r, 'Of which RTS resellable');
    $sheet->setCellValue('B' . $r, (float) ($summary['rts_units'] ?? 0));
    $sheet->getStyle('A' . $r)->getFont()->setBold(true);
    $r += 2;

    $sheet->setCellValue('A' . $r, 'By bottle / size');
    $sheet->setCellValue('B' . $r, 'Qty');
    $sheet->getStyle('A' . $r . ':B' . $r)->getFont()->setBold(true);
    $r++;
    $labels = [
        '250ml' => '250ml',
        '350ml' => '350ml',
        'liter' => '1L',
        'gallon' => 'Gallon',
        'dept' => 'Dept (pouch/jar/etc.)',
        'other' => 'Other',
    ];
    $bySize = is_array($summary['by_size'] ?? null) ? $summary['by_size'] : [];
    $sizeStart = $r;
    foreach ($labels as $key => $label) {
        $q = (float) ($bySize[$key] ?? 0);
        if ($q <= 0) {
            continue;
        }
        $sheet->setCellValue('A' . $r, $label);
        $sheet->setCellValue('B' . $r, $q);
        $r++;
    }
    if ($r === $sizeStart) {
        $sheet->setCellValue('A' . $r, '(none)');
        $r++;
    }

    $r++;
    $sheet->setCellValue('A' . $r, 'By variant');
    $sheet->setCellValue('B' . $r, 'Qty');
    $sheet->getStyle('A' . $r . ':B' . $r)->getFont()->setBold(true);
    $r++;
    $variants = is_array($summary['by_variant'] ?? null) ? $summary['by_variant'] : [];
    $variantKeys = array_keys($variants);
    usort($variantKeys, static function ($a, $b) use ($variants) {
        return (float) ($variants[$b] ?? 0) <=> (float) ($variants[$a] ?? 0);
    });
    $variantStart = $r;
    foreach ($variantKeys as $vk) {
        $q = (float) ($variants[$vk] ?? 0);
        if ($q <= 0) {
            continue;
        }
        $sheet->setCellValue('A' . $r, $vk);
        $sheet->setCellValue('B' . $r, $q);
        $r++;
    }
    if ($r === $variantStart) {
        $sheet->setCellValue('A' . $r, '(none)');
        $r++;
    }

    $r++;
    $sheet->setCellValue('A' . $r, 'By variant × size');
    $sheet->setCellValue('B' . $r, 'Qty');
    $sheet->getStyle('A' . $r . ':B' . $r)->getFont()->setBold(true);
    $r++;
    $vs = is_array($summary['by_variant_size'] ?? null) ? $summary['by_variant_size'] : [];
    $vsKeys = array_keys($vs);
    usort($vsKeys, static function ($a, $b) use ($vs) {
        return (float) ($vs[$b] ?? 0) <=> (float) ($vs[$a] ?? 0);
    });
    $vsStart = $r;
    foreach ($vsKeys as $vk) {
        $q = (float) ($vs[$vk] ?? 0);
        if ($q <= 0) {
            continue;
        }
        $sheet->setCellValue('A' . $r, $vk);
        $sheet->setCellValue('B' . $r, $q);
        $r++;
    }
    if ($r === $vsStart) {
        $sheet->setCellValue('A' . $r, '(none)');
        $r++;
    }

    return $r + 1;
}

/**
 * True when export should get one Summary sheet per calendar month
 * (all-time / multi-month). False for Today / This week / This month / single-month custom.
 *
 * @param array{period?: string, from?: string|null, to?: string|null}|null $range
 * @param array<string, mixed> $monthOrder
 */
function honey_sales_export_use_per_month_summaries(?array $range, array $monthOrder): bool
{
    if ($range === null) {
        return count($monthOrder) > 1 || $monthOrder !== [];
    }
    $period = strtolower((string) ($range['period'] ?? ''));
    if (in_array($period, ['day', 'week', 'month'], true)) {
        return false;
    }
    if ($period === 'custom') {
        $from = (string) ($range['from'] ?? '');
        $to = (string) ($range['to'] ?? '');
        if ($from !== '' && $to !== '' && substr($from, 0, 7) === substr($to, 0, 7)) {
            return false;
        }
    }

    return count($monthOrder) > 1;
}

/**
 * Combined shipped + walk-in summary sheet for a filter period or one calendar month.
 *
 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
 * @param array{total: float, by_size: array<string, float>, by_variant?: array<string, float>, by_variant_size?: array<string, float>, rts_units: float} $shippedSummary
 * @param array{total: float, by_size: array<string, float>, by_variant?: array<string, float>, by_variant_size?: array<string, float>, rts_units: float} $walkInSummary
 */
function honey_sales_export_write_period_summary_sheet(
    $sheet,
    string $heading,
    string $periodFieldLabel,
    string $periodValue,
    array $shippedSummary,
    array $walkInSummary,
    int $shippedRowCount,
    int $walkInCheckoutCount,
    string $channelScopeLabel = 'this period'
): void {
    $sheet->setCellValue('A1', $heading);
    $sheet->setCellValue('A2', $periodFieldLabel);
    $sheet->setCellValue('B2', $periodValue);
    $sheet->setCellValue('A3', 'Shipped rows');
    $sheet->setCellValue('B3', $shippedRowCount);
    $sheet->setCellValue('A4', 'Walk-in checkouts');
    $sheet->setCellValue('B4', $walkInCheckoutCount);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2:A4')->getFont()->setBold(true);

    $r = 6;
    $r = honey_sales_export_write_channel_breakdown_block(
        $sheet,
        $r,
        'Shipped / pack-scan (' . $channelScopeLabel . ')',
        'Shipped / pack-scan (units in sheet)',
        $shippedSummary
    );
    $r = honey_sales_export_write_channel_breakdown_block(
        $sheet,
        $r,
        'Walk-in sales (' . $channelScopeLabel . ')',
        'Walk-in sold (units on receipts)',
        $walkInSummary
    );

    foreach (['A', 'B'] as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

/**
 * @deprecated Use honey_sales_export_write_period_summary_sheet
 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
 * @param array{total: float, by_size: array<string, float>, by_variant?: array<string, float>, by_variant_size?: array<string, float>, rts_units: float} $shippedSummary
 * @param array{total: float, by_size: array<string, float>, by_variant?: array<string, float>, by_variant_size?: array<string, float>, rts_units: float} $walkInSummary
 */
function honey_sales_export_write_month_summary_sheet(
    $sheet,
    string $monthLabel,
    string $monthKey,
    array $shippedSummary,
    array $walkInSummary,
    int $shippedRowCount,
    int $walkInCheckoutCount
): void {
    honey_sales_export_write_period_summary_sheet(
        $sheet,
        $monthLabel . ' — monthly summary',
        'Calendar month',
        $monthKey,
        $shippedSummary,
        $walkInSummary,
        $shippedRowCount,
        $walkInCheckoutCount,
        'this month'
    );
}

/**
 * Write shipped detail table with mini size/variant summary at top.
 *
 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
 * @param list<array<string, mixed>> $rows
 */
function honey_sales_export_write_shipped_detail(
    $sheet,
    array $rows,
    string $partLabel,
    string $emptyMessage,
    string $periodLabel = ''
): void {
    $summary = honey_sales_export_summarize_shipped($rows);
    $period = $periodLabel !== '' ? $periodLabel : $partLabel;
    $detailStart = honey_sales_export_write_summary_block(
        $sheet,
        'Shipped / pack-scan',
        $period,
        $summary,
        'shipped'
    );

    $sheet->setCellValue('A' . $detailStart, 'Detail · ' . $partLabel);
    $sheet->getStyle('A' . $detailStart)->getFont()->setBold(true)->setSize(12);

    $headers = ['When packed', 'Variant', 'Size', 'Qty', 'Tracking / pack session', 'Barcode', 'RTS resellable', 'Packed by'];
    $headerRow = $detailStart + 2;
    $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    foreach ($headers as $i => $h) {
        $sheet->setCellValue($cols[$i] . $headerRow, $h);
    }
    $sheet->getStyle('A' . $headerRow . ':H' . $headerRow)->getFont()->setBold(true);

    if ($rows === []) {
        $sheet->setCellValue('A' . ($headerRow + 1), $emptyMessage);
    } else {
        $r = $headerRow + 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, (string) $row['when_packed']);
            $sheet->setCellValue('B' . $r, (string) ($row['variant'] ?? $row['product'] ?? ''));
            $sheet->setCellValue('C' . $r, (string) ($row['size_label'] ?? '—'));
            $sheet->setCellValue('D' . $r, (float) $row['qty']);
            $sheet->setCellValue('E' . $r, (string) $row['tracking_or_session']);
            $sheet->setCellValue('F' . $r, (string) $row['barcode']);
            $sheet->setCellValue('G' . $r, !empty($row['is_rts_resellable']) ? 'Yes' : 'No');
            $packedBy = trim((string) ($row['packed_by_name'] ?? ''));
            $sheet->setCellValue('H' . $r, $packedBy !== '' ? $packedBy : '—');
            $r++;
        }
    }
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

/**
 * Write walk-in detail table with mini size/variant summary at top.
 *
 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
 * @param list<array<string, mixed>> $rows
 * @param array<int, bool> $rtsFlags
 */
function honey_sales_export_write_walk_in_detail(
    $sheet,
    array $rows,
    array $rtsFlags,
    bool $canViewAmounts,
    string $partLabel,
    string $emptyMessage,
    string $periodLabel = ''
): void {
    $summary = honey_sales_export_summarize_walk_in($rows, $rtsFlags);
    $period = $periodLabel !== '' ? $periodLabel : $partLabel;
    $detailStart = honey_sales_export_write_summary_block(
        $sheet,
        'Walk-in sales',
        $period,
        $summary,
        'walk_in'
    );

    $sheet->setCellValue('A' . $detailStart, 'Detail · ' . $partLabel);
    $sheet->getStyle('A' . $detailStart)->getFont()->setBold(true)->setSize(12);

    $headers = ['When sold', 'Receipt', 'Sold by', 'Sold to', 'Items', 'Qty', 'Type', 'RTS resellable'];
    if ($canViewAmounts) {
        $headers[] = 'Total';
    }
    $headerRow = $detailStart + 2;
    $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
    foreach ($headers as $i => $h) {
        $sheet->setCellValue($cols[$i] . $headerRow, $h);
    }
    $lastCol = $canViewAmounts ? 'I' : 'H';
    $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getFont()->setBold(true);

    if ($rows === []) {
        $sheet->setCellValue('A' . ($headerRow + 1), $emptyMessage);
    } else {
        $r = $headerRow + 1;
        foreach ($rows as $row) {
            $saleId = (int) ($row['sale_id'] ?? 0);
            $type = !empty($row['is_ar'])
                ? 'AR'
                : (!empty($row['discount_percent']) ? ((int) $row['discount_percent'] . '% off') : 'Regular');
            $items = is_array($row['items'] ?? null) ? $row['items'] : [];
            $sheet->setCellValue('A' . $r, (string) ($row['sold_at'] ?? ''));
            $sheet->setCellValue('B' . $r, (string) ($row['receipt_no'] ?? ''));
            $sheet->setCellValue('C' . $r, (string) ($row['sold_by_name'] ?? 'POS'));
            $sheet->setCellValue('D' . $r, (string) ($row['buyer_name'] ?? 'Walk-in'));
            $sheet->setCellValue('E' . $r, honey_sales_export_checkout_item_labels($items));
            $sheet->setCellValue('F' . $r, honey_sales_export_checkout_item_qtys($items));
            $sheet->setCellValue('G' . $r, $type);
            $sheet->setCellValue('H' . $r, !empty($rtsFlags[$saleId]) ? 'Yes' : 'No');
            if ($canViewAmounts) {
                $sheet->setCellValue('I' . $r, (float) ($row['total_amount'] ?? 0));
            }
            $r++;
        }
    }
    foreach (range('A', $lastCol) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

/**
 * Build a multi-sheet sales workbook (Summary + chunked Shipped + Walk-in) and return the temp file path.
 *
 * @param array{period:?string,from:?string,to:?string,receipt:?string,sale_type:string,channel:string,product:string} $opts
 */
function honey_sales_build_export_xlsx(PDO $pdo, array $opts, bool $canViewAmounts): string
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer autoload missing (PhpSpreadsheet). Run composer install.');
    }
    require_once $autoload;

    $period = $opts['period'] ?? null;
    $from = $opts['from'] ?? null;
    $to = $opts['to'] ?? null;
    $receipt = $opts['receipt'] ?? null;
    $saleType = (string) ($opts['sale_type'] ?? 'all');
    $channel = (string) ($opts['channel'] ?? 'all');
    $product = (string) ($opts['product'] ?? 'all');
    if (!in_array($channel, ['all', 'walk_in', 'online'], true)) {
        $channel = 'all';
    }

    $filters = honey_sales_resolve_filters($period, $from, $to);
    $range = $filters['range'];
    $filterLabel = $range !== null
        ? (string) ($range['label'] ?? ($range['from'] . ' – ' . $range['to']))
        : 'All time';

    $walkInRows = [];
    $rtsFlags = [];
    if ($channel !== 'online') {
        $exported = honey_sales_fetch_checkouts_export($pdo, $period, $from, $to, $receipt, $saleType, $product);
        $walkInRows = $exported['rows'];
        $saleIds = array_map(static fn ($r) => (int) ($r['sale_id'] ?? 0), $walkInRows);
        $rtsFlags = honey_sales_checkout_rts_flags($pdo, $saleIds);
        if (!empty($exported['filter_label'])) {
            $filterLabel = (string) $exported['filter_label'];
        }
    }

    $shippedRows = [];
    if ($channel !== 'walk_in') {
        $shippedRows = honey_sales_fetch_shipped_export($pdo, $period, $from, $to, $product);
    }

    $shippedWeeks = honey_sales_export_group_by_week($shippedRows, 'when_packed');
    $walkInWeeks = honey_sales_export_group_by_week($walkInRows, 'sold_at');
    $shippedMonths = honey_sales_export_group_by_month($shippedRows, 'when_packed');
    $walkInMonths = honey_sales_export_group_by_month($walkInRows, 'sold_at');

    $weekYears = [];
    foreach ($shippedWeeks as $w) {
        $weekYears[$w['year']] = true;
    }
    foreach ($walkInWeeks as $w) {
        $weekYears[$w['year']] = true;
    }
    $includeYear = count($weekYears) > 1;

    // Merge week keys chronologically for sheet order (shp then wlk per week).
    $weekOrder = [];
    foreach ($shippedWeeks as $w) {
        $weekOrder[$w['key']] = $w;
    }
    foreach ($walkInWeeks as $w) {
        if (!isset($weekOrder[$w['key']])) {
            $weekOrder[$w['key']] = $w;
        }
    }
    ksort($weekOrder);

    $shippedByKey = [];
    foreach ($shippedWeeks as $w) {
        $shippedByKey[$w['key']] = $w;
    }
    $walkInByKey = [];
    foreach ($walkInWeeks as $w) {
        $walkInByKey[$w['key']] = $w;
    }

    $monthOrder = [];
    foreach ($shippedMonths as $key => $m) {
        $monthOrder[$key] = $m;
    }
    foreach ($walkInMonths as $key => $m) {
        if (!isset($monthOrder[$key])) {
            $monthOrder[$key] = $m;
        } else {
            // keep meta from first; rows stay in per-channel maps
        }
    }
    ksort($monthOrder);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $usedTitles = [];
    $firstSheet = true;
    $splitMonthSummaries = honey_sales_export_use_per_month_summaries($range, $monthOrder);

    // --- Summary sheet(s): filter-scoped for Today/week/month; per calendar month for all-time ---
    if ($monthOrder === [] && $shippedRows === [] && $walkInRows === []) {
        $sheet = $spreadsheet->getActiveSheet();
        $title = 'No data';
        $usedTitles[$title] = true;
        $sheet->setTitle($title);
        $sheet->setCellValue('A1', 'No sales rows for this export filter.');
        $sheet->setCellValue('A2', 'Period');
        $sheet->setCellValue('B2', $filterLabel);
        $firstSheet = false;
    } elseif (!$splitMonthSummaries) {
        $shpSum = honey_sales_export_summarize_shipped($shippedRows);
        $wlkSum = honey_sales_export_summarize_walk_in($walkInRows, $rtsFlags);
        $title = 'Summary';
        $usedTitles[$title] = true;
        $sheet = $spreadsheet->getActiveSheet();
        $firstSheet = false;
        $sheet->setTitle($title);
        honey_sales_export_write_period_summary_sheet(
            $sheet,
            $filterLabel . ' — summary',
            'Period',
            $filterLabel,
            $shpSum,
            $wlkSum,
            count($shippedRows),
            count($walkInRows),
            'this period'
        );
    } else {
        $monthYearsInExport = [];
        foreach ($monthOrder as $m) {
            $monthYearsInExport[(string) ($m['year'] ?? '')] = true;
        }
        $multiYearMonths = count($monthYearsInExport) > 1;

        foreach ($monthOrder as $monthKey => $meta) {
            $shpRows = $shippedMonths[$monthKey]['rows'] ?? [];
            $wlkRows = $walkInMonths[$monthKey]['rows'] ?? [];
            $shpSum = honey_sales_export_summarize_shipped($shpRows);
            $wlkSum = honey_sales_export_summarize_walk_in($wlkRows, $rtsFlags);
            $monthName = (string) ($meta['name'] ?? $meta['label'] ?? 'Month');
            $monthYear = (string) ($meta['year'] ?? '');
            $titleBase = $multiYearMonths
                ? ($monthName . ' ' . $monthYear . ' Summary')
                : ($monthName . ' Summary');
            $title = $titleBase;
            if (strlen($title) > 31) {
                $title = substr($title, 0, 31);
            }
            $n = 2;
            $candidate = $title;
            while (isset($usedTitles[$candidate])) {
                $suffix = ' (' . $n . ')';
                $candidate = substr($title, 0, max(1, 31 - strlen($suffix))) . $suffix;
                $n++;
            }
            $usedTitles[$candidate] = true;

            if ($firstSheet) {
                $sheet = $spreadsheet->getActiveSheet();
                $firstSheet = false;
            } else {
                $sheet = $spreadsheet->createSheet();
            }
            $sheet->setTitle($candidate);
            honey_sales_export_write_month_summary_sheet(
                $sheet,
                $monthName,
                $monthKey,
                $shpSum,
                $wlkSum,
                count($shpRows),
                count($wlkRows)
            );
        }
    }

    $shippedEmpty = $channel === 'walk_in'
        ? 'No shipped rows for Walk-in filter.'
        : 'No shipped / pack-scan rows for this period.';
    $walkEmpty = $channel === 'online'
        ? 'No walk-in rows for Shipped filter.'
        : 'No walk-in checkouts for this period.';

    if ($weekOrder === []) {
        if ($channel !== 'walk_in') {
            $sheet = $spreadsheet->createSheet();
            $title = honey_sales_export_unique_sheet_title('No data', 'shp', 1, false, '', $usedTitles);
            $sheet->setTitle($title);
            honey_sales_export_write_shipped_detail(
                $sheet,
                [],
                'Shipped / pack-scan',
                $shippedEmpty,
                $filterLabel
            );
        }
        if ($channel !== 'online') {
            $sheet = $spreadsheet->createSheet();
            $title = honey_sales_export_unique_sheet_title('No data', 'wlk', 1, false, '', $usedTitles);
            $sheet->setTitle($title);
            honey_sales_export_write_walk_in_detail(
                $sheet,
                [],
                $rtsFlags,
                $canViewAmounts,
                'Walk-in sales',
                $walkEmpty,
                $filterLabel
            );
        }
    } else {
        foreach ($weekOrder as $key => $meta) {
            $weekLabel = (string) $meta['label'];
            $year = (string) $meta['year'];
            $weekPeriodLabel = $filterLabel . ' · ' . $weekLabel
                . ' (Mon ' . $meta['start'] . ' – Sun ' . $meta['end'] . ')';

            if ($channel !== 'walk_in') {
                $shpRows = $shippedByKey[$key]['rows'] ?? [];
                foreach (honey_sales_export_chunk_rows($shpRows) as $i => $chunk) {
                    $part = $i + 1;
                    $sheet = $spreadsheet->createSheet();
                    $title = honey_sales_export_unique_sheet_title(
                        $weekLabel,
                        'shp',
                        $part,
                        $includeYear,
                        $year,
                        $usedTitles
                    );
                    $sheet->setTitle($title);
                    $partLabel = 'Shipped / pack-scan · ' . $weekLabel
                        . ($part > 1 ? ' · part ' . $part : '');
                    honey_sales_export_write_shipped_detail(
                        $sheet,
                        $chunk,
                        $partLabel,
                        $shippedEmpty,
                        $weekPeriodLabel
                    );
                }
            }

            if ($channel !== 'online') {
                $wlkRows = $walkInByKey[$key]['rows'] ?? [];
                foreach (honey_sales_export_chunk_rows($wlkRows) as $i => $chunk) {
                    $part = $i + 1;
                    $sheet = $spreadsheet->createSheet();
                    $title = honey_sales_export_unique_sheet_title(
                        $weekLabel,
                        'wlk',
                        $part,
                        $includeYear,
                        $year,
                        $usedTitles
                    );
                    $sheet->setTitle($title);
                    $partLabel = 'Walk-in · ' . $weekLabel
                        . ($part > 1 ? ' · part ' . $part : '');
                    honey_sales_export_write_walk_in_detail(
                        $sheet,
                        $chunk,
                        $rtsFlags,
                        $canViewAmounts,
                        $partLabel,
                        $walkEmpty,
                        $weekPeriodLabel
                    );
                }
            }
        }
    }

    $spreadsheet->setActiveSheetIndex(0);

    $dir = sys_get_temp_dir();
    $path = $dir . DIRECTORY_SEPARATOR . 'sales_export_' . bin2hex(random_bytes(8)) . '.xlsx';
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($path);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    return $path;
}


