---
title: honey_bottle_barcodes
source: includes/honey_bottle_barcodes.php
generated: 2026-08-15T07:45:50+02:00
type: module
function_count: 51
---


# honey_bottle_barcodes

> Source: `includes/honey_bottle_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/rts_schema.php`
- `includes/rts_inventory_sync.php`

## honey_bottle_size_label {#honey_bottle_size_label}

**Callers (10):**

- `api/v1/bottle_barcodes.php`:92 — `static fn ($s) => ['value' => $s, 'label' => honey_bottle_size_label($s)],`
- `includes/barcode_verify.php`:71 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:148 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:177 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:642 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:699 — `'size_label' => honey_bottle_size_label((string) $row['size']),`
- `includes/honey_bottle_barcodes.php`:882 — `'size_label' => honey_bottle_size_label($size),`
- `includes/honey_bottle_barcodes.php`:891 — `'size_label' => honey_bottle_size_label($size),`
- `includes/honey_bottle_barcodes.php`:902 — `'size_label' => honey_bottle_size_label($size),`
- `includes/honey_bottle_barcodes.php`:1584 — `'size_label' => honey_bottle_size_label((string) $row['size']),`

## honey_bottle_can_request {#honey_bottle_can_request}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:24 — `if (!honey_bottle_can_request((string) $user['role'])) {`
- `api/v1/bottle_barcodes.php`:135 — `if (!honey_bottle_can_request($role) && !honey_bottle_user_can_generate($user)) {`

## honey_bottle_can_generate {#honey_bottle_can_generate}

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:70 — `return honey_bottle_can_generate((string) ($user['role'] ?? ''), honey_bottle_user_dept_key($user));`
- `includes/honey_bottle_barcodes.php`:351 — `if ($req['generated_count'] > 0 && !honey_bottle_can_generate($role, $deptKey)) {`
- `includes/honey_bottle_barcodes.php`:354 — `if ((int) $req['requested_by'] !== $userId && !honey_bottle_can_generate($role, $deptKey)) {`

## honey_bottle_can_scan {#honey_bottle_can_scan}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:44 — `if (!honey_bottle_can_scan((string) $user['role'])) {`

## honey_bottle_user_dept_key {#honey_bottle_user_dept_key}

**Tables:** `departments`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:204 — `honey_bottle_user_dept_key($user)`
- `includes/honey_bottle_barcodes.php`:70 — `return honey_bottle_can_generate((string) ($user['role'] ?? ''), honey_bottle_user_dept_key($user));`

## honey_bottle_user_can_generate {#honey_bottle_user_can_generate}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:34 — `if (!honey_bottle_user_can_generate($user)) {`
- `api/v1/bottle_barcodes.php`:135 — `if (!honey_bottle_can_request($role) && !honey_bottle_user_can_generate($user)) {`

## honey_bottle_normalize_code {#honey_bottle_normalize_code}

**Callers (6):**

- `includes/honey_bottle_barcodes.php`:554 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:600 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:728 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1038 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1318 — `$code = honey_bottle_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1390 — `$code = honey_bottle_normalize_code((string) $raw);`

## honey_bottle_random_code {#honey_bottle_random_code}

**Tables:** `honey_bottle_codes_issued`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:432 — `$insC->execute([$batchId, honey_bottle_random_code($pdo, $used), $seq, $size]);`

## honey_bottle_serialize_request {#honey_bottle_serialize_request}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:295 — `return honey_bottle_serialize_request($row);`
- `includes/honey_bottle_barcodes.php`:339 — `$rows[] = honey_bottle_serialize_request($row);`

## honey_bottle_serialize_batch {#honey_bottle_serialize_batch}

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:509 — `return honey_bottle_serialize_batch($row);`
- `includes/honey_bottle_barcodes.php`:532 — `$rows[] = honey_bottle_serialize_batch($row);`

## honey_bottle_next_sequence {#honey_bottle_next_sequence}

**Tables:** `honey_bottle_barcode_seq`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:405 — `$start = honey_bottle_next_sequence($pdo, $qty);`

## honey_bottle_adjust_aggregate {#honey_bottle_adjust_aggregate}

**Tables:** `honey_packaging_rows`

**Callers (4):**

- `includes/honey_bottle_barcodes.php`:780 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $row['size'], 1);`
- `includes/honey_bottle_barcodes.php`:1123 — `honey_bottle_adjust_aggregate($pdo, $packId, $size, -1);`
- `includes/honey_bottle_barcodes.php`:1279 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], -1);`
- `includes/honey_bottle_barcodes.php`:1350 — `honey_bottle_adjust_aggregate($pdo, $packId, (string) $unit['size'], 1);`

## honey_bottle_request_create {#honey_bottle_request_create}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:162 — `$row = honey_bottle_request_create(`

## honey_bottle_request_get {#honey_bottle_request_get}

**Tables:** `honey_barcode_batches`, `honey_bottle_requests`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (5):**

- `api/v1/bottle_barcodes.php`:190 — `api_ok(['request' => honey_bottle_request_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:267 — `return honey_bottle_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:347 — `$req = honey_bottle_request_get($pdo, $id);`
- `includes/honey_bottle_barcodes.php`:463 — `$req = honey_bottle_request_get($pdo, $requestId);`
- `includes/honey_bottle_barcodes.php`:815 — `$out['request'] = honey_bottle_request_get($pdo, (int) $row['request_id']);`

## honey_bottle_request_list {#honey_bottle_request_list}

**Tables:** `honey_bottle_requests`, `honey_barcode_batches`, `honey_bottle_barcodes`, `users`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:182 — `api_ok(honey_bottle_request_list(ims_pdo(), $status, $filterUser, $page, 25));`

## honey_bottle_request_cancel {#honey_bottle_request_cancel}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:199 — `honey_bottle_request_cancel(`

## honey_bottle_pending_request_count {#honey_bottle_pending_request_count}

**Tables:** `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:213 — `api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);`

## honey_bottle_generate_batch {#honey_bottle_generate_batch}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`, `honey_bottle_requests`

**Callers (2):**

- `includes/honey_bottle_barcodes.php`:480 — `return honey_bottle_generate_batch(`
- `includes/honey_bottle_barcodes.php`:1498 — `$created[] = honey_bottle_generate_batch(`

## honey_bottle_generate_for_request {#honey_bottle_generate_for_request}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:228 — `$batch = honey_bottle_generate_for_request(`

## honey_bottle_batch_get {#honey_bottle_batch_get}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (4):**

- `api/v1/bottle_barcodes.php`:251 — `api_ok(['batch' => honey_bottle_batch_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:450 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:548 — `return honey_bottle_batch_get($pdo, $batchId);`
- `includes/honey_bottle_barcodes.php`:1789 — `$batch = honey_bottle_batch_get($pdo, $batchId);`

## honey_bottle_batch_list {#honey_bottle_batch_list}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:243 — `api_ok(['rows' => honey_bottle_batch_list(ims_pdo(), $source)]);`

## honey_bottle_mark_printed {#honey_bottle_mark_printed}

**Tables:** `honey_barcode_batches`, `honey_bottle_barcodes`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:260 — `api_ok(['batch' => honey_bottle_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);`

## honey_bottle_void_code {#honey_bottle_void_code}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:283 — `honey_bottle_void_code(`

## honey_bottle_fetch_code_row {#honey_bottle_fetch_code_row}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `honey_bottle_units`

**Callers (5):**

- `includes/honey_bottle_barcodes.php`:623 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:810 — `$fresh = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1043 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1227 — `$row = honey_bottle_fetch_code_row($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1394 — `$row = honey_bottle_fetch_code_row($pdo, $code);`

## honey_bottle_lookup {#honey_bottle_lookup}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:297 — `api_ok(['barcode' => honey_bottle_lookup(ims_pdo(), $code)]);`
- `includes/honey_bottle_barcodes.php`:1373 — `return honey_bottle_lookup($pdo, $code);`
- `includes/honey_inventory.php`:814 — `$lookup = honey_bottle_lookup($pdo, strtoupper($query));`

## honey_bottle_serialize_lookup {#honey_bottle_serialize_lookup}

**Callers (9):**

- `includes/honey_bottle_barcodes.php`:628 — `return honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:811 — `$out = honey_bottle_serialize_lookup($fresh ?? $row);`
- `includes/honey_bottle_barcodes.php`:1048 — `return ['result' => 'void', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];`
- `includes/honey_bottle_barcodes.php`:1055 — `return ['result' => 'duplicate_session', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];`
- `includes/honey_bottle_barcodes.php`:1059 — `return ['result' => 'already_consumed', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];`
- `includes/honey_bottle_barcodes.php`:1062 — `return ['result' => 'not_in_stock', 'session' => $sess, 'lookup' => honey_bottle_serialize_lookup($row)];`
- `includes/honey_bottle_barcodes.php`:1071 — `'lookup' => honey_bottle_serialize_lookup($row),`
- `includes/honey_bottle_barcodes.php`:1231 — `$lookup = honey_bottle_serialize_lookup($row);`
- `includes/honey_bottle_barcodes.php`:1412 — `$received[] = honey_bottle_serialize_lookup($row);`

## honey_bottle_packaging_scan_log {#honey_bottle_packaging_scan_log}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`, `users`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:111 — `api_ok(honey_bottle_packaging_scan_log(ims_pdo(), $packId, $page, 50));`

## honey_bottle_scan_stock_in {#honey_bottle_scan_stock_in}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_barcode_batches`, `honey_bottle_requests`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:307 — `api_ok(honey_bottle_scan_stock_in(ims_pdo(), (int) $user['id'], (string) ($in['code'] ?? ''), $adopt));`

## honey_bottle_pack_session_start {#honey_bottle_pack_session_start}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:315 — `api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);`

## honey_bottle_pack_session_get {#honey_bottle_pack_session_get}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `users`, `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (7):**

- `api/v1/bottle_barcodes.php`:323 — `api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);`
- `includes/honey_bottle_barcodes.php`:832 — `return honey_bottle_pack_session_get($pdo, $existing);`
- `includes/honey_bottle_barcodes.php`:837 — `return honey_bottle_pack_session_get($pdo, (int) $pdo->lastInsertId());`
- `includes/honey_bottle_barcodes.php`:1006 — `$detail = honey_bottle_pack_session_get($pdo, (int) $r['id']);`
- `includes/honey_bottle_barcodes.php`:1039 — `$sess = honey_bottle_pack_session_get($pdo, $sessionId);`
- `includes/honey_bottle_barcodes.php`:1070 — `'session' => honey_bottle_pack_session_get($pdo, $sessionId),`
- `includes/honey_bottle_barcodes.php`:1151 — `return honey_bottle_pack_session_get($pdo, $sessionId);`

## honey_bottle_pack_session_list {#honey_bottle_pack_session_list}

**Tables:** `honey_bottle_pack_session_items`, `honey_bottle_pack_sessions`, `honey_bottle_units`, `honey_packaging_rows`, `users`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:331 — `api_ok(honey_bottle_pack_session_list(`

## honey_bottle_pack_session_scan {#honey_bottle_pack_session_scan}

**Tables:** `honey_bottle_pack_session_items`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:349 — `api_ok(honey_bottle_pack_session_scan(`

## honey_bottle_pack_session_confirm {#honey_bottle_pack_session_confirm}

**Tables:** `honey_bottle_pack_sessions`, `honey_bottle_pack_session_items`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:362 — `api_ok(['session' => honey_bottle_pack_session_confirm(`

## honey_bottle_pack_session_discard {#honey_bottle_pack_session_discard}

**Tables:** `honey_bottle_pack_sessions`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:375 — `honey_bottle_pack_session_discard(ims_pdo(), (int) ($in['session_id'] ?? 0));`

## honey_bottle_write_sale_log {#honey_bottle_write_sale_log}

**Tables:** `honey_sales_log`

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:1125 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:1296 — `honey_bottle_write_sale_log($pdo, [`
- `includes/honey_bottle_barcodes.php`:1352 — `honey_bottle_write_sale_log($pdo, [`

## honey_bottle_pos_lookup {#honey_bottle_pos_lookup}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:384 — `api_ok(['barcode' => honey_bottle_pos_lookup(ims_pdo(), $code)]);`

## honey_bottle_pos_consume {#honey_bottle_pos_consume}

**Tables:** `honey_bottle_units`, `honey_packaging_rows`

**Callers (2):**

- `includes/honey_pos.php`:404 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`
- `includes/honey_pos.php`:623 — `honey_bottle_pos_consume($pdo, $unitIds, $saleId, 1);`

## honey_bottle_opened_return_scan {#honey_bottle_opened_return_scan}

**Tables:** `honey_bottle_barcodes`, `honey_bottle_units`, `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:393 — `api_ok(['barcode' => honey_bottle_opened_return_scan(`

## honey_bottle_rts_receive {#honey_bottle_rts_receive}

**Tables:** `honey_bottle_units`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:411 — `api_ok(honey_bottle_rts_receive(ims_pdo(), (int) api_require_ims_user()['id'], $codes, $pool));`
- `includes/rts_case_handlers.php`:510 — `honey_bottle_rts_receive($pdo, 0, $bottleCodes, $pool);`

## honey_bottle_backfill_preview {#honey_bottle_backfill_preview}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:418 — `api_ok(honey_bottle_backfill_preview(ims_pdo()));`
- `includes/honey_bottle_barcodes.php`:1485 — `$preview = honey_bottle_backfill_preview($pdo);`

## honey_bottle_unit_count_for_row {#honey_bottle_unit_count_for_row}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1437 — `$already = honey_bottle_unit_count_for_row($pdo, (int) $pk['id'], $size);`

## honey_bottle_backfill_generate {#honey_bottle_backfill_generate}

**Callers (1):**

- `api/v1/bottle_barcodes.php`:426 — `$created = honey_bottle_backfill_generate(ims_pdo(), (int) $user['id']);`

## honey_bottle_reconcile {#honey_bottle_reconcile}

**Tables:** `honey_packaging_rows`, `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:434 — `api_ok(honey_bottle_reconcile(ims_pdo()));`

## honey_bottle_stock_summary {#honey_bottle_stock_summary}

**Tables:** `honey_bottle_units`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:441 — `api_ok(['rows' => honey_bottle_stock_summary(ims_pdo())]);`

## honey_bottle_code128_svg {#honey_bottle_code128_svg}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:539 — `$svg = honey_bottle_code128_svg($code);`
- `includes/honey_bottle_barcodes.php`:1801 — `$svg = honey_bottle_code128_svg($code);`

## honey_bottle_batch_codes {#honey_bottle_batch_codes}

**Tables:** `honey_bottle_barcodes`, `honey_barcode_batches`

**Callers (1):**

- `includes/honey_bottle_barcodes.php`:1790 — `$codes = honey_bottle_batch_codes($pdo, $batchId);`

## honey_bottle_ensure_packaging_shell {#honey_bottle_ensure_packaging_shell}

**Tables:** `honey_packaging_rows`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:122 — `$pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);`
- `includes/honey_bottle_barcodes.php`:469 — `$shell = honey_bottle_ensure_packaging_shell($pdo, $userId, $productionRowId);`

## honey_bottle_create_custom_packaging {#honey_bottle_create_custom_packaging}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:140 — `$pk = honey_bottle_create_custom_packaging(`

## honey_bottle_serialize_packaging_option {#honey_bottle_serialize_packaging_option}

**Callers (3):**

- `api/v1/bottle_barcodes.php`:124 — `'packaging' => honey_bottle_serialize_packaging_option($pk, $before === null),`
- `api/v1/bottle_barcodes.php`:150 — `'packaging' => honey_bottle_serialize_packaging_option($pk, !$reused),`
- `includes/honey_bottle_barcodes.php`:1921 — `$rows[] = honey_bottle_serialize_packaging_option($r, false);`

## honey_bottle_label_sheet_html {#honey_bottle_label_sheet_html}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:271 — `$html = honey_bottle_label_sheet_html(ims_pdo(), $id);`
- `includes/dept_pouch_barcodes.php`:580 — `honey_bottle_label_sheet_html(). */`

## honey_bottle_packaging_options {#honey_bottle_packaging_options}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `api/v1/bottle_barcodes.php`:102 — `api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);`

