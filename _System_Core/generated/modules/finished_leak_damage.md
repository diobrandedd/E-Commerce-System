---
title: finished_leak_damage
source: includes/finished_leak_damage.php
generated: 2026-10-09T06:34:05+02:00
type: module
function_count: 11
---


# finished_leak_damage

> Source: `includes/finished_leak_damage.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`
- `includes/honey_pos.php`
- `includes/sku_barcodes.php`
- `includes/honey_inventory.php`

## finished_leak_damage_normalize_reason {#finished_leak_damage_normalize_reason}

**Callers (1):**

- `includes/finished_leak_damage.php`:68 — `$reason = finished_leak_damage_normalize_reason($reason);`

## finished_leak_damage_reason_label {#finished_leak_damage_reason_label}

**Callers (5):**

- `includes/finished_leak_damage.php`:228 — `'reason_label' => finished_leak_damage_reason_label($reason),`
- `includes/finished_leak_damage.php`:259 — `'reason_label' => finished_leak_damage_reason_label($reason),`
- `includes/finished_leak_damage.php`:364 — `'reason_label' => finished_leak_damage_reason_label($reason),`
- `includes/finished_leak_damage.php`:457 — `'reason_label' => finished_leak_damage_reason_label($reason),`
- `includes/finished_leak_damage.php`:536 — `'reason_label' => finished_leak_damage_reason_label((string) $row['reason']),`

## finished_leak_damage_product_label {#finished_leak_damage_product_label}

**Callers (4):**

- `includes/finished_leak_damage.php`:125 — `'label' => finished_leak_damage_product_label([`
- `includes/finished_leak_damage.php`:229 — `'product_label' => finished_leak_damage_product_label([`
- `includes/finished_leak_damage.php`:365 — `'product_label' => finished_leak_damage_product_label([`
- `includes/finished_leak_damage.php`:537 — `'product_label' => finished_leak_damage_product_label([`

## variant_label_for_display {#variant_label_for_display}

**Callers (1):**

- `includes/finished_leak_damage.php`:33 — `$variant = variant_label_for_display((string) ($row['variant'] ?? ''));`

## finished_leak_damage_scan {#finished_leak_damage_scan}

**Callers (2):**

- `api/v1/finished_leak_damage.php`:42 — `$result = finished_leak_damage_scan(ims_pdo(), (int) $user['id'], $code, $reason, $note, $qty);`
- `includes/dept_pouch_barcodes.php`:1461 — `return finished_leak_damage_scan($pdo, $userId, $code, 'damaged', $note);`

## finished_leak_damage_sku_pending_payload {#finished_leak_damage_sku_pending_payload}

**Callers (1):**

- `includes/finished_leak_damage.php`:188 — `return finished_leak_damage_sku_pending_payload($sku);`

## finished_leak_damage_deduct_honey_packaging {#finished_leak_damage_deduct_honey_packaging}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/finished_leak_damage.php`:209 — `finished_leak_damage_deduct_honey_packaging($pdo, $variant, $size, $qty);`

## finished_leak_damage_scan_sku {#finished_leak_damage_scan_sku}

**Tables:** `finished_unit_leak_damage_log`

**Callers (1):**

- `includes/finished_leak_damage.php`:100 — `return finished_leak_damage_scan_sku($pdo, $userId, $sku, $reason, $note, $qty);`

## finished_leak_damage_scan_honey {#finished_leak_damage_scan_honey}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`, `finished_unit_leak_damage_log`

**Callers (1):**

- `includes/finished_leak_damage.php`:78 — `return finished_leak_damage_scan_honey($pdo, $userId, $honeyRow, $reason, $note);`

## finished_leak_damage_scan_dept {#finished_leak_damage_scan_dept}

**Tables:** `finished_unit_leak_damage_log`

**Callers (1):**

- `includes/finished_leak_damage.php`:84 — `return finished_leak_damage_scan_dept($pdo, $userId, $deptCode, $reason, $note);`

## finished_leak_damage_history {#finished_leak_damage_history}

**Tables:** `finished_unit_leak_damage_log`, `users`

**Callers (1):**

- `api/v1/finished_leak_damage.php`:66 — `api_ok(finished_leak_damage_history(ims_pdo(), $page, FINISHED_LEAK_DAMAGE_HISTORY_PER_PAGE, $filters));`

