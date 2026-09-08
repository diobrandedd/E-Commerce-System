---
title: order_remarks
source: includes/order_remarks.php
generated: 2026-09-08T09:44:07+02:00
type: module
function_count: 19
---


# order_remarks

> Source: `includes/order_remarks.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/waybill_stamper.php`

## order_remarks_storage_root {#order_remarks_storage_root}

**Callers (2):**

- `includes/order_remarks.php`:372 — `$stashDir = order_remarks_storage_root() . '/stash';`
- `includes/order_remarks.php`:479 — `$outDir = order_remarks_storage_root() . '/outputs';`

## order_remarks_workbook_script {#order_remarks_workbook_script}

**Callers (1):**

- `includes/order_remarks.php`:225 — `$script = order_remarks_workbook_script();`

## order_remarks_require_access {#order_remarks_require_access}

**Callers (1):**

- `includes/order_remarks.php`:563 — `order_remarks_require_access();`

## order_remarks_user_can_access {#order_remarks_user_can_access}

**Callers (2):**

- `api/v1/order_remarks.php`:34 — `if (!order_remarks_user_can_access($user)) {`
- `orders-editor/auth_gate.php`:11 — `if ($user === null || !order_remarks_user_can_access($user)) {`

## order_remarks_profiles {#order_remarks_profiles}

**Callers (2):**

- `includes/order_remarks.php`:204 — `foreach (order_remarks_profiles() as $p) {`
- `includes/order_remarks.php`:212 — `$json = json_encode(order_remarks_profiles(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);`

## order_remarks_profile_labels {#order_remarks_profile_labels}

**Callers (2):**

- `api/v1/order_remarks.php`:127 — `api_ok(['profiles' => order_remarks_profile_labels()]);`
- `includes/order_remarks.php`:458 — `'profiles' => order_remarks_profile_labels(),`

## order_remarks_profiles_json {#order_remarks_profiles_json}

**Callers (1):**

- `includes/order_remarks.php`:265 — `$profilesPath = order_remarks_write_temp_json(order_remarks_profiles_json());`

## order_remarks_python_command {#order_remarks_python_command}

**Callers (1):**

- `includes/order_remarks.php`:294 — `$built = order_remarks_python_command(...$args);`

## order_remarks_write_temp_json {#order_remarks_write_temp_json}

**Callers (3):**

- `includes/order_remarks.php`:265 — `$profilesPath = order_remarks_write_temp_json(order_remarks_profiles_json());`
- `includes/order_remarks.php`:281 — `$layoutPath = order_remarks_write_temp_json($layoutJson);`
- `includes/order_remarks.php`:282 — `$remarksPath = order_remarks_write_temp_json($remarksJson);`

## order_remarks_run_python {#order_remarks_run_python}

**Callers (3):**

- `includes/order_remarks.php`:429 — `$data = order_remarks_run_python('detect', $path, $override);`
- `includes/order_remarks.php`:483 — `order_remarks_run_python('write', $path, null, $outPath, $layout, $remarks);`
- `includes/order_remarks.php`:547 — `$data = order_remarks_run_python('tracking_map', $workbookPath, $override);`

## order_remarks_stash_prune {#order_remarks_stash_prune}

**Callers (2):**

- `includes/order_remarks.php`:364 — `order_remarks_stash_prune();`
- `includes/order_remarks.php`:400 — `order_remarks_stash_prune();`

## order_remarks_stash_upload {#order_remarks_stash_upload}

**Callers (1):**

- `api/v1/order_remarks.php`:63 — `$token = order_remarks_stash_upload();`

## order_remarks_resolve_stash_path {#order_remarks_resolve_stash_path}

**Callers (3):**

- `includes/online_orders_generator.php`:869 — `$trackingPath = order_remarks_resolve_stash_path($trackingExcelToken);`
- `includes/order_remarks.php`:427 — `$path = order_remarks_resolve_stash_path($token);`
- `includes/order_remarks.php`:468 — `$path = order_remarks_resolve_stash_path($token);`

## order_remarks_parse_workbook {#order_remarks_parse_workbook}

**Callers (1):**

- `api/v1/order_remarks.php`:84 — `$result = order_remarks_parse_workbook($token, $override);`

## order_remarks_export_workbook {#order_remarks_export_workbook}

**Callers (1):**

- `api/v1/order_remarks.php`:116 — `$result = order_remarks_export_workbook($token, $cleanRemarks, $layoutArr);`

## order_remarks_register_download {#order_remarks_register_download}

**Callers (1):**

- `includes/order_remarks.php`:493 — `$downloadToken = order_remarks_register_download($outPath, $downloadName);`

## vip_normalize_parcel_ref {#vip_normalize_parcel_ref}

**Callers (2):**

- `includes/online_orders_generator.php`:881 — `$ref = vip_normalize_parcel_ref((string) ($refRow['ref'] ?? ''));`
- `includes/online_orders_generator.php`:901 — `$parcelRef = vip_normalize_parcel_ref((string) ($row['parcel_ref'] ?? ''));`

## order_remarks_parse_tracking_map {#order_remarks_parse_tracking_map}

**Callers (1):**

- `includes/online_orders_generator.php`:874 — `$tracking = order_remarks_parse_tracking_map($trackingPath, $override);`

## order_remarks_serve_download {#order_remarks_serve_download}

**Callers (1):**

- `api/v1/order_remarks.php`:12 — `order_remarks_serve_download(trim((string) ($_GET['token'] ?? '')));`

