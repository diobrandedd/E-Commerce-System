---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-08-14T10:21:41+02:00
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

- `includes/honey_sales_report.php`:255 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:267 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);`
- `includes/honey_sales_report.php`:347 — `[$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:119 — `[$where, $params] = honey_sales_pos_checkout_where_clause($range, $receiptSearch, $saleType);`
- `includes/honey_sales_report.php`:322 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (1):**

- `includes/honey_sales_report.php`:92 — `'size_label' => honey_sales_size_label($size),`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:171 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `honey_sales_log`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:338 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType);`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:280 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:293 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:306 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:319 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:40 — `$data = honey_sales_sold_snapshot(`

