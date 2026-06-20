<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/material_inventory.php';
require_once dirname(__DIR__) . '/api/v1/_costing_helpers.php';

$pdo = ims_pdo();
$st = $pdo->query("SELECT id, on_hand, current_price FROM stocks WHERE category = 'packaging'");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $stockId = (int) $row['id'];
    $derived = material_packaging_pool_unit_cost_from_lots($pdo, $stockId, (float) $row['on_hand']);
    if ($derived === null) {
        continue;
    }
    $new = round($derived, 2);
    $old = round((float) $row['current_price'], 2);
    if ($new !== $old) {
        echo "stock {$stockId}: {$old} -> {$new}\n";
        api_costing_apply_price_change($pdo, $stockId, $old, $new, 1);
    }
}

echo "done\n";
