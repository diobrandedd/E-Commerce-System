---
title: online_order_parcels
source: includes/online_order_parcels.php
generated: 2026-07-01T09:42:08+02:00
type: module
function_count: 27
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
- `includes/online_orders_generator.php`
- `includes/online_orders_generator.php`

## online_parcels_user_is_csr_dept {#online_parcels_user_is_csr_dept}

**Tables:** `departments`

**Callers (2):**

- `includes/online_order_parcels.php`:55 — `return online_parcels_user_is_csr_dept($user);`
- `includes/online_order_parcels.php`:85 — `if (in_array($role, ['tl', 'staff'], true) && online_parcels_user_is_csr_dept($user)) {`

## online_parcels_user_can_access {#online_parcels_user_can_access}

**Callers (2):**

- `api/v1/parcels.php`:14 — `if (!online_parcels_user_can_access($user)) {`
- `includes/partner_sold_reconcile.php`:29 — `return online_parcels_user_can_access($user);`

## online_parcels_user_can_ship {#online_parcels_user_can_ship}

**Callers (1):**

- `api/v1/deliveries.php`:16 — `if (!online_parcels_user_can_ship($user)) {`

## online_parcels_user_can_edit_items {#online_parcels_user_can_edit_items}

**Callers (2):**

- `api/v1/parcels.php`:154 — `if (!online_parcels_user_can_edit_items($user)) {`
- `api/v1/parcels.php`:176 — `if (!online_parcels_user_can_edit_items($user)) {`

## online_parcels_ensure_schema {#online_parcels_ensure_schema}

**Callers (25):**

- `api/v1/deliveries.php`:132 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:51 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:69 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:109 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:137 — `online_parcels_ensure_schema($pdo);`
- `includes/deliveries_upload.php`:63 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:144 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:417 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:240 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:336 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:354 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:426 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:460 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:783 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:810 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:851 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:893 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:976 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1091 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1210 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1312 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1418 — `online_parcels_ensure_schema($pdo);`
- `includes/online_orders_generator.php`:389 — `online_parcels_ensure_schema($pdo);`
- `includes/partner_sold_reconcile.php`:252 — `online_parcels_ensure_schema($pdo);`
- `includes/rts_case_handlers.php`:466 — `online_parcels_ensure_schema($pdo);`

## online_parcels_apply_rts_schema_migrations {#online_parcels_apply_rts_schema_migrations}

**Tables:** `online_order_parcels`, `delivery_cancel_events`

**Callers (1):**

- `includes/online_order_parcels.php`:170 — `online_parcels_apply_rts_schema_migrations($pdo);`

## online_parcels_promote_from_batch {#online_parcels_promote_from_batch}

**Tables:** `csr_online_order_lines`, `online_order_parcels`

**Callers (1):**

- `includes/online_orders_generator.php`:1296 — `$promote = online_parcels_promote_from_batch($pdo, $batchId);`

## online_parcels_normalize_tracking {#online_parcels_normalize_tracking}

**Callers (19):**

- `api/v1/deliveries.php`:133 — `$tracking = online_parcels_normalize_tracking($raw);`
- `api/v1/parcels.php`:138 — `$tracking = online_parcels_normalize_tracking($raw);`
- `includes/deliveries_upload.php`:126 — `$tnNorm = online_parcels_normalize_tracking((string) $tn);`
- `includes/delivery_edit_requests.php`:14 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:126 — `$tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));`
- `includes/delivery_edit_requests.php`:210 — `$tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));`
- `includes/delivery_edit_requests.php`:258 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:297 — `$normalized = online_parcels_normalize_tracking($newValue);`
- `includes/delivery_edit_requests.php`:361 — `$normalized = online_parcels_normalize_tracking($newValue);`
- `includes/delivery_edit_requests.php`:429 — `$tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));`
- `includes/delivery_edit_requests.php`:483 — `$tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));`
- `includes/online_order_parcels.php`:337 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:355 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:977 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:1092 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:1211 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:1313 — `$oldTracking = online_parcels_normalize_tracking($oldTracking);`
- `includes/online_order_parcels.php`:1314 — `$newTracking = online_parcels_normalize_tracking($newTracking);`
- `includes/rts_prefill.php`:191 — `$normalized = online_parcels_normalize_tracking(trim($tracking));`

## online_parcels_get_by_tracking {#online_parcels_get_by_tracking}

**Tables:** `online_order_parcels`

**Callers (9):**

- `api/v1/deliveries.php`:138 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:115 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:142 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/delivery_edit_requests.php`:130 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:988 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:1097 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:1216 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:1322 — `$parcel = online_parcels_get_by_tracking($pdo, $oldTracking);`
- `includes/rts_prefill.php`:209 — `$parcel = online_parcels_get_by_tracking($pdo, $normalized);`

## online_parcels_mark_rts {#online_parcels_mark_rts}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/rts_case_handlers.php`:503 — `online_parcels_mark_rts($pdo, $tracking, $caseId);`

## online_parcels_revert_rts_by_case {#online_parcels_revert_rts_by_case}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/rts_case_handlers.php`:658 — `online_parcels_revert_rts_by_case($pdo, $caseId);`

## online_parcels_get_by_id {#online_parcels_get_by_id}

**Tables:** `online_order_parcels`

**Callers (13):**

- `api/v1/parcels.php`:113 — `$parcel = online_parcels_get_by_id($pdo, $id);`
- `includes/delivery_edit_requests.php`:198 — `return online_parcels_get_by_id($pdo, $parcelId) ?? $parcel;`
- `includes/online_order_parcels.php`:375 — `return online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:395 — `return online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:415 — `return online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:956 — `$fresh = online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:1028 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1070 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1135 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1188 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1247 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1291 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1405 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`

## online_parcels_find_packing_photo {#online_parcels_find_packing_photo}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:543 — `$parcel['packing_photo'] = online_parcels_find_packing_photo(`

## online_parcels_format_packing_photo {#online_parcels_format_packing_photo}

**Callers (5):**

- `api/v1/deliveries.php`:380 — `$photo = online_parcels_format_packing_photo([`
- `includes/online_order_parcels.php`:490 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:514 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:589 — `$formatted = online_parcels_format_packing_photo($photoRow);`
- `includes/online_order_parcels.php`:625 — `$formatted = online_parcels_format_packing_photo($photoRow);`

## online_parcels_enrich_packing_photo {#online_parcels_enrich_packing_photo}

**Callers (1):**

- `includes/online_order_parcels.php`:686 — `return online_parcels_enrich_packing_photo($pdo, $formatted);`

## online_parcels_enrich_packing_photos_batch {#online_parcels_enrich_packing_photos_batch}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:883 — `return ['rows' => online_parcels_enrich_packing_photos_batch($pdo, $rows), 'total' => $total];`

## online_parcels_format_row {#online_parcels_format_row}

**Callers (5):**

- `includes/delivery_edit_requests.php`:444 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/delivery_edit_requests.php`:464 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/online_order_parcels.php`:344 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:464 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:877 — `$formatted = online_parcels_format_row($r);`

## online_parcels_list_query_parts {#online_parcels_list_query_parts}

**Callers (1):**

- `includes/online_order_parcels.php`:862 — `$parts = online_parcels_list_query_parts($filters);`

## online_parcels_filter_options {#online_parcels_filter_options}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/parcels.php`:70 — `api_ok(online_parcels_filter_options($pdo));`

## online_parcels_export_selected {#online_parcels_export_selected}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/parcels.php`:84 — `$result = online_parcels_export_selected(ims_pdo(), $ids);`

## online_parcels_list {#online_parcels_list}

**Tables:** `online_order_parcels`

**Callers (1):**

- `api/v1/parcels.php`:52 — `$result = online_parcels_list($pdo, $q, $status, $page, $perPage, [`

## online_parcels_update_items {#online_parcels_update_items}

**Tables:** `online_order_parcels`, `online_parcel_deduct_lines`, `csr_online_order_lines`

**Callers (1):**

- `api/v1/parcels.php`:188 — `$parcel = online_parcels_update_items(ims_pdo(), $parcelId, $items, (int) $user['id']);`

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

