---
title: dept_pouch_barcodes
source: includes/dept_pouch_barcodes.php
generated: 2026-08-15T09:53:41+02:00
type: module
function_count: 27
---


# dept_pouch_barcodes

> Source: `includes/dept_pouch_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/dept_production.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_inventory.php`

## dept_pouch_can_ops {#dept_pouch_can_ops}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:24 — `if (!dept_pouch_can_ops((string) $user['role'])) {`

## dept_pouch_supports {#dept_pouch_supports}

**Callers (6):**

- `api/v1/dept_pouch_barcodes.php`:40 — `if (!dept_pouch_supports(strtolower($key))) {`
- `includes/dept_pos.php`:87 — `if (dept_pouch_supports($productKey) && $stockSource === 'packaging') {`
- `includes/dept_pos.php`:110 — `} elseif (dept_pouch_supports($productKey) && $stockSource === 'rts' && $unitIds === []) {`
- `includes/dept_pouch_barcodes.php`:27 — `if (!dept_pouch_supports($productKey)) {`
- `includes/honey_bottle_barcodes.php`:1050 — `if ($productKey !== '' && $productKey !== 'honey' && !dept_pouch_supports($productKey)) {`
- `includes/pack_cancel_restock.php`:424 — `if (!dept_pouch_supports($productKey)) {`

## dept_pouch_normalize_product_key {#dept_pouch_normalize_product_key}

**Callers (6):**

- `includes/dept_pouch_barcodes.php`:173 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:225 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:276 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:311 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:342 — `$productKey = dept_pouch_normalize_product_key((string) $req['product_key']);`
- `includes/dept_pouch_barcodes.php`:415 — `$productKey = dept_pouch_normalize_product_key($productKey);`

## dept_pouch_product_label {#dept_pouch_product_label}

**Callers (11):**

- `includes/barcode_verify.php`:100 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/dept_pouch_barcodes.php`:437 — `'This barcode belongs to ' . dept_pouch_product_label($codeProduct) . '.'`
- `includes/dept_pouch_barcodes.php`:463 — `)->execute([dept_pouch_product_label($productKey), (int) $fin['id']]);`
- `includes/dept_pouch_barcodes.php`:587 — `$productLabel = dept_pouch_product_label((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:733 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/dept_pouch_barcodes.php`:826 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/honey_bottle_barcodes.php`:937 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/honey_bottle_barcodes.php`:1246 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/pack_cancel_restock.php`:103 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/pack_cancel_restock.php`:232 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/pack_cancel_restock.php`:237 — `'variant' => dept_pouch_product_label($productKey),`

## dept_pouch_unit_singular {#dept_pouch_unit_singular}

**Callers (8):**

- `includes/barcode_verify.php`:101 — `$unit = dept_pouch_unit_singular($productKey);`
- `includes/dept_pouch_barcodes.php`:620 — `$unit = dept_pouch_unit_singular((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:737 — `'unit_noun' => dept_pouch_unit_singular($productKey),`
- `includes/honey_bottle_barcodes.php`:938 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/honey_bottle_barcodes.php`:1247 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/pack_cancel_restock.php`:111 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:122 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:238 — `'size_label' => dept_pouch_unit_singular($productKey),`

## dept_pouch_normalize_code {#dept_pouch_normalize_code}

**Callers (5):**

- `includes/dept_pouch_barcodes.php`:416 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:697 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:791 — `$code = dept_pouch_normalize_code((string) $raw);`
- `includes/honey_bottle_barcodes.php`:1224 — `$code = dept_pouch_normalize_code($code);`
- `includes/pack_cancel_restock.php`:212 — `$code = dept_pouch_normalize_code($code);`

## dept_pouch_normalize_batch_no {#dept_pouch_normalize_batch_no}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:174 — `$batchNo = dept_pouch_normalize_batch_no($batchNo);`

## dept_pouch_random_code {#dept_pouch_random_code}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:373 — `$insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);`

## dept_pouch_next_sequence {#dept_pouch_next_sequence}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:360 — `$start = dept_pouch_next_sequence($pdo, $qty);`

## dept_pouch_serialize_request {#dept_pouch_serialize_request}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:217 — `return dept_pouch_serialize_request($row);`
- `includes/dept_pouch_barcodes.php`:256 — `$rows[] = dept_pouch_serialize_request($row);`

## dept_pouch_serialize_batch {#dept_pouch_serialize_batch}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:303 — `return dept_pouch_serialize_batch($row);`
- `includes/dept_pouch_barcodes.php`:324 — `$rows[] = dept_pouch_serialize_batch($row);`

## dept_pouch_request_create {#dept_pouch_request_create}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:68 — `$row = dept_pouch_request_create(`

## dept_pouch_request_get {#dept_pouch_request_get}

**Tables:** `users`

**Callers (6):**

- `api/v1/dept_pouch_barcodes.php`:93 — `api_ok(['request' => dept_pouch_request_get(ims_pdo(), $id)]);`
- `includes/dept_pouch_barcodes.php`:190 — `return dept_pouch_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/dept_pouch_barcodes.php`:264 — `$req = dept_pouch_request_get($pdo, $id);`
- `includes/dept_pouch_barcodes.php`:335 — `$req = dept_pouch_request_get($pdo, $requestId);`
- `includes/dept_pouch_barcodes.php`:502 — `$out['request'] = dept_pouch_request_get($pdo, (int) $row['request_id']);`
- `includes/dept_pouch_barcodes.php`:515 — `$req = dept_pouch_request_get($pdo, $requestId);`

## dept_pouch_request_list {#dept_pouch_request_list}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:85 — `api_ok(dept_pouch_request_list(ims_pdo(), api_dept_pouch_product_key(), $status, $page, 25));`

## dept_pouch_request_cancel {#dept_pouch_request_cancel}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:102 — `dept_pouch_request_cancel(`

## dept_pouch_pending_request_count {#dept_pouch_pending_request_count}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:115 — `api_ok(['count' => dept_pouch_pending_request_count(ims_pdo(), api_dept_pouch_product_key())]);`

## dept_pouch_batch_get {#dept_pouch_batch_get}

**Tables:** `users`

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:389 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:405 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:584 — `$batch = dept_pouch_batch_get($pdo, $batchId);`

## dept_pouch_batch_list {#dept_pouch_batch_list}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:136 — `api_ok(['rows' => dept_pouch_batch_list(ims_pdo(), api_dept_pouch_product_key())]);`

## dept_pouch_generate_for_request {#dept_pouch_generate_for_request}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:124 — `$batch = dept_pouch_generate_for_request(`

## dept_pouch_mark_printed {#dept_pouch_mark_printed}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:145 — `api_ok(['batch' => dept_pouch_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);`

## dept_pouch_scan_stock_in {#dept_pouch_scan_stock_in}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:164 — `$result = dept_pouch_scan_stock_in(`

## dept_pouch_request_scan_log {#dept_pouch_request_scan_log}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:180 — `api_ok(dept_pouch_request_scan_log(ims_pdo(), $id, $page));`

## dept_pouch_batch_codes {#dept_pouch_batch_codes}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:585 — `$codes = dept_pouch_batch_codes($pdo, $batchId);`

## dept_pouch_label_sheet_html {#dept_pouch_label_sheet_html}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:154 — `echo dept_pouch_label_sheet_html(ims_pdo(), $id);`

## dept_pouch_pos_lookup {#dept_pouch_pos_lookup}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:192 — `api_ok(['barcode' => dept_pouch_pos_lookup(ims_pdo(), $code)]);`

## dept_pouch_pos_consume {#dept_pouch_pos_consume}

**Callers (2):**

- `includes/dept_pos.php`:257 — `dept_pouch_pos_consume($pdo, $unitIds, $saleId);`
- `includes/dept_pos.php`:406 — `dept_pouch_pos_consume($pdo, $unitIds, $saleId);`

## dept_pouch_rts_receive {#dept_pouch_rts_receive}

**Callers (1):**

- `includes/rts_case_handlers.php`:524 — `dept_pouch_rts_receive($pdo, $remaining, $pool);`

