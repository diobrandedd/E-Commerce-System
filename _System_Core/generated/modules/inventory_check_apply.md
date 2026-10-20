---
title: inventory_check_apply
source: includes/inventory_check_apply.php
generated: 2026-10-15T11:17:50+02:00
type: module
function_count: 10
---


# inventory_check_apply

> Source: `includes/inventory_check_apply.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_raw_freebies.php`

## inventory_check_compare_run_for_events {#inventory_check_compare_run_for_events}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`

**Callers (2):**

- `includes/inventory_check.php`:1049 — `return inventory_check_compare_run_for_events($pdo, [$eventId], $userId);`
- `includes/inventory_check_apply.php`:404 — `$compare = inventory_check_compare_run_for_events($pdo, $eventIds, $userId);`

## inventory_check_audited_unique_codes {#inventory_check_audited_unique_codes}

**Tables:** `inventory_check_scans`

**Callers (1):**

- `includes/inventory_check_apply.php`:408 — `$uniques = inventory_check_audited_unique_codes($pdo, $eventIds);`

## inventory_check_void_missing_uniques {#inventory_check_void_missing_uniques}

**Tables:** `honey_bottle_units`, `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check_apply.php`:409 — `$voided = inventory_check_void_missing_uniques($pdo, $uniques, $runId);`

## inventory_check_apply_honey_qty {#inventory_check_apply_honey_qty}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check_apply.php`:322 — `inventory_check_apply_honey_qty($pdo, $userId, $variant, $size, $target);`

## inventory_check_apply_dept_qty {#inventory_check_apply_dept_qty}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `includes/inventory_check_apply.php`:330 — `inventory_check_apply_dept_qty($pdo, $userId, $pk, $target);`

## inventory_check_apply_freebie_qty {#inventory_check_apply_freebie_qty}

**Tables:** `dept_raw_freebie_balances`

**Callers (1):**

- `includes/inventory_check_apply.php`:338 — `inventory_check_apply_freebie_qty($pdo, $userId, $pk, $target);`

## inventory_check_apply_qty_to_audited {#inventory_check_apply_qty_to_audited}

**Callers (1):**

- `includes/inventory_check_apply.php`:410 — `inventory_check_apply_qty_to_audited($pdo, $userId, $lines);`

## inventory_check_assert_not_already_applied {#inventory_check_assert_not_already_applied}

**Tables:** `inventory_check_compare_runs`

**Callers (1):**

- `includes/inventory_check_apply.php`:397 — `inventory_check_assert_not_already_applied($pdo, $eventIds);`

## inventory_check_finalize_with_sync {#inventory_check_finalize_with_sync}

**Tables:** `inventory_check_compare_runs`

**Callers (2):**

- `api/v1/inventory_check.php`:413 — `$result = inventory_check_finalize_with_sync($pdo, $user, [$eventId], false);`
- `api/v1/inventory_check.php`:445 — `$result = inventory_check_finalize_with_sync($pdo, $user, $eventIds, false);`

## inventory_check_serialize_compare_run {#inventory_check_serialize_compare_run}

**Callers (2):**

- `includes/inventory_check.php`:1341 — `'run' => inventory_check_serialize_compare_run($run),`
- `includes/inventory_check.php`:1577 — `$run = inventory_check_serialize_compare_run($row);`

