---
title: rts_stock_in_sessions
source: includes/rts_stock_in_sessions.php
generated: 2026-09-10T08:17:47+02:00
type: module
function_count: 13
---


# rts_stock_in_sessions

> Source: `includes/rts_stock_in_sessions.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/rts_inventory_sync.php`

## rts_stock_in_request_key {#rts_stock_in_request_key}

**Callers (1):**

- `includes/rts_stock_in_sessions.php`:238 — `$requestKey = rts_stock_in_request_key($requestKey);`

## rts_stock_in_session_row {#rts_stock_in_session_row}

**Tables:** `rts_stock_in_sessions`, `users`

**Callers (6):**

- `includes/rts_stock_in_sessions.php`:44 — `$session = rts_stock_in_session_row($pdo, $sessionId, $userId);`
- `includes/rts_stock_in_sessions.php`:244 — `$session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);`
- `includes/rts_stock_in_sessions.php`:370 — `$session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);`
- `includes/rts_stock_in_sessions.php`:411 — `$session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);`
- `includes/rts_stock_in_sessions.php`:447 — `$session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);`
- `includes/rts_stock_in_sessions.php`:479 — `$session = rts_stock_in_session_row($pdo, $sessionId, $userId, true);`

## rts_stock_in_session_get {#rts_stock_in_session_get}

**Tables:** `rts_stock_in_session_items`

**Callers (10):**

- `api/v1/rts.php`:225 — `'session' => rts_stock_in_session_get(`
- `includes/rts_stock_in_sessions.php`:117 — `return rts_stock_in_session_get($pdo, $existing, $userId);`
- `includes/rts_stock_in_sessions.php`:252 — `return ['result' => 'replayed', 'session' => rts_stock_in_session_get($pdo, $sessionId, $userId), 'lookup' => null];`
- `includes/rts_stock_in_sessions.php`:336 — `'session' => rts_stock_in_session_get($pdo, $sessionId, $userId),`
- `includes/rts_stock_in_sessions.php`:350 — `return ['result' => 'ok', 'session' => rts_stock_in_session_get($pdo, $sessionId, $userId), 'lookup' => $lookup];`
- `includes/rts_stock_in_sessions.php`:394 — `return rts_stock_in_session_get($pdo, $sessionId, $userId);`
- `includes/rts_stock_in_sessions.php`:430 — `return rts_stock_in_session_get($pdo, $sessionId, $userId);`
- `includes/rts_stock_in_sessions.php`:462 — `return rts_stock_in_session_get($pdo, $sessionId, $userId);`
- `includes/rts_stock_in_sessions.php`:484 — `return rts_stock_in_session_get($pdo, $sessionId, $userId);`
- `includes/rts_stock_in_sessions.php`:531 — `return rts_stock_in_session_get($pdo, $sessionId, $userId);`

## rts_stock_in_session_start {#rts_stock_in_session_start}

**Tables:** `rts_stock_in_sessions`

**Callers (1):**

- `api/v1/rts.php`:213 — `api_ok(['session' => rts_stock_in_session_start(ims_pdo(), (int) $user['id'])]);`

## rts_stock_in_event {#rts_stock_in_event}

**Tables:** `rts_stock_in_session_events`

**Callers (5):**

- `includes/rts_stock_in_sessions.php`:248 — `if (!rts_stock_in_event($pdo, $sessionId, $userId, 'scan', null, $requestKey, $code, 0)) {`
- `includes/rts_stock_in_sessions.php`:389 — `rts_stock_in_event($pdo, $sessionId, $userId, 'update', $itemId, null, (string) $item['code'], $qty - $oldQty);`
- `includes/rts_stock_in_sessions.php`:422 — `rts_stock_in_event($pdo, $sessionId, $userId, 'remove', $itemId, null, (string) $item['code'], -(int) $item['qty']);`
- `includes/rts_stock_in_sessions.php`:456 — `rts_stock_in_event($pdo, $sessionId, $userId, 'discard');`
- `includes/rts_stock_in_sessions.php`:523 — `rts_stock_in_event($pdo, $sessionId, $userId, 'confirm', null, null, null, null, [`

## rts_stock_in_validate_honey {#rts_stock_in_validate_honey}

**Callers (1):**

- `includes/rts_stock_in_sessions.php`:293 — `rts_stock_in_validate_honey($honey);`

## rts_stock_in_dept_lookup {#rts_stock_in_dept_lookup}

**Callers (1):**

- `includes/rts_stock_in_sessions.php`:307 — `$dept = rts_stock_in_dept_lookup($pdo, $code);`

## rts_stock_in_validate_dept {#rts_stock_in_validate_dept}

**Callers (1):**

- `includes/rts_stock_in_sessions.php`:311 — `rts_stock_in_validate_dept($dept);`

## rts_stock_in_session_scan {#rts_stock_in_session_scan}

**Tables:** `rts_stock_in_session_items`, `rts_stock_in_session_events`

**Callers (1):**

- `api/v1/rts.php`:243 — `api_ok(rts_stock_in_session_scan(`

## rts_stock_in_session_update_qty {#rts_stock_in_session_update_qty}

**Tables:** `rts_stock_in_session_items`

**Callers (1):**

- `api/v1/rts.php`:263 — `api_ok(['session' => rts_stock_in_session_update_qty(`

## rts_stock_in_session_remove {#rts_stock_in_session_remove}

**Tables:** `rts_stock_in_session_items`

**Callers (1):**

- `api/v1/rts.php`:282 — `api_ok(['session' => rts_stock_in_session_remove(`

## rts_stock_in_session_discard {#rts_stock_in_session_discard}

**Tables:** `rts_stock_in_sessions`

**Callers (1):**

- `api/v1/rts.php`:300 — `api_ok(['session' => rts_stock_in_session_discard(`

## rts_stock_in_session_confirm {#rts_stock_in_session_confirm}

**Tables:** `rts_stock_in_session_items`, `rts_stock_in_sessions`

**Callers (1):**

- `api/v1/rts.php`:317 — `api_ok(['session' => rts_stock_in_session_confirm(`

