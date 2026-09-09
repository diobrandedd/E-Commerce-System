---
title: pos_sales_helpers
source: includes/pos_sales_helpers.php
generated: 2026-09-09T10:17:37+02:00
type: module
function_count: 3
---


# pos_sales_helpers

> Source: `includes/pos_sales_helpers.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_pos.php`

## pos_normalize_buyer_name {#pos_normalize_buyer_name}

**Callers (3):**

- `includes/dept_pos.php`:192 — `$buyerName = pos_normalize_buyer_name($buyerName);`
- `includes/honey_pos.php`:303 — `$buyerName = pos_normalize_buyer_name($buyerName);`
- `includes/pos_unified.php`:400 — `$buyerName = pos_normalize_buyer_name($buyerName);`

## pos_normalize_seller_name {#pos_normalize_seller_name}

**Callers (7):**

- `includes/dept_pos.php`:193 — `$sellerName = pos_normalize_seller_name((string) ($opts['seller_name'] ?? ''));`
- `includes/dept_pos.php`:345 — `$sellerName = pos_normalize_seller_name((string) ($opts['seller_name'] ?? ''));`
- `includes/honey_pos.php`:304 — `$sellerName = pos_normalize_seller_name((string) ($opts['seller_name'] ?? ''));`
- `includes/honey_pos.php`:536 — `$sellerName = pos_normalize_seller_name((string) ($opts['seller_name'] ?? ''));`
- `includes/pos_sales_helpers.php`:39 — `$sellerName = pos_normalize_seller_name($sellerName);`
- `includes/pos_unified.php`:401 — `$sellerName = pos_normalize_seller_name((string) ($opts['seller_name'] ?? ''));`
- `includes/pos_unified.php`:563 — `$sellerName = pos_normalize_seller_name($sellerName);`

## pos_sales_seller_insert {#pos_sales_seller_insert}

**Callers (7):**

- `api/v1/pos.php`:166 — `$seller = pos_sales_seller_insert($pdo, ['pin_id' => $pinId, 'user_id' => null], $identity['seller_name']);`
- `includes/dept_pos.php`:194 — `$seller = pos_sales_seller_insert($pdo, ['pin_id' => $posPinId, 'user_id' => null], $sellerName);`
- `includes/dept_pos.php`:346 — `$seller = pos_sales_seller_insert($pdo, ['pin_id' => $posPinId, 'user_id' => null], $sellerName);`
- `includes/honey_pos.php`:305 — `$seller = pos_sales_seller_insert($pdo, ['pin_id' => $posPinId, 'user_id' => null], $sellerName);`
- `includes/honey_pos.php`:537 — `$seller = pos_sales_seller_insert($pdo, ['pin_id' => $posPinId, 'user_id' => null], $sellerName);`
- `includes/pos_unified.php`:402 — `$seller = pos_sales_seller_insert($pdo, $actor, $sellerName);`
- `includes/pos_unified.php`:564 — `$seller = pos_sales_seller_insert($pdo, $actor, $sellerName);`

