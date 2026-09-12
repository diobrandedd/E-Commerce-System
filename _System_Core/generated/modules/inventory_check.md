---
title: inventory_check
source: includes/inventory_check.php
generated: 2026-09-12T07:56:56+02:00
type: module
function_count: 33
---


# inventory_check

> Source: `includes/inventory_check.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/permissions.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`
- `includes/sku_barcodes.php`

## inventory_check_can_scan {#inventory_check_can_scan}

**Callers (1):**

- `api/v1/inventory_check.php`:11 — `if (!inventory_check_can_scan($user)) {`

## inventory_check_can_view_all {#inventory_check_can_view_all}

**Callers (4):**

- `api/v1/inventory_check.php`:21 — `if (!inventory_check_can_view_all($user)) {`
- `api/v1/inventory_check.php`:88 — `$viewAll = inventory_check_can_view_all($user);`
- `api/v1/inventory_check.php`:157 — `$onlyMine = inventory_check_can_view_all($user) ? null : (int) $user['id'];`
- `includes/inventory_check.php`:124 — `return inventory_check_can_view_all($user) || inventory_check_can_manage($user);`

## inventory_check_can_manage {#inventory_check_can_manage}

**Callers (5):**

- `api/v1/inventory_check.php`:31 — `if (!inventory_check_can_manage($user)) {`
- `api/v1/inventory_check.php`:92 — `'can_manage' => inventory_check_can_manage($user),`
- `api/v1/inventory_check.php`:209 — `inventory_check_can_manage($user),`
- `api/v1/inventory_check.php`:244 — `inventory_check_can_manage($user)`
- `includes/inventory_check.php`:124 — `return inventory_check_can_view_all($user) || inventory_check_can_manage($user);`

## inventory_check_serialize_event {#inventory_check_serialize_event}

**Callers (2):**

- `includes/inventory_check.php`:89 — `return inventory_check_serialize_event($row);`
- `includes/inventory_check.php`:171 — `return inventory_check_serialize_event($row);`

## inventory_check_event_fetch {#inventory_check_event_fetch}

**Tables:** `inventory_check_scans`, `inventory_check_compare_runs`, `inventory_check_events`, `users`

**Callers (4):**

- `includes/inventory_check.php`:84 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:166 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:185 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:322 — `$event = inventory_check_event_fetch($pdo, $eventId);`

## inventory_check_event_get {#inventory_check_event_get}

**Callers (9):**

- `api/v1/inventory_check.php`:323 — `$ev = inventory_check_event_get($pdo, $id);`
- `includes/inventory_check.php`:110 — `return $id > 0 ? inventory_check_event_get($pdo, $id) : null;`
- `includes/inventory_check.php`:133 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:158 — `return inventory_check_event_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:177 — `return inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:620 — `$event = inventory_check_event_get($pdo, $ids[0]);`
- `includes/inventory_check.php`:803 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:943 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:1256 — `'event' => $event ?? inventory_check_event_get($pdo, $eventId),`

## inventory_check_event_current {#inventory_check_event_current}

**Tables:** `inventory_check_events`

**Callers (2):**

- `api/v1/inventory_check.php`:87 — `$event = inventory_check_event_current($pdo, (int) $user['id']);`
- `includes/inventory_check.php`:148 — `$open = inventory_check_event_current($pdo, $userId);`

## inventory_check_user_can_access_event {#inventory_check_user_can_access_event}

**Callers (1):**

- `includes/inventory_check.php`:134 — `if (!inventory_check_user_can_access_event($user, $event)) {`

## inventory_check_event_require_access {#inventory_check_event_require_access}

**Callers (1):**

- `api/v1/inventory_check.php`:54 — `return inventory_check_event_require_access(ims_pdo(), $eventId, $user);`

## inventory_check_event_open {#inventory_check_event_open}

**Tables:** `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:108 — `api_ok(['event' => inventory_check_event_open(`

## inventory_check_event_close {#inventory_check_event_close}

**Tables:** `inventory_check_events`

**Callers (2):**

- `api/v1/inventory_check.php`:127 — `api_ok(['event' => inventory_check_event_close(`
- `api/v1/inventory_check.php`:365 — `$event = inventory_check_event_close($pdo, (int) $user['id'], $eventId);`

## inventory_check_event_cancel {#inventory_check_event_cancel}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`, `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:146 — `inventory_check_event_cancel(ims_pdo(), $eventId);`

## inventory_check_event_list {#inventory_check_event_list}

**Tables:** `inventory_check_events`, `inventory_check_scans`, `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:158 — `api_ok(inventory_check_event_list(`

## inventory_check_serialize_scan {#inventory_check_serialize_scan}

**Callers (2):**

- `includes/inventory_check.php`:435 — `'scan' => inventory_check_serialize_scan($dupRow),`
- `includes/inventory_check.php`:769 — `return inventory_check_serialize_scan($row);`

## inventory_check_require_doc_fields {#inventory_check_require_doc_fields}

**Callers (2):**

- `includes/inventory_check.php`:337 — `[$batchNo, $bottlingDate] = inventory_check_require_doc_fields($batchNo, $bottlingDate);`
- `includes/inventory_check.php`:550 — `[$newBatch, $newDate] = inventory_check_require_doc_fields($docBatch, $docDate);`

## inventory_check_scan {#inventory_check_scan}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:175 — `$res = inventory_check_scan(`

## inventory_check_packaging_by_batch {#inventory_check_packaging_by_batch}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `includes/inventory_check.php`:354 — `$pk = inventory_check_packaging_by_batch($pdo, $batchNo, $variant);`
- `includes/inventory_check.php`:556 — `$pk = inventory_check_packaging_by_batch(`

## inventory_check_scan_update {#inventory_check_scan_update}

**Tables:** `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:205 — `$scan = inventory_check_scan_update(`

## inventory_check_compare_preview {#inventory_check_compare_preview}

**Callers (2):**

- `api/v1/inventory_check.php`:304 — `api_ok(inventory_check_compare_preview(ims_pdo(), $eventId));`
- `api/v1/inventory_check.php`:329 — `api_ok(inventory_check_compare_preview($pdo, $ids[0], $ids));`

## inventory_check_resolve_honey {#inventory_check_resolve_honey}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:440 — `$honey = inventory_check_resolve_honey($pdo, $code);`

## inventory_check_resolve_dept {#inventory_check_resolve_dept}

**Callers (1):**

- `includes/inventory_check.php`:441 — `$resolved = $honey ?? inventory_check_resolve_dept($pdo, $code);`

## inventory_check_flag {#inventory_check_flag}

**Callers (2):**

- `includes/inventory_check.php`:703 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $unitStatus),`
- `includes/inventory_check.php`:740 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $row['unit_status'] !== null ? (string) $row['unit_status'] : ''),`

## inventory_check_scan_get {#inventory_check_scan_get}

**Tables:** `inventory_check_scans`, `users`

**Callers (4):**

- `includes/inventory_check.php`:392 — `$scan = inventory_check_scan_get($pdo, (int) $dupRow['id']);`
- `includes/inventory_check.php`:420 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:475 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:602 — `return inventory_check_scan_get($pdo, $scanId);`

## inventory_check_scan_remove {#inventory_check_scan_remove}

**Tables:** `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:240 — `inventory_check_scan_remove(`

## inventory_check_summary {#inventory_check_summary}

**Tables:** `inventory_check_scans`, `users`

**Callers (5):**

- `api/v1/inventory_check.php`:96 — `? inventory_check_summary($pdo, (int) $event['id'], null)`
- `api/v1/inventory_check.php`:193 — `'summary' => inventory_check_summary($pdo, $eventId, null),`
- `api/v1/inventory_check.php`:228 — `'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,`
- `api/v1/inventory_check.php`:253 — `'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,`
- `api/v1/inventory_check.php`:264 — `api_ok(inventory_check_summary(ims_pdo(), $eventId, null));`

## inventory_check_scan_log {#inventory_check_scan_log}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:285 — `api_ok(inventory_check_scan_log(`

## inventory_check_compare_run {#inventory_check_compare_run}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`

**Callers (2):**

- `api/v1/inventory_check.php`:344 — `api_ok(inventory_check_compare_run(`
- `api/v1/inventory_check.php`:364 — `$compare = inventory_check_compare_run($pdo, $eventId, (int) $user['id']);`

## inventory_check_compare_build {#inventory_check_compare_build}

**Tables:** `inventory_check_scans`

**Callers (2):**

- `includes/inventory_check.php`:621 — `$built = inventory_check_compare_build($pdo, $ids);`
- `includes/inventory_check.php`:944 — `$lines = inventory_check_compare_build($pdo, [$eventId]);`

## inventory_check_compare_honey_lines {#inventory_check_compare_honey_lines}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:1071 — `inventory_check_compare_honey_lines($pdo, $honeyCounted, $honeyMeta, $honeyBatches)`

## inventory_check_dept_system_qty {#inventory_check_dept_system_qty}

**Callers (1):**

- `includes/inventory_check.php`:1076 — `$systemQty = inventory_check_dept_system_qty($pdo, (string) $productKey);`

## inventory_check_compare_get {#inventory_check_compare_get}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`, `users`

**Callers (2):**

- `api/v1/inventory_check.php`:380 — `$compare = inventory_check_compare_get(ims_pdo(), (int) ($_GET['run_id'] ?? 0));`
- `includes/inventory_check.php`:998 — `return inventory_check_compare_get($pdo, $runId, $event);`

## inventory_check_batch_docs_build {#inventory_check_batch_docs_build}

**Tables:** `inventory_check_scans`

**Callers (2):**

- `includes/inventory_check.php`:659 — `'batches' => inventory_check_batch_docs_build($pdo, $ids),`
- `includes/inventory_check.php`:1255 — `'batches' => inventory_check_batch_docs_build($pdo, [$eventId]),`

## inventory_check_compare_list {#inventory_check_compare_list}

**Tables:** `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:398 — `api_ok(['runs' => inventory_check_compare_list(ims_pdo(), $eventId)]);`

