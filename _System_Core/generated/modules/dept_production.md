---
title: dept_production
source: includes/dept_production.php
generated: 2026-06-12T05:33:53+02:00
type: module
function_count: 22
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

**Callers (3):**

- `includes/dept_production.php`:358 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:424 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:530 — `if (!dept_production_tables_ready($pdo)) {`

## dept_production_load_formula_lines {#dept_production_load_formula_lines}

**Tables:** `products`, `product_formula`, `stocks`

**Callers (3):**

- `includes/dept_production.php`:180 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:463 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:523 — `'used_formula' => dept_production_load_formula_lines($pdo, $productKey) !== [],`

## dept_production_stock_deducts {#dept_production_stock_deducts}

**Callers (3):**

- `includes/dept_production.php`:185 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:219 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:467 — `if (dept_production_stock_deducts($line)) {`

## dept_production_pt_stage_for_stock {#dept_production_pt_stage_for_stock}

**Callers (2):**

- `includes/dept_production.php`:188 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`
- `includes/dept_production.php`:471 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`

## dept_production_line_kind {#dept_production_line_kind}

**Callers (3):**

- `includes/dept_production.php`:209 — `'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),`
- `includes/dept_production.php`:229 — `'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),`
- `includes/dept_production.php`:250 — `$line['kind'] = dept_production_line_kind($productKey, $line['stock_name'], (string) $stock['category']);`

## dept_production_fallback_deduct_lines {#dept_production_fallback_deduct_lines}

**Callers (1):**

- `includes/dept_production.php`:237 — `$fallback = dept_production_fallback_deduct_lines($productKey, $action, $qty);`

## dept_production_formula_deduct_lines {#dept_production_formula_deduct_lines}

**Tables:** `stocks`

**Callers (5):**

- `includes/dept_production.php`:513 — `$deductLines = dept_production_formula_deduct_lines($pdo, $productKey, $action, $qty);`
- `includes/dept_production.php`:538 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'make_tea_bags', $teaBags);`
- `includes/dept_production.php`:567 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'pouch', $pouches);`
- `includes/dept_production.php`:621 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'brc', 'produce', $pouches);`
- `includes/dept_production.php`:673 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'jar_cacao', 'produce', $jars);`

## dept_packaging_deduct {#dept_packaging_deduct}

**Tables:** `stock_entries`, `stocks`

**Callers (3):**

- `includes/dept_production.php`:322 — `$res = dept_packaging_deduct($pdo, $stockId, (float) $line['qty'], $userId, $label);`
- `includes/honey_inventory.php`:418 — `$res = dept_packaging_deduct($pdo, $stockId, $qty, $userId, 'bottling: ' . substr($label, 0, 100));`
- `includes/honey_inventory.php`:433 — `$res = dept_packaging_deduct($pdo, $stickerId, $totalBottles, $userId, 'bottling: ' . substr($label, 0, 100));`

## dept_production_apply_deduct_lines {#dept_production_apply_deduct_lines}

**Callers (4):**

- `includes/dept_production.php`:539 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:568 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:622 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'brc', $deductPlan, $label);`
- `includes/dept_production.php`:674 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'jar_cacao', $deductPlan, $label);`

## dept_wip_fetch_row {#dept_wip_fetch_row}

**Tables:** `dept_wip_rows`

**Callers (2):**

- `includes/dept_production.php`:361 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit);`
- `includes/dept_production.php`:374 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit, true);`

## dept_wip_on_hand {#dept_wip_on_hand}

**Callers (6):**

- `includes/dept_production.php`:391 — `return ['product_key' => $productKey, 'wip_unit' => $wipUnit, 'wip_on_hand' => dept_wip_on_hand($pdo, $productKey, $wipUnit)];`
- `includes/dept_production.php`:546 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:560 — `$wip = dept_wip_on_hand($pdo, 'pt');`
- `includes/dept_production.php`:590 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:610 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:652 — `$snap['wip_tea_bags'] = $productKey === 'pt' ? dept_wip_on_hand($pdo, 'pt') : null;`

## dept_wip_adjust {#dept_wip_adjust}

**Tables:** `dept_wip_rows`

**Callers (3):**

- `includes/dept_production.php`:540 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:569 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaNeeded);`
- `includes/dept_production.php`:603 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaBags);`

## dept_production_log_entry {#dept_production_log_entry}

**Tables:** `dept_production_entries`

**Callers (5):**

- `includes/dept_production.php`:541 — `$entryId = dept_production_log_entry($pdo, 'pt', 'make_tea_bags', $teaBags, $userId, $note, $applied);`
- `includes/dept_production.php`:584 — `$entryId = dept_production_log_entry($pdo, 'pt', 'pouch', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:605 — `$entryId = dept_production_log_entry($pdo, 'pt', 'wip_waste', $teaBags, $userId, $note, []);`
- `includes/dept_production.php`:632 — `$entryId = dept_production_log_entry($pdo, 'brc', 'produce_brc', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:684 — `$entryId = dept_production_log_entry($pdo, 'jar_cacao', 'produce_jar', $jars, $userId, $note, $applied);`

## dept_production_recent_entries {#dept_production_recent_entries}

**Tables:** `dept_production_entries`, `users`

**Callers (1):**

- `includes/dept_production.php`:654 — `$snap['production_entries'] = dept_production_recent_entries($pdo, $productKey, 20);`

## dept_production_cost_only_lines {#dept_production_cost_only_lines}

**Callers (1):**

- `includes/dept_production.php`:514 — `$costOnlyLines = dept_production_cost_only_lines($pdo, $productKey, $action, $qty);`

## dept_production_preview_lines {#dept_production_preview_lines}

**Callers (1):**

- `api/v1/honey.php`:767 — `$preview = dept_production_preview_lines(ims_pdo(), $productKey, $action, $qty);`

## dept_pt_make_tea_bags {#dept_pt_make_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:654 — `$result = dept_pt_make_tea_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_pt_pouch {#dept_pt_pouch}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/honey.php`:676 — `$result = dept_pt_pouch($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_wip_waste_tea_bags {#dept_wip_waste_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:742 — `$result = dept_wip_waste_tea_bags($pdo, (int) $user['id'], $qty, $reason !== '' ? $reason : null);`

## dept_brc_produce_pouches {#dept_brc_produce_pouches}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/honey.php`:698 — `$result = dept_brc_produce_pouches($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_raw_detail_enriched {#dept_raw_detail_enriched}

**Callers (1):**

- `api/v1/honey.php`:560 — `$snapshot = dept_raw_detail_enriched(ims_pdo(), $slug);`

## dept_jar_produce {#dept_jar_produce}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/honey.php`:720 — `$result = dept_jar_produce($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

