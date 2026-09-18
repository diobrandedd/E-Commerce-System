---
title: honey_volume
source: includes/honey_volume.php
generated: 2026-09-18T11:56:21+02:00
type: module
function_count: 13
---


# honey_volume

> Source: `includes/honey_volume.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/app_settings.php`

## honey_setting_parse_bool {#honey_setting_parse_bool}

**Callers (1):**

- `includes/honey_volume.php`:44 — `return honey_setting_parse_bool(app_setting_get($pdo, HONEY_PACKAGING_DEDUCT_SETTING_KEY, null), true);`

## honey_packaging_deduct_enabled {#honey_packaging_deduct_enabled}

**Callers (3):**

- `api/v1/settings.php`:258 — `'packaging_deduct' => honey_packaging_deduct_enabled($pdo),`
- `api/v1/settings.php`:275 — `$packagingDeduct = honey_packaging_deduct_enabled(ims_pdo());`
- `includes/honey_inventory.php`:1469 — `if (!honey_packaging_deduct_enabled($pdo)) {`

## honey_packaging_deduct_save {#honey_packaging_deduct_save}

**Callers (1):**

- `api/v1/settings.php`:277 — `$packagingDeduct = honey_packaging_deduct_save(`

## honey_price_per_liter_from_gallon {#honey_price_per_liter_from_gallon}

**Callers (1):**

- `includes/honey_pricing.php`:144 — `$pricePerLiter = round(honey_price_per_liter_from_gallon((float) $priceRow['price_per_gallon']), 6);`

## honey_ml_per_gallon {#honey_ml_per_gallon}

**Callers (8):**

- `api/v1/bottle_barcodes.php`:503 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `api/v1/settings.php`:254 — `'ml_per_gallon' => honey_ml_per_gallon($pdo),`
- `includes/honey_inventory.php`:353 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/honey_inventory.php`:388 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/honey_inventory.php`:1096 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/honey_inventory.php`:1329 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/honey_inventory.php`:1431 — `$mlPerGallon = honey_ml_per_gallon($pdo);`
- `includes/rts_damaged_use.php`:313 — `$volumeGallons = honey_lines_to_gallons($deductLines, honey_ml_per_gallon($pdo));`

## honey_ml_per_gallon_save {#honey_ml_per_gallon_save}

**Callers (1):**

- `api/v1/settings.php`:274 — `$saved = honey_ml_per_gallon_save(ims_pdo(), $ml, $userId);`

## honey_normalize_bottle_size {#honey_normalize_bottle_size}

**Callers (10):**

- `api/v1/_costing_helpers.php`:143 — `$norm = honey_normalize_bottle_size((string) $size);`
- `includes/catalog_bootstrap.php`:27 — `$norm = honey_normalize_bottle_size($size);`
- `includes/catalog_bootstrap.php`:50 — `return honey_bottle_size_ml(honey_normalize_bottle_size($size)) / 1000.0;`
- `includes/honey_inventory.php`:381 — `$size = honey_normalize_bottle_size($size);`
- `includes/honey_inventory.php`:1311 — `$size = honey_normalize_bottle_size($size);`
- `includes/honey_inventory.php`:1430 — `$size = honey_normalize_bottle_size($size);`
- `includes/honey_volume.php`:115 — `return match (honey_normalize_bottle_size($size)) {`
- `includes/honey_volume.php`:135 — `$size = honey_normalize_bottle_size((string) ($line['size'] ?? ''));`
- `includes/honey_volume.php`:205 — `'size' => honey_normalize_bottle_size((string) ($line['size'] ?? '')),`
- `includes/honey_volume.php`:221 — `'size' => honey_normalize_bottle_size((string) $size),`

## honey_bottle_size_ml {#honey_bottle_size_ml}

**Callers (3):**

- `api/v1/_costing_helpers.php`:145 — `return honey_bottle_size_ml($norm) / 1000.0;`
- `includes/catalog_bootstrap.php`:50 — `return honey_bottle_size_ml(honey_normalize_bottle_size($size)) / 1000.0;`
- `includes/honey_volume.php`:160 — `$total += $qty * honey_bottle_size_ml((string) ($line['size'] ?? ''), $mlPerGallon);`

## honey_aggregate_bottle_lines {#honey_aggregate_bottle_lines}

**Callers (5):**

- `api/v1/bottle_barcodes.php`:509 — `honey_aggregate_bottle_lines([['size' => $size, 'qty' => 1]]),`
- `includes/honey_inventory.php`:387 — `$sizeQtys = honey_aggregate_bottle_lines($lines);`
- `includes/honey_inventory.php`:1095 — `$sizeQtys = honey_aggregate_bottle_lines($lines);`
- `includes/honey_inventory.php`:1328 — `$sizeQtys = honey_aggregate_bottle_lines($lines);`
- `includes/honey_inventory.php`:1447 — `honey_aggregate_bottle_lines([['size' => $size, 'qty' => 1]]),`

## honey_lines_total_ml {#honey_lines_total_ml}

**Callers (1):**

- `includes/honey_volume.php`:184 — `return honey_ml_to_gallons(honey_lines_total_ml($lines, $mlPerGallon), $mlPerGallon);`

## honey_ml_to_gallons {#honey_ml_to_gallons}

**Callers (1):**

- `includes/honey_volume.php`:184 — `return honey_ml_to_gallons(honey_lines_total_ml($lines, $mlPerGallon), $mlPerGallon);`

## honey_lines_to_gallons {#honey_lines_to_gallons}

**Callers (6):**

- `api/v1/bottle_barcodes.php`:504 — `$usedGallons = honey_lines_to_gallons([['size' => $size, 'qty' => 1]], $mlPerGallon);`
- `includes/honey_inventory.php`:389 — `$usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);`
- `includes/honey_inventory.php`:1097 — `$usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);`
- `includes/honey_inventory.php`:1330 — `$usedGallons = honey_lines_to_gallons($lines, $mlPerGallon);`
- `includes/honey_inventory.php`:1432 — `$usedGallons = honey_lines_to_gallons([['size' => $size, 'qty' => 1]], $mlPerGallon);`
- `includes/rts_damaged_use.php`:313 — `$volumeGallons = honey_lines_to_gallons($deductLines, honey_ml_per_gallon($pdo));`

## honey_parse_bottle_lines_from_input {#honey_parse_bottle_lines_from_input}

**Callers (1):**

- `api/v1/honey.php`:533 — `$lines = honey_parse_bottle_lines_from_input($input);`

