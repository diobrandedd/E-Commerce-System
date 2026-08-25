---
title: dept_pouch_barcodes
source: includes/dept_pouch_barcodes.php
generated: 2026-08-25T08:08:57+02:00
type: module
function_count: 34
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
- `includes/dept_pouch_barcodes.php`:313 — `if (!dept_pouch_can_ops($user, $key)) {`

## dept_pouch_supports_defect {#dept_pouch_supports_defect}

**Callers (3):**

- `api/v1/dept_pouch_barcodes.php`:273 — `if (!dept_pouch_supports_defect($productKey)) {`
- `api/v1/dept_pouch_barcodes.php`:295 — `if (!dept_pouch_supports_defect($productKey)) {`
- `includes/dept_pouch_barcodes.php`:1087 — `if (!dept_pouch_supports_defect($productKey)) {`

## dept_pouch_supports {#dept_pouch_supports}

**Callers (7):**

- `api/v1/dept_pouch_barcodes.php`:62 — `if (!dept_pouch_supports(strtolower($key))) {`
- `includes/dept_pos.php`:87 — `if (dept_pouch_supports($productKey) && $stockSource === 'packaging') {`
- `includes/dept_pos.php`:110 — `} elseif (dept_pouch_supports($productKey) && $stockSource === 'rts' && $unitIds === []) {`
- `includes/dept_pouch_barcodes.php`:43 — `if (!dept_pouch_supports($productKey)) {`
- `includes/honey_bottle_barcodes.php`:1182 — `if ($productKey !== '' && $productKey !== 'honey' && !dept_pouch_supports($productKey)) {`
- `includes/pack_cancel_restock.php`:424 — `if (!dept_pouch_supports($productKey)) {`
- `includes/rts_barcode_labels.php`:290 — `} elseif (dept_pouch_supports($productKey)) {`

## dept_pouch_normalize_product_key {#dept_pouch_normalize_product_key}

**Callers (9):**

- `includes/dept_pouch_barcodes.php`:192 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:244 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:295 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:348 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:379 — `$productKey = dept_pouch_normalize_product_key((string) $req['product_key']);`
- `includes/dept_pouch_barcodes.php`:443 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:513 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:632 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:1086 — `$productKey = dept_pouch_normalize_product_key($productKey);`

## dept_pouch_product_label {#dept_pouch_product_label}

**Callers (22):**

- `api/v1/rts_barcode_labels.php`:49 — `'label' => dept_pouch_product_label($key),`
- `includes/barcode_verify.php`:100 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/dept_pouch_barcodes.php`:535 — `'This barcode belongs to ' . dept_pouch_product_label($codeProduct) . '.'`
- `includes/dept_pouch_barcodes.php`:561 — `)->execute([dept_pouch_product_label($productKey), (int) $fin['id']]);`
- `includes/dept_pouch_barcodes.php`:819 — `$productLabel = dept_pouch_product_label((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:965 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/dept_pouch_barcodes.php`:1058 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/finished_leak_damage.php`:39 — `return dept_pouch_product_label((string) ($row['product_key'] ?? ''));`
- `includes/finished_leak_damage.php`:258 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/honey_bottle_barcodes.php`:1069 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/honey_bottle_barcodes.php`:1378 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/inventory_check.php`:201 — `'product_label' => $kind === 'honey' ? 'Honey' : dept_pouch_product_label($productKey),`
- `includes/inventory_check.php`:364 — `'variant' => dept_pouch_product_label($productKey),`
- `includes/inventory_check.php`:462 — `$productLabel = $kind === 'honey' ? 'Honey' : dept_pouch_product_label($productKey);`
- `includes/inventory_check.php`:669 — `$scopeLabels[] = dept_pouch_product_label($productKey);`
- `includes/inventory_check.php`:684 — `'product_label' => dept_pouch_product_label((string) $productKey),`
- `includes/inventory_check.php`:689 — `'variant' => dept_pouch_product_label((string) $productKey),`
- `includes/inventory_check.php`:822 — `'product_label' => $kind === 'honey' ? 'Honey' : dept_pouch_product_label((string) $row['product_key']),`
- `includes/pack_cancel_restock.php`:103 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/pack_cancel_restock.php`:232 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/pack_cancel_restock.php`:237 — `'variant' => dept_pouch_product_label($productKey),`
- `includes/rts_barcode_labels.php`:83 — `: dept_pouch_product_label($productKey);`

## dept_pouch_unit_singular {#dept_pouch_unit_singular}

**Callers (11):**

- `includes/barcode_verify.php`:101 — `$unit = dept_pouch_unit_singular($productKey);`
- `includes/dept_pouch_barcodes.php`:852 — `$unit = dept_pouch_unit_singular((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:969 — `'unit_noun' => dept_pouch_unit_singular($productKey),`
- `includes/honey_bottle_barcodes.php`:1070 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/honey_bottle_barcodes.php`:1379 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/inventory_check.php`:365 — `'size' => dept_pouch_unit_singular($productKey),`
- `includes/inventory_check.php`:690 — `'size' => dept_pouch_unit_singular((string) $productKey),`
- `includes/inventory_check.php`:691 — `'size_label' => dept_pouch_unit_singular((string) $productKey),`
- `includes/pack_cancel_restock.php`:111 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:122 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:238 — `'size_label' => dept_pouch_unit_singular($productKey),`

## dept_pouch_normalize_code {#dept_pouch_normalize_code}

**Callers (6):**

- `includes/dept_pouch_barcodes.php`:514 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:929 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:1023 — `$code = dept_pouch_normalize_code((string) $raw);`
- `includes/finished_leak_damage.php`:75 — `$deptCode = dept_pouch_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1356 — `$code = dept_pouch_normalize_code($code);`
- `includes/pack_cancel_restock.php`:212 — `$code = dept_pouch_normalize_code($code);`

## dept_pouch_normalize_batch_no {#dept_pouch_normalize_batch_no}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:193 — `$batchNo = dept_pouch_normalize_batch_no($batchNo);`
- `includes/dept_pouch_barcodes.php`:444 — `$batchNo = dept_pouch_normalize_batch_no($batchNo);`

## dept_pouch_random_code {#dept_pouch_random_code}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:410 — `$insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);`
- `includes/dept_pouch_barcodes.php`:475 — `$insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);`

## dept_pouch_next_sequence {#dept_pouch_next_sequence}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:397 — `$start = dept_pouch_next_sequence($pdo, $qty);`
- `includes/dept_pouch_barcodes.php`:462 — `$start = dept_pouch_next_sequence($pdo, $qty);`

## dept_pouch_serialize_request {#dept_pouch_serialize_request}

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:236 — `return dept_pouch_serialize_request($row);`
- `includes/dept_pouch_barcodes.php`:275 — `$rows[] = dept_pouch_serialize_request($row);`
- `includes/dept_pouch_barcodes.php`:701 — `$rows[] = dept_pouch_serialize_request($row);`

## dept_pouch_serialize_batch {#dept_pouch_serialize_batch}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:340 — `return dept_pouch_serialize_batch($row);`
- `includes/dept_pouch_barcodes.php`:361 — `$rows[] = dept_pouch_serialize_batch($row);`

## dept_pouch_request_create {#dept_pouch_request_create}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:95 — `$row = dept_pouch_request_create(`

## dept_pouch_request_get {#dept_pouch_request_get}

**Tables:** `users`

**Callers (7):**

- `api/v1/dept_pouch_barcodes.php`:34 — `$req = dept_pouch_request_get(ims_pdo(), $requestId);`
- `api/v1/dept_pouch_barcodes.php`:121 — `api_ok(['request' => dept_pouch_request_get(ims_pdo(), $id)]);`
- `includes/dept_pouch_barcodes.php`:209 — `return dept_pouch_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/dept_pouch_barcodes.php`:283 — `$req = dept_pouch_request_get($pdo, $id);`
- `includes/dept_pouch_barcodes.php`:372 — `$req = dept_pouch_request_get($pdo, $requestId);`
- `includes/dept_pouch_barcodes.php`:600 — `$out['request'] = dept_pouch_request_get($pdo, (int) $row['request_id']);`
- `includes/dept_pouch_barcodes.php`:736 — `$req = dept_pouch_request_get($pdo, $requestId);`

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
- `includes/dept_pouch_barcodes.php`:316 — `$counts[$key] = dept_pouch_pending_request_count($pdo, $key);`

## dept_pouch_pending_request_counts_for_user {#dept_pouch_pending_request_counts_for_user}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:152 — `api_ok(['counts' => dept_pouch_pending_request_counts_for_user(ims_pdo(), $user)]);`

## dept_pouch_batch_get {#dept_pouch_batch_get}

**Tables:** `users`

**Callers (5):**

- `api/v1/dept_pouch_barcodes.php`:45 — `$batch = dept_pouch_batch_get(ims_pdo(), $batchId);`
- `includes/dept_pouch_barcodes.php`:426 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:487 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:503 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:816 — `$batch = dept_pouch_batch_get($pdo, $batchId);`

## dept_pouch_batch_list {#dept_pouch_batch_list}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:175 — `api_ok(['rows' => dept_pouch_batch_list(ims_pdo(), $productKey)]);`

## dept_pouch_generate_for_request {#dept_pouch_generate_for_request}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:162 — `$batch = dept_pouch_generate_for_request(`

## dept_pouch_generate_rts_label {#dept_pouch_generate_rts_label}

**Callers (1):**

- `includes/rts_barcode_labels.php`:291 — `$batch = dept_pouch_generate_rts_label(`

## dept_pouch_mark_printed {#dept_pouch_mark_printed}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:185 — `api_ok(['batch' => dept_pouch_mark_printed(ims_pdo(), $batchId)]);`
- `includes/rts_barcode_labels.php`:339 — `dept_pouch_mark_printed($pdo, (int) $line['dept_batch_id']);`

## dept_pouch_scan_stock_in {#dept_pouch_scan_stock_in}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:205 — `$result = dept_pouch_scan_stock_in(`

## dept_pouch_scan_report_date {#dept_pouch_scan_report_date}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:633 — `$scanDate = dept_pouch_scan_report_date($scanDate);`
- `includes/dept_pouch_barcodes.php`:740 — `$scanDate = dept_pouch_scan_report_date($scanDate);`

## dept_pouch_scan_report {#dept_pouch_scan_report}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:242 — `api_ok(dept_pouch_scan_report(`

## dept_pouch_request_scan_log {#dept_pouch_request_scan_log}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:222 — `api_ok(dept_pouch_request_scan_log(`

## dept_pouch_batch_codes {#dept_pouch_batch_codes}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:817 — `$codes = dept_pouch_batch_codes($pdo, $batchId);`
- `includes/rts_barcode_labels.php`:450 — `$codes = dept_pouch_batch_codes($pdo, (int) $line['dept_batch_id']);`

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

