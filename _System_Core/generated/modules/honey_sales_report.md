---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-09-18T11:28:06+02:00
type: module
function_count: 38
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
- `includes/honey_sales_report.php`:802 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:995 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1067 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:2209 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:815 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);`
- `includes/honey_sales_report.php`:896 — `[$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');`

## honey_sales_rts_scan_units {#honey_sales_rts_scan_units}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:914 — `$rtsScan = honey_sales_rts_scan_units($pdo, $range);`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (1):**

- `includes/honey_sales_report.php`:870 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_normalize_product_filter {#honey_sales_normalize_product_filter}

**Callers (4):**

- `includes/honey_sales_report.php`:177 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:580 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:814 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:1069 — `$product = honey_sales_normalize_product_filter($product);`

## honey_sales_product_exists_clause {#honey_sales_product_exists_clause}

**Tables:** `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:233 — `[$productSql, $productParams] = honey_sales_product_exists_clause($product);`

## honey_sales_checkout_where_clause {#honey_sales_checkout_where_clause}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:581 — `[$where, $params] = honey_sales_checkout_where_clause($range, $receiptSearch, $saleType, $product);`

## honey_sales_dept_breakdown {#honey_sales_dept_breakdown}

**Tables:** `pos_receipt_items`, `honey_bottle_pack_session_dept_items`, `products`, `pos_sales`, `honey_bottle_pack_sessions`

**Callers (2):**

- `includes/honey_sales_report.php`:923 — `$dept = honey_sales_dept_breakdown($pdo, $range, $channel);`
- `includes/honey_sales_report.php`:924 — `$deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (2):**

- `includes/honey_sales_report.php`:358 — `$sizeLabel = honey_sales_size_label($size);`
- `includes/honey_sales_report.php`:1529 — `$sizeLabel = honey_sales_size_label($bucket === 'liter' ? 'liter' : ($bucket === 'other' ? '' : $bucket));`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:694 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_load_checkout_barcodes {#honey_sales_load_checkout_barcodes}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/honey_sales_report.php`:697 — `$barcodesBySale = honey_sales_load_checkout_barcodes($pdo, $saleIds);`

## honey_sales_attach_barcodes_to_items {#honey_sales_attach_barcodes_to_items}

**Callers (1):**

- `includes/honey_sales_report.php`:699 — `$itemsBySale[$sid] = honey_sales_attach_barcodes_to_items($items, $barcodesBySale[$sid] ?? []);`

## honey_sales_pos_has_sold_by_user {#honey_sales_pos_has_sold_by_user}

**Callers (2):**

- `includes/honey_sales_report.php`:595 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`
- `includes/honey_sales_report.php`:660 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `users`, `pos_pins`, `products`

**Callers (4):**

- `api/v1/pos.php`:559 — `$checkouts = honey_sales_fetch_checkouts(`
- `api/v1/pos.php`:590 — `$found = honey_sales_fetch_checkouts($pdo, null, 1, (string) $receiptNo, 'all', 'all');`
- `includes/honey_sales_report.php`:886 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType, $product);`
- `includes/honey_sales_report.php`:997 — `$page1 = honey_sales_fetch_checkouts(`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:828 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:841 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:854 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:867 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:102 — `$data = honey_sales_sold_snapshot(`

## honey_sales_fetch_checkouts_export {#honey_sales_fetch_checkouts_export}

**Callers (1):**

- `includes/honey_sales_report.php`:2218 — `$exported = honey_sales_fetch_checkouts_export($pdo, $period, $from, $to, $receipt, $saleType, $product);`

## honey_sales_checkout_rts_flags {#honey_sales_checkout_rts_flags}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:2221 — `$rtsFlags = honey_sales_checkout_rts_flags($pdo, $saleIds);`

## honey_sales_fetch_shipped_export {#honey_sales_fetch_shipped_export}

**Tables:** `honey_sales_log`, `honey_bottle_pack_session_dept_items`, `honey_bottle_units`, `honey_bottle_barcodes`, `online_order_parcels`, `users`, `honey_bottle_pack_sessions`

**Callers (1):**

- `includes/honey_sales_report.php`:2229 — `$shippedRows = honey_sales_fetch_shipped_export($pdo, $period, $from, $to, $product);`

## honey_sales_export_checkout_item_labels {#honey_sales_export_checkout_item_labels}

**Callers (2):**

- `includes/honey_sales_report.php`:1426 — `$labels = explode('; ', honey_sales_export_checkout_item_labels($items));`
- `includes/honey_sales_report.php`:2170 — `$sheet->setCellValue('E' . $r, honey_sales_export_checkout_item_labels($items));`

## honey_sales_export_checkout_item_qtys {#honey_sales_export_checkout_item_qtys}

**Callers (2):**

- `includes/honey_sales_report.php`:1427 — `$qtys = explode('; ', honey_sales_export_checkout_item_qtys($items));`
- `includes/honey_sales_report.php`:2171 — `$sheet->setCellValue('F' . $r, honey_sales_export_checkout_item_qtys($items));`

## honey_sales_export_checkout_items_summary {#honey_sales_export_checkout_items_summary}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_sales_export_size_bucket {#honey_sales_export_size_bucket}

**Callers (2):**

- `includes/honey_sales_report.php`:1519 — `$bucket = honey_sales_export_size_bucket(`
- `includes/honey_sales_report.php`:1599 — `$bucket = $size === 'dept' ? 'dept' : honey_sales_export_size_bucket((string) $size);`

## honey_sales_export_summarize_walk_in {#honey_sales_export_summarize_walk_in}

**Callers (1):**

- `includes/honey_sales_report.php`:2305 — `$wlkSum = honey_sales_export_summarize_walk_in($wlkRows, $rtsFlags);`

## honey_sales_export_summarize_shipped {#honey_sales_export_summarize_shipped}

**Callers (1):**

- `includes/honey_sales_report.php`:2304 — `$shpSum = honey_sales_export_summarize_shipped($shpRows);`

## honey_sales_export_write_summary_block {#honey_sales_export_write_summary_block}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_sales_export_chunk_rows {#honey_sales_export_chunk_rows}

**Callers (2):**

- `includes/honey_sales_report.php`:2377 — `foreach (honey_sales_export_chunk_rows($shpRows) as $i => $chunk) {`
- `includes/honey_sales_report.php`:2398 — `foreach (honey_sales_export_chunk_rows($wlkRows) as $i => $chunk) {`

## honey_sales_export_month_code {#honey_sales_export_month_code}

**Callers (4):**

- `includes/honey_sales_report.php`:1793 — `$label = honey_sales_export_month_code($sm) . ' ' . $sd . '-' . $ed;`
- `includes/honey_sales_report.php`:1795 — `$label = honey_sales_export_month_code($sm) . ' ' . $sd . '-'`
- `includes/honey_sales_report.php`:1796 — `. honey_sales_export_month_code($em) . ' ' . $ed;`
- `includes/honey_sales_report.php`:1909 — `$code = honey_sales_export_month_code($month);`

## honey_sales_export_week_meta {#honey_sales_export_week_meta}

**Callers (1):**

- `includes/honey_sales_report.php`:1855 — `$meta = honey_sales_export_week_meta($when);`

## honey_sales_export_unique_sheet_title {#honey_sales_export_unique_sheet_title}

**Callers (4):**

- `includes/honey_sales_report.php`:2353 — `$title = honey_sales_export_unique_sheet_title('No data', 'shp', 1, false, '', $usedTitles);`
- `includes/honey_sales_report.php`:2359 — `$title = honey_sales_export_unique_sheet_title('No data', 'wlk', 1, false, '', $usedTitles);`
- `includes/honey_sales_report.php`:2380 — `$title = honey_sales_export_unique_sheet_title(`
- `includes/honey_sales_report.php`:2401 — `$title = honey_sales_export_unique_sheet_title(`

## honey_sales_export_group_by_week {#honey_sales_export_group_by_week}

**Callers (2):**

- `includes/honey_sales_report.php`:2232 — `$shippedWeeks = honey_sales_export_group_by_week($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2233 — `$walkInWeeks = honey_sales_export_group_by_week($walkInRows, 'sold_at');`

## honey_sales_export_month_name {#honey_sales_export_month_name}

**Callers (1):**

- `includes/honey_sales_report.php`:1910 — `$name = honey_sales_export_month_name($month);`

## honey_sales_export_group_by_month {#honey_sales_export_group_by_month}

**Callers (2):**

- `includes/honey_sales_report.php`:2234 — `$shippedMonths = honey_sales_export_group_by_month($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2235 — `$walkInMonths = honey_sales_export_group_by_month($walkInRows, 'sold_at');`

## honey_sales_export_write_channel_breakdown_block {#honey_sales_export_write_channel_breakdown_block}

**Callers (2):**

- `includes/honey_sales_report.php`:2061 — `$r = honey_sales_export_write_channel_breakdown_block(`
- `includes/honey_sales_report.php`:2068 — `$r = honey_sales_export_write_channel_breakdown_block(`

## honey_sales_export_write_month_summary_sheet {#honey_sales_export_write_month_summary_sheet}

**Callers (1):**

- `includes/honey_sales_report.php`:2331 — `honey_sales_export_write_month_summary_sheet(`

## honey_sales_export_write_shipped_detail {#honey_sales_export_write_shipped_detail}

**Callers (2):**

- `includes/honey_sales_report.php`:2355 — `honey_sales_export_write_shipped_detail($sheet, [], 'Shipped / pack-scan', $shippedEmpty);`
- `includes/honey_sales_report.php`:2392 — `honey_sales_export_write_shipped_detail($sheet, $chunk, $partLabel, $shippedEmpty);`

## honey_sales_export_write_walk_in_detail {#honey_sales_export_write_walk_in_detail}

**Callers (2):**

- `includes/honey_sales_report.php`:2361 — `honey_sales_export_write_walk_in_detail(`
- `includes/honey_sales_report.php`:2413 — `honey_sales_export_write_walk_in_detail(`

## honey_sales_build_export_xlsx {#honey_sales_build_export_xlsx}

**Callers (1):**

- `api/v1/sales.php`:136 — `$path = honey_sales_build_export_xlsx(ims_pdo(), $f, $canViewAmounts);`

