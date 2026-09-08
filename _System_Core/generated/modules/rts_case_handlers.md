---
title: rts_case_handlers
source: includes/rts_case_handlers.php
generated: 2026-09-08T07:51:16+02:00
type: module
function_count: 28
---


# rts_case_handlers

> Source: `includes/rts_case_handlers.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/courier_detect.php`
- `includes/rts_attachments.php`
- `includes/rts_shop_couriers.php`
- `includes/rts_claim_status.php`
- `includes/rts_products.php`
- `includes/rts_schema.php`
- `includes/rts_storage.php`
- `includes/rts_prefill.php`
- `includes/online_order_parcels.php`
- `includes/rts_inventory_sync.php`
- `includes/tracking_search.php`
- `includes/honey_bottle_barcodes.php`
- `includes/dept_pouch_barcodes.php`
- `includes/rts_inventory_sync.php`

## __construct {#__construct}

**Callers (2):**

- `includes/online_orders_generator.php`:349 — `parent::__construct($message ?? 'One or more tracking numbers already exist.');`
- `includes/rts_case_handlers.php`:25 — `parent::__construct($message);`

## rts_case_allowed_couriers {#rts_case_allowed_couriers}

**Callers (3):**

- `includes/rts_case_handlers.php`:349 — `if (!in_array($courierFinal, rts_case_allowed_couriers(), true)) {`
- `includes/rts_case_handlers.php`:770 — `if ($courier !== '' && in_array($courier, rts_case_allowed_couriers(), true)) {`
- `includes/rts_prefill.php`:60 — `if ($detected !== 'unknown' && in_array($detected, rts_case_allowed_couriers(), true)) {`

## rts_case_allowed_shops {#rts_case_allowed_shops}

**Callers (3):**

- `includes/rts_case_handlers.php`:354 — `if (!in_array($shop, rts_case_allowed_shops(), true)) {`
- `includes/rts_case_handlers.php`:766 — `} elseif ($shop !== '' && in_array($shop, rts_case_allowed_shops(), true)) {`
- `includes/rts_case_handlers.php`:1020 — `'shops' => rts_case_allowed_shops(),`

## rts_case_resolve_disposition {#rts_case_resolve_disposition}

**Callers (3):**

- `includes/rts_case_handlers.php`:195 — `$case['case_disposition'] = rts_case_resolve_disposition(['damaged_reason' => $case['damaged_reason'] ?? '']);`
- `includes/rts_case_handlers.php`:342 — `$disposition = rts_case_resolve_disposition($data);`
- `includes/rts_case_handlers.php`:835 — `$row['case_disposition'] = rts_case_resolve_disposition(['damaged_reason' => $row['damaged_reason'] ?? '']);`

## rts_case_disposition_to_damaged_reason {#rts_case_disposition_to_damaged_reason}

**Callers (1):**

- `includes/rts_case_handlers.php`:427 — `$damagedReason = rts_case_disposition_to_damaged_reason($disposition);`

## rts_case_disposition_registry_where {#rts_case_disposition_registry_where}

**Callers (1):**

- `includes/rts_case_handlers.php`:865 — `$registry = rts_case_disposition_registry_where($pdo, $disposition);`

## rts_case_enrich_before_shipment {#rts_case_enrich_before_shipment}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/rts_case_handlers.php`:197 — `$case['before_shipment'] = rts_case_enrich_before_shipment($pdo, $case);`

## rts_case_list_before_shipment_light {#rts_case_list_before_shipment_light}

**Callers (2):**

- `includes/rts_case_handlers.php`:837 — `$row['before_shipment'] = rts_case_list_before_shipment_light($row);`
- `includes/rts_case_handlers.php`:902 — `$row['before_shipment'] = rts_case_list_before_shipment_light($row);`

## rts_case_handler_get_by_tracking {#rts_case_handler_get_by_tracking}

**Callers (2):**

- `api/case.php`:35 — `$result = rts_case_handler_get_by_tracking($pdo, $tracking);`
- `api/v1/rts_cases.php`:90 — `api_ok(rts_case_handler_get_by_tracking(ims_pdo(), $tracking));`

## rts_case_handler_find_delivery_match_item_id {#rts_case_handler_find_delivery_match_item_id}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `includes/rts_case_handlers.php`:432 — `$deliveryMatchItemId = rts_case_handler_find_delivery_match_item_id($pdo, $tracking);`

## rts_case_handler_delivery_match {#rts_case_handler_delivery_match}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (1):**

- `api/v1/rts_cases.php`:258 — `api_ok(rts_case_handler_delivery_match(ims_pdo(), $tracking));`

## rts_case_parse_product_lines {#rts_case_parse_product_lines}

**Callers (1):**

- `includes/rts_case_handlers.php`:343 — `$lines = rts_case_parse_product_lines($disposition, $data);`

## rts_case_handler_parse_case_fields {#rts_case_handler_parse_case_fields}

**Callers (2):**

- `includes/rts_case_handlers.php`:473 — `$fields = rts_case_handler_parse_case_fields($pdo, $data, true);`
- `includes/rts_case_handlers.php`:554 — `$fields = rts_case_handler_parse_case_fields($pdo, $data, false);`

## rts_case_handler_ensure_save_schemas {#rts_case_handler_ensure_save_schemas}

**Callers (3):**

- `includes/rts_case_handlers.php`:475 — `rts_case_handler_ensure_save_schemas($pdo);`
- `includes/rts_case_handlers.php`:556 — `rts_case_handler_ensure_save_schemas($pdo);`
- `includes/rts_case_handlers.php`:648 — `rts_case_handler_ensure_save_schemas($pdo);`

## rts_case_handler_create {#rts_case_handler_create}

**Callers (2):**

- `api/case.php`:86 — `$result = rts_case_handler_create($pdo, $data);`
- `api/v1/rts_cases.php`:107 — `$result = rts_case_handler_create($pdo, $input);`

## rts_case_handler_update {#rts_case_handler_update}

**Tables:** `rts_cases`

**Callers (2):**

- `api/case.php`:81 — `$result = rts_case_handler_update($pdo, (int) ($data['case_id'] ?? 0), $data);`
- `api/v1/rts_cases.php`:146 — `$result = rts_case_handler_update($pdo, (int) ($input['case_id'] ?? 0), $input);`

## rts_case_handler_append_lines {#rts_case_handler_append_lines}

**Tables:** `rts_cases`

**Callers (2):**

- `api/case.php`:91 — `$result = rts_case_handler_append_lines($pdo, $tracking, $data);`
- `api/v1/rts_cases.php`:127 — `$result = rts_case_handler_append_lines($pdo, $tracking, $input);`

## rts_case_handler_void {#rts_case_handler_void}

**Tables:** `rts_cases`

**Callers (2):**

- `api/case.php`:76 — `rts_case_handler_void($pdo, (int) ($data['case_id'] ?? 0), (int) $authUser['id']);`
- `api/v1/rts_cases.php`:164 — `rts_case_handler_void(ims_pdo(), (int) ($input['case_id'] ?? 0), (int) $user['id']);`

## rts_case_handler_update_claim {#rts_case_handler_update_claim}

**Tables:** `rts_cases`

**Callers (2):**

- `api/case.php`:66 — `$result = rts_case_handler_update_claim(`
- `api/v1/rts_cases.php`:181 — `$result = rts_case_handler_update_claim(`

## rts_case_handler_list {#rts_case_handler_list}

**Tables:** `rts_cases`, `rts_line_items`

**Callers (1):**

- `api/v1/rts_cases.php`:198 — `api_ok(rts_case_handler_list(ims_pdo(), $_GET));`

## rts_case_handler_list_by_disposition {#rts_case_handler_list_by_disposition}

**Tables:** `rts_cases`

**Callers (2):**

- `includes/rts_case_handlers.php`:923 — `return rts_case_handler_list_by_disposition($pdo, 'swapped', $filters);`
- `includes/rts_case_handlers.php`:928 — `return rts_case_handler_list_by_disposition($pdo, 'leak', $filters);`

## rts_case_handler_list_swapped {#rts_case_handler_list_swapped}

**Callers (1):**

- `api/v1/rts_cases.php`:209 — `api_ok(rts_case_handler_list_swapped(ims_pdo(), $_GET));`

## rts_case_handler_list_leaks {#rts_case_handler_list_leaks}

**Callers (1):**

- `api/v1/rts_cases.php`:220 — `api_ok(rts_case_handler_list_leaks(ims_pdo(), $_GET));`

## rts_case_handler_tracking_prefill_api {#rts_case_handler_tracking_prefill_api}

**Callers (1):**

- `api/v1/rts_cases.php`:232 — `api_ok(rts_case_handler_tracking_prefill_api(ims_pdo(), $tracking));`

## rts_case_handler_dashboard {#rts_case_handler_dashboard}

**Tables:** `rts_cases`

**Callers (1):**

- `api/v1/rts_cases.php`:242 — `api_ok(rts_case_handler_dashboard(ims_pdo()));`

## rts_case_handler_product_catalog {#rts_case_handler_product_catalog}

**Callers (1):**

- `api/v1/rts_cases.php`:249 — `api_ok(rts_case_handler_product_catalog());`

## rts_case_handler_upload_error_message {#rts_case_handler_upload_error_message}

**Callers (1):**

- `includes/rts_case_handlers.php`:1070 — `throw new RtsCaseException(rts_case_handler_upload_error_message($uploadErr), 'upload_failed', 400);`

## rts_case_handler_upload_documentation {#rts_case_handler_upload_documentation}

**Callers (1):**

- `api/v1/rts_cases.php`:277 — `api_ok(rts_case_handler_upload_documentation(is_array($file) ? $file : null));`

