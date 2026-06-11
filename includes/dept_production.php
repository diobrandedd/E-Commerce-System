<?php

declare(strict_types=1);

require_once __DIR__ . '/dept_inventory.php';
require_once __DIR__ . '/material_inventory.php';

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
                s.name AS stock_name, s.category, s.unit, s.on_hand, s.dept_id
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
        ];
    }, $rows);
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
    if ($productKey === 'pt' && $action === 'make_tea_bags') {
        $bags = round($qty * (PT_GRAMS_PER_TEA_BAG / PT_GRAMS_PER_BAG), 6);
        $lines[] = [
            'stock_id' => 0,
            'stock_name' => PT_LEAF_STOCK_NAME,
            'kind' => 'raw_bag',
            'unit' => 'bag',
            'qty' => $bags,
        ];
        $lines[] = [
            'stock_id' => 0,
            'stock_name' => PT_TEA_BAG_SACHET_STOCK_NAME,
            'kind' => 'packaging',
            'unit' => 'pcs',
            'qty' => $qty,
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
            $stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);
            if ($stage === null) {
                throw new RuntimeException(
                    'Formula stock "' . $line['stock_name'] . '" is not used at Make tea bags or Pouch. '
                    . 'Use leaf + tea-bag packaging on the formula for the fill step, and '
                    . PT_PACKAGING_STOCK_NAME . ' for the pouch step — or remove the extra line from Product Costing.'
                );
            }
            if ($stage !== $action) {
                continue;
            }
            $perPouch = $line['qty_per_unit'];
            $deductQty = $action === 'make_tea_bags'
                ? round($perPouch / PT_TEA_BAGS_PER_POUCH * $qty, 6)
                : round($perPouch * $qty, 6);
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
            $stockId = (int) $line['stock_id'];
            if ($stockId < 1) {
                throw new RuntimeException('Missing stock_id for packaging line.');
            }
            $res = dept_packaging_deduct($pdo, $stockId, (float) $line['qty'], $userId, $label);
            $applied[] = [
                'kind' => 'packaging',
                'stock_id' => $stockId,
                'stock_name' => $line['stock_name'],
                'qty' => (float) $line['qty'],
                'unit' => $line['unit'],
                'on_hand' => $res['on_hand'],
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
 */
function dept_production_log_entry(
    PDO $pdo,
    string $productKey,
    string $action,
    float $qty,
    int $userId,
    ?string $note,
    array $deductLines
): int {
    $pdo->prepare(
        'INSERT INTO dept_production_entries (product_key, action, qty, note, deduct_lines, created_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $productKey,
        $action,
        $qty,
        $note !== null && trim($note) !== '' ? trim($note) : null,
        json_encode($deductLines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $userId,
    ]);

    return (int) $pdo->lastInsertId();
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

    return array_map(static function (array $row): array {
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
    }, $rows);
}

/** @return array<string, mixed> */
function dept_production_preview_lines(PDO $pdo, string $productKey, string $action, float $qty): array
{
    $lines = dept_production_formula_deduct_lines($pdo, $productKey, $action, $qty);

    return [
        'product_key' => $productKey,
        'action' => $action,
        'qty' => $qty,
        'lines' => $lines,
        'used_formula' => dept_production_load_formula_lines($pdo, $productKey) !== [],
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
    $snap['tea_bags_per_pouch'] = $productKey === 'pt' ? PT_TEA_BAGS_PER_POUCH : null;
    $snap['production_entries'] = dept_production_recent_entries($pdo, $productKey, 20);
    $snap['supports_wip'] = $productKey === 'pt';
    $snap['produce_action'] = match ($productKey) {
        'pt' => null,
        'brc' => 'produce_brc',
        'jar_cacao' => 'produce_jar',
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
