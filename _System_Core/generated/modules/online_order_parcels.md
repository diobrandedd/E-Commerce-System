---
title: online_order_parcels
source: includes/online_order_parcels.php
generated: 2026-06-12T11:00:18+02:00
type: module
function_count: 15
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

- `includes/online_order_parcels.php`:47 — `return online_parcels_user_is_csr_dept($user);`

## online_parcels_user_can_access {#online_parcels_user_can_access}

**Callers (1):**

- `api/v1/parcels.php`:14 — `if (!online_parcels_user_can_access($user)) {`

## online_parcels_user_can_ship {#online_parcels_user_can_ship}

**Callers (1):**

- `api/v1/deliveries.php`:16 — `if (!online_parcels_user_can_ship($user)) {`

## online_parcels_ensure_schema {#online_parcels_ensure_schema}

**Callers (13):**

- `api/v1/deliveries.php`:128 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:47 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:60 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:88 — `online_parcels_ensure_schema($pdo);`
- `includes/deliveries_upload.php`:63 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:144 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:417 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:127 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:221 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:237 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:471 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:523 — `online_parcels_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:348 — `online_parcels_ensure_schema($pdo);`

## online_parcels_promote_from_batch {#online_parcels_promote_from_batch}

**Tables:** `csr_online_order_lines`, `online_order_parcels`

**Callers (1):**

- `includes/online_orders_generator.php`:1027 — `$promote = online_parcels_promote_from_batch($pdo, $batchId);`

## online_parcels_normalize_tracking {#online_parcels_normalize_tracking}

**Callers (13):**

- `api/v1/deliveries.php`:129 — `$tracking = online_parcels_normalize_tracking($raw);`
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
- `includes/online_order_parcels.php`:222 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:524 — `$tracking = online_parcels_normalize_tracking($tracking);`

## online_parcels_get_by_tracking {#online_parcels_get_by_tracking}

**Tables:** `online_order_parcels`

**Callers (5):**

- `api/v1/deliveries.php`:134 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:66 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:93 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/delivery_edit_requests.php`:130 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:535 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`

## online_parcels_get_by_id {#online_parcels_get_by_id}

**Tables:** `online_order_parcels`

**Callers (4):**

- `api/v1/parcels.php`:64 — `$parcel = online_parcels_get_by_id($pdo, $id);`
- `includes/delivery_edit_requests.php`:198 — `return online_parcels_get_by_id($pdo, $parcelId) ?? $parcel;`
- `includes/online_order_parcels.php`:575 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:607 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`

## online_parcels_find_packing_photo {#online_parcels_find_packing_photo}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:323 — `$parcel['packing_photo'] = online_parcels_find_packing_photo(`

## online_parcels_format_packing_photo {#online_parcels_format_packing_photo}

**Callers (5):**

- `api/v1/deliveries.php`:259 — `$photo = online_parcels_format_packing_photo([`
- `includes/online_order_parcels.php`:267 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:291 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:369 — `$formatted = online_parcels_format_packing_photo($photoRow);`
- `includes/online_order_parcels.php`:405 — `$formatted = online_parcels_format_packing_photo($photoRow);`

## online_parcels_enrich_packing_photo {#online_parcels_enrich_packing_photo}

**Callers (1):**

- `includes/online_order_parcels.php`:460 — `return online_parcels_enrich_packing_photo($pdo, $formatted);`

## online_parcels_enrich_packing_photos_batch {#online_parcels_enrich_packing_photos_batch}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:508 — `return ['rows' => online_parcels_enrich_packing_photos_batch($pdo, $rows), 'total' => $total];`

## online_parcels_format_row {#online_parcels_format_row}

**Callers (5):**

- `includes/delivery_edit_requests.php`:444 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/delivery_edit_requests.php`:464 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/online_order_parcels.php`:229 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:241 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:502 — `$formatted = online_parcels_format_row($r);`

## online_parcels_list {#online_parcels_list}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/parcels.php`:48 — `$result = online_parcels_list($pdo, $q, $status, $page, $perPage);`

## online_parcels_ship {#online_parcels_ship}

**Tables:** `delivery_batch_items`, `online_order_parcels`, `delivery_batches`

**Callers (1):**

- `api/v1/deliveries.php`:166 — `$result = online_parcels_ship($pdo, (int) $user['id'], $tracking, $deliveryItemId, $shipDate);`

