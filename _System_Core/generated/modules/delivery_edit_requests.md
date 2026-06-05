---
title: delivery_edit_requests
source: includes/delivery_edit_requests.php
generated: 2026-06-05T06:53:56+02:00
type: module
function_count: 8
---


# delivery_edit_requests

> Source: `includes/delivery_edit_requests.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/online_order_parcels.php`

## deliveries_find_existing_recordings {#deliveries_find_existing_recordings}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `api/v1/deliveries.php`:135 — `$existingRecordings = deliveries_find_existing_recordings($pdo, $tracking, $parcelId);`

## deliveries_get_batch_item_row {#deliveries_get_batch_item_row}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (3):**

- `includes/delivery_edit_requests.php`:62 — `$row = deliveries_get_batch_item_row($pdo, $itemId);`
- `includes/delivery_edit_requests.php`:75 — `$row = deliveries_get_batch_item_row($pdo, $itemId);`
- `includes/delivery_edit_requests.php`:146 — `$row = deliveries_get_batch_item_row($pdo, $itemId);`

## deliveries_void_item_by_id {#deliveries_void_item_by_id}

**Tables:** `delivery_batch_items`

**Callers (2):**

- `api/v1/deliveries.php`:247 — `api_ok(['voided' => deliveries_void_item_by_id($pdo, $itemId)]);`
- `includes/delivery_edit_requests.php`:182 — `if (!deliveries_void_item_by_id($pdo, $itemId)) {`

## deliveries_resolve_item_by_id {#deliveries_resolve_item_by_id}

**Tables:** `delivery_batch_items`

**Callers (2):**

- `api/v1/deliveries.php`:268 — `deliveries_resolve_item_by_id($pdo, $itemId, $tracking);`
- `includes/delivery_edit_requests.php`:190 — `deliveries_resolve_item_by_id($pdo, $itemId, (string) ($req['new_value'] ?? ''));`

## delivery_edit_request_normalize_values {#delivery_edit_request_normalize_values}

**Callers (3):**

- `api/v1/edit_requests.php`:101 — `$normalized = delivery_edit_request_normalize_values(`
- `includes/delivery_edit_requests.php`:155 — `delivery_edit_request_normalize_values($field, $currentTn !== '' ? $currentTn : (string) $row['item_status'], '');`
- `includes/delivery_edit_requests.php`:163 — `delivery_edit_request_normalize_values($field, $currentTn, $newValue);`

## delivery_edit_request_assert_no_duplicate_pending {#delivery_edit_request_assert_no_duplicate_pending}

**Tables:** `edit_requests`

**Callers (1):**

- `includes/delivery_edit_requests.php`:151 — `delivery_edit_request_assert_no_duplicate_pending($pdo, $itemId, $field);`

## delivery_edit_request_validate_submit {#delivery_edit_request_validate_submit}

**Callers (1):**

- `api/v1/edit_requests.php`:99 — `$item = delivery_edit_request_validate_submit($pdo, $recordId, $field, trim($newValue));`

## delivery_edit_request_apply {#delivery_edit_request_apply}

**Callers (1):**

- `api/v1/edit_requests.php`:157 — `delivery_edit_request_apply($pdo, $req);`

