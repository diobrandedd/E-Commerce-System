---
title: rts_prefill
source: includes/rts_prefill.php
generated: 2026-06-23T11:18:03+02:00
type: module
function_count: 8
---


# rts_prefill

> Source: `includes/rts_prefill.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/courier_detect.php`
- `includes/honey_parcel_item_parser.php`
- `includes/online_order_parcels.php`
- `includes/rts_products.php`
- `includes/rts_schema.php`
- `includes/rts_storage.php`

## rts_case_allowed_dispositions {#rts_case_allowed_dispositions}

**Callers (1):**

- `includes/rts_case_handlers.php`:42 — `if (in_array($d, rts_case_allowed_dispositions(), true)) {`

## rts_case_disposition_label {#rts_case_disposition_label}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## rts_pipeline_to_shop {#rts_pipeline_to_shop}

**Callers (2):**

- `includes/rts_prefill.php`:214 — `$shop = rts_pipeline_to_shop($pipeline);`
- `includes/rts_prefill.php`:246 — `$shop = rts_pipeline_to_shop((string) ($csrRow['source_pipeline'] ?? ''));`

## rts_parcel_courier_to_final {#rts_parcel_courier_to_final}

**Callers (2):**

- `includes/rts_prefill.php`:220 — `$courier = rts_parcel_courier_to_final((string) ($parcel['courier'] ?? ''), $normalized);`
- `includes/rts_prefill.php`:254 — `$courier = rts_parcel_courier_to_final((string) ($csrRow['courier'] ?? ''), $normalized);`

## rts_dept_key_to_product_type {#rts_dept_key_to_product_type}

**Callers (1):**

- `includes/rts_prefill.php`:129 — `$pt = $key !== '' ? rts_dept_key_to_product_type($key) : null;`

## rts_items_raw_to_line_suggestions {#rts_items_raw_to_line_suggestions}

**Callers (1):**

- `includes/rts_prefill.php`:271 — `$mapped = rts_items_raw_to_line_suggestions($itemsRaw);`

## rts_prefill_delivery_lookup {#rts_prefill_delivery_lookup}

**Tables:** `delivery_batch_items`, `delivery_batches`

**Callers (3):**

- `includes/rts_case_handlers.php`:120 — `$match = rts_prefill_delivery_lookup($pdo, (string) $caseRow['tracking_number']);`
- `includes/rts_case_handlers.php`:355 — `$deliveryLookup = rts_prefill_delivery_lookup($pdo, $tracking);`
- `includes/rts_prefill.php`:280 — `$delivery = rts_prefill_delivery_lookup($pdo, $normalized);`

## rts_case_handler_tracking_prefill {#rts_case_handler_tracking_prefill}

**Tables:** `csr_online_order_lines`, `csr_online_batches`

**Callers (1):**

- `includes/rts_case_handlers.php`:774 — `return rts_case_handler_tracking_prefill($pdo, $tracking);`

