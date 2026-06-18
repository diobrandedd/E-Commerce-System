<?php

declare(strict_types=1);

/**
 * One-shot: push Supplier Master + Packaging lot WAC into product COGS baselines.
 *
 * After this, day-to-day you only update:
 *   - Supplier Master (honey ₱/gal, dept ₱/bag)
 *   - Packaging Materials stock-in lots (WAC per roll/pcs)
 *   - Generic Bubble wrap roll (parcel packaging picks it up automatically)
 *
 * Run: php tools/sync_all_costing_from_prices.php
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/material_inventory.php';
require_once dirname(__DIR__) . '/includes/honey_pricing.php';
require_once dirname(__DIR__) . '/includes/dept_pricing.php';
require_once dirname(__DIR__) . '/includes/suppliers.php';
require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';

$pdo = ims_pdo();
$userId = 1;

echo "=== Sync costing from current prices ===\n";

// 1) Supplier Master → raw honey / dept leaf stocks.current_price
$suppliers = $pdo->query('SELECT id, category FROM suppliers WHERE is_active = 1')->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($suppliers as $sup) {
    $sid = (int) $sup['id'];
    $cat = (string) $sup['category'];
    if ($cat === 'honey') {
        $updated = honey_pricing_upsert_supplier_costing_stocks($pdo, $sid, $userId);
        if ($updated !== []) {
            echo "Honey supplier {$sid}: " . implode(', ', $updated) . "\n";
        }
    } elseif (in_array($cat, ['paragis', 'brc', 'cacao'], true)) {
        $updated = dept_pricing_upsert_supplier_costing_stocks($pdo, $sid, $userId);
        if ($updated !== []) {
            echo "Dept supplier {$sid}: " . implode(', ', $updated) . "\n";
        }
    }
}

// 2) Packaging lot WAC → stocks.current_price (+ product COGS via apply_price_change)
$st = $pdo->query("SELECT id, on_hand, current_price FROM stocks WHERE category = 'packaging' AND is_active = 1");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $stockId = (int) $row['id'];
    $onHand = (float) $row['on_hand'];
    $derived = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, $onHand);
    if ($derived === null) {
        continue;
    }
    $new = round($derived, 2);
    $old = round((float) $row['current_price'], 2);
    if ($new !== $old) {
        echo "Packaging stock {$stockId}: {$old} -> {$new}\n";
        api_costing_apply_price_change($pdo, $stockId, $old, $new, $userId);
    }
}

// 3) Align formula snapshots to current unit prices (baseline tracks today's prices)
$formulaSt = $pdo->query(
    'SELECT pf.id, pf.stock_id, pf.price_at_creation,
            s.current_price, s.production_deduct, s.pool_yield_qty
     FROM product_formula pf
     INNER JOIN stocks s ON s.id = pf.stock_id'
);
$updFormula = $pdo->prepare('UPDATE product_formula SET price_at_creation = ? WHERE id = ?');
$formulaUpdates = 0;
foreach ($formulaSt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $unit = api_costing_formula_row_current_unit($row);
    $old = round((float) $row['price_at_creation'], 6);
    $new = round($unit, 6);
    if (abs($old - $new) >= 0.0001) {
        $updFormula->execute([$new, (int) $row['id']]);
        $formulaUpdates++;
    }
}
echo "Formula lines synced: {$formulaUpdates}\n";

// 4) Recalculate product baseline_cost from synced formula
$productIds = $pdo->query('SELECT id FROM products')->fetchAll(PDO::FETCH_COLUMN) ?: [];
foreach ($productIds as $pid) {
    $baseline = api_costing_recalculate_baseline($pdo, (int) $pid);
    echo "Product {$pid} baseline_cost = {$baseline}\n";
}

echo "done — parcel packaging costs are live from stocks.current_price (bubble uses generic roll WAC).\n";
