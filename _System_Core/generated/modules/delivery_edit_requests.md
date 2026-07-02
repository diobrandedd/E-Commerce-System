---
title: delivery_edit_requests
source: includes/delivery_edit_requests.php
generated: 2026-07-02T11:42:00+02:00
type: module
function_count: 15
---


# delivery_edit_requests

> Source: `includes/delivery_edit_requests.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/online_order_parcels.php`
- `includes/honey_parcel_inventory.php`
- `includes/rts_storage.php`

## deliveries_find_existing_recordings {#deliveries_find_existing_recordings}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (4):**

- `api/v1/deliveries.php`:143 — `$existingRecordings = deliveries_find_existing_recordings($pdo, $tracking, $parcelId);`
- `includes/delivery_edit_requests.php`:64 — `$existing = deliveries_find_existing_recordings($pdo, $tracking, $parcelId);`
- `includes/delivery_edit_requests.php`:178 — `if (deliveries_find_existing_recordings($pdo, $tracking, $parcelId) !== []) {`
- `includes/delivery_edit_requests.php`:227 — `$remaining = deliveries_find_existing_recordings($pdo, $tracking, $parcelId);`

## deliveries_assert_tracking_not_duplicate {#deliveries_assert_tracking_not_duplicate}

**Callers (3):**

- `includes/deliveries_upload.php`:137 — `deliveries_assert_tracking_not_duplicate($pdo, $tnNorm, 0, $parcelId);`
- `includes/delivery_edit_requests.php`:270 — `deliveries_assert_tracking_not_duplicate($pdo, $tracking, $itemId, $parcelId);`
- `includes/delivery_edit_requests.php`:369 — `deliveries_assert_tracking_not_duplicate($pdo, $normalized, $itemId, $parcelId);`

## deliveries_get_batch_item_row {#deliveries_get_batch_item_row}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (3):**

- `includes/delivery_edit_requests.php`:207 — `$row = deliveries_get_batch_item_row($pdo, $itemId);`
- `includes/delivery_edit_requests.php`:256 — `$row = deliveries_get_batch_item_row($pdo, $itemId);`
- `includes/delivery_edit_requests.php`:333 — `$row = deliveries_get_batch_item_row($pdo, $itemId);`

## deliveries_delete_item_photo_file {#deliveries_delete_item_photo_file}

**Callers (1):**

- `includes/delivery_edit_requests.php`:242 — `deliveries_delete_item_photo_file($photoFn);`

## deliveries_parcel_id_from_item {#deliveries_parcel_id_from_item}

**Callers (1):**

- `includes/delivery_edit_requests.php`:209 — `$parcelId = deliveries_parcel_id_from_item($pdo, $row);`

## deliveries_revert_parcel_to_packing_if_shipped {#deliveries_revert_parcel_to_packing_if_shipped}

**Tables:** `online_order_parcels`

**Callers (2):**

- `includes/delivery_edit_requests.php`:187 — `deliveries_revert_parcel_to_packing_if_shipped($pdo, $parcelId);`
- `includes/delivery_edit_requests.php`:229 — `deliveries_revert_parcel_to_packing_if_shipped($pdo, $parcelId);`

## deliveries_repair_orphaned_shipped_parcel {#deliveries_repair_orphaned_shipped_parcel}

**Callers (1):**

- `api/v1/deliveries.php`:140 — `$parcel = deliveries_repair_orphaned_shipped_parcel($pdo, $parcel);`

## deliveries_delete_item_by_id {#deliveries_delete_item_by_id}

**Tables:** `edit_requests`, `delivery_batch_items`

**Callers (4):**

- `api/delivery_resolve.php`:53 — `$deleted = deliveries_delete_item_by_id($pdo, $itemId);`
- `api/v1/deliveries.php`:407 — `api_ok(['deleted' => deliveries_delete_item_by_id($pdo, $itemId)]);`
- `includes/delivery_edit_requests.php`:251 — `return deliveries_delete_item_by_id($pdo, $itemId);`
- `includes/delivery_edit_requests.php`:389 — `if (!deliveries_delete_item_by_id($pdo, $itemId)) {`

## deliveries_void_item_by_id {#deliveries_void_item_by_id}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## deliveries_resolve_item_by_id {#deliveries_resolve_item_by_id}

**Tables:** `delivery_batch_items`

**Callers (2):**

- `api/v1/deliveries.php`:428 — `deliveries_resolve_item_by_id($pdo, $itemId, $tracking);`
- `includes/delivery_edit_requests.php`:397 — `deliveries_resolve_item_by_id($pdo, $itemId, (string) ($req['new_value'] ?? ''));`

## delivery_edit_request_normalize_values {#delivery_edit_request_normalize_values}

**Callers (3):**

- `api/v1/edit_requests.php`:94 — `$normalized = delivery_edit_request_normalize_values(`
- `includes/delivery_edit_requests.php`:352 — `delivery_edit_request_normalize_values($field, $currentTn !== '' ? $currentTn : (string) $row['item_status'], '');`
- `includes/delivery_edit_requests.php`:370 — `delivery_edit_request_normalize_values($field, $currentTn, $newValue);`

## delivery_edit_request_assert_no_duplicate_pending {#delivery_edit_request_assert_no_duplicate_pending}

**Tables:** `edit_requests`

**Callers (1):**

- `includes/delivery_edit_requests.php`:347 — `delivery_edit_request_assert_no_duplicate_pending($pdo, $itemId, $field);`

## delivery_edit_request_validate_submit {#delivery_edit_request_validate_submit}

**Tables:** `edit_requests`

**Callers (1):**

- `api/v1/edit_requests.php`:92 — `$item = delivery_edit_request_validate_submit($pdo, $recordId, $field, trim($newValue));`

## delivery_edit_request_apply {#delivery_edit_request_apply}

**Callers (1):**

- `api/v1/edit_requests.php`:150 — `delivery_edit_request_apply($pdo, $req);`

## deliveries_enrich_review_rows {#deliveries_enrich_review_rows}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/deliveries.php`:388 — `$rows = deliveries_enrich_review_rows($pdo, $rows);`

