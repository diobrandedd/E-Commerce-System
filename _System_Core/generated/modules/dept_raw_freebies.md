---
title: dept_raw_freebies
source: includes/dept_raw_freebies.php
generated: 2026-10-30T07:50:02+01:00
type: module
function_count: 14
---


# dept_raw_freebies

> Source: `includes/dept_raw_freebies.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`
- `includes/dept_products.php`
- `includes/dept_inventory.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`
- `includes/sku_barcodes.php`

## dept_raw_freebie_setting_key {#dept_raw_freebie_setting_key}

**Callers (2):**

- `includes/dept_raw_freebies.php`:211 — `$raw = app_setting_get($pdo, dept_raw_freebie_setting_key($productKey), null);`
- `includes/dept_raw_freebies.php`:233 — `app_setting_set($pdo, dept_raw_freebie_setting_key($productKey), (string) $grams, $userId);`

## dept_raw_freebie_product_allowed {#dept_raw_freebie_product_allowed}

**Callers (15):**

- `api/v1/honey.php`:1104 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `api/v1/honey.php`:1130 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `api/v1/honey.php`:1154 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:59 — `if ($row === false || !dept_raw_freebie_product_allowed((string) $row['product_key'])) {`
- `includes/dept_raw_freebies.php`:107 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:176 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:208 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:225 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:240 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:250 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:281 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:352 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/dept_raw_freebies.php`:410 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/inventory_check.php`:404 — `if (!dept_raw_freebie_product_allowed($productKey)) {`
- `includes/inventory_check_apply.php`:284 — `if (!dept_raw_freebie_product_allowed($productKey)) {`

## dept_raw_freebie_label {#dept_raw_freebie_label}

**Callers (8):**

- `api/v1/dept_pouch_barcodes.php`:323 — `api_fail('This freebie barcode belongs to ' . dept_raw_freebie_label($freebie['product_key']) . '.', 422);`
- `api/v1/dept_pouch_barcodes.php`:340 — `'label' => dept_raw_freebie_label($productKey),`
- `includes/barcode_verify.php`:35 — `'product_label' => dept_raw_freebie_label($productKey) . ' · ' . $grams . ' g · ' . $onHand . ' on hand',`
- `includes/dept_raw_freebies.php`:84 — `'label' => dept_raw_freebie_label($productKey),`
- `includes/honey_bottle_barcodes.php`:1424 — `$productLabel = $isFreebie ? dept_raw_freebie_label($productKey) : dept_pouch_product_label($productKey);`
- `includes/honey_bottle_barcodes.php`:1781 — `'Not enough ' . dept_raw_freebie_label($productKey) . ' on hand (' . $onHand`
- `includes/honey_bottle_barcodes.php`:1804 — `'product_label' => dept_raw_freebie_label($productKey),`
- `includes/sku_barcodes.php`:756 — `'label' => dept_raw_freebie_label($productKey),`

## dept_raw_freebie_barcode_fetch_by_code {#dept_raw_freebie_barcode_fetch_by_code}

**Tables:** `dept_raw_freebie_barcodes`

**Callers (7):**

- `api/v1/dept_pouch_barcodes.php`:320 — `$freebie = dept_raw_freebie_barcode_fetch_by_code($pdo, $code);`
- `includes/barcode_verify.php`:23 — `$freebie = dept_raw_freebie_barcode_fetch_by_code($pdo, $code);`
- `includes/honey_bottle_barcodes.php`:1727 — `$freebie = dept_raw_freebie_barcode_fetch_by_code($pdo, $code);`
- `includes/inventory_check.php`:479 — `$freebie = dept_raw_freebie_barcode_fetch_by_code($pdo, $code);`
- `includes/pack_cancel_restock.php`:255 — `if (dept_raw_freebie_barcode_fetch_by_code($pdo, $code) !== null) {`
- `includes/sku_barcodes.php`:562 — `if (dept_raw_freebie_barcode_fetch_by_code($pdo, $code) !== null) {`
- `includes/sku_barcodes.php`:747 — `$freebie = dept_raw_freebie_barcode_fetch_by_code($pdo, $code);`

## dept_raw_freebie_barcode_list {#dept_raw_freebie_barcode_list}

**Tables:** `dept_raw_freebie_barcodes`

**Callers (3):**

- `api/v1/sku_barcodes.php`:150 — `api_ok(['rows' => dept_raw_freebie_barcode_list(ims_pdo())]);`
- `includes/dept_raw_freebies.php`:166 — `return dept_raw_freebie_barcode_list($pdo);`
- `includes/dept_raw_freebies.php`:181 — `foreach (dept_raw_freebie_barcode_list($pdo) as $candidate) {`

## dept_raw_freebie_barcode_save_bulk {#dept_raw_freebie_barcode_save_bulk}

**Tables:** `product_sku_barcodes`, `honey_bottle_barcodes`, `dept_raw_freebie_barcodes`

**Callers (1):**

- `api/v1/sku_barcodes.php`:169 — `$rows = dept_raw_freebie_barcode_save_bulk(ims_pdo(), (int) $user['id'], $rawItems);`

## dept_raw_freebie_print_sheet_html {#dept_raw_freebie_print_sheet_html}

**Callers (1):**

- `api/v1/sku_barcodes.php`:182 — `$html = dept_raw_freebie_print_sheet_html(ims_pdo(), (string) ($_GET['product_key'] ?? ''));`

## dept_raw_freebie_grams {#dept_raw_freebie_grams}

**Callers (6):**

- `includes/barcode_verify.php`:26 — `$grams = rtrim(rtrim(number_format(dept_raw_freebie_grams($pdo, $productKey), 2, '.', ''), '0'), '.');`
- `includes/dept_raw_freebies.php`:86 — `'grams' => dept_raw_freebie_grams($pdo, $productKey),`
- `includes/dept_raw_freebies.php`:235 — `return dept_raw_freebie_grams($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:288 — `$gramsEach = dept_raw_freebie_grams($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:458 — `'freebie_grams' => dept_raw_freebie_grams($pdo, $productKey),`
- `includes/sku_barcodes.php`:758 — `'grams' => dept_raw_freebie_grams($pdo, $productKey),`

## dept_raw_freebie_grams_save {#dept_raw_freebie_grams_save}

**Callers (0):**

_No direct callers detected (may be entry point or dynamic call)._

## dept_raw_freebie_ensure_balance_row {#dept_raw_freebie_ensure_balance_row}

**Tables:** `dept_raw_freebie_balances`

**Callers (4):**

- `includes/dept_raw_freebies.php`:253 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:302 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:363 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`
- `includes/inventory_check_apply.php`:288 — `dept_raw_freebie_ensure_balance_row($pdo, $productKey);`

## dept_raw_freebie_on_hand {#dept_raw_freebie_on_hand}

**Tables:** `dept_raw_freebie_balances`

**Callers (11):**

- `includes/barcode_verify.php`:27 — `$onHand = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/dept_production.php`:990 — `$snap['freebie_on_hand'] = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/dept_production.php`:1001 — `$snap['freebie_on_hand'] = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/dept_production.php`:1012 — `$snap['freebie_on_hand'] = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/dept_raw_freebies.php`:330 — `'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),`
- `includes/dept_raw_freebies.php`:392 — `'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),`
- `includes/dept_raw_freebies.php`:457 — `'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),`
- `includes/honey_bottle_barcodes.php`:1778 — `$onHand = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/inventory_check.php`:1221 — `$systemQty = (float) dept_raw_freebie_on_hand($pdo, (string) $productKey);`
- `includes/inventory_check_apply.php`:289 — `$old = dept_raw_freebie_on_hand($pdo, $productKey);`
- `includes/sku_barcodes.php`:759 — `'freebie_on_hand' => dept_raw_freebie_on_hand($pdo, $productKey),`

## dept_raw_record_freebie_create {#dept_raw_record_freebie_create}

**Tables:** `dept_raw_freebie_log`, `dept_raw_freebie_balances`

**Callers (2):**

- `api/v1/dept_pouch_barcodes.php`:333 — `$created = dept_raw_record_freebie_create($pdo, (int) $user['id'], $productKey, $qty, 'Scan stock-in');`
- `api/v1/honey.php`:1112 — `$result = dept_raw_record_freebie_create($pdo, (int) $user['id'], $productKey, $qty, $reason);`

## dept_raw_record_freebie_give_out {#dept_raw_record_freebie_give_out}

**Tables:** `dept_raw_freebie_balances`, `dept_raw_freebie_log`

**Callers (2):**

- `api/v1/honey.php`:1138 — `$result = dept_raw_record_freebie_give_out($pdo, (int) $user['id'], $productKey, $qty, $reason);`
- `includes/honey_bottle_barcodes.php`:2220 — `dept_raw_record_freebie_give_out(`

## dept_raw_freebie_history {#dept_raw_freebie_history}

**Tables:** `dept_raw_freebie_log`, `users`

**Callers (1):**

- `api/v1/honey.php`:1163 — `$result = dept_raw_freebie_history(ims_pdo(), $productKey, $page, $perPage);`

