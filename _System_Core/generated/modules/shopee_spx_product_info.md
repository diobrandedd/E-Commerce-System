---
title: shopee_spx_product_info
source: includes/shopee_spx_product_info.php
generated: 2026-09-01T04:04:37+02:00
type: module
function_count: 12
---


# shopee_spx_product_info

> Source: `includes/shopee_spx_product_info.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## shopee_spx_parse_product_info_fields {#shopee_spx_parse_product_info_fields}

**Callers (1):**

- `includes/shopee_spx_product_info.php`:230 — `$fields = shopee_spx_parse_product_info_fields($raw);`

## shopee_spx_extract_honey_variant {#shopee_spx_extract_honey_variant}

**Callers (2):**

- `includes/shopee_spx_product_info.php`:238 — `$variant = shopee_spx_extract_honey_variant($variationName, $productName);`
- `includes/shopee_spx_product_info.php`:298 — `$variant = shopee_spx_extract_honey_variant($variationName, $productName);`

## shopee_spx_product_is_gallon {#shopee_spx_product_is_gallon}

**Callers (1):**

- `includes/shopee_spx_product_info.php`:237 — `if (shopee_spx_product_is_gallon($productName, $variationName)) {`

## shopee_spx_format_gallon_items {#shopee_spx_format_gallon_items}

**Callers (2):**

- `includes/shopee_spx_product_info.php`:241 — `'items' => shopee_spx_format_gallon_items($packQty['count'], $variant),`
- `includes/shopee_spx_product_info.php`:326 — `'items' => shopee_spx_format_gallon_items($qty, $variant),`

## shopee_spx_packer_qty_from_name {#shopee_spx_packer_qty_from_name}

**Callers (2):**

- `includes/shopee_spx_product_info.php`:112 — `$pack = shopee_spx_packer_qty_from_name($productName, $shopeeQty);`
- `includes/shopee_spx_product_info.php`:234 — `$packQty = shopee_spx_packer_qty_from_name($productName, $qty);`

## shopee_spx_order_line_prefix {#shopee_spx_order_line_prefix}

**Callers (1):**

- `includes/shopee_spx_product_info.php`:235 — `$prefix = shopee_spx_order_line_prefix($productName, $qty);`

## shopee_spx_is_b2t1_350ml_promo {#shopee_spx_is_b2t1_350ml_promo}

**Callers (1):**

- `includes/shopee_spx_product_info.php`:290 — `if ($isB2t1 && shopee_spx_is_b2t1_350ml_promo($productName, $variationName)) {`

## shopee_spx_format_b2t1_350ml_items {#shopee_spx_format_b2t1_350ml_items}

**Callers (3):**

- `includes/shopee_spx_product_info.php`:293 — `'items' => shopee_spx_format_b2t1_350ml_items($qty, true),`
- `includes/shopee_spx_product_info.php`:301 — `'items' => shopee_spx_format_b2t1_350ml_items($qty, false, $variant[0]),`
- `includes/shopee_spx_product_info.php`:308 — `'items' => shopee_spx_format_b2t1_350ml_items($qty, false),`

## shopee_spx_split_product_info_blocks {#shopee_spx_split_product_info_blocks}

**Callers (1):**

- `includes/shopee_spx_product_info.php`:183 — `$blocks = shopee_spx_split_product_info_blocks($raw);`

## shopee_spx_build_items_from_product_info {#shopee_spx_build_items_from_product_info}

**Callers (1):**

- `includes/shopee_spx_packing_list.php`:105 — `$built = shopee_spx_build_items_from_product_info($productInfo);`

## shopee_spx_build_items_from_single_product_info {#shopee_spx_build_items_from_single_product_info}

**Callers (2):**

- `includes/shopee_spx_product_info.php`:185 — `return shopee_spx_build_items_from_single_product_info($blocks[0]);`
- `includes/shopee_spx_product_info.php`:192 — `$built = shopee_spx_build_items_from_single_product_info($block);`

## shopee_spx_items_need_variant_resolution {#shopee_spx_items_need_variant_resolution}

**Callers (3):**

- `includes/shopee_spx_product_info.php`:359 — `if (preg_match('/honey/i', $productName) && shopee_spx_items_need_variant_resolution($variationName !== '' ? $variationName : $productName)) {`
- `includes/shopee_spx_product_info.php`:373 — `'complete' => !shopee_spx_items_need_variant_resolution($fallback),`
- `includes/shopee_spx_product_info.php`:374 — `'missing_fields' => shopee_spx_items_need_variant_resolution($fallback) ? ['items_variant'] : [],`

