---
title: honey_warehouse_barcodes
source: includes/honey_warehouse_barcodes.php
generated: 2026-10-09T06:32:20+02:00
type: module
function_count: 12
---


# honey_warehouse_barcodes

> Source: `includes/honey_warehouse_barcodes.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/honey_inventory.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`

## honey_warehouse_barcode_normalize {#honey_warehouse_barcode_normalize}

**Callers (5):**

- `api/v1/honey.php`:475 — `'code' => honey_warehouse_barcode_normalize((string) ($_GET['code'] ?? '')),`
- `includes/honey_warehouse_barcodes.php`:156 — `$code = honey_warehouse_barcode_normalize($rawCode);`
- `includes/honey_warehouse_barcodes.php`:199 — `$code = honey_warehouse_barcode_normalize($rawCode);`
- `includes/honey_warehouse_barcodes.php`:301 — `$code = honey_warehouse_barcode_normalize($code);`
- `includes/honey_warehouse_barcodes.php`:346 — `$code = honey_warehouse_barcode_normalize((string) $request['supplier_code']);`

## honey_warehouse_barcode_date {#honey_warehouse_barcode_date}

**Callers (2):**

- `includes/honey_warehouse_barcodes.php`:51 — `$arrivalDate = honey_warehouse_barcode_date($arrivalDate);`
- `includes/honey_warehouse_barcodes.php`:200 — `$arrivalDate = honey_warehouse_barcode_date($arrivalDate);`

## honey_warehouse_barcode_quantity {#honey_warehouse_barcode_quantity}

**Callers (3):**

- `includes/honey_warehouse_barcodes.php`:52 — `$quantity = honey_warehouse_barcode_quantity($quantity);`
- `includes/honey_warehouse_barcodes.php`:302 — `$quantity = honey_warehouse_barcode_quantity($quantity);`
- `includes/honey_warehouse_barcodes.php`:347 — `$qty = honey_warehouse_barcode_quantity($request['quantity']);`

## honey_warehouse_barcode_request_create {#honey_warehouse_barcode_request_create}

**Tables:** `honey_warehouse_rows`, `honey_warehouse_barcode_requests`

**Callers (1):**

- `api/v1/honey.php`:351 — `'request' => honey_warehouse_barcode_request_create(`

## honey_warehouse_barcode_request_fetch {#honey_warehouse_barcode_request_fetch}

**Tables:** `honey_warehouse_barcode_requests`, `users`

**Callers (5):**

- `api/v1/honey.php`:449 — `$request = honey_warehouse_barcode_request_fetch($pdo, (int) ($_GET['request_id'] ?? 0));`
- `includes/honey_warehouse_barcodes.php`:72 — `return honey_warehouse_barcode_request_fetch($pdo, (int) $pdo->lastInsertId()) ?? [`
- `includes/honey_warehouse_barcodes.php`:134 — `$request = honey_warehouse_barcode_request_fetch($pdo, $requestId);`
- `includes/honey_warehouse_barcodes.php`:148 — `return honey_warehouse_barcode_request_fetch($pdo, $requestId) ?? $request;`
- `includes/honey_warehouse_barcodes.php`:336 — `$request = honey_warehouse_barcode_request_fetch($pdo, $requestId);`

## honey_warehouse_barcode_request_list {#honey_warehouse_barcode_request_list}

**Tables:** `honey_warehouse_barcode_requests`, `users`

**Callers (1):**

- `api/v1/honey.php`:330 — `'rows' => honey_warehouse_barcode_request_list(`

## honey_warehouse_barcode_request_generate {#honey_warehouse_barcode_request_generate}

**Tables:** `honey_warehouse_barcode_requests`

**Callers (2):**

- `api/v1/honey.php`:396 — `$request = honey_warehouse_barcode_request_generate(`
- `includes/honey_warehouse_barcodes.php`:388 — `$requests[] = honey_warehouse_barcode_request_generate($pdo, $userId, $requestId);`

## honey_warehouse_barcode_lookup {#honey_warehouse_barcode_lookup}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:476 — `'candidates' => honey_warehouse_barcode_lookup(`

## honey_warehouse_barcode_stock_out {#honey_warehouse_barcode_stock_out}

**Tables:** `honey_warehouse_rows`

**Callers (1):**

- `api/v1/honey.php`:494 — `'stock_out' => honey_warehouse_barcode_stock_out(`

## honey_warehouse_barcode_print_sheet_html {#honey_warehouse_barcode_print_sheet_html}

**Callers (1):**

- `api/v1/honey.php`:456 — `$html = honey_warehouse_barcode_print_sheet_html(`

## honey_warehouse_barcode_combined_print_sheet_html {#honey_warehouse_barcode_combined_print_sheet_html}

**Callers (1):**

- `api/v1/honey.php`:447 — `$html = honey_warehouse_barcode_combined_print_sheet_html($pdo, $ids);`

## honey_warehouse_barcode_request_generate_many {#honey_warehouse_barcode_request_generate_many}

**Callers (1):**

- `api/v1/honey.php`:425 — `api_ok(honey_warehouse_barcode_request_generate_many(`

