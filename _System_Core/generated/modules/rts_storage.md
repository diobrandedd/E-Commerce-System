---
title: rts_storage
source: includes/rts_storage.php
generated: 2026-06-12T05:34:04+02:00
type: module
function_count: 12
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
- `includes/rts_storage.php`:138 — `$base = trim((string) (rts_storage_cfg()['web_base'] ?? ''));`

## rts_storage_external_enabled {#rts_storage_external_enabled}

**Callers (4):**

- `includes/rts_storage.php`:64 — `if (rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:134 — `if (!rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:165 — `if (rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:205 — `$root = rts_storage_external_enabled()`

## rts_storage_root_path {#rts_storage_root_path}

**Callers (2):**

- `includes/rts_storage.php`:65 — `$dir = rts_storage_root_path() . '/' . $bucket;`
- `includes/rts_storage.php`:140 — `$name = basename(rts_storage_root_path());`

## rts_storage_dir {#rts_storage_dir}

**Callers (10):**

- `api/upload_documentation.php`:106 — `$targetDir = rts_storage_dir(RTS_STORAGE_BUCKET_RTS);`
- `includes/delivery_edit_requests.php`:109 — `$path = rts_storage_dir(RTS_STORAGE_BUCKET_DELIVERIES) . '/' . $safe;`
- `includes/rts_case_handlers.php`:656 — `$targetDir = rts_storage_dir(RTS_STORAGE_BUCKET_RTS);`
- `includes/rts_storage.php`:95 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_DELIVERIES) . '/' . $month;`
- `includes/rts_storage.php`:206 — `? rts_storage_dir($bucket)`
- `includes/rts_storage.php`:225 — `$path = rts_storage_dir($bucket) . '/' . $safe;`
- `includes/rts_storage.php`:232 — `$matches = glob(rts_storage_dir($bucket) . '/*/' . $safe);`
- `includes/sp_sort_service.php`:26 — `return rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT);`
- `includes/sp_sort_service.php`:33 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT) . '/tmp';`
- `includes/suppliers.php`:141 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_SUPPLIER_IDS);`

## rts_storage_deliveries_month_key {#rts_storage_deliveries_month_key}

**Callers (2):**

- `includes/deliveries_upload.php`:43 — `$monthKey = rts_storage_deliveries_month_key($deliveryDate);`
- `includes/rts_storage.php`:94 — `$month = rts_storage_deliveries_month_key($deliveryDate);`

## rts_storage_dir_deliveries_month {#rts_storage_dir_deliveries_month}

**Callers (1):**

- `includes/deliveries_upload.php`:44 — `$targetDir = rts_storage_dir_deliveries_month($deliveryDate);`

## rts_storage_normalize_stored_filename {#rts_storage_normalize_stored_filename}

**Callers (3):**

- `includes/delivery_edit_requests.php`:105 — `$safe = rts_storage_normalize_stored_filename(RTS_STORAGE_BUCKET_DELIVERIES, $photoFilename);`
- `includes/rts_storage.php`:182 — `$safe = rts_storage_normalize_stored_filename($bucket, $filename);`
- `includes/rts_storage.php`:221 — `$safe = rts_storage_normalize_stored_filename($bucket, $filename);`

## rts_storage_web_base {#rts_storage_web_base}

**Callers (1):**

- `includes/rts_storage.php`:166 — `$web = rts_storage_web_base();`

## rts_storage_public_prefix {#rts_storage_public_prefix}

**Callers (4):**

- `includes/rts_case_handlers.php`:588 — `'doc_url_base' => rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''),`
- `includes/rts_storage.php`:188 — `$prefix = rts_storage_public_prefix($bucket, $relativeToApp);`
- `record.php`:762 — `const RTS_DOC_URL_BASE = <?= json_encode(rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''), JSON_UNESCAPED_SLASHES) ?>;`
- `records.php`:834 — `var RTS_DOC_URL_BASE = <?= json_encode(rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''), JSON_UNESCAPED_SLASHES) ?>;`

## rts_storage_public_url {#rts_storage_public_url}

**Callers (6):**

- `api/delivery_match_tracking.php`:76 — `$fileUrl = rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn);`
- `api/upload_documentation.php`:126 — `'file_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_RTS, $name),`
- `includes/online_order_parcels.php`:304 — `$url = rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn);`
- `includes/rts_case_handlers.php`:132 — `'file_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn),`
- `includes/rts_case_handlers.php`:666 — `'file_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_RTS, $name),`
- `includes/suppliers.php`:79 — `'valid_id_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_SUPPLIER_IDS, (string) $row['valid_id_filename'], '/rts/'),`

## rts_storage_csr_tool_root {#rts_storage_csr_tool_root}

**Callers (4):**

- `includes/online_orders_generator.php`:28 — `return rts_storage_csr_tool_root(`
- `includes/order_remarks.php`:13 — `return rts_storage_csr_tool_root(`
- `includes/pdf_to_excel.php`:13 — `return rts_storage_csr_tool_root(`
- `includes/waybill_stamper.php`:13 — `return rts_storage_csr_tool_root(`

## rts_storage_resolve_file {#rts_storage_resolve_file}

**Callers (1):**

- `api/storage_media.php`:21 — `$path = rts_storage_resolve_file($bucket, $filename);`

