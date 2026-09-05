---
title: dept_pouch_barcodes
source: includes/dept_pouch_barcodes.php
generated: 2026-09-05T04:13:57+02:00
type: module
function_count: 36
---


# dept_pouch_barcodes

> Source: `includes/dept_pouch_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/dept_production.php`
- `includes/rts_schema.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_inventory.php`
- `includes/rts_inventory_sync.php`
- `includes/rts_inventory_sync.php`
- `includes/finished_leak_damage.php`

## dept_pouch_can_ops {#dept_pouch_can_ops}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:24 — `if (!dept_pouch_can_ops($user, $productKey)) {`
- `includes/dept_pouch_barcodes.php`:336 — `if (!dept_pouch_can_ops($user, $key)) {`

## dept_pouch_supports_defect {#dept_pouch_supports_defect}

**Callers (3):**

- `api/v1/dept_pouch_barcodes.php`:411 — `if (!dept_pouch_supports_defect($productKey)) {`
- `api/v1/dept_pouch_barcodes.php`:433 — `if (!dept_pouch_supports_defect($productKey)) {`
- `includes/dept_pouch_barcodes.php`:1268 — `if (!dept_pouch_supports_defect($productKey)) {`

## dept_pouch_supports {#dept_pouch_supports}

**Callers (7):**

- `api/v1/dept_pouch_barcodes.php`:62 — `if (!dept_pouch_supports(strtolower($key))) {`
- `includes/dept_pos.php`:87 — `if (dept_pouch_supports($productKey) && $stockSource === 'packaging') {`
- `includes/dept_pos.php`:110 — `} elseif (dept_pouch_supports($productKey) && $stockSource === 'rts' && $unitIds === []) {`
- `includes/dept_pouch_barcodes.php`:44 — `if (!dept_pouch_supports($productKey)) {`
- `includes/honey_bottle_barcodes.php`:1391 — `if ($productKey !== '' && $productKey !== 'honey' && !dept_pouch_supports($productKey)) {`
- `includes/pack_cancel_restock.php`:740 — `if (!dept_pouch_supports($productKey)) {`
- `includes/rts_barcode_labels.php`:277 — `} elseif (dept_pouch_supports($productKey)) {`

## dept_pouch_normalize_product_key {#dept_pouch_normalize_product_key}

**Callers (10):**

- `includes/dept_pouch_barcodes.php`:205 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:263 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:318 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:371 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:402 — `$productKey = dept_pouch_normalize_product_key((string) $req['product_key']);`
- `includes/dept_pouch_barcodes.php`:466 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:536 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:655 — `$productKey = dept_pouch_normalize_product_key($productKey);`
- `includes/dept_pouch_barcodes.php`:1186 — `$productKey = dept_pouch_normalize_product_key((string) $row['product_key']);`
- `includes/dept_pouch_barcodes.php`:1267 — `$productKey = dept_pouch_normalize_product_key($productKey);`

## dept_pouch_product_label {#dept_pouch_product_label}

**Callers (39):**

- `api/v1/rts_barcode_labels.php`:49 — `'label' => dept_pouch_product_label($key),`
- `includes/barcode_verify.php`:132 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/dept_pouch_barcodes.php`:558 — `'This barcode belongs to ' . dept_pouch_product_label($codeProduct) . '.'`
- `includes/dept_pouch_barcodes.php`:584 — `)->execute([dept_pouch_product_label($productKey), (int) $fin['id']]);`
- `includes/dept_pouch_barcodes.php`:877 — `$productLabel = dept_pouch_product_label((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:1023 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/dept_pouch_barcodes.php`:1071 — `dept_pouch_product_label($productKey),`
- `includes/dept_pouch_barcodes.php`:1144 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/dept_pouch_barcodes.php`:1187 — `$label = dept_pouch_product_label($productKey);`
- `includes/finished_leak_damage.php`:39 — `return dept_pouch_product_label((string) ($row['product_key'] ?? ''));`
- `includes/finished_leak_damage.php`:258 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/honey_bottle_barcodes.php`:1272 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/honey_bottle_barcodes.php`:1647 — `'product_label' => dept_pouch_product_label($productKey),`
- `includes/honey_bottle_barcodes.php`:1733 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/honey_bottle_barcodes.php`:1972 — `dept_pouch_product_label($productKey),`
- `includes/inventory_check.php`:201 — `'product_label' => $kind === 'honey' ? 'Honey' : dept_pouch_product_label($productKey),`
- `includes/inventory_check.php`:422 — `'variant' => dept_pouch_product_label($productKey),`
- `includes/inventory_check.php`:520 — `$productLabel = $kind === 'honey' ? 'Honey' : dept_pouch_product_label($productKey);`
- `includes/inventory_check.php`:727 — `$scopeLabels[] = dept_pouch_product_label($productKey);`
- `includes/inventory_check.php`:742 — `'product_label' => dept_pouch_product_label((string) $productKey),`
- `includes/inventory_check.php`:747 — `'variant' => dept_pouch_product_label((string) $productKey),`
- `includes/inventory_check.php`:880 — `'product_label' => $kind === 'honey' ? 'Honey' : dept_pouch_product_label((string) $row['product_key']),`
- `includes/pack_cancel_restock.php`:143 — `: dept_pouch_product_label($productKey);`
- `includes/pack_cancel_restock.php`:184 — `$productLabel = dept_pouch_product_label($productKey);`
- `includes/pack_cancel_restock.php`:407 — `'product_label' => dept_pouch_product_label($productKey),`
- _… and 14 more (run impact.php for full list)_

## dept_pouch_unit_singular {#dept_pouch_unit_singular}

**Callers (14):**

- `includes/barcode_verify.php`:47 — `'unit' => dept_pouch_unit_singular((string) $sku['product_key']),`
- `includes/barcode_verify.php`:48 — `'unit_plural' => dept_pouch_unit_singular((string) $sku['product_key']) === 'jar' ? 'jars' : 'pouches',`
- `includes/barcode_verify.php`:133 — `$unit = dept_pouch_unit_singular($productKey);`
- `includes/dept_pouch_barcodes.php`:910 — `$unit = dept_pouch_unit_singular((string) $batch['product_key']);`
- `includes/dept_pouch_barcodes.php`:1027 — `'unit_noun' => dept_pouch_unit_singular($productKey),`
- `includes/honey_bottle_barcodes.php`:1273 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/honey_bottle_barcodes.php`:1734 — `$unitNoun = dept_pouch_unit_singular($productKey);`
- `includes/inventory_check.php`:423 — `'size' => dept_pouch_unit_singular($productKey),`
- `includes/inventory_check.php`:748 — `'size' => dept_pouch_unit_singular((string) $productKey),`
- `includes/inventory_check.php`:749 — `'size_label' => dept_pouch_unit_singular((string) $productKey),`
- `includes/pack_cancel_restock.php`:144 — `$sizeLabel = $isHoney ? honey_bottle_size_label($size) : dept_pouch_unit_singular($productKey);`
- `includes/pack_cancel_restock.php`:193 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:207 — `'size_label' => dept_pouch_unit_singular($productKey),`
- `includes/pack_cancel_restock.php`:415 — `'size_label' => dept_pouch_unit_singular($productKey),`

## dept_pouch_normalize_code {#dept_pouch_normalize_code}

**Callers (7):**

- `includes/dept_pouch_barcodes.php`:537 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:987 — `$code = dept_pouch_normalize_code($code);`
- `includes/dept_pouch_barcodes.php`:1100 — `$code = dept_pouch_normalize_code((string) $raw);`
- `includes/dept_pouch_barcodes.php`:1164 — `$code = dept_pouch_normalize_code($code);`
- `includes/finished_leak_damage.php`:75 — `$deptCode = dept_pouch_normalize_code($code);`
- `includes/honey_bottle_barcodes.php`:1711 — `$code = dept_pouch_normalize_code($code);`
- `includes/pack_cancel_restock.php`:383 — `$code = dept_pouch_normalize_code($code);`

## dept_pouch_normalize_batch_no {#dept_pouch_normalize_batch_no}

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:83 — `return dept_pouch_normalize_batch_no('SKU-' . $slug . '-' . date('ymdHis'));`
- `includes/dept_pouch_barcodes.php`:208 — `: dept_pouch_normalize_batch_no($batchNo);`
- `includes/dept_pouch_barcodes.php`:467 — `$batchNo = dept_pouch_normalize_batch_no($batchNo);`

## dept_pouch_system_request_batch_no {#dept_pouch_system_request_batch_no}

**Callers (1):**

- `includes/dept_pouch_barcodes.php`:207 — `? dept_pouch_system_request_batch_no($productKey)`

## dept_pouch_random_code {#dept_pouch_random_code}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:433 — `$insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);`
- `includes/dept_pouch_barcodes.php`:498 — `$insC->execute([$batchId, dept_pouch_random_code($pdo, $used), $start + $i]);`

## dept_pouch_next_sequence {#dept_pouch_next_sequence}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:420 — `$start = dept_pouch_next_sequence($pdo, $qty);`
- `includes/dept_pouch_barcodes.php`:485 — `$start = dept_pouch_next_sequence($pdo, $qty);`

## dept_pouch_serialize_request {#dept_pouch_serialize_request}

**Callers (3):**

- `includes/dept_pouch_barcodes.php`:255 — `return dept_pouch_serialize_request($row);`
- `includes/dept_pouch_barcodes.php`:298 — `$rows[] = dept_pouch_serialize_request($row);`
- `includes/dept_pouch_barcodes.php`:747 — `$rows[] = dept_pouch_serialize_request($row);`

## dept_pouch_serialize_batch {#dept_pouch_serialize_batch}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:363 — `return dept_pouch_serialize_batch($row);`
- `includes/dept_pouch_barcodes.php`:384 — `$rows[] = dept_pouch_serialize_batch($row);`

## dept_pouch_request_create {#dept_pouch_request_create}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:97 — `$row = dept_pouch_request_create(`

## dept_pouch_request_get {#dept_pouch_request_get}

**Tables:** `sku_barcode_print_jobs`, `users`

**Callers (12):**

- `api/v1/dept_pouch_barcodes.php`:34 — `$req = dept_pouch_request_get(ims_pdo(), $requestId);`
- `api/v1/dept_pouch_barcodes.php`:123 — `api_ok(['request' => dept_pouch_request_get(ims_pdo(), $id)]);`
- `api/v1/dept_pouch_barcodes.php`:165 — `$req = dept_pouch_request_get(ims_pdo(), $requestId);`
- `api/v1/dept_pouch_barcodes.php`:192 — `'request' => dept_pouch_request_get(ims_pdo(), $requestId),`
- `api/v1/dept_pouch_barcodes.php`:218 — `$req = dept_pouch_request_get($pdo, $requestId);`
- `api/v1/dept_pouch_barcodes.php`:253 — `$requests[] = dept_pouch_request_get($pdo, $requestId);`
- `api/v1/dept_pouch_barcodes.php`:336 — `$payload['request'] = dept_pouch_request_get(ims_pdo(), (int) $stocked['primary_request_id']);`
- `includes/dept_pouch_barcodes.php`:224 — `return dept_pouch_request_get($pdo, (int) $pdo->lastInsertId());`
- `includes/dept_pouch_barcodes.php`:306 — `$req = dept_pouch_request_get($pdo, $id);`
- `includes/dept_pouch_barcodes.php`:395 — `$req = dept_pouch_request_get($pdo, $requestId);`
- `includes/dept_pouch_barcodes.php`:623 — `$out['request'] = dept_pouch_request_get($pdo, (int) $row['request_id']);`
- `includes/dept_pouch_barcodes.php`:794 — `$req = dept_pouch_request_get($pdo, $requestId);`

## dept_pouch_request_list {#dept_pouch_request_list}

**Tables:** `sku_barcode_print_jobs`, `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:115 — `api_ok(dept_pouch_request_list(ims_pdo(), $productKey, $status, $page, 25));`

## dept_pouch_request_cancel {#dept_pouch_request_cancel}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:133 — `dept_pouch_request_cancel(`

## dept_pouch_pending_request_count {#dept_pouch_pending_request_count}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:147 — `api_ok(['count' => dept_pouch_pending_request_count(ims_pdo(), $productKey)]);`
- `includes/dept_pouch_barcodes.php`:339 — `$counts[$key] = dept_pouch_pending_request_count($pdo, $key);`

## dept_pouch_pending_request_counts_for_user {#dept_pouch_pending_request_counts_for_user}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:154 — `api_ok(['counts' => dept_pouch_pending_request_counts_for_user(ims_pdo(), $user)]);`

## dept_pouch_batch_get {#dept_pouch_batch_get}

**Tables:** `users`

**Callers (5):**

- `api/v1/dept_pouch_barcodes.php`:45 — `$batch = dept_pouch_batch_get(ims_pdo(), $batchId);`
- `includes/dept_pouch_barcodes.php`:449 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:510 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:526 — `return dept_pouch_batch_get($pdo, $batchId);`
- `includes/dept_pouch_barcodes.php`:874 — `$batch = dept_pouch_batch_get($pdo, $batchId);`

## dept_pouch_batch_list {#dept_pouch_batch_list}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:285 — `api_ok(['rows' => dept_pouch_batch_list(ims_pdo(), $productKey)]);`

## dept_pouch_generate_for_request {#dept_pouch_generate_for_request}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_pouch_generate_rts_label {#dept_pouch_generate_rts_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_pouch_mark_printed {#dept_pouch_mark_printed}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:295 — `api_ok(['batch' => dept_pouch_mark_printed(ims_pdo(), $batchId)]);`
- `includes/rts_barcode_labels.php`:325 — `dept_pouch_mark_printed($pdo, (int) $line['dept_batch_id']);`

## dept_pouch_scan_stock_in {#dept_pouch_scan_stock_in}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:343 — `$result = dept_pouch_scan_stock_in(`

## dept_pouch_scan_report_date {#dept_pouch_scan_report_date}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:656 — `$scanDate = dept_pouch_scan_report_date($scanDate);`
- `includes/dept_pouch_barcodes.php`:798 — `$scanDate = dept_pouch_scan_report_date($scanDate);`

## dept_pouch_scan_report {#dept_pouch_scan_report}

**Tables:** `sku_barcode_stock_events`, `sku_barcode_print_jobs`, `product_sku_barcodes`, `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:380 — `api_ok(dept_pouch_scan_report(`

## dept_pouch_request_scan_log {#dept_pouch_request_scan_log}

**Tables:** `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:360 — `api_ok(dept_pouch_request_scan_log(`

## dept_pouch_batch_codes {#dept_pouch_batch_codes}

**Callers (2):**

- `includes/dept_pouch_barcodes.php`:875 — `$codes = dept_pouch_batch_codes($pdo, $batchId);`
- `includes/rts_barcode_labels.php`:435 — `$codes = dept_pouch_batch_codes($pdo, (int) $line['dept_batch_id']);`

## dept_pouch_label_sheet_html {#dept_pouch_label_sheet_html}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:304 — `echo dept_pouch_label_sheet_html(ims_pdo(), $id);`

## dept_pouch_pos_lookup {#dept_pouch_pos_lookup}

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:398 — `api_ok(['barcode' => dept_pouch_pos_lookup(ims_pdo(), $code)]);`
- `includes/pos_unified.php`:192 — `$dept = dept_pouch_pos_lookup($pdo, $code);`

## dept_pouch_pos_consume {#dept_pouch_pos_consume}

**Callers (4):**

- `includes/dept_pos.php`:266 — `dept_pouch_pos_consume($pdo, $unitIds, $saleId);`
- `includes/dept_pos.php`:419 — `dept_pouch_pos_consume($pdo, $unitIds, $saleId);`
- `includes/pos_unified.php`:474 — `dept_pouch_pos_consume($pdo, $line['pouch_unit_ids'], $saleId);`
- `includes/pos_unified.php`:638 — `dept_pouch_pos_consume($pdo, $line['pouch_unit_ids'], $saleId);`

## dept_pouch_rts_receive {#dept_pouch_rts_receive}

**Callers (1):**

- `includes/rts_case_handlers.php`:529 — `dept_pouch_rts_receive($pdo, $remaining, $pool);`

## dept_pouch_rts_stock_in_scan {#dept_pouch_rts_stock_in_scan}

**Callers (1):**

- `api/v1/rts.php`:223 — `'unit' => dept_pouch_rts_stock_in_scan(ims_pdo(), (int) $user['id'], $code),`

## dept_pouch_defect_scan {#dept_pouch_defect_scan}

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:415 — `$result = dept_pouch_defect_scan(`

## dept_pouch_defect_history {#dept_pouch_defect_history}

**Tables:** `dept_pouch_defect_log`, `users`

**Callers (1):**

- `api/v1/dept_pouch_barcodes.php`:438 — `api_ok(dept_pouch_defect_history(ims_pdo(), $productKey, $page));`

