---
title: online_order_parcels
source: includes/online_order_parcels.php
generated: 2026-06-21T11:11:06+02:00
type: module
function_count: 19
---


# online_order_parcels

> Source: `includes/online_order_parcels.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_parcel_item_parser.php`
- `includes/honey_parcel_inventory.php`
- `includes/qr_decode.php`
- `includes/rts_schema.php`
- `includes/rts_storage.php`

## online_parcels_user_is_csr_dept {#online_parcels_user_is_csr_dept}

**Tables:** `departments`

**Callers (1):**

- `includes/online_order_parcels.php`:52 — `return online_parcels_user_is_csr_dept($user);`

## online_parcels_user_can_access {#online_parcels_user_can_access}

**Callers (1):**

- `api/v1/parcels.php`:14 — `if (!online_parcels_user_can_access($user)) {`

## online_parcels_user_can_ship {#online_parcels_user_can_ship}

**Callers (1):**

- `api/v1/deliveries.php`:16 — `if (!online_parcels_user_can_ship($user)) {`

## online_parcels_ensure_schema {#online_parcels_ensure_schema}

**Callers (17):**

- `api/v1/deliveries.php`:132 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:47 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:60 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:88 — `online_parcels_ensure_schema($pdo);`
- `includes/deliveries_upload.php`:63 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:144 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:417 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:151 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:245 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:261 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:495 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:547 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:662 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:781 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:883 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:989 — `online_parcels_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:348 — `online_parcels_ensure_schema($pdo);`

## online_parcels_promote_from_batch {#online_parcels_promote_from_batch}

**Tables:** `csr_online_order_lines`, `online_order_parcels`

**Callers (1):**

- `includes/online_orders_generator.php`:1027 — `$promote = online_parcels_promote_from_batch($pdo, $batchId);`

## online_parcels_normalize_tracking {#online_parcels_normalize_tracking}

**Callers (17):**

- `api/v1/deliveries.php`:133 — `$tracking = online_parcels_normalize_tracking($raw);`
- `api/v1/parcels.php`:89 — `$tracking = online_parcels_normalize_tracking($raw);`
- `includes/deliveries_upload.php`:126 — `$tnNorm = online_parcels_normalize_tracking((string) $tn);`
- `includes/delivery_edit_requests.php`:14 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:126 — `$tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));`
- `includes/delivery_edit_requests.php`:210 — `$tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));`
- `includes/delivery_edit_requests.php`:258 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:297 — `$normalized = online_parcels_normalize_tracking($newValue);`
- `includes/delivery_edit_requests.php`:361 — `$normalized = online_parcels_normalize_tracking($newValue);`
- `includes/delivery_edit_requests.php`:429 — `$tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));`
- `includes/delivery_edit_requests.php`:483 — `$tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));`
- `includes/online_order_parcels.php`:246 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:548 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:663 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:782 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:884 — `$oldTracking = online_parcels_normalize_tracking($oldTracking);`
- `includes/online_order_parcels.php`:885 — `$newTracking = online_parcels_normalize_tracking($newTracking);`

## online_parcels_get_by_tracking {#online_parcels_get_by_tracking}

**Tables:** `online_order_parcels`

**Callers (8):**

- `api/v1/deliveries.php`:138 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:66 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:93 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/delivery_edit_requests.php`:130 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:559 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:668 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:787 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:893 — `$parcel = online_parcels_get_by_tracking($pdo, $oldTracking);`

## online_parcels_get_by_id {#online_parcels_get_by_id}

**Tables:** `online_order_parcels`

**Callers (9):**

- `api/v1/parcels.php`:64 — `$parcel = online_parcels_get_by_id($pdo, $id);`
- `includes/delivery_edit_requests.php`:198 — `return online_parcels_get_by_id($pdo, $parcelId) ?? $parcel;`
- `includes/online_order_parcels.php`:599 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:641 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:706 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:759 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:818 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:862 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:976 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`

## online_parcels_find_packing_photo {#online_parcels_find_packing_photo}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:347 — `$parcel['packing_photo'] = online_parcels_find_packing_photo(`

## online_parcels_format_packing_photo {#online_parcels_format_packing_photo}

**Callers (5):**

- `api/v1/deliveries.php`:380 — `$photo = online_parcels_format_packing_photo([`
- `includes/online_order_parcels.php`:291 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:315 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:393 — `$formatted = online_parcels_format_packing_photo($photoRow);`
- `includes/online_order_parcels.php`:429 — `$formatted = online_parcels_format_packing_photo($photoRow);`

## online_parcels_enrich_packing_photo {#online_parcels_enrich_packing_photo}

**Callers (1):**

- `includes/online_order_parcels.php`:484 — `return online_parcels_enrich_packing_photo($pdo, $formatted);`

## online_parcels_enrich_packing_photos_batch {#online_parcels_enrich_packing_photos_batch}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:532 — `return ['rows' => online_parcels_enrich_packing_photos_batch($pdo, $rows), 'total' => $total];`

## online_parcels_format_row {#online_parcels_format_row}

**Callers (5):**

- `includes/delivery_edit_requests.php`:444 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/delivery_edit_requests.php`:464 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/online_order_parcels.php`:253 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:265 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:526 — `$formatted = online_parcels_format_row($r);`

## online_parcels_list {#online_parcels_list}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/parcels.php`:48 — `$result = online_parcels_list($pdo, $q, $status, $page, $perPage);`

## online_parcels_ship {#online_parcels_ship}

**Tables:** `delivery_batch_items`, `online_order_parcels`, `online_parcel_deduct_lines`, `delivery_batches`

**Callers (1):**

- `api/v1/deliveries.php`:170 — `$result = online_parcels_ship($pdo, (int) $user['id'], $tracking, $deliveryItemId, $shipDate);`

## online_parcels_cancel {#online_parcels_cancel}

**Tables:** `online_order_parcels`, `delivery_cancel_events`

**Callers (1):**

- `api/v1/deliveries.php`:203 — `$result = online_parcels_cancel($pdo, (int) $user['id'], $tracking, $source, $note);`

## online_parcels_hold {#online_parcels_hold}

**Tables:** `online_order_parcels`, `online_parcel_hold_events`

**Callers (1):**

- `api/v1/deliveries.php`:259 — `$result = online_parcels_hold($pdo, (int) $user['id'], $tracking, $note);`

## online_parcels_rewaybill {#online_parcels_rewaybill}

**Tables:** `online_order_parcels`, `csr_online_order_lines`, `online_parcel_tracking_history`

**Callers (1):**

- `api/v1/deliveries.php`:291 — `$result = online_parcels_rewaybill($pdo, (int) $user['id'], $oldTracking, $newTracking);`

## online_parcels_cancel_log_list {#online_parcels_cancel_log_list}

**Tables:** `delivery_cancel_events`, `online_order_parcels`, `users`

**Callers (1):**

- `api/v1/deliveries.php`:232 — `$result = online_parcels_cancel_log_list($pdo, $q, $from, $to, $page, $perPage);`

