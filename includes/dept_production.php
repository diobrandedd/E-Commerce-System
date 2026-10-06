<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/material_inventory.php';
require_once __DIR__ . '/catalog_bootstrap.php';

function dept_production_tables_ready(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $pdo->query('SELECT 1 FROM dept_wip_rows LIMIT 1');
        $pdo->query('SELECT 1 FROM dept_production_entries LIMIT 1');
        $ready = true;
    } catch (Throwable) {
        $ready = false;
    }

    return $ready;
}

/** @return list<array<string, mixed>> */
function dept_production_load_formula_lines(PDO $pdo, string $productKey): array
{
    $st = $pdo->prepare(
        'SELECT pf.id AS formula_id, pf.stock_id, pf.qty_per_unit, pf.line_label,
                s.name AS stock_name, s.category, s.unit, s.on_hand, s.dept_id,
                s.current_price, s.production_deduct, s.pool_yield_qty, s.pool_yield_unit
         FROM products p
         INNER JOIN product_formula pf ON pf.product_id = p.id
         INNER JOIN stocks s ON s.id = pf.stock_id
         WHERE p.dept_product_key = ? AND pf.stock_id IS NOT NULL
         ORDER BY pf.id ASC'
    );
    $st->execute([$productKey]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        return [
            'formula_id' => (int) $row['formula_id'],
            'stock_id' => (int) $row['stock_id'],
            'stock_name' => (string) $row['stock_name'],
            'category' => (string) $row['category'],
            'unit' => (string) $row['unit'],
            'qty_per_unit' => (float) $row['qty_per_unit'],
            'on_hand' => round((float) ($row['on_hand'] ?? 0), 4),
            'dept_id' => (int) $row['dept_id'],
            'current_price' => round((float) ($row['current_price'] ?? 0), 4),
            'production_deduct' => (int) ($row['production_deduct'] ?? 1),
            'pool_yield_qty' => $row['pool_yield_qty'] !== null ? (float) $row['pool_yield_qty'] : null,
            'pool_yield_unit' => $row['pool_yield_unit'] !== null ? (string) $row['pool_yield_unit'] : null,
        ];
    }, $rows);
}

function dept_production_stock_deducts(array $line): bool
{
    if (($line['category'] ?? '') === 'packaging') {
        return (int) ($line['production_deduct'] ?? 1) === 1;
    }

    return true;
}

function dept_production_pt_action_matches_stage(string $action, string $stage): bool
{
    if ($action === $stage) {
        return true;
    }

    return $action === 'use_raw_bags' && $stage === 'make_tea_bags';
}

function dept_production_pt_stage_for_stock(string $stockName, string $category = ''): ?string
{
    if ($stockName === PT_LEAF_STOCK_NAME) {
        return 'make_tea_bags';
    }
    if ($stockName === PT_PACKAGING_STOCK_NAME) {
        return 'pouch';
    }
    // Tea-bag-stage packaging (sachet, yarn, etc.) — anything except outer pouch packaging.
    if ($category === 'packaging' && $stockName !== PT_PACKAGING_STOCK_NAME) {
        return 'make_tea_bags';
    }

    return null;
}

/**
 * @return 'raw_bag'|'packaging'
 */
function dept_production_line_kind(string $productKey, string $stockName, string $category): string
{
    $leaf = dept_pricing_leaf_stock_name($productKey);
    if ($leaf !== null && $stockName === $leaf) {
        return 'raw_bag';
    }
    if ($category === 'packaging') {
        return 'packaging';
    }

    throw new RuntimeException(
        'Formula stock "' . $stockName . '" is not mapped for production deduct. Update production stage mapping.'
    );
}

/**
 * @return list<array{stock_id: int, stock_name: string, kind: string, unit: string, qty: float}>
 */
function dept_production_fallback_deduct_lines(string $productKey, string $action, float $qty): array
{
    $lines = [];
    if ($productKey === 'pt' && $action === 'use_raw_bags') {
        $lines[] = [
            'stock_id' => 0,
            'stock_name' => PT_LEAF_STOCK_NAME,
            'kind' => 'raw_bag',
            'unit' => 'bag',
            'qty' => round($qty, 6),
        ];

        return $lines;
    }
    if ($productKey === 'pt' && $action === 'make_tea_bags') {
        $bags = round($qty * (PT_GRAMS_PER_TEA_BAG / PT_GRAMS_PER_BAG), 6);
        $lines[] = [
            'stock_id' => 0,
            'stock_name' => PT_LEAF_STOCK_NAME,
            'kind' => 'raw_bag',
            'unit' => 'bag',
            'qty' => $bags,
        ];
        return $lines;
    }
    if ($productKey === 'pt' && $action === 'pouch') {
        $lines[] = [
            'stock_id' => 0,
            'stock_name' => PT_PACKAGING_STOCK_NAME,
            'kind' => 'packaging',
            'unit' => 'pouch',
            'qty' => $qty,
        ];

        return $lines;
    }
    if ($productKey === 'brc' && $action === 'produce') {
        return [
            [
                'stock_id' => 0,
                'stock_name' => BRC_RAW_STOCK_NAME,
                'kind' => 'raw_bag',
                'unit' => 'bag',
                'qty' => round($qty * dept_brc_bags_per_pouch(), 6),
            ],
            [
                'stock_id' => 0,
                'stock_name' => BRC_PACKAGING_STOCK_NAME,
                'kind' => 'packaging',
                'unit' => 'pouch',
                'qty' => $qty,
            ],
        ];
    }
    if ($productKey === 'jar_cacao' && $action === 'produce') {
        return [
            [
                'stock_id' => 0,
                'stock_name' => JAR_CACAO_RAW_STOCK_NAME,
                'kind' => 'raw_bag',
                'unit' => 'bag',
                'qty' => round($qty * dept_jar_cacao_bags_per_jar(), 6),
            ],
            [
                'stock_id' => 0,
                'stock_name' => JAR_CACAO_PACKAGING_STOCK_NAME,
                'kind' => 'packaging',
                'unit' => 'pcs',
                'qty' => $qty,
            ],
        ];
    }
    if ($productKey === 'honey_comb' && $action === 'produce') {
        return [
            [
                'stock_id' => 0,
                'stock_name' => HONEY_COMB_RAW_STOCK_NAME,
                'kind' => 'raw_bag',
                'unit' => 'bag',
                'qty' => round($qty * dept_honey_comb_bags_per_jar(), 6),
            ],
            [
                'stock_id' => 0,
                'stock_name' => HONEY_COMB_JAR_STOCK_NAME,
                'kind' => 'packaging',
                'unit' => 'pcs',
                'qty' => $qty,
            ],
            [
                'stock_id' => 0,
                'stock_name' => HONEY_COMB_STICKER_STOCK_NAME,
                'kind' => 'packaging',
                'unit' => 'pcs',
                'qty' => $qty,
            ],
        ];
    }

    throw new RuntimeException('No production BOM fallback for this product and action.');
}

/**
 * @return list<array{stock_id: int, stock_name: string, kind: string, unit: string, qty: float}>
 */
function dept_production_formula_deduct_lines(PDO $pdo, string $productKey, string $action, float $qty): array
{
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }

    $formulaLines = dept_production_load_formula_lines($pdo, $productKey);
    $result = [];

    if ($productKey === 'pt') {
        foreach ($formulaLines as $line) {
            if (!dept_production_stock_deducts($line)) {
                continue;
            }
            $stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);
            if ($stage === null) {
                throw new RuntimeException(
                    'Formula stock "' . $line['stock_name'] . '" is not used at Make tea bags or Pouch. '
                    . 'Use leaf on the formula for the fill step, and '
                    . PT_PACKAGING_STOCK_NAME . ' for the pouch step — or remove the extra line from Product Costing.'
                );
            }
            if (!dept_production_pt_action_matches_stage($action, $stage)) {
                continue;
            }
            $perPouch = $line['qty_per_unit'];
            $lineKind = dept_production_line_kind($productKey, $line['stock_name'], $line['category']);
            if ($action === 'use_raw_bags' && $lineKind === 'raw_bag') {
                $deductQty = round($qty, 6);
            } elseif ($action === 'use_raw_bags') {
                $teaBagsDerived = round($qty * dept_pt_tea_bags_per_bag(), 6);
                $deductQty = round($perPouch / PT_TEA_BAGS_PER_POUCH * $teaBagsDerived, 6);
            } elseif ($action === 'make_tea_bags') {
                $deductQty = round($perPouch / PT_TEA_BAGS_PER_POUCH * $qty, 6);
            } else {
                $deductQty = round($perPouch * $qty, 6);
            }
            if ($deductQty <= 0) {
                continue;
            }
            $result[] = [
                'stock_id' => (int) $line['stock_id'],
                'stock_name' => $line['stock_name'],
                'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),
                'unit' => $line['unit'],
                'qty' => $deductQty,
            ];
        }
    } else {
        if ($action !== 'produce') {
            throw new RuntimeException('Invalid production action for this product.');
        }
        foreach ($formulaLines as $line) {
            if (!dept_production_stock_deducts($line)) {
                continue;
            }
            $deductQty = round($line['qty_per_unit'] * $qty, 6);
            if ($deductQty <= 0) {
                continue;
            }
            $result[] = [
                'stock_id' => (int) $line['stock_id'],
                'stock_name' => $line['stock_name'],
                'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),
                'unit' => $line['unit'],
                'qty' => $deductQty,
            ];
        }
    }

    if ($result === []) {
        $fallback = dept_production_fallback_deduct_lines($productKey, $action, $qty);
        foreach ($fallback as $line) {
            if ($line['stock_id'] < 1) {
                $st = $pdo->prepare(
                    'SELECT id, unit, category FROM stocks WHERE name = ? AND supplier_id IS NULL ORDER BY id ASC LIMIT 1'
                );
                $st->execute([$line['stock_name']]);
                $stock = $st->fetch(PDO::FETCH_ASSOC);
                if (!$stock) {
                    throw new RuntimeException('Stock not found for BOM line: ' . $line['stock_name']);
                }
                $line['stock_id'] = (int) $stock['id'];
                $line['unit'] = (string) $stock['unit'];
                $line['kind'] = dept_production_line_kind($productKey, $line['stock_name'], (string) $stock['category']);
            }
            $result[] = $line;
        }
    }

    return $result;
}

/** @return array{stock_id: int, qty: float, on_hand: float} */
function dept_packaging_deduct(PDO $pdo, int $stockId, float $qty, int $userId, string $label): array
{
    if ($qty <= 0) {
        throw new RuntimeException('Packaging quantity must be > 0.');
    }
    $row = material_inventory_fetch_stock($pdo, $stockId, true);
    if ($row === null) {
        throw new RuntimeException('Packaging stock not found.');
    }
    if ((string) $row['category'] !== 'packaging') {
        throw new RuntimeException('Only packaging stocks can be deducted here.');
    }
    $current = round((float) ($row['on_hand'] ?? 0), 4);
    if ($qty > $current + 0.0001) {
        throw new RuntimeException(
            'Not enough ' . $row['name'] . ' on hand (' . round($current, 2) . ' ' . $row['unit'] . ', need ' . round($qty, 2) . ').'
        );
    }
    $next = round($current - $qty, 4);
    $pdo->prepare('UPDATE stocks SET on_hand = ? WHERE id = ?')->execute([$next, $stockId]);
    $pdo->prepare(
        'INSERT INTO stock_entries (stock_id, dept_id, qty, batching_date, supplier_batch_no, entered_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $stockId,
        (int) $row['dept_id'],
        round(-$qty, 4),
        date('Y-m-d H:i:s'),
        substr($label, 0, 120),
        $userId,
    ]);

    return ['stock_id' => $stockId, 'qty' => $qty, 'on_hand' => $next];
}

/**
 * @param list<array{stock_id: int, stock_name: string, kind: string, unit: string, qty: float}> $lines
 * @return list<array<string, mixed>>
 */
function dept_production_apply_deduct_lines(
    PDO $pdo,
    int $userId,
    string $productKey,
    array $lines,
    string $label
): array {
    $applied = [];
    foreach ($lines as $line) {
        if ($line['kind'] === 'raw_bag') {
            $alloc = dept_raw_deduct_bags_fifo($pdo, $userId, $productKey, (float) $line['qty'], $label);
            $applied[] = [
                'kind' => 'raw_bag',
                'stock_name' => $line['stock_name'],
                'qty' => (float) $line['qty'],
                'unit' => $line['unit'],
                'allocations' => $alloc,
            ];
        } elseif ($line['kind'] === 'packaging') {
            // Packaging materials leave warehouse only via Packaging Materials scan/manual deduct.
            $applied[] = [
                'kind' => 'packaging',
                'stock_id' => (int) $line['stock_id'],
                'stock_name' => $line['stock_name'],
                'qty' => (float) $line['qty'],
                'unit' => $line['unit'],
                'skipped' => true,
                'skip_reason' => 'Packaging stock-out is scan-only; not deducted on produce.',
            ];
        }
    }

    return $applied;
}

/** @return array<string, mixed>|null */
function dept_wip_fetch_row(PDO $pdo, string $productKey, string $wipUnit = 'tea_bag', bool $forUpdate = false): ?array
{
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        return null;
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $sql = 'SELECT * FROM dept_wip_rows WHERE dept_id = ? AND product_key = ? AND wip_unit = ? LIMIT 1';
    if ($forUpdate) {
        $sql = str_replace('LIMIT 1', 'LIMIT 1 FOR UPDATE', $sql);
    }
    $st = $pdo->prepare($sql);
    $st->execute([$deptId, $productKey, $wipUnit]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function dept_wip_on_hand(PDO $pdo, string $productKey, string $wipUnit = 'tea_bag'): float
{
    if (!dept_production_tables_ready($pdo)) {
        return 0.0;
    }
    $row = dept_wip_fetch_row($pdo, $productKey, $wipUnit);

    return $row !== null ? round((float) $row['current_qty'], 2) : 0.0;
}

/** @return array<string, mixed> */
function dept_wip_adjust(PDO $pdo, int $userId, string $productKey, float $delta, string $wipUnit = 'tea_bag'): array
{
    $def = dept_product_by_key($productKey);
    if ($def === null) {
        throw new RuntimeException('Unknown product.');
    }
    $deptId = dept_product_dept_id($pdo, (string) $def['dept']);
    $row = dept_wip_fetch_row($pdo, $productKey, $wipUnit, true);
    if ($row === null) {
        if ($delta < 0) {
            throw new RuntimeException('No WIP on hand to deduct.');
        }
        $pdo->prepare(
            'INSERT INTO dept_wip_rows (dept_id, product_key, wip_unit, current_qty, created_by)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$deptId, $productKey, $wipUnit, $delta, $userId]);
    } else {
        $next = round((float) $row['current_qty'] + $delta, 2);
        if ($next < -0.0001) {
            throw new RuntimeException('Not enough WIP on hand.');
        }
        $pdo->prepare('UPDATE dept_wip_rows SET current_qty = ? WHERE id = ?')->execute([max(0, $next), (int) $row['id']]);
    }

    return ['product_key' => $productKey, 'wip_unit' => $wipUnit, 'wip_on_hand' => dept_wip_on_hand($pdo, $productKey, $wipUnit)];
}

/**
 * @param list<array<string, mixed>> $deductLines
 * @param string|null $createdAt Y-m-d H:i:s for backdated log rows; null = DB default (now)
 */
function dept_production_log_entry(
    PDO $pdo,
    string $productKey,
    string $action,
    float $qty,
    int $userId,
    ?string $note,
    array $deductLines,
    ?string $createdAt = null
): int {
    $noteVal = $note !== null && trim($note) !== '' ? trim($note) : null;
    $deductJson = json_encode($deductLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($createdAt !== null && $createdAt !== '') {
        $pdo->prepare(
            'INSERT INTO dept_production_entries (product_key, action, qty, note, deduct_lines, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $productKey,
            $action,
            $qty,
            $noteVal,
            $deductJson,
            $userId,
            $createdAt,
        ]);
    } else {
        $pdo->prepare(
            'INSERT INTO dept_production_entries (product_key, action, qty, note, deduct_lines, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $productKey,
            $action,
            $qty,
            $noteVal,
            $deductJson,
            $userId,
        ]);
    }

    return (int) $pdo->lastInsertId();
}

/** @param array<string, mixed> $row */
function dept_production_format_entry_row(array $row): array
{
    $deduct = $row['deduct_lines'] ?? null;
    if (is_string($deduct)) {
        $deduct = json_decode($deduct, true);
    }

    return [
        'id' => (int) $row['id'],
        'product_key' => (string) $row['product_key'],
        'action' => (string) $row['action'],
        'qty' => (float) $row['qty'],
        'note' => $row['note'],
        'deduct_lines' => is_array($deduct) ? $deduct : [],
        'created_by_name' => $row['created_by_name'],
        'created_at' => (string) $row['created_at'],
    ];
}

/** @return list<array<string, mixed>> */
function dept_production_recent_entries(PDO $pdo, string $productKey, int $limit = 25): array
{
    if (!dept_production_tables_ready($pdo)) {
        return [];
    }
    $limit = max(1, min(100, $limit));
    $st = $pdo->prepare(
        'SELECT dpe.*, u.name AS created_by_name
         FROM dept_production_entries dpe
         LEFT JOIN users u ON u.id = dpe.created_by
         WHERE dpe.product_key = ?
         ORDER BY dpe.created_at DESC, dpe.id DESC
         LIMIT ' . $limit
    );
    $st->execute([$productKey]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map('dept_production_format_entry_row', $rows);
}

/**
 * Paginated production log (optional action + calendar-day filter).
 *
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
 */
function dept_production_list_entries(
    PDO $pdo,
    string $productKey,
    ?string $action = null,
    ?string $date = null,
    int $page = 1,
    int $perPage = 10
): array {
    $page = max(1, $page);
    $perPage = max(1, min(50, $perPage));
    if (!dept_production_tables_ready($pdo)) {
        return [
            'rows' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => 1,
        ];
    }

    $where = ['dpe.product_key = ?'];
    $params = [$productKey];
    if ($action !== null && trim($action) !== '') {
        $where[] = 'dpe.action = ?';
        $params[] = trim($action);
    }
    if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $where[] = 'DATE(dpe.created_at) = ?';
        $params[] = $date;
    }
    $whereSql = implode(' AND ', $where);

    $countSt = $pdo->prepare(
        "SELECT COUNT(*) FROM dept_production_entries dpe WHERE {$whereSql}"
    );
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $st = $pdo->prepare(
        "SELECT dpe.*, u.name AS created_by_name
         FROM dept_production_entries dpe
         LEFT JOIN users u ON u.id = dpe.created_by
         WHERE {$whereSql}
         ORDER BY dpe.created_at DESC, dpe.id DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return [
        'rows' => array_map('dept_production_format_entry_row', $rows),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * One row per calendar day: total qty for an action (e.g. pouch creations).
 *
 * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
 */
function dept_production_daily_totals(
    PDO $pdo,
    string $productKey,
    string $action,
    ?string $date = null,
    int $page = 1,
    int $perPage = 10
): array {
    $page = max(1, $page);
    $perPage = max(1, min(50, $perPage));
    $action = trim($action);
    if ($action === '' || !dept_production_tables_ready($pdo)) {
        return [
            'rows' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => 1,
        ];
    }

    $where = ['dpe.product_key = ?', 'dpe.action = ?'];
    $params = [$productKey, $action];
    if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $where[] = 'DATE(dpe.created_at) = ?';
        $params[] = $date;
    }
    $whereSql = implode(' AND ', $where);

    $countSt = $pdo->prepare(
        "SELECT COUNT(*) FROM (
            SELECT DATE(dpe.created_at) AS production_date
            FROM dept_production_entries dpe
            WHERE {$whereSql}
            GROUP BY DATE(dpe.created_at)
         ) daily"
    );
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $st = $pdo->prepare(
        "SELECT DATE(dpe.created_at) AS production_date,
                COALESCE(SUM(dpe.qty), 0) AS qty_total,
                COUNT(*) AS run_count
         FROM dept_production_entries dpe
         WHERE {$whereSql}
         GROUP BY DATE(dpe.created_at)
         ORDER BY production_date DESC
         LIMIT {$perPage} OFFSET {$offset}"
    );
    $st->execute($params);
    $raw = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $rows = array_map(static function (array $r): array {
        return [
            'production_date' => (string) $r['production_date'],
            'qty_total' => (float) $r['qty_total'],
            'run_count' => (int) $r['run_count'],
        ];
    }, $raw);

    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
    ];
}

/**
 * Log-only catch-up: record qty made on a calendar day (no stock / packaging changes).
 *
 * @return array{id: int, product_key: string, action: string, qty: float, production_date: string}
 */
function dept_production_record_daily(
    PDO $pdo,
    string $productKey,
    float $qty,
    string $productionDate,
    int $userId,
    ?string $note = null
): array {
    if (!dept_production_tables_ready($pdo)) {
        throw new RuntimeException('Production tables missing. Run migration_phase26_dept_wip.sql.');
    }
    $productKey = trim($productKey);
    $actions = [
        'pt' => 'pouch',
        'brc' => 'produce_brc',
        'jar_cacao' => 'produce_jar',
    ];
    if (!isset($actions[$productKey])) {
        throw new RuntimeException('Manual daily record is only for Paragis, Black Rice, or Cacao.');
    }
    if ($qty <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $productionDate)) {
        throw new RuntimeException('Invalid production date.');
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $productionDate);
    if ($dt === false || $dt->format('Y-m-d') !== $productionDate) {
        throw new RuntimeException('Invalid production date.');
    }

    $action = $actions[$productKey];
    $userNote = $note !== null && trim($note) !== '' ? trim($note) : null;
    $fullNote = $userNote !== null
        ? 'Manual daily record: ' . $userNote
        : 'Manual daily record';
    $createdAt = $productionDate . ' 12:00:00';

    $id = dept_production_log_entry(
        $pdo,
        $productKey,
        $action,
        $qty,
        $userId,
        $fullNote,
        [],
        $createdAt
    );

    return [
        'id' => $id,
        'product_key' => $productKey,
        'action' => $action,
        'qty' => $qty,
        'production_date' => $productionDate,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function dept_production_cost_only_lines(PDO $pdo, string $productKey, string $action, float $qty): array
{
    $formulaLines = dept_production_load_formula_lines($pdo, $productKey);
    $costOnly = [];

    foreach ($formulaLines as $line) {
        if (dept_production_stock_deducts($line)) {
            continue;
        }
        if ($productKey === 'pt') {
            $stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);
            if ($stage !== null && !dept_production_pt_action_matches_stage($action, $stage)) {
                continue;
            }
        } elseif ($action !== 'produce') {
            continue;
        }

        $perSellable = (float) $line['qty_per_unit'];
        if ($productKey === 'pt' && $action === 'make_tea_bags') {
            $consumeQty = $qty;
            $consumeUnit = $line['pool_yield_unit'] ?? 'tea_bag';
        } elseif ($productKey === 'pt' && $action === 'use_raw_bags') {
            $consumeQty = round($qty * dept_pt_tea_bags_per_bag(), 6);
            $consumeUnit = $line['pool_yield_unit'] ?? 'tea_bag';
        } elseif ($productKey === 'pt' && $action === 'pouch') {
            $consumeQty = round($perSellable * $qty, 6);
            $consumeUnit = $line['pool_yield_unit'] ?? 'tea_bag';
        } else {
            $consumeQty = round($perSellable * $qty, 6);
            $consumeUnit = $line['pool_yield_unit'] ?? (string) $line['unit'];
        }

        $yield = (float) ($line['pool_yield_qty'] ?? 0);
        $rollPrice = (float) ($line['current_price'] ?? 0);
        $estCost = $yield > 0 ? round(($consumeQty / $yield) * $rollPrice, 4) : 0.0;

        $costOnly[] = [
            'stock_id' => (int) $line['stock_id'],
            'stock_name' => $line['stock_name'],
            'kind' => 'cost_only',
            'unit' => $consumeUnit,
            'qty' => $consumeQty,
            'estimated_cost' => $estCost,
            'pool_yield_qty' => $line['pool_yield_qty'],
            'pool_yield_unit' => $line['pool_yield_unit'],
        ];
    }

    return $costOnly;
}

/** @return array<string, mixed> */
function dept_production_preview_lines(PDO $pdo, string $productKey, string $action, float $qty): array
{
    $deductLines = dept_production_formula_deduct_lines($pdo, $productKey, $action, $qty);
    $costOnlyLines = dept_production_cost_only_lines($pdo, $productKey, $action, $qty);

    $preview = [
        'product_key' => $productKey,
        'action' => $action,
        'qty' => $qty,
        'lines' => $deductLines,
        'deduct_lines' => $deductLines,
        'cost_only_lines' => $costOnlyLines,
        'used_formula' => dept_production_load_formula_lines($pdo, $productKey) !== [],
    ];
    if ($productKey === 'pt' && $action === 'use_raw_bags') {
        $derivedTeaBags = round($qty * dept_pt_tea_bags_per_bag(), 2);
        $preview['derived_tea_bags'] = $derivedTeaBags;
        $preview['packable_pouches'] = (int) floor($derivedTeaBags / PT_TEA_BAGS_PER_POUCH);
    }

    return $preview;
}

/** @return array<string, mixed> */
function dept_pt_use_raw_bags(PDO $pdo, int $userId, float $bags, ?string $note = null): array
{
    if (!dept_production_tables_ready($pdo)) {
        throw new RuntimeException('Production tables missing. Run migration_phase26_dept_wip.sql.');
    }
    if ($bags <= 0) {
        throw new RuntimeException('Bag count must be > 0.');
    }
    $bags = round($bags, 6);
    $onHand = dept_raw_bags_on_hand($pdo, 'pt');
    if ($bags > $onHand + 0.0001) {
        throw new RuntimeException(
            'Not enough raw bags on hand (' . round($onHand, 2) . ' available, need ' . round($bags, 2) . ').'
        );
    }
    $teaBags = round($bags * dept_pt_tea_bags_per_bag(), 2);
    $label = $note !== null && trim($note) !== '' ? 'use_raw_bags: ' . trim($note) : 'use_raw_bags';
    $deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'use_raw_bags', $bags);
    $applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);
    dept_wip_adjust($pdo, $userId, 'pt', $teaBags);
    $entryId = dept_production_log_entry($pdo, 'pt', 'use_raw_bags', $bags, $userId, $note, $applied);

    return [
        'entry_id' => $entryId,
        'bags_used' => $bags,
        'tea_bags_added' => $teaBags,
        'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'pt'),
        'deduct_lines' => $applied,
    ];
}

/** @return array<string, mixed> */
function dept_pt_make_tea_bags(PDO $pdo, int $userId, float $teaBags, ?string $note = null): array
{
    if (!dept_production_tables_ready($pdo)) {
        throw new RuntimeException('Production tables missing. Run migration_phase26_dept_wip.sql.');
    }
    if ($teaBags <= 0 || floor($teaBags) != $teaBags) {
        throw new RuntimeException('Tea bag count must be a positive whole number.');
    }
    $teaBags = (float) (int) $teaBags;
    $label = $note !== null && trim($note) !== '' ? 'make_tea_bags: ' . trim($note) : 'make_tea_bags';
    $deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'make_tea_bags', $teaBags);
    $applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);
    dept_wip_adjust($pdo, $userId, 'pt', $teaBags);
    $entryId = dept_production_log_entry($pdo, 'pt', 'make_tea_bags', $teaBags, $userId, $note, $applied);

    return [
        'entry_id' => $entryId,
        'tea_bags_added' => $teaBags,
        'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'pt'),
        'deduct_lines' => $applied,
    ];
}

/** @return array<string, mixed> */
function dept_pt_pouch(PDO $pdo, int $userId, float $pouches, ?string $note = null): array
{
    if ($pouches <= 0 || floor($pouches) != $pouches) {
        throw new RuntimeException('Pouch count must be a positive whole number.');
    }
    $pouches = (float) (int) $pouches;
    $teaNeeded = $pouches * PT_TEA_BAGS_PER_POUCH;
    $wip = dept_wip_on_hand($pdo, 'pt');
    if ($teaNeeded > $wip + 0.0001) {
        throw new RuntimeException(
            'Not enough tea bags in WIP (' . round($wip, 0) . ' available, need ' . round($teaNeeded, 0) . ').'
        );
    }
    $label = $note !== null && trim($note) !== '' ? 'pouch: ' . trim($note) : 'pouch';
    $deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'pouch', $pouches);
    $applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);
    dept_wip_adjust($pdo, $userId, 'pt', -$teaNeeded);
    $row = dept_inventory_ensure_row($pdo, $userId, 'pt');
    $rowId = (int) $row['id'];
    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, source_note = COALESCE(?, source_note), last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$pouches, $pouches, 'Paragis Tea', $note, $rowId]);
    $applied[] = [
        'kind' => 'wip',
        'stock_name' => 'tea_bag',
        'qty' => $teaNeeded,
        'unit' => 'tea_bag',
    ];
    $entryId = dept_production_log_entry($pdo, 'pt', 'pouch', $pouches, $userId, $note, $applied);
    $finished = dept_inventory_fetch_row($pdo, 'pt');

    return [
        'entry_id' => $entryId,
        'pouches_added' => $pouches,
        'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),
        'finished_on_hand' => $finished !== null ? (float) $finished['current_qty'] : $pouches,
        'deduct_lines' => $applied,
    ];
}

/** @return array<string, mixed> */
function dept_wip_waste_tea_bags(PDO $pdo, int $userId, float $teaBags, ?string $reason = null): array
{
    if ($teaBags <= 0 || floor($teaBags) != $teaBags) {
        throw new RuntimeException('Tea bag count must be a positive whole number.');
    }
    $teaBags = (float) (int) $teaBags;
    dept_wip_adjust($pdo, $userId, 'pt', -$teaBags);
    $note = $reason !== null && trim($reason) !== '' ? trim($reason) : 'WIP waste';
    $entryId = dept_production_log_entry($pdo, 'pt', 'wip_waste', $teaBags, $userId, $note, []);

    return [
        'entry_id' => $entryId,
        'tea_bags_wasted' => $teaBags,
        'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),
    ];
}

/** @return array<string, mixed> */
function dept_brc_produce_pouches(PDO $pdo, int $userId, float $pouches, ?string $note = null): array
{
    if ($pouches <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }
    $label = $note !== null && trim($note) !== '' ? 'produce: ' . trim($note) : 'produce';
    $deductPlan = dept_production_formula_deduct_lines($pdo, 'brc', 'produce', $pouches);
    $applied = dept_production_apply_deduct_lines($pdo, $userId, 'brc', $deductPlan, $label);
    $row = dept_inventory_ensure_row($pdo, $userId, 'brc');
    $rowId = (int) $row['id'];
    $def = dept_product_by_key('brc');
    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, source_note = COALESCE(?, source_note), last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$pouches, $pouches, $def['label'] ?? 'Black Rice Coffee', $note, $rowId]);
    $entryId = dept_production_log_entry($pdo, 'brc', 'produce_brc', $pouches, $userId, $note, $applied);
    $finished = dept_inventory_fetch_row($pdo, 'brc');

    return [
        'entry_id' => $entryId,
        'sell_units_added' => $pouches,
        'finished_on_hand' => $finished !== null ? (float) $finished['current_qty'] : $pouches,
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'brc'),
        'deduct_lines' => $applied,
    ];
}

/** @return array<string, mixed>|null */
function dept_raw_detail_enriched(PDO $pdo, string $slug): ?array
{
    $snap = dept_raw_material_snapshot($pdo, $slug);
    if ($snap === null) {
        return null;
    }
    $productKey = (string) $snap['product_key'];
    $snap['wip_tea_bags'] = $productKey === 'pt' ? dept_wip_on_hand($pdo, 'pt') : null;
    if ($productKey === 'pt') {
        require_once __DIR__ . '/dept_pt_settings.php';
        $bom = dept_pt_bom_snapshot($pdo);
        $snap['tea_bags_per_pouch'] = $bom['tea_bags_per_pouch'];
        // Floor workflow skips WIP; keep historical wip_tea_bags for reference only.
        $snap['supports_wip'] = false;
        $snap['bag_pool_workflow'] = true;
        $snap['bag_pool_deduct_enabled'] = !empty($bom['bag_pool_deduct']);
    } elseif ($productKey === 'brc') {
        require_once __DIR__ . '/dept_brc_settings.php';
        $bom = dept_brc_bom_snapshot($pdo);
        $snap['tea_bags_per_pouch'] = null;
        $snap['supports_wip'] = false;
        $snap['bag_pool_workflow'] = true;
        $snap['sell_units_per_bag'] = $bom['pouches_per_bag'];
        $snap['bag_pool_deduct_enabled'] = !empty($bom['bag_pool_deduct']);
    } elseif ($productKey === 'jar_cacao') {
        require_once __DIR__ . '/dept_jar_cacao_settings.php';
        $bom = dept_jar_cacao_bom_snapshot($pdo);
        $snap['tea_bags_per_pouch'] = null;
        $snap['supports_wip'] = false;
        $snap['bag_pool_workflow'] = true;
        $snap['sell_units_per_bag'] = $bom['jars_per_bag'];
        $snap['bag_pool_deduct_enabled'] = !empty($bom['bag_pool_deduct']);
    } else {
        $snap['tea_bags_per_pouch'] = null;
        $snap['supports_wip'] = false;
        $snap['bag_pool_workflow'] = false;
        $snap['bag_pool_deduct_enabled'] = false;
    }
    $snap['production_entries'] = dept_production_recent_entries($pdo, $productKey, 20);
    $snap['produce_action'] = match ($productKey) {
        'pt', 'brc', 'jar_cacao' => null,
        'honey_comb' => 'produce_comb',
        default => null,
    };

    return $snap;
}

/** @return array<string, mixed> */
function dept_jar_produce(PDO $pdo, int $userId, float $jars, ?string $note = null): array
{
    if ($jars <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }
    $label = $note !== null && trim($note) !== '' ? 'produce: ' . trim($note) : 'produce';
    $deductPlan = dept_production_formula_deduct_lines($pdo, 'jar_cacao', 'produce', $jars);
    $applied = dept_production_apply_deduct_lines($pdo, $userId, 'jar_cacao', $deductPlan, $label);
    $row = dept_inventory_ensure_row($pdo, $userId, 'jar_cacao');
    $rowId = (int) $row['id'];
    $def = dept_product_by_key('jar_cacao');
    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, source_note = COALESCE(?, source_note), last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$jars, $jars, $def['label'] ?? 'Jar Cacao', $note, $rowId]);
    $entryId = dept_production_log_entry($pdo, 'jar_cacao', 'produce_jar', $jars, $userId, $note, $applied);
    $finished = dept_inventory_fetch_row($pdo, 'jar_cacao');

    return [
        'entry_id' => $entryId,
        'sell_units_added' => $jars,
        'finished_on_hand' => $finished !== null ? (float) $finished['current_qty'] : $jars,
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'jar_cacao'),
        'deduct_lines' => $applied,
    ];
}

/** @return array<string, mixed> */
function dept_honey_comb_produce(PDO $pdo, int $userId, float $jars, ?string $note = null): array
{
    if ($jars <= 0) {
        throw new RuntimeException('Quantity must be > 0.');
    }
    $label = $note !== null && trim($note) !== '' ? 'produce: ' . trim($note) : 'produce';
    $deductPlan = dept_production_formula_deduct_lines($pdo, 'honey_comb', 'produce', $jars);
    $applied = dept_production_apply_deduct_lines($pdo, $userId, 'honey_comb', $deductPlan, $label);
    $row = dept_inventory_ensure_row($pdo, $userId, 'honey_comb');
    $rowId = (int) $row['id'];
    $def = dept_product_by_key('honey_comb');
    $pdo->prepare(
        'UPDATE dept_inventory_rows
         SET current_qty = current_qty + ?, initial_qty = initial_qty + ?,
             product_label = ?, source_note = COALESCE(?, source_note), last_stock_in_at = NOW()
         WHERE id = ?'
    )->execute([$jars, $jars, $def['label'] ?? 'Honey Comb', $note, $rowId]);
    $entryId = dept_production_log_entry($pdo, 'honey_comb', 'produce_comb', $jars, $userId, $note, $applied);
    $finished = dept_inventory_fetch_row($pdo, 'honey_comb');

    return [
        'entry_id' => $entryId,
        'sell_units_added' => $jars,
        'finished_on_hand' => $finished !== null ? (float) $finished['current_qty'] : $jars,
        'bags_on_hand' => dept_raw_bags_on_hand($pdo, 'honey_comb'),
        'deduct_lines' => $applied,
    ];
}
