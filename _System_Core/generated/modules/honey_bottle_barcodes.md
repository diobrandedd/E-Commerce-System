---
title: honey_bottle_barcodes
source: includes/honey_bottle_barcodes.php
generated: 2026-08-19T03:30:44+02:00
type: module
function_count: 56
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

## honey_bottle_size_label {#honey_bottle_size_label}

**Callers (12):**

- `api/v1/bottle_barcodes.php`:94 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `includes/barcode_verify.php`:71 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/finished_leak_damage.php`:34 — `$size = honey_bottle_size_label((string) ($row['size'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:135 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:164 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:629 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:812 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:1005 — `$sizeLabel = honey_bottle_size_label($size);`
- `includes/honey_bottle_barcodes.php`:1990 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/pack_cancel_restock.php`:75 — `$sizeLabel = honey_bottle_size_label($size);`
- `includes/pos_unified.php`:144 — `ucfirst($variant) . ' · ' . honey_bottle_size_label($size)`
- `includes/pos_unified.php`:150 — `'size_label' => honey_bottle_size_label($size),`

## honey_bottle_can_request {#honey_bottle_can_request}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:24 — `if (!honey_bottle_can_request($user)) {`
- `api/v1/bottle_barcodes.php`:155 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:233 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`

## honey_bottle_can_generate {#honey_bottle_can_generate}

**Callers (4):**

- `api/v1/bottle_barcodes.php`:233 — `if (!honey_bottle_can_generate($user) && !honey_bottle_can_request($user)) {`
- `includes/honey_bottle_barcodes.php`:57 — `return honey_bottle_can_generate($user);`
- `includes/honey_bottle_barcodes.php`:338 — `if ($req['generated_count'] > 0 && !honey_bottle_can_generate($role, $deptKey)) {`
- `includes/honey_bottle_barcodes.php`:341 — `if ((int) $req['requested_by'] !== $userId && !honey_bottle_can_generate($role, $deptKey)) {`

## honey_bottle_can_scan {#honey_bottle_can_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:44 — `if (!honey_bottle_can_scan($user)) {`

## honey_bottle_user_dept_key {#honey_bottle_user_dept_key}

**Tables:** `departments`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:224 — `honey_bottle_user_dept_key($user)`

## honey_bottle_user_can_generate {#honey_bottle_user_can_generate}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:34 — `if (!honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:155 — `if (!honey_bottle_can_request($user) && !honey_bottle_user_can_generate($user)) {`

## honey_bottle_normalize_code {#honey_bottle_normalize_code}

**Callers (8):**

- `includes/finished_leak_damage.php`:94 — `$code = honey_bottle_normalize_code((string) ($row['code'] ?? ''));`
- `includes/honey_bottle_barcodes.php`:541 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:587 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:842 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1268 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1724 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1796 — `$code = honey_bottle_normalize_code((string) $raw);`
- `includes/pack_cancel_restock.php`:153 — `$code = honey_bottle_normalize_code($code);`

## honey_bottle_random_code {#honey_bottle_random_code}

**Tables:** `honey_bottle_codes_issued`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:419 — `$insC->execute([$batchId, honey_bottle_random_code($pdo, $used), $seq, $size]);`

## honey_bottle_serialize_request {#honey_bottle_serialize_request}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:282 — `return honey_bottle_serialize_request($row);`
- `includes/honey_bottle_barcodes.php`:326 — `$rows[] = honey_bottle_serialize_request($row);`

## honey_bottle_serialize_batch {#honey_bottle_serialize_batch}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:496 — `return honey_bottle_serialize_batch($row);`
- `includes/honey_bottle_barcodes.php`:519 — `$rows[] = honey_bottle_serialize_batch($row);`

## honey_bottle_next_sequence {#honey_bottle_next_sequence}

**Tables:** `honey_bottle_barcode_seq`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:392 — `$start = honey_bottle_next_sequence($pdo, $qty);`

## honey_bottle_adjust_aggregate {#honey_bottle_adjust_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (6):**

- `includes/finished_leak_damage.php`:139 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, -1.0);`
- `includes/honey_bottle_barcodes.php`:894 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $row['size'], 1);`
- `includes/honey_bottle_barcodes.php`:1483 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, -1);`
- `includes/honey_bottle_barcodes.php`:1685 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], -1);`
- `includes/honey_bottle_barcodes.php`:1756 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], 1);`
- `includes/pack_cancel_restock.php`:334 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $item['size'], 1.0);`

## honey_bottle_request_create {#honey_bottle_request_create}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:182 — `$row = honey_bottle_request_create(`

## honey_bottle_request_get {#honey_bottle_request_get}

**Tables:** `honey_barcode_batches`, `honey_bottle_requests`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (5):**

- `api/v1/bottle_barcodes.php`:210 — `api_ok(['request' => honey_bottle_request_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:254 — `return honey_bottle_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:334 — `$req = honey_bottle_request_get($pdo, $id);`
- `includes/honey_bottle_barcodes.php`:450 — `$req = honey_bottle_request_get($pdo, $requestId);`
- `includes/honey_bottle_barcodes.php`:929 — `$out['request'] = honey_bottle_request_get($pdo, (int) $row['request_id']);`

## honey_bottle_request_list {#honey_bottle_request_list}

**Tables:** `honey_bottle_requests`, `honey_barcode_batches`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:202 — `api_ok(honey_bottle_request_list(ims_pdo(), $status, $filterUser, $page, 25));`

## honey_bottle_request_cancel {#honey_bottle_request_cancel}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:219 — `honey_bottle_request_cancel(`

## honey_bottle_pending_request_count {#honey_bottle_pending_request_count}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:236 — `api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);`

## honey_bottle_generate_batch {#honey_bottle_generate_batch}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`, `honey_bottle_requests`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:467 — `return honey_bottle_generate_batch(`
- `includes/honey_bottle_barcodes.php`:1904 — `$created[] = honey_bottle_generate_batch(`

## honey_bottle_generate_for_request {#honey_bottle_generate_for_request}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:251 — `$batch = honey_bottle_generate_for_request(`

## honey_bottle_batch_get {#honey_bottle_batch_get}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (4):**

- `api/v1/bottle_barcodes.php`:274 — `api_ok(['batch' => honey_bottle_batch_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:437 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:535 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:2195 — `$batch = honey_bottle_batch_get($pdo, $batchId);`

## honey_bottle_batch_list {#honey_bottle_batch_list}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:266 — `api_ok(['rows' => honey_bottle_batch_list(ims_pdo(), $source)]);`

## honey_bottle_mark_printed {#honey_bottle_mark_printed}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:283 — `api_ok(['batch' => honey_bottle_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);`

## honey_bottle_void_code {#honey_bottle_void_code}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:306 — `honey_bottle_void_code(`

## honey_bottle_fetch_code_row {#honey_bottle_fetch_code_row}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (7):**

- `includes/finished_leak_damage.php`:70 — `$honeyRow = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:610 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:924 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1273 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1633 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1800 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/pack_cancel_restock.php`:159 — `$honey = honey_bottle_fetch_code_row($pdo, $code);`

## honey_bottle_lookup {#honey_bottle_lookup}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:320 — `api_ok(['barcode' => honey_bottle_lookup(ims_pdo(), $code)]);`
- `includes/honey_bottle_barcodes.php`:1779 — `return honey_bottle_lookup($pdo, $code);`
- `includes/honey_inventory.php`:813 — `$lookup = honey_bottle_lookup($pdo, strtoupper($query));`

## honey_bottle_serialize_lookup {#honey_bottle_serialize_lookup}

**Callers (10):**

- `includes/honey_bottle_barcodes.php`:615 — `return honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:925 — `$out = honey_bottle_serialize_lookup($fresh ?? $row);`
- `includes/honey_bottle_barcodes.php`:1291 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1302 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1310 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1317 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1327 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1637 — `$lookup = honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:1818 — `$received[] = honey_bottle_serialize_lookup($row);`
- `includes/pack_cancel_restock.php`:173 — `$lookup = honey_bottle_serialize_lookup($row);`

## honey_bottle_scan_report_date {#honey_bottle_scan_report_date}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:665 — `$scanDate = honey_bottle_scan_report_date($scanDate);`
- `includes/honey_bottle_barcodes.php`:775 — `$scanDate = honey_bottle_scan_report_date($scanDate);`

## honey_bottle_packaging_scan_report {#honey_bottle_packaging_scan_report}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:128 — `api_ok(honey_bottle_packaging_scan_report(`

## honey_bottle_packaging_scan_log {#honey_bottle_packaging_scan_log}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:114 — `api_ok(honey_bottle_packaging_scan_log(`

## honey_bottle_scan_stock_in {#honey_bottle_scan_stock_in}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_barcode_batches`, `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:330 — `api_ok(honey_bottle_scan_stock_in(ims_pdo(), (int) $user['id'], (string) ($in['code'] ?? ''), $adopt));`

## honey_bottle_pack_session_start {#honey_bottle_pack_session_start}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:338 — `api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);`

## honey_bottle_pack_session_get {#honey_bottle_pack_session_get}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `users`, `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (19):**

- `api/v1/bottle_barcodes.php`:346 — `api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:946 — `return honey_bottle_pack_session_get($pdo, $existing);`
- `includes/honey_bottle_barcodes.php`:951 — `return honey_bottle_pack_session_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:1235 — `$detail = honey_bottle_pack_session_get($pdo, (int) $r['id']);`
- `includes/honey_bottle_barcodes.php`:1269 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1290 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1301 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1309 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1316 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1326 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1352 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1378 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1390 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1399 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1406 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1418 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1530 — `return honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1540 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1557 — `return honey_bottle_pack_session_get($pdo, $sessionId);`

## honey_bottle_pack_session_list {#honey_bottle_pack_session_list}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_sessions`, `honey_bottle_units`, `honey_packaging_rows`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:354 — `api_ok(honey_bottle_pack_session_list(`

## honey_bottle_pack_session_scan {#honey_bottle_pack_session_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:373 — `api_ok(honey_bottle_pack_session_scan(`

## honey_bottle_pack_session_scan_honey {#honey_bottle_pack_session_scan_honey}

**Tables:** `honey_bottle_pack_session_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1275 — `return honey_bottle_pack_session_scan_honey($pdo, $sessionId, $row);`

## honey_bottle_pack_session_scan_dept {#honey_bottle_pack_session_scan_dept}

**Tables:** `honey_bottle_pack_session_dept_items`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1278 — `return honey_bottle_pack_session_scan_dept($pdo, $sessionId, $code);`

## honey_bottle_pack_session_confirm {#honey_bottle_pack_session_confirm}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_pack_session_dept_items`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:404 — `api_ok(['session' => honey_bottle_pack_session_confirm(`

## honey_bottle_pack_session_remove_item {#honey_bottle_pack_session_remove_item}

**Tables:** `honey_bottle_pack_session_dept_items`, `honey_bottle_pack_session_items`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:387 — `api_ok(['session' => honey_bottle_pack_session_remove_item(`

## honey_bottle_pack_session_discard {#honey_bottle_pack_session_discard}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:417 — `honey_bottle_pack_session_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));`

## honey_bottle_write_sale_log {#honey_bottle_write_sale_log}

**Tables:** `honey_sales_log`

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:1485 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:1702 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:1758 — `honey_bottle_write_sale_log($pdo, [`

## honey_bottle_pos_lookup {#honey_bottle_pos_lookup}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:426 — `api_ok(['barcode' => honey_bottle_pos_lookup(ims_pdo(), $code)]);`
- `includes/pos_unified.php`:133 — `$honey = honey_bottle_pos_lookup($pdo, $code);`

## honey_bottle_pos_consume {#honey_bottle_pos_consume}

**Tables:** `honey_bottle_units`, `honey_packaging_rows`

**Callers (4):**

- `includes/honey_pos.php`:404 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/honey_pos.php`:623 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/pos_unified.php`:347 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`
- `includes/pos_unified.php`:479 — `honey_bottle_pos_consume($pdo, $line['bottle_unit_ids'], $saleId, 0);`

## honey_bottle_opened_return_scan {#honey_bottle_opened_return_scan}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:435 — `api_ok(['barcode' => honey_bottle_opened_return_scan(`

## honey_bottle_rts_receive {#honey_bottle_rts_receive}

**Tables:** `honey_bottle_units`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:453 — `api_ok(honey_bottle_rts_receive(ims_pdo(), (int) api_require_ims_user()['id'], $codes, $pool));`
- `includes/rts_case_handlers.php`:511 — `$honeyRx = honey_bottle_rts_receive($pdo, 0, $bottleCodes, $pool);`

## honey_bottle_backfill_preview {#honey_bottle_backfill_preview}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:460 — `api_ok(honey_bottle_backfill_preview(ims_pdo()));`
- `includes/honey_bottle_barcodes.php`:1891 — `$preview = honey_bottle_backfill_preview($pdo);`

## honey_bottle_unit_count_for_row {#honey_bottle_unit_count_for_row}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1843 — `$already = honey_bottle_unit_count_for_row($pdo, (int) $pk['id'], $size);`

## honey_bottle_backfill_generate {#honey_bottle_backfill_generate}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:468 — `$created = honey_bottle_backfill_generate(ims_pdo(), (int) $user['id']);`

## honey_bottle_reconcile {#honey_bottle_reconcile}

**Tables:** `honey_packaging_rows`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:476 — `api_ok(honey_bottle_reconcile(ims_pdo()));`

## honey_bottle_stock_summary {#honey_bottle_stock_summary}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:483 — `api_ok(['rows' => honey_bottle_stock_summary(ims_pdo())]);`

## honey_bottle_code128_svg {#honey_bottle_code128_svg}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:763 — `$svg = honey_bottle_code128_svg($code);`
- `includes/honey_bottle_barcodes.php`:2207 — `$svg = honey_bottle_code128_svg($code);`

## honey_bottle_batch_codes {#honey_bottle_batch_codes}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:2196 — `$codes = honey_bottle_batch_codes($pdo, $batchId);`

## honey_bottle_ensure_packaging_shell {#honey_bottle_ensure_packaging_shell}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:142 — `$pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);`
- `includes/honey_bottle_barcodes.php`:456 — `$shell = honey_bottle_ensure_packaging_shell($pdo, $userId, $productionRowId);`

## honey_bottle_create_custom_packaging {#honey_bottle_create_custom_packaging}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:160 — `$pk = honey_bottle_create_custom_packaging(`

## honey_bottle_serialize_packaging_option {#honey_bottle_serialize_packaging_option}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:144 — `'packaging' => honey_bottle_serialize_packaging_option($pk, $before === null),`
- `api/v1/bottle_barcodes.php`:170 — `'packaging' => honey_bottle_serialize_packaging_option($pk, !$reused),`
- `includes/honey_bottle_barcodes.php`:2327 — `$rows[] = honey_bottle_serialize_packaging_option($r, false);`

## honey_bottle_label_sheet_html {#honey_bottle_label_sheet_html}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:294 — `$html = honey_bottle_label_sheet_html(ims_pdo(), $id);`
- `includes/dept_pouch_barcodes.php`:804 — `honey_bottle_label_sheet_html(). */`

## honey_bottle_packaging_options {#honey_bottle_packaging_options}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:104 — `api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);`

