---
title: honey_bottle_barcodes
source: includes/honey_bottle_barcodes.php
generated: 2026-09-04T04:55:39+02:00
type: module
function_count: 60
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
- `includes/sku_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/sku_barcodes.php`
- `includes/rts_inventory_sync.php`

## honey_bottle_size_label {#honey_bottle_size_label}

**Callers (39):**

- `api/v1/bottle_barcodes.php`:106 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `api/v1/bottle_barcodes.php`:478 — `'size_label' => honey_bottle_size_label((string) $sku['size']),`
- `api/v1/rts_barcode_labels.php`:55 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `includes/barcode_verify.php`:43 — `'size_label' => honey_bottle_size_label((string) $sku['size']),`
- `includes/barcode_verify.php`:103 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/finished_leak_damage.php`:34 — `$size = honey_bottle_size_label((string) ($row['size'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:140 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:176 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:265 — `. ucfirst($variant) . ' ' . honey_bottle_size_label($size) . '.'`
- `includes/honey_bottle_barcodes.php`:714 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:920 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:1224 — `$sizeLabel = honey_bottle_size_label($size);`
- `includes/honey_bottle_barcodes.php`:1604 — `'size_label' => honey_bottle_size_label($size),`
- `includes/honey_bottle_barcodes.php`:2474 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/inventory_check.php`:209 — `'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,`
- `includes/inventory_check.php`:536 — `'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,`
- `includes/inventory_check.php`:828 — `'size_label' => $size !== '' ? honey_bottle_size_label($size) : null,`
- `includes/inventory_check.php`:887 — `'size_label' => $kind === 'honey' && $size !== null ? honey_bottle_size_label($size) : $size,`
- `includes/pack_cancel_restock.php`:101 — `$sizeLabel = honey_bottle_size_label($size);`
- `includes/pack_cancel_restock.php`:144 — `$sizeLabel = $isHoney ? honey_bottle_size_label($size) : dept_pouch_unit_singular($productKey);`
- `includes/pos_unified.php`:117 — `'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),`
- `includes/pos_unified.php`:122 — `'size_label' => honey_bottle_size_label($size),`
- `includes/pos_unified.php`:172 — `ucfirst($variant) . ' · ' . honey_bottle_size_label($size)`
- `includes/pos_unified.php`:178 — `'size_label' => honey_bottle_size_label($size),`
- `includes/rts_barcode_labels.php`:99 — `'size_label' => $size !== null ? honey_bottle_size_label($size) : null,`
- _… and 14 more (run impact.php for full list)_

## honey_bottle_can_request {#honey_bottle_can_request}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:24 — `if (!honey_bottle_can_request($user)) {`
- `api/v1/bottle_barcodes.php`:167 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:245 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`

## honey_bottle_can_generate {#honey_bottle_can_generate}

**Callers (4):**

- `api/v1/bottle_barcodes.php`:245 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`
- `includes/honey_bottle_barcodes.php`:62 — `return honey_bottle_can_generate($user);`
- `includes/honey_bottle_barcodes.php`:410 — `if ($req['generated_count'] > 0 && !honey_bottle_can_generate($role, $deptKey)) {`
- `includes/honey_bottle_barcodes.php`:413 — `if ((int) $req['requested_by'] !== $userId && !honey_bottle_can_generate($role, $deptKey)) {`

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

- `api/v1/bottle_barcodes.php`:236 — `honey_bottle_user_dept_key($user)`

## honey_bottle_user_can_generate {#honey_bottle_user_can_generate}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:34 — `if (!honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:167 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`

## honey_bottle_normalize_code {#honey_bottle_normalize_code}

**Callers (9):**

- `includes/finished_leak_damage.php`:94 — `$code = honey_bottle_normalize_code((string) ($row['code'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:626 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:672 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:953 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1054 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1503 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:2198 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:2272 — `$code = honey_bottle_normalize_code((string) $raw);`
- `includes/pack_cancel_restock.php`:273 — `$code = honey_bottle_normalize_code($code);`

## honey_bottle_random_code {#honey_bottle_random_code}

**Tables:** `honey_bottle_codes_issued`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:504 — `$insC->execute([$batchId, honey_bottle_random_code($pdo, $used), $seq, $size]);`

## honey_bottle_serialize_request {#honey_bottle_serialize_request}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:350 — `return honey_bottle_serialize_request($row);`
- `includes/honey_bottle_barcodes.php`:398 — `$rows[] = honey_bottle_serialize_request($row);`

## honey_bottle_serialize_batch {#honey_bottle_serialize_batch}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:581 — `return honey_bottle_serialize_batch($row);`
- `includes/honey_bottle_barcodes.php`:604 — `$rows[] = honey_bottle_serialize_batch($row);`

## honey_bottle_next_sequence {#honey_bottle_next_sequence}

**Tables:** `honey_bottle_barcode_seq`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:476 — `$start = honey_bottle_next_sequence($pdo, $qty);`

## honey_bottle_adjust_aggregate {#honey_bottle_adjust_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (9):**

- `includes/finished_leak_damage.php`:139 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, -1.0);`
- `includes/honey_bottle_barcodes.php`:269 — `honey_bottle_adjust_aggregate($pdo, $id, $size, -1.0);`
- `includes/honey_bottle_barcodes.php`:1005 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $row['size'], 1);`
- `includes/honey_bottle_barcodes.php`:2230 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], 1);`
- `includes/pack_cancel_restock.php`:542 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, 1.0);`
- `includes/sku_barcodes.php`:1006 — `honey_bottle_adjust_aggregate($pdo, $packagingRowId, $size, (float) $qty);`
- `includes/sku_barcodes.php`:1188 — `honey_bottle_adjust_aggregate($pdo, (int) $row['id'], $size, -(float) $take);`
- `includes/sku_barcodes.php`:1197 — `honey_bottle_adjust_aggregate($pdo, $packagingRowId, $size, -(float) $qty);`
- `includes/sku_barcodes.php`:1269 — `honey_bottle_adjust_aggregate($pdo, $packagingRowId, $size, (float) $qty);`

## honey_bottle_deduct_packaging_one {#honey_bottle_deduct_packaging_one}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:1915 — `$packId = honey_bottle_deduct_packaging_one($pdo, $variant, $size, $packId);`
- `includes/honey_bottle_barcodes.php`:2154 — `$packId = honey_bottle_deduct_packaging_one(`

## honey_bottle_request_create {#honey_bottle_request_create}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:194 — `$row = honey_bottle_request_create(`

## honey_bottle_request_get {#honey_bottle_request_get}

**Tables:** `sku_barcode_print_jobs`, `honey_barcode_batches`, `honey_bottle_requests`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (10):**

- `api/v1/bottle_barcodes.php`:222 — `api_ok(['request' => honey_bottle_request_get(ims_pdo(), $id)]);`
- `api/v1/bottle_barcodes.php`:262 — `$req = honey_bottle_request_get(ims_pdo(), $requestId);`
- `api/v1/bottle_barcodes.php`:288 — `'request' => honey_bottle_request_get(ims_pdo(), $requestId),`
- `api/v1/bottle_barcodes.php`:313 — `$req = honey_bottle_request_get($pdo, $requestId);`
- `api/v1/bottle_barcodes.php`:346 — `$requests[] = honey_bottle_request_get($pdo, $requestId);`
- `api/v1/bottle_barcodes.php`:482 — `$payload['request'] = honey_bottle_request_get(ims_pdo(), (int) $stocked['primary_request_id']);`
- `includes/honey_bottle_barcodes.php`:318 — `return honey_bottle_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:406 — `$req = honey_bottle_request_get($pdo, $id);`
- `includes/honey_bottle_barcodes.php`:535 — `$req = honey_bottle_request_get($pdo, $requestId);`
- `includes/honey_bottle_barcodes.php`:1040 — `$out['request'] = honey_bottle_request_get($pdo, (int) $row['request_id']);`

## honey_bottle_request_list {#honey_bottle_request_list}

**Tables:** `honey_bottle_requests`, `sku_barcode_print_jobs`, `honey_barcode_batches`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:214 — `api_ok(honey_bottle_request_list(ims_pdo(), $status, $filterUser, $page, 25));`

## honey_bottle_request_cancel {#honey_bottle_request_cancel}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:231 — `honey_bottle_request_cancel(`

## honey_bottle_pending_request_count {#honey_bottle_pending_request_count}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:248 — `api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);`

## honey_bottle_generate_batch {#honey_bottle_generate_batch}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`, `honey_bottle_requests`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:552 — `return honey_bottle_generate_batch(`
- `includes/honey_bottle_barcodes.php`:2388 — `$created[] = honey_bottle_generate_batch(`

## honey_bottle_generate_for_request {#honey_bottle_generate_for_request}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_bottle_batch_get {#honey_bottle_batch_get}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (4):**

- `api/v1/bottle_barcodes.php`:380 — `api_ok(['batch' => honey_bottle_batch_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:522 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:620 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:2679 — `$batch = honey_bottle_batch_get($pdo, $batchId);`

## honey_bottle_batch_list {#honey_bottle_batch_list}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:372 — `api_ok(['rows' => honey_bottle_batch_list(ims_pdo(), $source)]);`

## honey_bottle_mark_printed {#honey_bottle_mark_printed}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:389 — `api_ok(['batch' => honey_bottle_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);`
- `includes/rts_barcode_labels.php`:322 — `honey_bottle_mark_printed($pdo, (int) $line['honey_batch_id']);`

## honey_bottle_void_code {#honey_bottle_void_code}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:412 — `honey_bottle_void_code(`

## honey_bottle_fetch_code_row {#honey_bottle_fetch_code_row}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (10):**

- `api/v1/rts.php`:212 — `$honeyRow = honey_bottle_fetch_code_row(ims_pdo(), $code);`
- `includes/finished_leak_damage.php`:70 — `$honeyRow = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:695 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1035 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1141 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1522 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:2102 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:2276 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/pack_cancel_restock.php`:274 — `$honey = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/sku_barcodes.php`:697 — `$honeyRow = honey_bottle_fetch_code_row($pdo, $code);`

## honey_bottle_lookup {#honey_bottle_lookup}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:426 — `api_ok(['barcode' => honey_bottle_lookup(ims_pdo(), $code)]);`
- `includes/honey_bottle_barcodes.php`:2253 — `return honey_bottle_lookup($pdo, $code);`
- `includes/honey_inventory.php`:813 — `$lookup = honey_bottle_lookup($pdo, strtoupper($query));`

## honey_bottle_serialize_lookup {#honey_bottle_serialize_lookup}

**Callers (12):**

- `includes/honey_bottle_barcodes.php`:700 — `return honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:1036 — `$out = honey_bottle_serialize_lookup($fresh ?? $row);`
- `includes/honey_bottle_barcodes.php`:1142 — `$out = honey_bottle_serialize_lookup($fresh ?? []);`
- `includes/honey_bottle_barcodes.php`:1666 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1677 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1685 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1692 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1702 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:2106 — `$lookup = honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:2302 — `$received[] = honey_bottle_serialize_lookup($row);`
- `includes/pack_cancel_restock.php`:342 — `$lookup = honey_bottle_serialize_lookup($row);`
- `includes/sku_barcodes.php`:699 — `$lookup = honey_bottle_serialize_lookup($honeyRow);`

## honey_bottle_scan_report_date {#honey_bottle_scan_report_date}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:751 — `$scanDate = honey_bottle_scan_report_date($scanDate);`
- `includes/honey_bottle_barcodes.php`:869 — `$scanDate = honey_bottle_scan_report_date($scanDate);`

## honey_bottle_packaging_scan_report {#honey_bottle_packaging_scan_report}

**Tables:** `honey_bottle_barcodes`, `sku_barcode_stock_events`, `honey_barcode_batches`, `product_sku_barcodes`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:140 — `api_ok(honey_bottle_packaging_scan_report(`

## honey_bottle_packaging_scan_log {#honey_bottle_packaging_scan_log}

**Tables:** `honey_bottle_barcodes`, `sku_barcode_stock_events`, `honey_barcode_batches`, `honey_bottle_units`, `product_sku_barcodes`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:126 — `api_ok(honey_bottle_packaging_scan_log(`

## honey_bottle_scan_stock_in {#honey_bottle_scan_stock_in}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_barcode_batches`, `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:490 — `api_ok(honey_bottle_scan_stock_in(ims_pdo(), (int) $user['id'], $code, $adopt));`

## honey_bottle_rts_stock_in_scan {#honey_bottle_rts_stock_in_scan}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (1):**

- `api/v1/rts.php`:217 — `'unit' => honey_bottle_rts_stock_in_scan(ims_pdo(), (int) $user['id'], $code),`

## honey_bottle_pack_session_start {#honey_bottle_pack_session_start}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:498 — `api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);`

## honey_bottle_pack_session_get {#honey_bottle_pack_session_get}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `users`, `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (21):**

- `api/v1/bottle_barcodes.php`:506 — `api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:1160 — `return honey_bottle_pack_session_get($pdo, $existing);`
- `includes/honey_bottle_barcodes.php`:1165 — `return honey_bottle_pack_session_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:1464 — `$detail = honey_bottle_pack_session_get($pdo, (int) $r['id']);`
- `includes/honey_bottle_barcodes.php`:1504 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1597 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1642 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1665 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1676 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1684 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1691 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1701 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1727 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1753 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1765 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1774 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1781 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1793 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1999 — `return honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:2009 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:2026 — `return honey_bottle_pack_session_get($pdo, $sessionId);`

## honey_bottle_pack_session_list {#honey_bottle_pack_session_list}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_sessions`, `honey_bottle_units`, `honey_packaging_rows`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:514 — `api_ok(honey_bottle_pack_session_list(`

## honey_bottle_pack_session_scan {#honey_bottle_pack_session_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:533 — `api_ok(honey_bottle_pack_session_scan(`

## honey_bottle_pack_session_scan_sku {#honey_bottle_pack_session_scan_sku}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1512 — `return honey_bottle_pack_session_scan_sku(`

## honey_bottle_pack_session_scan_honey {#honey_bottle_pack_session_scan_honey}

**Tables:** `honey_bottle_pack_session_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1524 — `return honey_bottle_pack_session_scan_honey($pdo, $sessionId, $row);`

## honey_bottle_pack_session_scan_dept {#honey_bottle_pack_session_scan_dept}

**Tables:** `honey_bottle_pack_session_dept_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1527 — `return honey_bottle_pack_session_scan_dept($pdo, $sessionId, $code);`

## honey_bottle_pack_session_confirm {#honey_bottle_pack_session_confirm}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:567 — `api_ok(['session' => honey_bottle_pack_session_confirm(`

## honey_bottle_pack_session_remove_item {#honey_bottle_pack_session_remove_item}

**Tables:** `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_session_items`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:550 — `api_ok(['session' => honey_bottle_pack_session_remove_item(`

## honey_bottle_pack_session_discard {#honey_bottle_pack_session_discard}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:580 — `honey_bottle_pack_session_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));`

## honey_bottle_write_sale_log {#honey_bottle_write_sale_log}

**Tables:** `honey_sales_log`

**Callers (5):**

- `includes/honey_bottle_barcodes.php`:1917 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:2176 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:2232 — `honey_bottle_write_sale_log($pdo, [`
- `includes/pack_cancel_restock.php`:552 — `honey_bottle_write_sale_log($pdo, [`
- `includes/sku_barcodes.php`:1202 — `honey_bottle_write_sale_log($pdo, [`

## honey_bottle_pos_lookup {#honey_bottle_pos_lookup}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:589 — `api_ok(['barcode' => honey_bottle_pos_lookup(ims_pdo(), $code)]);`
- `includes/pos_unified.php`:159 — `$honey = honey_bottle_pos_lookup($pdo, $code);`

## honey_bottle_pos_consume {#honey_bottle_pos_consume}

**Tables:** `honey_bottle_units`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_pos.php`:416 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/honey_pos.php`:647 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/pos_unified.php`:459 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`
- `includes/pos_unified.php`:623 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`

## honey_bottle_opened_return_scan {#honey_bottle_opened_return_scan}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:598 — `api_ok(['barcode' => honey_bottle_opened_return_scan(`

## honey_bottle_rts_receive {#honey_bottle_rts_receive}

**Tables:** `honey_bottle_units`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:616 — `api_ok(honey_bottle_rts_receive(ims_pdo(), (int) api_require_ims_user()['id'], $codes, $pool));`
- `includes/rts_case_handlers.php`:516 — `$honeyRx = honey_bottle_rts_receive($pdo, 0, $bottleCodes, $pool);`

## honey_bottle_backfill_preview {#honey_bottle_backfill_preview}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:623 — `api_ok(honey_bottle_backfill_preview(ims_pdo()));`
- `includes/honey_bottle_barcodes.php`:2375 — `$preview = honey_bottle_backfill_preview($pdo);`

## honey_bottle_unit_count_for_row {#honey_bottle_unit_count_for_row}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:2327 — `$already = honey_bottle_unit_count_for_row($pdo, (int) $pk['id'], $size);`

## honey_bottle_backfill_generate {#honey_bottle_backfill_generate}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:631 — `$created = honey_bottle_backfill_generate(ims_pdo(), (int) $user['id']);`

## honey_bottle_reconcile {#honey_bottle_reconcile}

**Tables:** `honey_packaging_rows`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:639 — `api_ok(honey_bottle_reconcile(ims_pdo()));`

## honey_bottle_stock_summary {#honey_bottle_stock_summary}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:646 — `api_ok(['rows' => honey_bottle_stock_summary(ims_pdo())]);`

## honey_bottle_code128_svg {#honey_bottle_code128_svg}

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:885 — `$svg = honey_bottle_code128_svg($code);`
- `includes/honey_bottle_barcodes.php`:2691 — `$svg = honey_bottle_code128_svg($code);`
- `includes/sku_barcodes.php`:1374 — `$svg = honey_bottle_code128_svg($code);`

## honey_bottle_batch_codes {#honey_bottle_batch_codes}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:2680 — `$codes = honey_bottle_batch_codes($pdo, $batchId);`
- `includes/rts_barcode_labels.php`:433 — `$codes = honey_bottle_batch_codes($pdo, (int) $line['honey_batch_id']);`

## honey_bottle_ensure_packaging_shell {#honey_bottle_ensure_packaging_shell}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:154 — `$pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);`
- `includes/honey_bottle_barcodes.php`:541 — `$shell = honey_bottle_ensure_packaging_shell($pdo, $userId, $productionRowId);`

## honey_bottle_create_custom_packaging {#honey_bottle_create_custom_packaging}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:172 — `$pk = honey_bottle_create_custom_packaging(`
- `api/v1/bottle_barcodes.php`:454 — `$customPack = honey_bottle_create_custom_packaging(`

## honey_bottle_serialize_packaging_option {#honey_bottle_serialize_packaging_option}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:156 — `'packaging' => honey_bottle_serialize_packaging_option($pk, $before === null),`
- `api/v1/bottle_barcodes.php`:182 — `'packaging' => honey_bottle_serialize_packaging_option($pk, !$reused),`
- `includes/honey_bottle_barcodes.php`:2811 — `$rows[] = honey_bottle_serialize_packaging_option($r, false);`

## honey_bottle_label_sheet_html {#honey_bottle_label_sheet_html}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:400 — `$html = honey_bottle_label_sheet_html(ims_pdo(), $id);`
- `includes/dept_pouch_barcodes.php`:926 — `honey_bottle_label_sheet_html(). */`

## honey_bottle_packaging_options {#honey_bottle_packaging_options}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:116 — `api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);`

