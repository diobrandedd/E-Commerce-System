---
title: online_order_parcels
source: includes/online_order_parcels.php
generated: 2026-06-05T06:53:58+02:00
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

**Callers (10):**

- `api/v1/deliveries.php`:127 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:47 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:60 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:88 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:114 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:205 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:221 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:455 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:507 — `online_parcels_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:344 — `online_parcels_ensure_schema($pdo);`

## online_parcels_promote_from_batch {#online_parcels_promote_from_batch}

**Tables:** `csr_online_order_lines`, `online_order_parcels`

**Callers (1):**

- `includes/online_orders_generator.php`:871 — `$promote = online_parcels_promote_from_batch($pdo, $batchId);`

## online_parcels_normalize_tracking {#online_parcels_normalize_tracking}

**Callers (7):**

- `api/v1/deliveries.php`:128 — `$tracking = online_parcels_normalize_tracking($raw);`
- `api/v1/parcels.php`:89 — `$tracking = online_parcels_normalize_tracking($raw);`
- `includes/delivery_edit_requests.php`:15 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:80 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:110 — `$normalized = online_parcels_normalize_tracking($newValue);`
- `includes/online_order_parcels.php`:206 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:508 — `$tracking = online_parcels_normalize_tracking($tracking);`

## online_parcels_get_by_tracking {#online_parcels_get_by_tracking}

**Tables:** `online_order_parcels`

**Callers (5):**

- `api/v1/deliveries.php`:133 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:66 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:93 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/deliveries_upload.php`:118 — `$parcel = online_parcels_get_by_tracking($pdo, $tn);`
- `includes/online_order_parcels.php`:519 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`

## online_parcels_get_by_id {#online_parcels_get_by_id}

**Tables:** `online_order_parcels`

**Callers (3):**

- `api/v1/parcels.php`:64 — `$parcel = online_parcels_get_by_id($pdo, $id);`
- `includes/online_order_parcels.php`:559 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:591 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`

## online_parcels_find_packing_photo {#online_parcels_find_packing_photo}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:307 — `$parcel['packing_photo'] = online_parcels_find_packing_photo(`

## online_parcels_format_packing_photo {#online_parcels_format_packing_photo}

**Callers (4):**

- `includes/online_order_parcels.php`:251 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:275 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:353 — `$formatted = online_parcels_format_packing_photo($photoRow);`
- `includes/online_order_parcels.php`:389 — `$formatted = online_parcels_format_packing_photo($photoRow);`

## online_parcels_enrich_packing_photo {#online_parcels_enrich_packing_photo}

**Callers (1):**

- `includes/online_order_parcels.php`:444 — `return online_parcels_enrich_packing_photo($pdo, $formatted);`

## online_parcels_enrich_packing_photos_batch {#online_parcels_enrich_packing_photos_batch}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:492 — `return ['rows' => online_parcels_enrich_packing_photos_batch($pdo, $rows), 'total' => $total];`

## online_parcels_format_row {#online_parcels_format_row}

**Callers (3):**

- `includes/online_order_parcels.php`:213 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:225 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:486 — `$formatted = online_parcels_format_row($r);`

## online_parcels_list {#online_parcels_list}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/parcels.php`:48 — `$result = online_parcels_list($pdo, $q, $status, $page, $perPage);`

## online_parcels_ship {#online_parcels_ship}

**Tables:** `delivery_batch_items`, `online_order_parcels`, `delivery_batches`

**Callers (1):**

- `api/v1/deliveries.php`:162 — `$result = online_parcels_ship($pdo, (int) $user['id'], $tracking, $deliveryItemId, $shipDate);`

