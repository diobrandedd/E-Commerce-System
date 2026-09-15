---
title: honey_sales_report
source: includes/honey_sales_report.php
generated: 2026-09-15T04:17:28+02:00
type: module
function_count: 15
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

**Callers (2):**

- `api/v1/pos.php`:556 — `$filters = honey_sales_resolve_filters($period !== '' ? $period : 'day', $from, $to);`
- `includes/honey_sales_report.php`:692 — `$filters = honey_sales_resolve_filters($period, $from, $to);`

## honey_sales_log_where_clause {#honey_sales_log_where_clause}

**Callers (2):**

- `includes/honey_sales_report.php`:705 — `[$logWhere, $logParams] = honey_sales_log_where_clause($range, $channel);`
- `includes/honey_sales_report.php`:786 — `[$shareWhere, $shareParams] = honey_sales_log_where_clause($range, 'all');`

## honey_sales_pos_checkout_where_clause {#honey_sales_pos_checkout_where_clause}

**Callers (1):**

- `includes/honey_sales_report.php`:760 — `[$salesWhere, $salesParams] = honey_sales_pos_checkout_where_clause($range, null, 'all');`

## honey_sales_normalize_product_filter {#honey_sales_normalize_product_filter}

**Callers (3):**

- `includes/honey_sales_report.php`:97 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:470 — `$product = honey_sales_normalize_product_filter($product);`
- `includes/honey_sales_report.php`:704 — `$product = honey_sales_normalize_product_filter($product);`

## honey_sales_product_exists_clause {#honey_sales_product_exists_clause}

**Tables:** `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:153 — `[$productSql, $productParams] = honey_sales_product_exists_clause($product);`

## honey_sales_checkout_where_clause {#honey_sales_checkout_where_clause}

**Tables:** `honey_sales_log`, `pos_receipt_items`, `products`

**Callers (1):**

- `includes/honey_sales_report.php`:471 — `[$where, $params] = honey_sales_checkout_where_clause($range, $receiptSearch, $saleType, $product);`

## honey_sales_dept_breakdown {#honey_sales_dept_breakdown}

**Tables:** `pos_receipt_items`, `products`, `pos_sales`

**Callers (2):**

- `includes/honey_sales_report.php`:806 — `$dept = honey_sales_dept_breakdown($pdo, $range, $channel);`
- `includes/honey_sales_report.php`:807 — `$deptShare = $channel === 'all' ? $dept : honey_sales_dept_breakdown($pdo, $range, 'all');`

## honey_sales_size_label {#honey_sales_size_label}

**Callers (1):**

- `includes/honey_sales_report.php`:251 — `$sizeLabel = honey_sales_size_label($size);`

## honey_sales_serialize_receipt_item {#honey_sales_serialize_receipt_item}

**Callers (1):**

- `includes/honey_sales_report.php`:584 — `$itemsBySale[$sid][] = honey_sales_serialize_receipt_item($item);`

## honey_sales_load_checkout_barcodes {#honey_sales_load_checkout_barcodes}

**Tables:** `honey_sales_log`, `honey_bottle_units`, `honey_bottle_barcodes`

**Callers (1):**

- `includes/honey_sales_report.php`:587 — `$barcodesBySale = honey_sales_load_checkout_barcodes($pdo, $saleIds);`

## honey_sales_attach_barcodes_to_items {#honey_sales_attach_barcodes_to_items}

**Callers (1):**

- `includes/honey_sales_report.php`:589 — `$itemsBySale[$sid] = honey_sales_attach_barcodes_to_items($items, $barcodesBySale[$sid] ?? []);`

## honey_sales_pos_has_sold_by_user {#honey_sales_pos_has_sold_by_user}

**Callers (2):**

- `includes/honey_sales_report.php`:485 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`
- `includes/honey_sales_report.php`:550 — `$hasUserSeller = honey_sales_pos_has_sold_by_user($pdo);`

## honey_sales_fetch_checkouts {#honey_sales_fetch_checkouts}

**Tables:** `pos_sales`, `pos_receipt_items`, `users`, `pos_pins`, `products`

**Callers (3):**

- `api/v1/pos.php`:557 — `$checkouts = honey_sales_fetch_checkouts(`
- `api/v1/pos.php`:588 — `$found = honey_sales_fetch_checkouts($pdo, null, 1, (string) $receiptNo, 'all', 'all');`
- `includes/honey_sales_report.php`:776 — `$checkouts = honey_sales_fetch_checkouts($pdo, $range, $page, $receiptSearch, $saleType, $product);`

## honey_sales_variant_breakdown {#honey_sales_variant_breakdown}

**Callers (4):**

- `includes/honey_sales_report.php`:718 — `$all = honey_sales_variant_breakdown($stAll->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:731 — `$regular = honey_sales_variant_breakdown($stRegular->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:744 — `$discount10 = honey_sales_variant_breakdown($stDiscount10->fetchAll(PDO::FETCH_ASSOC));`
- `includes/honey_sales_report.php`:757 — `$discount20 = honey_sales_variant_breakdown($stDiscount20->fetchAll(PDO::FETCH_ASSOC));`

## honey_sales_sold_snapshot {#honey_sales_sold_snapshot}

**Tables:** `honey_sales_log`, `pos_sales`

**Callers (1):**

- `api/v1/sales.php`:82 — `$data = honey_sales_sold_snapshot(`

