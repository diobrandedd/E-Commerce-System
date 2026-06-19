---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-06-19T07:05:39+02:00
type: module
function_count: 8
---


# honey_sales_report

> Source: `includes/honey_sales_report.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dashboard_inventory.php`

## honey_sales_resolve_filters {#honey_sales_resolve_filters}

**Callers (1):**

- `includes/honey_sales_report.php`:242 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (1):**

- `includes/honey_sales_report.php`:251 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range);`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:110 — `[$where, $params] = honey_sales_pos_checkout_where_clause($range, $receiptSearch, $saleType);`
- `includes/honey_sales_report.php`:306 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (1):**

- `includes/honey_sales_report.php`:83 — `'size_label' => honey_sales_size_label($size),`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:161 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `honey_sales_log`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:321 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType);`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:264 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:277 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:290 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:303 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:39 — `$data = honey_sales_sold_snapshot(`

