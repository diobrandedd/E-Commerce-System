---
title: inventory_check
source: includes/inventory_check.php
generated: 2026-10-21T11:22:15+02:00
type: module
function_count: 42
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
- `includes/dept_raw_freebies.php`
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
- `api/v1/inventory_check.php`:91 — `$viewAll = inventory_check_can_view_all($user);`
- `api/v1/inventory_check.php`:160 — `$onlyMine = inventory_check_can_view_all($user) ? null : (int) $user['id'];`
- `includes/inventory_check.php`:127 — `return inventory_check_can_view_all($user) || inventory_check_can_manage($user);`
- `includes/inventory_check.php`:1507 — `if (inventory_check_can_view_all($user) || inventory_check_can_manage($user)) {`
- `includes/inventory_check_apply.php`:393 — `if (!inventory_check_can_view_all($user)) {`

## inventory_check_can_manage {#inventory_check_can_manage}

**Callers (6):**

- `api/v1/inventory_check.php`:31 — `if (!inventory_check_can_manage($user)) {`
- `api/v1/inventory_check.php`:95 — `'can_manage' => inventory_check_can_manage($user),`
- `api/v1/inventory_check.php`:251 — `inventory_check_can_manage($user),`
- `api/v1/inventory_check.php`:286 — `inventory_check_can_manage($user)`
- `includes/inventory_check.php`:127 — `return inventory_check_can_view_all($user) || inventory_check_can_manage($user);`
- `includes/inventory_check.php`:1507 — `if (inventory_check_can_view_all($user) || inventory_check_can_manage($user)) {`

## inventory_check_serialize_event {#inventory_check_serialize_event}

**Callers (2):**

- `includes/inventory_check.php`:92 — `return inventory_check_serialize_event($row);`
- `includes/inventory_check.php`:174 — `return inventory_check_serialize_event($row);`

## inventory_check_event_fetch {#inventory_check_event_fetch}

**Tables:** `inventory_check_scans`, `inventory_check_compare_runs`, `inventory_check_events`, `users`

**Callers (5):**

- `includes/inventory_check.php`:87 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:169 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:188 — `$row = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:394 — `$event = inventory_check_event_fetch($pdo, $eventId);`
- `includes/inventory_check.php`:460 — `$event = inventory_check_event_fetch($pdo, $eventId);`

## inventory_check_event_get {#inventory_check_event_get}

**Callers (12):**

- `api/v1/inventory_check.php`:365 — `$ev = inventory_check_event_get($pdo, $id);`
- `api/v1/inventory_check.php`:408 — `$ev = inventory_check_event_get($pdo, $eventId);`
- `api/v1/inventory_check.php`:439 — `$ev = inventory_check_event_get($pdo, $eid);`
- `includes/inventory_check.php`:113 — `return $id > 0 ? inventory_check_event_get($pdo, $id) : null;`
- `includes/inventory_check.php`:136 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:161 — `return inventory_check_event_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:180 — `return inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:724 — `$event = inventory_check_event_get($pdo, $ids[0]);`
- `includes/inventory_check.php`:907 — `$event = inventory_check_event_get($pdo, $eventId);`
- `includes/inventory_check.php`:1344 — `'event' => $event ?? inventory_check_event_get($pdo, $eventId),`
- `includes/inventory_check_apply.php`:23 — `$event = inventory_check_event_get($pdo, $primaryId);`
- `includes/inventory_check_apply.php`:419 — `$event = inventory_check_event_get($pdo, $eventIds[0]);`

## inventory_check_event_current {#inventory_check_event_current}

**Tables:** `inventory_check_events`

**Callers (2):**

- `api/v1/inventory_check.php`:90 — `$event = inventory_check_event_current($pdo, (int) $user['id']);`
- `includes/inventory_check.php`:151 — `$open = inventory_check_event_current($pdo, $userId);`

## inventory_check_user_can_access_event {#inventory_check_user_can_access_event}

**Callers (1):**

- `includes/inventory_check.php`:137 — `if (!inventory_check_user_can_access_event($user, $event)) {`

## inventory_check_event_require_access {#inventory_check_event_require_access}

**Callers (1):**

- `api/v1/inventory_check.php`:54 — `return inventory_check_event_require_access(ims_pdo(), $eventId, $user);`

## inventory_check_event_open {#inventory_check_event_open}

**Tables:** `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:111 — `api_ok(['event' => inventory_check_event_open(`

## inventory_check_event_close {#inventory_check_event_close}

**Tables:** `inventory_check_events`

**Callers (2):**

- `api/v1/inventory_check.php`:130 — `api_ok(['event' => inventory_check_event_close(`
- `includes/inventory_check_apply.php`:421 — `$event = inventory_check_event_close($pdo, $userId, $eventIds[0]);`

## inventory_check_event_cancel {#inventory_check_event_cancel}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`, `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:149 — `inventory_check_event_cancel(ims_pdo(), $eventId);`

## inventory_check_history_date_bound {#inventory_check_history_date_bound}

**Callers (2):**

- `includes/inventory_check.php`:233 — `$from = inventory_check_history_date_bound($from);`
- `includes/inventory_check.php`:234 — `$to = inventory_check_history_date_bound($to);`

## inventory_check_history_date_span {#inventory_check_history_date_span}

**Callers (4):**

- `api/v1/inventory_check.php`:162 — `[$from, $to] = inventory_check_history_date_span(`
- `api/v1/inventory_check.php`:500 — `[$from, $to] = inventory_check_history_date_span(`
- `includes/inventory_check.php`:255 — `[$fromDate, $toDate] = inventory_check_history_date_span($fromDate, $toDate);`
- `includes/inventory_check.php`:1541 — `[$fromDate, $toDate] = inventory_check_history_date_span($fromDate, $toDate);`

## inventory_check_event_list {#inventory_check_event_list}

**Tables:** `inventory_check_events`, `inventory_check_scans`, `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:169 — `api_ok(inventory_check_event_list(`

## inventory_check_freebie_product_label {#inventory_check_freebie_product_label}

**Callers (4):**

- `includes/inventory_check.php`:316 — `return inventory_check_freebie_product_label($productKey);`
- `includes/inventory_check.php`:412 — `$label = inventory_check_freebie_product_label($productKey);`
- `includes/inventory_check.php`:1117 — `$scopeLabels[] = inventory_check_freebie_product_label($productKey);`
- `includes/inventory_check.php`:1174 — `$label = inventory_check_freebie_product_label((string) $productKey);`

## inventory_check_scan_product_label {#inventory_check_scan_product_label}

**Callers (4):**

- `includes/inventory_check.php`:332 — `'product_label' => inventory_check_scan_product_label($kind, $productKey),`
- `includes/inventory_check.php`:940 — `$productLabel = inventory_check_scan_product_label($kind, $productKey);`
- `includes/inventory_check.php`:1315 — `'product_label' => inventory_check_scan_product_label($kind, (string) $row['product_key']),`
- `includes/inventory_check.php`:1392 — `$productLabel = inventory_check_scan_product_label($kind, $productKey);`

## inventory_check_serialize_scan {#inventory_check_serialize_scan}

**Callers (2):**

- `includes/inventory_check.php`:574 — `'scan' => inventory_check_serialize_scan($dupRow),`
- `includes/inventory_check.php`:873 — `return inventory_check_serialize_scan($row);`

## inventory_check_optional_doc_fields {#inventory_check_optional_doc_fields}

**Callers (2):**

- `includes/inventory_check.php`:475 — `[$docBatch, $docDate] = inventory_check_optional_doc_fields($batchNo, $bottlingDate);`
- `includes/inventory_check.php`:697 — `[$newBatch, $newDate] = inventory_check_optional_doc_fields($batchNo, $bottlingDate);`

## inventory_check_add_freebie {#inventory_check_add_freebie}

**Tables:** `inventory_check_scans`

**Callers (1):**

- `api/v1/inventory_check.php`:219 — `$res = inventory_check_add_freebie(`

## inventory_check_scan {#inventory_check_scan}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:188 — `$res = inventory_check_scan(`

## inventory_check_packaging_by_batch {#inventory_check_packaging_by_batch}

**Tables:** `honey_packaging_rows`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## inventory_check_scan_update {#inventory_check_scan_update}

**Tables:** `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:247 — `$scan = inventory_check_scan_update(`

## inventory_check_compare_preview {#inventory_check_compare_preview}

**Callers (2):**

- `api/v1/inventory_check.php`:346 — `api_ok(inventory_check_compare_preview(ims_pdo(), $eventId));`
- `api/v1/inventory_check.php`:371 — `api_ok(inventory_check_compare_preview($pdo, $ids[0], $ids));`

## inventory_check_resolve_honey {#inventory_check_resolve_honey}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:579 — `$honey = inventory_check_resolve_honey($pdo, $code);`

## inventory_check_resolve_dept {#inventory_check_resolve_dept}

**Callers (1):**

- `includes/inventory_check.php`:580 — `$resolved = $honey ?? inventory_check_resolve_dept($pdo, $code);`

## inventory_check_flag {#inventory_check_flag}

**Callers (2):**

- `includes/inventory_check.php`:807 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $unitStatus),`
- `includes/inventory_check.php`:844 — `'flag' => inventory_check_flag((string) $row['barcode_status'], $row['unit_status'] !== null ? (string) $row['unit_status'] : ''),`

## inventory_check_scan_get {#inventory_check_scan_get}

**Tables:** `inventory_check_scans`, `users`

**Callers (6):**

- `includes/inventory_check.php`:427 — `$scan = inventory_check_scan_get($pdo, $existingId);`
- `includes/inventory_check.php`:442 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:531 — `$scan = inventory_check_scan_get($pdo, (int) $dupRow['id']);`
- `includes/inventory_check.php`:559 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:620 — `$scan = inventory_check_scan_get($pdo, (int) $pdo->lastInsertId());`
- `includes/inventory_check.php`:706 — `return inventory_check_scan_get($pdo, $scanId);`

## inventory_check_scan_remove {#inventory_check_scan_remove}

**Tables:** `inventory_check_scans`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:282 — `inventory_check_scan_remove(`

## inventory_check_summary {#inventory_check_summary}

**Tables:** `inventory_check_scans`, `users`

**Callers (6):**

- `api/v1/inventory_check.php`:99 — `? inventory_check_summary($pdo, (int) $event['id'], null)`
- `api/v1/inventory_check.php`:206 — `'summary' => inventory_check_summary($pdo, $eventId, null),`
- `api/v1/inventory_check.php`:235 — `'summary' => inventory_check_summary($pdo, $eventId, null),`
- `api/v1/inventory_check.php`:270 — `'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,`
- `api/v1/inventory_check.php`:295 — `'summary' => $eventId > 0 ? inventory_check_summary($pdo, $eventId, null) : null,`
- `api/v1/inventory_check.php`:306 — `api_ok(inventory_check_summary(ims_pdo(), $eventId, null));`

## inventory_check_scan_log {#inventory_check_scan_log}

**Tables:** `inventory_check_scans`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:327 — `api_ok(inventory_check_scan_log(`

## inventory_check_compare_run {#inventory_check_compare_run}

**Callers (1):**

- `api/v1/inventory_check.php`:386 — `api_ok(inventory_check_compare_run(`

## inventory_check_compare_build {#inventory_check_compare_build}

**Tables:** `inventory_check_scans`

**Callers (2):**

- `includes/inventory_check.php`:725 — `$built = inventory_check_compare_build($pdo, $ids);`
- `includes/inventory_check_apply.php`:24 — `$lines = inventory_check_compare_build($pdo, $eventIds);`

## inventory_check_compare_honey_lines {#inventory_check_compare_honey_lines}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/inventory_check.php`:1134 — `inventory_check_compare_honey_lines($pdo, $honeyCounted, $honeyMeta, $honeyBatches)`

## inventory_check_dept_system_qty {#inventory_check_dept_system_qty}

**Callers (1):**

- `includes/inventory_check.php`:1139 — `$systemQty = inventory_check_dept_system_qty($pdo, (string) $productKey);`

## inventory_check_compare_get {#inventory_check_compare_get}

**Tables:** `inventory_check_compare_runs`, `inventory_check_variance_lines`, `users`

**Callers (3):**

- `api/v1/inventory_check.php`:477 — `api_ok(inventory_check_compare_get($pdo, $runId));`
- `includes/inventory_check_apply.php`:81 — `return inventory_check_compare_get($pdo, $runId, $event, $eventIds);`
- `includes/inventory_check_apply.php`:431 — `'compare' => inventory_check_compare_get($pdo, $runId, $event, $eventIds),`

## inventory_check_batch_docs_build {#inventory_check_batch_docs_build}

**Tables:** `inventory_check_scans`

**Callers (2):**

- `includes/inventory_check.php`:763 — `'batches' => inventory_check_batch_docs_build($pdo, $ids),`
- `includes/inventory_check.php`:1343 — `'batches' => inventory_check_batch_docs_build($pdo, $docIds),`

## inventory_check_compare_list {#inventory_check_compare_list}

**Tables:** `inventory_check_compare_runs`, `users`

**Callers (1):**

- `api/v1/inventory_check.php`:492 — `api_ok(['runs' => inventory_check_compare_list(ims_pdo(), $eventId)]);`

## inventory_check_parse_merged_event_ids {#inventory_check_parse_merged_event_ids}

**Callers (3):**

- `includes/inventory_check.php`:1510 — `$ids = inventory_check_parse_merged_event_ids(`
- `includes/inventory_check.php`:1578 — `$sessionIds = inventory_check_parse_merged_event_ids(`
- `includes/inventory_check_apply.php`:452 — `$eventIds = inventory_check_parse_merged_event_ids($mergedCsv, $eventId);`

## inventory_check_event_summaries_by_ids {#inventory_check_event_summaries_by_ids}

**Tables:** `inventory_check_events`, `users`

**Callers (1):**

- `includes/inventory_check.php`:1582 — `$sessions = inventory_check_event_summaries_by_ids($pdo, $sessionIds);`

## inventory_check_user_can_access_compare_run {#inventory_check_user_can_access_compare_run}

**Tables:** `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:474 — `if (!inventory_check_user_can_access_compare_run($pdo, $user, $runRow)) {`

## inventory_check_applied_runs_list {#inventory_check_applied_runs_list}

**Tables:** `inventory_check_compare_runs`, `users`, `inventory_check_events`

**Callers (1):**

- `api/v1/inventory_check.php`:507 — `api_ok(inventory_check_applied_runs_list(`

