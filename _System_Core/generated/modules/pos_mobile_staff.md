---
title: pos_mobile_staff
source: includes/pos_mobile_staff.php
generated: 2026-10-09T05:12:40+02:00
type: module
function_count: 9
---


# pos_mobile_staff

> Source: `includes/pos_mobile_staff.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/auth_check.php`
- `includes/auth_check.php`

## pos_mobile_staff_ensure_schema {#pos_mobile_staff_ensure_schema}

**Callers (5):**

- `includes/pos_mobile_staff.php`:37 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:145 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:174 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:202 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:219 — `pos_mobile_staff_ensure_schema($pdo);`

## pos_mobile_user_is_assigned {#pos_mobile_user_is_assigned}

**Tables:** `pos_mobile_staff`

**Callers (1):**

- `includes/pos_mobile_staff.php`:59 — `return pos_mobile_user_is_assigned($userId);`

## pos_mobile_user_can_login {#pos_mobile_user_can_login}

**Callers (3):**

- `api/v1/auth.php`:220 — `&& !pos_mobile_user_can_login((int) $probe['id'], (string) $probe['role_name'])) {`
- `api/v1/pos.php`:59 — `|| pos_mobile_user_can_login((int) $user['id'], $role)) {`
- `includes/pos_mobile_staff.php`:88 — `if (!pos_mobile_user_can_login((int) $row['id'], $role)) {`

## pos_mobile_authenticate {#pos_mobile_authenticate}

**Tables:** `users`, `roles`

**Callers (1):**

- `api/v1/auth.php`:206 — `$user = pos_mobile_authenticate($pin);`

## pos_mobile_verify_executive {#pos_mobile_verify_executive}

**Tables:** `users`, `roles`

**Callers (1):**

- `api/v1/auth.php`:286 — `$exec = pos_mobile_verify_executive($pin);`

## pos_mobile_staff_candidates {#pos_mobile_staff_candidates}

**Tables:** `pos_mobile_staff`, `users`, `roles`, `departments`

**Callers (1):**

- `api/v1/settings.php`:103 — `api_ok(['users' => pos_mobile_staff_candidates(ims_pdo())]);`

## pos_mobile_staff_list {#pos_mobile_staff_list}

**Tables:** `pos_mobile_staff`, `users`, `roles`, `departments`

**Callers (3):**

- `api/v1/settings.php`:96 — `api_ok(['staff' => pos_mobile_staff_list(ims_pdo())]);`
- `api/v1/settings.php`:118 — `api_ok(['assigned' => true, 'staff' => pos_mobile_staff_list(ims_pdo())]);`
- `api/v1/settings.php`:133 — `api_ok(['assigned' => false, 'staff' => pos_mobile_staff_list(ims_pdo())]);`

## pos_mobile_staff_assign {#pos_mobile_staff_assign}

**Tables:** `users`, `pos_mobile_staff`

**Callers (1):**

- `api/v1/settings.php`:114 — `pos_mobile_staff_assign(ims_pdo(), $userId, (int) ($exec['id'] ?? 0));`

## pos_mobile_staff_unassign {#pos_mobile_staff_unassign}

**Tables:** `pos_mobile_staff`

**Callers (1):**

- `api/v1/settings.php`:129 — `pos_mobile_staff_unassign(ims_pdo(), $userId);`

