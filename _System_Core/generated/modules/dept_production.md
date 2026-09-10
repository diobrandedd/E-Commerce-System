---
title: dept_production
source: includes/dept_production.php
generated: 2026-09-10T08:17:11+02:00
type: module
function_count: 29
---


# dept_production

> Source: `includes/dept_production.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/material_inventory.php`
- `includes/catalog_bootstrap.php`
- `includes/dept_pt_settings.php`
- `includes/dept_brc_settings.php`
- `includes/dept_jar_cacao_settings.php`

## dept_production_tables_ready {#dept_production_tables_ready}

**Tables:** `dept_wip_rows`, `dept_production_entries`

**Callers (7):**

- `includes/dept_production.php`:411 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:517 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:550 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:619 — `if ($action === '' || !dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:696 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:828 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:861 — `if (!dept_production_tables_ready($pdo)) {`

## dept_production_load_formula_lines {#dept_production_load_formula_lines}

**Tables:** `products`, `product_formula`, `stocks`

**Callers (3):**

- `includes/dept_production.php`:225 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:751 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:814 — `'used_formula' => dept_production_load_formula_lines($pdo, $productKey) !== [],`

## dept_production_stock_deducts {#dept_production_stock_deducts}

**Callers (3):**

- `includes/dept_production.php`:230 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:272 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:755 — `if (dept_production_stock_deducts($line)) {`

## dept_production_pt_action_matches_stage {#dept_production_pt_action_matches_stage}

**Callers (2):**

- `includes/dept_production.php`:241 — `if (!dept_production_pt_action_matches_stage($action, $stage)) {`
- `includes/dept_production.php`:760 — `if ($stage !== null && !dept_production_pt_action_matches_stage($action, $stage)) {`

## dept_production_pt_stage_for_stock {#dept_production_pt_stage_for_stock}

**Callers (2):**

- `includes/dept_production.php`:233 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`
- `includes/dept_production.php`:759 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`

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

- `includes/dept_production.php`:804 — `$deductLines = dept_production_formula_deduct_lines($pdo, $productKey, $action, $qty);`
- `includes/dept_production.php`:843 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'use_raw_bags', $bags);`
- `includes/dept_production.php`:869 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'make_tea_bags', $teaBags);`
- `includes/dept_production.php`:898 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'pouch', $pouches);`
- `includes/dept_production.php`:952 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'brc', 'produce', $pouches);`
- `includes/dept_production.php`:1031 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'jar_cacao', 'produce', $jars);`
- `includes/dept_production.php`:1061 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'honey_comb', 'produce', $jars);`

## dept_packaging_deduct {#dept_packaging_deduct}

**Tables:** `stock_entries`, `stocks`

**Callers (4):**

- `includes/dept_production.php`:375 — `$res = dept_packaging_deduct($pdo, $stockId, (float) $line['qty'], $userId, $label);`
- `includes/honey_inventory.php`:887 — `$res = dept_packaging_deduct($pdo, $stockId, $qty, $userId, 'bottling: ' . substr($label, 0, 100));`
- `includes/honey_inventory.php`:902 — `$res = dept_packaging_deduct($pdo, $stickerId, $totalBottles, $userId, 'bottling: ' . substr($label, 0, 100));`
- `includes/rts_damaged_use.php`:140 — `$res = dept_packaging_deduct($pdo, $stockId, $bottleCount, $userId, $label);`

## dept_production_apply_deduct_lines {#dept_production_apply_deduct_lines}

**Callers (6):**

- `includes/dept_production.php`:844 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:870 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:899 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:953 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'brc', $deductPlan, $label);`
- `includes/dept_production.php`:1032 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'jar_cacao', $deductPlan, $label);`
- `includes/dept_production.php`:1062 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'honey_comb', $deductPlan, $label);`

## dept_wip_fetch_row {#dept_wip_fetch_row}

**Tables:** `dept_wip_rows`

**Callers (2):**

- `includes/dept_production.php`:414 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit);`
- `includes/dept_production.php`:427 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit, true);`

## dept_wip_on_hand {#dept_wip_on_hand}

**Callers (7):**

- `includes/dept_production.php`:444 — `return ['product_key' => $productKey, 'wip_unit' => $wipUnit, 'wip_on_hand' => dept_wip_on_hand($pdo, $productKey, $wipUnit)];`
- `includes/dept_production.php`:852 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:877 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:891 — `$wip = dept_wip_on_hand($pdo, 'pt');`
- `includes/dept_production.php`:921 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:941 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:983 — `$snap['wip_tea_bags'] = $productKey === 'pt' ? dept_wip_on_hand($pdo, 'pt') : null;`

## dept_wip_adjust {#dept_wip_adjust}

**Tables:** `dept_wip_rows`

**Callers (4):**

- `includes/dept_production.php`:845 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:871 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:900 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaNeeded);`
- `includes/dept_production.php`:934 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaBags);`

## dept_production_log_entry {#dept_production_log_entry}

**Tables:** `dept_production_entries`

**Callers (8):**

- `includes/dept_production.php`:726 — `$id = dept_production_log_entry(`
- `includes/dept_production.php`:846 — `$entryId = dept_production_log_entry($pdo, 'pt', 'use_raw_bags', $bags, $userId, $note, $applied);`
- `includes/dept_production.php`:872 — `$entryId = dept_production_log_entry($pdo, 'pt', 'make_tea_bags', $teaBags, $userId, $note, $applied);`
- `includes/dept_production.php`:915 — `$entryId = dept_production_log_entry($pdo, 'pt', 'pouch', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:936 — `$entryId = dept_production_log_entry($pdo, 'pt', 'wip_waste', $teaBags, $userId, $note, []);`
- `includes/dept_production.php`:963 — `$entryId = dept_production_log_entry($pdo, 'brc', 'produce_brc', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:1042 — `$entryId = dept_production_log_entry($pdo, 'jar_cacao', 'produce_jar', $jars, $userId, $note, $applied);`
- `includes/dept_production.php`:1072 — `$entryId = dept_production_log_entry($pdo, 'honey_comb', 'produce_comb', $jars, $userId, $note, $applied);`

## dept_production_format_entry_row {#dept_production_format_entry_row}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_production_recent_entries {#dept_production_recent_entries}

**Tables:** `dept_production_entries`, `users`

**Callers (1):**

- `includes/dept_production.php`:1014 — `$snap['production_entries'] = dept_production_recent_entries($pdo, $productKey, 20);`

## dept_production_list_entries {#dept_production_list_entries}

**Tables:** `dept_production_entries`, `users`

**Callers (1):**

- `api/v1/honey.php`:1113 — `api_ok(dept_production_list_entries(ims_pdo(), $productKey, $action, $date, $page, $perPage));`

## dept_production_daily_totals {#dept_production_daily_totals}

**Tables:** `dept_production_entries`

**Callers (1):**

- `api/v1/honey.php`:1111 — `api_ok(dept_production_daily_totals(ims_pdo(), $productKey, $action, $date, $page, $perPage));`

## dept_production_record_daily {#dept_production_record_daily}

**Callers (1):**

- `api/v1/honey.php`:1129 — `$result = dept_production_record_daily(`

## dept_production_cost_only_lines {#dept_production_cost_only_lines}

**Callers (1):**

- `includes/dept_production.php`:805 — `$costOnlyLines = dept_production_cost_only_lines($pdo, $productKey, $action, $qty);`

## dept_production_preview_lines {#dept_production_preview_lines}

**Callers (1):**

- `api/v1/honey.php`:1081 — `$preview = dept_production_preview_lines(ims_pdo(), $productKey, $action, $qty);`

## dept_pt_use_raw_bags {#dept_pt_use_raw_bags}

**Callers (1):**

- `api/v1/honey.php`:976 — `$result = dept_pt_use_raw_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_pt_make_tea_bags {#dept_pt_make_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:998 — `$result = dept_pt_make_tea_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_pt_pouch {#dept_pt_pouch}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_wip_waste_tea_bags {#dept_wip_waste_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:1056 — `$result = dept_wip_waste_tea_bags($pdo, (int) $user['id'], $qty, $reason !== '' ? $reason : null);`

## dept_brc_produce_pouches {#dept_brc_produce_pouches}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_raw_detail_enriched {#dept_raw_detail_enriched}

**Callers (1):**

- `api/v1/honey.php`:882 — `$snapshot = dept_raw_detail_enriched(ims_pdo(), $slug);`

## dept_jar_produce {#dept_jar_produce}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_honey_comb_produce {#dept_honey_comb_produce}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

