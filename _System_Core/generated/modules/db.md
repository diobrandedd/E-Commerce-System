---
title: db
source: includes/db.php
generated: 2026-06-10T11:00:00+02:00
type: module
function_count: 3
---


# db

> Source: `includes/db.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/forwarding.php`

## rts_config {#rts_config}

**Callers (2):**

- `includes/db.php`:21 — `$c = rts_config()['db'] ?? [];`
- `includes/rts_storage.php`:21 — `$cfg = rts_config()['storage'] ?? [];`

## rts_pdo {#rts_pdo}

**Callers (23):**

- `api/case.php`:34 — `$pdo = rts_pdo();`
- `api/case.php`:64 — `$pdo = rts_pdo();`
- `api/delivery_batch_detail.php`:32 — `$pdo = rts_pdo();`
- `api/delivery_bulk.php`:91 — `$pdo = rts_pdo();`
- `api/delivery_match_tracking.php`:37 — `$pdo = rts_pdo();`
- `api/delivery_resolve.php`:42 — `$pdo = rts_pdo();`
- `includes/auth.php`:180 — `$pdo = rts_pdo();`
- `includes/auth.php`:251 — `$pdo = rts_pdo();`
- `includes/db.php`:42 — `return rts_pdo();`
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

**Callers (144):**

- `api/v1/_bootstrap.php`:176 — `$pdo = ims_pdo();`
- `api/v1/ar.php`:26 — `$pdo = ims_pdo();`
- `api/v1/ar.php`:42 — `$pdo = ims_pdo();`
- `api/v1/ar.php`:87 — `$pdo = ims_pdo();`
- `api/v1/ar.php`:115 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:53 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:112 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:154 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:187 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:262 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:296 — `$pdo = ims_pdo();`
- `api/v1/costing.php`:331 — `$pdo = ims_pdo();`
- `api/v1/dashboard.php`:23 — `$pdo = ims_pdo();`
- `api/v1/dashboard.php`:161 — `$snapshot = dashboard_inventory_snapshot(ims_pdo(), $period, $from, $to);`
- `api/v1/deliveries.php`:62 — `$pdo = ims_pdo();`
- `api/v1/deliveries.php`:106 — `$pdo = ims_pdo();`
- `api/v1/deliveries.php`:127 — `$pdo = ims_pdo();`
- `api/v1/deliveries.php`:164 — `$pdo = ims_pdo();`
- `api/v1/deliveries.php`:193 — `$pdo = ims_pdo();`
- `api/v1/deliveries.php`:284 — `$pdo = ims_pdo();`
- `api/v1/deliveries.php`:305 — `$pdo = ims_pdo();`
- `api/v1/edit_requests.php`:24 — `$pdo = ims_pdo();`
- `api/v1/edit_requests.php`:81 — `$pdo = ims_pdo();`
- `api/v1/edit_requests.php`:131 — `$pdo = ims_pdo();`
- `api/v1/edit_requests.php`:194 — `$pdo = ims_pdo();`
- _… and 119 more (run impact.php for full list)_

