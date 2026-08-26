---
title: honey_bottle_barcodes
source: includes/honey_bottle_barcodes.php
generated: 2026-08-26T04:06:52+02:00
type: module
function_count: 58
---


# honey_bottle_barcodes

> Source: `includes/honey_bottle_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/rts_schema.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_inventory_sync.php`

## honey_bottle_size_label {#honey_bottle_size_label}

**Callers (18):**

- `api/v1/bottle_barcodes.php`:104 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `api/v1/rts_barcode_labels.php`:55 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `includes/barcode_verify.php`:71 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/finished_leak_damage.php`:34 — `$size = honey_bottle_size_label((string) ($row['size'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:140 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:171 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:649 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:832 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:1128 — `$sizeLabel = honey_bottle_size_label($size);`
- `includes/honey_bottle_barcodes.php`:2154 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/inventory_check.php`:209 — `'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,`
- `includes/inventory_check.php`:478 — `'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,`
- `includes/inventory_check.php`:770 — `'size_label' => $size !== '' ? honey_bottle_size_label($size) : null,`
- `includes/inventory_check.php`:829 — `'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,`
- `includes/pack_cancel_restock.php`:87 — `$sizeLabel = honey_bottle_size_label($size);`
- `includes/pos_unified.php`:144 — `ucfirst($variant) . ' · ' . honey_bottle_size_label($size)`
- `includes/pos_unified.php`:150 — `'size_label' => honey_bottle_size_label($size),`
- `includes/rts_barcode_labels.php`:99 — `'size_label' => $size !== null ? honey_bottle_size_label($size) : null,`

## honey_bottle_can_request {#honey_bottle_can_request}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:24 — `if (!honey_bottle_can_request($user)) {`
- `api/v1/bottle_barcodes.php`:165 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:243 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`

## honey_bottle_can_generate {#honey_bottle_can_generate}

**Callers (4):**

- `api/v1/bottle_barcodes.php`:243 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`
- `includes/honey_bottle_barcodes.php`:62 — `return honey_bottle_can_generate($user);`
- `includes/honey_bottle_barcodes.php`:345 — `if ($req['generated_count'] > 0 && !honey_bottle_can_generate($role, $deptKey)) {`
- `includes/honey_bottle_barcodes.php`:348 — `if ((int) $req['requested_by'] !== $userId && !honey_bottle_can_generate($role, $deptKey)) {`

## honey_bottle_can_scan {#honey_bottle_can_scan}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:44 — `if (!honey_bottle_can_scan($user)) {`
- `api/v1/bottle_barcodes.php`:54 — `if (!honey_bottle_can_pack_scan($user) && !honey_bottle_can_scan($user)) {`

## honey_bottle_can_pack_scan {#honey_bottle_can_pack_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:54 — `if (!honey_bottle_can_pack_scan($user) && !honey_bottle_can_scan($user)) {`

## honey_bottle_user_dept_key {#honey_bottle_user_dept_key}

**Tables:** `departments`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:234 — `honey_bottle_user_dept_key($user)`

## honey_bottle_user_can_generate {#honey_bottle_user_can_generate}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:34 — `if (!honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:165 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`

## honey_bottle_normalize_code {#honey_bottle_normalize_code}

**Callers (9):**

- `includes/finished_leak_damage.php`:94 — `$code = honey_bottle_normalize_code((string) ($row['code'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:561 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:607 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:862 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:963 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1391 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1878 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1952 — `$code = honey_bottle_normalize_code((string) $raw);`
- `includes/pack_cancel_restock.php`:177 — `$code = honey_bottle_normalize_code($code);`

## honey_bottle_random_code {#honey_bottle_random_code}

**Tables:** `honey_bottle_codes_issued`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:439 — `$insC->execute([$batchId, honey_bottle_random_code($pdo, $used), $seq, $size]);`

## honey_bottle_serialize_request {#honey_bottle_serialize_request}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:289 — `return honey_bottle_serialize_request($row);`
- `includes/honey_bottle_barcodes.php`:333 — `$rows[] = honey_bottle_serialize_request($row);`

## honey_bottle_serialize_batch {#honey_bottle_serialize_batch}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:516 — `return honey_bottle_serialize_batch($row);`
- `includes/honey_bottle_barcodes.php`:539 — `$rows[] = honey_bottle_serialize_batch($row);`

## honey_bottle_next_sequence {#honey_bottle_next_sequence}

**Tables:** `honey_bottle_barcode_seq`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:411 — `$start = honey_bottle_next_sequence($pdo, $qty);`

## honey_bottle_adjust_aggregate {#honey_bottle_adjust_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (6):**

- `includes/finished_leak_damage.php`:139 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, -1.0);`
- `includes/honey_bottle_barcodes.php`:914 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $row['size'], 1);`
- `includes/honey_bottle_barcodes.php`:1621 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, -1);`
- `includes/honey_bottle_barcodes.php`:1839 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], -1);`
- `includes/honey_bottle_barcodes.php`:1910 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], 1);`
- `includes/pack_cancel_restock.php`:386 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, 1.0);`

## honey_bottle_request_create {#honey_bottle_request_create}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:192 — `$row = honey_bottle_request_create(`

## honey_bottle_request_get {#honey_bottle_request_get}

**Tables:** `honey_barcode_batches`, `honey_bottle_requests`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (5):**

- `api/v1/bottle_barcodes.php`:220 — `api_ok(['request' => honey_bottle_request_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:261 — `return honey_bottle_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:341 — `$req = honey_bottle_request_get($pdo, $id);`
- `includes/honey_bottle_barcodes.php`:470 — `$req = honey_bottle_request_get($pdo, $requestId);`
- `includes/honey_bottle_barcodes.php`:949 — `$out['request'] = honey_bottle_request_get($pdo, (int) $row['request_id']);`

## honey_bottle_request_list {#honey_bottle_request_list}

**Tables:** `honey_bottle_requests`, `honey_barcode_batches`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:212 — `api_ok(honey_bottle_request_list(ims_pdo(), $status, $filterUser, $page, 25));`

## honey_bottle_request_cancel {#honey_bottle_request_cancel}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:229 — `honey_bottle_request_cancel(`

## honey_bottle_pending_request_count {#honey_bottle_pending_request_count}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:246 — `api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);`

## honey_bottle_generate_batch {#honey_bottle_generate_batch}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`, `honey_bottle_requests`

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:487 — `return honey_bottle_generate_batch(`
- `includes/honey_bottle_barcodes.php`:2068 — `$created[] = honey_bottle_generate_batch(`
- `includes/rts_barcode_labels.php`:264 — `$batch = honey_bottle_generate_batch(`

## honey_bottle_generate_for_request {#honey_bottle_generate_for_request}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:261 — `$batch = honey_bottle_generate_for_request(`

## honey_bottle_batch_get {#honey_bottle_batch_get}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (4):**

- `api/v1/bottle_barcodes.php`:284 — `api_ok(['batch' => honey_bottle_batch_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:457 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:555 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:2359 — `$batch = honey_bottle_batch_get($pdo, $batchId);`

## honey_bottle_batch_list {#honey_bottle_batch_list}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:276 — `api_ok(['rows' => honey_bottle_batch_list(ims_pdo(), $source)]);`

## honey_bottle_mark_printed {#honey_bottle_mark_printed}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:293 — `api_ok(['batch' => honey_bottle_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);`
- `includes/rts_barcode_labels.php`:336 — `honey_bottle_mark_printed($pdo, (int) $line['honey_batch_id']);`

## honey_bottle_void_code {#honey_bottle_void_code}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:316 — `honey_bottle_void_code(`

## honey_bottle_fetch_code_row {#honey_bottle_fetch_code_row}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (9):**

- `api/v1/rts.php`:191 — `$honeyRow = honey_bottle_fetch_code_row(ims_pdo(), $code);`
- `includes/finished_leak_damage.php`:70 — `$honeyRow = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:630 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:944 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1050 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1396 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1787 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1956 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/pack_cancel_restock.php`:183 — `$honey = honey_bottle_fetch_code_row($pdo, $code);`

## honey_bottle_lookup {#honey_bottle_lookup}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:330 — `api_ok(['barcode' => honey_bottle_lookup(ims_pdo(), $code)]);`
- `includes/honey_bottle_barcodes.php`:1933 — `return honey_bottle_lookup($pdo, $code);`
- `includes/honey_inventory.php`:813 — `$lookup = honey_bottle_lookup($pdo, strtoupper($query));`

## honey_bottle_serialize_lookup {#honey_bottle_serialize_lookup}

**Callers (11):**

- `includes/honey_bottle_barcodes.php`:635 — `return honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:945 — `$out = honey_bottle_serialize_lookup($fresh ?? $row);`
- `includes/honey_bottle_barcodes.php`:1051 — `$out = honey_bottle_serialize_lookup($fresh ?? []);`
- `includes/honey_bottle_barcodes.php`:1414 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1425 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1433 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1440 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1450 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1791 — `$lookup = honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:1982 — `$received[] = honey_bottle_serialize_lookup($row);`
- `includes/pack_cancel_restock.php`:197 — `$lookup = honey_bottle_serialize_lookup($row);`

## honey_bottle_scan_report_date {#honey_bottle_scan_report_date}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:685 — `$scanDate = honey_bottle_scan_report_date($scanDate);`
- `includes/honey_bottle_barcodes.php`:795 — `$scanDate = honey_bottle_scan_report_date($scanDate);`

## honey_bottle_packaging_scan_report {#honey_bottle_packaging_scan_report}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:138 — `api_ok(honey_bottle_packaging_scan_report(`

## honey_bottle_packaging_scan_log {#honey_bottle_packaging_scan_log}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:124 — `api_ok(honey_bottle_packaging_scan_log(`

## honey_bottle_scan_stock_in {#honey_bottle_scan_stock_in}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_barcode_batches`, `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:340 — `api_ok(honey_bottle_scan_stock_in(ims_pdo(), (int) $user['id'], (string) ($in['code'] ?? ''), $adopt));`

## honey_bottle_rts_stock_in_scan {#honey_bottle_rts_stock_in_scan}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (1):**

- `api/v1/rts.php`:195 — `'unit' => honey_bottle_rts_stock_in_scan(ims_pdo(), (int) $user['id'], $code),`

## honey_bottle_pack_session_start {#honey_bottle_pack_session_start}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:348 — `api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);`

## honey_bottle_pack_session_get {#honey_bottle_pack_session_get}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `users`, `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (19):**

- `api/v1/bottle_barcodes.php`:356 — `api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:1069 — `return honey_bottle_pack_session_get($pdo, $existing);`
- `includes/honey_bottle_barcodes.php`:1074 — `return honey_bottle_pack_session_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:1358 — `$detail = honey_bottle_pack_session_get($pdo, (int) $r['id']);`
- `includes/honey_bottle_barcodes.php`:1392 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1413 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1424 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1432 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1439 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1449 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1475 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1501 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1513 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1522 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1529 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1541 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1684 — `return honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1694 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1711 — `return honey_bottle_pack_session_get($pdo, $sessionId);`

## honey_bottle_pack_session_list {#honey_bottle_pack_session_list}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_sessions`, `honey_bottle_units`, `honey_packaging_rows`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:364 — `api_ok(honey_bottle_pack_session_list(`

## honey_bottle_pack_session_scan {#honey_bottle_pack_session_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:383 — `api_ok(honey_bottle_pack_session_scan(`

## honey_bottle_pack_session_scan_honey {#honey_bottle_pack_session_scan_honey}

**Tables:** `honey_bottle_pack_session_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1398 — `return honey_bottle_pack_session_scan_honey($pdo, $sessionId, $row);`

## honey_bottle_pack_session_scan_dept {#honey_bottle_pack_session_scan_dept}

**Tables:** `honey_bottle_pack_session_dept_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1401 — `return honey_bottle_pack_session_scan_dept($pdo, $sessionId, $code);`

## honey_bottle_pack_session_confirm {#honey_bottle_pack_session_confirm}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:414 — `api_ok(['session' => honey_bottle_pack_session_confirm(`

## honey_bottle_pack_session_remove_item {#honey_bottle_pack_session_remove_item}

**Tables:** `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_session_items`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:397 — `api_ok(['session' => honey_bottle_pack_session_remove_item(`

## honey_bottle_pack_session_discard {#honey_bottle_pack_session_discard}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:427 — `honey_bottle_pack_session_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));`

## honey_bottle_write_sale_log {#honey_bottle_write_sale_log}

**Tables:** `honey_sales_log`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:1623 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:1856 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:1912 — `honey_bottle_write_sale_log($pdo, [`
- `includes/pack_cancel_restock.php`:396 — `honey_bottle_write_sale_log($pdo, [`

## honey_bottle_pos_lookup {#honey_bottle_pos_lookup}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:436 — `api_ok(['barcode' => honey_bottle_pos_lookup(ims_pdo(), $code)]);`
- `includes/pos_unified.php`:133 — `$honey = honey_bottle_pos_lookup($pdo, $code);`

## honey_bottle_pos_consume {#honey_bottle_pos_consume}

**Tables:** `honey_bottle_units`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_pos.php`:412 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/honey_pos.php`:639 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/pos_unified.php`:347 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`
- `includes/pos_unified.php`:479 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`

## honey_bottle_opened_return_scan {#honey_bottle_opened_return_scan}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:445 — `api_ok(['barcode' => honey_bottle_opened_return_scan(`

## honey_bottle_rts_receive {#honey_bottle_rts_receive}

**Tables:** `honey_bottle_units`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:463 — `api_ok(honey_bottle_rts_receive(ims_pdo(), (int) api_require_ims_user()['id'], $codes, $pool));`
- `includes/rts_case_handlers.php`:516 — `$honeyRx = honey_bottle_rts_receive($pdo, 0, $bottleCodes, $pool);`

## honey_bottle_backfill_preview {#honey_bottle_backfill_preview}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:470 — `api_ok(honey_bottle_backfill_preview(ims_pdo()));`
- `includes/honey_bottle_barcodes.php`:2055 — `$preview = honey_bottle_backfill_preview($pdo);`

## honey_bottle_unit_count_for_row {#honey_bottle_unit_count_for_row}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:2007 — `$already = honey_bottle_unit_count_for_row($pdo, (int) $pk['id'], $size);`

## honey_bottle_backfill_generate {#honey_bottle_backfill_generate}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:478 — `$created = honey_bottle_backfill_generate(ims_pdo(), (int) $user['id']);`

## honey_bottle_reconcile {#honey_bottle_reconcile}

**Tables:** `honey_packaging_rows`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:486 — `api_ok(honey_bottle_reconcile(ims_pdo()));`

## honey_bottle_stock_summary {#honey_bottle_stock_summary}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:493 — `api_ok(['rows' => honey_bottle_stock_summary(ims_pdo())]);`

## honey_bottle_code128_svg {#honey_bottle_code128_svg}

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:828 — `$svg = honey_bottle_code128_svg($code);`
- `includes/honey_bottle_barcodes.php`:2371 — `$svg = honey_bottle_code128_svg($code);`
- `includes/rts_barcode_labels.php`:458 — `$svg = honey_bottle_code128_svg($code);`

## honey_bottle_batch_codes {#honey_bottle_batch_codes}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:2360 — `$codes = honey_bottle_batch_codes($pdo, $batchId);`
- `includes/rts_barcode_labels.php`:448 — `$codes = honey_bottle_batch_codes($pdo, (int) $line['honey_batch_id']);`

## honey_bottle_ensure_packaging_shell {#honey_bottle_ensure_packaging_shell}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:152 — `$pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);`
- `includes/honey_bottle_barcodes.php`:476 — `$shell = honey_bottle_ensure_packaging_shell($pdo, $userId, $productionRowId);`

## honey_bottle_create_custom_packaging {#honey_bottle_create_custom_packaging}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:170 — `$pk = honey_bottle_create_custom_packaging(`
- `includes/rts_barcode_labels.php`:255 — `$pk = honey_bottle_create_custom_packaging(`

## honey_bottle_serialize_packaging_option {#honey_bottle_serialize_packaging_option}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:154 — `'packaging' => honey_bottle_serialize_packaging_option($pk, $before === null),`
- `api/v1/bottle_barcodes.php`:180 — `'packaging' => honey_bottle_serialize_packaging_option($pk, !$reused),`
- `includes/honey_bottle_barcodes.php`:2491 — `$rows[] = honey_bottle_serialize_packaging_option($r, false);`

## honey_bottle_label_sheet_html {#honey_bottle_label_sheet_html}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:304 — `$html = honey_bottle_label_sheet_html(ims_pdo(), $id);`
- `includes/dept_pouch_barcodes.php`:869 — `honey_bottle_label_sheet_html(). */`

## honey_bottle_packaging_options {#honey_bottle_packaging_options}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:114 — `api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);`

