---
title: bulk_honey_orders
source: includes/bulk_honey_orders.php
generated: 2026-09-16T03:38:44+02:00
type: module
function_count: 21
---


# bulk_honey_orders

> Source: `includes/bulk_honey_orders.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/honey_pricing.php`

## bulk_honey_orders_assert_executive {#bulk_honey_orders_assert_executive}

**Callers (1):**

- `api/v1/bulk_orders.php`:25 — `bulk_honey_orders_assert_executive($user);`

## bulk_honey_orders_table_exists {#bulk_honey_orders_table_exists}

**Callers (2):**

- `api/v1/bulk_orders.php`:38 — `if (!bulk_honey_orders_table_exists($pdo)) {`
- `includes/bulk_honey_orders.php`:350 — `if (!bulk_honey_orders_table_exists($pdo)) {`

## bulk_honey_normalize_variant {#bulk_honey_normalize_variant}

**Callers (2):**

- `includes/bulk_honey_orders.php`:61 — `$variant = bulk_honey_normalize_variant($variant);`
- `includes/bulk_honey_orders.php`:251 — `$variant = bulk_honey_normalize_variant((string) ($line['variant'] ?? ''));`

## bulk_honey_normalize_negotiation_mode {#bulk_honey_normalize_negotiation_mode}

**Callers (2):**

- `includes/bulk_honey_orders.php`:205 — `$mode = bulk_honey_normalize_negotiation_mode($negotiationMode) ?? 'unit_price';`
- `includes/bulk_honey_orders.php`:438 — `$mode = bulk_honey_normalize_negotiation_mode(`

## bulk_honey_warehouse_rows_for_variant {#bulk_honey_warehouse_rows_for_variant}

**Tables:** `honey_warehouse_rows`

**Callers (2):**

- `api/v1/bulk_orders.php`:67 — `api_ok(['rows' => bulk_honey_warehouse_rows_for_variant(ims_pdo(), $variant)]);`
- `includes/bulk_honey_orders.php`:103 — `$rows = bulk_honey_warehouse_rows_for_variant($pdo, $variant);`

## bulk_honey_suggest_fifo_allocations {#bulk_honey_suggest_fifo_allocations}

**Callers (1):**

- `api/v1/bulk_orders.php`:82 — `'allocations' => bulk_honey_suggest_fifo_allocations(ims_pdo(), $variant, $qty),`

## bulk_honey_compute_allocation_cogs {#bulk_honey_compute_allocation_cogs}

**Callers (1):**

- `includes/bulk_honey_orders.php`:257 — `$cogsData = bulk_honey_compute_allocation_cogs($pdo, $qty, $allocs);`

## bulk_honey_resolve_unit_sale_price {#bulk_honey_resolve_unit_sale_price}

**Callers (1):**

- `includes/bulk_honey_orders.php`:260 — `$unitPrice = bulk_honey_resolve_unit_sale_price(`

## bulk_honey_preview_cost {#bulk_honey_preview_cost}

**Callers (2):**

- `api/v1/bulk_orders.php`:96 — `api_ok(bulk_honey_preview_cost(ims_pdo(), $lines));`
- `includes/bulk_honey_orders.php`:358 — `$preview = bulk_honey_preview_cost($pdo, $linesIn);`

## bulk_honey_margin_suggestions {#bulk_honey_margin_suggestions}

**Callers (1):**

- `includes/bulk_honey_orders.php`:286 — `'margin_suggestions' => bulk_honey_margin_suggestions($cogsPerGal, $qty),`

## bulk_honey_next_order_no {#bulk_honey_next_order_no}

**Tables:** `bulk_honey_orders`

**Callers (1):**

- `includes/bulk_honey_orders.php`:398 — `$orderNo = bulk_honey_next_order_no($pdo);`

## bulk_honey_save_draft {#bulk_honey_save_draft}

**Tables:** `bulk_honey_orders`, `bulk_honey_order_allocations`, `bulk_honey_order_lines`

**Callers (2):**

- `api/v1/bulk_orders.php`:109 — `$result = bulk_honey_save_draft(ims_pdo(), (int) $user['id'], null, $input);`
- `api/v1/bulk_orders.php`:127 — `$result = bulk_honey_save_draft(ims_pdo(), (int) $user['id'], $id, $input);`

## bulk_honey_nullable_str {#bulk_honey_nullable_str}

**Callers (9):**

- `includes/bulk_honey_orders.php`:383 — `bulk_honey_nullable_str($payload['partner_label'] ?? null),`
- `includes/bulk_honey_orders.php`:384 — `bulk_honey_nullable_str($payload['notes'] ?? null),`
- `includes/bulk_honey_orders.php`:386 — `bulk_honey_nullable_str($payload['courier'] ?? null),`
- `includes/bulk_honey_orders.php`:387 — `bulk_honey_nullable_str($payload['tracking_ref'] ?? null),`
- `includes/bulk_honey_orders.php`:408 — `bulk_honey_nullable_str($payload['partner_label'] ?? null),`
- `includes/bulk_honey_orders.php`:409 — `bulk_honey_nullable_str($payload['notes'] ?? null),`
- `includes/bulk_honey_orders.php`:411 — `bulk_honey_nullable_str($payload['courier'] ?? null),`
- `includes/bulk_honey_orders.php`:412 — `bulk_honey_nullable_str($payload['tracking_ref'] ?? null),`
- `includes/bulk_honey_orders.php`:493 — `$s = bulk_honey_nullable_str($v);`

## bulk_honey_nullable_date {#bulk_honey_nullable_date}

**Callers (2):**

- `includes/bulk_honey_orders.php`:385 — `bulk_honey_nullable_date($payload['shipment_date'] ?? null),`
- `includes/bulk_honey_orders.php`:410 — `bulk_honey_nullable_date($payload['shipment_date'] ?? null),`

## bulk_honey_order_confirm {#bulk_honey_order_confirm}

**Tables:** `bulk_honey_orders`, `bulk_honey_order_allocations`

**Callers (1):**

- `api/v1/bulk_orders.php`:145 — `api_ok(bulk_honey_order_confirm(ims_pdo(), (int) $user['id'], $id));`

## bulk_honey_order_void {#bulk_honey_order_void}

**Tables:** `bulk_honey_orders`, `bulk_honey_order_allocations`

**Callers (1):**

- `api/v1/bulk_orders.php`:162 — `api_ok(bulk_honey_order_void(ims_pdo(), (int) $user['id'], $id));`

## bulk_honey_order_delete_draft {#bulk_honey_order_delete_draft}

**Tables:** `bulk_honey_orders`

**Callers (1):**

- `api/v1/bulk_orders.php`:179 — `bulk_honey_order_delete_draft(ims_pdo(), $id);`

## bulk_honey_order_get {#bulk_honey_order_get}

**Tables:** `bulk_honey_orders`, `bulk_honey_order_lines`, `bulk_honey_order_allocations`

**Callers (4):**

- `api/v1/bulk_orders.php`:55 — `api_ok(bulk_honey_order_get(ims_pdo(), $id));`
- `includes/bulk_honey_orders.php`:478 — `return bulk_honey_order_get($pdo, $orderId);`
- `includes/bulk_honey_orders.php`:545 — `return bulk_honey_order_get($pdo, $orderId);`
- `includes/bulk_honey_orders.php`:582 — `return bulk_honey_order_get($pdo, $orderId);`

## bulk_honey_orders_list {#bulk_honey_orders_list}

**Tables:** `bulk_honey_orders`

**Callers (1):**

- `api/v1/bulk_orders.php`:43 — `api_ok(['orders' => bulk_honey_orders_list($pdo, $status)]);`

## bulk_honey_format_order {#bulk_honey_format_order}

**Callers (2):**

- `includes/bulk_honey_orders.php`:642 — `'order' => bulk_honey_format_order($order),`
- `includes/bulk_honey_orders.php`:664 — `static fn (array $row) => bulk_honey_format_order($row),`

## bulk_honey_format_line {#bulk_honey_format_line}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

