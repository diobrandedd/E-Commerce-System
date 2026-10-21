---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-10-21T11:22:12+02:00
type: module
function_count: 43
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
- `includes/honey_sales_report.php`:810 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1003 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1075 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:2372 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:823 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);`
- `includes/honey_sales_report.php`:904 — `[$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');`

## honey_sales_rts_scan_units {#honey_sales_rts_scan_units}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:922 — `$rtsScan = honey_sales_rts_scan_units($pdo, $range);`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (1):**

- `includes/honey_sales_report.php`:878 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_normalize_product_filter {#honey_sales_normalize_product_filter}

**Callers (4):**

- `includes/honey_sales_report.php`:183 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:588 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:822 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:1077 — `$product = honey_sales_normalize_product_filter($product);`

## honey_sales_product_exists_clause {#honey_sales_product_exists_clause}

**Tables:** `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:241 — `[$productSql, $productParams] = honey_sales_product_exists_clause($product);`

## honey_sales_checkout_where_clause {#honey_sales_checkout_where_clause}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:589 — `[$where, $params] = honey_sales_checkout_where_clause($range, $receiptSearch, $saleType, $product);`

## honey_sales_dept_breakdown {#honey_sales_dept_breakdown}

**Tables:** `pos_receipt_items`, `honey_bottle_pack_session_dept_items`, `products`, `pos_sales`, `honey_bottle_pack_sessions`

**Callers (2):**

- `includes/honey_sales_report.php`:931 — `$dept = honey_sales_dept_breakdown($pdo, $range, $channel);`
- `includes/honey_sales_report.php`:932 — `$deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (2):**

- `includes/honey_sales_report.php`:366 — `$sizeLabel = honey_sales_size_label($size);`
- `includes/honey_sales_report.php`:1537 — `$sizeLabel = honey_sales_size_label($bucket === 'liter' ? 'liter' : ($bucket === 'other' ? '' : $bucket));`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:702 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_load_checkout_barcodes {#honey_sales_load_checkout_barcodes}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/honey_sales_report.php`:705 — `$barcodesBySale = honey_sales_load_checkout_barcodes($pdo, $saleIds);`

## honey_sales_attach_barcodes_to_items {#honey_sales_attach_barcodes_to_items}

**Callers (1):**

- `includes/honey_sales_report.php`:707 — `$itemsBySale[$sid] = honey_sales_attach_barcodes_to_items($items, $barcodesBySale[$sid] ?? []);`

## honey_sales_pos_has_sold_by_user {#honey_sales_pos_has_sold_by_user}

**Callers (2):**

- `includes/honey_sales_report.php`:603 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`
- `includes/honey_sales_report.php`:668 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `users`, `pos_pins`, `products`

**Callers (4):**

- `api/v1/pos.php`:559 — `$checkouts = honey_sales_fetch_checkouts(`
- `api/v1/pos.php`:590 — `$found = honey_sales_fetch_checkouts($pdo, null, 1, (string) $receiptNo, 'all', 'all');`
- `includes/honey_sales_report.php`:894 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType, $product);`
- `includes/honey_sales_report.php`:1005 — `$page1 = honey_sales_fetch_checkouts(`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:836 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:849 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:862 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:875 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:102 — `$data = honey_sales_sold_snapshot(`

## honey_sales_fetch_checkouts_export {#honey_sales_fetch_checkouts_export}

**Callers (1):**

- `includes/honey_sales_report.php`:2381 — `$exported = honey_sales_fetch_checkouts_export($pdo, $period, $from, $to, $receipt, $saleType, $product);`

## honey_sales_checkout_rts_flags {#honey_sales_checkout_rts_flags}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:2384 — `$rtsFlags = honey_sales_checkout_rts_flags($pdo, $saleIds);`

## honey_sales_fetch_shipped_export {#honey_sales_fetch_shipped_export}

**Tables:** `honey_sales_log`, `honey_bottle_pack_session_dept_items`, `honey_bottle_units`, `honey_bottle_barcodes`, `online_order_parcels`, `users`, `honey_bottle_pack_sessions`

**Callers (1):**

- `includes/honey_sales_report.php`:2392 — `$shippedRows = honey_sales_fetch_shipped_export($pdo, $period, $from, $to, $product);`

## honey_sales_export_checkout_item_labels {#honey_sales_export_checkout_item_labels}

**Callers (2):**

- `includes/honey_sales_report.php`:1434 — `$labels = explode('; ', honey_sales_export_checkout_item_labels($items));`
- `includes/honey_sales_report.php`:2333 — `$sheet->setCellValue('E' . $r, honey_sales_export_checkout_item_labels($items));`

## honey_sales_export_checkout_item_qtys {#honey_sales_export_checkout_item_qtys}

**Callers (2):**

- `includes/honey_sales_report.php`:1435 — `$qtys = explode('; ', honey_sales_export_checkout_item_qtys($items));`
- `includes/honey_sales_report.php`:2334 — `$sheet->setCellValue('F' . $r, honey_sales_export_checkout_item_qtys($items));`

## honey_sales_export_checkout_items_summary {#honey_sales_export_checkout_items_summary}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_sales_export_size_bucket {#honey_sales_export_size_bucket}

**Callers (2):**

- `includes/honey_sales_report.php`:1527 — `$bucket = honey_sales_export_size_bucket(`
- `includes/honey_sales_report.php`:1607 — `$bucket = $size === 'dept' ? 'dept' : honey_sales_export_size_bucket((string) $size);`

## honey_sales_export_summarize_walk_in {#honey_sales_export_summarize_walk_in}

**Callers (3):**

- `includes/honey_sales_report.php`:2294 — `$summary = honey_sales_export_summarize_walk_in($rows, $rtsFlags);`
- `includes/honey_sales_report.php`:2466 — `$wlkSum = honey_sales_export_summarize_walk_in($walkInRows, $rtsFlags);`
- `includes/honey_sales_report.php`:2494 — `$wlkSum = honey_sales_export_summarize_walk_in($wlkRows, $rtsFlags);`

## honey_sales_export_summarize_shipped {#honey_sales_export_summarize_shipped}

**Callers (3):**

- `includes/honey_sales_report.php`:2235 — `$summary = honey_sales_export_summarize_shipped($rows);`
- `includes/honey_sales_report.php`:2465 — `$shpSum = honey_sales_export_summarize_shipped($shippedRows);`
- `includes/honey_sales_report.php`:2493 — `$shpSum = honey_sales_export_summarize_shipped($shpRows);`

## honey_sales_export_write_summary_block {#honey_sales_export_write_summary_block}

**Callers (2):**

- `includes/honey_sales_report.php`:2237 — `$detailStart = honey_sales_export_write_summary_block(`
- `includes/honey_sales_report.php`:2296 — `$detailStart = honey_sales_export_write_summary_block(`

## honey_sales_export_chunk_rows {#honey_sales_export_chunk_rows}

**Callers (2):**

- `includes/honey_sales_report.php`:2578 — `foreach (honey_sales_export_chunk_rows($shpRows) as $i => $chunk) {`
- `includes/honey_sales_report.php`:2604 — `foreach (honey_sales_export_chunk_rows($wlkRows) as $i => $chunk) {`

## honey_sales_export_month_code {#honey_sales_export_month_code}

**Callers (5):**

- `includes/honey_sales_report.php`:1801 — `$label = honey_sales_export_month_code($sm) . ' ' . $sd . '-' . $ed;`
- `includes/honey_sales_report.php`:1803 — `$label = honey_sales_export_month_code($sm) . ' ' . $sd . '-'`
- `includes/honey_sales_report.php`:1804 — `. honey_sales_export_month_code($em) . ' ' . $ed;`
- `includes/honey_sales_report.php`:1862 — `$label = honey_sales_export_month_code((int) date('n', $ts)) . ' ' . (int) date('j', $ts);`
- `includes/honey_sales_report.php`:1993 — `$code = honey_sales_export_month_code($month);`

## honey_sales_export_week_meta {#honey_sales_export_week_meta}

**Callers (1):**

- `includes/honey_sales_report.php`:1939 — `$meta = honey_sales_export_week_meta($when);`

## honey_sales_export_unique_sheet_title {#honey_sales_export_unique_sheet_title}

**Callers (4):**

- `includes/honey_sales_report.php`:2542 — `$title = honey_sales_export_unique_sheet_title('No data', 'shp', 1, false, '', $usedTitles);`
- `includes/honey_sales_report.php`:2554 — `$title = honey_sales_export_unique_sheet_title('No data', 'wlk', 1, false, '', $usedTitles);`
- `includes/honey_sales_report.php`:2581 — `$title = honey_sales_export_unique_sheet_title(`
- `includes/honey_sales_report.php`:2607 — `$title = honey_sales_export_unique_sheet_title(`

## honey_sales_export_day_meta {#honey_sales_export_day_meta}

**Callers (1):**

- `includes/honey_sales_report.php`:1887 — `$meta = honey_sales_export_day_meta($when);`

## honey_sales_export_group_by_day {#honey_sales_export_group_by_day}

**Callers (2):**

- `includes/honey_sales_report.php`:2397 — `$shippedWeeks = honey_sales_export_group_by_day($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2398 — `$walkInWeeks = honey_sales_export_group_by_day($walkInRows, 'sold_at');`

## honey_sales_export_is_single_day {#honey_sales_export_is_single_day}

**Callers (1):**

- `includes/honey_sales_report.php`:2395 — `$singleDay = honey_sales_export_is_single_day($range);`

## honey_sales_export_group_by_week {#honey_sales_export_group_by_week}

**Callers (2):**

- `includes/honey_sales_report.php`:2400 — `$shippedWeeks = honey_sales_export_group_by_week($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2401 — `$walkInWeeks = honey_sales_export_group_by_week($walkInRows, 'sold_at');`

## honey_sales_export_month_name {#honey_sales_export_month_name}

**Callers (1):**

- `includes/honey_sales_report.php`:1994 — `$name = honey_sales_export_month_name($month);`

## honey_sales_export_group_by_month {#honey_sales_export_group_by_month}

**Callers (2):**

- `includes/honey_sales_report.php`:2403 — `$shippedMonths = honey_sales_export_group_by_month($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2404 — `$walkInMonths = honey_sales_export_group_by_month($walkInRows, 'sold_at');`

## honey_sales_export_write_channel_breakdown_block {#honey_sales_export_write_channel_breakdown_block}

**Callers (2):**

- `includes/honey_sales_report.php`:2174 — `$r = honey_sales_export_write_channel_breakdown_block(`
- `includes/honey_sales_report.php`:2181 — `$r = honey_sales_export_write_channel_breakdown_block(`

## honey_sales_export_use_per_month_summaries {#honey_sales_export_use_per_month_summaries}

**Callers (1):**

- `includes/honey_sales_report.php`:2452 — `$splitMonthSummaries = honey_sales_export_use_per_month_summaries($range, $monthOrder);`

## honey_sales_export_write_period_summary_sheet {#honey_sales_export_write_period_summary_sheet}

**Callers (2):**

- `includes/honey_sales_report.php`:2209 — `honey_sales_export_write_period_summary_sheet(`
- `includes/honey_sales_report.php`:2472 — `honey_sales_export_write_period_summary_sheet(`

## honey_sales_export_write_month_summary_sheet {#honey_sales_export_write_month_summary_sheet}

**Callers (1):**

- `includes/honey_sales_report.php`:2520 — `honey_sales_export_write_month_summary_sheet(`

## honey_sales_export_write_shipped_detail {#honey_sales_export_write_shipped_detail}

**Callers (2):**

- `includes/honey_sales_report.php`:2544 — `honey_sales_export_write_shipped_detail(`
- `includes/honey_sales_report.php`:2592 — `honey_sales_export_write_shipped_detail(`

## honey_sales_export_write_walk_in_detail {#honey_sales_export_write_walk_in_detail}

**Callers (2):**

- `includes/honey_sales_report.php`:2556 — `honey_sales_export_write_walk_in_detail(`
- `includes/honey_sales_report.php`:2618 — `honey_sales_export_write_walk_in_detail(`

## honey_sales_build_export_xlsx {#honey_sales_build_export_xlsx}

**Callers (1):**

- `api/v1/sales.php`:136 — `$path = honey_sales_build_export_xlsx(ims_pdo(), $f, $canViewAmounts);`

