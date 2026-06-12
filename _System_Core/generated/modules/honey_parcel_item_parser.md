---
title: honey_parcel_item_parser
source: includes/honey_parcel_item_parser.php
generated: 2026-06-12T11:00:14+02:00
type: module
function_count: 12
---


# honey_parcel_item_parser

> Source: `includes/honey_parcel_item_parser.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`

## honey_parcel_parse_items_string {#honey_parcel_parse_items_string}

**Callers (5):**

- `api/v1/parcels.php`:116 — `$parsed = honey_parcel_parse_items_string($items);`
- `includes/honey_parcel_inventory.php`:21 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/online_order_parcels.php`:164 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/parcel_pack_costing.php`:181 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/parcel_pack_costing.php`:362 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`

## honey_parcel_parse_segment {#honey_parcel_parse_segment}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:43 — `$part = honey_parcel_parse_segment($segment);`

## honey_parcel_extract_qty {#honey_parcel_extract_qty}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:75 — `$qty = honey_parcel_extract_qty($segment);`

## honey_parcel_detect_other_product {#honey_parcel_detect_other_product}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:93 — `$other = honey_parcel_detect_other_product($segment);`

## honey_parcel_detect_product {#honey_parcel_detect_product}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:111 — `$product = honey_parcel_detect_product($segment);`

## honey_parcel_extract_350_code {#honey_parcel_extract_350_code}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:80 — `$code = honey_parcel_extract_350_code($segment);`

## honey_parcel_resolve_main_code {#honey_parcel_resolve_main_code}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:133 — `$code = honey_parcel_resolve_main_code($segment, $product);`

## honey_parcel_code_to_letters {#honey_parcel_code_to_letters}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:147 — `$letters = honey_parcel_code_to_letters($code, $product);`

## honey_parcel_detect_word_variant {#honey_parcel_detect_word_variant}

**Callers (2):**

- `includes/honey_parcel_item_parser.php`:121 — `$variant = honey_parcel_detect_word_variant($segment);`
- `includes/honey_parcel_item_parser.php`:135 — `$variant = honey_parcel_detect_word_variant($segment);`

## honey_parcel_letter_to_variant {#honey_parcel_letter_to_variant}

**Callers (2):**

- `includes/honey_parcel_item_parser.php`:84 — `$variant = honey_parcel_letter_to_variant($code);`
- `includes/honey_parcel_item_parser.php`:151 — `'variant' => honey_parcel_letter_to_variant($letter),`

## honey_parcel_merge_deltas {#honey_parcel_merge_deltas}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:60 — `'deltas' => honey_parcel_merge_deltas($deltas),`

## honey_parcel_merge_other_items {#honey_parcel_merge_other_items}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:61 — `'other_items' => honey_parcel_merge_other_items($otherItems),`

