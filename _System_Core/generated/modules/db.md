---
title: db
source: includes/db.php
generated: 2026-08-14T10:21:29+02:00
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
- `includes/rts_storage.php`:24 — `$cfg = rts_config()['storage'] ?? [];`

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

**Callers (250):**

- `api/v1/_bootstrap.php`:176 — `$pdo = ims_pdo();`
- `api/v1/ar.php`:33 — `api_ok(['products' => ar_credits_sellable_products(ims_pdo())]);`
- `api/v1/ar.php`:44 — `api_ok(['staff' => ar_staff_picker_list(ims_pdo())]);`
- `api/v1/ar.php`:63 — `api_ok(['credits' => ar_credits_list(ims_pdo(), $user)]);`
- `api/v1/ar.php`:87 — `$result = ar_credits_take_for_user(ims_pdo(), (int) $user['id'], $productId, $qty);`
- `api/v1/ar.php`:107 — `ar_credits_mark_paid(ims_pdo(), $id, (int) $user['id']);`
- `api/v1/ar.php`:127 — `ar_credits_mark_unpaid(ims_pdo(), $id);`
- `api/v1/bottle_barcodes.php`:99 — `api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);`
- `api/v1/bottle_barcodes.php`:109 — `$before = honey_fetch_packaging_by_production(ims_pdo(), $productionRowId);`
- `api/v1/bottle_barcodes.php`:110 — `$pk = honey_bottle_ensure_packaging_shell(ims_pdo(), (int) $user['id'], $productionRowId);`
- `api/v1/bottle_barcodes.php`:124 — `ims_pdo(),`
- `api/v1/bottle_barcodes.php`:143 — `api_ok(honey_bottle_request_list(ims_pdo(), $status, $filterUser, $page, 25));`
- `api/v1/bottle_barcodes.php`:151 — `api_ok(['request' => honey_bottle_request_get(ims_pdo(), $id)]);`
- `api/v1/bottle_barcodes.php`:161 — `ims_pdo(),`
- `api/v1/bottle_barcodes.php`:174 — `api_ok(['count' => honey_bottle_pending_request_count(ims_pdo())]);`
- `api/v1/bottle_barcodes.php`:190 — `ims_pdo(),`
- `api/v1/bottle_barcodes.php`:204 — `api_ok(['rows' => honey_bottle_batch_list(ims_pdo(), $source)]);`
- `api/v1/bottle_barcodes.php`:212 — `api_ok(['batch' => honey_bottle_batch_get(ims_pdo(), $id)]);`
- `api/v1/bottle_barcodes.php`:221 — `api_ok(['batch' => honey_bottle_mark_printed(ims_pdo(), (int) ($in['batch_id'] ?? 0))]);`
- `api/v1/bottle_barcodes.php`:232 — `$html = honey_bottle_label_sheet_html(ims_pdo(), $id);`
- `api/v1/bottle_barcodes.php`:245 — `ims_pdo(),`
- `api/v1/bottle_barcodes.php`:258 — `api_ok(['barcode' => honey_bottle_lookup(ims_pdo(), $code)]);`
- `api/v1/bottle_barcodes.php`:268 — `api_ok(honey_bottle_scan_stock_in(ims_pdo(), (int) $user['id'], (string) ($in['code'] ?? ''), $adopt));`
- `api/v1/bottle_barcodes.php`:276 — `api_ok(['session' => honey_bottle_pack_session_start(ims_pdo(), (int) $user['id'])]);`
- `api/v1/bottle_barcodes.php`:284 — `api_ok(['session' => honey_bottle_pack_session_get(ims_pdo(), $id)]);`
- _… and 225 more (run impact.php for full list)_

