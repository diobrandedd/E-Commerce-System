---
title: permissions
source: includes/permissions.php
generated: 2026-11-17T06:08:20+01:00
type: module
function_count: 21
---


# permissions

> Source: `includes/permissions.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/auth_check.php`
- `includes/db.php`
- `includes/system_activity_log.php`
- `includes/system_activity_log.php`
- `includes/system_activity_log.php`

## ims_permission_catalog {#ims_permission_catalog}

**Callers (9):**

- `includes/permissions.php`:136 — `return array_keys(ims_permission_catalog());`
- `includes/permissions.php`:301 — `static fn(string $k): bool => isset(ims_permission_catalog()[$k])`
- `includes/permissions.php`:350 — `if (!isset(ims_permission_catalog()[$key])) {`
- `includes/permissions.php`:394 — `$catalog = ims_permission_catalog();`
- `includes/permissions.php`:406 — `foreach (ims_permission_catalog() as $key => $meta) {`
- `includes/permissions.php`:422 — `foreach (ims_permission_catalog() as $key => $meta) {`
- `includes/permissions.php`:451 — `$catalog = ims_permission_catalog();`
- `includes/permissions.php`:508 — `$catalog = ims_permission_catalog();`
- `includes/permissions.php`:546 — `$catalog = ims_permission_catalog();`

## ims_all_permission_keys {#ims_all_permission_keys}

**Callers (1):**

- `includes/permissions.php`:235 — `return ims_all_permission_keys();`

## ims_dept_line_map {#ims_dept_line_map}

**Callers (1):**

- `includes/permissions.php`:156 — `$line = ims_dept_line_map()[strtolower(trim($productKey))] ?? null;`

## ims_dept_permission_key {#ims_dept_permission_key}

**Callers (1):**

- `includes/permissions.php`:373 — `$key = ims_dept_permission_key($productKey, $action);`

## ims_dept_line_permission_keys {#ims_dept_line_permission_keys}

**Callers (3):**

- `includes/permissions.php`:249 — `], ims_dept_line_permission_keys(['paragis', 'brc', 'cacao', 'comb'], ['view', 'produce', 'barcodes']), [`
- `includes/permissions.php`:278 — `], ims_preset_honey_pipeline('tl', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'produce', 'barcodes']), $isCsr ? [`
- `includes/permissions.php`:291 — `], ims_preset_honey_pipeline('staff', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'barcodes']), $isCsr ? [`

## ims_preset_dept_lines {#ims_preset_dept_lines}

**Callers (1):**

- `includes/permissions.php`:232 — `$deptLines = ims_preset_dept_lines($deptKey);`

## ims_preset_honey_pipeline {#ims_preset_honey_pipeline}

**Callers (2):**

- `includes/permissions.php`:278 — `], ims_preset_honey_pipeline('tl', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'produce', 'barcodes']), $isCsr ? [`
- `includes/permissions.php`:291 — `], ims_preset_honey_pipeline('staff', $deptKey), ims_dept_line_permission_keys($deptLines, ['view', 'barcodes']), $isCsr ? [`

## ims_permission_preset_for_role {#ims_permission_preset_for_role}

**Callers (1):**

- `api/v1/staff.php`:302 — `'preset' => ims_permission_preset_for_role($role, $deptKey),`

## ims_user_permissions {#ims_user_permissions}

**Tables:** `user_permissions`

**Callers (6):**

- `api/v1/auth.php`:95 — `$payload['permissions'] = ims_user_permissions((int) $user['id']);`
- `api/v1/auth.php`:133 — `$serialized['permissions'] = ims_user_permissions((int) $user['id']);`
- `api/v1/auth.php`:243 — `$serialized['permissions'] = ims_user_permissions((int) $user['id']);`
- `includes/permissions.php`:353 — `$granted = ims_user_permissions((int) $user['id']);`
- `includes/permissions.php`:381 — `$keys = ims_user_permissions($userId);`
- `includes/permissions.php`:499 — `return count(ims_user_permissions($userId));`

## ims_user_permissions_clear_cache {#ims_user_permissions_clear_cache}

**Callers (3):**

- `includes/permissions.php`:480 — `ims_user_permissions_clear_cache($userId);`
- `includes/permissions.php`:523 — `ims_user_permissions_clear_cache($userId);`
- `includes/permissions.php`:559 — `ims_user_permissions_clear_cache($userId);`

## ims_user_can {#ims_user_can}

**Callers (124):**

- `api/v1/_bootstrap.php`:168 — `if (ims_user_can($u, $key)) {`
- `api/v1/_costing_helpers.php`:73 — `return ims_user_can($user, 'costing.manage');`
- `api/v1/_costing_helpers.php`:80 — `return ims_user_can($user, 'costing.manage');`
- `api/v1/_costing_helpers.php`:87 — `return ims_user_can($user, 'costing.manage');`
- `api/v1/_costing_helpers.php`:94 — `return ims_user_can($user, 'honey.pricing.view_cost');`
- `api/v1/dashboard.php`:24 — `if (ims_user_can($user, 'csr.parcels.view') || ims_user_can($user, 'csr.online_orders')) {`
- `api/v1/dashboard.php`:27 — `if ((string) $user['role'] === 'staff' && !ims_user_can($user, 'inventory.entries.view')) {`
- `api/v1/dashboard.php`:112 — `$canApproveEditRequests = ims_user_can($user, 'inventory.edit_requests.approve');`
- `api/v1/dashboard.php`:113 — `$isCrossDept = ims_user_can($user, 'inventory.entries.view');`
- `api/v1/dashboard.php`:118 — `'can_view_financials' => ims_user_can($user, 'reports.view'),`
- `api/v1/dashboard.php`:180 — `if (ims_user_can($user, 'reports.view')) {`
- `api/v1/dashboard.php`:376 — `if (ims_user_can($user, $perm)) {`
- `api/v1/edit_requests.php`:35 — `if (ims_user_can($user, 'inventory.edit_requests.approve')) {`
- `api/v1/edit_requests.php`:50 — `'is_reviewer' => ims_user_can($user, 'inventory.edit_requests.approve'),`
- `api/v1/finished_leak_damage.php`:11 — `if (!ims_user_can($user, 'inventory.leak_damage.record') && !ims_user_can($user, 'inventory.leak_damage.view')) {`
- `api/v1/finished_leak_damage.php`:28 — `if (!ims_user_can($user, 'inventory.leak_damage.record')) {`
- `api/v1/honey.php`:161 — `'honey' => ims_user_can($user, 'honey.view')`
- `api/v1/honey.php`:162 — `|| ims_user_can($user, 'honey.ops')`
- `api/v1/honey.php`:163 — `|| ims_user_can($user, 'honey.mutate')`
- `api/v1/honey.php`:168 — `'csr' => ims_user_can($user, 'honey.view') || ims_user_can($user, 'honey.bottle.generate'),`
- `api/v1/honey.php`:182 — `!ims_user_can($user, 'honey.view')`
- `api/v1/honey.php`:183 — `&& !ims_user_can($user, 'honey.mutate')`
- `api/v1/honey.php`:184 — `&& !ims_user_can($user, 'honey.ops')`
- `api/v1/honey.php`:185 — `&& !ims_user_can($user, 'honey.bottle.request')`
- `api/v1/honey.php`:186 — `&& !ims_user_can($user, 'honey.bottle.scan')`
- _… and 99 more (run impact.php for full list)_

## ims_user_can_dept_line {#ims_user_can_dept_line}

**Callers (10):**

- `api/v1/honey.php`:164 — `|| ims_user_can_dept_line($user, 'honey_comb', 'view'),`
- `api/v1/honey.php`:165 — `'paragis' => ims_user_can_dept_line($user, 'pt', 'view'),`
- `api/v1/honey.php`:166 — `'cacao_brc' => ims_user_can_dept_line($user, 'brc', 'view')`
- `api/v1/honey.php`:167 — `|| ims_user_can_dept_line($user, 'jar_cacao', 'view'),`
- `api/v1/honey.php`:909 — `if ($productKey !== '' && !ims_user_can_dept_line($user, $productKey, 'view')) {`
- `api/v1/honey.php`:920 — `static fn(array $item): bool => ims_user_can_dept_line($user, (string) $item['key'], 'view')`
- `api/v1/honey.php`:1003 — `if (!ims_user_can_dept_line($user, $productKey, 'view')) {`
- `api/v1/honey.php`:1143 — `if (!ims_user_can_dept_line($user, $productKey, 'view')) {`
- `includes/dept_pouch_barcodes.php`:21 — `return ims_user_can_dept_line($user, $productKey, 'barcodes');`
- `includes/honey_inventory.php`:183 — `return ims_user_can_dept_line($user, $productKey, 'produce');`

## ims_user_permissions_expanded {#ims_user_permissions_expanded}

**Callers (1):**

- `api/v1/staff.php`:301 — `'permissions' => ims_user_permissions_expanded($userId),`

## ims_permission_catalog_list {#ims_permission_catalog_list}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## ims_permission_catalog_grouped {#ims_permission_catalog_grouped}

**Callers (1):**

- `api/v1/staff.php`:265 — `api_ok(['groups' => ims_permission_catalog_grouped(), 'presets' => array_keys(array_filter([`

## ims_user_permissions_set {#ims_user_permissions_set}

**Tables:** `user_permissions`

**Callers (1):**

- `api/v1/staff.php`:331 — `ims_user_permissions_set($userId, array_map('strval', $keys), (int) $actor['id']);`

## ims_user_permission_count {#ims_user_permission_count}

**Callers (2):**

- `api/v1/staff.php`:60 — `'permission_count' => ims_user_permission_count($id),`
- `api/v1/staff.php`:332 — `api_ok(['updated' => true, 'permission_count' => ims_user_permission_count($userId)]);`

## ims_user_permission_grant {#ims_user_permission_grant}

**Tables:** `user_permissions`

**Callers (1):**

- `includes/material_inventory.php`:2763 — `ims_user_permission_grant($targetUserId, 'packaging.deduct', $actorUserId);`

## ims_user_permission_revoke {#ims_user_permission_revoke}

**Tables:** `user_permissions`

**Callers (1):**

- `includes/material_inventory.php`:2782 — `ims_user_permission_revoke($targetUserId, 'packaging.deduct', $actorUserId);`

## ims_user_dept_key {#ims_user_dept_key}

**Tables:** `departments`

**Callers (1):**

- `includes/permissions.php`:596 — `return ims_user_dept_key($user) === 'csr';`

## ims_user_is_csr_dept {#ims_user_is_csr_dept}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

