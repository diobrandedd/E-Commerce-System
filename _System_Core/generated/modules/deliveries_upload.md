---
title: deliveries_upload
source: includes/deliveries_upload.php
generated: 2026-08-18T09:58:59+02:00
type: module
function_count: 3
---


# deliveries_upload

> Source: `includes/deliveries_upload.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/auth.php`
- `includes/qr_decode.php`
- `includes/rts_storage.php`
- `includes/rts_schema.php`
- `includes/delivery_edit_requests.php`
- `includes/online_order_parcels.php`

## deliveries_upload_photos {#deliveries_upload_photos}

**Tables:** `delivery_batches`, `online_order_parcels`, `delivery_batch_items`

**Callers (2):**

- `api/delivery_bulk.php`:114 — `$result = deliveries_upload_photos(`
- `api/v1/deliveries.php`:115 — `$result = deliveries_upload_photos($pdo, $deliveryDate, $files, $hints, $submitterId);`

## deliveries_classify_upload {#deliveries_classify_upload}

**Callers (2):**

- `includes/deliveries_upload.php`:105 — `$class = deliveries_classify_upload('', $hintRaw);`
- `includes/deliveries_upload.php`:111 — `$class = deliveries_classify_upload($blob, '');`

## deliveries_ims_submitter_app_user_id {#deliveries_ims_submitter_app_user_id}

**Callers (1):**

- `api/v1/deliveries.php`:112 — `$submitterId = deliveries_ims_submitter_app_user_id($pdo);`

