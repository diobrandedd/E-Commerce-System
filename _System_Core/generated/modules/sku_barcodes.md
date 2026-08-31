---
title: sku_barcodes
source: includes/sku_barcodes.php
generated: 2026-08-31T09:50:07+02:00
type: module
function_count: 35
---


# sku_barcodes

> Source: `includes/sku_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_products.php`
- `includes/dept_inventory.php`
- `includes/dept_pouch_barcodes.php`
- `includes/rts_schema.php`
- `includes/honey_pos.php`
- `includes/honey_pos.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/honey_bottle_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/dept_pouch_barcodes.php`
- `includes/honey_bottle_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/dept_pouch_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/honey_bottle_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/dept_pouch_barcodes.php`
- `includes/rts_inventory_sync.php`
- `includes/honey_bottle_barcodes.php`

## sku_barcode_catalog_slots {#sku_barcode_catalog_slots}

**Callers (1):**

- `includes/sku_barcodes.php`:329 — `foreach (sku_barcode_catalog_slots() as $slot) {`

## sku_barcode_normalize_code {#sku_barcode_normalize_code}

**Callers (4):**

- `includes/pack_cancel_restock.php`:248 — `$code = sku_barcode_normalize_code($code);`
- `includes/sku_barcodes.php`:58 — `$code = sku_barcode_normalize_code($code);`
- `includes/sku_barcodes.php`:127 — `$code = sku_barcode_normalize_code($rawCode);`
- `includes/sku_barcodes.php`:575 — `$code = sku_barcode_normalize_code($rawCode);`

## sku_barcode_assert_code {#sku_barcode_assert_code}

**Callers (2):**

- `includes/sku_barcodes.php`:428 — `$code = sku_barcode_assert_code($codeTrim);`
- `includes/sku_barcodes.php`:509 — `$code = sku_barcode_assert_code($codeTrim);`

## sku_barcode_serialize {#sku_barcode_serialize}

**Callers (9):**

- `api/v1/bottle_barcodes.php`:285 — `'sku' => sku_barcode_serialize($sku),`
- `api/v1/dept_pouch_barcodes.php`:189 — `'sku' => sku_barcode_serialize($sku),`
- `api/v1/sku_barcodes.php`:242 — `api_ok(['stock_in' => $result, 'sku' => sku_barcode_serialize($sku)]);`
- `includes/barcode_verify.php`:24 — `$ser = sku_barcode_serialize($sku);`
- `includes/pack_cancel_restock.php`:256 — `$lookup = sku_barcode_serialize($sku);`
- `includes/pack_cancel_restock.php`:316 — `$lookup = sku_barcode_serialize($sku);`
- `includes/sku_barcodes.php`:339 — `$out[] = sku_barcode_serialize($byIdentity[$key], $hint);`
- `includes/sku_barcodes.php`:485 — `return sku_barcode_serialize(`
- `includes/sku_barcodes.php`:582 — `$ser = sku_barcode_serialize($sku);`

## sku_barcode_fetch_by_id {#sku_barcode_fetch_by_id}

**Tables:** `product_sku_barcodes`, `users`

**Callers (5):**

- `api/v1/sku_barcodes.php`:205 — `$sku = sku_barcode_fetch_by_id($pdo, (int) $input['sku_id']);`
- `includes/sku_barcodes.php`:480 — `$row = sku_barcode_fetch_by_id($pdo, $id);`
- `includes/sku_barcodes.php`:1204 — `if (sku_barcode_fetch_by_id($pdo, $skuId) === null) {`
- `includes/sku_barcodes.php`:1527 — `$row = sku_barcode_fetch_by_id($pdo, $skuId);`
- `includes/sku_barcodes.php`:1570 — `if (sku_barcode_fetch_by_id($pdo, $skuId) === null) {`

## sku_barcode_fetch_by_code {#sku_barcode_fetch_by_code}

**Tables:** `product_sku_barcodes`, `users`

**Callers (10):**

- `api/v1/bottle_barcodes.php`:440 — `$sku = sku_barcode_fetch_by_code(ims_pdo(), $code);`
- `api/v1/dept_pouch_barcodes.php`:318 — `$sku = sku_barcode_fetch_by_code(ims_pdo(), $code);`
- `api/v1/rts.php`:193 — `$sku = sku_barcode_fetch_by_code(ims_pdo(), $code);`
- `api/v1/sku_barcodes.php`:207 — `$sku = sku_barcode_fetch_by_code($pdo, (string) $input['code']);`
- `includes/barcode_verify.php`:22 — `$sku = sku_barcode_fetch_by_code($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1458 — `$sku = sku_barcode_fetch_by_code($pdo, $code);`
- `includes/inventory_check.php`:240 — `$sku = sku_barcode_fetch_by_code($pdo, $code);`
- `includes/pack_cancel_restock.php`:254 — `$sku = sku_barcode_fetch_by_code($pdo, $code);`
- `includes/pos_unified.php`:133 — `$sku = sku_barcode_fetch_by_code($pdo, $code);`
- `includes/sku_barcodes.php`:580 — `$sku = sku_barcode_fetch_by_code($pdo, $code);`

## sku_barcode_fetch_honey {#sku_barcode_fetch_honey}

**Tables:** `product_sku_barcodes`, `users`

**Callers (6):**

- `api/v1/bottle_barcodes.php`:263 — `$sku = sku_barcode_fetch_honey(ims_pdo(), (string) $req['variant'], (string) $req['size']);`
- `api/v1/bottle_barcodes.php`:317 — `$sku = sku_barcode_fetch_honey($pdo, (string) $req['variant'], (string) $req['size']);`
- `includes/rts_barcode_labels.php`:255 — `$sku = sku_barcode_fetch_honey($pdo, $variant, $size);`
- `includes/rts_barcode_labels.php`:439 — `$sku = sku_barcode_fetch_honey(`
- `includes/sku_barcodes.php`:400 — `? sku_barcode_fetch_honey($pdo, $variant, $size)`
- `includes/sku_barcodes.php`:904 — `$sku = sku_barcode_fetch_honey($pdo, $variant, $size);`

## sku_barcode_fetch_dept {#sku_barcode_fetch_dept}

**Tables:** `product_sku_barcodes`, `users`

**Callers (6):**

- `api/v1/dept_pouch_barcodes.php`:167 — `$sku = sku_barcode_fetch_dept(ims_pdo(), $productKey);`
- `api/v1/dept_pouch_barcodes.php`:225 — `$sku = sku_barcode_fetch_dept($pdo, $productKey);`
- `includes/rts_barcode_labels.php`:279 — `$sku = sku_barcode_fetch_dept($pdo, $productKey);`
- `includes/rts_barcode_labels.php`:445 — `$sku = sku_barcode_fetch_dept($pdo, (string) ($line['product_key'] ?? ''));`
- `includes/sku_barcodes.php`:401 — `: sku_barcode_fetch_dept($pdo, $productKey);`
- `includes/sku_barcodes.php`:952 — `$sku = sku_barcode_fetch_dept($pdo, $productKey);`

## sku_barcode_selling_price_hint {#sku_barcode_selling_price_hint}

**Tables:** `products`

**Callers (3):**

- `includes/sku_barcodes.php`:331 — `$hint = sku_barcode_selling_price_hint(`
- `includes/sku_barcodes.php`:422 — `'selling_price_hint' => sku_barcode_selling_price_hint($pdo, $kind, $variant, $size, $productKey),`
- `includes/sku_barcodes.php`:487 — `sku_barcode_selling_price_hint($pdo, $kind, $variant, $size, $productKey)`

## sku_barcode_product_ids_for_slot {#sku_barcode_product_ids_for_slot}

**Tables:** `products`

**Callers (1):**

- `includes/sku_barcodes.php`:295 — `$ids = sku_barcode_product_ids_for_slot($pdo, $kind, $variant, $size, $productKey);`

## sku_barcode_update_selling_price {#sku_barcode_update_selling_price}

**Tables:** `products`

**Callers (1):**

- `includes/sku_barcodes.php`:534 — `sku_barcode_update_selling_price(`

## sku_barcode_list_settings {#sku_barcode_list_settings}

**Tables:** `product_sku_barcodes`, `users`

**Callers (1):**

- `api/v1/sku_barcodes.php`:39 — `api_ok(['rows' => sku_barcode_list_settings(ims_pdo())]);`

## sku_barcode_save {#sku_barcode_save}

**Tables:** `product_sku_barcodes`, `honey_bottle_barcodes`

**Callers (2):**

- `api/v1/sku_barcodes.php`:65 — `$row = sku_barcode_save(`
- `includes/sku_barcodes.php`:545 — `$out[] = sku_barcode_save(`

## sku_barcode_save_bulk {#sku_barcode_save_bulk}

**Callers (1):**

- `api/v1/sku_barcodes.php`:95 — `$rows = sku_barcode_save_bulk(ims_pdo(), (int) $user['id'], $rawItems);`

## sku_barcode_lookup {#sku_barcode_lookup}

**Callers (1):**

- `api/v1/sku_barcodes.php`:48 — `api_ok(['lookup' => sku_barcode_lookup(ims_pdo(), $code)]);`

## sku_barcode_credit_honey_requests {#sku_barcode_credit_honey_requests}

**Tables:** `honey_bottle_requests`, `honey_barcode_batches`, `honey_bottle_barcodes`, `sku_barcode_stock_events`

**Callers (1):**

- `includes/sku_barcodes.php`:907 — `$credit = sku_barcode_credit_honey_requests(`

## sku_barcode_refresh_honey_request_status {#sku_barcode_refresh_honey_request_status}

**Tables:** `honey_bottle_requests`, `honey_barcode_batches`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/sku_barcodes.php`:693 — `sku_barcode_refresh_honey_request_status($pdo, $reqId);`

## sku_barcode_credit_dept_requests {#sku_barcode_credit_dept_requests}

**Tables:** `sku_barcode_stock_events`

**Callers (1):**

- `includes/sku_barcodes.php`:955 — `$credit = sku_barcode_credit_dept_requests(`

## sku_barcode_refresh_dept_request_status {#sku_barcode_refresh_dept_request_status}

**Callers (1):**

- `includes/sku_barcodes.php`:778 — `sku_barcode_refresh_dept_request_status($pdo, $reqId);`

## sku_barcode_print_job_list {#sku_barcode_print_job_list}

**Tables:** `sku_barcode_print_jobs`, `product_sku_barcodes`, `users`, `honey_bottle_requests`

**Callers (2):**

- `api/v1/bottle_barcodes.php`:364 — `api_ok(['rows' => sku_barcode_print_job_list(ims_pdo(), 'honey', (int) ($_GET['limit'] ?? 40))]);`
- `api/v1/dept_pouch_barcodes.php`:272 — `$rows = sku_barcode_print_job_list(ims_pdo(), 'dept', (int) ($_GET['limit'] ?? 40));`

## sku_barcode_honey_stock_in {#sku_barcode_honey_stock_in}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:465 — `$stocked = sku_barcode_honey_stock_in(`
- `api/v1/sku_barcodes.php`:226 — `$result = sku_barcode_honey_stock_in(`

## sku_barcode_dept_stock_in {#sku_barcode_dept_stock_in}

**Tables:** `dept_inventory_rows`

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:330 — `$stocked = sku_barcode_dept_stock_in(ims_pdo(), (int) $user['id'], $productKey, $qty);`
- `api/v1/sku_barcodes.php`:235 — `$result = sku_barcode_dept_stock_in(`

## sku_barcode_rts_stock_in {#sku_barcode_rts_stock_in}

**Callers (2):**

- `api/v1/rts.php`:201 — `'unit' => sku_barcode_rts_stock_in(`
- `api/v1/sku_barcodes.php`:216 — `$result = sku_barcode_rts_stock_in(`

## sku_barcode_honey_deduct {#sku_barcode_honey_deduct}

**Tables:** `honey_packaging_rows`

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:1794 — `sku_barcode_honey_deduct(`
- `includes/pos_unified.php`:474 — `sku_barcode_honey_deduct(`
- `includes/pos_unified.php`:636 — `sku_barcode_honey_deduct(`

## sku_barcode_dept_deduct {#sku_barcode_dept_deduct}

**Callers (3):**

- `includes/honey_bottle_barcodes.php`:1858 — `sku_barcode_dept_deduct($pdo, $userId, $productKey, $qty, $pool, 'parcel');`
- `includes/pos_unified.php`:493 — `sku_barcode_dept_deduct(`
- `includes/pos_unified.php`:655 — `sku_barcode_dept_deduct(`

## sku_barcode_honey_credit {#sku_barcode_honey_credit}

**Callers (1):**

- `includes/pack_cancel_restock.php`:624 — `sku_barcode_honey_credit(`

## sku_barcode_dept_credit {#sku_barcode_dept_credit}

**Tables:** `dept_inventory_rows`

**Callers (1):**

- `includes/pack_cancel_restock.php`:637 — `sku_barcode_dept_credit($pdo, $userId, $productKey, $qty, $pool);`

## sku_barcode_record_print {#sku_barcode_record_print}

**Tables:** `sku_barcode_print_jobs`

**Callers (7):**

- `api/v1/bottle_barcodes.php`:271 — `$jobId = sku_barcode_record_print(`
- `api/v1/bottle_barcodes.php`:328 — `sku_barcode_record_print(`
- `api/v1/dept_pouch_barcodes.php`:175 — `$jobId = sku_barcode_record_print(`
- `api/v1/dept_pouch_barcodes.php`:235 — `sku_barcode_record_print(`
- `api/v1/sku_barcodes.php`:163 — `$jobId = sku_barcode_record_print(`
- `includes/rts_barcode_labels.php`:262 — `sku_barcode_record_print($pdo, $userId, (int) $sku['id'], $qty, 'rts', null, null, $jobId);`
- `includes/rts_barcode_labels.php`:286 — `sku_barcode_record_print($pdo, $userId, (int) $sku['id'], $qty, 'rts', null, null, $jobId);`

## sku_barcode_product_info_text {#sku_barcode_product_info_text}

**Callers (3):**

- `includes/rts_barcode_labels.php`:421 — `$bannerText = sku_barcode_product_info_text($line);`
- `includes/sku_barcodes.php`:1280 — `$topText = sku_barcode_product_info_text($product);`
- `includes/sku_barcodes.php`:1537 — `: sku_barcode_product_info_text($row);`

## sku_barcode_label_cell_html {#sku_barcode_label_cell_html}

**Callers (3):**

- `includes/honey_warehouse_barcodes.php`:346 — `$stream[] = ['slots' => 1, 'html' => sku_barcode_label_cell_html($code)];`
- `includes/rts_barcode_labels.php`:460 — `'html' => sku_barcode_label_cell_html((string) $row['code'], $line),`
- `includes/sku_barcodes.php`:1550 — `'html' => sku_barcode_label_cell_html($code, $row),`

## sku_barcode_label_banner_html {#sku_barcode_label_banner_html}

**Callers (2):**

- `includes/rts_barcode_labels.php`:427 — `'html' => sku_barcode_label_banner_html($bannerText),`
- `includes/sku_barcodes.php`:1544 — `'html' => sku_barcode_label_banner_html($bannerText),`

## sku_barcode_render_packed_label_sheet {#sku_barcode_render_packed_label_sheet}

**Callers (3):**

- `includes/honey_warehouse_barcodes.php`:349 — `return sku_barcode_render_packed_label_sheet(`
- `includes/rts_barcode_labels.php`:468 — `return sku_barcode_render_packed_label_sheet($stream, $title, $labelCount);`
- `includes/sku_barcodes.php`:1562 — `return sku_barcode_render_packed_label_sheet($stream, $title, $labelCount);`

## sku_barcode_combined_label_sheet_html {#sku_barcode_combined_label_sheet_html}

**Callers (2):**

- `api/v1/sku_barcodes.php`:125 — `$html = sku_barcode_combined_label_sheet_html($pdo, $groups);`
- `includes/sku_barcodes.php`:1574 — `return sku_barcode_combined_label_sheet_html($pdo, [`

## sku_barcode_label_sheet_html {#sku_barcode_label_sheet_html}

**Callers (1):**

- `api/v1/sku_barcodes.php`:129 — `$html = sku_barcode_label_sheet_html($pdo, $skuId, $qty);`

## sku_barcode_print_sheet_url {#sku_barcode_print_sheet_url}

**Callers (2):**

- `api/v1/bottle_barcodes.php`:352 — `'print_url' => sku_barcode_print_sheet_url($groups),`
- `api/v1/dept_pouch_barcodes.php`:259 — `'print_url' => sku_barcode_print_sheet_url($groups),`

