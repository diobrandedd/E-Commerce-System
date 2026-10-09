---
title: dept_raw_freebies
source: includes/dept_raw_freebies.php
generated: 2026-10-09T05:57:41+02:00
type: module
function_count: 9
---


# dept_raw_freebies

> Source: `includes/dept_raw_freebies.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/dept_products.php`
- `includes/dept_inventory.php`

## dept_raw_freebie_setting_key {#dept_raw_freebie_setting_key}

**Callers (2):**

- `includes/dept_raw_freebies.php`:35 — `$raw = app_setting_get($pdo, dept_raw_freebie_setting_key($productKey), null);`
- `includes/dept_raw_freebies.php`:57 — `app_setting_set($pdo, dept_raw_freebie_setting_key($productKey), (string) $grams, $userId);`

## dept_raw_freebie_product_allowed {#dept_raw_freebie_product_allowed}

**Callers (12):**

- `api/v1/honey.php`:1104 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `api/v1/honey.php`:1130 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `api/v1/honey.php`:1154 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:32 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:49 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:64 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:74 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:105 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:176 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:234 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/inventory_check.php`:355 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/inventory_check_apply.php`:340 — `if (!dept_raw_freebie_product_allowed($productKey)) {`

## dept_raw_freebie_grams {#dept_raw_freebie_grams}

**Callers (3):**

- `includes/dept_raw_freebies.php`:59 — `return dept_raw_freebie_grams($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:112 — `$gramsEach = dept_raw_freebie_grams($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:282 — `'freebie_grams' => dept_raw_freebie_grams($pdo, $productKey),`

## dept_raw_freebie_grams_save {#dept_raw_freebie_grams_save}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_raw_freebie_ensure_balance_row {#dept_raw_freebie_ensure_balance_row}

**Tables:** `dept_raw_freebie_balances`

**Callers (4):**

- `includes/dept_raw_freebies.php`:77 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:126 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:187 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`
- `includes/inventory_check_apply.php`:344 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`

## dept_raw_freebie_on_hand {#dept_raw_freebie_on_hand}

**Tables:** `dept_raw_freebie_balances`

**Callers (8):**

- `includes/dept_production.php`:990 — `$snap['freebie_on_hand'] = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/dept_production.php`:1001 — `$snap['freebie_on_hand'] = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/dept_production.php`:1012 — `$snap['freebie_on_hand'] = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:154 — `'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),`
- `includes/dept_raw_freebies.php`:216 — `'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),`
- `includes/dept_raw_freebies.php`:281 — `'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),`
- `includes/inventory_check.php`:1124 — `$systemQty = (float) dept_raw_freebie_on_hand($pdo, (string) $productKey);`
- `includes/inventory_check_apply.php`:345 — `$old = dept_raw_freebie_on_hand($pdo, $productKey);`

## dept_raw_record_freebie_create {#dept_raw_record_freebie_create}

**Tables:** `dept_raw_freebie_log`, `dept_raw_freebie_balances`

**Callers (1):**

- `api/v1/honey.php`:1112 — `$result = dept_raw_record_freebie_create($pdo, (int) $user['id'], $productKey, $qty, $reason);`

## dept_raw_record_freebie_give_out {#dept_raw_record_freebie_give_out}

**Tables:** `dept_raw_freebie_balances`, `dept_raw_freebie_log`

**Callers (1):**

- `api/v1/honey.php`:1138 — `$result = dept_raw_record_freebie_give_out($pdo, (int) $user['id'], $productKey, $qty, $reason);`

## dept_raw_freebie_history {#dept_raw_freebie_history}

**Tables:** `dept_raw_freebie_log`, `users`

**Callers (1):**

- `api/v1/honey.php`:1163 — `$result = dept_raw_freebie_history(ims_pdo(), $productKey, $page, $perPage);`

