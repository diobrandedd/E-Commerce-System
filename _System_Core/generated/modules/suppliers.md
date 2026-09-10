---
title: suppliers
source: includes/suppliers.php
generated: 2026-09-10T08:42:18+02:00
type: module
function_count: 12
---


# suppliers

> Source: `includes/suppliers.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/db.php`
- `includes/rts_storage.php`

## supplier_display_name {#supplier_display_name}

**Callers (13):**

- `api/v1/costing.php`:95 — `$row['supplier_display_name'] = supplier_display_name(`
- `api/v1/honey.php`:1189 — `'supplier_name' => supplier_display_name((string) $p['first_name'], (string) $p['last_name']),`
- `api/v1/honey.php`:1251 — `'supplier_name' => supplier_display_name((string) $p['first_name'], (string) $p['last_name']),`
- `includes/dept_inventory.php`:190 — `$supplierName = supplier_display_name(`
- `includes/dept_pricing.php`:156 — `$supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);`
- `includes/honey_inventory.php`:314 — `$supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);`
- `includes/honey_inventory.php`:1095 — `$supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);`
- `includes/honey_inventory.php`:1135 — `$supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);`
- `includes/honey_inventory.php`:1301 — `$supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);`
- `includes/honey_inventory.php`:1568 — `? supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name'])`
- `includes/honey_pricing.php`:133 — `$supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);`
- `includes/honey_warehouse_barcodes.php`:54 — `$supplierName = supplier_display_name((string) $supplier['first_name'], (string) $supplier['last_name']);`
- `includes/suppliers.php`:74 — `'display_name' => supplier_display_name((string) $row['first_name'], (string) $row['last_name']),`

## supplier_normalize_category {#supplier_normalize_category}

**Callers (3):**

- `includes/suppliers.php`:101 — `$params[] = supplier_normalize_category($category);`
- `includes/suppliers.php`:158 — `$category = supplier_normalize_category((string) ($fields['category'] ?? ''));`
- `includes/suppliers.php`:193 — `$category = supplier_normalize_category((string) ($fields['category'] ?? $row['category']));`

## supplier_category_label {#supplier_category_label}

**Callers (1):**

- `includes/suppliers.php`:78 — `'category_label' => supplier_category_label((string) $row['category']),`

## supplier_fetch {#supplier_fetch}

**Tables:** `suppliers`

**Callers (12):**

- `api/v1/suppliers.php`:52 — `$row = supplier_fetch(ims_pdo(), $id);`
- `includes/dept_pricing.php`:87 — `$supplier = supplier_fetch($pdo, $supplierId);`
- `includes/dept_pricing.php`:151 — `$supplier = supplier_fetch($pdo, $supplierId);`
- `includes/honey_inventory.php`:1133 — `$supplier = supplier_fetch($pdo, $supplierId);`
- `includes/honey_inventory.php`:1566 — `$supplier = supplier_fetch($pdo, $supplierId);`
- `includes/honey_pricing.php`:128 — `$supplier = supplier_fetch($pdo, $supplierId);`
- `includes/honey_pricing.php`:249 — `$supplier = supplier_fetch($pdo, $supplierId);`
- `includes/suppliers.php`:175 — `$row = supplier_fetch($pdo, (int) $pdo->lastInsertId());`
- `includes/suppliers.php`:182 — `$row = supplier_fetch($pdo, $id);`
- `includes/suppliers.php`:209 — `return supplier_serialize(supplier_fetch($pdo, $id), true);`
- `includes/suppliers.php`:215 — `$row = supplier_fetch($pdo, $id);`
- `includes/suppliers.php`:226 — `$row = supplier_fetch($pdo, $supplierId);`

## supplier_fetch_by_honey_code {#supplier_fetch_by_honey_code}

**Tables:** `suppliers`, `honey_warehouse_rows`

**Callers (1):**

- `api/v1/suppliers.php`:67 — `$row = supplier_fetch_by_honey_code(ims_pdo(), $code);`

## supplier_serialize {#supplier_serialize}

**Callers (6):**

- `api/v1/suppliers.php`:56 — `api_ok(['supplier' => supplier_serialize($row, true)]);`
- `api/v1/suppliers.php`:71 — `api_ok(['supplier' => supplier_serialize($row, true), 'supplier_code' => $code]);`
- `includes/suppliers.php`:117 — `return array_map(fn ($r) => supplier_serialize($r, $includePayment), $st->fetchAll());`
- `includes/suppliers.php`:177 — `return supplier_serialize($row, true);`
- `includes/suppliers.php`:209 — `return supplier_serialize(supplier_fetch($pdo, $id), true);`
- `includes/suppliers.php`:220 — `return supplier_serialize($row, true);`

## supplier_list {#supplier_list}

**Tables:** `suppliers`

**Callers (1):**

- `api/v1/suppliers.php`:36 — `$rows = supplier_list(`

## supplier_store_valid_id_upload {#supplier_store_valid_id_upload}

**Callers (2):**

- `includes/suppliers.php`:167 — `$idFile = supplier_store_valid_id_upload($file);`
- `includes/suppliers.php`:201 — `$idFile = supplier_store_valid_id_upload($file);`

## supplier_create {#supplier_create}

**Tables:** `suppliers`

**Callers (1):**

- `api/v1/suppliers.php`:84 — `$supplier = supplier_create(ims_pdo(), (int) $user['id'], $_POST, $file);`

## supplier_update {#supplier_update}

**Tables:** `suppliers`

**Callers (1):**

- `api/v1/suppliers.php`:99 — `$supplier = supplier_update(`

## supplier_set_active {#supplier_set_active}

**Tables:** `suppliers`

**Callers (2):**

- `api/v1/suppliers.php`:118 — `$supplier = supplier_set_active(ims_pdo(), (int) ($input['id'] ?? 0), true);`
- `api/v1/suppliers.php`:132 — `$supplier = supplier_set_active(ims_pdo(), (int) ($input['id'] ?? 0), false);`

## supplier_require_honey_active {#supplier_require_honey_active}

**Callers (4):**

- `includes/honey_inventory.php`:313 — `$supplier = supplier_require_honey_active($pdo, $supplierId);`
- `includes/honey_inventory.php`:1090 — `$supplier = supplier_require_honey_active($pdo, $supplierId);`
- `includes/honey_inventory.php`:1300 — `$supplier = supplier_require_honey_active($pdo, $supplierId);`
- `includes/honey_warehouse_barcodes.php`:53 — `$supplier = supplier_require_honey_active($pdo, $supplierId);`

