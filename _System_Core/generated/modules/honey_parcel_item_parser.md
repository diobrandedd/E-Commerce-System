---
title: honey_parcel_item_parser
source: includes/honey_parcel_item_parser.php
generated: 2026-06-05T10:39:40+02:00
type: module
function_count: 12
---


# honey_parcel_item_parser

> Source: `includes/honey_parcel_item_parser.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## honey_parcel_parse_items_string {#honey_parcel_parse_items_string}

**Callers (3):**

- `api/v1/parcels.php`:116 — `$parsed = honey_parcel_parse_items_string($items);`
- `includes/honey_parcel_inventory.php`:16 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`
- `includes/online_order_parcels.php`:151 — `$parsed = honey_parcel_parse_items_string($itemsRaw);`

## honey_parcel_parse_segment {#honey_parcel_parse_segment}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:41 — `$part = honey_parcel_parse_segment($segment);`

## honey_parcel_extract_qty {#honey_parcel_extract_qty}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:73 — `$qty = honey_parcel_extract_qty($segment);`

## honey_parcel_detect_other_product {#honey_parcel_detect_other_product}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:91 — `$otherLabel = honey_parcel_detect_other_product($segment);`

## honey_parcel_detect_product {#honey_parcel_detect_product}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:100 — `$product = honey_parcel_detect_product($segment);`

## honey_parcel_extract_350_code {#honey_parcel_extract_350_code}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:78 — `$code = honey_parcel_extract_350_code($segment);`

## honey_parcel_resolve_main_code {#honey_parcel_resolve_main_code}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:122 — `$code = honey_parcel_resolve_main_code($segment, $product);`

## honey_parcel_code_to_letters {#honey_parcel_code_to_letters}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:136 — `$letters = honey_parcel_code_to_letters($code, $product);`

## honey_parcel_detect_word_variant {#honey_parcel_detect_word_variant}

**Callers (2):**

- `includes/honey_parcel_item_parser.php`:110 — `$variant = honey_parcel_detect_word_variant($segment);`
- `includes/honey_parcel_item_parser.php`:124 — `$variant = honey_parcel_detect_word_variant($segment);`

## honey_parcel_letter_to_variant {#honey_parcel_letter_to_variant}

**Callers (2):**

- `includes/honey_parcel_item_parser.php`:82 — `$variant = honey_parcel_letter_to_variant($code);`
- `includes/honey_parcel_item_parser.php`:140 — `'variant' => honey_parcel_letter_to_variant($letter),`

## honey_parcel_merge_deltas {#honey_parcel_merge_deltas}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:58 — `'deltas' => honey_parcel_merge_deltas($deltas),`

## honey_parcel_merge_other_items {#honey_parcel_merge_other_items}

**Callers (1):**

- `includes/honey_parcel_item_parser.php`:59 — `'other_items' => honey_parcel_merge_other_items($otherItems),`

