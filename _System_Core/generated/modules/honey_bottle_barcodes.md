---
title: honey_bottle_barcodes
source: includes/honey_bottle_barcodes.php
generated: 2026-10-21T11:07:41+02:00
type: module
function_count: 64
---


# honey_bottle_barcodes

> Source: `includes/honey_bottle_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/rts_schema.php`
- `includes/dept_pouch_barcodes.php`
- `includes/dept_inventory.php`
- `includes/honey_inventory.php`
- `includes/rts_inventory_sync.php`
- `includes/sku_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/sku_barcodes.php`
- `includes/rts_inventory_sync.php`

## honey_bottle_size_label {#honey_bottle_size_label}

**Callers (52):**

- `api/v1/bottle_barcodes.php`:119 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `api/v1/bottle_barcodes.php`:484 — `'size_label' => honey_bottle_size_label((string) $sku['size']),`
- `api/v1/rts_barcode_labels.php`:55 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `includes/barcode_verify.php`:43 — `'size_label' => honey_bottle_size_label((string) $sku['size']),`
- `includes/barcode_verify.php`:103 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/dashboard_stock_ins.php`:85 — `? honey_bottle_size_label($size)`
- `includes/dashboard_stock_ins.php`:350 — `? honey_bottle_size_label($size)`
- `includes/finished_leak_damage.php`:34 — `$size = honey_bottle_size_label((string) ($row['size'] ?? ''));`
- `includes/finished_leak_damage.php`:170 — `'Not enough packaging stock for ' . ucfirst($variant) . ' ' . honey_bottle_size_label($size) . '.'`
- `includes/honey_bottle_barcodes.php`:149 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:185 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:240 — `'Not enough ' . ucfirst($variant) . ' ' . honey_bottle_size_label($size) . ' in the finished pool.'`
- `includes/honey_bottle_barcodes.php`:318 — `. ucfirst($variant) . ' ' . honey_bottle_size_label($size) . '.'`
- `includes/honey_bottle_barcodes.php`:767 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:1063 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:1375 — `$sizeLabel = honey_bottle_size_label($size);`
- `includes/honey_bottle_barcodes.php`:1819 — `'size_label' => honey_bottle_size_label($size),`
- `includes/honey_bottle_barcodes.php`:2753 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_inventory.php`:645 — `'size_label' => honey_bottle_size_label($size),`
- `includes/honey_inventory.php`:650 — `'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),`
- `includes/honey_inventory.php`:1873 — `'size_label' => honey_bottle_size_label($size),`
- `includes/honey_inventory.php`:1878 — `'label' => ucfirst($variant) . ' · ' . honey_bottle_size_label($size),`
- `includes/inventory_check.php`:341 — `? honey_bottle_size_label($size)`
- `includes/inventory_check.php`:957 — `? honey_bottle_size_label($size)`
- `includes/inventory_check.php`:1245 — `$sizeLabel = $size !== '' ? honey_bottle_size_label($size) : null;`
- _… and 27 more (run impact.php for full list)_

## honey_bottle_can_request {#honey_bottle_can_request}

**Callers (4):**

- `api/v1/bottle_barcodes.php`:24 — `if (!honey_bottle_can_request($user)) {`
- `api/v1/bottle_barcodes.php`:182 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:260 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`
- `includes/honey_bottle_barcodes.php`:42 — `|| honey_bottle_can_request($user)`

## honey_bottle_can_generate {#honey_bottle_can_generate}

**Callers (4):**

- `api/v1/bottle_barcodes.php`:260 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`
- `includes/honey_bottle_barcodes.php`:71 — `return honey_bottle_can_generate($user);`
- `includes/honey_bottle_barcodes.php`:463 — `if ($req['generated_count'] > 0 && !honey_bottle_can_generate($role, $deptKey)) {`
- `includes/honey_bottle_barcodes.php`:466 — `if ((int) $req['requested_by'] !== $userId && !honey_bottle_can_generate($role, $deptKey)) {`

## honey_bottle_can_scan {#honey_bottle_can_scan}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:55 — `if (!honey_bottle_can_scan($user)) {`
- `api/v1/bottle_barcodes.php`:65 — `if (!honey_bottle_can_pack_scan($user) && !honey_bottle_can_scan($user)) {`
- `includes/honey_bottle_barcodes.php`:43 — `|| honey_bottle_can_scan($user);`

## honey_bottle_can_view_packaging_report {#honey_bottle_can_view_packaging_report}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:35 — `if (!honey_bottle_can_view_packaging_report($user)) {`

## honey_bottle_can_pack_scan {#honey_bottle_can_pack_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:65 — `if (!honey_bottle_can_pack_scan($user) && !honey_bottle_can_scan($user)) {`

## honey_bottle_user_dept_key {#honey_bottle_user_dept_key}

**Tables:** `departments`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:251 — `honey_bottle_user_dept_key($user)`

## honey_bottle_user_can_generate {#honey_bottle_user_can_generate}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:45 — `if (!honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:182 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`

## honey_bottle_normalize_code {#honey_bottle_normalize_code}

**Callers (9):**

- `includes/finished_leak_damage.php`:290 — `$code = honey_bottle_normalize_code((string) ($row['code'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:679 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:725 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1096 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1205 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1718 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:2477 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:2551 — `$code = honey_bottle_normalize_code((string) $raw);`
- `includes/pack_cancel_restock.php`:273 — `$code = honey_bottle_normalize_code($code);`

## honey_bottle_random_code {#honey_bottle_random_code}

**Tables:** `honey_bottle_codes_issued`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:557 — `$insC->execute([$batchId, honey_bottle_random_code($pdo, $used), $seq, $size]);`

## honey_bottle_serialize_request {#honey_bottle_serialize_request}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:403 — `return honey_bottle_serialize_request($row);`
- `includes/honey_bottle_barcodes.php`:451 — `$rows[] = honey_bottle_serialize_request($row);`

## honey_bottle_serialize_batch {#honey_bottle_serialize_batch}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:634 — `return honey_bottle_serialize_batch($row);`
- `includes/honey_bottle_barcodes.php`:657 — `$rows[] = honey_bottle_serialize_batch($row);`

## honey_bottle_next_sequence {#honey_bottle_next_sequence}

**Tables:** `honey_bottle_barcode_seq`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:529 — `$start = honey_bottle_next_sequence($pdo, $qty);`

## honey_bottle_adjust_variant_pool {#honey_bottle_adjust_variant_pool}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:2130 — `$packId = honey_bottle_adjust_variant_pool($pdo, $userId, $variant, $size, -1.0);`
- `includes/pack_cancel_restock.php`:542 — `$packId = honey_bottle_adjust_variant_pool($pdo, $userId, $variant, $size, 1.0);`
- `includes/sku_barcodes.php`:1281 — `$poolId = honey_bottle_adjust_variant_pool($pdo, $userId, $variant, $size, -(float) $qty);`
- `includes/sku_barcodes.php`:1355 — `honey_bottle_adjust_variant_pool($pdo, 1, $variant, $size, (float) $qty);`

## honey_bottle_adjust_aggregate {#honey_bottle_adjust_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (10):**

- `includes/finished_leak_damage.php`:165 — `honey_bottle_adjust_aggregate($pdo, (int) $row['id'], $size, -(float) $take);`
- `includes/finished_leak_damage.php`:335 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, -1.0);`
- `includes/honey_bottle_barcodes.php`:322 — `honey_bottle_adjust_aggregate($pdo, $id, $size, -1.0);`
- `includes/honey_bottle_barcodes.php`:1154 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $row['size'], 1);`
- `includes/honey_bottle_barcodes.php`:2509 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], 1);`
- `includes/honey_inventory.php`:610 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, (float) $qty);`
- `includes/honey_inventory.php`:1841 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, (float) $qty);`
- `includes/pos_refund.php`:31 — `honey_bottle_adjust_aggregate($pdo, $preferredPackagingRowId, $size, $qty);`
- `includes/pos_refund.php`:43 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, $qty);`
- `includes/sku_barcodes.php`:1017 — `honey_bottle_adjust_aggregate($pdo, $packagingRowId, $size, (float) $qty);`

## honey_bottle_deduct_packaging_one {#honey_bottle_deduct_packaging_one}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:2433 — `$packId = honey_bottle_deduct_packaging_one(`

## honey_bottle_request_create {#honey_bottle_request_create}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:209 — `$row = honey_bottle_request_create(`

## honey_bottle_request_get {#honey_bottle_request_get}

**Tables:** `sku_barcode_print_jobs`, `honey_barcode_batches`, `honey_bottle_requests`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (10):**

- `api/v1/bottle_barcodes.php`:237 — `api_ok(['request' => honey_bottle_request_get(ims_pdo(), $id)]);`
- `api/v1/bottle_barcodes.php`:277 — `$req = honey_bottle_request_get(ims_pdo(), $requestId);`
- `api/v1/bottle_barcodes.php`:303 — `'request' => honey_bottle_request_get(ims_pdo(), $requestId),`
- `api/v1/bottle_barcodes.php`:328 — `$req = honey_bottle_request_get($pdo, $requestId);`
- `api/v1/bottle_barcodes.php`:361 — `$requests[] = honey_bottle_request_get($pdo, $requestId);`
- `api/v1/bottle_barcodes.php`:487 — `$payload['request'] = honey_bottle_request_get($pdo, (int) $stocked['primary_request_id']);`
- `includes/honey_bottle_barcodes.php`:371 — `return honey_bottle_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:459 — `$req = honey_bottle_request_get($pdo, $id);`
- `includes/honey_bottle_barcodes.php`:588 — `$req = honey_bottle_request_get($pdo, $requestId);`
- `includes/honey_bottle_barcodes.php`:1191 — `$out['request'] = honey_bottle_request_get($pdo, (int) $row['request_id']);`

## honey_bottle_request_list {#honey_bottle_request_list}

**Tables:** `honey_bottle_requests`, `sku_barcode_print_jobs`, `honey_barcode_batches`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:229 — `api_ok(honey_bottle_request_list(ims_pdo(), $status, $filterUser, $page, 25));`

## honey_bottle_request_cancel {#honey_bottle_request_cancel}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:246 — `honey_bottle_request_cancel(`

## honey_bottle_pending_request_count {#honey_bottle_pending_request_count}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:263 — `api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);`

## honey_bottle_generate_batch {#honey_bottle_generate_batch}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`, `honey_bottle_requests`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:605 — `return honey_bottle_generate_batch(`
- `includes/honey_bottle_barcodes.php`:2667 — `$created[] = honey_bottle_generate_batch(`

## honey_bottle_generate_for_request {#honey_bottle_generate_for_request}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_bottle_batch_get {#honey_bottle_batch_get}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (4):**

- `api/v1/bottle_barcodes.php`:395 — `api_ok(['batch' => honey_bottle_batch_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:575 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:673 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:2921 — `$batch = honey_bottle_batch_get($pdo, $batchId);`

## honey_bottle_batch_list {#honey_bottle_batch_list}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:387 — `api_ok(['rows' => honey_bottle_batch_list(ims_pdo(), $source)]);`

## honey_bottle_mark_printed {#honey_bottle_mark_printed}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:404 — `api_ok(['batch' => honey_bottle_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);`
- `includes/rts_barcode_labels.php`:322 — `honey_bottle_mark_printed($pdo, (int) $line['honey_batch_id']);`

## honey_bottle_void_code {#honey_bottle_void_code}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:427 — `honey_bottle_void_code(`
- `includes/inventory_check_apply.php`:173 — `honey_bottle_void_code($pdo, $code, 0, $reason);`

## honey_bottle_fetch_code_row {#honey_bottle_fetch_code_row}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (10):**

- `includes/finished_leak_damage.php`:76 — `$honeyRow = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:748 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1184 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1292 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1737 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:2381 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:2555 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/pack_cancel_restock.php`:274 — `$honey = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/rts_stock_in_sessions.php`:291 — `$honey = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/sku_barcodes.php`:697 — `$honeyRow = honey_bottle_fetch_code_row($pdo, $code);`

## honey_bottle_lookup {#honey_bottle_lookup}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:441 — `api_ok(['barcode' => honey_bottle_lookup(ims_pdo(), $code)]);`
- `includes/honey_bottle_barcodes.php`:2532 — `return honey_bottle_lookup($pdo, $code);`
- `includes/honey_inventory.php`:2248 — `$lookup = honey_bottle_lookup($pdo, strtoupper($query));`

## honey_bottle_serialize_lookup {#honey_bottle_serialize_lookup}

**Callers (12):**

- `includes/honey_bottle_barcodes.php`:753 — `return honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:1185 — `$out = honey_bottle_serialize_lookup($fresh ?? $row);`
- `includes/honey_bottle_barcodes.php`:1293 — `$out = honey_bottle_serialize_lookup($fresh ?? []);`
- `includes/honey_bottle_barcodes.php`:1881 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1892 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1900 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1907 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1917 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:2385 — `$lookup = honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:2581 — `$received[] = honey_bottle_serialize_lookup($row);`
- `includes/pack_cancel_restock.php`:342 — `$lookup = honey_bottle_serialize_lookup($row);`
- `includes/sku_barcodes.php`:699 — `$lookup = honey_bottle_serialize_lookup($honeyRow);`

## honey_bottle_scan_report_date {#honey_bottle_scan_report_date}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:817 — `$scanDate = honey_bottle_scan_report_date($scanDate);`
- `includes/honey_bottle_barcodes.php`:1011 — `$scanDate = honey_bottle_scan_report_date($scanDate);`

## honey_bottle_packaging_scan_report {#honey_bottle_packaging_scan_report}

**Tables:** `honey_bottle_barcodes`, `sku_barcode_stock_events`, `honey_barcode_batches`, `honey_bottle_units`, `product_sku_barcodes`, `honey_packaging_rows`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:154 — `api_ok(honey_bottle_packaging_scan_report(`

## honey_bottle_packaging_scan_log {#honey_bottle_packaging_scan_log}

**Tables:** `honey_bottle_barcodes`, `sku_barcode_stock_events`, `honey_barcode_batches`, `honey_bottle_units`, `product_sku_barcodes`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:139 — `api_ok(honey_bottle_packaging_scan_log(`

## honey_bottle_scan_stock_in {#honey_bottle_scan_stock_in}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_barcode_batches`, `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:498 — `$scanned = honey_bottle_scan_stock_in($pdo, (int) $user['id'], $code, $adopt);`

## honey_bottle_rts_stock_in_scan {#honey_bottle_rts_stock_in_scan}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (1):**

- `includes/rts_stock_in_sessions.php`:514 — `honey_bottle_rts_stock_in_scan($pdo, $userId, (string) $item['code']);`

## honey_bottle_pack_session_start {#honey_bottle_pack_session_start}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:539 — `api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);`

## honey_bottle_pack_session_get {#honey_bottle_pack_session_get}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `users`, `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (23):**

- `api/v1/bottle_barcodes.php`:547 — `api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:1311 — `return honey_bottle_pack_session_get($pdo, $existing);`
- `includes/honey_bottle_barcodes.php`:1316 — `return honey_bottle_pack_session_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:1621 — `$detail = honey_bottle_pack_session_get($pdo, (int) $r['id']);`
- `includes/honey_bottle_barcodes.php`:1719 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1812 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1857 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1880 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1891 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1899 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1906 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1916 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1942 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1968 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1980 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1989 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1996 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:2008 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:2214 — `return honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:2224 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:2241 — `return honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:2256 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:2305 — `return honey_bottle_pack_session_get($pdo, $sessionId);`

## honey_bottle_pack_session_list {#honey_bottle_pack_session_list}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_sessions`, `honey_bottle_units`, `honey_packaging_rows`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:555 — `api_ok(honey_bottle_pack_session_list(`

## honey_bottle_pack_session_today_stats {#honey_bottle_pack_session_today_stats}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_sessions`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:572 — `api_ok(honey_bottle_pack_session_today_stats(ims_pdo(), (int) $user['id']));`

## honey_bottle_pack_session_scan {#honey_bottle_pack_session_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:581 — `api_ok(honey_bottle_pack_session_scan(`

## honey_bottle_pack_session_scan_sku {#honey_bottle_pack_session_scan_sku}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1727 — `return honey_bottle_pack_session_scan_sku(`

## honey_bottle_pack_session_scan_honey {#honey_bottle_pack_session_scan_honey}

**Tables:** `honey_bottle_pack_session_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1739 — `return honey_bottle_pack_session_scan_honey($pdo, $sessionId, $row);`

## honey_bottle_pack_session_scan_dept {#honey_bottle_pack_session_scan_dept}

**Tables:** `honey_bottle_pack_session_dept_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1742 — `return honey_bottle_pack_session_scan_dept($pdo, $sessionId, $code);`

## honey_bottle_pack_session_confirm {#honey_bottle_pack_session_confirm}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:634 — `api_ok(['session' => honey_bottle_pack_session_confirm(`

## honey_bottle_pack_session_remove_item {#honey_bottle_pack_session_remove_item}

**Tables:** `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_session_items`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:598 — `api_ok(['session' => honey_bottle_pack_session_remove_item(`

## honey_bottle_pack_session_set_item_qty {#honey_bottle_pack_session_set_item_qty}

**Tables:** `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_session_items`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:616 — `api_ok(['session' => honey_bottle_pack_session_set_item_qty(`

## honey_bottle_pack_session_discard {#honey_bottle_pack_session_discard}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:647 — `honey_bottle_pack_session_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));`

## honey_bottle_write_sale_log {#honey_bottle_write_sale_log}

**Tables:** `honey_sales_log`

**Callers (7):**

- `includes/honey_bottle_barcodes.php`:2132 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:2455 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:2511 — `honey_bottle_write_sale_log($pdo, [`
- `includes/pack_cancel_restock.php`:552 — `honey_bottle_write_sale_log($pdo, [`
- `includes/pos_refund.php`:116 — `honey_bottle_write_sale_log($pdo, [`
- `includes/pos_refund.php`:198 — `honey_bottle_write_sale_log($pdo, [`
- `includes/sku_barcodes.php`:1286 — `honey_bottle_write_sale_log($pdo, [`

## honey_bottle_pos_lookup {#honey_bottle_pos_lookup}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:656 — `api_ok(['barcode' => honey_bottle_pos_lookup(ims_pdo(), $code)]);`
- `includes/pos_unified.php`:159 — `$honey = honey_bottle_pos_lookup($pdo, $code);`

## honey_bottle_pos_consume {#honey_bottle_pos_consume}

**Tables:** `honey_bottle_units`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_pos.php`:416 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/honey_pos.php`:647 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/pos_unified.php`:459 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`
- `includes/pos_unified.php`:624 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`

## honey_bottle_opened_return_scan {#honey_bottle_opened_return_scan}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:665 — `api_ok(['barcode' => honey_bottle_opened_return_scan(`

## honey_bottle_rts_receive {#honey_bottle_rts_receive}

**Tables:** `honey_bottle_units`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:683 — `api_ok(honey_bottle_rts_receive(ims_pdo(), (int) api_require_ims_user()['id'], $codes, $pool));`
- `includes/rts_case_handlers.php`:516 — `$honeyRx = honey_bottle_rts_receive($pdo, 0, $bottleCodes, $pool);`

## honey_bottle_backfill_preview {#honey_bottle_backfill_preview}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:690 — `api_ok(honey_bottle_backfill_preview(ims_pdo()));`
- `includes/honey_bottle_barcodes.php`:2654 — `$preview = honey_bottle_backfill_preview($pdo);`

## honey_bottle_unit_count_for_row {#honey_bottle_unit_count_for_row}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:2606 — `$already = honey_bottle_unit_count_for_row($pdo, (int) $pk['id'], $size);`

## honey_bottle_backfill_generate {#honey_bottle_backfill_generate}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:698 — `$created = honey_bottle_backfill_generate(ims_pdo(), (int) $user['id']);`

## honey_bottle_reconcile {#honey_bottle_reconcile}

**Tables:** `honey_packaging_rows`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:706 — `api_ok(honey_bottle_reconcile(ims_pdo()));`

## honey_bottle_stock_summary {#honey_bottle_stock_summary}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:713 — `api_ok(['rows' => honey_bottle_stock_summary(ims_pdo())]);`

## honey_bottle_code128_svg {#honey_bottle_code128_svg}

**Callers (4):**

- `includes/dept_pouch_barcodes.php`:1087 — `$svg = honey_bottle_code128_svg($code);`
- `includes/honey_bottle_barcodes.php`:2933 — `$svg = honey_bottle_code128_svg($code);`
- `includes/material_inventory.php`:2564 — `$svg = honey_bottle_code128_svg($code);`
- `includes/sku_barcodes.php`:1458 — `$svg = honey_bottle_code128_svg($code);`

## honey_bottle_batch_codes {#honey_bottle_batch_codes}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:2922 — `$codes = honey_bottle_batch_codes($pdo, $batchId);`
- `includes/rts_barcode_labels.php`:433 — `$codes = honey_bottle_batch_codes($pdo, (int) $line['honey_batch_id']);`

## honey_bottle_ensure_packaging_shell {#honey_bottle_ensure_packaging_shell}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:169 — `$pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);`
- `includes/honey_bottle_barcodes.php`:594 — `$shell = honey_bottle_ensure_packaging_shell($pdo, $userId, $productionRowId);`

## honey_bottle_create_custom_packaging {#honey_bottle_create_custom_packaging}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:187 — `$pk = honey_bottle_create_custom_packaging(`

## honey_bottle_serialize_packaging_option {#honey_bottle_serialize_packaging_option}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:171 — `'packaging' => honey_bottle_serialize_packaging_option($pk, $before === null),`
- `api/v1/bottle_barcodes.php`:197 — `'packaging' => honey_bottle_serialize_packaging_option($pk, !$reused),`
- `includes/honey_bottle_barcodes.php`:3053 — `$rows[] = honey_bottle_serialize_packaging_option($r, false);`

## honey_bottle_label_sheet_html {#honey_bottle_label_sheet_html}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:415 — `$html = honey_bottle_label_sheet_html(ims_pdo(), $id);`
- `includes/dept_pouch_barcodes.php`:1128 — `honey_bottle_label_sheet_html(). */`

## honey_bottle_packaging_options {#honey_bottle_packaging_options}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:129 — `api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);`

