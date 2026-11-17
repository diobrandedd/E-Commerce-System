---
title: pos_unified
source: includes/pos_unified.php
generated: 2026-11-17T06:08:23+01:00
type: module
function_count: 13
---


# pos_unified

> Source: `includes/pos_unified.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/honey_pos.php`
- `includes/dept_pos.php`
- `includes/pos_sales_helpers.php`
- `includes/dept_raw_freebies.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/system_activity_log.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`

## pos_unified_reporter_user_id {#pos_unified_reporter_user_id}

**Tables:** `users`

**Callers (2):**

- `includes/pos_unified.php`:681 — `pos_unified_reporter_user_id($pdo, $actor),`
- `includes/pos_unified.php`:883 — `pos_unified_reporter_user_id($pdo, $actor),`

## pos_session_actor {#pos_session_actor}

**Callers (2):**

- `api/v1/pos.php`:465 — `$result = pos_unified_checkout(ims_pdo(), pos_session_actor(), $items, $identity['buyer_name'], $identity['buyer_credentials'], [`
- `api/v1/pos.php`:498 — `$result = pos_unified_ar_checkout(ims_pdo(), pos_session_actor(), $items, [`

## pos_unified_resolve_honey_product {#pos_unified_resolve_honey_product}

**Tables:** `products`

**Callers (4):**

- `includes/pos_unified.php`:125 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`
- `includes/pos_unified.php`:204 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`
- `includes/pos_unified.php`:416 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`
- `includes/pos_unified.php`:442 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`

## pos_unified_resolve_dept_product {#pos_unified_resolve_dept_product}

**Tables:** `products`

**Callers (4):**

- `includes/pos_unified.php`:150 — `$prod = pos_unified_resolve_dept_product($pdo, $productKey);`
- `includes/pos_unified.php`:235 — `$prod = pos_unified_resolve_dept_product($pdo, (string) $dept['product_key']);`
- `includes/pos_unified.php`:488 — `$prod = pos_unified_resolve_dept_product($pdo, $productKey);`
- `includes/pos_unified.php`:512 — `$prod = pos_unified_resolve_dept_product($pdo, $productKey);`

## pos_unified_lookup {#pos_unified_lookup}

**Callers (1):**

- `api/v1/pos.php`:430 — `api_ok(['barcode' => pos_unified_lookup(ims_pdo(), $code)]);`

## pos_unified_freebie_options {#pos_unified_freebie_options}

**Callers (1):**

- `api/v1/pos.php`:441 — `api_ok(['options' => pos_unified_freebie_options(ims_pdo())]);`

## pos_unified_split_freebie_items {#pos_unified_split_freebie_items}

**Callers (3):**

- `includes/pos_unified.php`:572 — `$split = pos_unified_split_freebie_items($items);`
- `includes/pos_unified.php`:775 — `$split = pos_unified_split_freebie_items($items);`
- `includes/pos_unified.php`:928 — `$split = pos_unified_split_freebie_items($items);`

## pos_unified_record_freebies {#pos_unified_record_freebies}

**Tables:** `pos_receipt_freebies`

**Callers (2):**

- `includes/pos_unified.php`:679 — `$recordedFreebies = pos_unified_record_freebies(`
- `includes/pos_unified.php`:881 — `$recordedFreebies = pos_unified_record_freebies(`

## pos_unified_build_cart_lines {#pos_unified_build_cart_lines}

**Tables:** `honey_bottle_units`

**Callers (3):**

- `includes/pos_unified.php`:576 — `$cleanLines = pos_unified_build_cart_lines($pdo, $split['paid'], $discountFactor);`
- `includes/pos_unified.php`:779 — `$cleanLines = pos_unified_build_cart_lines($pdo, $split['paid'], 1.0);`
- `includes/pos_unified.php`:933 — `$clean = pos_unified_build_cart_lines($pdo, $split['paid'], $discountFactor);`

## pos_unified_checkout {#pos_unified_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`, `pos_discount_requests`

**Callers (2):**

- `api/v1/pos.php`:465 — `$result = pos_unified_checkout(ims_pdo(), pos_session_actor(), $items, $identity['buyer_name'], $identity['buyer_credentials'], [`
- `includes/pos_discount_requests.php`:240 — `$result = pos_unified_checkout(`

## pos_unified_ar_checkout {#pos_unified_ar_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`

**Callers (1):**

- `api/v1/pos.php`:498 — `$result = pos_unified_ar_checkout(ims_pdo(), pos_session_actor(), $items, [`

## pos_unified_discount_quote {#pos_unified_discount_quote}

**Callers (1):**

- `includes/pos_discount_requests.php`:139 — `return pos_unified_discount_quote($pdo, $items, $discountPercent);`

## pos_discount_cart_is_unified {#pos_discount_cart_is_unified}

**Callers (3):**

- `includes/pos_discount_requests.php`:138 — `if (pos_discount_cart_is_unified($items)) {`
- `includes/pos_discount_requests.php`:239 — `if (pos_discount_cart_is_unified($items)) {`
- `includes/pos_discount_requests.php`:277 — `!pos_discount_cart_is_unified($items)`

