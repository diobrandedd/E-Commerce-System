---
title: inventory_check
source: includes/inventory_check.php
generated: 2026-08-25T08:09:13+02:00
type: module
function_count: 25
---


# inventory_check

> Source: `includes/inventory_check.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/permissions.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`

## inventory_check_can_scan {#inventory_check_can_scan}

**Callers (1):**

- `api/v1/inventory_check.php`:11 — `if (!inventory_check_can_scan($user)) {`

## inventory_check_can_view_all {#inventory_check_can_view_all}

**Callers (6):**

- `api/v1/inventory_check.php`:21 — `if (!inventory_check_can_view_all($user)) {`
- `api/v1/inventory_check.php`:66 — `$viewAll = inventory_check_can_view_all($user);`
- `api/v1/inventory_check.php`:146 — `inventory_check_can_view_all($user) ? null : (int) $user['id']`
- `api/v1/inventory_check.php`:176 — `inventory_check_can_view_all($user) ? null : (int) $user['id']`
- `api/v1/inventory_check.php`:187 — `$viewAll = inventory_check_can_view_all($user);`
- `api/v1/inventory_check.php`:208 — `if (!inventory_check_can_view_all($user)) {`

## inventory_check_can_manage {#inventory_check_can_manage}

**Callers (3):**

- `api/v1/inventory_check.php`:31 — `if (!inventory_check_can_manage($user)) {`
- `api/v1/inventory_check.php`:70 — `'can_manage' => inventory_check_can_manage($user),`
- `api/v1/inventory_check.php`:163 — `inventory_check_can_manage($user)`

## inventory_check_serialize_event {#inventory_check_serialize_event}

**Callers (2):**

- `includes/inventory_check.php`:89 — `return inventory_check_serialize_event($row);`
- `includes/inventory_check.php`:146 — `return inventory_check_serialize_event($row);`

## inventory_check_event_fetch {#inventory_check_event_fetch}

**Tables:** `inventory_check_scans`, `inventory_check_compare_runs`, `inventory_check_events`, `users`

**Callers (3):**

- `includes/inventory_check.php`:84 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:141 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:224 — `$event = inventory_check_event_fetch($pdo, $eventId);`

## inventory_check_event_get {#inventory_check_event_get}

**Callers (7):**

- `includes/inventory_check.php`:103 — `return $id > 0 ? inventory_check_event_get($pdo, $id) : null;`
- `includes/inventory_check.php`:126 — `return inventory_check_event_get($pdo, $existingId);`
- `includes/inventory_check.php`:133 — `return inventory_check_event_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:152 — `return inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:429 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:569 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:849 — `'event' => $event ?? inventory_check_event_get($pdo, (int) $run['event_id']),`

## inventory_check_event_current {#inventory_check_event_current}

**Tables:** `inventory_check_events`

**Callers (2):**

- `api/v1/inventory_check.php`:65 — `$event = inventory_check_event_current($pdo);`
- `includes/inventory_check.php`:111 — `$open = inventory_check_event_current($pdo);`

## inventory_check_event_open {#inventory_check_event_open}

**Tables:** `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:86 — `api_ok(['event' => inventory_check_event_open(`

## inventory_check_event_close {#inventory_check_event_close}

**Tables:** `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:103 — `api_ok(['event' => inventory_check_event_close(`

## inventory_check_event_list {#inventory_check_event_list}

**Tables:** `inventory_check_events`, `inventory_check_scans`, `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:117 — `api_ok(inventory_check_event_list(`

## inventory_check_serialize_scan {#inventory_check_serialize_scan}

**Callers (2):**

- `includes/inventory_check.php`:247 — `'scan' => inventory_check_serialize_scan($dupRow),`
- `includes/inventory_check.php`:395 — `return inventory_check_serialize_scan($row);`

## inventory_check_scan {#inventory_check_scan}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:133 — `$res = inventory_check_scan($pdo, $eventId, (int) $user['id'], (string) ($in['code'] ?? ''));`

## inventory_check_resolve_honey {#inventory_check_resolve_honey}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:252 — `$honey = inventory_check_resolve_honey($pdo, $code);`

## inventory_check_resolve_dept {#inventory_check_resolve_dept}

**Callers (1):**

- `includes/inventory_check.php`:253 — `$resolved = $honey ?? inventory_check_resolve_dept($pdo, $code);`

## inventory_check_flag {#inventory_check_flag}

**Callers (2):**

- `includes/inventory_check.php`:329 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $unitStatus),`
- `includes/inventory_check.php`:366 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $row['unit_status'] !== null ? (string) $row['unit_status'] : ''),`

## inventory_check_scan_get {#inventory_check_scan_get}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `includes/inventory_check.php`:280 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`

## inventory_check_scan_remove {#inventory_check_scan_remove}

**Tables:** `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:159 — `inventory_check_scan_remove(`

## inventory_check_summary {#inventory_check_summary}

**Tables:** `inventory_check_scans`, `users`

**Callers (4):**

- `api/v1/inventory_check.php`:74 — `? inventory_check_summary($pdo, (int) $event['id'], $viewAll ? null : (int) $user['id'])`
- `api/v1/inventory_check.php`:143 — `'summary' => inventory_check_summary(`
- `api/v1/inventory_check.php`:173 — `? inventory_check_summary(`
- `api/v1/inventory_check.php`:189 — `api_ok(inventory_check_summary(ims_pdo(), $eventId, $viewAll ? null : (int) $user['id']));`

## inventory_check_scan_log {#inventory_check_scan_log}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:212 — `api_ok(inventory_check_scan_log(`

## inventory_check_compare_run {#inventory_check_compare_run}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`

**Callers (1):**

- `api/v1/inventory_check.php`:231 — `api_ok(inventory_check_compare_run(`

## inventory_check_compare_build {#inventory_check_compare_build}

**Tables:** `inventory_check_scans`

**Callers (1):**

- `includes/inventory_check.php`:570 — `$lines = inventory_check_compare_build($pdo, $eventId);`

## inventory_check_compare_honey_lines {#inventory_check_compare_honey_lines}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:676 — `$lines = array_merge($lines, inventory_check_compare_honey_lines($pdo, $honeyCounted, $honeyMeta));`

## inventory_check_dept_system_qty {#inventory_check_dept_system_qty}

**Callers (1):**

- `includes/inventory_check.php`:680 — `$systemQty = inventory_check_dept_system_qty($pdo, (string) $productKey);`

## inventory_check_compare_get {#inventory_check_compare_get}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`, `users`

**Callers (2):**

- `api/v1/inventory_check.php`:246 — `api_ok(inventory_check_compare_get(ims_pdo(), (int) ($_GET['run_id'] ?? 0)));`
- `includes/inventory_check.php`:624 — `return inventory_check_compare_get($pdo, $runId, $event);`

## inventory_check_compare_list {#inventory_check_compare_list}

**Tables:** `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:256 — `api_ok(['runs' => inventory_check_compare_list(ims_pdo(), (int) ($_GET['event_id'] ?? 0))]);`

