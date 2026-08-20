---
title: dept_pouch_barcodes
source: includes/dept_pouch_barcodes.php
generated: 2026-08-20T05:19:36+02:00
type: module
function_count: 33
---


# dept_pouch_barcodes

> Source: `includes/dept_pouch_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/dept_production.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_inventory.php`
- `includes/finished_leak_damage.php`

## dept_pouch_can_ops {#dept_pouch_can_ops}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:24 — `if (!dept_pouch_can_ops($user, $productKey)) {`
- `includes/dept_pouch_barcodes.php`:310 — `if (!dept_pouch_can_ops($user, $key)) {`

## dept_pouch_supports_defect {#dept_pouch_supports_defect}

**Callers (3):**

- `api/v1/dept_pouch_barcodes.php`:273 — `if (!dept_pouch_supports_defect($productKey)) {`
- `api/v1/dept_pouch_barcodes.php`:295 — `if (!dept_pouch_supports_defect($productKey)) {`
- `includes/dept_pouch_barcodes.php`:1023 — `if (!dept_pouch_supports_defect($productKey)) {`

## dept_pouch_supports {#dept_pouch_supports}

**Callers (6):**

- `api/v1/dept_pouch_barcodes.php`:62 — `if (!dept_pouch_supports(strtolower($key))) {`
- `includes/dept_pos.php`:87 — `if (dept_pouch_supports($productKey) && $stockSource === 'packaging') {`
- `includes/dept_pos.php`:110 — `} elseif (dept_pouch_supports($productKey) && $stockSource === 'rts' && $unitIds === []) {`
- `includes/dept_pouch_barcodes.php`:43 — `if (!dept_pouch_supports($productKey)) {`
- `includes/honey_bottle_barcodes.php`:1167 — `if ($productKey !== '' && $productKey !== 'honey' && !dept_pouch_supports($productKey)) {`
- `includes/pack_cancel_restock.php`:424 — `if (!dept_pouch_supports($productKey)) {`

## dept_pouch_normalize_product_key {#dept_pouch_normalize_product_key}

**Callers (8):**

- `includes/dept_pouch_barcodes.php`:189 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:241 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:292 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:345 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:376 — `$productKey = dept_pouch_normalize_product_key((string) $req['product_key']);`
- `includes/dept_pouch_barcodes.php`:449 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:568 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:1022 — `$productKey = dept_pouch_normalize_product_key($productKey);`

## dept_pouch_product_label {#dept_pouch_product_label}

**Callers (13):**

- `includes/barcode_verify.php`:100 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/dept_pouch_barcodes.php`:471 — `'This barcode belongs to ' . dept_pouch_product_label($codeProduct) . '.'`
- `includes/dept_pouch_barcodes.php`:497 — `)->execute([dept_pouch_product_label($productKey), (int) $fin['id']]);`
- `includes/dept_pouch_barcodes.php`:755 — `$productLabel = dept_pouch_product_label((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:901 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/dept_pouch_barcodes.php`:994 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/finished_leak_damage.php`:39 — `return dept_pouch_product_label((string) ($row['product_key'] ?? ''));`
- `includes/finished_leak_damage.php`:258 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/honey_bottle_barcodes.php`:1054 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/honey_bottle_barcodes.php`:1363 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/pack_cancel_restock.php`:103 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/pack_cancel_restock.php`:232 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/pack_cancel_restock.php`:237 — `'variant' => dept_pouch_product_label($productKey),`

## dept_pouch_unit_singular {#dept_pouch_unit_singular}

**Callers (8):**

- `includes/barcode_verify.php`:101 — `$unit = dept_pouch_unit_singular($productKey);`
- `includes/dept_pouch_barcodes.php`:788 — `$unit = dept_pouch_unit_singular((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:905 — `'unit_noun' => dept_pouch_unit_singular($productKey),`
- `includes/honey_bottle_barcodes.php`:1055 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/honey_bottle_barcodes.php`:1364 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/pack_cancel_restock.php`:111 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:122 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:238 — `'size_label' => dept_pouch_unit_singular($productKey),`

## dept_pouch_normalize_code {#dept_pouch_normalize_code}

**Callers (6):**

- `includes/dept_pouch_barcodes.php`:450 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:865 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:959 — `$code = dept_pouch_normalize_code((string) $raw);`
- `includes/finished_leak_damage.php`:75 — `$deptCode = dept_pouch_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1341 — `$code = dept_pouch_normalize_code($code);`
- `includes/pack_cancel_restock.php`:212 — `$code = dept_pouch_normalize_code($code);`

## dept_pouch_normalize_batch_no {#dept_pouch_normalize_batch_no}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:190 — `$batchNo = dept_pouch_normalize_batch_no($batchNo);`

## dept_pouch_random_code {#dept_pouch_random_code}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:407 — `$insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);`

## dept_pouch_next_sequence {#dept_pouch_next_sequence}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:394 — `$start = dept_pouch_next_sequence($pdo, $qty);`

## dept_pouch_serialize_request {#dept_pouch_serialize_request}

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:233 — `return dept_pouch_serialize_request($row);`
- `includes/dept_pouch_barcodes.php`:272 — `$rows[] = dept_pouch_serialize_request($row);`
- `includes/dept_pouch_barcodes.php`:637 — `$rows[] = dept_pouch_serialize_request($row);`

## dept_pouch_serialize_batch {#dept_pouch_serialize_batch}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:337 — `return dept_pouch_serialize_batch($row);`
- `includes/dept_pouch_barcodes.php`:358 — `$rows[] = dept_pouch_serialize_batch($row);`

## dept_pouch_request_create {#dept_pouch_request_create}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:95 — `$row = dept_pouch_request_create(`

## dept_pouch_request_get {#dept_pouch_request_get}

**Tables:** `users`

**Callers (7):**

- `api/v1/dept_pouch_barcodes.php`:34 — `$req = dept_pouch_request_get(ims_pdo(), $requestId);`
- `api/v1/dept_pouch_barcodes.php`:121 — `api_ok(['request' => dept_pouch_request_get(ims_pdo(), $id)]);`
- `includes/dept_pouch_barcodes.php`:206 — `return dept_pouch_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/dept_pouch_barcodes.php`:280 — `$req = dept_pouch_request_get($pdo, $id);`
- `includes/dept_pouch_barcodes.php`:369 — `$req = dept_pouch_request_get($pdo, $requestId);`
- `includes/dept_pouch_barcodes.php`:536 — `$out['request'] = dept_pouch_request_get($pdo, (int) $row['request_id']);`
- `includes/dept_pouch_barcodes.php`:672 — `$req = dept_pouch_request_get($pdo, $requestId);`

## dept_pouch_request_list {#dept_pouch_request_list}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:113 — `api_ok(dept_pouch_request_list(ims_pdo(), $productKey, $status, $page, 25));`

## dept_pouch_request_cancel {#dept_pouch_request_cancel}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:131 — `dept_pouch_request_cancel(`

## dept_pouch_pending_request_count {#dept_pouch_pending_request_count}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:145 — `api_ok(['count' => dept_pouch_pending_request_count(ims_pdo(), $productKey)]);`
- `includes/dept_pouch_barcodes.php`:313 — `$counts[$key] = dept_pouch_pending_request_count($pdo, $key);`

## dept_pouch_pending_request_counts_for_user {#dept_pouch_pending_request_counts_for_user}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:152 — `api_ok(['counts' => dept_pouch_pending_request_counts_for_user(ims_pdo(), $user)]);`

## dept_pouch_batch_get {#dept_pouch_batch_get}

**Tables:** `users`

**Callers (4):**

- `api/v1/dept_pouch_barcodes.php`:45 — `$batch = dept_pouch_batch_get(ims_pdo(), $batchId);`
- `includes/dept_pouch_barcodes.php`:423 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:439 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:752 — `$batch = dept_pouch_batch_get($pdo, $batchId);`

## dept_pouch_batch_list {#dept_pouch_batch_list}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:175 — `api_ok(['rows' => dept_pouch_batch_list(ims_pdo(), $productKey)]);`

## dept_pouch_generate_for_request {#dept_pouch_generate_for_request}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:162 — `$batch = dept_pouch_generate_for_request(`

## dept_pouch_mark_printed {#dept_pouch_mark_printed}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:185 — `api_ok(['batch' => dept_pouch_mark_printed(ims_pdo(), $batchId)]);`

## dept_pouch_scan_stock_in {#dept_pouch_scan_stock_in}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:205 — `$result = dept_pouch_scan_stock_in(`

## dept_pouch_scan_report_date {#dept_pouch_scan_report_date}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:569 — `$scanDate = dept_pouch_scan_report_date($scanDate);`
- `includes/dept_pouch_barcodes.php`:676 — `$scanDate = dept_pouch_scan_report_date($scanDate);`

## dept_pouch_scan_report {#dept_pouch_scan_report}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:242 — `api_ok(dept_pouch_scan_report(`

## dept_pouch_request_scan_log {#dept_pouch_request_scan_log}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:222 — `api_ok(dept_pouch_request_scan_log(`

## dept_pouch_batch_codes {#dept_pouch_batch_codes}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:753 — `$codes = dept_pouch_batch_codes($pdo, $batchId);`

## dept_pouch_label_sheet_html {#dept_pouch_label_sheet_html}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:194 — `echo dept_pouch_label_sheet_html(ims_pdo(), $id);`

## dept_pouch_pos_lookup {#dept_pouch_pos_lookup}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:260 — `api_ok(['barcode' => dept_pouch_pos_lookup(ims_pdo(), $code)]);`
- `includes/pos_unified.php`:163 — `$dept = dept_pouch_pos_lookup($pdo, $code);`

## dept_pouch_pos_consume {#dept_pouch_pos_consume}

**Callers (4):**

- `includes/dept_pos.php`:257 — `dept_pouch_pos_consume($pdo, $unitIds, $saleId);`
- `includes/dept_pos.php`:406 — `dept_pouch_pos_consume($pdo, $unitIds, $saleId);`
- `includes/pos_unified.php`:349 — `dept_pouch_pos_consume($pdo, $line['pouch_unit_ids'], $saleId);`
- `includes/pos_unified.php`:481 — `dept_pouch_pos_consume($pdo, $line['pouch_unit_ids'], $saleId);`

## dept_pouch_rts_receive {#dept_pouch_rts_receive}

**Callers (1):**

- `includes/rts_case_handlers.php`:524 — `dept_pouch_rts_receive($pdo, $remaining, $pool);`

## dept_pouch_defect_scan {#dept_pouch_defect_scan}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:277 — `$result = dept_pouch_defect_scan(`

## dept_pouch_defect_history {#dept_pouch_defect_history}

**Tables:** `dept_pouch_defect_log`, `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:300 — `api_ok(dept_pouch_defect_history(ims_pdo(), $productKey, $page));`

