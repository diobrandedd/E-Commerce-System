<?php

declare(strict_types=1);

/**
 * Shared helpers for product costing operations.
 * Extracted so other endpoints (e.g. `stocks.php`) can reuse the
 * price-change → product cost recalculation logic without triggering
 * the costing.php dispatcher.
 */

require_once __DIR__ . '/_bootstrap.php';

if (!function_exists('api_costing_formula_current_unit_sql')) {
    /** SQL expression: unit price for a formula line (custom lines use frozen price_at_creation). */
    function api_costing_formula_current_unit_sql(): string
    {
        return 'CASE
            WHEN f.stock_id IS NULL THEN f.price_at_creation
            WHEN s.production_deduct = 0 AND COALESCE(s.pool_yield_qty, 0) > 0 THEN s.current_price / s.pool_yield_qty
            ELSE s.current_price
        END';
    }
}

if (!function_exists('api_costing_formula_current_unit_sql_pf')) {
    function api_costing_formula_current_unit_sql_pf(): string
    {
        return 'CASE
            WHEN pf.stock_id IS NULL THEN pf.price_at_creation
            WHEN s.production_deduct = 0 AND COALESCE(s.pool_yield_qty, 0) > 0 THEN s.current_price / s.pool_yield_qty
            ELSE s.current_price
        END';
    }
}

if (!function_exists('api_costing_formula_baseline_unit_sql_pf')) {
    function api_costing_formula_baseline_unit_sql_pf(): string
    {
        return 'CASE
            WHEN pf.stock_id IS NULL THEN pf.price_at_creation
            WHEN s.production_deduct = 0 AND COALESCE(s.pool_yield_qty, 0) > 0 THEN pf.price_at_creation / s.pool_yield_qty
            ELSE pf.price_at_creation
        END';
    }
}

if (!function_exists('api_costing_formula_row_current_unit')) {
    /**
     * @param array{stock_id: ?int, price_at_creation: float|string, current_price?: float|string, production_deduct?: int|string, pool_yield_qty?: float|string|null} $row
     */
    function api_costing_formula_row_current_unit(array $row): float
    {
        if ($row['stock_id'] === null || $row['stock_id'] === '') {
            return (float) $row['price_at_creation'];
        }

        $price = (float) ($row['current_price'] ?? $row['price_at_creation']);
        if ((int) ($row['production_deduct'] ?? 1) === 0) {
            $yield = (float) ($row['pool_yield_qty'] ?? 0);
            if ($yield > 0) {
                return round($price / $yield, 8);
            }
        }

        return $price;
    }
}

if (!function_exists('api_costing_can_view_all')) {
    function api_costing_can_view_all(string $role): bool
    {
        return $role === 'executive';
    }
}

if (!function_exists('api_costing_can_manage_products')) {
    function api_costing_can_manage_products(string $role): bool
    {
        return $role === 'executive';
    }
}

if (!function_exists('api_costing_can_edit_stocks')) {
    function api_costing_can_edit_stocks(string $role): bool
    {
        return $role === 'executive';
    }
}

if (!function_exists('api_costing_show_pricing')) {
    function api_costing_show_pricing(string $role): bool
    {
        return $role === 'executive';
    }
}

if (!function_exists('api_costing_is_honey_raw_stock')) {
    function api_costing_is_honey_raw_stock(string $stockName): bool
    {
        return (bool) preg_match('/^Raw Honey — (Light|Amber|Dark)$/i', trim($stockName));
    }
}

if (!function_exists('api_costing_resolve_formula_qty')) {
    /**
     * For honey raw lines on sized products, use exact bottle liters (gallon = full US gal constant).
     *
     * @param array{size: ?string} $product
     */
    function api_costing_resolve_formula_qty(array $product, string $stockName, float $qty): float
    {
        $deptKey = isset($product['dept_product_key']) ? trim((string) $product['dept_product_key']) : '';
        if ($deptKey !== '') {
            if (!function_exists('dept_product_formula_qty_for_stock')) {
                require_once dirname(__DIR__, 2) . '/includes/dept_products.php';
            }
            $deptQty = dept_product_formula_qty_for_stock($deptKey, $stockName);
            if ($deptQty !== null) {
                return round($deptQty, 8);
            }
        }

        if (!function_exists('catalog_honey_packaging_formula_qty')) {
            require_once dirname(__DIR__, 2) . '/includes/catalog_bootstrap.php';
        }
        $size = isset($product['size']) ? (string) $product['size'] : null;
        $honeyPackQty = catalog_honey_packaging_formula_qty($stockName, $size);
        if ($honeyPackQty !== null) {
            return $honeyPackQty;
        }

        if (!api_costing_is_honey_raw_stock($stockName)) {
            return $qty;
        }
        if ($size === null || $size === '') {
            return $qty;
        }
        if (!function_exists('honey_normalize_bottle_size')) {
            require_once dirname(__DIR__, 2) . '/includes/honey_volume.php';
        }
        try {
            $norm = honey_normalize_bottle_size((string) $size);

            return honey_bottle_size_ml($norm) / 1000.0;
        } catch (Throwable) {
            return $qty;
        }
    }
}

if (!function_exists('api_costing_stock_price_direction')) {
    /**
     * @return 'up'|'down'|'unchanged'|'unknown'
     */
    function api_costing_stock_price_direction(float $currentPrice, ?float $previousPrice): string
    {
        if ($previousPrice === null) {
            return 'unknown';
        }
        $diff = round($currentPrice - $previousPrice, 2);
        if (abs($diff) < 0.01) {
            return 'unchanged';
        }

        return $diff > 0 ? 'up' : 'down';
    }
}

if (!function_exists('api_costing_enrich_stock_row')) {
    /** @param array<string, mixed> $row */
    function api_costing_enrich_stock_row(array $row): array
    {
        $name = (string) ($row['name'] ?? '');
        $current = round((float) ($row['current_price'] ?? 0), 2);
        $previousRaw = $row['previous_price'] ?? null;
        $previous = $previousRaw !== null && $previousRaw !== '' ? round((float) $previousRaw, 2) : null;

        $supplierId = isset($row['supplier_id']) && $row['supplier_id'] !== null && $row['supplier_id'] !== ''
            ? (int) $row['supplier_id']
            : null;

        return array_merge($row, [
            'supplier_id' => $supplierId,
            'is_honey_raw' => api_costing_is_honey_raw_stock($name) && $supplierId !== null && $supplierId > 0,
            'previous_price' => $previous,
            'price_changed_at' => $row['price_changed_at'] ?? null,
            'price_direction' => api_costing_stock_price_direction($current, $previous),
        ]);
    }
}

if (!function_exists('api_costing_fetch_product_scope')) {
    function api_costing_fetch_product_scope(PDO $pdo, int $productId, bool $canViewAll, int $userDeptId): array
    {
        $unitExpr = api_costing_formula_current_unit_sql();
        $sql = "SELECT p.id, p.dept_id, p.dept_product_key, p.name, p.size, p.variant, p.selling_price, p.baseline_cost,
                       COALESCE(SUM(f.qty_per_unit * ({$unitExpr})), 0) AS current_cost,
                       d.name AS dept_name
                FROM products p
                INNER JOIN departments d ON d.id = p.dept_id
                LEFT JOIN product_formula f ON f.product_id = p.id
                LEFT JOIN stocks s ON s.id = f.stock_id
                WHERE p.id = ?";
        $args = [$productId];
        if (!$canViewAll) {
            $sql .= ' AND p.dept_id = ?';
            $args[] = $userDeptId;
        }
        $sql .= ' GROUP BY p.id, p.dept_id, p.dept_product_key, p.name, p.size, p.variant, p.selling_price, p.baseline_cost, d.name LIMIT 1';
        $st = $pdo->prepare($sql);
        $st->execute($args);
        $row = $st->fetch();
        if (!$row) {
            throw new RuntimeException('Product not found or out of your scope.');
        }
        return [
            'id' => (int) $row['id'],
            'dept_id' => (int) $row['dept_id'],
            'dept_product_key' => $row['dept_product_key'] !== null ? (string) $row['dept_product_key'] : null,
            'dept_name' => (string) $row['dept_name'],
            'name' => (string) $row['name'],
            'size' => $row['size'] !== null ? (string) $row['size'] : null,
            'variant' => $row['variant'] !== null ? (string) $row['variant'] : null,
            'selling_price' => (float) $row['selling_price'],
            'baseline_cost' => (float) $row['baseline_cost'],
            'current_cost' => (float) $row['current_cost'],
        ];
    }
}

if (!function_exists('api_costing_recalculate_baseline')) {
    function api_costing_recalculate_baseline(PDO $pdo, int $productId): float
    {
        $unitExpr = api_costing_formula_baseline_unit_sql_pf();
        $st = $pdo->prepare(
            "SELECT COALESCE(SUM(pf.qty_per_unit * ({$unitExpr})), 0)
             FROM product_formula pf
             LEFT JOIN stocks s ON s.id = pf.stock_id
             WHERE pf.product_id = ?"
        );
        $st->execute([$productId]);
        $baseline = (float) $st->fetchColumn();
        $upd = $pdo->prepare('UPDATE products SET baseline_cost = ? WHERE id = ?');
        $upd->execute([$baseline, $productId]);
        return $baseline;
    }
}

if (!function_exists('api_costing_apply_price_change')) {
    function api_costing_apply_price_change(PDO $pdo, int $stockId, float $oldPrice, float $newPrice, int $userId): void
    {
        $stAffected = $pdo->prepare('SELECT DISTINCT product_id FROM product_formula WHERE stock_id = ?');
        $stAffected->execute([$stockId]);
        $affectedIds = array_map(static fn(array $r): int => (int) $r['product_id'], $stAffected->fetchAll());

        $oldCostMap = [];
        if ($affectedIds !== []) {
            $in = implode(',', array_fill(0, count($affectedIds), '?'));
            $unitExpr = api_costing_formula_current_unit_sql_pf();
            $stOldCosts = $pdo->prepare(
                "SELECT pf.product_id,
                        COALESCE(SUM(
                            pf.qty_per_unit * CASE WHEN pf.stock_id IS NULL THEN pf.price_at_creation ELSE ({$unitExpr}) END
                        ), 0) AS current_cost
                 FROM product_formula pf
                 LEFT JOIN stocks s ON s.id = pf.stock_id
                 WHERE pf.product_id IN ($in)
                 GROUP BY pf.product_id"
            );
            $stOldCosts->execute($affectedIds);
            foreach ($stOldCosts->fetchAll() as $r) {
                $oldCostMap[(int) $r['product_id']] = round((float) $r['current_cost'], 2);
            }
        }

        $upd = $pdo->prepare('UPDATE stocks SET current_price = ? WHERE id = ?');
        $upd->execute([$newPrice, $stockId]);

        $insHist = $pdo->prepare(
            'INSERT INTO stock_price_history (stock_id, old_price, new_price, changed_by) VALUES (?, ?, ?, ?)'
        );
        $insHist->execute([$stockId, $oldPrice, $newPrice, $userId]);

        if ($affectedIds === []) {
            return;
        }

        $in = implode(',', array_fill(0, count($affectedIds), '?'));
        $unitExpr = api_costing_formula_current_unit_sql_pf();
        $stNewCosts = $pdo->prepare(
            "SELECT pf.product_id,
                    COALESCE(SUM(
                        pf.qty_per_unit * CASE WHEN pf.stock_id IS NULL THEN pf.price_at_creation ELSE ({$unitExpr}) END
                    ), 0) AS current_cost
             FROM product_formula pf
             LEFT JOIN stocks s ON s.id = pf.stock_id
             WHERE pf.product_id IN ($in)
             GROUP BY pf.product_id"
        );
        $stNewCosts->execute($affectedIds);
        $newCostMap = [];
        foreach ($stNewCosts->fetchAll() as $r) {
            $newCostMap[(int) $r['product_id']] = round((float) $r['current_cost'], 2);
        }

        $stLast = $pdo->prepare('SELECT new_cost FROM product_cost_history WHERE product_id = ? ORDER BY id DESC LIMIT 1');
        $insCost = $pdo->prepare(
            'INSERT INTO product_cost_history (product_id, old_cost, new_cost, triggered_by_stock_id)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($affectedIds as $pid) {
            $stLast->execute([$pid]);
            $last = $stLast->fetchColumn();
            $compareOld = $last !== false ? round((float) $last, 2) : ($oldCostMap[$pid] ?? 0.00);
            $newCost = $newCostMap[$pid] ?? 0.00;
            if ($compareOld !== $newCost) {
                $insCost->execute([$pid, $compareOld, $newCost, $stockId]);
            }
        }
    }
}

if (!function_exists('api_costing_build_product_detail')) {
    function api_costing_build_product_detail(PDO $pdo, int $productId, bool $canViewAll, int $userDeptId, bool $showPricing): array
    {
        $product = api_costing_fetch_product_scope($pdo, $productId, $canViewAll, $userDeptId);

        $stFormula = $pdo->prepare(
            'SELECT pf.id, pf.stock_id, pf.line_label, pf.qty_per_unit, pf.price_at_creation,
                    s.name AS stock_name, s.unit, s.current_price,
                    s.production_deduct, s.pool_yield_qty, s.pool_yield_unit
             FROM product_formula pf
             LEFT JOIN stocks s ON s.id = pf.stock_id
             WHERE pf.product_id = ?
             ORDER BY pf.id ASC'
        );
        $stFormula->execute([$productId]);
        $rows = [];
        $stockIds = [];
        foreach ($stFormula->fetchAll() as $r) {
            $qty = (float) $r['qty_per_unit'];
            $isCustom = $r['stock_id'] === null || $r['stock_id'] === '';
            $baselineRow = $r;
            $baselineRow['current_price'] = $r['price_at_creation'];
            $baselineUnit = api_costing_formula_row_current_unit($baselineRow);
            $currentUnit = api_costing_formula_row_current_unit($r);
            $isPool = !$isCustom && (int) ($r['production_deduct'] ?? 1) === 0;
            $poolYield = $isPool ? (float) ($r['pool_yield_qty'] ?? 0) : null;
            $rows[] = [
                'id' => (int) $r['id'],
                'stock_id' => $isCustom ? null : (int) $r['stock_id'],
                'is_custom' => $isCustom,
                'is_pool_packaging' => $isPool,
                'pool_yield_qty' => $poolYield,
                'pool_yield_unit' => $isPool ? ($r['pool_yield_unit'] ?? null) : null,
                'stock_name' => $isCustom ? (string) ($r['line_label'] ?? 'Custom') : (string) $r['stock_name'],
                'unit' => $isCustom ? 'flat' : (string) $r['unit'],
                'qty_per_unit' => $qty,
                'price_at_creation' => $baselineUnit,
                'current_price' => $currentUnit,
                'baseline_line_cost' => round($qty * $baselineUnit, 4),
                'current_line_cost' => round($qty * $currentUnit, 4),
                'delta' => round(($qty * $currentUnit) - ($qty * $baselineUnit), 4),
            ];
            if (!$isCustom) {
                $stockIds[] = (int) $r['stock_id'];
            }
        }
        $stockIds = array_values(array_unique($stockIds));

        $costHistory = [];
        $stockHistory = [];

        if ($showPricing) {
            $stCost = $pdo->prepare(
                'SELECT old_cost, new_cost, recalculated_at, triggered_by_stock_id
                 FROM product_cost_history
                 WHERE product_id = ?
                 ORDER BY id DESC
                 LIMIT 100'
            );
            $stCost->execute([$productId]);
            $costHistory = $stCost->fetchAll();

            if ($stockIds !== []) {
                $in = implode(',', array_fill(0, count($stockIds), '?'));
                $stStockHist = $pdo->prepare(
                    "SELECT sph.stock_id, sph.old_price, sph.new_price, sph.changed_at, s.name AS stock_name
                     FROM stock_price_history sph
                     INNER JOIN stocks s ON s.id = sph.stock_id
                     WHERE sph.stock_id IN ($in)
                     ORDER BY sph.id DESC
                     LIMIT 200"
                );
                $stStockHist->execute($stockIds);
                $stockHistory = $stStockHist->fetchAll();
            }
        }

        return [
            'product' => $product,
            'formula_rows' => $rows,
            'cost_history' => $costHistory,
            'stock_history' => $stockHistory,
            'show_pricing' => $showPricing,
        ];
    }
}
