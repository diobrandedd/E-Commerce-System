---
title: shopee_spx_product_info
source: includes/shopee_spx_product_info.php
generated: 2026-06-05T06:54:02+02:00
type: module
function_count: 3
---


# shopee_spx_product_info

> Source: `includes/shopee_spx_product_info.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## shopee_spx_parse_product_info_fields {#shopee_spx_parse_product_info_fields}

**Callers (1):**

- `includes/shopee_spx_product_info.php`:45 — `$fields = shopee_spx_parse_product_info_fields($raw);`

## shopee_spx_build_items_from_product_info {#shopee_spx_build_items_from_product_info}

**Callers (1):**

- `includes/shopee_spx_packing_list.php`:97 — `$built = shopee_spx_build_items_from_product_info($productInfo);`

## shopee_spx_items_need_variant_resolution {#shopee_spx_items_need_variant_resolution}

**Callers (3):**

- `includes/shopee_spx_product_info.php`:108 — `if (preg_match('/honey/i', $productName) && shopee_spx_items_need_variant_resolution($variationName !== '' ? $variationName : $productName)) {`
- `includes/shopee_spx_product_info.php`:122 — `'complete' => !shopee_spx_items_need_variant_resolution($fallback),`
- `includes/shopee_spx_product_info.php`:123 — `'missing_fields' => shopee_spx_items_need_variant_resolution($fallback) ? ['items_variant'] : [],`

