---
title: db
source: includes/db.php
generated: 2026-11-27T06:28:46+01:00
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

**Callers (441):**

- `api/v1/_bootstrap.php`:204 — `$pdo = ims_pdo();`
- `api/v1/approvals.php`:38 — `$rows = override_approval_list(ims_pdo(), $user, $status !== '' ? $status : 'all');`
- `api/v1/approvals.php`:39 — `$hr = override_approval_hr_manager_public(ims_pdo());`
- `api/v1/approvals.php`:52 — `$row = override_approval_get(ims_pdo(), $id);`
- `api/v1/approvals.php`:71 — `$request = override_approval_approve(ims_pdo(), $id, $user);`
- `api/v1/approvals.php`:74 — `$req = override_approval_get(ims_pdo(), $id);`
- `api/v1/approvals.php`:91 — `$request = override_approval_reject(ims_pdo(), $id, $user, $note);`
- `api/v1/approvals.php`:102 — `api_ok(['count' => override_approval_pending_count(ims_pdo())]);`
- `api/v1/approvals.php`:109 — `api_ok(['rows' => override_approval_unread_list(ims_pdo())]);`
- `api/v1/approvals.php`:119 — `override_approval_mark_viewed(ims_pdo(), $id && $id > 0 ? $id : null);`
- `api/v1/ar.php`:33 — `api_ok(['products' => ar_credits_sellable_products(ims_pdo())]);`
- `api/v1/ar.php`:44 — `api_ok(['staff' => ar_staff_picker_list(ims_pdo())]);`
- `api/v1/ar.php`:63 — `api_ok(['credits' => ar_credits_list(ims_pdo(), $user)]);`
- `api/v1/ar.php`:87 — `$result = ar_credits_take_for_user(ims_pdo(), (int) $user['id'], $productId, $qty);`
- `api/v1/ar.php`:107 — `ar_credits_mark_paid(ims_pdo(), $id, (int) $user['id']);`
- `api/v1/ar.php`:127 — `ar_credits_mark_unpaid(ims_pdo(), $id);`
- `api/v1/auth.php`:180 — `$issued = pos_api_token_issue(ims_pdo(), [`
- `api/v1/auth.php`:212 — `$pdo = ims_pdo();`
- `api/v1/auth.php`:258 — `$issued = pos_api_token_issue(ims_pdo(), [`
- `api/v1/auth.php`:292 — `$pdo = ims_pdo();`
- `api/v1/auth.php`:323 — `pos_api_token_revoke_plain(ims_pdo(), $plain);`
- `api/v1/barcode_verify.php`:25 — `api_ok(['result' => barcode_verify_lookup(ims_pdo(), $code)]);`
- `api/v1/bottle_barcodes.php`:131 — `api_ok(['rows' => honey_bottle_packaging_options(ims_pdo())]);`
- `api/v1/bottle_barcodes.php`:142 — `ims_pdo(),`
- `api/v1/bottle_barcodes.php`:157 — `ims_pdo(),`
- _… and 416 more (run impact.php for full list)_

