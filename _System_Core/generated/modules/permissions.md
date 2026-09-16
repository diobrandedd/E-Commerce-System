---
title: permissions
source: includes/permissions.php
generated: 2026-09-16T11:48:40+02:00
type: module
function_count: 19
---


# permissions

> Source: `includes/permissions.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/auth_check.php`
- `includes/db.php`

## ims_permission_catalog {#ims_permission_catalog}

**Callers (7):**

- `includes/permissions.php`:139 — `return array_keys(ims_permission_catalog());`
- `includes/permissions.php`:304 — `static fn(string $k): bool => isset(ims_permission_catalog()[$k])`
- `includes/permissions.php`:353 — `if (!isset(ims_permission_catalog()[$key])) {`
- `includes/permissions.php`:397 — `$catalog = ims_permission_catalog();`
- `includes/permissions.php`:409 — `foreach (ims_permission_catalog() as $key => $meta) {`
- `includes/permissions.php`:425 — `foreach (ims_permission_catalog() as $key => $meta) {`
- `includes/permissions.php`:454 — `$catalog = ims_permission_catalog();`

## ims_all_permission_keys {#ims_all_permission_keys}

**Callers (1):**

- `includes/permissions.php`:238 — `return ims_all_permission_keys();`

## ims_dept_line_map {#ims_dept_line_map}

**Callers (1):**

- `includes/permissions.php`:159 — `$line = ims_dept_line_map()[strtolower(trim($productKey))] ?? null;`

## ims_dept_permission_key {#ims_dept_permission_key}

**Callers (1):**

- `includes/permissions.php`:376 — `$key = ims_dept_permission_key($productKey, $action);`

## ims_dept_line_permission_keys {#ims_dept_line_permission_keys}

**Callers (3):**

- `includes/permissions.php`:252 — `], ims_dept_line_permission_keys(['paragis', 'brc', 'cacao', 'comb'], ['view', 'produce', 'barcodes']), [`
- `includes/permissions.php`:281 — `], ims_preset_honey_pipeline('tl', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'produce', 'barcodes']), $isCsr ? [`
- `includes/permissions.php`:294 — `], ims_preset_honey_pipeline('staff', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'barcodes']), $isCsr ? [`

## ims_preset_dept_lines {#ims_preset_dept_lines}

**Callers (1):**

- `includes/permissions.php`:235 — `$deptLines = ims_preset_dept_lines($deptKey);`

## ims_preset_honey_pipeline {#ims_preset_honey_pipeline}

**Callers (2):**

- `includes/permissions.php`:281 — `], ims_preset_honey_pipeline('tl', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'produce', 'barcodes']), $isCsr ? [`
- `includes/permissions.php`:294 — `], ims_preset_honey_pipeline('staff', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'barcodes']), $isCsr ? [`

## ims_permission_preset_for_role {#ims_permission_preset_for_role}

**Callers (1):**

- `api/v1/staff.php`:287 — `'preset' => ims_permission_preset_for_role($role, $deptKey),`

## ims_user_permissions {#ims_user_permissions}

**Tables:** `user_permissions`

**Callers (6):**

- `api/v1/auth.php`:92 — `$payload['permissions'] = ims_user_permissions((int) $user['id']);`
- `api/v1/auth.php`:130 — `$serialized['permissions'] = ims_user_permissions((int) $user['id']);`
- `api/v1/auth.php`:240 — `$serialized['permissions'] = ims_user_permissions((int) $user['id']);`
- `includes/permissions.php`:356 — `$granted = ims_user_permissions((int) $user['id']);`
- `includes/permissions.php`:384 — `$keys = ims_user_permissions($userId);`
- `includes/permissions.php`:488 — `return count(ims_user_permissions($userId));`

## ims_user_permissions_clear_cache {#ims_user_permissions_clear_cache}

**Callers (1):**

- `includes/permissions.php`:483 — `ims_user_permissions_clear_cache($userId);`

## ims_user_can {#ims_user_can}

**Callers (117):**

- `api/v1/_bootstrap.php`:168 — `if (ims_user_can($u, $key)) {`
- `api/v1/_costing_helpers.php`:73 — `return ims_user_can($user, 'costing.manage');`
- `api/v1/_costing_helpers.php`:80 — `return ims_user_can($user, 'costing.manage');`
- `api/v1/_costing_helpers.php`:87 — `return ims_user_can($user, 'costing.manage');`
- `api/v1/_costing_helpers.php`:94 — `return ims_user_can($user, 'honey.pricing.view_cost');`
- `api/v1/dashboard.php`:22 — `if (ims_user_can($user, 'csr.parcels.view') || ims_user_can($user, 'csr.online_orders')) {`
- `api/v1/dashboard.php`:25 — `if ((string) $user['role'] === 'staff' && !ims_user_can($user, 'inventory.entries.view')) {`
- `api/v1/dashboard.php`:110 — `$canApproveEditRequests = ims_user_can($user, 'inventory.edit_requests.approve');`
- `api/v1/dashboard.php`:111 — `$isCrossDept = ims_user_can($user, 'inventory.entries.view');`
- `api/v1/dashboard.php`:116 — `'can_view_financials' => ims_user_can($user, 'reports.view'),`
- `api/v1/dashboard.php`:178 — `if (ims_user_can($user, 'reports.view')) {`
- `api/v1/edit_requests.php`:35 — `if (ims_user_can($user, 'inventory.edit_requests.approve')) {`
- `api/v1/edit_requests.php`:50 — `'is_reviewer' => ims_user_can($user, 'inventory.edit_requests.approve'),`
- `api/v1/finished_leak_damage.php`:11 — `if (!ims_user_can($user, 'inventory.leak_damage.record') && !ims_user_can($user, 'inventory.leak_damage.view')) {`
- `api/v1/finished_leak_damage.php`:28 — `if (!ims_user_can($user, 'inventory.leak_damage.record')) {`
- `api/v1/honey.php`:155 — `'honey' => ims_user_can($user, 'honey.view')`
- `api/v1/honey.php`:156 — `|| ims_user_can($user, 'honey.ops')`
- `api/v1/honey.php`:157 — `|| ims_user_can($user, 'honey.mutate')`
- `api/v1/honey.php`:162 — `'csr' => ims_user_can($user, 'honey.view') || ims_user_can($user, 'honey.bottle.generate'),`
- `api/v1/honey.php`:176 — `!ims_user_can($user, 'honey.view')`
- `api/v1/honey.php`:177 — `&& !ims_user_can($user, 'honey.mutate')`
- `api/v1/honey.php`:178 — `&& !ims_user_can($user, 'honey.ops')`
- `api/v1/honey.php`:179 — `&& !ims_user_can($user, 'honey.bottle.request')`
- `api/v1/honey.php`:180 — `&& !ims_user_can($user, 'honey.bottle.scan')`
- `api/v1/honey.php`:181 — `&& !ims_user_can($user, 'honey.bottle.generate')`
- _… and 92 more (run impact.php for full list)_

## ims_user_can_dept_line {#ims_user_can_dept_line}

**Callers (9):**

- `api/v1/honey.php`:158 — `|| ims_user_can_dept_line($user, 'honey_comb', 'view'),`
- `api/v1/honey.php`:159 — `'paragis' => ims_user_can_dept_line($user, 'pt', 'view'),`
- `api/v1/honey.php`:160 — `'cacao_brc' => ims_user_can_dept_line($user, 'brc', 'view')`
- `api/v1/honey.php`:161 — `|| ims_user_can_dept_line($user, 'jar_cacao', 'view'),`
- `api/v1/honey.php`:852 — `if ($productKey !== '' && !ims_user_can_dept_line($user, $productKey, 'view')) {`
- `api/v1/honey.php`:863 — `static fn(array $item): bool => ims_user_can_dept_line($user, (string) $item['key'], 'view')`
- `api/v1/honey.php`:946 — `if (!ims_user_can_dept_line($user, $productKey, 'view')) {`
- `includes/dept_pouch_barcodes.php`:21 — `return ims_user_can_dept_line($user, $productKey, 'barcodes');`
- `includes/honey_inventory.php`:44 — `return ims_user_can_dept_line($user, $productKey, 'produce');`

## ims_user_permissions_expanded {#ims_user_permissions_expanded}

**Callers (1):**

- `api/v1/staff.php`:286 — `'permissions' => ims_user_permissions_expanded($userId),`

## ims_permission_catalog_list {#ims_permission_catalog_list}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## ims_permission_catalog_grouped {#ims_permission_catalog_grouped}

**Callers (1):**

- `api/v1/staff.php`:250 — `api_ok(['groups' => ims_permission_catalog_grouped(), 'presets' => array_keys(array_filter([`

## ims_user_permissions_set {#ims_user_permissions_set}

**Tables:** `user_permissions`

**Callers (1):**

- `api/v1/staff.php`:316 — `ims_user_permissions_set($userId, array_map('strval', $keys), (int) $actor['id']);`

## ims_user_permission_count {#ims_user_permission_count}

**Callers (2):**

- `api/v1/staff.php`:60 — `'permission_count' => ims_user_permission_count($id),`
- `api/v1/staff.php`:317 — `api_ok(['updated' => true, 'permission_count' => ims_user_permission_count($userId)]);`

## ims_user_dept_key {#ims_user_dept_key}

**Tables:** `departments`

**Callers (1):**

- `includes/permissions.php`:511 — `return ims_user_dept_key($user) === 'csr';`

## ims_user_is_csr_dept {#ims_user_is_csr_dept}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

