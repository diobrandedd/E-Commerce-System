---
title: ar_credits
source: includes/ar_credits.php
generated: 2026-09-18T05:30:16+02:00
type: module
function_count: 9
---


# ar_credits

> Source: `includes/ar_credits.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## ar_credits_staff_roles {#ar_credits_staff_roles}

**Callers (1):**

- `api/v1/ar.php`:76 — `if (!in_array($role, ar_credits_staff_roles(), true)) {`

## ar_staff_picker_list {#ar_staff_picker_list}

**Tables:** `users`, `roles`, `departments`

**Callers (2):**

- `api/v1/ar.php`:44 — `api_ok(['staff' => ar_staff_picker_list(ims_pdo())]);`
- `api/v1/pos.php`:294 — `api_ok(['staff' => ar_staff_picker_list(ims_pdo())]);`

## ar_credits_list_scope {#ar_credits_list_scope}

**Callers (1):**

- `includes/ar_credits.php`:58 — `$scope = ar_credits_list_scope($user);`

## ar_credits_list {#ar_credits_list}

**Tables:** `ar_credits`, `users`, `departments`, `products`, `pos_sales`

**Callers (1):**

- `api/v1/ar.php`:63 — `api_ok(['credits' => ar_credits_list(ims_pdo(), $user)]);`

## ar_credits_record_lines {#ar_credits_record_lines}

**Tables:** `ar_credits`

**Callers (4):**

- `includes/ar_credits.php`:203 — `$ids = ar_credits_record_lines($pdo, [`
- `includes/dept_pos.php`:439 — `$arCreditIds = ar_credits_record_lines($pdo, [`
- `includes/honey_pos.php`:707 — `$arCreditIds = ar_credits_record_lines($pdo, [`
- `includes/pos_unified.php`:678 — `$creditIds = ar_credits_record_lines($pdo, [`

## ar_credits_mark_paid {#ar_credits_mark_paid}

**Tables:** `ar_credits`

**Callers (1):**

- `api/v1/ar.php`:107 — `ar_credits_mark_paid(ims_pdo(), $id, (int) $user['id']);`

## ar_credits_mark_unpaid {#ar_credits_mark_unpaid}

**Tables:** `ar_credits`

**Callers (1):**

- `api/v1/ar.php`:127 — `ar_credits_mark_unpaid(ims_pdo(), $id);`

## ar_credits_take_for_user {#ar_credits_take_for_user}

**Tables:** `products`, `users`

**Callers (1):**

- `api/v1/ar.php`:87 — `$result = ar_credits_take_for_user(ims_pdo(), (int) $user['id'], $productId, $qty);`

## ar_credits_sellable_products {#ar_credits_sellable_products}

**Tables:** `products`, `departments`

**Callers (1):**

- `api/v1/ar.php`:33 — `api_ok(['products' => ar_credits_sellable_products(ims_pdo())]);`

