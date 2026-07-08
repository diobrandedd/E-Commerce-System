---
title: dept_production
source: includes/dept_production.php
generated: 2026-07-08T05:14:59+02:00
type: module
function_count: 25
---


# dept_production

> Source: `includes/dept_production.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/material_inventory.php`
- `includes/catalog_bootstrap.php`

## dept_production_tables_ready {#dept_production_tables_ready}

**Tables:** `dept_wip_rows`, `dept_production_entries`

**Callers (4):**

- `includes/dept_production.php`:411 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:477 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:593 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:626 — `if (!dept_production_tables_ready($pdo)) {`

## dept_production_load_formula_lines {#dept_production_load_formula_lines}

**Tables:** `products`, `product_formula`, `stocks`

**Callers (3):**

- `includes/dept_production.php`:225 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:516 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:579 — `'used_formula' => dept_production_load_formula_lines($pdo, $productKey) !== [],`

## dept_production_stock_deducts {#dept_production_stock_deducts}

**Callers (3):**

- `includes/dept_production.php`:230 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:272 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:520 — `if (dept_production_stock_deducts($line)) {`

## dept_production_pt_action_matches_stage {#dept_production_pt_action_matches_stage}

**Callers (2):**

- `includes/dept_production.php`:241 — `if (!dept_production_pt_action_matches_stage($action, $stage)) {`
- `includes/dept_production.php`:525 — `if ($stage !== null && !dept_production_pt_action_matches_stage($action, $stage)) {`

## dept_production_pt_stage_for_stock {#dept_production_pt_stage_for_stock}

**Callers (2):**

- `includes/dept_production.php`:233 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`
- `includes/dept_production.php`:524 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`

## dept_production_line_kind {#dept_production_line_kind}

**Callers (4):**

- `includes/dept_production.php`:245 — `$lineKind = dept_production_line_kind($productKey, $line['stock_name'], $line['category']);`
- `includes/dept_production.php`:262 — `'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),`
- `includes/dept_production.php`:282 — `'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),`
- `includes/dept_production.php`:303 — `$line['kind'] = dept_production_line_kind($productKey, $line['stock_name'], (string) $stock['category']);`

## dept_production_fallback_deduct_lines {#dept_production_fallback_deduct_lines}

**Callers (1):**

- `includes/dept_production.php`:290 — `$fallback = dept_production_fallback_deduct_lines($productKey, $action, $qty);`

## dept_production_formula_deduct_lines {#dept_production_formula_deduct_lines}

**Tables:** `stocks`

**Callers (7):**

- `includes/dept_production.php`:569 — `$deductLines = dept_production_formula_deduct_lines($pdo, $productKey, $action, $qty);`
- `includes/dept_production.php`:608 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'use_raw_bags', $bags);`
- `includes/dept_production.php`:634 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'make_tea_bags', $teaBags);`
- `includes/dept_production.php`:663 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'pouch', $pouches);`
- `includes/dept_production.php`:717 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'brc', 'produce', $pouches);`
- `includes/dept_production.php`:770 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'jar_cacao', 'produce', $jars);`
- `includes/dept_production.php`:800 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'honey_comb', 'produce', $jars);`

## dept_packaging_deduct {#dept_packaging_deduct}

**Tables:** `stock_entries`, `stocks`

**Callers (4):**

- `includes/dept_production.php`:375 — `$res = dept_packaging_deduct($pdo, $stockId, (float) $line['qty'], $userId, $label);`
- `includes/honey_inventory.php`:468 — `$res = dept_packaging_deduct($pdo, $stockId, $qty, $userId, 'bottling: ' . substr($label, 0, 100));`
- `includes/honey_inventory.php`:483 — `$res = dept_packaging_deduct($pdo, $stickerId, $totalBottles, $userId, 'bottling: ' . substr($label, 0, 100));`
- `includes/rts_damaged_use.php`:44 — `$res = dept_packaging_deduct($pdo, $stockId, $bottleCount, $userId, $label);`

## dept_production_apply_deduct_lines {#dept_production_apply_deduct_lines}

**Callers (6):**

- `includes/dept_production.php`:609 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:635 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:664 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:718 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'brc', $deductPlan, $label);`
- `includes/dept_production.php`:771 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'jar_cacao', $deductPlan, $label);`
- `includes/dept_production.php`:801 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'honey_comb', $deductPlan, $label);`

## dept_wip_fetch_row {#dept_wip_fetch_row}

**Tables:** `dept_wip_rows`

**Callers (2):**

- `includes/dept_production.php`:414 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit);`
- `includes/dept_production.php`:427 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit, true);`

## dept_wip_on_hand {#dept_wip_on_hand}

**Callers (7):**

- `includes/dept_production.php`:444 — `return ['product_key' => $productKey, 'wip_unit' => $wipUnit, 'wip_on_hand' => dept_wip_on_hand($pdo, $productKey, $wipUnit)];`
- `includes/dept_production.php`:617 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:642 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:656 — `$wip = dept_wip_on_hand($pdo, 'pt');`
- `includes/dept_production.php`:686 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:706 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:748 — `$snap['wip_tea_bags'] = $productKey === 'pt' ? dept_wip_on_hand($pdo, 'pt') : null;`

## dept_wip_adjust {#dept_wip_adjust}

**Tables:** `dept_wip_rows`

**Callers (4):**

- `includes/dept_production.php`:610 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:636 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:665 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaNeeded);`
- `includes/dept_production.php`:699 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaBags);`

## dept_production_log_entry {#dept_production_log_entry}

**Tables:** `dept_production_entries`

**Callers (7):**

- `includes/dept_production.php`:611 — `$entryId = dept_production_log_entry($pdo, 'pt', 'use_raw_bags', $bags, $userId, $note, $applied);`
- `includes/dept_production.php`:637 — `$entryId = dept_production_log_entry($pdo, 'pt', 'make_tea_bags', $teaBags, $userId, $note, $applied);`
- `includes/dept_production.php`:680 — `$entryId = dept_production_log_entry($pdo, 'pt', 'pouch', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:701 — `$entryId = dept_production_log_entry($pdo, 'pt', 'wip_waste', $teaBags, $userId, $note, []);`
- `includes/dept_production.php`:728 — `$entryId = dept_production_log_entry($pdo, 'brc', 'produce_brc', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:781 — `$entryId = dept_production_log_entry($pdo, 'jar_cacao', 'produce_jar', $jars, $userId, $note, $applied);`
- `includes/dept_production.php`:811 — `$entryId = dept_production_log_entry($pdo, 'honey_comb', 'produce_comb', $jars, $userId, $note, $applied);`

## dept_production_recent_entries {#dept_production_recent_entries}

**Tables:** `dept_production_entries`, `users`

**Callers (1):**

- `includes/dept_production.php`:750 — `$snap['production_entries'] = dept_production_recent_entries($pdo, $productKey, 20);`

## dept_production_cost_only_lines {#dept_production_cost_only_lines}

**Callers (1):**

- `includes/dept_production.php`:570 — `$costOnlyLines = dept_production_cost_only_lines($pdo, $productKey, $action, $qty);`

## dept_production_preview_lines {#dept_production_preview_lines}

**Callers (1):**

- `api/v1/honey.php`:892 — `$preview = dept_production_preview_lines(ims_pdo(), $productKey, $action, $qty);`

## dept_pt_use_raw_bags {#dept_pt_use_raw_bags}

**Callers (1):**

- `api/v1/honey.php`:735 — `$result = dept_pt_use_raw_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_pt_make_tea_bags {#dept_pt_make_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:757 — `$result = dept_pt_make_tea_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_pt_pouch {#dept_pt_pouch}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/honey.php`:779 — `$result = dept_pt_pouch($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_wip_waste_tea_bags {#dept_wip_waste_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:867 — `$result = dept_wip_waste_tea_bags($pdo, (int) $user['id'], $qty, $reason !== '' ? $reason : null);`

## dept_brc_produce_pouches {#dept_brc_produce_pouches}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/honey.php`:801 — `$result = dept_brc_produce_pouches($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_raw_detail_enriched {#dept_raw_detail_enriched}

**Callers (1):**

- `api/v1/honey.php`:641 — `$snapshot = dept_raw_detail_enriched(ims_pdo(), $slug);`

## dept_jar_produce {#dept_jar_produce}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/honey.php`:823 — `$result = dept_jar_produce($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_honey_comb_produce {#dept_honey_comb_produce}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/honey.php`:845 — `$result = dept_honey_comb_produce($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

