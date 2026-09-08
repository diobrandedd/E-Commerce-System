---
title: finished_leak_damage
source: includes/finished_leak_damage.php
generated: 2026-09-08T07:50:49+02:00
type: module
function_count: 8
---


# finished_leak_damage

> Source: `includes/finished_leak_damage.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`
- `includes/honey_pos.php`

## finished_leak_damage_normalize_reason {#finished_leak_damage_normalize_reason}

**Callers (1):**

- `includes/finished_leak_damage.php`:62 — `$reason = finished_leak_damage_normalize_reason($reason);`

## finished_leak_damage_reason_label {#finished_leak_damage_reason_label}

**Callers (3):**

- `includes/finished_leak_damage.php`:166 — `'reason_label' => finished_leak_damage_reason_label($reason),`
- `includes/finished_leak_damage.php`:257 — `'reason_label' => finished_leak_damage_reason_label($reason),`
- `includes/finished_leak_damage.php`:334 — `'reason_label' => finished_leak_damage_reason_label((string) $row['reason']),`

## finished_leak_damage_product_label {#finished_leak_damage_product_label}

**Callers (2):**

- `includes/finished_leak_damage.php`:167 — `'product_label' => finished_leak_damage_product_label([`
- `includes/finished_leak_damage.php`:335 — `'product_label' => finished_leak_damage_product_label([`

## variant_label_for_display {#variant_label_for_display}

**Callers (1):**

- `includes/finished_leak_damage.php`:33 — `$variant = variant_label_for_display((string) ($row['variant'] ?? ''));`

## finished_leak_damage_scan {#finished_leak_damage_scan}

**Callers (2):**

- `api/v1/finished_leak_damage.php`:37 — `api_ok(['row' => finished_leak_damage_scan(ims_pdo(), (int) $user['id'], $code, $reason, $note)]);`
- `includes/dept_pouch_barcodes.php`:1259 — `return finished_leak_damage_scan($pdo, $userId, $code, 'damaged', $note);`

## finished_leak_damage_scan_honey {#finished_leak_damage_scan_honey}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`, `finished_unit_leak_damage_log`

**Callers (1):**

- `includes/finished_leak_damage.php`:72 — `return finished_leak_damage_scan_honey($pdo, $userId, $honeyRow, $reason, $note);`

## finished_leak_damage_scan_dept {#finished_leak_damage_scan_dept}

**Tables:** `finished_unit_leak_damage_log`

**Callers (1):**

- `includes/finished_leak_damage.php`:80 — `return finished_leak_damage_scan_dept($pdo, $userId, $deptCode, $reason, $note);`

## finished_leak_damage_history {#finished_leak_damage_history}

**Tables:** `finished_unit_leak_damage_log`, `users`

**Callers (1):**

- `api/v1/finished_leak_damage.php`:55 — `api_ok(finished_leak_damage_history(ims_pdo(), $page, FINISHED_LEAK_DAMAGE_HISTORY_PER_PAGE, $filters));`

