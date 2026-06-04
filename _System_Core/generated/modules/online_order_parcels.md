---
title: online_order_parcels
source: includes/online_order_parcels.php
generated: 2026-06-04T11:09:09+02:00
type: module
function_count: 10
---


# online_order_parcels

> Source: `includes/online_order_parcels.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_parcel_item_parser.php`
- `includes/honey_parcel_inventory.php`
- `includes/qr_decode.php`
- `includes/rts_schema.php`

## online_parcels_user_can_access {#online_parcels_user_can_access}

**Callers (1):**

- `api/v1/parcels.php`:14 — `if (!online_parcels_user_can_access($user)) {`

## online_parcels_user_can_ship {#online_parcels_user_can_ship}

**Callers (1):**

- `api/v1/deliveries.php`:15 — `if (!online_parcels_user_can_ship($user)) {`

## online_parcels_ensure_schema {#online_parcels_ensure_schema}

**Callers (9):**

- `api/v1/deliveries.php`:116 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:47 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:60 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:88 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:81 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:167 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:183 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:231 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:283 — `online_parcels_ensure_schema($pdo);`

## online_parcels_promote_from_batch {#online_parcels_promote_from_batch}

**Tables:** `csr_online_order_lines`, `online_order_parcels`

**Callers (1):**

- `includes/online_orders_generator.php`:709 — `$promote = online_parcels_promote_from_batch($pdo, $batchId);`

## online_parcels_normalize_tracking {#online_parcels_normalize_tracking}

**Callers (5):**

- `api/v1/deliveries.php`:117 — `$tracking = online_parcels_normalize_tracking($raw);`
- `api/v1/deliveries.php`:260 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `api/v1/parcels.php`:89 — `$tracking = online_parcels_normalize_tracking($raw);`
- `includes/online_order_parcels.php`:168 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:284 — `$tracking = online_parcels_normalize_tracking($tracking);`

## online_parcels_get_by_tracking {#online_parcels_get_by_tracking}

**Tables:** `online_order_parcels`

**Callers (5):**

- `api/v1/deliveries.php`:122 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:66 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:93 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/deliveries_upload.php`:118 — `$parcel = online_parcels_get_by_tracking($pdo, $tn);`
- `includes/online_order_parcels.php`:295 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`

## online_parcels_get_by_id {#online_parcels_get_by_id}

**Tables:** `online_order_parcels`

**Callers (3):**

- `api/v1/parcels.php`:64 — `$parcel = online_parcels_get_by_id($pdo, $id);`
- `includes/online_order_parcels.php`:333 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:365 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`

## online_parcels_format_row {#online_parcels_format_row}

**Callers (3):**

- `includes/online_order_parcels.php`:175 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null);`
- `includes/online_order_parcels.php`:187 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null);`
- `includes/online_order_parcels.php`:262 — `$formatted = online_parcels_format_row($r);`

## online_parcels_list {#online_parcels_list}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/parcels.php`:48 — `$result = online_parcels_list($pdo, $q, $status, $page, $perPage);`

## online_parcels_ship {#online_parcels_ship}

**Tables:** `delivery_batch_items`, `online_order_parcels`, `delivery_batches`

**Callers (1):**

- `api/v1/deliveries.php`:158 — `$result = online_parcels_ship($pdo, (int) $user['id'], $tracking, $deliveryItemId, $shipDate);`

