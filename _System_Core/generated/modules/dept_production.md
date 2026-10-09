---
title: dept_production
source: includes/dept_production.php
generated: 2026-10-09T06:34:01+02:00
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
- `includes/dept_raw_freebies.php`
- `includes/dept_brc_settings.php`
- `includes/dept_raw_freebies.php`
- `includes/dept_jar_cacao_settings.php`
- `includes/dept_raw_freebies.php`

## dept_production_tables_ready {#dept_production_tables_ready}

**Tables:** `dept_wip_rows`, `dept_production_entries`

**Callers (7):**

- `includes/dept_production.php`:408 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:514 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:547 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:616 — `if ($action === '' || !dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:693 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:825 — `if (!dept_production_tables_ready($pdo)) {`
- `includes/dept_production.php`:858 — `if (!dept_production_tables_ready($pdo)) {`

## dept_production_load_formula_lines {#dept_production_load_formula_lines}

**Tables:** `products`, `product_formula`, `stocks`

**Callers (3):**

- `includes/dept_production.php`:225 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:748 — `$formulaLines = dept_production_load_formula_lines($pdo, $productKey);`
- `includes/dept_production.php`:811 — `'used_formula' => dept_production_load_formula_lines($pdo, $productKey) !== [],`

## dept_production_stock_deducts {#dept_production_stock_deducts}

**Callers (3):**

- `includes/dept_production.php`:230 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:272 — `if (!dept_production_stock_deducts($line)) {`
- `includes/dept_production.php`:752 — `if (dept_production_stock_deducts($line)) {`

## dept_production_pt_action_matches_stage {#dept_production_pt_action_matches_stage}

**Callers (2):**

- `includes/dept_production.php`:241 — `if (!dept_production_pt_action_matches_stage($action, $stage)) {`
- `includes/dept_production.php`:757 — `if ($stage !== null && !dept_production_pt_action_matches_stage($action, $stage)) {`

## dept_production_pt_stage_for_stock {#dept_production_pt_stage_for_stock}

**Callers (2):**

- `includes/dept_production.php`:233 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`
- `includes/dept_production.php`:756 — `$stage = dept_production_pt_stage_for_stock($line['stock_name'], $line['category']);`

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

- `includes/dept_production.php`:801 — `$deductLines = dept_production_formula_deduct_lines($pdo, $productKey, $action, $qty);`
- `includes/dept_production.php`:840 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'use_raw_bags', $bags);`
- `includes/dept_production.php`:866 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'make_tea_bags', $teaBags);`
- `includes/dept_production.php`:895 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'pt', 'pouch', $pouches);`
- `includes/dept_production.php`:949 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'brc', 'produce', $pouches);`
- `includes/dept_production.php`:1039 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'jar_cacao', 'produce', $jars);`
- `includes/dept_production.php`:1069 — `$deductPlan = dept_production_formula_deduct_lines($pdo, 'honey_comb', 'produce', $jars);`

## dept_packaging_deduct {#dept_packaging_deduct}

**Tables:** `stock_entries`, `stocks`

**Callers (1):**

- `includes/rts_damaged_use.php`:140 — `$res = dept_packaging_deduct($pdo, $stockId, $bottleCount, $userId, $label);`

## dept_production_apply_deduct_lines {#dept_production_apply_deduct_lines}

**Callers (6):**

- `includes/dept_production.php`:841 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:867 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:896 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'pt', $deductPlan, $label);`
- `includes/dept_production.php`:950 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'brc', $deductPlan, $label);`
- `includes/dept_production.php`:1040 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'jar_cacao', $deductPlan, $label);`
- `includes/dept_production.php`:1070 — `$applied = dept_production_apply_deduct_lines($pdo, $userId, 'honey_comb', $deductPlan, $label);`

## dept_wip_fetch_row {#dept_wip_fetch_row}

**Tables:** `dept_wip_rows`

**Callers (2):**

- `includes/dept_production.php`:411 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit);`
- `includes/dept_production.php`:424 — `$row = dept_wip_fetch_row($pdo, $productKey, $wipUnit, true);`

## dept_wip_on_hand {#dept_wip_on_hand}

**Callers (7):**

- `includes/dept_production.php`:441 — `return ['product_key' => $productKey, 'wip_unit' => $wipUnit, 'wip_on_hand' => dept_wip_on_hand($pdo, $productKey, $wipUnit)];`
- `includes/dept_production.php`:849 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:874 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:888 — `$wip = dept_wip_on_hand($pdo, 'pt');`
- `includes/dept_production.php`:918 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:938 — `'wip_tea_bags' => dept_wip_on_hand($pdo, 'pt'),`
- `includes/dept_production.php`:980 — `$snap['wip_tea_bags'] = $productKey === 'pt' ? dept_wip_on_hand($pdo, 'pt') : null;`

## dept_wip_adjust {#dept_wip_adjust}

**Tables:** `dept_wip_rows`

**Callers (4):**

- `includes/dept_production.php`:842 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:868 — `dept_wip_adjust($pdo, $userId, 'pt', $teaBags);`
- `includes/dept_production.php`:897 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaNeeded);`
- `includes/dept_production.php`:931 — `dept_wip_adjust($pdo, $userId, 'pt', -$teaBags);`

## dept_production_log_entry {#dept_production_log_entry}

**Tables:** `dept_production_entries`

**Callers (8):**

- `includes/dept_production.php`:723 — `$id = dept_production_log_entry(`
- `includes/dept_production.php`:843 — `$entryId = dept_production_log_entry($pdo, 'pt', 'use_raw_bags', $bags, $userId, $note, $applied);`
- `includes/dept_production.php`:869 — `$entryId = dept_production_log_entry($pdo, 'pt', 'make_tea_bags', $teaBags, $userId, $note, $applied);`
- `includes/dept_production.php`:912 — `$entryId = dept_production_log_entry($pdo, 'pt', 'pouch', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:933 — `$entryId = dept_production_log_entry($pdo, 'pt', 'wip_waste', $teaBags, $userId, $note, []);`
- `includes/dept_production.php`:960 — `$entryId = dept_production_log_entry($pdo, 'brc', 'produce_brc', $pouches, $userId, $note, $applied);`
- `includes/dept_production.php`:1050 — `$entryId = dept_production_log_entry($pdo, 'jar_cacao', 'produce_jar', $jars, $userId, $note, $applied);`
- `includes/dept_production.php`:1080 — `$entryId = dept_production_log_entry($pdo, 'honey_comb', 'produce_comb', $jars, $userId, $note, $applied);`

## dept_production_format_entry_row {#dept_production_format_entry_row}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_production_recent_entries {#dept_production_recent_entries}

**Tables:** `dept_production_entries`, `users`

**Callers (1):**

- `includes/dept_production.php`:1022 — `$snap['production_entries'] = dept_production_recent_entries($pdo, $productKey, 20);`

## dept_production_list_entries {#dept_production_list_entries}

**Tables:** `dept_production_entries`, `users`

**Callers (1):**

- `api/v1/honey.php`:1324 — `api_ok(dept_production_list_entries(ims_pdo(), $productKey, $action, $date, $page, $perPage));`

## dept_production_daily_totals {#dept_production_daily_totals}

**Tables:** `dept_production_entries`

**Callers (1):**

- `api/v1/honey.php`:1322 — `api_ok(dept_production_daily_totals(ims_pdo(), $productKey, $action, $date, $page, $perPage));`

## dept_production_record_daily {#dept_production_record_daily}

**Callers (1):**

- `api/v1/honey.php`:1340 — `$result = dept_production_record_daily(`

## dept_production_cost_only_lines {#dept_production_cost_only_lines}

**Callers (1):**

- `includes/dept_production.php`:802 — `$costOnlyLines = dept_production_cost_only_lines($pdo, $productKey, $action, $qty);`

## dept_production_preview_lines {#dept_production_preview_lines}

**Callers (1):**

- `api/v1/honey.php`:1292 — `$preview = dept_production_preview_lines(ims_pdo(), $productKey, $action, $qty);`

## dept_pt_use_raw_bags {#dept_pt_use_raw_bags}

**Callers (1):**

- `api/v1/honey.php`:1187 — `$result = dept_pt_use_raw_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_pt_make_tea_bags {#dept_pt_make_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:1209 — `$result = dept_pt_make_tea_bags($pdo, (int) $user['id'], $qty, $note !== '' ? $note : null);`

## dept_pt_pouch {#dept_pt_pouch}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_wip_waste_tea_bags {#dept_wip_waste_tea_bags}

**Callers (1):**

- `api/v1/honey.php`:1267 — `$result = dept_wip_waste_tea_bags($pdo, (int) $user['id'], $qty, $reason !== '' ? $reason : null);`

## dept_brc_produce_pouches {#dept_brc_produce_pouches}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_raw_detail_enriched {#dept_raw_detail_enriched}

**Callers (1):**

- `api/v1/honey.php`:1020 — `$snapshot = dept_raw_detail_enriched(ims_pdo(), $slug);`

## dept_jar_produce {#dept_jar_produce}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_honey_comb_produce {#dept_honey_comb_produce}

**Tables:** `dept_inventory_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

