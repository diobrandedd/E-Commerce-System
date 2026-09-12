---
title: pos_mobile_staff
source: includes/pos_mobile_staff.php
generated: 2026-09-12T11:56:37+02:00
type: module
function_count: 6
---


# pos_mobile_staff

> Source: `includes/pos_mobile_staff.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`

## pos_mobile_staff_ensure_schema {#pos_mobile_staff_ensure_schema}

**Callers (5):**

- `includes/pos_mobile_staff.php`:35 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:47 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:76 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:104 — `pos_mobile_staff_ensure_schema($pdo);`
- `includes/pos_mobile_staff.php`:121 — `pos_mobile_staff_ensure_schema($pdo);`

## pos_mobile_user_is_assigned {#pos_mobile_user_is_assigned}

**Tables:** `pos_mobile_staff`

**Callers (2):**

- `api/v1/auth.php`:208 — `if (!pos_mobile_user_is_assigned((int) $user['id'])) {`
- `api/v1/pos.php`:57 — `if (ims_user_can($user, 'pos.sell') || pos_mobile_user_is_assigned((int) $user['id'])) {`

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

