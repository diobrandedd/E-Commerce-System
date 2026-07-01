---
title: shopee_spx_packing_list
source: includes/shopee_spx_packing_list.php
generated: 2026-07-01T06:18:23+02:00
type: module
function_count: 4
---


# shopee_spx_packing_list

> Source: `includes/shopee_spx_packing_list.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/shopee_spx_product_info.php`
- `includes/online_orders_generator.php`
- `includes/waybill_stamper.php`
- `includes/pdf_to_excel.php`

## shopee_spx_packing_list_parse_paths {#shopee_spx_packing_list_parse_paths}

**Callers (1):**

- `includes/shopee_spx_packing_list.php`:208 — `$rows = shopee_spx_packing_list_parse_paths($excels);`

## shopee_spx_pdf_rows_by_tracking {#shopee_spx_pdf_rows_by_tracking}

**Callers (1):**

- `includes/shopee_spx_packing_list.php`:212 — `$pdfMap = shopee_spx_pdf_rows_by_tracking($pdfs);`

## shopee_spx_merge_pdf_names {#shopee_spx_merge_pdf_names}

**Callers (1):**

- `includes/shopee_spx_packing_list.php`:213 — `$rows = shopee_spx_merge_pdf_names($rows, $pdfMap);`

## online_orders_spx_import_packing_list {#online_orders_spx_import_packing_list}

**Callers (1):**

- `api/v1/online_orders.php`:480 — `$result = online_orders_spx_import_packing_list($excelToken, $pdfTokens);`

