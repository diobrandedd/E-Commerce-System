---
title: rts_storage
source: includes/rts_storage.php
generated: 2026-06-04T03:07:06+02:00
type: module
function_count: 11
---


# rts_storage

> Source: `includes/rts_storage.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## rts_storage_cfg {#rts_storage_cfg}

**Callers (3):**

- `includes/rts_storage.php`:25 — `return trim((string) (rts_storage_cfg()['root'] ?? '')) !== '';`
- `includes/rts_storage.php`:31 — `$root = trim((string) (rts_storage_cfg()['root'] ?? ''));`
- `includes/rts_storage.php`:130 — `$base = trim((string) (rts_storage_cfg()['web_base'] ?? ''));`

## rts_storage_external_enabled {#rts_storage_external_enabled}

**Callers (3):**

- `includes/rts_storage.php`:56 — `if (rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:126 — `if (!rts_storage_external_enabled()) {`
- `includes/rts_storage.php`:157 — `if (rts_storage_external_enabled()) {`

## rts_storage_root_path {#rts_storage_root_path}

**Callers (2):**

- `includes/rts_storage.php`:57 — `$dir = rts_storage_root_path() . '/' . $bucket;`
- `includes/rts_storage.php`:132 — `$name = basename(rts_storage_root_path());`

## rts_storage_dir {#rts_storage_dir}

**Callers (7):**

- `api/upload_documentation.php`:106 — `$targetDir = rts_storage_dir(RTS_STORAGE_BUCKET_RTS);`
- `includes/rts_storage.php`:87 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_DELIVERIES) . '/' . $month;`
- `includes/rts_storage.php`:194 — `$path = rts_storage_dir($bucket) . '/' . $safe;`
- `includes/rts_storage.php`:201 — `$matches = glob(rts_storage_dir($bucket) . '/*/' . $safe);`
- `includes/sp_sort_service.php`:26 — `return rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT);`
- `includes/sp_sort_service.php`:33 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_SP_SORT) . '/tmp';`
- `includes/suppliers.php`:141 — `$dir = rts_storage_dir(RTS_STORAGE_BUCKET_SUPPLIER_IDS);`

## rts_storage_deliveries_month_key {#rts_storage_deliveries_month_key}

**Callers (2):**

- `api/delivery_bulk.php`:92 — `$monthKey = rts_storage_deliveries_month_key($deliveryDate);`
- `includes/rts_storage.php`:86 — `$month = rts_storage_deliveries_month_key($deliveryDate);`

## rts_storage_dir_deliveries_month {#rts_storage_dir_deliveries_month}

**Callers (1):**

- `api/delivery_bulk.php`:93 — `$targetDir = rts_storage_dir_deliveries_month($deliveryDate);`

## rts_storage_normalize_stored_filename {#rts_storage_normalize_stored_filename}

**Callers (2):**

- `includes/rts_storage.php`:174 — `$safe = rts_storage_normalize_stored_filename($bucket, $filename);`
- `includes/rts_storage.php`:190 — `$safe = rts_storage_normalize_stored_filename($bucket, $filename);`

## rts_storage_web_base {#rts_storage_web_base}

**Callers (1):**

- `includes/rts_storage.php`:158 — `$web = rts_storage_web_base();`

## rts_storage_public_prefix {#rts_storage_public_prefix}

**Callers (3):**

- `includes/rts_storage.php`:180 — `$prefix = rts_storage_public_prefix($bucket, $relativeToApp);`
- `record.php`:756 — `const RTS_DOC_URL_BASE = <?= json_encode(rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''), JSON_UNESCAPED_SLASHES) ?>;`
- `records.php`:828 — `var RTS_DOC_URL_BASE = <?= json_encode(rts_storage_public_prefix(RTS_STORAGE_BUCKET_RTS, ''), JSON_UNESCAPED_SLASHES) ?>;`

## rts_storage_public_url {#rts_storage_public_url}

**Callers (4):**

- `api/delivery_match_tracking.php`:76 — `$fileUrl = rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn);`
- `api/upload_documentation.php`:126 — `'file_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_RTS, $name),`
- `deliveries/review.php`:441 — `$imgUrl = rts_storage_public_url(RTS_STORAGE_BUCKET_DELIVERIES, $fn, '../');`
- `includes/suppliers.php`:79 — `'valid_id_url' => rts_storage_public_url(RTS_STORAGE_BUCKET_SUPPLIER_IDS, (string) $row['valid_id_filename'], '/rts/'),`

## rts_storage_resolve_file {#rts_storage_resolve_file}

**Callers (1):**

- `api/storage_media.php`:21 — `$path = rts_storage_resolve_file($bucket, $filename);`

