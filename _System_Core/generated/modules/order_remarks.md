---
title: order_remarks
source: includes/order_remarks.php
generated: 2026-06-11T05:22:15+02:00
type: module
function_count: 17
---


# order_remarks

> Source: `includes/order_remarks.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/waybill_stamper.php`

## order_remarks_storage_root {#order_remarks_storage_root}

**Callers (2):**

- `includes/order_remarks.php`:302 — `$stashDir = order_remarks_storage_root() . '/stash';`
- `includes/order_remarks.php`:409 — `$outDir = order_remarks_storage_root() . '/outputs';`

## order_remarks_workbook_script {#order_remarks_workbook_script}

**Callers (1):**

- `includes/order_remarks.php`:165 — `$script = order_remarks_workbook_script();`

## order_remarks_require_access {#order_remarks_require_access}

**Callers (1):**

- `includes/order_remarks.php`:460 — `order_remarks_require_access();`

## order_remarks_user_can_access {#order_remarks_user_can_access}

**Callers (2):**

- `api/v1/order_remarks.php`:34 — `if (!order_remarks_user_can_access($user)) {`
- `orders-editor/auth_gate.php`:11 — `if ($user === null || !order_remarks_user_can_access($user)) {`

## order_remarks_profiles {#order_remarks_profiles}

**Callers (2):**

- `includes/order_remarks.php`:144 — `foreach (order_remarks_profiles() as $p) {`
- `includes/order_remarks.php`:152 — `$json = json_encode(order_remarks_profiles(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);`

## order_remarks_profile_labels {#order_remarks_profile_labels}

**Callers (2):**

- `api/v1/order_remarks.php`:127 — `api_ok(['profiles' => order_remarks_profile_labels()]);`
- `includes/order_remarks.php`:388 — `'profiles' => order_remarks_profile_labels(),`

## order_remarks_profiles_json {#order_remarks_profiles_json}

**Callers (1):**

- `includes/order_remarks.php`:205 — `$profilesPath = order_remarks_write_temp_json(order_remarks_profiles_json());`

## order_remarks_python_command {#order_remarks_python_command}

**Callers (1):**

- `includes/order_remarks.php`:232 — `$built = order_remarks_python_command(...$args);`

## order_remarks_write_temp_json {#order_remarks_write_temp_json}

**Callers (3):**

- `includes/order_remarks.php`:205 — `$profilesPath = order_remarks_write_temp_json(order_remarks_profiles_json());`
- `includes/order_remarks.php`:221 — `$layoutPath = order_remarks_write_temp_json($layoutJson);`
- `includes/order_remarks.php`:222 — `$remarksPath = order_remarks_write_temp_json($remarksJson);`

## order_remarks_run_python {#order_remarks_run_python}

**Callers (2):**

- `includes/order_remarks.php`:359 — `$data = order_remarks_run_python('detect', $path, $override);`
- `includes/order_remarks.php`:413 — `order_remarks_run_python('write', $path, null, $outPath, $layout, $remarks);`

## order_remarks_stash_prune {#order_remarks_stash_prune}

**Callers (2):**

- `includes/order_remarks.php`:294 — `order_remarks_stash_prune();`
- `includes/order_remarks.php`:330 — `order_remarks_stash_prune();`

## order_remarks_stash_upload {#order_remarks_stash_upload}

**Callers (1):**

- `api/v1/order_remarks.php`:63 — `$token = order_remarks_stash_upload();`

## order_remarks_resolve_stash_path {#order_remarks_resolve_stash_path}

**Callers (2):**

- `includes/order_remarks.php`:357 — `$path = order_remarks_resolve_stash_path($token);`
- `includes/order_remarks.php`:398 — `$path = order_remarks_resolve_stash_path($token);`

## order_remarks_parse_workbook {#order_remarks_parse_workbook}

**Callers (1):**

- `api/v1/order_remarks.php`:84 — `$result = order_remarks_parse_workbook($token, $override);`

## order_remarks_export_workbook {#order_remarks_export_workbook}

**Callers (1):**

- `api/v1/order_remarks.php`:116 — `$result = order_remarks_export_workbook($token, $cleanRemarks, $layoutArr);`

## order_remarks_register_download {#order_remarks_register_download}

**Callers (1):**

- `includes/order_remarks.php`:423 — `$downloadToken = order_remarks_register_download($outPath, $downloadName);`

## order_remarks_serve_download {#order_remarks_serve_download}

**Callers (1):**

- `api/v1/order_remarks.php`:12 — `order_remarks_serve_download(trim((string) ($_GET['token'] ?? '')));`

