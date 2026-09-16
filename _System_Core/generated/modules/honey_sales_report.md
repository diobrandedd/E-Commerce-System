---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-09-16T03:59:51+02:00
type: module
function_count: 25
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
- `includes/honey_sales_report.php`:766 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:959 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1031 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1656 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:779 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);`
- `includes/honey_sales_report.php`:860 — `[$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');`

## honey_sales_rts_scan_units {#honey_sales_rts_scan_units}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:878 — `$rtsScan = honey_sales_rts_scan_units($pdo, $range);`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (1):**

- `includes/honey_sales_report.php`:834 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_normalize_product_filter {#honey_sales_normalize_product_filter}

**Callers (4):**

- `includes/honey_sales_report.php`:168 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:544 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:778 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:1033 — `$product = honey_sales_normalize_product_filter($product);`

## honey_sales_product_exists_clause {#honey_sales_product_exists_clause}

**Tables:** `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:224 — `[$productSql, $productParams] = honey_sales_product_exists_clause($product);`

## honey_sales_checkout_where_clause {#honey_sales_checkout_where_clause}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:545 — `[$where, $params] = honey_sales_checkout_where_clause($range, $receiptSearch, $saleType, $product);`

## honey_sales_dept_breakdown {#honey_sales_dept_breakdown}

**Tables:** `pos_receipt_items`, `products`, `pos_sales`

**Callers (2):**

- `includes/honey_sales_report.php`:887 — `$dept = honey_sales_dept_breakdown($pdo, $range, $channel);`
- `includes/honey_sales_report.php`:888 — `$deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (2):**

- `includes/honey_sales_report.php`:322 — `$sizeLabel = honey_sales_size_label($size);`
- `includes/honey_sales_report.php`:1384 — `$sizeLabel = honey_sales_size_label($bucket === 'liter' ? 'liter' : ($bucket === 'other' ? '' : $bucket));`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:658 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_load_checkout_barcodes {#honey_sales_load_checkout_barcodes}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/honey_sales_report.php`:661 — `$barcodesBySale = honey_sales_load_checkout_barcodes($pdo, $saleIds);`

## honey_sales_attach_barcodes_to_items {#honey_sales_attach_barcodes_to_items}

**Callers (1):**

- `includes/honey_sales_report.php`:663 — `$itemsBySale[$sid] = honey_sales_attach_barcodes_to_items($items, $barcodesBySale[$sid] ?? []);`

## honey_sales_pos_has_sold_by_user {#honey_sales_pos_has_sold_by_user}

**Callers (2):**

- `includes/honey_sales_report.php`:559 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`
- `includes/honey_sales_report.php`:624 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `users`, `pos_pins`, `products`

**Callers (4):**

- `api/v1/pos.php`:559 — `$checkouts = honey_sales_fetch_checkouts(`
- `api/v1/pos.php`:590 — `$found = honey_sales_fetch_checkouts($pdo, null, 1, (string) $receiptNo, 'all', 'all');`
- `includes/honey_sales_report.php`:850 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType, $product);`
- `includes/honey_sales_report.php`:961 — `$page1 = honey_sales_fetch_checkouts(`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:792 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:805 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:818 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:831 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:102 — `$data = honey_sales_sold_snapshot(`

## honey_sales_fetch_checkouts_export {#honey_sales_fetch_checkouts_export}

**Callers (1):**

- `includes/honey_sales_report.php`:1665 — `$exported = honey_sales_fetch_checkouts_export($pdo, $period, $from, $to, $receipt, $saleType, $product);`

## honey_sales_checkout_rts_flags {#honey_sales_checkout_rts_flags}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:1668 — `$rtsFlags = honey_sales_checkout_rts_flags($pdo, $saleIds);`

## honey_sales_fetch_shipped_export {#honey_sales_fetch_shipped_export}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`, `online_order_parcels`, `users`, `honey_bottle_pack_sessions`

**Callers (1):**

- `includes/honey_sales_report.php`:1676 — `$shippedRows = honey_sales_fetch_shipped_export($pdo, $period, $from, $to, $product);`

## honey_sales_export_checkout_items_summary {#honey_sales_export_checkout_items_summary}

**Callers (1):**

- `includes/honey_sales_report.php`:1764 — `$walkIn->setCellValue('E' . $r, honey_sales_export_checkout_items_summary(`

## honey_sales_export_size_bucket {#honey_sales_export_size_bucket}

**Callers (2):**

- `includes/honey_sales_report.php`:1374 — `$bucket = honey_sales_export_size_bucket(`
- `includes/honey_sales_report.php`:1454 — `$bucket = $size === 'dept' ? 'dept' : honey_sales_export_size_bucket((string) $size);`

## honey_sales_export_summarize_walk_in {#honey_sales_export_summarize_walk_in}

**Callers (1):**

- `includes/honey_sales_report.php`:1679 — `$walkInSummary = honey_sales_export_summarize_walk_in($walkInRows, $rtsFlags);`

## honey_sales_export_summarize_shipped {#honey_sales_export_summarize_shipped}

**Callers (1):**

- `includes/honey_sales_report.php`:1680 — `$shippedSummary = honey_sales_export_summarize_shipped($shippedRows);`

## honey_sales_export_write_summary_block {#honey_sales_export_write_summary_block}

**Callers (2):**

- `includes/honey_sales_report.php`:1687 — `$nextRow = honey_sales_export_write_summary_block(`
- `includes/honey_sales_report.php`:1729 — `$walkNext = honey_sales_export_write_summary_block(`

## honey_sales_build_export_xlsx {#honey_sales_build_export_xlsx}

**Callers (1):**

- `api/v1/sales.php`:136 — `$path = honey_sales_build_export_xlsx(ims_pdo(), $f, $canViewAmounts);`

