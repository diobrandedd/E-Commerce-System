---
title: dept_pos
source: includes/dept_pos.php
generated: 2026-06-24T05:30:58+02:00
type: module
function_count: 3
---


# dept_pos

> Source: `includes/dept_pos.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_inventory.php`
- `includes/dept_products.php`

## dept_pos_catalog {#dept_pos_catalog}

**Callers (1):**

- `api/v1/pos.php`:71 — `api_ok(dept_pos_catalog(ims_pdo(), $deptKey !== '' ? $deptKey : null));`

## dept_pos_build_cart_lines {#dept_pos_build_cart_lines}

**Tables:** `products`

**Callers (2):**

- `includes/dept_pos.php`:117 — `$cleanLines = dept_pos_build_cart_lines($pdo, $items, $discountFactor);`
- `includes/pos_discount_requests.php`:37 — `$clean = dept_pos_build_cart_lines($pdo, $items, $discountFactor);`

## dept_pos_checkout {#dept_pos_checkout}

**Tables:** `pos_sales`, `pos_receipt_items`, `pos_discount_requests`

**Callers (2):**

- `api/v1/pos.php`:90 — `$result = dept_pos_checkout(ims_pdo(), $pinId, $items, $buyerName, $buyerCreds);`
- `includes/pos_discount_requests.php`:235 — `$result = dept_pos_checkout(`

