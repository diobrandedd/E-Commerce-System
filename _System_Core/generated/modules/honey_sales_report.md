---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-09-15T10:42:39+02:00
type: module
function_count: 24
---


# honey_sales_report

> Source: `includes/honey_sales_report.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dashboard_inventory.php`
- `includes/dept_products.php`
- `includes/rts_schema.php`
- `includes/honey_pos.php`

## honey_sales_resolve_filters {#honey_sales_resolve_filters}

**Callers (5):**

- `api/v1/pos.php`:558 — `$filters = honey_sales_resolve_filters($period !== '' ? $period : 'day', $from, $to);`
- `includes/honey_sales_report.php`:698 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:896 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:968 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1399 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:711 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);`
- `includes/honey_sales_report.php`:792 — `[$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (1):**

- `includes/honey_sales_report.php`:766 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_normalize_product_filter {#honey_sales_normalize_product_filter}

**Callers (4):**

- `includes/honey_sales_report.php`:100 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:476 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:710 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:970 — `$product = honey_sales_normalize_product_filter($product);`

## honey_sales_product_exists_clause {#honey_sales_product_exists_clause}

**Tables:** `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:156 — `[$productSql, $productParams] = honey_sales_product_exists_clause($product);`

## honey_sales_checkout_where_clause {#honey_sales_checkout_where_clause}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:477 — `[$where, $params] = honey_sales_checkout_where_clause($range, $receiptSearch, $saleType, $product);`

## honey_sales_dept_breakdown {#honey_sales_dept_breakdown}

**Tables:** `pos_receipt_items`, `products`, `pos_sales`

**Callers (2):**

- `includes/honey_sales_report.php`:812 — `$dept = honey_sales_dept_breakdown($pdo, $range, $channel);`
- `includes/honey_sales_report.php`:813 — `$deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (1):**

- `includes/honey_sales_report.php`:254 — `$sizeLabel = honey_sales_size_label($size);`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:590 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_load_checkout_barcodes {#honey_sales_load_checkout_barcodes}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/honey_sales_report.php`:593 — `$barcodesBySale = honey_sales_load_checkout_barcodes($pdo, $saleIds);`

## honey_sales_attach_barcodes_to_items {#honey_sales_attach_barcodes_to_items}

**Callers (1):**

- `includes/honey_sales_report.php`:595 — `$itemsBySale[$sid] = honey_sales_attach_barcodes_to_items($items, $barcodesBySale[$sid] ?? []);`

## honey_sales_pos_has_sold_by_user {#honey_sales_pos_has_sold_by_user}

**Callers (2):**

- `includes/honey_sales_report.php`:491 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`
- `includes/honey_sales_report.php`:556 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `users`, `pos_pins`, `products`

**Callers (4):**

- `api/v1/pos.php`:559 — `$checkouts = honey_sales_fetch_checkouts(`
- `api/v1/pos.php`:590 — `$found = honey_sales_fetch_checkouts($pdo, null, 1, (string) $receiptNo, 'all', 'all');`
- `includes/honey_sales_report.php`:782 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType, $product);`
- `includes/honey_sales_report.php`:898 — `$page1 = honey_sales_fetch_checkouts(`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:724 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:737 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:750 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:763 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:102 — `$data = honey_sales_sold_snapshot(`

## honey_sales_fetch_checkouts_export {#honey_sales_fetch_checkouts_export}

**Callers (1):**

- `includes/honey_sales_report.php`:1408 — `$exported = honey_sales_fetch_checkouts_export($pdo, $period, $from, $to, $receipt, $saleType, $product);`

## honey_sales_checkout_rts_flags {#honey_sales_checkout_rts_flags}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:1411 — `$rtsFlags = honey_sales_checkout_rts_flags($pdo, $saleIds);`

## honey_sales_fetch_shipped_export {#honey_sales_fetch_shipped_export}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`, `online_order_parcels`

**Callers (1):**

- `includes/honey_sales_report.php`:1419 — `$shippedRows = honey_sales_fetch_shipped_export($pdo, $period, $from, $to, $product);`

## honey_sales_export_checkout_items_summary {#honey_sales_export_checkout_items_summary}

**Callers (1):**

- `includes/honey_sales_report.php`:1504 — `$walkIn->setCellValue('E' . $r, honey_sales_export_checkout_items_summary(`

## honey_sales_export_size_bucket {#honey_sales_export_size_bucket}

**Callers (2):**

- `includes/honey_sales_report.php`:1239 — `$bucket = honey_sales_export_size_bucket(`
- `includes/honey_sales_report.php`:1291 — `$bucket = $size === 'dept' ? 'dept' : honey_sales_export_size_bucket((string) $size);`

## honey_sales_export_summarize_walk_in {#honey_sales_export_summarize_walk_in}

**Callers (1):**

- `includes/honey_sales_report.php`:1422 — `$walkInSummary = honey_sales_export_summarize_walk_in($walkInRows, $rtsFlags);`

## honey_sales_export_summarize_shipped {#honey_sales_export_summarize_shipped}

**Callers (1):**

- `includes/honey_sales_report.php`:1423 — `$shippedSummary = honey_sales_export_summarize_shipped($shippedRows);`

## honey_sales_export_write_summary_block {#honey_sales_export_write_summary_block}

**Callers (2):**

- `includes/honey_sales_report.php`:1430 — `$nextRow = honey_sales_export_write_summary_block(`
- `includes/honey_sales_report.php`:1469 — `$walkNext = honey_sales_export_write_summary_block(`

## honey_sales_build_export_xlsx {#honey_sales_build_export_xlsx}

**Callers (1):**

- `api/v1/sales.php`:136 — `$path = honey_sales_build_export_xlsx(ims_pdo(), $f, $canViewAmounts);`

