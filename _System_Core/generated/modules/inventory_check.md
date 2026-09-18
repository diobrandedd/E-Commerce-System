---
title: inventory_check
source: includes/inventory_check.php
generated: 2026-09-18T11:30:03+02:00
type: module
function_count: 37
---


# inventory_check

> Source: `includes/inventory_check.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/permissions.php`
- `includes/honey_bottle_barcodes.php`
- `includes/honey_inventory.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`
- `includes/inventory_edit_log.php`
- `includes/sku_barcodes.php`
- `includes/honey_inventory.php`
- `includes/inventory_check_apply.php`

## inventory_check_can_scan {#inventory_check_can_scan}

**Callers (1):**

- `api/v1/inventory_check.php`:11 — `if (!inventory_check_can_scan($user)) {`

## inventory_check_can_view_all {#inventory_check_can_view_all}

**Callers (6):**

- `api/v1/inventory_check.php`:21 — `if (!inventory_check_can_view_all($user)) {`
- `api/v1/inventory_check.php`:90 — `$viewAll = inventory_check_can_view_all($user);`
- `api/v1/inventory_check.php`:159 — `$onlyMine = inventory_check_can_view_all($user) ? null : (int) $user['id'];`
- `includes/inventory_check.php`:126 — `return inventory_check_can_view_all($user) || inventory_check_can_manage($user);`
- `includes/inventory_check.php`:1330 — `if (inventory_check_can_view_all($user) || inventory_check_can_manage($user)) {`
- `includes/inventory_check_apply.php`:412 — `if (!inventory_check_can_view_all($user)) {`

## inventory_check_can_manage {#inventory_check_can_manage}

**Callers (6):**

- `api/v1/inventory_check.php`:31 — `if (!inventory_check_can_manage($user)) {`
- `api/v1/inventory_check.php`:94 — `'can_manage' => inventory_check_can_manage($user),`
- `api/v1/inventory_check.php`:211 — `inventory_check_can_manage($user),`
- `api/v1/inventory_check.php`:246 — `inventory_check_can_manage($user)`
- `includes/inventory_check.php`:126 — `return inventory_check_can_view_all($user) || inventory_check_can_manage($user);`
- `includes/inventory_check.php`:1330 — `if (inventory_check_can_view_all($user) || inventory_check_can_manage($user)) {`

## inventory_check_serialize_event {#inventory_check_serialize_event}

**Callers (2):**

- `includes/inventory_check.php`:91 — `return inventory_check_serialize_event($row);`
- `includes/inventory_check.php`:173 — `return inventory_check_serialize_event($row);`

## inventory_check_event_fetch {#inventory_check_event_fetch}

**Tables:** `inventory_check_scans`, `inventory_check_compare_runs`, `inventory_check_events`, `users`

**Callers (4):**

- `includes/inventory_check.php`:86 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:168 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:187 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:322 — `$event = inventory_check_event_fetch($pdo, $eventId);`

## inventory_check_event_get {#inventory_check_event_get}

**Callers (12):**

- `api/v1/inventory_check.php`:325 — `$ev = inventory_check_event_get($pdo, $id);`
- `api/v1/inventory_check.php`:368 — `$ev = inventory_check_event_get($pdo, $eventId);`
- `api/v1/inventory_check.php`:399 — `$ev = inventory_check_event_get($pdo, $eid);`
- `includes/inventory_check.php`:112 — `return $id > 0 ? inventory_check_event_get($pdo, $id) : null;`
- `includes/inventory_check.php`:135 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:160 — `return inventory_check_event_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:179 — `return inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:586 — `$event = inventory_check_event_get($pdo, $ids[0]);`
- `includes/inventory_check.php`:769 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:1167 — `'event' => $event ?? inventory_check_event_get($pdo, $eventId),`
- `includes/inventory_check_apply.php`:23 — `$event = inventory_check_event_get($pdo, $primaryId);`
- `includes/inventory_check_apply.php`:438 — `$event = inventory_check_event_get($pdo, $eventIds[0]);`

## inventory_check_event_current {#inventory_check_event_current}

**Tables:** `inventory_check_events`

**Callers (2):**

- `api/v1/inventory_check.php`:89 — `$event = inventory_check_event_current($pdo, (int) $user['id']);`
- `includes/inventory_check.php`:150 — `$open = inventory_check_event_current($pdo, $userId);`

## inventory_check_user_can_access_event {#inventory_check_user_can_access_event}

**Callers (1):**

- `includes/inventory_check.php`:136 — `if (!inventory_check_user_can_access_event($user, $event)) {`

## inventory_check_event_require_access {#inventory_check_event_require_access}

**Callers (1):**

- `api/v1/inventory_check.php`:54 — `return inventory_check_event_require_access(ims_pdo(), $eventId, $user);`

## inventory_check_event_open {#inventory_check_event_open}

**Tables:** `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:110 — `api_ok(['event' => inventory_check_event_open(`

## inventory_check_event_close {#inventory_check_event_close}

**Tables:** `inventory_check_events`

**Callers (2):**

- `api/v1/inventory_check.php`:129 — `api_ok(['event' => inventory_check_event_close(`
- `includes/inventory_check_apply.php`:440 — `$event = inventory_check_event_close($pdo, $userId, $eventIds[0]);`

## inventory_check_event_cancel {#inventory_check_event_cancel}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`, `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:148 — `inventory_check_event_cancel(ims_pdo(), $eventId);`

## inventory_check_event_list {#inventory_check_event_list}

**Tables:** `inventory_check_events`, `inventory_check_scans`, `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:160 — `api_ok(inventory_check_event_list(`

## inventory_check_serialize_scan {#inventory_check_serialize_scan}

**Callers (2):**

- `includes/inventory_check.php`:436 — `'scan' => inventory_check_serialize_scan($dupRow),`
- `includes/inventory_check.php`:735 — `return inventory_check_serialize_scan($row);`

## inventory_check_optional_doc_fields {#inventory_check_optional_doc_fields}

**Callers (2):**

- `includes/inventory_check.php`:337 — `[$docBatch, $docDate] = inventory_check_optional_doc_fields($batchNo, $bottlingDate);`
- `includes/inventory_check.php`:559 — `[$newBatch, $newDate] = inventory_check_optional_doc_fields($batchNo, $bottlingDate);`

## inventory_check_scan {#inventory_check_scan}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:177 — `$res = inventory_check_scan(`

## inventory_check_packaging_by_batch {#inventory_check_packaging_by_batch}

**Tables:** `honey_packaging_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## inventory_check_scan_update {#inventory_check_scan_update}

**Tables:** `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:207 — `$scan = inventory_check_scan_update(`

## inventory_check_compare_preview {#inventory_check_compare_preview}

**Callers (2):**

- `api/v1/inventory_check.php`:306 — `api_ok(inventory_check_compare_preview(ims_pdo(), $eventId));`
- `api/v1/inventory_check.php`:331 — `api_ok(inventory_check_compare_preview($pdo, $ids[0], $ids));`

## inventory_check_resolve_honey {#inventory_check_resolve_honey}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:441 — `$honey = inventory_check_resolve_honey($pdo, $code);`

## inventory_check_resolve_dept {#inventory_check_resolve_dept}

**Callers (1):**

- `includes/inventory_check.php`:442 — `$resolved = $honey ?? inventory_check_resolve_dept($pdo, $code);`

## inventory_check_flag {#inventory_check_flag}

**Callers (2):**

- `includes/inventory_check.php`:669 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $unitStatus),`
- `includes/inventory_check.php`:706 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $row['unit_status'] !== null ? (string) $row['unit_status'] : ''),`

## inventory_check_scan_get {#inventory_check_scan_get}

**Tables:** `inventory_check_scans`, `users`

**Callers (4):**

- `includes/inventory_check.php`:393 — `$scan = inventory_check_scan_get($pdo, (int) $dupRow['id']);`
- `includes/inventory_check.php`:421 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:482 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:568 — `return inventory_check_scan_get($pdo, $scanId);`

## inventory_check_scan_remove {#inventory_check_scan_remove}

**Tables:** `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:242 — `inventory_check_scan_remove(`

## inventory_check_summary {#inventory_check_summary}

**Tables:** `inventory_check_scans`, `users`

**Callers (5):**

- `api/v1/inventory_check.php`:98 — `? inventory_check_summary($pdo, (int) $event['id'], null)`
- `api/v1/inventory_check.php`:195 — `'summary' => inventory_check_summary($pdo, $eventId, null),`
- `api/v1/inventory_check.php`:230 — `'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,`
- `api/v1/inventory_check.php`:255 — `'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,`
- `api/v1/inventory_check.php`:266 — `api_ok(inventory_check_summary(ims_pdo(), $eventId, null));`

## inventory_check_scan_log {#inventory_check_scan_log}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:287 — `api_ok(inventory_check_scan_log(`

## inventory_check_compare_run {#inventory_check_compare_run}

**Callers (1):**

- `api/v1/inventory_check.php`:346 — `api_ok(inventory_check_compare_run(`

## inventory_check_compare_build {#inventory_check_compare_build}

**Tables:** `inventory_check_scans`

**Callers (2):**

- `includes/inventory_check.php`:587 — `$built = inventory_check_compare_build($pdo, $ids);`
- `includes/inventory_check_apply.php`:24 — `$lines = inventory_check_compare_build($pdo, $eventIds);`

## inventory_check_compare_honey_lines {#inventory_check_compare_honey_lines}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:982 — `inventory_check_compare_honey_lines($pdo, $honeyCounted, $honeyMeta, $honeyBatches)`

## inventory_check_dept_system_qty {#inventory_check_dept_system_qty}

**Callers (1):**

- `includes/inventory_check.php`:987 — `$systemQty = inventory_check_dept_system_qty($pdo, (string) $productKey);`

## inventory_check_compare_get {#inventory_check_compare_get}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`, `users`

**Callers (3):**

- `api/v1/inventory_check.php`:437 — `api_ok(inventory_check_compare_get($pdo, $runId));`
- `includes/inventory_check_apply.php`:81 — `return inventory_check_compare_get($pdo, $runId, $event, $eventIds);`
- `includes/inventory_check_apply.php`:450 — `'compare' => inventory_check_compare_get($pdo, $runId, $event, $eventIds),`

## inventory_check_batch_docs_build {#inventory_check_batch_docs_build}

**Tables:** `inventory_check_scans`

**Callers (2):**

- `includes/inventory_check.php`:625 — `'batches' => inventory_check_batch_docs_build($pdo, $ids),`
- `includes/inventory_check.php`:1166 — `'batches' => inventory_check_batch_docs_build($pdo, $docIds),`

## inventory_check_compare_list {#inventory_check_compare_list}

**Tables:** `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:452 — `api_ok(['runs' => inventory_check_compare_list(ims_pdo(), $eventId)]);`

## inventory_check_parse_merged_event_ids {#inventory_check_parse_merged_event_ids}

**Callers (3):**

- `includes/inventory_check.php`:1333 — `$ids = inventory_check_parse_merged_event_ids(`
- `includes/inventory_check.php`:1381 — `$sessionIds = inventory_check_parse_merged_event_ids(`
- `includes/inventory_check_apply.php`:471 — `$eventIds = inventory_check_parse_merged_event_ids($mergedCsv, $eventId);`

## inventory_check_event_summaries_by_ids {#inventory_check_event_summaries_by_ids}

**Tables:** `inventory_check_events`, `users`

**Callers (1):**

- `includes/inventory_check.php`:1385 — `$sessions = inventory_check_event_summaries_by_ids($pdo, $sessionIds);`

## inventory_check_user_can_access_compare_run {#inventory_check_user_can_access_compare_run}

**Tables:** `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:434 — `if (!inventory_check_user_can_access_compare_run($pdo, $user, $runRow)) {`

## inventory_check_applied_runs_list {#inventory_check_applied_runs_list}

**Tables:** `inventory_check_compare_runs`, `users`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:459 — `api_ok(inventory_check_applied_runs_list(`

