---
title: pos_unified
source: includes/pos_unified.php
generated: 2026-09-08T09:44:15+02:00
type: module
function_count: 9
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
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`

## pos_session_actor {#pos_session_actor}

**Callers (2):**

- `api/v1/pos.php`:438 — `$result = pos_unified_checkout(ims_pdo(), pos_session_actor(), $items, $identity['buyer_name'], $identity['buyer_credentials'], [`
- `api/v1/pos.php`:471 — `$result = pos_unified_ar_checkout(ims_pdo(), pos_session_actor(), $items, [`

## pos_unified_resolve_honey_product {#pos_unified_resolve_honey_product}

**Tables:** `products`

**Callers (4):**

- `includes/pos_unified.php`:108 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`
- `includes/pos_unified.php`:162 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`
- `includes/pos_unified.php`:255 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`
- `includes/pos_unified.php`:281 — `$prod = pos_unified_resolve_honey_product($pdo, $variant, $size);`

## pos_unified_resolve_dept_product {#pos_unified_resolve_dept_product}

**Tables:** `products`

**Callers (4):**

- `includes/pos_unified.php`:133 — `$prod = pos_unified_resolve_dept_product($pdo, $productKey);`
- `includes/pos_unified.php`:193 — `$prod = pos_unified_resolve_dept_product($pdo, (string) $dept['product_key']);`
- `includes/pos_unified.php`:327 — `$prod = pos_unified_resolve_dept_product($pdo, $productKey);`
- `includes/pos_unified.php`:351 — `$prod = pos_unified_resolve_dept_product($pdo, $productKey);`

## pos_unified_lookup {#pos_unified_lookup}

**Callers (1):**

- `api/v1/pos.php`:414 — `api_ok(['barcode' => pos_unified_lookup(ims_pdo(), $code)]);`

## pos_unified_build_cart_lines {#pos_unified_build_cart_lines}

**Tables:** `honey_bottle_units`

**Callers (3):**

- `includes/pos_unified.php`:411 — `$cleanLines = pos_unified_build_cart_lines($pdo, $items, $discountFactor);`
- `includes/pos_unified.php`:578 — `$cleanLines = pos_unified_build_cart_lines($pdo, $items, 1.0);`
- `includes/pos_unified.php`:717 — `$clean = pos_unified_build_cart_lines($pdo, $items, $discountFactor);`

## pos_unified_checkout {#pos_unified_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`, `pos_discount_requests`

**Callers (2):**

- `api/v1/pos.php`:438 — `$result = pos_unified_checkout(ims_pdo(), pos_session_actor(), $items, $identity['buyer_name'], $identity['buyer_credentials'], [`
- `includes/pos_discount_requests.php`:240 — `$result = pos_unified_checkout(`

## pos_unified_ar_checkout {#pos_unified_ar_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`

**Callers (1):**

- `api/v1/pos.php`:471 — `$result = pos_unified_ar_checkout(ims_pdo(), pos_session_actor(), $items, [`

## pos_unified_discount_quote {#pos_unified_discount_quote}

**Callers (1):**

- `includes/pos_discount_requests.php`:139 — `return pos_unified_discount_quote($pdo, $items, $discountPercent);`

## pos_discount_cart_is_unified {#pos_discount_cart_is_unified}

**Callers (2):**

- `includes/pos_discount_requests.php`:138 — `if (pos_discount_cart_is_unified($items)) {`
- `includes/pos_discount_requests.php`:239 — `if (pos_discount_cart_is_unified($items)) {`

