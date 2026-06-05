---
title: deliveries_upload
source: includes/deliveries_upload.php
generated: 2026-06-05T06:53:55+02:00
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
- `includes/online_order_parcels.php`

## deliveries_upload_photos {#deliveries_upload_photos}

**Tables:** `delivery_batches`, `delivery_batch_items`

**Callers (2):**

- `api/delivery_bulk.php`:114 — `$result = deliveries_upload_photos(`
- `api/v1/deliveries.php`:109 — `$result = deliveries_upload_photos($pdo, $deliveryDate, $files, $hints, $submitterId);`

## deliveries_classify_upload {#deliveries_classify_upload}

**Callers (1):**

- `includes/deliveries_upload.php`:105 — `$class = deliveries_classify_upload($blob, $hintRaw);`

## deliveries_ims_submitter_app_user_id {#deliveries_ims_submitter_app_user_id}

**Callers (1):**

- `api/v1/deliveries.php`:106 — `$submitterId = deliveries_ims_submitter_app_user_id($pdo);`

