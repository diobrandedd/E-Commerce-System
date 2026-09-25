---
title: pack_cancel_restock
source: includes/pack_cancel_restock.php
generated: 2026-09-25T09:41:51+02:00
type: module
function_count: 11
---


# pack_cancel_restock

> Source: `includes/pack_cancel_restock.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_schema.php`
- `includes/honey_pos.php`
- `includes/sku_barcodes.php`

## pack_cancel_restock_is_restorable {#pack_cancel_restock_is_restorable}

**Callers (4):**

- `includes/pack_cancel_restock.php`:357 — `if (!pack_cancel_restock_is_restorable($unitStatus)) {`
- `includes/pack_cancel_restock.php`:428 — `if (!pack_cancel_restock_is_restorable($unitStatus)) {`
- `includes/pack_cancel_restock.php`:512 — `if (!pack_cancel_restock_is_restorable($status)) {`
- `includes/pack_cancel_restock.php`:568 — `if (!pack_cancel_restock_is_restorable($status)) {`

## pack_cancel_restock_start {#pack_cancel_restock_start}

**Tables:** `pack_cancel_restock_sessions`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:46 — `api_ok(['session' => pack_cancel_restock_start(ims_pdo(), (int) $user['id'])]);`

## pack_cancel_restock_get {#pack_cancel_restock_get}

**Tables:** `pack_cancel_restock_sessions`, `pack_cancel_restock_honey_items`, `pack_cancel_restock_dept_items`, `pack_cancel_restock_sku_items`, `users`, `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`, `product_sku_barcodes`

**Callers (18):**

- `api/v1/pack_cancel_restock.php`:55 — `api_ok(['session' => pack_cancel_restock_get(ims_pdo(), $id)]);`
- `includes/pack_cancel_restock.php`:31 — `return pack_cancel_restock_get($pdo, (int) $existing);`
- `includes/pack_cancel_restock.php`:36 — `return pack_cancel_restock_get($pdo, (int) $pdo->lastInsertId());`
- `includes/pack_cancel_restock.php`:249 — `$sess = pack_cancel_restock_get($pdo, $sessionId);`
- `includes/pack_cancel_restock.php`:331 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:347 — `return ['result' => 'void', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:354 — `return ['result' => 'duplicate_session', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:360 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:365 — `return ['result' => 'not_restorable', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:373 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:400 — `return ['result' => 'unknown', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => null];`
- `includes/pack_cancel_restock.php`:418 — `return ['result' => 'void', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:425 — `return ['result' => 'duplicate_session', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:431 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:436 — `return ['result' => 'not_restorable', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:445 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:659 — `return pack_cancel_restock_get($pdo, $sessionId);`
- `includes/pack_cancel_restock.php`:782 — `$detail = pack_cancel_restock_get($pdo, (int) $r['id']);`

## pack_cancel_restock_scan {#pack_cancel_restock_scan}

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:76 — `api_ok(pack_cancel_restock_scan(`

## pack_cancel_restock_scan_sku {#pack_cancel_restock_scan_sku}

**Tables:** `pack_cancel_restock_sku_items`

**Callers (1):**

- `includes/pack_cancel_restock.php`:270 — `return pack_cancel_restock_scan_sku($pdo, $userId, $sessionId, $sku, $qty, $pool);`

## pack_cancel_restock_scan_honey {#pack_cancel_restock_scan_honey}

**Tables:** `pack_cancel_restock_honey_items`

**Callers (1):**

- `includes/pack_cancel_restock.php`:276 — `return pack_cancel_restock_scan_honey($pdo, $sessionId, $honey);`

## pack_cancel_restock_scan_dept {#pack_cancel_restock_scan_dept}

**Tables:** `pack_cancel_restock_dept_items`

**Callers (1):**

- `includes/pack_cancel_restock.php`:279 — `return pack_cancel_restock_scan_dept($pdo, $sessionId, $code);`

## pack_cancel_restock_confirm {#pack_cancel_restock_confirm}

**Tables:** `pack_cancel_restock_sessions`, `pack_cancel_restock_honey_items`, `pack_cancel_restock_dept_items`, `pack_cancel_restock_sku_items`, `honey_bottle_units`, `product_sku_barcodes`, `dept_inventory_rows`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:96 — `api_ok(['session' => pack_cancel_restock_confirm(`

## pack_cancel_restock_system_packaging_row {#pack_cancel_restock_system_packaging_row}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/pack_cancel_restock.php`:619 — `$packagingRowId = pack_cancel_restock_system_packaging_row($pdo, $userId, $variant);`

## pack_cancel_restock_discard {#pack_cancel_restock_discard}

**Tables:** `pack_cancel_restock_sessions`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:112 — `pack_cancel_restock_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));`

## pack_cancel_restock_list {#pack_cancel_restock_list}

**Tables:** `pack_cancel_restock_honey_items`, `pack_cancel_restock_sku_items`, `pack_cancel_restock_dept_items`, `pack_cancel_restock_sessions`, `product_sku_barcodes`, `users`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:121 — `api_ok(pack_cancel_restock_list(`

