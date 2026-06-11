---
title: dept_production
source: includes/dept_production.php
generated: 2026-06-11T11:15:30+02:00
type: module
function_count: 20
---


# dept_production

> Source: `includes/dept_production.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/material_inventory.php`

## dept_production_tables_ready {#dept_production_tables_ready}

**Tables:** `dept_wip_rows`, `dept_production_entries`

**Callers (3):**

- `includes/dept_production.php`:345 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:411 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:462 — `if (!dept_production_tables_ready($pdo)) {`

## dept_production_load_formula_lines {#dept_production_load_formula_lines}

**Tables:** `products`, `product_formula`, `stocks`

**Callers (2):**

- `includes/dept_production.php`:173 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:455 — `'used_formula' => dept_production_load_formula_lines($pdo, $productKey) !== [],`

## dept_production_pt_stage_for_stock {#dept_production_pt_stage_for_stock}

**Callers (1):**

- `includes/dept_production.php`:178 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`

## dept_production_line_kind {#dept_production_line_kind}

**Callers (3):**

- `includes/dept_production.php`:199 — `'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),`
- `includes/dept_production.php`:216 — `'kind' => dept_production_line_kind($productKey, $line['stock_name'], $line['category']),`
- `includes/dept_production.php`:237 — `$line['kind'] = dept_production_line_kind($productKey, $line['stock_name'], (string) $stock['category']);`

## dept_production_fallback_deduct_lines {#dept_production_fallback_deduct_lines}

**Callers (1):**

- `includes/dept_production.php`:224 — `$fallback = dept_production_fallback_deduct_lines($productKey, $action, $qty);`

## dept_production_formula_deduct_lines {#dept_production_formula_deduct_lines}

**Tables:** `stocks`

**Callers (5):**

- `includes/dept_production.php`:448 — `$lines = dept_production_formula_deduct_lines($pdo, $productKey, $action, $qty);`
- `includes/dept_production.php`:470 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'make_tea_bags', $teaBags);`
- `includes/dept_production.php`:499 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'pouch', $pouches);`
- `includes/dept_production.php`:553 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'brc', 'produce', $pouches);`
- `includes/dept_production.php`:605 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'jar_cacao', 'produce', $jars);`

## dept_packaging_deduct {#dept_packaging_deduct}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/dept_production.php`:309 — `$res = dept_packaging_deduct($pdo, $stockId, (float) $line['qty'], $userId, $label);`

## dept_production_apply_deduct_lines {#dept_production_apply_deduct_lines}

**Callers (4):**

- `includes/dept_production.php`:471 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:500 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:554 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'brc', $deductPlan, $label);`
- `includes/dept_production.php`:606 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'jar_cacao', $deductPlan, $label);`

## dept_wip_fetch_row {#dept_wip_fetch_row}

**Tables:** `dept_wip_rows`

**Callers (2):**

- `includes/dept_production.php`:348 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit);`
- `includes/dept_production.php`:361 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit, true);`

## dept_wip_on_hand {#dept_wip_on_hand}

**Callers (6):**

- `includes/dept_production.php`:378 — `return ['product_key' => $productKey, 'wip_unit' => $wipUnit, 'wip_on_hand' => dept_wip_on_hand($pdo, $productKey, $wipUnit)];`
- `includes/dept_production.php`:478 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:492 — `$wip = dept_wip_on_hand($pdo, 'pt');`
- `includes/dept_production.php`:522 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:542 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:584 — `$snap['wip_tea_bags'] = $productKey === 'pt' ? dept_wip_on_hand($pdo, 'pt') : null;`

## dept_wip_adjust {#dept_wip_adjust}

**Tables:** `dept_wip_rows`

**Callers (3):**

- `includes/dept_production.php`:472 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:501 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaNeeded);`
- `includes/dept_production.php`:535 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaBags);`

## dept_production_log_entry {#dept_production_log_entry}

**Tables:** `dept_production_entries`

**Callers (5):**

- `includes/dept_production.php`:473 — `$entryId = dept_production_log_entry($pdo, 'pt', 'make_tea_bags', $teaBags, $userId, $note, $applied);`
- `includes/dept_production.php`:516 — `$entryId = dept_production_log_entry($pdo, 'pt', 'pouch', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:537 — `$entryId = dept_production_log_entry($pdo, 'pt', 'wip_waste', $teaBags, $userId, $note, []);`
- `includes/dept_production.php`:564 — `$entryId = dept_production_log_entry($pdo, 'brc', 'produce_brc', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:616 — `$entryId = dept_production_log_entry($pdo, 'jar_cacao', 'produce_jar', $jars, $userId, $note, $applied);`

## dept_production_recent_entries {#dept_production_recent_entries}

**Tables:** `dept_production_entries`, `users`

**Callers (1):**

- `includes/dept_production.php`:586 — `$snap['production_entries'] = dept_production_recent_entries($pdo, $productKey, 20);`

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

