---
title: dept_pos
source: includes/dept_pos.php
generated: 2026-06-10T11:00:01+02:00
type: module
function_count: 2
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

## dept_pos_checkout {#dept_pos_checkout}

**Tables:** `products`, `pos_sales`, `pos_receipt_items`

**Callers (1):**

- `api/v1/pos.php`:90 — `$result = dept_pos_checkout(ims_pdo(), $pinId, $items, $buyerName, $buyerCreds);`

