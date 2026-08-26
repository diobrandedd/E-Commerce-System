---
title: pack_cancel_restock
source: includes/pack_cancel_restock.php
generated: 2026-08-26T04:07:13+02:00
type: module
function_count: 9
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

## pack_cancel_restock_is_restorable {#pack_cancel_restock_is_restorable}

**Callers (4):**

- `includes/pack_cancel_restock.php`:212 — `if (!pack_cancel_restock_is_restorable($unitStatus)) {`
- `includes/pack_cancel_restock.php`:283 — `if (!pack_cancel_restock_is_restorable($unitStatus)) {`
- `includes/pack_cancel_restock.php`:356 — `if (!pack_cancel_restock_is_restorable($status)) {`
- `includes/pack_cancel_restock.php`:412 — `if (!pack_cancel_restock_is_restorable($status)) {`

## pack_cancel_restock_start {#pack_cancel_restock_start}

**Tables:** `pack_cancel_restock_sessions`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:46 — `api_ok(['session' => pack_cancel_restock_start(ims_pdo(), (int) $user['id'])]);`

## pack_cancel_restock_get {#pack_cancel_restock_get}

**Tables:** `pack_cancel_restock_sessions`, `pack_cancel_restock_honey_items`, `pack_cancel_restock_dept_items`, `users`, `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (17):**

- `api/v1/pack_cancel_restock.php`:55 — `api_ok(['session' => pack_cancel_restock_get(ims_pdo(), $id)]);`
- `includes/pack_cancel_restock.php`:30 — `return pack_cancel_restock_get($pdo, (int) $existing);`
- `includes/pack_cancel_restock.php`:35 — `return pack_cancel_restock_get($pdo, (int) $pdo->lastInsertId());`
- `includes/pack_cancel_restock.php`:178 — `$sess = pack_cancel_restock_get($pdo, $sessionId);`
- `includes/pack_cancel_restock.php`:202 — `return ['result' => 'void', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:209 — `return ['result' => 'duplicate_session', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:215 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:220 — `return ['result' => 'not_restorable', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:228 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:255 — `return ['result' => 'unknown', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => null];`
- `includes/pack_cancel_restock.php`:273 — `return ['result' => 'void', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:280 — `return ['result' => 'duplicate_session', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:286 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:291 — `return ['result' => 'not_restorable', 'session' => pack_cancel_restock_get($pdo, $sessionId), 'lookup' => $lookup];`
- `includes/pack_cancel_restock.php`:300 — `'session' => pack_cancel_restock_get($pdo, $sessionId),`
- `includes/pack_cancel_restock.php`:464 — `return pack_cancel_restock_get($pdo, $sessionId);`
- `includes/pack_cancel_restock.php`:547 — `$detail = pack_cancel_restock_get($pdo, (int) $r['id']);`

## pack_cancel_restock_scan {#pack_cancel_restock_scan}

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:68 — `api_ok(pack_cancel_restock_scan(`

## pack_cancel_restock_scan_honey {#pack_cancel_restock_scan_honey}

**Tables:** `pack_cancel_restock_honey_items`

**Callers (1):**

- `includes/pack_cancel_restock.php`:185 — `return pack_cancel_restock_scan_honey($pdo, $sessionId, $honey);`

## pack_cancel_restock_scan_dept {#pack_cancel_restock_scan_dept}

**Tables:** `pack_cancel_restock_dept_items`

**Callers (1):**

- `includes/pack_cancel_restock.php`:188 — `return pack_cancel_restock_scan_dept($pdo, $sessionId, $code);`

## pack_cancel_restock_confirm {#pack_cancel_restock_confirm}

**Tables:** `pack_cancel_restock_sessions`, `pack_cancel_restock_honey_items`, `pack_cancel_restock_dept_items`, `honey_bottle_units`, `dept_inventory_rows`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:85 — `api_ok(['session' => pack_cancel_restock_confirm(`

## pack_cancel_restock_discard {#pack_cancel_restock_discard}

**Tables:** `pack_cancel_restock_sessions`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:101 — `pack_cancel_restock_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));`

## pack_cancel_restock_list {#pack_cancel_restock_list}

**Tables:** `pack_cancel_restock_honey_items`, `pack_cancel_restock_dept_items`, `pack_cancel_restock_sessions`, `users`

**Callers (1):**

- `api/v1/pack_cancel_restock.php`:110 — `api_ok(pack_cancel_restock_list(`

