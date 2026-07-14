---
title: rts_storage
source: includes/rts_storage.php
generated: 2026-07-14T11:51:04+02:00
type: module
function_count: 16
---


# rts_storage

> Source: `includes/rts_storage.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## rts_storage_cfg {#rts_storage_cfg}

**Callers (3):**

- `includes/rts_storage.php`:29 — `return trim((string) (rts_storage_cfg()['root'] ?? '')) !== '';`
- `includes/rts_storage.php`:35 — `$root = trim((string) (rts_storage_cfg()['root'] ?? ''));`
- `includes/rts_storage.php`:165 — `$base = trim((string) (rts_storage_cfg()['web_base'] ?? ''));`

## rts_storage_external_enabled {#rts_storage_external_enabled}

**Callers (4):**

- `includes/rts_storage.php`:64 — `if (rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:161 — `if (!rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:192 — `if (rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:243 — `$root = rts_storage_external_enabled()`

## rts_storage_root_path {#rts_storage_root_path}

**Callers (2):**

- `includes/rts_storage.php`:65 — `$dir = rts_storage_root_path() . '/' . $bucket;`
- `includes/rts_storage.php`:167 — `$name = basename(rts_storage_root_path());`

## rts_storage_dir {#rts_storage_dir}

**Callers (8):**

- `includes/delivery_edit_requests.php`:109 — `$path = rts_storage_dir(RTS_STORAGE_BUCKET_DELIVERIES) . '/' . $safe;`
- `includes/rts_storage.php`:108 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_DELIVERIES) . '/' . $month;`
- `includes/rts_storage.php`:122 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_RTS) . '/' . $month;`
- `includes/rts_storage.php`:244 — `? rts_storage_dir($bucket)`
- `includes/rts_storage.php`:263 — `$bucketDir = rts_storage_dir($bucket);`
- `includes/sp_sort_service.php`:26 — `return rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT);`
- `includes/sp_sort_service.php`:33 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT) . '/tmp';`
- `includes/suppliers.php`:142 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_SUPPLIER_IDS);`

## rts_storage_deliveries_month_key {#rts_storage_deliveries_month_key}

**Callers (2):**

- `includes/deliveries_upload.php`:43 — `$monthKey = rts_storage_deliveries_month_key($deliveryDate);`
- `includes/rts_storage.php`:107 — `$month = rts_storage_deliveries_month_key($deliveryDate);`

## rts_storage_rts_month_key {#rts_storage_rts_month_key}

**Callers (2):**

- `includes/rts_attachments.php`:210 — `$monthKey = rts_storage_rts_month_key($recordDate);`
- `includes/rts_storage.php`:121 — `$month = rts_storage_rts_month_key($recordDate);`

## rts_storage_dir_deliveries_month {#rts_storage_dir_deliveries_month}

**Callers (1):**

- `includes/deliveries_upload.php`:44 — `$targetDir = rts_storage_dir_deliveries_month($deliveryDate);`

## rts_storage_dir_rts_month {#rts_storage_dir_rts_month}

**Callers (1):**

- `includes/rts_attachments.php`:211 — `$targetDir = rts_storage_dir_rts_month($recordDate);`

## rts_storage_normalize_stored_filename {#rts_storage_normalize_stored_filename}

**Callers (3):**

- `includes/delivery_edit_requests.php`:105 — `$safe = rts_storage_normalize_stored_filename(RTS_STORAGE_BUCKET_DELIVERIES, $photoFilename);`
- `includes/rts_storage.php`:209 — `$safe = rts_storage_normalize_stored_filename($bucket, $filename);`
- `includes/rts_storage.php`:259 — `$safe = rts_storage_normalize_stored_filename($bucket, $filename);`

## rts_storage_web_base {#rts_storage_web_base}

**Callers (1):**

- `includes/rts_storage.php`:193 — `$web = rts_storage_web_base();`

## rts_storage_public_prefix {#rts_storage_public_prefix}

**Callers (4):**

- `includes/rts_storage.php`:215 — `$prefix = rts_storage_public_prefix($bucket, $relativeToApp);`
- `includes/rts_storage.php`:226 — `return rts_storage_public_prefix($bucket, '/rts/');`
- `record.php`:786 — `const RTS_DOC_URL_BASE = <?= json_encode(rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''), JSON_UNESCAPED_SLASHES) ?>;`
- `records.php`:834 — `var RTS_DOC_URL_BASE = <?= json_encode(rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''), JSON_UNESCAPED_SLASHES) ?>;`

## rts_storage_public_url {#rts_storage_public_url}

**Callers (2):**

- `includes/rts_storage.php`:231 — `return rts_storage_public_url($bucket, $filename, '/rts/');`
- `includes/suppliers.php`:80 — `'valid_id_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_SUPPLIER_IDS, (string) $row['valid_id_filename'], '/rts/'),`

## rts_storage_public_prefix_for_app {#rts_storage_public_prefix_for_app}

**Callers (1):**

- `includes/rts_case_handlers.php`:1004 — `'doc_url_base' => rts_storage_public_prefix_for_app(RTS_STORAGE_BUCKET_RTS),`

## rts_storage_public_url_for_app {#rts_storage_public_url_for_app}

**Callers (6):**

- `api/delivery_match_tracking.php`:76 — `$fileUrl = rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_DELIVERIES, $fn);`
- `includes/online_order_parcels.php`:529 — `$url = rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_DELIVERIES, $fn);`
- `includes/rts_attachments.php`:223 — `'file_url' => rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_RTS, $storedName),`
- `includes/rts_case_handlers.php`:140 — `'download_url' => rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_DELIVERIES, $fn),`
- `includes/rts_case_handlers.php`:265 — `'file_url' => rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_DELIVERIES, $fn),`
- `includes/rts_prefill.php`:182 — `'file_url' => rts_storage_public_url_for_app(RTS_STORAGE_BUCKET_DELIVERIES, $fn),`

## rts_storage_csr_tool_root {#rts_storage_csr_tool_root}

**Callers (4):**

- `includes/online_orders_generator.php`:69 — `return rts_storage_csr_tool_root(`
- `includes/order_remarks.php`:13 — `return rts_storage_csr_tool_root(`
- `includes/pdf_to_excel.php`:13 — `return rts_storage_csr_tool_root(`
- `includes/waybill_stamper.php`:13 — `return rts_storage_csr_tool_root(`

## rts_storage_resolve_file {#rts_storage_resolve_file}

**Callers (2):**

- `api/delivery_resolve.php`:99 — `$path = rts_storage_resolve_file(RTS_STORAGE_BUCKET_DELIVERIES, $photo);`
- `api/storage_media.php`:21 — `$path = rts_storage_resolve_file($bucket, $filename);`

