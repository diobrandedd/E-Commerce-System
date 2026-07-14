---
title: db
source: includes/db.php
generated: 2026-07-14T09:44:42+02:00
type: module
function_count: 4
---


# db

> Source: `includes/db.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/forwarding.php`

## rts_config {#rts_config}

**Callers (2):**

- `includes/db.php`:50 — `$c = rts_config()['db'] ?? [];`
- `includes/rts_storage.php`:21 — `$cfg = rts_config()['storage'] ?? [];`

## rts_runtime_db_name {#rts_runtime_db_name}

**Callers (1):**

- `includes/db.php`:46 — `$name = rts_runtime_db_name();`

## rts_pdo {#rts_pdo}

**Callers (25):**

- `api/case.php`:34 — `$pdo = rts_pdo();`
- `api/case.php`:64 — `$pdo = rts_pdo();`
- `api/delivery_batch_detail.php`:32 — `$pdo = rts_pdo();`
- `api/delivery_bulk.php`:91 — `$pdo = rts_pdo();`
- `api/delivery_check_tracking.php`:24 — `$pdo = rts_pdo();`
- `api/delivery_match_tracking.php`:37 — `$pdo = rts_pdo();`
- `api/delivery_resolve.php`:43 — `$pdo = rts_pdo();`
- `includes/auth.php`:180 — `$pdo = rts_pdo();`
- `includes/auth.php`:251 — `$pdo = rts_pdo();`
- `includes/auth.php`:311 — `$pdo = rts_pdo();`
- `includes/db.php`:71 — `return rts_pdo();`
- `sp_sort/api/complete.php`:10 — `$pdo = rts_pdo();`
- `sp_sort/api/export.php`:8 — `$pdo = rts_pdo();`
- `sp_sort/batches.php`:7 — `$pdo = rts_pdo();`
- `sp_sort/bootstrap.php`:18 — `return rts_delivery_review_nav_badge_html(rts_pdo());`
- `sp_sort/carousel.php`:8 — `$pdo = rts_pdo();`
- `sp_sort/download.php`:8 — `$pdo = rts_pdo();`
- `sp_sort/print.php`:8 — `$pdo = rts_pdo();`
- `sp_sort/process.php`:8 — `$pdo = rts_pdo();`
- `sp_sort/workflow.php`:8 — `$pdo = rts_pdo();`
- `dashboard.php`:17 — `$pdo = rts_pdo();`
- `executive.php`:46 — `$pdo = rts_pdo();`
- `record.php`:21 — `$deliveryNavBadge = rts_delivery_review_nav_badge_html(rts_pdo());`
- `records.php`:51 — `$pdo = rts_pdo();`
- `settings.php`:34 — `$pdo = rts_pdo();`

## ims_pdo {#ims_pdo}

**Callers (215):**

- `api/v1/_bootstrap.php`:176 — `$pdo = ims_pdo();`
- `api/v1/ar.php`:33 — `api_ok(['products' => ar_credits_sellable_products(ims_pdo())]);`
- `api/v1/ar.php`:44 — `api_ok(['staff' => ar_staff_picker_list(ims_pdo())]);`
- `api/v1/ar.php`:63 — `api_ok(['credits' => ar_credits_list(ims_pdo(), $user)]);`
- `api/v1/ar.php`:87 — `$result = ar_credits_take_for_user(ims_pdo(), (int) $user['id'], $productId, $qty);`
- `api/v1/ar.php`:107 — `ar_credits_mark_paid(ims_pdo(), $id, (int) $user['id']);`
- `api/v1/ar.php`:127 — `ar_credits_mark_unpaid(ims_pdo(), $id);`
- `api/v1/bulk_orders.php`:37 — `$pdo = ims_pdo();`
- `api/v1/bulk_orders.php`:55 — `api_ok(bulk_honey_order_get(ims_pdo(), $id));`
- `api/v1/bulk_orders.php`:67 — `api_ok(['rows' => bulk_honey_warehouse_rows_for_variant(ims_pdo(), $variant)]);`
- `api/v1/bulk_orders.php`:82 — `'allocations' => bulk_honey_suggest_fifo_allocations(ims_pdo(), $variant, $qty),`
- `api/v1/bulk_orders.php`:96 — `api_ok(bulk_honey_preview_cost(ims_pdo(), $lines));`
- `api/v1/bulk_orders.php`:109 — `$result = bulk_honey_save_draft(ims_pdo(), (int) $user['id'], null, $input);`
- `api/v1/bulk_orders.php`:127 — `$result = bulk_honey_save_draft(ims_pdo(), (int) $user['id'], $id, $input);`
- `api/v1/bulk_orders.php`:145 — `api_ok(bulk_honey_order_confirm(ims_pdo(), (int) $user['id'], $id));`
- `api/v1/bulk_orders.php`:162 — `api_ok(bulk_honey_order_void(ims_pdo(), (int) $user['id'], $id));`
- `api/v1/bulk_orders.php`:179 — `bulk_honey_order_delete_draft(ims_pdo(), $id);`
- `api/v1/costing.php`:58 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:122 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:192 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:225 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:297 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:331 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:363 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:489 — `$pdo = ims_pdo();`
- _… and 190 more (run impact.php for full list)_

