---
title: dashboard_inventory
source: includes/dashboard_inventory.php
generated: 2026-09-18T11:55:59+02:00
type: module
function_count: 6
---


# dashboard_inventory

> Source: `includes/dashboard_inventory.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/dept_products.php`

## dashboard_inventory_resolve_range {#dashboard_inventory_resolve_range}

**Callers (3):**

- `api/v1/dashboard.php`:320 — `$range = dashboard_inventory_resolve_range($period, null, null);`
- `includes/dashboard_inventory.php`:214 — `$range = dashboard_inventory_resolve_range($period, $from, $to);`
- `includes/honey_sales_report.php`:36 — `$range = dashboard_inventory_resolve_range($period, $from, $to);`

## dashboard_honey_packaging_snapshot {#dashboard_honey_packaging_snapshot}

**Tables:** `honey_packaging_rows`

**Callers (1):**

- `includes/dashboard_inventory.php`:223 — `'honey_packaging' => dashboard_honey_packaging_snapshot($pdo, $range),`

## dashboard_dept_product_definitions {#dashboard_dept_product_definitions}

**Callers (1):**

- `includes/dashboard_inventory.php`:186 — `foreach (dashboard_dept_product_definitions() as $def) {`

## dashboard_label_matches_product {#dashboard_label_matches_product}

**Callers (1):**

- `includes/dashboard_inventory.php`:197 — `if ($rowKey === '' && dashboard_label_matches_product((string) $row['product_label'], $def['patterns'])) {`

## dashboard_dept_products_snapshot {#dashboard_dept_products_snapshot}

**Tables:** `dept_inventory_rows`, `departments`

**Callers (1):**

- `includes/dashboard_inventory.php`:224 — `'dept_products' => dashboard_dept_products_snapshot($pdo, $range),`

## dashboard_inventory_snapshot {#dashboard_inventory_snapshot}

**Callers (1):**

- `api/v1/dashboard.php`:275 — `$snapshot = dashboard_inventory_snapshot(ims_pdo(), $period, $from, $to);`

