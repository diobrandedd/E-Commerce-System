---
title: online_order_parcels
source: includes/online_order_parcels.php
generated: 2026-09-16T11:48:32+02:00
type: module
function_count: 29
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
- `includes/tracking_search.php`
- `includes/permissions.php`
- `includes/online_orders_generator.php`
- `includes/online_orders_generator.php`

## online_parcels_user_is_csr_dept {#online_parcels_user_is_csr_dept}

**Tables:** `departments`

**Callers (2):**

- `includes/online_order_parcels.php`:55 — `return online_parcels_user_is_csr_dept($user);`
- `includes/online_order_parcels.php`:86 — `if (in_array($role, ['tl', 'staff'], true) && online_parcels_user_is_csr_dept($user)) {`

## online_parcels_user_can_access {#online_parcels_user_can_access}

**Callers (4):**

- `api/v1/parcels.php`:14 — `if (!online_parcels_user_can_access($user)) {`
- `includes/courier_soa_reconcile.php`:30 — `return online_parcels_user_can_access($user);`
- `includes/historical_orders_import.php`:100 — `return online_parcels_user_can_access($user);`
- `includes/partner_sold_reconcile.php`:29 — `return online_parcels_user_can_access($user);`

## online_parcels_user_can_ship {#online_parcels_user_can_ship}

**Callers (1):**

- `api/v1/deliveries.php`:17 — `if (!online_parcels_user_can_ship($user)) {`

## online_parcels_user_can_edit_items {#online_parcels_user_can_edit_items}

**Callers (2):**

- `api/v1/parcels.php`:153 — `if (!online_parcels_user_can_edit_items($user)) {`
- `api/v1/parcels.php`:175 — `if (!online_parcels_user_can_edit_items($user)) {`

## online_parcels_ensure_schema {#online_parcels_ensure_schema}

**Callers (29):**

- `api/v1/deliveries.php`:133 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:51 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:69 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:109 — `online_parcels_ensure_schema($pdo);`
- `api/v1/parcels.php`:137 — `online_parcels_ensure_schema($pdo);`
- `includes/courier_soa_reconcile.php`:338 — `online_parcels_ensure_schema($pdo);`
- `includes/deliveries_upload.php`:63 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:144 — `online_parcels_ensure_schema($pdo);`
- `includes/delivery_edit_requests.php`:417 — `online_parcels_ensure_schema($pdo);`
- `includes/historical_orders_import.php`:385 — `online_parcels_ensure_schema($pdo);`
- `includes/historical_orders_import.php`:609 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:241 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:337 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:353 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:388 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:460 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:494 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:827 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:854 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:895 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:937 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1020 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1126 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1245 — `online_parcels_ensure_schema($pdo);`
- `includes/online_order_parcels.php`:1347 — `online_parcels_ensure_schema($pdo);`
- _… and 4 more (run impact.php for full list)_

## online_parcels_apply_rts_schema_migrations {#online_parcels_apply_rts_schema_migrations}

**Tables:** `online_order_parcels`, `delivery_cancel_events`

**Callers (1):**

- `includes/online_order_parcels.php`:171 — `online_parcels_apply_rts_schema_migrations($pdo);`

## online_parcels_promote_from_batch {#online_parcels_promote_from_batch}

**Tables:** `csr_online_order_lines`, `online_order_parcels`

**Callers (1):**

- `includes/online_orders_generator.php`:1296 — `$promote = online_parcels_promote_from_batch($pdo, $batchId);`

## online_parcels_normalize_tracking {#online_parcels_normalize_tracking}

**Callers (19):**

- `api/v1/deliveries.php`:134 — `$tracking = online_parcels_normalize_tracking($raw);`
- `includes/courier_soa_reconcile.php`:52 — `$tn = online_parcels_normalize_tracking($raw);`
- `includes/deliveries_upload.php`:126 — `$tnNorm = online_parcels_normalize_tracking((string) $tn);`
- `includes/delivery_edit_requests.php`:14 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:126 — `$tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));`
- `includes/delivery_edit_requests.php`:210 — `$tracking = online_parcels_normalize_tracking(trim((string) ($row['tracking_number'] ?? '')));`
- `includes/delivery_edit_requests.php`:258 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/delivery_edit_requests.php`:297 — `$normalized = online_parcels_normalize_tracking($newValue);`
- `includes/delivery_edit_requests.php`:361 — `$normalized = online_parcels_normalize_tracking($newValue);`
- `includes/delivery_edit_requests.php`:429 — `$tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));`
- `includes/delivery_edit_requests.php`:483 — `$tn = online_parcels_normalize_tracking((string) ($row['tracking_number'] ?? ''));`
- `includes/online_order_parcels.php`:338 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:389 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:1021 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:1127 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:1246 — `$tracking = online_parcels_normalize_tracking($tracking);`
- `includes/online_order_parcels.php`:1348 — `$oldTracking = online_parcels_normalize_tracking($oldTracking);`
- `includes/online_order_parcels.php`:1349 — `$newTracking = online_parcels_normalize_tracking($newTracking);`
- `includes/rts_prefill.php`:191 — `$normalized = online_parcels_normalize_tracking(trim($tracking));`

## online_parcels_get_by_tracking {#online_parcels_get_by_tracking}

**Tables:** `online_order_parcels`

**Callers (11):**

- `api/v1/deliveries.php`:139 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `api/v1/parcels.php`:115 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/delivery_edit_requests.php`:130 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/historical_orders_import.php`:511 — `$existing = online_parcels_get_by_tracking($pdo, $tn);`
- `includes/historical_orders_import.php`:808 — `if (online_parcels_get_by_tracking($pdo, $tn) !== null) {`
- `includes/online_order_parcels.php`:373 — `$byTn = online_parcels_get_by_tracking($pdo, $raw);`
- `includes/online_order_parcels.php`:1032 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:1132 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:1251 — `$parcel = online_parcels_get_by_tracking($pdo, $tracking);`
- `includes/online_order_parcels.php`:1357 — `$parcel = online_parcels_get_by_tracking($pdo, $oldTracking);`
- `includes/rts_prefill.php`:209 — `$parcel = online_parcels_get_by_tracking($pdo, $normalized);`

## online_parcels_get_by_order_no {#online_parcels_get_by_order_no}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/online_order_parcels.php`:378 — `return online_parcels_get_by_order_no($pdo, $raw);`

## online_parcels_lookup_q {#online_parcels_lookup_q}

**Callers (1):**

- `api/v1/parcels.php`:138 — `$parcel = online_parcels_lookup_q($pdo, $raw);`

## online_parcels_mark_rts {#online_parcels_mark_rts}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/rts_case_handlers.php`:533 — `online_parcels_mark_rts($pdo, $tracking, $caseId);`

## online_parcels_revert_rts_by_case {#online_parcels_revert_rts_by_case}

**Tables:** `online_order_parcels`

**Callers (1):**

- `includes/rts_case_handlers.php`:688 — `online_parcels_revert_rts_by_case($pdo, $caseId);`

## online_parcels_get_by_id {#online_parcels_get_by_id}

**Tables:** `online_order_parcels`

**Callers (13):**

- `api/v1/parcels.php`:113 — `$parcel = online_parcels_get_by_id($pdo, $id);`
- `includes/delivery_edit_requests.php`:198 — `return online_parcels_get_by_id($pdo, $parcelId) ?? $parcel;`
- `includes/online_order_parcels.php`:409 — `return online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:429 — `return online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:449 — `return online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:1000 — `$fresh = online_parcels_get_by_id($pdo, $parcelId);`
- `includes/online_order_parcels.php`:1072 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1105 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1170 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1223 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1282 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1326 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`
- `includes/online_order_parcels.php`:1440 — `$fresh = online_parcels_get_by_id($pdo, (int) $parcel['id']);`

## online_parcels_find_packing_photo {#online_parcels_find_packing_photo}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:577 — `$parcel['packing_photo'] = online_parcels_find_packing_photo(`

## online_parcels_format_packing_photo {#online_parcels_format_packing_photo}

**Callers (5):**

- `api/v1/deliveries.php`:362 — `$photo = online_parcels_format_packing_photo([`
- `includes/online_order_parcels.php`:524 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:548 — `return online_parcels_format_packing_photo($row);`
- `includes/online_order_parcels.php`:623 — `$formatted = online_parcels_format_packing_photo($photoRow);`
- `includes/online_order_parcels.php`:659 — `$formatted = online_parcels_format_packing_photo($photoRow);`

## online_parcels_enrich_packing_photo {#online_parcels_enrich_packing_photo}

**Callers (1):**

- `includes/online_order_parcels.php`:720 — `return online_parcels_enrich_packing_photo($pdo, $formatted);`

## online_parcels_enrich_packing_photos_batch {#online_parcels_enrich_packing_photos_batch}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/online_order_parcels.php`:927 — `return ['rows' => online_parcels_enrich_packing_photos_batch($pdo, $rows), 'total' => $total];`

## online_parcels_format_row {#online_parcels_format_row}

**Callers (6):**

- `includes/delivery_edit_requests.php`:444 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/delivery_edit_requests.php`:464 — `$parcel = online_parcels_format_row($raw, null);`
- `includes/online_order_parcels.php`:345 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:361 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:498 — `return online_parcels_format_row($st->fetch(PDO::FETCH_ASSOC) ?: null, $pdo);`
- `includes/online_order_parcels.php`:921 — `$formatted = online_parcels_format_row($r);`

## online_parcels_list_query_parts {#online_parcels_list_query_parts}

**Callers (1):**

- `includes/online_order_parcels.php`:906 — `$parts = online_parcels_list_query_parts($filters);`

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

- `api/v1/parcels.php`:187 — `$parcel = online_parcels_update_items(ims_pdo(), $parcelId, $items, (int) $user['id']);`

## online_parcels_ship {#online_parcels_ship}

**Tables:** `delivery_batch_items`, `online_order_parcels`, `delivery_batches`

**Callers (1):**

- `api/v1/deliveries.php`:171 — `$result = online_parcels_ship($pdo, (int) $user['id'], $tracking, $deliveryItemId, $shipDate);`

## online_parcels_cancel {#online_parcels_cancel}

**Tables:** `online_order_parcels`, `delivery_cancel_events`

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## online_parcels_hold {#online_parcels_hold}

**Tables:** `online_order_parcels`, `online_parcel_hold_events`

**Callers (1):**

- `api/v1/deliveries.php`:233 — `$result = online_parcels_hold($pdo, (int) $user['id'], $tracking, $note);`

## online_parcels_rewaybill {#online_parcels_rewaybill}

**Tables:** `online_order_parcels`, `csr_online_order_lines`, `online_parcel_tracking_history`

**Callers (1):**

- `api/v1/deliveries.php`:265 — `$result = online_parcels_rewaybill($pdo, (int) $user['id'], $oldTracking, $newTracking);`

## online_parcels_cancel_log_list {#online_parcels_cancel_log_list}

**Tables:** `delivery_cancel_events`, `online_order_parcels`, `users`

**Callers (1):**

- `api/v1/deliveries.php`:206 — `$result = online_parcels_cancel_log_list($pdo, $q, $from, $to, $page, $perPage);`

