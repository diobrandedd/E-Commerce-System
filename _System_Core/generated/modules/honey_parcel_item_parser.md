---
title: honey_parcel_item_parser
source: includes/honey_parcel_item_parser.php
generated: 2026-09-04T04:55:43+02:00
type: module
function_count: 17
---


# honey_parcel_item_parser

> Source: `includes/honey_parcel_item_parser.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`

## honey_parcel_split_jnt_concatenated_bulk_ml {#honey_parcel_split_jnt_concatenated_bulk_ml}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:52 — `$jntParts = honey_parcel_split_jnt_concatenated_bulk_ml($part);`

## honey_parcel_split_segments {#honey_parcel_split_segments}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:99 — `$segments = honey_parcel_split_segments($raw);`

## honey_parcel_promo_bottle_count {#honey_parcel_promo_bottle_count}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:284 — `$promo = honey_parcel_promo_bottle_count($segment);`

## honey_parcel_parse_items_string {#honey_parcel_parse_items_string}

**Callers (8):**

- `api/v1/parcels.php`:166 — `$parsed = honey_parcel_parse_items_string($items);`
- `includes/historical_orders_import.php`:325 — `$parsed = honey_parcel_parse_items_string($raw);`
- `includes/honey_parcel_inventory.php`:23 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/online_order_parcels.php`:278 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/online_order_parcels.php`:948 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/parcel_pack_costing.php`:380 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/parcel_pack_costing.php`:638 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/rts_prefill.php`:88 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`

## honey_parcel_parse_segment {#honey_parcel_parse_segment}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:109 — `$part = honey_parcel_parse_segment($segment);`

## honey_parcel_extract_dept_qty {#honey_parcel_extract_dept_qty}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:201 — `$deptQty = honey_parcel_extract_dept_qty($segment);`

## honey_parcel_extract_qty {#honey_parcel_extract_qty}

**Callers (2):**

- `includes/honey_parcel_item_parser.php`:141 — `$qty = honey_parcel_extract_qty($segment);`
- `includes/honey_parcel_item_parser.php`:279 — `return honey_parcel_extract_qty($segment);`

## honey_parcel_detect_other_product {#honey_parcel_detect_other_product}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:199 — `$other = honey_parcel_detect_other_product($segment);`

## honey_parcel_detect_product {#honey_parcel_detect_product}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:218 — `$product = honey_parcel_detect_product($segment);`

## honey_parcel_extract_bulk_ml_code {#honey_parcel_extract_bulk_ml_code}

**Callers (4):**

- `includes/honey_parcel_item_parser.php`:146 — `$code = honey_parcel_extract_bulk_ml_code($segment, '250');`
- `includes/honey_parcel_item_parser.php`:173 — `$code = honey_parcel_extract_bulk_ml_code($segment, '350');`
- `includes/honey_parcel_item_parser.php`:365 — `/** @deprecated Use honey_parcel_extract_bulk_ml_code($segment, '350') */`
- `includes/honey_parcel_item_parser.php`:368 — `return honey_parcel_extract_bulk_ml_code($segment, '350');`

## honey_parcel_extract_350_code {#honey_parcel_extract_350_code}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_parcel_resolve_main_code {#honey_parcel_resolve_main_code}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:240 — `$code = honey_parcel_resolve_main_code($segment, $product);`

## honey_parcel_code_to_letters {#honey_parcel_code_to_letters}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:254 — `$letters = honey_parcel_code_to_letters($code, $product);`

## honey_parcel_detect_word_variant {#honey_parcel_detect_word_variant}

**Callers (2):**

- `includes/honey_parcel_item_parser.php`:228 — `$variant = honey_parcel_detect_word_variant($segment);`
- `includes/honey_parcel_item_parser.php`:242 — `$variant = honey_parcel_detect_word_variant($segment);`

## honey_parcel_letter_to_variant {#honey_parcel_letter_to_variant}

**Callers (3):**

- `includes/honey_parcel_item_parser.php`:155 — `$variant = honey_parcel_letter_to_variant($letter);`
- `includes/honey_parcel_item_parser.php`:182 — `$variant = honey_parcel_letter_to_variant($letter);`
- `includes/honey_parcel_item_parser.php`:258 — `'variant' => honey_parcel_letter_to_variant($letter),`

## honey_parcel_merge_deltas {#honey_parcel_merge_deltas}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:126 — `'deltas' => honey_parcel_merge_deltas($deltas),`

## honey_parcel_merge_other_items {#honey_parcel_merge_other_items}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:127 — `'other_items' => honey_parcel_merge_other_items($otherItems),`

