---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-11-17T06:10:31+01:00
type: module
function_count: 45
---


# honey_sales_report

> Source: `includes/honey_sales_report.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dashboard_inventory.php`
- `includes/dept_products.php`
- `includes/rts_schema.php`
- `includes/honey_pos.php`
- `includes/dept_raw_freebies.php`
- `includes/dept_raw_freebies.php`

## honey_sales_resolve_filters {#honey_sales_resolve_filters}

**Callers (5):**

- `api/v1/pos.php`:570 — `$filters = honey_sales_resolve_filters($period !== '' ? $period : 'day', $from, $to);`
- `includes/honey_sales_report.php`:945 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1140 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:1212 — `$filters = honey_sales_resolve_filters($period, $from, $to);`
- `includes/honey_sales_report.php`:2520 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:958 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);`
- `includes/honey_sales_report.php`:1039 — `[$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');`

## honey_sales_rts_scan_units {#honey_sales_rts_scan_units}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:1057 — `$rtsScan = honey_sales_rts_scan_units($pdo, $range);`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (1):**

- `includes/honey_sales_report.php`:1013 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_normalize_product_filter {#honey_sales_normalize_product_filter}

**Callers (4):**

- `includes/honey_sales_report.php`:183 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:714 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:957 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:1214 — `$product = honey_sales_normalize_product_filter($product);`

## honey_sales_product_exists_clause {#honey_sales_product_exists_clause}

**Tables:** `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:241 — `[$productSql, $productParams] = honey_sales_product_exists_clause($product);`

## honey_sales_checkout_where_clause {#honey_sales_checkout_where_clause}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:715 — `[$where, $params] = honey_sales_checkout_where_clause($range, $receiptSearch, $saleType, $product);`

## honey_sales_load_checkout_freebies {#honey_sales_load_checkout_freebies}

**Tables:** `pos_receipt_freebies`

**Callers (1):**

- `includes/honey_sales_report.php`:849 — `$freebiesBySale = honey_sales_load_checkout_freebies($pdo, $saleIds);`

## honey_sales_freebie_breakdown {#honey_sales_freebie_breakdown}

**Tables:** `pos_receipt_freebies`, `honey_bottle_pack_session_dept_items`, `pos_sales`, `honey_bottle_pack_sessions`

**Callers (1):**

- `includes/honey_sales_report.php`:1068 — `$freebies = honey_sales_freebie_breakdown($pdo, $range, $channel);`

## honey_sales_dept_breakdown {#honey_sales_dept_breakdown}

**Tables:** `pos_receipt_items`, `honey_bottle_pack_session_dept_items`, `products`, `pos_sales`, `honey_bottle_pack_sessions`

**Callers (2):**

- `includes/honey_sales_report.php`:1066 — `$dept = honey_sales_dept_breakdown($pdo, $range, $channel);`
- `includes/honey_sales_report.php`:1067 — `$deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (2):**

- `includes/honey_sales_report.php`:492 — `$sizeLabel = honey_sales_size_label($size);`
- `includes/honey_sales_report.php`:1674 — `$sizeLabel = honey_sales_size_label($bucket === 'liter' ? 'liter' : ($bucket === 'other' ? '' : $bucket));`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:828 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_load_checkout_barcodes {#honey_sales_load_checkout_barcodes}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/honey_sales_report.php`:831 — `$barcodesBySale = honey_sales_load_checkout_barcodes($pdo, $saleIds);`

## honey_sales_attach_barcodes_to_items {#honey_sales_attach_barcodes_to_items}

**Callers (1):**

- `includes/honey_sales_report.php`:833 — `$itemsBySale[$sid] = honey_sales_attach_barcodes_to_items($items, $barcodesBySale[$sid] ?? []);`

## honey_sales_pos_has_sold_by_user {#honey_sales_pos_has_sold_by_user}

**Callers (2):**

- `includes/honey_sales_report.php`:729 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`
- `includes/honey_sales_report.php`:794 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `users`, `pos_pins`, `products`

**Callers (4):**

- `api/v1/pos.php`:571 — `$checkouts = honey_sales_fetch_checkouts(`
- `api/v1/pos.php`:602 — `$found = honey_sales_fetch_checkouts($pdo, null, 1, (string) $receiptNo, 'all', 'all');`
- `includes/honey_sales_report.php`:1029 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType, $product);`
- `includes/honey_sales_report.php`:1142 — `$page1 = honey_sales_fetch_checkouts(`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:971 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:984 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:997 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:1010 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:102 — `$data = honey_sales_sold_snapshot(`

## honey_sales_fetch_checkouts_export {#honey_sales_fetch_checkouts_export}

**Callers (1):**

- `includes/honey_sales_report.php`:2529 — `$exported = honey_sales_fetch_checkouts_export($pdo, $period, $from, $to, $receipt, $saleType, $product);`

## honey_sales_checkout_rts_flags {#honey_sales_checkout_rts_flags}

**Tables:** `honey_sales_log`

**Callers (1):**

- `includes/honey_sales_report.php`:2532 — `$rtsFlags = honey_sales_checkout_rts_flags($pdo, $saleIds);`

## honey_sales_fetch_shipped_export {#honey_sales_fetch_shipped_export}

**Tables:** `honey_sales_log`, `honey_bottle_pack_session_dept_items`, `honey_bottle_units`, `honey_bottle_barcodes`, `online_order_parcels`, `users`, `honey_bottle_pack_sessions`

**Callers (1):**

- `includes/honey_sales_report.php`:2540 — `$shippedRows = honey_sales_fetch_shipped_export($pdo, $period, $from, $to, $product);`

## honey_sales_export_checkout_item_labels {#honey_sales_export_checkout_item_labels}

**Callers (2):**

- `includes/honey_sales_report.php`:1571 — `$labels = explode('; ', honey_sales_export_checkout_item_labels($items));`
- `includes/honey_sales_report.php`:2480 — `$sheet->setCellValue('E' . $r, honey_sales_export_checkout_item_labels($items));`

## honey_sales_export_checkout_item_qtys {#honey_sales_export_checkout_item_qtys}

**Callers (2):**

- `includes/honey_sales_report.php`:1572 — `$qtys = explode('; ', honey_sales_export_checkout_item_qtys($items));`
- `includes/honey_sales_report.php`:2481 — `$sheet->setCellValue('F' . $r, honey_sales_export_checkout_item_qtys($items));`

## honey_sales_export_checkout_items_summary {#honey_sales_export_checkout_items_summary}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## honey_sales_export_size_bucket {#honey_sales_export_size_bucket}

**Callers (2):**

- `includes/honey_sales_report.php`:1664 — `$bucket = honey_sales_export_size_bucket(`
- `includes/honey_sales_report.php`:1744 — `$bucket = $size === 'dept' ? 'dept' : honey_sales_export_size_bucket((string) $size);`

## honey_sales_export_summarize_walk_in {#honey_sales_export_summarize_walk_in}

**Callers (3):**

- `includes/honey_sales_report.php`:2431 — `$summary = honey_sales_export_summarize_walk_in($rows, $rtsFlags);`
- `includes/honey_sales_report.php`:2614 — `$wlkSum = honey_sales_export_summarize_walk_in($walkInRows, $rtsFlags);`
- `includes/honey_sales_report.php`:2642 — `$wlkSum = honey_sales_export_summarize_walk_in($wlkRows, $rtsFlags);`

## honey_sales_export_summarize_shipped {#honey_sales_export_summarize_shipped}

**Callers (3):**

- `includes/honey_sales_report.php`:2372 — `$summary = honey_sales_export_summarize_shipped($rows);`
- `includes/honey_sales_report.php`:2613 — `$shpSum = honey_sales_export_summarize_shipped($shippedRows);`
- `includes/honey_sales_report.php`:2641 — `$shpSum = honey_sales_export_summarize_shipped($shpRows);`

## honey_sales_export_write_summary_block {#honey_sales_export_write_summary_block}

**Callers (2):**

- `includes/honey_sales_report.php`:2374 — `$detailStart = honey_sales_export_write_summary_block(`
- `includes/honey_sales_report.php`:2433 — `$detailStart = honey_sales_export_write_summary_block(`

## honey_sales_export_chunk_rows {#honey_sales_export_chunk_rows}

**Callers (2):**

- `includes/honey_sales_report.php`:2726 — `foreach (honey_sales_export_chunk_rows($shpRows) as $i => $chunk) {`
- `includes/honey_sales_report.php`:2752 — `foreach (honey_sales_export_chunk_rows($wlkRows) as $i => $chunk) {`

## honey_sales_export_month_code {#honey_sales_export_month_code}

**Callers (5):**

- `includes/honey_sales_report.php`:1938 — `$label = honey_sales_export_month_code($sm) . ' ' . $sd . '-' . $ed;`
- `includes/honey_sales_report.php`:1940 — `$label = honey_sales_export_month_code($sm) . ' ' . $sd . '-'`
- `includes/honey_sales_report.php`:1941 — `. honey_sales_export_month_code($em) . ' ' . $ed;`
- `includes/honey_sales_report.php`:1999 — `$label = honey_sales_export_month_code((int) date('n', $ts)) . ' ' . (int) date('j', $ts);`
- `includes/honey_sales_report.php`:2130 — `$code = honey_sales_export_month_code($month);`

## honey_sales_export_week_meta {#honey_sales_export_week_meta}

**Callers (1):**

- `includes/honey_sales_report.php`:2076 — `$meta = honey_sales_export_week_meta($when);`

## honey_sales_export_unique_sheet_title {#honey_sales_export_unique_sheet_title}

**Callers (4):**

- `includes/honey_sales_report.php`:2690 — `$title = honey_sales_export_unique_sheet_title('No data', 'shp', 1, false, '', $usedTitles);`
- `includes/honey_sales_report.php`:2702 — `$title = honey_sales_export_unique_sheet_title('No data', 'wlk', 1, false, '', $usedTitles);`
- `includes/honey_sales_report.php`:2729 — `$title = honey_sales_export_unique_sheet_title(`
- `includes/honey_sales_report.php`:2755 — `$title = honey_sales_export_unique_sheet_title(`

## honey_sales_export_day_meta {#honey_sales_export_day_meta}

**Callers (1):**

- `includes/honey_sales_report.php`:2024 — `$meta = honey_sales_export_day_meta($when);`

## honey_sales_export_group_by_day {#honey_sales_export_group_by_day}

**Callers (2):**

- `includes/honey_sales_report.php`:2545 — `$shippedWeeks = honey_sales_export_group_by_day($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2546 — `$walkInWeeks = honey_sales_export_group_by_day($walkInRows, 'sold_at');`

## honey_sales_export_is_single_day {#honey_sales_export_is_single_day}

**Callers (1):**

- `includes/honey_sales_report.php`:2543 — `$singleDay = honey_sales_export_is_single_day($range);`

## honey_sales_export_group_by_week {#honey_sales_export_group_by_week}

**Callers (2):**

- `includes/honey_sales_report.php`:2548 — `$shippedWeeks = honey_sales_export_group_by_week($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2549 — `$walkInWeeks = honey_sales_export_group_by_week($walkInRows, 'sold_at');`

## honey_sales_export_month_name {#honey_sales_export_month_name}

**Callers (1):**

- `includes/honey_sales_report.php`:2131 — `$name = honey_sales_export_month_name($month);`

## honey_sales_export_group_by_month {#honey_sales_export_group_by_month}

**Callers (2):**

- `includes/honey_sales_report.php`:2551 — `$shippedMonths = honey_sales_export_group_by_month($shippedRows, 'when_packed');`
- `includes/honey_sales_report.php`:2552 — `$walkInMonths = honey_sales_export_group_by_month($walkInRows, 'sold_at');`

## honey_sales_export_write_channel_breakdown_block {#honey_sales_export_write_channel_breakdown_block}

**Callers (2):**

- `includes/honey_sales_report.php`:2311 — `$r = honey_sales_export_write_channel_breakdown_block(`
- `includes/honey_sales_report.php`:2318 — `$r = honey_sales_export_write_channel_breakdown_block(`

## honey_sales_export_use_per_month_summaries {#honey_sales_export_use_per_month_summaries}

**Callers (1):**

- `includes/honey_sales_report.php`:2600 — `$splitMonthSummaries = honey_sales_export_use_per_month_summaries($range, $monthOrder);`

## honey_sales_export_write_period_summary_sheet {#honey_sales_export_write_period_summary_sheet}

**Callers (2):**

- `includes/honey_sales_report.php`:2346 — `honey_sales_export_write_period_summary_sheet(`
- `includes/honey_sales_report.php`:2620 — `honey_sales_export_write_period_summary_sheet(`

## honey_sales_export_write_month_summary_sheet {#honey_sales_export_write_month_summary_sheet}

**Callers (1):**

- `includes/honey_sales_report.php`:2668 — `honey_sales_export_write_month_summary_sheet(`

## honey_sales_export_write_shipped_detail {#honey_sales_export_write_shipped_detail}

**Callers (2):**

- `includes/honey_sales_report.php`:2692 — `honey_sales_export_write_shipped_detail(`
- `includes/honey_sales_report.php`:2740 — `honey_sales_export_write_shipped_detail(`

## honey_sales_export_write_walk_in_detail {#honey_sales_export_write_walk_in_detail}

**Callers (2):**

- `includes/honey_sales_report.php`:2704 — `honey_sales_export_write_walk_in_detail(`
- `includes/honey_sales_report.php`:2766 — `honey_sales_export_write_walk_in_detail(`

## honey_sales_build_export_xlsx {#honey_sales_build_export_xlsx}

**Callers (1):**

- `api/v1/sales.php`:136 — `$path = honey_sales_build_export_xlsx(ims_pdo(), $f, $canViewAmounts);`

